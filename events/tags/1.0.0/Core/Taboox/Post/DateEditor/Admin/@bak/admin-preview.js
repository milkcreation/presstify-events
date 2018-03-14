jQuery( document ).ready( function($){
	// Prévisualisation lors de l'initialisation
	/*$( '[data-tify_control="dynamic_inputs"] > ul > li' ).each( function(){
		var $container = $(this),
			event_id 	= $( '.tify_event_id', $container ).val();
			
		$container.addClass( 'load_preview' );
		
		$.ajax({
			'url'		: tify_ajaxurl,
			'data'		: { action : 'tify_events_display_preview', event_id : event_id },
			'type'		: 'post',
			'success'	: function( resp ){
				$( '.preview > textarea', $container ).html( resp );
				$container.removeClass( 'load_preview' );
			}	
		});
	});
	
	// Prévisualisation lors de la modification d'une date
	$( document ).on( 'tify_touch_time_change', function( e ){
		var $container 		= $( e.target ).closest( 'li' ),
			event_id 		= 	$( '.tify_event_id', $(this) ).val(),
			start_datetime 	= 	$( '.tify_event_start_date-wrapper .tify_control_touch_time-input', $container ).val() +' '
								+$( '.tify_event_start_time-wrapper .tify_control_touch_time-input', $container ).val(),
			end_datetime	= 	$( '.tify_event_end_date-wrapper .tify_control_touch_time-input', $container ).val() +' '
								+ $( '.tify_event_end_time-wrapper .tify_control_touch_time-input', $container ).val();
							
		$container.addClass( 'load_preview' );
		
		$.ajax({
			'url'		: tify_ajaxurl,
			'data'		: { action : 'tify_events_display_preview', event_id : event_id, start_datetime : start_datetime, end_datetime : end_datetime },
			'type'		: 'post',
			'success'	: function( resp ){
				$( '.preview > textarea', $container ).html( resp );
				$container.removeClass( 'load_preview' );
			}	
		});		
	});*/
	 	
	/* = Initialisation des dates existantes = */
	$( '.tify_events-taboox [data-tify_control="touch_time"]' ).each( function( u, v ){
		var target = $( '.tify_control_touch_time-input', $(this) );
		touchTimeInput2Handler( target );		
	});
	
	/* = Action à l'ajout d'une date supplémentaire = */
	$( document ).on( 'tify_dynamic_inputs_added', function(e){
		$( '.tify_control_touch_time-input', e.target ).each( function(){
			touchTimeInput2Handler( $(this) );
		});
	});
	
	/* = = */
	function touchTimeInput2Handler( $target ){
		var $closest = $target.closest( '[data-tify_control="touch_time"]' );
		var value = $target.val();
		var matches;
		
		if ( matches = value.match( /^(\d{4})\-(\d{2})\-(\d{2})$/ ) ) {
			var Y 	= parseInt( matches[1], 10 );
			var m 	= ("0" + parseInt( matches[2], 10 ) ).slice(-2);
			var d	= ("0" + parseInt( matches[3], 10 ) ).slice(-2);			
			
			$( '.tify_control_touch_time-handler-yyyy', $closest ).val(Y);
			$( '.tify_control_touch_time-handler-mm', $closest ).val(m);
			$( '.tify_control_touch_time-handler-dd', $closest ).val(d);;
		} else if( matches = value.match( /^(\d{2}):(\d{2}):(\d{2})$/ ) ){
			var H 	= ("0" + parseInt( matches[1], 10 ) ).slice(-2);
			var i	= ("0" + parseInt( matches[2], 10 ) ).slice(-2);
			var s	= ("0" + parseInt( matches[3], 10 ) ).slice(-2);
			
			$( '.tify_control_touch_time-handler-hh', $closest ).val(H);
			$( '.tify_control_touch_time-handler-ii', $closest ).val(i);
			$( '.tify_control_touch_time-handler-ss', $closest ).val(s);
		}
	}
});