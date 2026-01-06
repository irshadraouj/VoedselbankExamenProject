/*
 * Wyvern Header plugin
 *
 * @package            Wyvern
 * @author             Rein de Vries (info@reinos.nl)
 * @copyright          Copyright (c) 2020 Rein de Vries
 * @license 		        http://ee.reinos.nl/commercial-license
 * @link               http://ee.reinos.nl/add-ons/wyvern
 */

(function () {

  'use strict'

  var pluginName = 'filemanager'

  CKEDITOR.plugins.add(pluginName, {
    icons: pluginName, // If you wish to have an icon...

    init: function (editor) {

      // Create the command
      editor.addCommand(pluginName, {
        exec: function (editor) {

          //place the image in the editor
          function place_image (data) {
            if (data.isImage) {
              editor.insertHtml('<img src="' + data.path + '" alt="' + data.title + '" title="' + data.title + '"/>')
            }
            else {
              editor.insertHtml('<a href="' + data.path + '">' + data.title + '</a>')
            }
          }

          //check what type of filemanager
          if (REINOS_WYVERN.config.filemanager === 'default') {
            REINOS_WYVERN.openFileManager('ee', editor.config.wyvern.filemanagerFiles, editor.config.wyvern.uploadDirFiles, editor.config.wyvern.id,
              function (file) {
                place_image(file)
              })

          }
          else if (REINOS_WYVERN.config.filemanager === 'assets') {
            REINOS_WYVERN.openFileManager('assets', '', editor.config.wyvern.uploadDirFiles, editor.config.wyvern.id,
              function (file) {
                place_image(file)
              })
          }
        },
      })

      var buttonName = pluginName[0].toUpperCase() + pluginName.slice(1)

      // This will add button to the toolbar.
      editor.ui.addButton(buttonName, {
        label: 'Set text to ' + pluginName,
        command: pluginName,
      })
    },
  })
})()
