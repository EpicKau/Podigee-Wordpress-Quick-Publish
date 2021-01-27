(function( $ ) {
	'use strict';

	/**
	* Registering plugin functions.  
	*/
    $.pfex = function(element, options) {
        var defaults = {
            podcastjson: null
        }
        var plugin = this;
        plugin.settings = {}
        var $element = $(element),
             element = element;
        plugin.init = function() {
            plugin.settings = $.extend({}, defaults, options);
        }
		plugin.init(); 
    }

    $.fn.pfex = function(options) {
        return this.each(function() {
            if (undefined == $(this).data('pfex')) {
                var plugin = new $.pfex(this, options);
                $(this).data('pfex', plugin);
            }
        });
    }

   $(document).ready(function(){
 
   		/**
   		* Clicking the fetch button triggers an Ajax call to fetch the podcast feed.
   		* If feed is valid and has items in it, the episode selector shows.
   		*/
   		$('#pfex-bulk-form').on('submit', function( event ) {
   			var selectedCounter = 0;
   			$(this).find('select').each(function(){
   				if ($(this).val() != "-1") selectedCounter += 1; 
	        });
   			if (selectedCounter == 0) {
	            event.preventDefault();
	            alert( 'Please select a bulk action before submitting the form.' );
   			}
	    });

	    /**
	    * Toggle visibility of the setup area
	    */
	    $('.pfex-toggle-hidden').click(function(){
	    	$('.pfex-option-section').toggleClass('pfex-hidden');
	    	var toggle = $(this).data('toggle');
	    	$(this).data('toggle', $(this).html());
	    	$(this).html(toggle);
	    	$("html, body").animate({ scrollTop: $(document).height() }, "fast");
	    });

	    $('.pfex-show-settings').click(function(){
	    	$("html, body").animate({ scrollTop: $(document).height() }, "fast");
	    	if ($('.pfex-option-section').css('display') == 'none') {
	    		 $('.pfex-toggle-hidden').trigger('click');
	    	} 
	    });

	    /**
	    * Copy shortcode to clipboard.
	    */
	    $('.pfex-copy-shortcode').click(function(){
	    	var td = $(this).closest('td').text();
	    	var shortcode = td.substring(0,td.indexOf(']')+1);
	    	copyTextToClipboard(shortcode);
	    });

	    //Copy to clipboard solution from stackoverflow: https://stackoverflow.com/questions/37478281/select-the-value-of-a-td-on-click-to-ease-copy
		function copyTextToClipboard(text) {
			var textArea = document.createElement("textarea");

			// Place in top-left corner of screen regardless of scroll position.
			textArea.style.position = 'fixed';
			textArea.style.top = 0;
			textArea.style.left = 0;

			// Ensure it has a small width and height. Setting to 1px / 1em
			// doesn't work as this gives a negative w/h on some browsers.
			textArea.style.width = '2em';
			textArea.style.height = '2em';

			// We don't need padding, reducing the size if it does flash render.
			textArea.style.padding = 0;

			// Clean up any borders.
			textArea.style.border = 'none';
			textArea.style.outline = 'none';
			textArea.style.boxShadow = 'none';

			// Avoid flash of white box if rendered for any reason.
			textArea.style.background = 'transparent';


			textArea.value = text;

			document.body.appendChild(textArea);

			textArea.select();

			try {
				var successful = document.execCommand('copy');
				var msg = successful ? 'successful' : 'unsuccessful';
				if (msg == "unsuccessful") console.log('Copying text command was ' + msg);
			} catch (err) {
				console.log('Oops, unable to copy');
			}

			document.body.removeChild(textArea);
		}
   	});
})( jQuery );
