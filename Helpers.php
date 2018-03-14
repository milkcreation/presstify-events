<?php
/* = HELPERS = */
/** == Affichage d'une date == **/
function tify_events_date_display( $dateObj, $echo = true )
{
	return \tiFy\Plugins\Events\GeneralTemplate::DateRender( $dateObj, $echo );
}

/** == BOUCLE WORDPRESS == **/
/** == Récupérère les dates d'événement d'un post == **/
function tify_events_get_the_dates( $post = 0, $query_args = array() )
{
	return \tiFy\Plugins\Events\Query::PostGetDates( $post, $query_args = array() );
}

/** == Récupére la date de l'événement == **/
function get_the_event_datetime( $post = 0 )
{
	if( ( $post = get_post( $post ) ) && isset( $post->event_start_datetime ) ) :
		$from = new \DateTime( $post->tyevfrom ); $start = new \DateTime( $post->event_start_datetime );
		if( $from->format( 'U' ) > $start->format( 'U' ) ) :
			return $from->format( 'Y-m-d' ) .' '. $start->format( 'H:i:s' );
		else :
			return $post->event_start_datetime;
		endif;
	endif;
}

/** == Affiche la date de l'événement == **/
function event_datetime( $post = 0 )
{
	echo get_the_event_datetime( $post );
}

/** == Récupére la date de début de l'événement == **/
function get_the_event_start_datetime( $post = 0 )
{
	if( ( $post = get_post( $post ) ) && isset( $post->event_start_datetime ) ) :
		return $post->event_start_datetime;
	endif;
}

/** == Affiche la date de début de l'événement == **/
function event_start_datetime( $post = 0 )
{
	echo get_the_event_start_datetime( $post );
}

/** == Récupére la date de fin de l'événement == **/
function get_the_event_end_datetime( $post = 0 )
{
	if( ( $post = get_post( $post ) ) && isset( $post->event_end_datetime ) ) :
		return $post->event_end_datetime;
	endif;
}

/** == Affiche la date de fin de l'événement == **/
function event_end_datetime( $post = 0 )
{
	echo get_the_event_end_datetime( $post );
}

/** == Récupére la date de début des événements liés au contenu == **/
function get_the_event_first_datetime( $post = 0 )
{
	if( $date_range = tify_events_get_the_dates( $post ) ) :
		$date = reset( $date_range );
		return $date['start_date'] .' '. $date['start_time'];
	endif;
}

/** == Récupére la liste complète des dates liés au contenu == **/
function get_the_event_datetimes( $post = 0 )
{
	if( ! $date_range = tify_events_get_the_dates( $post ) )
		return;

	$dates = array();	
	foreach( $date_range as $dr ) :
		$startDate = new \DateTime( $dr['start_date'] ); 
		if( ! empty( $dr['end_date'] ) ) :
			$endDate = new \DateTime( $dr['end_date'] ); 
			$interval = $startDate->diff( $endDate );
			foreach( range( 0, $interval->days ) as $i ) :
				$Date = new \DateTime( $dr['start_date'] .' '. $dr['start_time'] );
				$Date->add( new \DateInterval( "P{$i}D" ) );
				$dates[$Date->format( 'U' )] = $Date->format( 'Y-m-d H:i:s' );
			endforeach;
		else :
			$Date = new \DateTime( $dr['start_date'] .' '. $dr['start_time'] );
			$dates[$Date->format( 'U' )] = $Date->format( 'Y-m-d H:i:s' );
		endif;
	endforeach;
	
	ksort( $dates );
	
	return $dates;
}

/** == Récupére la date de fin des événements liés au contenu == **/
function get_the_event_last_datetime( $post = 0 )
{
	if( $date_range = tify_events_get_the_dates( $post ) ) :
		$date = end( $date_range );

		if( ! empty( $date['end_date'] ) ) :
			return $date['end_date'] .' '. $date['end_time'];
		else :
			return $date['start_date'] .' '. $date['start_time'];
		endif;
	endif;
}

/** == Récupération d'une metadonnée d'événement == **/
function tify_events_get_meta( $event_id, $meta_key = null, $single = false )
{
	return get_metadata( 'tify_events', $event_id, $meta_key, $single );
}

/** == Récupération de l'événement voisin == **/
function tify_events_get_adjacent( $previous = true )
{
	global $wpdb, $tify_events;
	
	if ( ! $post = get_post() )
		return null;
	
	$adjacent = $previous ? 'previous' : 'next';
	$op = $previous ? '<' : '>';
	$diffop = $previous ? '>' : '<';
	$order = $previous ? 'DESC' : 'ASC';
	
	$join 	= "INNER JOIN {$tify_events->db->wpdb_table} as tify_events ON ( p.ID = tify_events.event_post_id )";
	
	$where 	= "WHERE 1";
	$where 	.= " AND p.ID != {$post->ID}";
	$where 	.= " AND p.post_type = '{$post->post_type}'";
	$where 	.= " AND p.post_status = 'publish'";
	$where 	.= " AND tify_events.event_start_datetime {$op} '{$post->event_start_datetime}'";
	if( $previous ) :
	else:
		$where 	.= " AND p.ID NOT IN (SELECT diff_tify_events.event_post_id FROM {$tify_events->db->wpdb_table} AS diff_tify_events WHERE diff_tify_events.event_start_datetime {$diffop} '{$post->event_start_datetime}' )";
	endif;
	
	$sort 	= "ORDER BY tify_events.event_start_datetime {$order} LIMIT 1";
	
	$query = "SELECT DISTINCT p.ID FROM $wpdb->posts AS p $join $where $sort";
	
	$query_key = 'tify_events_adjacent_' . md5( $query );
	$result = wp_cache_get( $query_key, 'counts' );
	if ( false !== $result ) {
		if ( $result )
			$result = get_post( $result );
			return $result;
	}
	
	$result = $wpdb->get_var( $query );
	if ( null === $result )
		$result = '';
	
	wp_cache_set( $query_key, $result, 'counts' );

	if ( $result )
		$result = get_post( $result );

	return $result;
}

/** == Calendrier == **/
function tify_events_calendar( $date = null, $echo = true )
{
	return \tiFy\Plugins\Events\GeneralTemplate::Calendar( $date, $echo );
}