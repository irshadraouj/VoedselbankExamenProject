( function(){
	
	// Dialog Object
	// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.dialogDefinition.html
	var channelimages_dialog = function(editor){
		
		DialogElements = new Array();
		DialogSizes = new Array();
		
		
		//********************************************************************************* //
		
		var GetCISizes = function(){
			
			// If Channel Images is not loaded return
			if (typeof(ChannelImages.Fields) != 'object') return DialogSizes;
			
			var LoopCount = 1;
			var RadioName = Math.floor(Math.random()*11);
			
			// Loop over all images
			jQuery.each(ChannelImages.Fields, function(i, v){
				if(v.images.length > 0) {
					var sizes = v.images[0]['sizes_metadata'].split('/');
					sizes = sizes.filter(function(n){ return n != '' });

                    jQuery.each(sizes, function(index, val){
                    	var size = val.split('|');

                        var Checked = '';
                        if (LoopCount == 1) Checked = 'checked';

                        DialogSizes.push({
                            type:'html',
                            onClick: SelectImage,
                            html: '<div class="CISize">'+
                            '<input type="radio" value="'+size[0]+'" name="ci_size_'+RadioName+'" '+Checked+'/> &nbsp;&nbsp;&nbsp;'+
                            '<strong>'+ size[0]+'</strong>&nbsp;&nbsp;'+
                            '<span>(Width: ' + size[1] +'px)</span>&nbsp;&nbsp;'+
                            '<span>(Height: ' + size[2] +'px)</span>'+
                            '</div>'
                        });

                        LoopCount++;
                    });
				}
			});
			
			return DialogSizes;
		};
		
		//********************************************************************************* //
		
		var SelectImage = function(Event){

			if (typeof(Event.target) == 'undefined') return;
			
			var Target = jQuery(Event.target);
			
			// Remove all other
			Target.closest('table').find('.CImage').removeClass('Selected');
			
			Target.closest('.CImage').addClass('Selected');
		};
		
		
		//********************************************************************************* //
		
		return {
			
			// The dialog title, displayed in the dialog's header. Required. 
			title: 'Channel Images',
			
			// The minimum width of the dialog, in pixels.
			minWidth: '500',
			
			// The minimum height of the dialog, in pixels.
			minHeight: '400',
			
			// Buttons
			buttons: [CKEDITOR.dialog.okButton, CKEDITOR.dialog.cancelButton] /*array of button definitions*/,
			
			// On OK event
			onOk: function(Event){
				var Wrapper = jQuery(CKEDITOR.dialog.getCurrent().definition.dialog.parts.dialog.$);
				
				if ( Wrapper.find('.Selected').length == 0) return;
				
				var Selected = Wrapper.find('.Selected img');
				
				var IMGSRC = Selected.attr('src');
				
				var filename = Selected.attr('rel');
				var dot = filename.lastIndexOf('.');

				var Size = Wrapper.find('.CISize input[type=radio]:checked').val();

				if (Size != 'Original'){
					IMGSRC = IMGSRC.replace(Selected.data('size'), Size);
				}

				var imageElement = editor.document.createElement('img');
				imageElement.setAttribute('src', IMGSRC);
				//imageElement.setAttribute('width', ChannelImages.Sizes[Size].width);
				//imageElement.setAttribute('height', ChannelImages.Sizes[Size].height);
				imageElement.setAttribute('alt', Selected.attr('alt'));
				imageElement.setAttribute('class', 'ci-image ci-'+Size);
				
				editor.insertElement( imageElement );
				
				Selected.parent().removeClass('Selected');
			},
			
			// On Cancel Event
			onCancel: function(){
				
				var Wrapper = jQuery(CKEDITOR.dialog.getCurrent().definition.dialog.parts.dialog.$);
				
				if ( Wrapper.find('.Selected').length == 0) return;
				Wrapper.find('.Selected').removeClass('Selected');
				
			},
			
			// On Load Event
			onLoad: function(){},
			
			// On Show Event
			onShow: function(){
				
				// Grab the ImageWrapper
				var ImgWrapper = jQuery(this.getElement().$).find('.WCI_Images');

				// Loop over all images
                jQuery.each(ChannelImages.Fields, function(i, v){
                	jQuery.each(v.images, function(index, val){
						ImgWrapper.append('<div class="CImage"><img src="'+val.small_img_url+'" rel="'+val.image_title+'" alt="'+val.image_title+'" data-size="'+v.settings.small_preview+'"/></div>');
					});
				});

				ImgWrapper.find('.CImage').click(SelectImage);
			},
			
			// On Hide Event
			onHide: function(){
				jQuery(this.getElement().$).find('.WCI_Images').empty();
			},
			
			// Can dialog be resized?
			resizable: CKEDITOR.DIALOG_RESIZE_BOTH,
			
			// Content definition, basically the UI of the dialog
			contents: 
			[
				 {
					id: 'ci_images',  /* not CSS ID attribute! */
					label: 'Images',
					className : 'weeeej', 
					elements: [
					    {
						   type : 'html',
						   html : '<p>Please select an image and then your desired image size.</p>'
						},
						{
							type : 'html',
							 html : '<div class="WCI_Images"></div>'
						},
						{
							type : 'vbox',
							widths : [ '100%'],
							children : GetCISizes()
						}
					]
				 }
			]
		};
		
		//********************************************************************************* //
		
		
	};
	
	// Add the Dialog
	CKEDITOR.dialog.add('channelimages', function(editor) {
		return channelimages_dialog(editor);
	});
		
})();