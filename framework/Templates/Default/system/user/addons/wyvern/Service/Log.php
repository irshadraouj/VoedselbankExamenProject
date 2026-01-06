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

class Log
{
    /**
     * Log constructor.
     */
    public function __construct()
    {
        ee()->load->library(WYVERN_MAP . '_lib');
    }

    //--------------------------------------------------------------------------------

    /**
     * @param string $message
     * @param string $severity
     */
    public function add_log($message = '', $severity = 'notice')
    {
        if (
            (
                ee()->wyvern_settings->item('log_severity') == 'errors'
                && $severity == 'error'
            )
            || (
                ee()->wyvern_settings->item('log_severity') == 'all'
                && (
                    $severity == 'error'
                    || $severity == 'notice'
                )
            )
        ) {
            ee('Model')->make(WYVERN_MAP . ':Log')->set(
                array(
                    'site_id' => ee()->config->item('site_id'),
                    'time' => ee()->localize->now,
                    'severity' => $severity,
                    'message' => $message
                )
            )->save();

            // Can we also log our message to the template debugger?
            if (REQ == 'PAGE' && isset(ee()->TMPL)) {
                ee()->TMPL->log_item(WYVERN_NAME . " [{$severity}]: {$message}");
            }
        }
    }

    //--------------------------------------------------------------------------------

}
