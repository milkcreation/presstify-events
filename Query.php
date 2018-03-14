<?php
namespace tiFy\Plugins\Events; 

class Query
{
	/* = CONSTRUCTEUR = */
	public function __construct()
	{
		add_filter( 'query_vars', array( $this, 'query_vars' ), 1 );
		add_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 99, 2 );		
		add_filter( 'posts_results', array( $this, 'posts_results' ), 10, 2 );
	}
	
	/** == Définition des variables de requête == **/
	final public function query_vars( $aVars ) 
	{
		$aVars[] = 'tyevquery'; // all | uniq | none
		$aVars[] = 'tyevfrom'; 
		$aVars[] = 'tyevto';
		$aVars[] = 'tyevorder'; // defaut 1 | 0 permet de désactiver le forçage de l'ordonnacement des éléments par date de début 
	  
		return $aVars;
	}
		
	/** == Modification des condition de requête == **/	
	final public function posts_clauses( $pieces, $query )
	{	
		//Bypass	
		if( is_admin() && ! defined( 'DOING_AJAX' ) )
			return $pieces;		
		/// La requête est désactivée
		if( $query->get( 'tyevquery' ) === 'none' )
			return $pieces;
		/// La requête ne cible aucun type de post
		if( ! $post_types = $query->get( 'post_type' ) )
			return $pieces;
		
		// Traitement des types de post		
		if( is_string( $post_types ) )
			$post_types = array_map( 'trim', explode( ',', $post_types ) );
		
		/// La requête ne doit contenir des types de post déclarés en tant qu'événement		
		if( in_array( 'any', $post_types ) )
			return $pieces;		
		if( array_diff( $post_types, Events::GetPostTypes() ) )
			return $pieces;	

		global $wpdb;
		extract( $pieces );	

		// Récupération des arguments de contruction de la requête
		$Db		= tify_db_get( 'tify_events' );
		$show 	= ( ( $_show = $query->get( 'tyevquery' ) ) && in_array( $_show, array( 'all', 'uniq' ) ) ) ? $_show : 'uniq';
		$from 	= ( $_from = $query->get( 'tyevfrom' ) ) ? $_from : current_time( 'mysql' );
		$to 	= ( $_to = $query->get( 'tyevto' ) ) ? $_to : false;
		
		$fields .= ", '{$show}' as tyevquery,'{$from}' as tyevfrom";
		if( $to )
			$fields .= ",'{$to}' as tyevto"; 
		
		if( $query->is_singular() ) :
			$fields .= ", tify_events.event_id, MIN(tify_events.event_start_datetime) as event_start_datetime, MAX(tify_events.event_end_datetime) as event_end_datetime, GROUP_CONCAT(tify_events.event_start_datetime, '|', tify_events.event_end_datetime ORDER BY tify_events.event_start_datetime ASC) as event_date_range";
		else :
			$fields .= ", tify_events.event_id, tify_events.event_start_datetime, tify_events.event_end_datetime";
		endif;
		
		$join .= " INNER JOIN ". $Db->Name ." as tify_events ON ( $wpdb->posts.ID = tify_events.event_post_id )";  	
		
		if( ! $query->is_singular() ) :
			if( $show === 'uniq' ) :				
				$inner_where  = "SELECT MIN( event_start_datetime ) FROM ". $Db->Name ." WHERE 1";
				$inner_where .= " AND event_post_id = {$wpdb->posts}.ID";
				$inner_where .= " AND event_end_datetime >= '{$from}'";
				if( $to )
					$inner_where .= " AND event_start_datetime <= '{$to}'";			
				$where .= " AND tify_events.event_start_datetime IN ( {$inner_where} )";			
				// Éviter les doublons lorsqu'un événement à deux dates de démarrage identiques
				$groupby = "tify_events.event_post_id";
			else :		
				$where .= " AND tify_events.event_end_datetime >= '{$from}'";
				if( $to )
					$where .= " AND event_start_datetime <= '{$to}'";			
				// Autoriser les doublons
				$groupby = false;
			endif;
			if( $query->get( 'tyevorder', true ) )
				$orderby = "tify_events.event_start_datetime ASC";
		endif;
		
		$_pieces = array( 'where', 'groupby', 'join', 'orderby', 'distinct', 'fields', 'limits' );

		//var_dump( compact ( $_pieces ) ); exit;
		return compact ( $_pieces );
	}
	
	/** == == **/
	public function posts_results( $posts, $query )
	{
		foreach( $posts as $k => $p ) :
			if( ! $p->ID )
				unset( $posts[$k] );
		endforeach;
		
		return $posts;
	}
	
	/* = CONTROLEUR = */
	/** == Récupération des événements liés à un contenu == **/
	public static function PostGetEvents( $post = 0, $args = array() )
	{
		// Bypass
		if( ! $post = get_post( $post ) )
			return;
		
		// Traitement des arguments de requête
		$defaults = array(
			'orderby' 	=> 'start_datetime',
			'order'		=> 'ASC'
		);
		$args = wp_parse_args( $defaults, $args );	
		$args['post_id'] = $post->ID;
	
		return tify_db_get( 'tify_events' )->select()->rows( $args );
	}
	
	/** == Récupération des dates liées à un contenu == **/
	public static function PostGetDates( $post = 0, $args = array() )
	{
		// Bypass
		if( ! $post = get_post( $post ) )
			return;		
		if( ! $events = self::PostGetEvents( $post, $args ) )
			return;
		
		$dates = array();
		foreach( (array) $events as $n => $event ) :
			if( $_dates = self::EventParseAttrs( $event ) ) :
				foreach( $_dates as $_date ) :
					array_push( $dates, $_date );
				endforeach;
			endif;
		endforeach;

		return $dates;	
	}
	
	/** == Récupération des attributs de date lié à un événement == **/
	public static function GetEvent( $event_id )
	{
		return tify_db_get( 'tify_events' )->select()->row_by_id( $event_id );
	}
		
	/** == Traitement des attributs d'un événement == **/
	public static function EventParseAttrs( $event )
	{	
		$split = \tiFy\Plugins\Events\Events::GetPostTypeAttr( get_post_type( $event->event_post_id ), 'split' );
		$date = array(); 
		$k = 0;
		$s = new \DateTime( $event->event_start_datetime ); $e = new \DateTime( $event->event_end_datetime );
		
		// Le jour de fin est identique au jour de début
		if( $s->format( 'Ymd' ) === $e->format( 'Ymd' ) ) :
			$date[$k]['event_id']		= $event->event_id;
			$date[$k]['post_id']		= $event->event_post_id;
			$date[$k]['start_date'] 	= $s->format( 'Y-m-d' );
			$date[$k]['end_date'] 		= false;
			
			if( $s->format( 'Hi' ) === $e->format( 'Hi' ) ) :
				$date[$k]['start_time'] 	= $s->format( 'H:i:s' ); 
				$date[$k]['end_time'] 		= false;
			else :
				$date[$k]['start_time'] 	= $s->format( 'H:i:s' );
				$date[$k]['end_time'] 		= $e->format( 'H:i:s' );
			endif;	
			
		// Le jour de début est supérieur au jour de fin et ça c'est pas bien 	
		elseif( $s->format( 'Ymd' ) > $e->format( 'Ymd' ) ) :
			$date[$k]['event_id']		= $event->event_id;
			$date[$k]['post_id']		= $event->event_post_id;
			$date[$k]['start_date'] 	= $s->format( 'Y-m-d' );
			$date[$k]['end_date'] 		= false;		
			
			if( $s->format( 'Hi' ) === $e->format( 'Hi' ) ) :
				$date[$k]['start_time'] 	= $s->format( 'H:i:s' ); 
				$date[$k]['end_time'] 		= false;
			else :
				$date[$k]['start_time'] 	= $s->format( 'H:i:s' );
				$date[$k]['end_time'] 		= $e->format( 'H:i:s' );
			endif;
			
		// 	
		else :
			$sdiff = new \DateTime( $s->format( 'Y-m-d' ) );
			$ediff = new \DateTime( $e->format( 'Y-m-d' ) );
			$diff = $sdiff->diff( $ediff );
			if( $split == -1 )
				$split = $diff->days;
			
			if( $diff->days && $diff->days <= $split ) :			
				foreach( range( 0, $diff->days, 1 ) as $n ) :
					if( $n )
						$s->add( new \DateInterval( 'P1D' ) );
					$date[$n]['event_id']		= $event->event_id;
					$date[$n]['post_id']		= $event->event_post_id;
					$date[$n]['start_date'] 	= $s->format( 'Y-m-d' );
					$date[$n]['end_date'] 		= false;
					
					if( $s->format( 'Hi' ) == $e->format( 'Hi' ) ) :
						$date[$n]['start_time'] 	= $s->format( 'H:i:s' ); 
						$date[$n]['end_time'] 		= false;
					else :
						$date[$n]['start_time'] 	= $s->format( 'H:i:s' );
						$date[$n]['end_time'] 		= $e->format( 'H:i:s' );
					endif;
				endforeach;
			else :
				$date[$k]['event_id']		= $event->event_id;
				$date[$k]['post_id']		= $event->event_post_id;
				$date[$k]['start_date'] 	= $s->format( 'Y-m-d' );
				$date[$k]['end_date'] 		= $e->format( 'Y-m-d' );
				
				if( $s->format( 'Hi' ) == $e->format( 'Hi' ) ) :
					$date[$k]['start_time'] = $s->format( 'H:i:s' ); 
					$date[$k]['end_time'] 	= false;
				else :
					$date[$k]['start_time'] = $s->format( 'H:i:s' );
					$date[$k]['end_time'] 	= $e->format( 'H:i:s' );
				endif;
			endif;
		endif;
		
		return $date;
	}
	
	/** == Calcul le nombre d'événements d'une plage == **/
	public static function RangeCount( $from, $to )
	{
		global $wpdb;
		
		$timezone_string = get_option( 'timezone_string' );
		
		$from 		= new \DateTime( $from, new \DateTimeZone( $timezone_string ) );
		$to			= new \DateTime( $to, 	new \DateTimeZone( $timezone_string ) );
	
		return $wpdb->get_var( 
			$wpdb->prepare(
				"SELECT COUNT(event_id)".
				" FROM {$wpdb->tify_events}".
				" WHERE (".
				" ( UNIX_TIMESTAMP(event_start_datetime) BETWEEN %d AND %d )".
				" OR ( UNIX_TIMESTAMP(event_end_datetime) BETWEEN %d AND %d )".
				")",
				$from->getTimestamp(),
				$to->getTimestamp(),
				$from->getTimestamp(),
				$to->getTimestamp()
			)
		);
	}
}