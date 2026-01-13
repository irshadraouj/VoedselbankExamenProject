<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Delivery_manager_act
{
    public function __construct()
    {
        ee()->load->helper('text');
    }

    public function accept_delivery()
    {
        // Require login
        $member_id = (int) ee()->session->userdata('member_id');
        if (! $member_id) {
            return $this->deny();
        }

        $entry_id = (int) ee()->input->post('entry_id');
        if (! $entry_id) {
            return $this->error_redirect('Ongeldige levering.');
        }

        $suppliers_channel = ee('Model')->get('Channel')->filter('channel_name', 'suppliers_order')->first();
        if (! $suppliers_channel) {
            return $this->error_redirect('Channel "suppliers_order" niet gevonden.');
        }

        // Load suppliers_order entry
        $order = ee('Model')
            ->get('ChannelEntry')
            ->filter('entry_id', $entry_id)
            ->filter('channel_id', (int) $suppliers_channel->channel_id)
            ->first();

        if (! $order) {
            return $this->error_redirect('Levering niet gevonden.');
        }

        $status = (string) ($order->delivery_status ?? '');
        if (in_array($status, ['received', 'archived'], true)) {
            return $this->error_redirect('Deze levering is al verwerkt.');
        }

        $delivery_date = $order->delivery_date ?? null;
        if (empty($delivery_date) || $delivery_date === '0' || $delivery_date === 0) {
            return $this->error_redirect('Leveringsdatum ontbreekt.');
        }

        $products_channel = ee('Model')->get('Channel')->filter('channel_name', 'producten')->first();
        if (! $products_channel) {
            return $this->error_redirect('Channel "producten" niet gevonden.');
        }

        $products_grid_field = ee('Model')->get('ChannelField')->filter('field_name', 'products')->first();
        if (! $products_grid_field) {
            return $this->error_redirect('Grid field "products" niet gevonden.');
        }

        $grid_rows = $this->get_grid_rows((int) $products_grid_field->field_id, (int) $order->entry_id);

        $now = ee()->localize->now;

        ee()->db->trans_begin();

        try {
            foreach ($grid_rows as $row) {
                $delivered_amount = (int) ($row['delivered_amount'] ?? 0);
                if ($delivered_amount <= 0) {
                    continue;
                }

                $product_entry = null;

                // 1) Try relationship
                $rel_id = $this->parse_relationship_id($row['product_rel'] ?? null);
                if ($rel_id) {
                    $candidate = ee('Model')->get('ChannelEntry')->filter('entry_id', $rel_id)->first();
                    if ($candidate && (int) $candidate->channel_id === (int) $products_channel->channel_id) {
                        $product_entry = $candidate;
                    }
                }

                // 2) Try EAN
                $ean = trim((string) ($row['product_ean'] ?? ''));
                if (! $product_entry && $ean !== '') {
                    $product_entry = ee('Model')
                        ->get('ChannelEntry')
                        ->filter('channel_id', (int) $products_channel->channel_id)
                        ->filter('product_ean', $ean)
                        ->first();
                }

                // 3) Create product if needed
                if (! $product_entry) {
                    $name = trim((string) ($row['product_name'] ?? ''));
                    $cat = $row['product_cat'] ?? '';

                    $title = $name !== '' ? $name : ($ean !== '' ? $ean : 'Nieuw product');
                    $url_title = $this->slugify($title);
                    $url_title .= '-' . strtolower(substr(md5(uniqid('', true)), 0, 6));

                    $product_entry = ee('Model')->make('ChannelEntry');
                    $product_entry->site_id = ee()->config->item('site_id');
                    $product_entry->channel_id = (int) $products_channel->channel_id;
                    $product_entry->author_id = $member_id;
                    $product_entry->title = $title;
                    $product_entry->url_title = $url_title;
                    $product_entry->status = 'open';

                    if ($name !== '') {
                        $product_entry->set(['product_name' => $name]);
                    }
                    if ($ean !== '') {
                        $product_entry->set(['product_ean' => $ean]);
                    }
                    if ($cat !== '' && $cat !== null) {
                        $product_entry->set(['product_cat' => $cat]);
                    }

                    // Start voorraad at 0; we'll add delivered_amount below
                    $product_entry->set(['product_amount' => 0]);

                    $product_entry->save();
                }

                $current_amount = (int) ($product_entry->product_amount ?? 0);
                $new_amount = $current_amount + $delivered_amount;

                $product_entry->set(['product_amount' => $new_amount]);
                $product_entry->save();
            }

            $order->set([
                'delivery_status' => 'received',
                'approved_at' => $now,
                'approved_by' => $member_id,
                'received_at' => $now,
                'received_by' => $member_id,
            ]);
            $order->save();

            if (ee()->db->trans_status() === false) {
                ee()->db->trans_rollback();
                return $this->error_redirect('Opslaan mislukt.');
            }

            ee()->db->trans_commit();
        } catch (Throwable $e) {
            ee()->db->trans_rollback();
            ee()->logger->developer('Delivery_manager accept_delivery error: ' . $e->getMessage());
            return $this->error_redirect('Er is iets misgegaan bij het verwerken van de levering.');
        }

        $return_url = ee()->input->post('return_url');
        if (! $return_url) {
            $return_url = ee()->functions->form_backtrack('-1');
        }

        ee()->functions->redirect($return_url);
    }

    public function create_delivery()
    {
        // Require login
        $member_id = (int) ee()->session->userdata('member_id');
        if (! $member_id) {
            return $this->deny();
        }

        $suppliers_channel = ee('Model')->get('Channel')->filter('channel_name', 'suppliers_order')->first();
        if (! $suppliers_channel) {
            return $this->error_redirect('Channel "suppliers_order" niet gevonden.');
        }

        $products_grid_field = ee('Model')->get('ChannelField')->filter('field_name', 'products')->first();
        if (! $products_grid_field) {
            return $this->error_redirect('Grid field "products" niet gevonden.');
        }

        $delivery_date = (string) ee()->input->post('delivery_date');
        if ($delivery_date === '') {
            return $this->error_redirect('Leveringsdatum ontbreekt.');
        }

        $delivery_ts = strtotime($delivery_date . ' 00:00:00');
        if (! $delivery_ts) {
            return $this->error_redirect('Leveringsdatum is ongeldig.');
        }

        $supplier_title = trim((string) ee()->input->post('supplier_title'));
        $custom_title = trim((string) ee()->input->post('title'));

        $title = $custom_title !== '' ? $custom_title : ($supplier_title !== '' ? ('Levering - ' . $supplier_title) : 'Levering');
        $url_title = $this->slugify($title) . '-' . strtolower(substr(md5(uniqid('', true)), 0, 6));

        $items = ee()->input->post('items');
        if (! is_array($items)) {
            $items = [];
        }

        // Require at least 1 item with amount
        $has_any = false;
        foreach ($items as $item) {
            if (is_array($item) && (int) ($item['delivered_amount'] ?? 0) > 0) {
                $has_any = true;
                break;
            }
        }
        if (! $has_any) {
            return $this->error_redirect('Voeg minimaal 1 product met een aantal toe.');
        }

        // Map grid columns by name -> col_id
        $cols = ee()->db
            ->select('col_id, col_name')
            ->from('grid_columns')
            ->where('field_id', (int) $products_grid_field->field_id)
            ->get()
            ->result_array();

        $col_id_by_name = [];
        foreach ($cols as $col) {
            $col_id_by_name[(string) $col['col_name']] = (int) $col['col_id'];
        }

        $grid_table = ee()->db->dbprefix('channel_grid_field_' . (int) $products_grid_field->field_id);

        $products_channel = ee('Model')->get('Channel')->filter('channel_name', 'producten')->first();
        if (! $products_channel) {
            return $this->error_redirect('Channel "producten" niet gevonden.');
        }

        $now = ee()->localize->now;

        ee()->db->trans_begin();

        try {
            $entry = ee('Model')->make('ChannelEntry');
            $entry->site_id = ee()->config->item('site_id');
            $entry->channel_id = (int) $suppliers_channel->channel_id;
            $entry->author_id = $member_id;
            $entry->title = $title;
            $entry->url_title = $url_title;
            $entry->status = 'open';

            $entry->set([
                'delivery_status' => 'pending',
                'delivery_date' => (int) $delivery_ts,
            ]);

            // Optional metadata: we set created time implicitly; don't set approved/received here
            $entry->save();

            $row_order = 1;
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $delivered_amount = (int) ($item['delivered_amount'] ?? 0);
                if ($delivered_amount <= 0) {
                    continue;
                }

                $product_rel = $this->parse_relationship_id($item['product_rel'] ?? null);
                $product_name = trim((string) ($item['product_name'] ?? ''));
                $product_ean = trim((string) ($item['product_ean'] ?? ''));
                $product_cat = trim((string) ($item['product_cat'] ?? ''));

                // If no relationship was selected, create/resolve a product NOW so it appears in Producten.
                if (! $product_rel && ($product_ean !== '' || $product_name !== '')) {
                    $existing_product = null;

                    if ($product_ean !== '') {
                        $existing_product = ee('Model')
                            ->get('ChannelEntry')
                            ->filter('channel_id', (int) $products_channel->channel_id)
                            ->filter('product_ean', $product_ean)
                            ->first();
                    }

                    if (! $existing_product && $product_name !== '') {
                        // best-effort match on name
                        $existing_product = ee('Model')
                            ->get('ChannelEntry')
                            ->filter('channel_id', (int) $products_channel->channel_id)
                            ->filter('product_name', $product_name)
                            ->first();
                    }

                    if ($existing_product) {
                        $product_rel = (int) $existing_product->entry_id;
                    } else {
                        $title_for_product = $product_name !== '' ? $product_name : $product_ean;
                        $new_url_title = $this->slugify($title_for_product) . '-' . strtolower(substr(md5(uniqid('', true)), 0, 6));

                        $new_product = ee('Model')->make('ChannelEntry');
                        $new_product->site_id = ee()->config->item('site_id');
                        $new_product->channel_id = (int) $products_channel->channel_id;
                        $new_product->author_id = $member_id;
                        $new_product->title = $title_for_product;
                        $new_product->url_title = $new_url_title;
                        $new_product->status = 'open';

                        if ($product_name !== '') {
                            $new_product->set(['product_name' => $product_name]);
                        }
                        if ($product_ean !== '') {
                            $new_product->set(['product_ean' => $product_ean]);
                        }
                        if ($product_cat !== '') {
                            $new_product->set(['product_cat' => $product_cat]);
                        }

                        // Start at 0; stock is updated only on accept_delivery.
                        $new_product->set(['product_amount' => 0]);
                        $new_product->save();

                        $product_rel = (int) $new_product->entry_id;
                    }
                }

                $row = [
                    'entry_id' => (int) $entry->entry_id,
                    'row_order' => $row_order,
                ];

                $maybe_set = function (string $col_name, $value) use (&$row, $col_id_by_name): void {
                    if (! array_key_exists($col_name, $col_id_by_name)) {
                        return;
                    }
                    $col_id = $col_id_by_name[$col_name];
                    $row['col_id_' . $col_id] = $value;
                };

                $maybe_set('product_rel', $product_rel ? (string) $product_rel : '');
                $maybe_set('product_name', $product_name);
                $maybe_set('product_ean', $product_ean);
                $maybe_set('product_cat', $product_cat);
                $maybe_set('delivered_amount', $delivered_amount);

                ee()->db->insert($grid_table, $row);
                $row_order++;
            }

            if (ee()->db->trans_status() === false) {
                ee()->db->trans_rollback();
                return $this->error_redirect('Opslaan mislukt.');
            }

            ee()->db->trans_commit();
        } catch (Throwable $e) {
            ee()->db->trans_rollback();
            ee()->logger->developer('Delivery_manager create_delivery error: ' . $e->getMessage());
            return $this->error_redirect('Er is iets misgegaan bij het aanmaken van de levering.');
        }

        $return_url = ee()->input->post('return_url');
        if (! $return_url) {
            $return_url = ee()->functions->form_backtrack('-1');
        }

        ee()->functions->redirect($return_url);
    }

    private function get_grid_rows(int $field_id, int $entry_id): array
    {
        $cols = ee()->db
            ->select('col_id, col_name')
            ->from('grid_columns')
            ->where('field_id', $field_id)
            ->get()
            ->result_array();

        $col_map = [];
        foreach ($cols as $col) {
            $col_map[(int) $col['col_id']] = $col['col_name'];
        }

        $grid_table = ee()->db->dbprefix('channel_grid_field_' . $field_id);

        $rows = ee()->db
            ->from($grid_table)
            ->where('entry_id', $entry_id)
            ->order_by('row_order', 'asc')
            ->get()
            ->result_array();

        $out = [];
        foreach ($rows as $row) {
            $row_data = [];
            foreach ($col_map as $col_id => $name) {
                $key = 'col_id_' . $col_id;
                $row_data[$name] = $row[$key] ?? null;
            }
            $out[] = $row_data;
        }

        return $out;
    }

    private function parse_relationship_id($value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $id = (int) $value;
            return $id > 0 ? $id : null;
        }

        $value = (string) $value;
        if ($value === '') {
            return null;
        }

        if (preg_match('/(\d{1,})/', $value, $m)) {
            $id = (int) $m[1];
            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function slugify(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
        $value = trim($value, '-');
        return $value !== '' ? $value : 'item';
    }

    private function deny()
    {
        ee()->output->set_status_header(403);
        return ee()->output->show_user_error('general', ['Je moet ingelogd zijn om dit te doen.']);
    }

    private function error_redirect(string $message)
    {
        ee()->session->set_flashdata('delivery_manager_error', $message);

        $return_url = ee()->input->post('return_url');
        if (! $return_url) {
            $return_url = ee()->functions->form_backtrack('-1');
        }

        ee()->functions->redirect($return_url);
    }
}
