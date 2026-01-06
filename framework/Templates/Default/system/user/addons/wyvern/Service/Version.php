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

class Version
{
    public static function eeVersion()
    {
        return APP_VER;
    }

    // --------------------------------------------------------------------

    public static function isEE3()
    {
        return self::majorVersion() === 3;
    }

    // --------------------------------------------------------------------

    public static function isEE4()
    {
        return self::majorVersion() === 4;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isGteEE4()
    {
        return self::majorVersion() >= 4;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isLtEE4()
    {
        return self::majorVersion() < 4;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isEE5()
    {
        return self::majorVersion() === 5;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isGteEE5()
    {
        return self::majorVersion() >= 5;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isLtEE5()
    {
        return self::majorVersion() < 5;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isEE6()
    {
        return self::majorVersion() === 6;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isGteEE6()
    {
        return self::majorVersion() >= 6;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isLtEE6()
    {
        return self::majorVersion() < 6;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isEE7()
    {
        return self::majorVersion() === 7;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isGteEE7()
    {
        return self::majorVersion() >= 7;
    }

    // --------------------------------------------------------------------

    /**
     * @return boolean
     */
    public static function isLtEE7()
    {
        return self::majorVersion() < 7;
    }

    // --------------------------------------------------------------------

    /**
     * @return int
     */
    public static function majorVersion()
    {
        return (int)explode('.', APP_VER)[0];
    }

    // --------------------------------------------------------------------

    /**
     * EE4 ditched the .box class which wraps most form views.
     *
     * @return string
     */
    public static function viewBoxClass()
    {
        return self::isEE3() ? 'box' : '';
    }

    // --------------------------------------------------------------------

    /**
     * @return string
     */
    public static function viewFolder()
    {
        return self::isEE3() ? 'ee3/' : 'ee4/';
    }

}
