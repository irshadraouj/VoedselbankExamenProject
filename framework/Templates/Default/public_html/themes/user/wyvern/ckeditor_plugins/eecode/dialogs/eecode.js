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

    CKEDITOR.dialog.add( pluginName+'Dialog', function( editor ) {
        return {
            title: 'EE Code',
            minWidth: 400,
            minHeight: 200,
            contents: [
                {
                    id: 'tab-basic',
                    label: 'Basic Settings',
                    elements: [
                        {
                            type: 'textarea',
                            id: 'code',
                            label: 'EE Code'
                        }
                    ]
                }
            ],
            onOk: function() {
                var dialog = this;
                var code = editor.document.createElement( 'code' );
                code.setText( dialog.getValueOf( 'tab-basic', 'code' ) );
                code.setAttribute( 'class', 'ee_code' );
                editor.insertElement( code );
            }
        };
    });
})();

