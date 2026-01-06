/*
 * Wyvern Header plugin
 *
 * @package            Wyvern for EE3
 * @author             Rein de Vries (info@reinos.nl)
 * @copyright          Copyright (c) 2017 Rein de Vries
 * @license 		   http://ee.reinos.nl/commercial-license
 * @link               http://ee.reinos.nl/add-ons/wyvern
 */

(function () {

    "use strict";

    var pluginName = 'h6';

    CKEDITOR.plugins.add(pluginName, {
        icons: pluginName, // If you wish to have an icon...

        init: function (editor) {

            //  Variables
            var style = new CKEDITOR.style({element: pluginName});

            // Create the command
            editor.addCommand(pluginName, new CKEDITOR.styleCommand(style));

            // This part will provide toolbar button highlighting in editor.
            editor.attachStyleStateChange(style, function (state) {
                if (state == CKEDITOR.TRISTATE_ON) {
                    !editor.readOnly && editor.getCommand(pluginName).setState(state);
                } else {
                    editor.getCommand(pluginName).setState(2);
                }
            });

            // This will add button to the toolbar.
            editor.ui.addButton(pluginName, {
                label: 'Set text to '+pluginName,
                command: pluginName
            });

        }
    });
})();