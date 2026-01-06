<?php

/**
 * @author        Rein de Vries <support@reinos.nl>
 * @link        http://addons.reinos.nl
 * @copyright    Copyright (c) 2011 - 2025 Reinos.nl Internet Media
 * @license     http://addons.reinos.nl/commercial-license
 *
 * Copyright (c) 2011 - 2025 Reinos.nl Internet Media
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Rein de Vries and
 * Reinos.nl Internet Media) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

namespace Reinos\Wyvern\Service;

require_once PATH_THIRD . 'wyvern/config.php';

class License
{
    private $license_check_url = 'https://addons.reinos.nl/license-check/v2';

    public function __construct()
    {
        ee()->load->library(WYVERN_MAP . '_lib');
    }

    public function checkLicense($license_key = null, $reinos_member_id = null)
    {
        $license_key = $license_key == null ? ee()->wyvern_settings->item('license_key') : $license_key;
        $reinos_member_id = $reinos_member_id == null ? ee()->wyvern_settings->item(
            'license_reinos_member_id'
        ) : $reinos_member_id;

        $data = http_build_query(
            array(
                'license_key' => $license_key,
                'member_id' => $reinos_member_id,
                'module_name' => WYVERN_MAP,
                'module_version' => WYVERN_VERSION,
                'site_url' => ee()->config->item('site_url'),
                'ee_version' => APP_VER,
                'php_version' => PHP_VERSION,
                'site_id' => ee()->config->item('site_id'),
                'ip' => $_SERVER['SERVER_ADDR'],
            )
        );

        ee()->load->library('curl');
        $result = ee()->curl->simple_post($this->license_check_url, $data);

        //only go further if the result is valid
        if ($result != false) {
            //decode the result
            $result = json_decode($result);
            $result->message = '<strong>' . $result->message . '</strong>';
        } // server timeout?
        else {
            $result = (object)array(
                'success' => true,
                'message' => '<strong>Due the timeout on the license server, your license will be temporarily valid.</strong>'
            );
        }

        //save the setting
        ee()->wyvern_settings->save_setting('license_valid', $result->success);
        ee()->wyvern_settings->set_setting('license_valid', $result->success);

        // report automatic again in 1 days
        ee()->wyvern_settings->save_setting('license_report_date', ee()->localize->now + 1 * 24 * 60 * 60);
        ee()->wyvern_settings->set_setting('license_report_date', ee()->localize->now + 1 * 24 * 60 * 60);

        return $result;
    }

    public function hasValidLicense()
    {
        //do we need to check the license again
        if (ee()->wyvern_settings->item('license_report_date') < ee()->localize->now) {
            //run the check
            $this->checkLicense();
        }

        return ee()->wyvern_settings->item('license_valid') == 1 ? true : false;
    }

    public function getLicense()
    {
        return ee()->wyvern_settings->item('license_key');
    }

}
