<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Pakket_ext
{
    public $settings = array();
    public $description = 'Post-process createdorders: fill grid, generate unicode, adjust stock';
    public $docs_url = '';
    public $name = 'Pakket';
    public $version = '1.0.0';
    public $settings_exist = 'n';

    public function __construct($settings = '')
    {
        $this->settings = $settings;
    }

    public function activate_extension()
    {
        // Register both CP publish and Channel Form hooks
        $hooks = array(
            'entry_submission_end' => 'entry_submission_end',
            'channel_form_submit_entry_end' => 'channel_form_submit_entry_end',
        );

        foreach ($hooks as $hook => $method) {
            $exists = ee()->db->get_where('extensions', array(
                'class'  => __CLASS__,
                'method' => $method,
                'hook'   => $hook,
            ))->num_rows();

            if (! $exists) {
                ee()->db->insert('extensions', array(
                    'class'    => __CLASS__,
                    'method'   => $method,
                    'hook'     => $hook,
                    'settings' => serialize($this->settings),
                    'priority' => 10,
                    'version'  => $this->version,
                    'enabled'  => 'y',
                ));
            }
        }
    }

    public function disable_extension()
    {
        ee()->db->delete('extensions', array('class' => __CLASS__));
    }

    public function update_extension($current = '')
    {
        if ($current == '' || $current == $this->version) {
            return false;
        }

        ee()->db->where('class', __CLASS__);
        ee()->db->update('extensions', array('version' => $this->version));
    }

    /**
     * Hook: entry_submission_end
     * @param int $entry_id
     * @param array $meta
     * @param array $data
     */
    public function entry_submission_end($entry_id, $meta, $data)
    {
        // Only run for the createdorders channel
        if (empty($meta) || empty($meta['channel_id'])) {
            return;
        }

        $channel_id = isset($meta['channel_id']) ? (int) $meta['channel_id'] : 0;
        if (! $channel_id) {
            return;
        }

        $this->process_order($entry_id, $channel_id);
    }

    /**
     * Hook: channel_form_submit_entry_end (Channel Form front-end)
     * @param object $obj Channel_form_lib instance
     */
    public function channel_form_submit_entry_end($obj)
    {
        if (! $obj) {
            return;
        }

        $entry_id = (int) $obj->entry('entry_id');
        $channel_id = (int) $obj->entry('channel_id');

        if (! $entry_id || ! $channel_id) {
            return;
        }

        $this->process_order($entry_id, $channel_id);
    }

    /**
     * Core order processing logic shared by both hooks
     */
    protected function process_order($entry_id, $channel_id)
    {
        $channel = ee('Model')->get('Channel', $channel_id)->first();
        if (! $channel || $channel->channel_name !== 'createdorders') {
            return;
        }

        // Resolve custom fields that belong to this channel
        $unicode_field = null;
        $order_items_field = null;

        // Support multiple possible short-names for the unicode field
        $unicode_field_candidates = array('order_unicode', 'order_Unicode', 'order_UniCode');

        foreach ($channel->getAllCustomFields() as $field) {
            if (! $unicode_field && in_array($field->field_name, $unicode_field_candidates, true)) {
                $unicode_field = $field;
            }

            if (! $order_items_field && $field->field_name === 'order_items') {
                $order_items_field = $field;
            }
        }

        // Get posted values
        $order_items_json = ee()->input->post('order_items');
        $order_bsn = ee()->input->post('order_bsnconect') ?: ee()->input->post('order_bsn') ?: '';
        $order_name = ee()->input->post('order_name') ?: '';

            // Generate unique order Unicode (lowercase so it matches URL suffix)
            $order_unicode = strtolower(substr(md5(uniqid('', true)), 0, 8));

        // Update entry title, url_title and custom unicode field via the model
        $entry = ee('Model')->get('ChannelEntry', $entry_id)->first();
        if ($entry) {
            if ($order_name || $order_bsn) {
                $entry->title = trim($order_name . ' ' . $order_bsn);
            }

                // Base slug op klantnaam (fallback op 'order')
                $base = $order_name ?: 'order';
                $slug = strtolower($base);
                // simpele slugify
                $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
                $slug = trim($slug, '-');
                if ($slug === '') {
                    $slug = 'order';
                }

                // URL_title = slug + '-' + order_unicode
                $entry->url_title = $slug . '-' . $order_unicode;

            // Schrijf de unicode-waarde weg naar het juiste veld (als gevonden)
            if ($unicode_field) {
                $entry->set([$unicode_field->field_name => $order_unicode]);
                // extra zekerheid: direct property ook vullen
                $entry->{$unicode_field->field_name} = $order_unicode;
            }

            $entry->save();
        }

        // Parse items payload
        $items = json_decode($order_items_json, true);
        if (! $order_items_field || ! is_array($items) || ! count($items)) {
            return;
        }

        // Insert Grid rows for order_items
        $grid_field_id = $order_items_field->field_id;
        $cols = ee()->db->get_where('grid_columns', array('field_id' => $grid_field_id))->result_array();
        $col_map = array();
        foreach ($cols as $c) {
            $col_map[$c['col_name']] = $c['col_id'];
        }

        // EE 7+ slaat Grid-data op in exp_channel_grid_field_{field_id}
        $grid_table = ee()->db->dbprefix('channel_grid_field_' . $grid_field_id);

        $row_order = 0;
        foreach ($items as $item) {
            $row = array(
                'entry_id' => $entry_id,
                'row_order' => $row_order,
            );

            if (isset($col_map['order_item_name']) && ! empty($item['name'])) {
                $row['col_id_' . $col_map['order_item_name']] = $item['name'];
            }

            if (isset($col_map['order_item_total']) && isset($item['qty'])) {
                $row['col_id_' . $col_map['order_item_total']] = (int) $item['qty'];
            }

            ee()->db->insert($grid_table, $row);
            $row_order++;
        }

        // Adjust product inventory (producten channel) based on selected items
        $product_amount_field = ee()->db->get_where('channel_fields', array('field_name' => 'product_amount'))->row();
        if (! $product_amount_field) {
            return;
        }

        foreach ($items as $item) {
            if (empty($item['entryId'])) {
                continue;
            }

            $product_entry = ee('Model')->get('ChannelEntry', $item['entryId'])->first();
            if (! $product_entry) {
                continue;
            }

            $field_name = $product_amount_field->field_name;
            $current = (int) $product_entry->{$field_name};
            $new = max(0, $current - (int) $item['qty']);

            $product_entry->set([$field_name => $new]);
            $product_entry->save();
        }
    }
}
