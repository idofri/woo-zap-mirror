jQuery(function()
{
	/* check if select2 lib was loaded */
	if ( jQuery.isFunction( jQuery.fn.select2 ) )
	{
		var adminDirection = ( isRtl ) ? 'rtl' : 'ltr';
		
		if ( typeof tagsAttributes !== 'undefined' )
		{
			jQuery('#wc-zap-manufacturer, #wc-zap-warranty, #wc-zap-product-model').select2(
			{
				dir: adminDirection,
				tags: tagsAttributes,
				maximumSelectionSize: 1,
				formatSelectionTooBig: function ( limit ) 
				{
					return SelectionLimit;
				},
			});
		}
	}
	
	/* convert input placeholder => value */
	jQuery( '.wc_zap_input' ).on( 'keyup', function(e) 
	{
		if ( !jQuery(this).val().trim() && e.keyCode != 46 && e.keyCode != 8 )
		{
			jQuery(this).val( jQuery(this).attr('placeholder') );
		}
	});
	
	/* move checkbox */
	jQuery('#wc-zap-checkbox').appendTo('#woo-zap-mirror .hndle');
	
	/* enable/disable elements in metabox */
	jQuery('#wc-zap-disable').change(function(e)
	{
		if ( jQuery(this).prop('checked') )
		{
			jQuery('#wc-zap-options').css({'pointer-events' : 'none', 'opacity' : 0.3});
			
			return;
		}
			
		jQuery('#wc-zap-options').css({'pointer-events' : 'auto', 'opacity' : 1});
	});
	
	jQuery( function() 
	{
		// Prevent inputs in meta box headings opening/closing contents
		jQuery( '#woo-zap-mirror' ).find( '.hndle' ).unbind( 'click.postboxes' );

		jQuery( '#woo-zap-mirror' ).on( 'click', '.hndle', function( event ) 
		{
			// If the user clicks on some form input inside the h3 the box should not be toggled
			if ( jQuery( event.target ).filter( 'input, label' ).length ) 
			{
				return;
			}

			jQuery( '#woo-zap-mirror' ).toggleClass( 'closed' );
		});
	});
	
	jQuery(window).load(function()
	{
		var ph = jQuery('#wc-zap-placeholder'), ph_w = ph.innerWidth(),
			rl_w = jQuery('.wc-zap-row-label').outerWidth(),
			pb_w = jQuery('.preview.button').outerWidth(),
			zs_w = jQuery('.wc-zap-setting').innerWidth();
		
		ph.css({'position':'absolute'});
		
		jQuery('#wc-zap-mirror-url').css({'padding-left':ph_w,'width':(zs_w - pb_w - rl_w - 10)});
	});
	
	jQuery(window).resize(function()
	{
		var rl_w = jQuery('.wc-zap-row-label').outerWidth(),
			pb_w = jQuery('.preview.button').outerWidth(),
			zs_w = jQuery('.wc-zap-setting').innerWidth(),
			mu_h = jQuery('#wc-zap-mirror-url').outerHeight();
			
		
		jQuery('#wc-zap-mirror-url').css({'width':(zs_w - pb_w - rl_w - 10)});
		
		jQuery('#wc-zap-placeholder').css({'line-height':mu_h + 'px'});
	});
	
	jQuery('#wc-zap-settings input[type="checkbox"]').click(function(e)
	{	
		if ( jQuery(this).prop('checked') )
		{
			jQuery(this).parent().siblings('.children').find('input[type="checkbox"]').prop('checked', true);
		}
		else
		{
			if ( jQuery(this).parentsUntil('ul').parent().siblings('.selectit').find('input[type="checkbox"]').prop('checked') )
			{	
				e.preventDefault();	
			} 
			else 
			{
				jQuery(this).parent().siblings('.children').find('input[type="checkbox"]').prop('checked', false);
			}
		}
	});
});