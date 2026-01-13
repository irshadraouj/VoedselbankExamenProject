<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Delivery_manager
{
    public function __construct()
    {
        ee()->load->helper('text');

        // In front-end ACT requests, ee()->logger is not always preloaded.
        if (! isset(ee()->logger)) {
            ee()->load->library('logger');
        }
    }

    /**
     * Template tag: outputs the action URL for the accept_delivery action.
     * Usage: {exp:delivery_manager:action_url}
     */
    public function action_url()
    {
        $action_id = ee()->functions->fetch_action_id('Delivery_manager', 'accept_delivery');

        if (! $action_id) {
            return '';
        }

        return ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id;
    }

    /**
     * Template tag: outputs the action URL for the create_delivery action.
     * Usage: {exp:delivery_manager:create_delivery_action_url}
     */
    public function create_delivery_action_url()
    {
        $action_id = ee()->functions->fetch_action_id('Delivery_manager', 'create_delivery');

        if (! $action_id) {
            return '';
        }

        return ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id;
    }

    /**
     * Template tag: outputs the CP URL to create a new delivery (suppliers_order entry).
     * Usage: {exp:delivery_manager:cp_create_delivery_url}
     */
    public function cp_create_delivery_url()
    {
        return $this->cp_create_entry_url_for_channel('suppliers_order');
    }

    /**
     * Template tag: outputs the CP URL to create a new product (producten entry).
     * Usage: {exp:delivery_manager:cp_create_product_url}
     */
    public function cp_create_product_url()
    {
        return $this->cp_create_entry_url_for_channel('producten');
    }

    /**
     * Template tag: outputs <option> list for product categories.
     * Reads options from the `product_cat` field's list items in the CMS.
     * Usage: <select name="...">{exp:delivery_manager:product_cat_options}</select>
     * Optional param: selected="..."
     */
    public function product_cat_options()
    {
        $field = ee('Model')->get('ChannelField')->filter('field_name', 'product_cat')->first();
        if (! $field) {
            return '';
        }

        $selected = ee()->TMPL->fetch_param('selected');
        $list_items = (string) ($field->field_list_items ?? '');
        $lines = preg_split("/\r\n|\r|\n/", $list_items);

        $out = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $label = $line;
            $value = $line;

            if (strpos($line, '|') !== false) {
                [$label, $value] = array_map('trim', explode('|', $line, 2));
                if ($value === '') {
                    $value = $label;
                }
            }

            $sel = ($selected !== false && $selected !== null && (string) $selected === (string) $value) ? ' selected' : '';

            $out[] = '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                . '</option>';
        }

        return implode("\n", $out);
    }

    /**
     * Template tag: outputs flashdata by key.
     * Usage: {exp:delivery_manager:flash key="delivery_manager_error"}
     */
    public function flash()
    {
        $key = (string) ee()->TMPL->fetch_param('key');
        if ($key === '') {
            return '';
        }

        $val = ee()->session->flashdata($key);
        return is_string($val) ? $val : '';
    }

    /**
     * Action: POST endpoint to accept a delivery and update stock.
     * NOTE: EE action dispatch calls methods on the module class.
     */
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

        if ((string) ($order->status ?? '') === 'closed') {
            return $this->error_redirect('Deze levering is al verwerkt.');
        }

        $supplie_date = $order->supplie_date ?? null;
        if (empty($supplie_date) || $supplie_date === '0' || $supplie_date === 0) {
            return $this->error_redirect('Leveringsdatum ontbreekt.');
        }

        $products_channel = ee('Model')->get('Channel')->filter('channel_name', 'producten')->first();
        if (! $products_channel) {
            return $this->error_redirect('Channel "producten" niet gevonden.');
        }

        $now = (int) ee()->localize->now;

        $products_grid_field = ee('Model')->get('ChannelField')->filter('field_name', 'products')->first();
        if (! $products_grid_field) {
            return $this->error_redirect('Grid field "products" niet gevonden.');
        }

        $grid_rows = $this->get_grid_rows((int) $products_grid_field->field_id, (int) $order->entry_id);

        ee()->db->trans_begin();

        try {
            foreach ($grid_rows as $row) {
                // In this install, the delivery Grid uses `product_amount` as the delivered quantity.
                $delivered_amount = (int) ($row['product_amount'] ?? 0);
                if ($delivered_amount <= 0) {
                    continue;
                }

                $product_entry = null;

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
                    $product_entry->entry_date = $now;
                    $product_entry->edit_date = $now;

                    if ($name !== '') {
                        $product_entry->set(['product_name' => $name]);
                    }
                    if ($ean !== '') {
                        $product_entry->set(['product_ean' => $ean]);
                    }
                    if ($cat !== '' && $cat !== null) {
                        // product_cat is a multi_select in this install; set as array for safety.
                        $product_entry->set(['product_cat' => is_array($cat) ? $cat : [$cat]]);
                    }

                    // Start voorraad at 0; we'll add delivered_amount below
                    $product_entry->set(['product_amount' => 0]);

                    $result = $product_entry->validate();
                    if (! $result->isValid()) {
                        $all = $result->getAllErrors();
                        $flat = [];
                        foreach ($all as $field => $errors) {
                            foreach ((array) $errors as $err) {
                                $flat[] = $field . ': ' . $err;
                            }
                        }
                        throw new RuntimeException('Product validation failed: ' . implode(' | ', $flat));
                    }

                    if (! $product_entry->save()) {
                        throw new RuntimeException('Product save() returned false');
                    }
                }

                $current_amount = (int) ($product_entry->product_amount ?? 0);
                $new_amount = $current_amount + $delivered_amount;

                $product_entry->set(['product_amount' => $new_amount]);
                $product_entry->save();
            }

            $order->status = 'closed';
            $order->save();

            if (ee()->db->trans_status() === false) {
                ee()->db->trans_rollback();
                return $this->error_redirect('Opslaan mislukt.');
            }

            ee()->db->trans_commit();
        } catch (Throwable $e) {
            ee()->db->trans_rollback();
            $this->log_developer('Delivery_manager accept_delivery error: ' . $e->getMessage());

            $group_id = (int) ee()->session->userdata('group_id');
            if ($group_id === 1) {
                return $this->error_redirect('Er is iets misgegaan bij het verwerken van de levering: ' . $e->getMessage());
            }

            return $this->error_redirect('Er is iets misgegaan bij het verwerken van de levering.');
        }

        ee()->session->set_flashdata('delivery_manager_success', 'Levering is verwerkt en de voorraad is bijgewerkt.');

        $return_url = ee()->input->post('return_url');
        if (! $return_url) {
            $return_url = ee()->functions->form_backtrack('-1');
        }

        ee()->functions->redirect($return_url);
    }

    /**
     * Action: POST endpoint to create a new pending delivery entry.
     * NOTE: EE action dispatch calls methods on the module class.
     */
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

        // Require at least 1 meaningful item:
        // - existing product with amount > 0
        // - OR new product details (name/ean) even if amount is 0 (creates product in Producten)
        $has_any = false;
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $amount = (int) ($item['delivered_amount'] ?? 0);
            $rel = $this->parse_relationship_id($item['product_rel'] ?? null);
            $name = trim((string) ($item['product_name'] ?? ''));
            $ean = trim((string) ($item['product_ean'] ?? ''));

            if ($rel && $amount > 0) {
                $has_any = true;
                break;
            }

            if (! $rel && ($name !== '' || $ean !== '')) {
                // New product row counts even if amount is 0
                $has_any = true;
                break;
            }
        }
        if (! $has_any) {
            return $this->error_redirect('Voeg minimaal 1 product toe (bestaand met aantal > 0, of nieuw product).');
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

        ee()->db->trans_begin();

        try {
            $now = (int) ee()->localize->now;

            $entry = ee('Model')->make('ChannelEntry');
            $entry->site_id = ee()->config->item('site_id');
            $entry->channel_id = (int) $suppliers_channel->channel_id;
            $entry->author_id = $member_id;
            $entry->title = $title;
            $entry->url_title = $url_title;
            $entry->status = 'open';
            $entry->entry_date = $now;
            $entry->edit_date = $now;

            $entry->set([
                'supplie_date' => (int) $delivery_ts,
            ]);

            $result = $entry->validate();
            if (! $result->isValid()) {
                $all = $result->getAllErrors();
                $flat = [];
                foreach ($all as $field => $errors) {
                    foreach ((array) $errors as $err) {
                        $flat[] = $field . ': ' . $err;
                    }
                }
                $this->log_developer('Delivery_manager create_delivery validation failed: ' . implode(' | ', $flat));
                ee()->db->trans_rollback();
                return $this->error_redirect('Levering kon niet worden opgeslagen: ' . ($flat[0] ?? 'Validatie mislukt.'));
            }

            if (! $entry->save()) {
                $this->log_developer('Delivery_manager create_delivery save() returned false');
                ee()->db->trans_rollback();
                return $this->error_redirect('Levering kon niet worden opgeslagen.');
            }

            // Ensure `supplie_date` is actually persisted (some installs store fields in exp_channel_data_field_<id>
            // and the model assignment can be skipped depending on fieldtype/setup).
            $date_field = ee('Model')->get('ChannelField')->filter('field_name', 'supplie_date')->first();
            if ($date_field) {
                $date_table = ee()->db->dbprefix('channel_data_field_' . (int) $date_field->field_id);
                ee()->db->where('entry_id', (int) $entry->entry_id)->update($date_table, ['field_id_' . (int) $date_field->field_id => (int) $delivery_ts]);
                if (ee()->db->affected_rows() === 0) {
                    ee()->db->insert($date_table, [
                        'entry_id' => (int) $entry->entry_id,
                        'field_id_' . (int) $date_field->field_id => (int) $delivery_ts,
                    ]);
                }
            }

            $row_order = 1;
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $delivered_amount = (int) ($item['delivered_amount'] ?? 0);
                $product_rel = $this->parse_relationship_id($item['product_rel'] ?? null);
                $product_name = trim((string) ($item['product_name'] ?? ''));
                $product_ean = trim((string) ($item['product_ean'] ?? ''));
                $product_cat = trim((string) ($item['product_cat'] ?? ''));

                // If an existing product was selected, use its canonical fields for the delivery row.
                if ($product_rel) {
                    $selected = ee('Model')
                        ->get('ChannelEntry')
                        ->filter('channel_id', (int) $products_channel->channel_id)
                        ->filter('entry_id', (int) $product_rel)
                        ->first();

                    if ($selected) {
                        $pn = trim((string) ($selected->product_name ?? ''));
                        if ($pn === '') {
                            $pn = trim((string) ($selected->title ?? ''));
                        }
                        if ($pn !== '') {
                            $product_name = $pn;
                        }

                        $pe = trim((string) ($selected->product_ean ?? ''));
                        if ($pe !== '') {
                            $product_ean = $pe;
                        }

                        $pc = $selected->product_cat ?? '';
                        if (is_array($pc)) {
                            $first = trim((string) ($pc[0] ?? ''));
                            if ($first !== '') {
                                $product_cat = $first;
                            }
                        } else {
                            $pcs = trim((string) $pc);
                            if ($pcs !== '') {
                                $product_cat = $pcs;
                            }
                        }
                    }
                }

                $is_new_product_row = (! $product_rel && ($product_name !== '' || $product_ean !== ''));

                // Skip rows that have neither existing selection nor new product info
                if (! $product_rel && ! $is_new_product_row) {
                    continue;
                }

                // Existing product rows must have a quantity
                if ($product_rel && $delivered_amount <= 0) {
                    continue;
                }

                // New product rows may have quantity 0 (product still gets created), but never negative
                if ($is_new_product_row && $delivered_amount < 0) {
                    continue;
                }

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
                        $new_product->entry_date = $now;
                        $new_product->edit_date = $now;

                        if ($product_name !== '') {
                            $new_product->set(['product_name' => $product_name]);
                        }
                        if ($product_ean !== '') {
                            $new_product->set(['product_ean' => $product_ean]);
                        }
                        if ($product_cat !== '') {
                            // multi_select accepts array; safer than passing a raw string
                            $new_product->set(['product_cat' => [$product_cat]]);
                        }

                        // Start at 0; stock is updated only on accept_delivery.
                        $new_product->set(['product_amount' => 0]);

                        $result = $new_product->validate();
                        if (! $result->isValid()) {
                            $all = $result->getAllErrors();
                            $flat = [];
                            foreach ($all as $field => $errors) {
                                foreach ((array) $errors as $err) {
                                    $flat[] = $field . ': ' . $err;
                                }
                            }
                            throw new RuntimeException('New product validation failed: ' . implode(' | ', $flat));
                        }

                        if (! $new_product->save()) {
                            throw new RuntimeException('New product save() returned false');
                        }

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

                $maybe_set('product_name', $product_name);
                $maybe_set('product_ean', $product_ean);
                $maybe_set('product_cat', $product_cat);
                $maybe_set('product_amount', $delivered_amount);

                ee()->db->insert($grid_table, $row);
                $row_order++;
            }

            if ($row_order === 1) {
                ee()->db->trans_rollback();
                return $this->error_redirect('Voeg minimaal 1 productregel toe.');
            }

            if (ee()->db->trans_status() === false) {
                ee()->db->trans_rollback();
                return $this->error_redirect('Opslaan mislukt.');
            }

            ee()->db->trans_commit();
        } catch (Throwable $e) {
            ee()->db->trans_rollback();
            $this->log_developer('Delivery_manager create_delivery error: ' . $e->getMessage());

            $group_id = (int) ee()->session->userdata('group_id');
            if ($group_id === 1) {
                return $this->error_redirect('Er is iets misgegaan bij het aanmaken van de levering: ' . $e->getMessage());
            }

            return $this->error_redirect('Er is iets misgegaan bij het aanmaken van de levering.');
        }

        ee()->session->set_flashdata('delivery_manager_success', 'Levering is aangemaakt.');

        $return_url = ee()->input->post('return_url');
        if (! $return_url) {
            $return_url = ee()->functions->form_backtrack('-1');
        }

        ee()->functions->redirect($return_url);
    }

    private function cp_create_entry_url_for_channel(string $channel_name): string
    {
        $channel = ee('Model')->get('Channel')
            ->filter('channel_name', $channel_name)
            ->first();

        if (! $channel) {
            return '';
        }

        return '/cms.php?/cp/publish/create/' . $channel->channel_id;
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

    private function log_developer(string $message): void
    {
        try {
            if (! isset(ee()->logger)) {
                ee()->load->library('logger');
            }

            if (isset(ee()->logger) && is_object(ee()->logger) && method_exists(ee()->logger, 'developer')) {
                ee()->logger->developer($message);
                return;
            }
        } catch (Throwable $e) {
            // fall through to error_log
        }

        error_log($message);
    }
}
