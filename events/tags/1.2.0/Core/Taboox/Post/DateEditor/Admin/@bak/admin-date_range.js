jQuery( document ).ready( function($){
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
	
	/* = Vérification d'intégrité des dates (la date de début doit être supérieur à la date de fin) = */
	$( document ).on( 'tify_touch_time_change', function( e ){
		$closest = $( e.target ).closest( '[data-tify_control="touch_time"]' );		
		$parent = $closest.closest( 'li' );
		// @todo à Simplifier
		if( $closest.hasClass( 'tify_event_start_datetime-wrapper' ) ){
			var start = moment( $( '.tify_control_touch_time-input', $closest ).val() );
			var end = moment( $( '.tify_control_touch_time-input', $parent.find( '.tify_event_end_datetime-wrapper' ) ).val() );	
		} else if( $closest.hasClass( 'tify_event_end_datetime-wrapper' ) ){
			var end = moment( $( '.tify_control_touch_time-input', $closest ).val() );
			var start = moment( $( '.tify_control_touch_time-input', $parent.find( '.tify_event_start_datetime-wrapper' ) ).val() );			
		}
		if( ! ( end >= start ) && ! $( '.tify_event_end_datetime-wrapper .date_range_error', $parent ).size() )
			$( '.tify_event_end_datetime-wrapper', $parent ).append( '<span class="date_range_error"><i class="dashicons dashicons-no"></i> '+ tify_events.date_range_error +'</span>' );
		else if( ( end >= start ) && $( '.tify_event_end_datetime-wrapper .date_range_error', $parent ).size() )
			$( '.tify_event_end_datetime-wrapper .date_range_error', $parent ).remove();			
	});
	
	/* = = */
	function touchTimeInput2Handler( $target ){
		var $closest = $target.closest( '[data-tify_control="touch_time"]' );
		var value = $target.val();
		var matches = value.match( /^(\d{4})\-(\d{2})\-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/ );
		if ( matches !== null ) {
			var Y 	= parseInt( matches[1], 10 );
			var m 	= ("0" + parseInt( matches[2], 10 ) ).slice(-2);
			var d	= ("0" + parseInt( matches[3], 10 ) ).slice(-2);
			var H 	= ("0" + parseInt( matches[4], 10 ) ).slice(-2);
			var i	= ("0" + parseInt( matches[5], 10 ) ).slice(-2);
			var s	= ("0" + parseInt( matches[6], 10 ) ).slice(-2);
			
			$( '.tify_control_touch_time-handler-aa', $closest ).val(Y);
			$( '.tify_control_touch_time-handler-mm', $closest ).val(m);
			$( '.tify_control_touch_time-handler-jj', $closest ).val(d);
			$( '.tify_control_touch_time-handler-hh', $closest ).val(H);
			$( '.tify_control_touch_time-handler-mn', $closest ).val(i);
			$( '.tify_control_touch_time-handler-ss', $closest ).val(s);
		}
	}
});