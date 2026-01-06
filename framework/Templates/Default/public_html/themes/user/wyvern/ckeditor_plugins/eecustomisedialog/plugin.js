/*
 * Wyvern customise dilaog plugin
 *
 * @package            Wyvern for EE3
 * @author             Rein de Vries (info@reinos.nl)
 * @copyright          Copyright (c) 2017 Rein de Vries
 * @license 		   http://ee.reinos.nl/commercial-license
 * @link               http://ee.reinos.nl/add-ons/wyvern
 */

(function () {

  'use strict'

  var pluginName = 'eecustomisedialog'

  CKEDITOR.plugins.add(pluginName)

  CKEDITOR.scriptLoader.load(REINOS_WYVERN.config.ajaxUrl + '&method=get_pages')
  CKEDITOR.scriptLoader.load(REINOS_WYVERN.config.ajaxUrl + '&method=get_templates')

  CKEDITOR.on('dialogDefinition', function (ev) {

    var editor = ev.editor
    var dialogName = ev.data.name
    var dialogDefinition = ev.data.definition
    var infoTab = dialogDefinition.getContents('info')
    var currentDialog = ev.data.definition.dialog
    // var dialog = this

    var setValue = function (elemID, inputType, value, focus) {
      focus = focus || false

      //get the field
      var field = $('#' + elemID).find(inputType)

      // populate the field
      $(field).val(value)

      //focus it
      if (focus) {
        $(field).focus()
      }

    }

    var parsePresets = function (url, self) {

      if (self.getContentElement('info', 'image_preset') !== undefined) {

        $.get(REINOS_WYVERN.config.ajaxUrl, { method: 'get_presets', file_url: url }, function (response) {

          $('#' + self.getContentElement('info', 'image_preset').domId).hide()
          $('#' + self.getContentElement('info', 'image_preset').domId + ' select').hide()

          self.getContentElement('info', 'image_preset').clear()

          response = JSON.parse(response)
          var items = response[1]
          var selected_url = response[0]

          if (items[1] !== undefined && items[1].length > 0) {
            $('#' + self.getContentElement('info', 'image_preset').domId).show()
            $('#' + self.getContentElement('info', 'image_preset').domId + ' select').show()
            $.each(items, function (k, v) {
              self.getContentElement('info', 'image_preset').add(v[0], v[1], true)
            })

            //set the image as selected
            setTimeout(function () {
              var $selected_option = $('#' + self.getContentElement('info', 'image_preset').domId).
                find('select option').
                filter(function (i, e) {
                  return $(e).val() == selected_url
                })
              $selected_option.attr('selected', true)
            }, 10)

          }
        })
      }
    }

    var initFilemanager = function (self, dialogInput, type, callback) {
      callback = callback || function () {}

      $('.wyvern_browse_button_' + editor.config.wyvern.fieldName + '_' + type).click(function () {

        //get the current zIndex and lower the zIndex of the CKE dialog to show the EE dialog
        var zIndex = self.parts.dialog.getStyle('zIndex')
        self.parts.dialog.setStyle('zIndex', 10)
        var zIndexBG = $(self.parts.dialog.$).parent().css('zIndex')
        $(self.parts.dialog.$).parent().css('zIndex', 50)

        function place_image (file) {

          //set the value
          setValue(self.getContentElement('info', dialogInput).domId, 'input', file.path, true)

          if (REINOS_WYVERN.config.filemanager === 'default') {
            parsePresets(file.path, self)
          }

          // fire a change
          var txtUrl = CKEDITOR.dialog.getCurrent().getContentElement('info', dialogInput)
          if (txtUrl) {
            txtUrl.fire('change')
          }

          //reset the zIndex of the CKE Modal
          self.parts.dialog.setStyle('zIndex', zIndex)
          $(self.parts.dialog.$).parent().css('zIndex', zIndexBG)
        }

        //reset the zIndex of the CKE Modal
        $(document).on('click', '.m-close, .assets-cancel, .assets-shade', function (event) {
          self.parts.dialog.setStyle('zIndex', zIndex)
          $(self.parts.dialog.$).parent().css('zIndex', zIndexBG)

          $(this).off(event)
        })

        //reset the zIndex of the CKE Modal
        $(document).on('keyup', function (event) {
          if (event.key === 'Escape') {
            self.parts.dialog.setStyle('zIndex', zIndex)
            $(self.parts.dialog.$).parent().css('zIndex', zIndexBG)

            $(this).off(event)
          }
        })

        //set the upload dir
        var upload_dir = 0
        var filemanager;
        if (type == 'image') {
          upload_dir = editor.config.wyvern.uploadDirImages
          filemanager = editor.config.wyvern.filemanagerImages
        }
        else {
          upload_dir = editor.config.wyvern.uploadDirFiles
          filemanager = editor.config.wyvern.filemanagerFiles
        }

        if (REINOS_WYVERN.config.filemanager === 'default') {
          REINOS_WYVERN.openFileManager('ee', filemanager, upload_dir, editor.config.wyvern.id, function (file) {
            place_image(file)
            callback(file)
          })

          //fix so the dialog will be over the ckEditor dialog
          $('.modal-file').css('z-index', 99919)
          $('.overlay').css('z-index', 99910)

        }
        else if (REINOS_WYVERN.config.filemanager === 'assets') {
          REINOS_WYVERN.openFileManager('assets', '', upload_dir, editor.config.wyvern.id, function (file) {
            place_image(file)
            callback(file)
          })
        }
      })
    }

    // Remove the linking option in the image dialog, we handle this through the Link button instead.
    // for some reason, this doesn't work in the above conditional with this.removeContents, go figure.
    if (dialogName == 'image') {
      dialogDefinition.removeContents('Link')
    }

    // Remove some fields and tabs that are overkill, I want to simplify things
    if (dialogName == 'image' || dialogName == 'link') {
      infoTab.remove('browse')
    }

    // link dialog
    if (dialogName == 'link') {
      var urlOptionsPanel = infoTab.get('urlOptions')

      var selectItems = []
      selectItems.push(['', 'empty'])
      if (REINOS_WYVERN.config.sitePages.length > 0) {
        selectItems.push(['Pages', 'ee_pages'])
      }
      if (REINOS_WYVERN.config.siteTemplates.length > 0) {
        selectItems.push(['Templates', 'ee_templates'])
      }
      selectItems.push(['File', 'ee_fp'])

      var children = []
      children.push({
        type: 'html',
        style: 'font-size: 14px; color: #383838;padding: 15px 0 4px;font-weight:bold;border-bottom:1px solid #e2e2e2;',
        html: '<h2>Select a custom element from EE to link with</h2>',
      })

      children.push({
        type: 'select',
        id: 'ee_link_type',
        label: 'EE link type',
        items: selectItems,
        setup: function (data) {
          var d = CKEDITOR.dialog.getCurrent()
          if (REINOS_WYVERN.config.sitePages.length > 0) {
            $('#' + d.getContentElement('info', 'ee_pages').domId).parent().parent().hide()
          }
          if (REINOS_WYVERN.config.siteTemplates.length > 0) {
            $('#' + d.getContentElement('info', 'ee_templates').domId).parent().parent().hide()
          }
          $('#' + d.getContentElement('info', 'ee_fp').domId).parent().find('#fp_preview_image').remove()
          $('#' + d.getContentElement('info', 'ee_fp').domId).parent().parent().hide()
          $('#' + d.getContentElement('info', 'ee_fp_desc').domId).parent().parent().hide()

          if (data.type === 'url') {
            //set the selected template if the template is selected
            var regex = new RegExp('^(\{template_url:[0-9]{1,}\})$')
            if (regex.test(data.url.url)) {
              //set the value of the template select
              this.setValue('ee_templates')

              //show set the value of the templates
              if (REINOS_WYVERN.config.siteTemplates.length > 0) {
                $('#' + d.getContentElement('info', 'ee_templates').domId).parent().parent().show()
                $('#' + d.getContentElement('info', 'ee_templates').domId).find('select').val(data.url.url)
              }
            }

            //set the selected template if the template is selected
            var regex = new RegExp('^(\{page_url:[0-9]{1,}\})$')
            if (regex.test(data.url.url)) {
              //set the value of the template select
              this.setValue('ee_pages')

              //show set the value of the templates
              if (REINOS_WYVERN.config.sitePages.length > 0) {
                $('#' + d.getContentElement('info', 'ee_pages').domId).parent().parent().show()
                $('#' + d.getContentElement('info', 'ee_pages').domId).find('select').val(data.url.url)
              }
            }
          }
        },
        onChange: function () {
          var d = CKEDITOR.dialog.getCurrent()
          switch (this.getValue()) {
            case 'ee_pages':
              if (REINOS_WYVERN.config.sitePages.length > 0) {
                $('#' + d.getContentElement('info', 'ee_pages').domId).parent().parent().show()
              }
              if (REINOS_WYVERN.config.siteTemplates.length > 0) {
                $('#' + d.getContentElement('info', 'ee_templates').domId).parent().parent().hide()
              }
              $('#' + d.getContentElement('info', 'ee_fp').domId).parent().parent().hide()
              $('#' + d.getContentElement('info', 'ee_fp_desc').domId).parent().parent().hide()
              break
            case 'ee_templates':
              if (REINOS_WYVERN.config.sitePages.length > 0) {
                $('#' + d.getContentElement('info', 'ee_pages').domId).parent().parent().hide()
              }
              if (REINOS_WYVERN.config.siteTemplates.length > 0) {
                $('#' + d.getContentElement('info', 'ee_templates').domId).parent().parent().show()
              }
              $('#' + d.getContentElement('info', 'ee_fp').domId).parent().parent().hide()
              $('#' + d.getContentElement('info', 'ee_fp_desc').domId).parent().parent().hide()
              break
            case 'ee_fp':
              if (REINOS_WYVERN.config.sitePages.length > 0) {
                $('#' + d.getContentElement('info', 'ee_pages').domId).parent().parent().hide()
              }
              if (REINOS_WYVERN.config.siteTemplates.length > 0) {
                $('#' + d.getContentElement('info', 'ee_templates').domId).parent().parent().hide()
              }
              $('#' + d.getContentElement('info', 'ee_fp').domId).parent().parent().show()
              $('#' + d.getContentElement('info', 'ee_fp_desc').domId).parent().parent().show()
              break
          }
          //
          // //set the values
          // d.setValueOf('info', 'url', this.getValue());
          // d.setValueOf('info', 'protocol', '');
          // $("#" + d.getContentElement('info', 'ee_templates').domId).find('select').val('');
          //
          // //remove image field from the file browser
          // var $imageField = $("#" + d.getContentElement('info', 'fp').domId).parent().find('#fp_preview_image');
          // $imageField.remove();
        },
      })

      if (REINOS_WYVERN.config.sitePages.length > 0) {
        children.push({
          type: 'select',
          id: 'ee_pages',
          label: 'Select a page',
          items: REINOS_WYVERN.config.sitePages,
          onChange: function () {
            var d = CKEDITOR.dialog.getCurrent()

            //set the values
            d.setValueOf('info', 'url', this.getValue())
            d.setValueOf('info', 'protocol', '')

            //hide the other fields
            if (REINOS_WYVERN.config.siteTemplates.length > 0) {
              $('#' + d.getContentElement('info', 'ee_templates').domId).find('select').val('')
            }
            //remove image field from the file browser
            $('#' + d.getContentElement('info', 'ee_fp').domId).parent().find('#fp_preview_image').remove()
          },
        })
      }

      if (REINOS_WYVERN.config.siteTemplates.length > 0) {
        children.push({
          type: 'select',
          id: 'ee_templates',
          label: 'Select a template',
          items: REINOS_WYVERN.config.siteTemplates,
          onChange: function () {
            var d = CKEDITOR.dialog.getCurrent()

            //set the values
            d.setValueOf('info', 'url', this.getValue())
            d.setValueOf('info', 'protocol', '')
            // //set the pages to blank
            if (REINOS_WYVERN.config.sitePages.length > 0) {
              $('#' + d.getContentElement('info', 'ee_pages').domId).find('select').val('')
            }
            //remove image field from the file browser
            $('#' + d.getContentElement('info', 'ee_fp').domId).parent().find('#fp_preview_image').remove()
          },
        })
      }

      children.push({
        type: 'html',
        id: 'ee_fp_desc',
        html: '<h3>Select a file via the file browser</h3>',
      })
      children.push({
        type: 'button',
        id: 'ee_fp',
        class: 'wyvern_browse_button wyvern_browse_button_' + editor.config.wyvern.fieldName + '_link',
        label: 'Browse',
        title: 'Browse',
        onLoad: function () {
          var self = this
          var d = CKEDITOR.dialog.getCurrent()
          initFilemanager(d, 'url', 'link', function (file) {

            // //set the pages to blank
            if (REINOS_WYVERN.config.sitePages.length > 0) {
              $('#' + d.getContentElement('info', 'ee_pages').domId).find('select').val('')
            }
            if (REINOS_WYVERN.config.siteTemplates.length > 0) {
              $('#' + d.getContentElement('info', 'ee_templates').domId).find('select').val('')
            }

            // //set the pages to blank
            // d.setValueOf('info', 'url', file.path);
            // d.setValueOf('info', 'pages', '');
            // d.setValueOf('info', 'protocol', '');

            //remove image field
            var $imageField = $('#' + d.getContentElement('info', 'ee_fp').domId).parent().find('#fp_preview_image')
            $imageField.remove()

            //add a image next to the button
            if (file.isImage) {
              var $button = $('#' + self.domId)
              $button.before(
                '<img id="fp_preview_image" style="width:100px;display:block;margin-bottom:2px;" src="' + file.path +
                '"/>')
            }
          })
        },
      })

      //set the fields
      urlOptionsPanel.children.push({
        type: 'vbox',
        children: children,
      })

      //hide the other fields
      var d = CKEDITOR.dialog.getCurrent()
      // $("#" + d.getContentElement('info', 'ee_pages').domId).find('select').val('');
      // $("#" + d.getContentElement('info', 'ee_templates').domId).find('select').val('');
      // //remove image field from the file browser
      // $("#" + d.getContentElement('info', 'ee_fp').domId).parent().find('#fp_preview_image').remove();

    }

    // image dialog
    else if (dialogName == 'image') {

      // add browse button
      infoTab.elements[0].children[0].children.push({
        type: 'button',
        label: 'Browse Files',
        class: 'wyvern_browse_button wyvern_browse_button_' + editor.config.wyvern.fieldName + '_image',
        style: 'display: inline-block; margin-top: 13px;',
      })

      infoTab.elements[0].children.push({
        type: 'select',
        id: 'image_preset',
        label: 'Select an Image Manipulation',
        class: 'image_preset',
        items: [['default', '']],
        labelStyle: 'display: inline-block; margin-top:8px;',
        style: 'display: none;',
        onChange: function () {
          var d = CKEDITOR.dialog.getCurrent()
          d.setValueOf('info', 'txtUrl', this.getValue())
        },
      })

      //when showing the dialog, set the presets for the image, if needed
      currentDialog.on('show', function () {
        var self = this

        if (REINOS_WYVERN.config.filemanager === 'default') {

          //first hide the elements
          $('#' + self.getContentElement('info', 'image_preset').domId).hide()
          $('#' + self.getContentElement('info', 'image_preset').domId + ' select').hide()

          //timeout is need, the native stuff is not executed yet and the url is not availble
          setTimeout(function () {
            var file_url = $('#' + self.getContentElement('info', 'txtUrl').domId + ' input').val()
            if (file_url !== '') {
              parsePresets(file_url, self)
            }
          }, 100)
        }

      })

      //init the FP dialog
      dialogDefinition.onLoad = function () {
        //save the this object
        var self = this
        initFilemanager(self, 'txtUrl', 'image')

        //parse the presets
        if (REINOS_WYVERN.config.filemanager === 'default') {
          $('#' + self.getContentElement('info', 'txtUrl').domId + ' input').change(function () {
            parsePresets($(this).val(), self)
          })
        }
      }

      // var urlOptionsPanel = infoTab.get('urlOptions');
      //
      // //set the fields
      // urlOptionsPanel.children.push({
      //     type: 'vbox',
      //     children: children
      // });
    }
  })
})()
