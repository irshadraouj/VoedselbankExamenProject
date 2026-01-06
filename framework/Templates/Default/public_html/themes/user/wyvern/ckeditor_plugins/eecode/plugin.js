/*
 * EE code Dialog plugin
 *
 * @package            Wyvern for EE3
 * @author             Rein de Vries (info@reinos.nl)
 * @copyright          Copyright (c) 2017 Rein de Vries
 * @license 		   http://ee.reinos.nl/commercial-license
 * @link               http://ee.reinos.nl/add-ons/wyvern
 */

(function () {

    "use strict";

    var pluginName = 'eecode';

    CKEDITOR.plugins.add(pluginName, {
        icons: pluginName, // If you wish to have an icon...

        init: function( editor ) {
            editor.addCommand( pluginName, new CKEDITOR.dialogCommand( pluginName+'Dialog' ) );
            editor.ui.addButton( 'EECode', {
                label: 'Insert Abbreviation',
                command: pluginName,
                toolbar: 'insert'
            });

            CKEDITOR.dialog.add( pluginName+'Dialog', this.path + 'dialogs/eecode.js' );
        }
    });
})();