<?php
namespace tiFy\Plugins\Events\Core\Taboox\Post\DateEditor\Admin;

use tiFy\Core\Taboox\Admin;

class DateEditor extends Admin
{
	/* = CONSTRUCTEUR = */
	public function __construct()
	{
		parent::__construct();
		
		// Actions et Filtres Wordpress
		add_action( 'post_edit_form_tag', array( $this, 'post_edit_form_tag' ) );	
		add_action( 'wp_ajax_tify_events_display_preview', array( $this, 'wp_ajax' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
	}
	
	public function admin_init(){
		$defaults = array(
			'format'			=> 'date', // Affichage de l'interface de "date" : de date + heure à date + heure | show : représentation date de début, date de fin, heure de début à heure de fin
			'start_datetime'	=> date( 'Y-m-d 00:00:00', current_time( 'timestamp' ) ),
			'end_datetime'		=> date( 'Y-m-d 23:59:59', current_time( 'timestamp' ) ),
			'max'				=> -1
		);				
		$this->args = wp_parse_args( $this->args, $defaults );
	}
			
	/* = MISE EN FILE DES SCRIPTS DE L'INTERFACE D'ADMINISTRATION = */
	public function admin_enqueue_scripts()
	{
		wp_enqueue_style( 'tiFyPluginsEventsTabooxPostDateEditorAdminDateEditor', self::tFyAppUrl() .'/DateEditor.css', array( 'tify_control-touch_time', 'tify_control-dynamic_inputs'  ), 150610 );
		wp_enqueue_script( 'tiFyPluginsEventsTabooxPostDateEditorAdminDateEditor', self::tFyAppUrl() .'/DateEditor.js', array( 'jquery', 'moment', 'tify_control-touch_time', 'tify_control-dynamic_inputs' ), 150610, true );
		wp_localize_script(
			'tiFyPluginsEventsTabooxPostDateEditorAdminDateEditor',
			'tify_events',
			array(
				'date_range_error' => __( 'La date de début est supérieur à la date de fin', 'tify' )
			)
		);
	}
	
	/* = FORMULAIRE DE SAISIE = */
	public function form( $post )
	{
		$events = \tiFy\Plugins\Events\Query::PostGetEvents( $post );
		
		// Default
		$events 		= $this->ParseEvents( $events );
		$sample_html 	= $this->SampleDefault();	
	
		$args = array( 
			'sample_html' 				=> $sample_html, 
			'name' 						=> 'tify_event', 
			'values' 					=> $events,
			'max'						=> (int) $this->args['max']
		);
		
		try{
			$start 	= new \DateTime( $this->args['start_datetime'] );
		} catch( \Exception $e ){
			$start 	= new \DateTime( date( 'Y-m-d 00:00:00', current_time( 'timestamp' ) ) );
		}
		try{
			$end 	= new \DateTime( $this->args['end_datetime'] );
		} catch( \Exception $e ){
			$end 	= new \DateTime( date( 'Y-m-d 23:59:59', current_time( 'timestamp' ) ) );
		}

		$args['default'] = array(
			'event_start_date' 		=> $start->format( 'Y-m-d' ),
			'event_start_time' 		=> $start->format( 'H:i:s' ),
			'event_end_date' 		=> $end->format( 'Y-m-d' ),
			'event_end_time' 		=> $end->format( 'H:i:s' ),			
			'event_post_id' 		=> $post->ID, 
			'event_id' 				=> 0,
		);
	?>
		<div class="tify_events-taboox">
			<?php tify_control_dynamic_inputs( $args ); ?>
		</div>
	<?php
	}
	
	/** == == **/
	private function SampleDefault()
	{
		$before  = "";
		$before .= "<div class=\"DateEdit-item\">\n";
		$before .= "\t<input type=\"hidden\" class=\"tify_event_id\" name=\"%%name%%[%%index%%][event_id]\" value=\"%%value%%[event_id]\">\n";
		$before .= "\t<input type=\"hidden\" class=\"tify_event_post_id\"  name=\"%%name%%[%%index%%][event_post_id]\" value=\"%%value%%[event_post_id]\">\n";
		
		// Date de début
		$start_date  = "";
		$start_date .= "<div class=\"DateEdit-item--control DateEdit-itemStartDate\">";
		$start_date .= "<label>". __( 'Du', 'tify' ) ."</label>";
		$start_date .= tify_control_touch_time( 
			array( 
				'name' 				=> '%%name%%[%%index%%][event_start_date]', 
				'value' 			=> '%%value%%[event_start_date]',
				'container_id' 		=> 'tify_event_start_date-wrapper-%%index%%',
				'container_class' 	=> 'tify_event_start_date-wrapper',
				'id' 				=> 'tify_event_start_date-%%index%%',
				'type'				=> 'date', 
				'echo' 				=> 0,
				'debug'				=> false
			) 
		);
		$start_date .= "</div>";
		
		// Date de fin
		$end_date  = "";
		$end_date .= "<div class=\"DateEdit-item--control DateEdit-itemEndDate\">";
		$end_date .= "<label>". __( 'Au', 'tify' ) ."</label>";
		$end_date .= tify_control_touch_time( 
			array( 
				'name' 				=> '%%name%%[%%index%%][event_end_date]', 
				'value' 			=> '%%value%%[event_end_date]',
				'container_id' 		=> 'tify_event_end_date-wrapper-%%index%%',
				'container_class' 	=> 'tify_event_end_date-wrapper',
				'id' 				=> 'tify_event_end_date-%%index%%',
				'type'				=> 'date',
				'echo' 				=> 0,
				'debug'				=> false
			) 
		);
		$end_date .= "</div>";
		
		// Heure de début
		$start_time  = "";
		$start_time .= "<div class=\"DateEdit-item--control DateEdit-itemStartTime\">";
		$start_time .= "<label>". __( 'Depuis', 'tify' ) ."</label>";
		$start_time .= tify_control_touch_time( 
			array( 
				'name' 				=> '%%name%%[%%index%%][event_start_time]', 
				'value' 			=> '%%value%%[event_start_time]',
				'container_id' 		=> 'tify_event_start_time-wrapper-%%index%%',
				'container_class' 	=> 'tify_event_start_time-wrapper',
				'id' 				=> 'tify_event_start_time-%%index%%',
				'type'				=> 'time', 
				'echo' 				=> 0,
				'debug'				=> false
			) 
		);
		$start_time .= "</div>";
				
		// Heure de début
		$end_time  = "";
		$end_time .= "<div class=\"DateEdit-item--control DateEdit-itemEndTime\">";
		$end_time .= "<label>". __( 'Jusqu\'à', 'tify' ) ."</label>";
		$end_time .= tify_control_touch_time( 
			array( 
				'name' 				=> '%%name%%[%%index%%][event_end_time]', 
				'value' 			=> '%%value%%[event_end_time]',
				'container_id' 		=> 'tify_event_end_time-wrapper-%%index%%',
				'container_class' 	=> 'tify_event_end_time-wrapper',
				'id' 				=> 'tify_event_end_time-%%index%%',
				'type'				=> 'time', 
				'echo' 				=> 0,
				'debug'				=> false
			) 
		);
		$end_time .= "</div>";
		
		// Prévisualisation
		$preview  = "";
		$preview .= "<div class=\"DateEdit-itemPreview\">";
		//$preview .= "<span class=\"dashicons dashicons-visibility\"></span>";
		$preview .= "<textarea readonly=\"readonly\" autocomplete=\"off\"></textarea>";
		$preview .= "</div>";
		
		$after = "</div>\n";
		
		$output = "";
		$output .= $before;
		switch( $this->args['format'] ) :
			default :
			case 'date' :
				$output .= sprintf( '%1$s %2$s %3$s %4$s %5$s', $start_date, $start_time, $end_date, $end_time, $preview );
				break;
			case 'show' :
				$output .= sprintf( '%1$s %3$s %2$s %4$s %5$s', $start_date, $start_time, $end_date, $end_time, $preview );
				break;
		endswitch;
		$output .= '<div style="clear:both;"></div>';
		$output .= apply_filters( 'tify_events_custom_fields', '' );
		$output .= $after;
		
		return $output;
	}
	
	/* = CONTROLEURS = */
	/** == Traitement des données == **/
	private function ParseEvents( $events )
	{
		$_events = array();
		
		foreach( (array) $events as $k => $event ) :		
			$_events[$k]['event_id'] 		= $event->event_id;			
			$_events[$k]['event_post_id'] 	= $event->event_post_id;
			
			// Date de début de l'événement
			$s = new \DateTime( $event->event_start_datetime );			
			if( $s->format('Y') < 0 ) :
				$_events[$k]['event_start_date'] = date( 'Y-m-d', current_time( 'timestamp' ) );
			else :
				$_events[$k]['event_start_date'] = $s->format( 'Y-m-d' );
			endif;			
			$_events[$k]['event_start_time'] = $s->format( 'H:i:s' );			
			
			// Date de fin de l'événement
			$e = new \DateTime( $event->event_end_datetime );
			if( $e->format( 'Hi' ) < $s->format( 'Hi' ) ) :
				$e->sub( new \DateInterval( 'P1D' ) );	
			endif;			
			$_events[$k]['event_end_date'] = $e->format( 'Y-m-d' );
			$_events[$k]['event_end_time'] = $e->format( 'H:i:s' ); 
			
			// Metadonnées de l'événement
			if( $metas = tify_events_get_meta( $event->event_id ) )  :
				foreach( (array) $metas as $meta_key => $meta_value ) :
					$_events[$k][$meta_key] = current( $meta_value );
				endforeach;
			endif;
		endforeach;
			
		return $_events;	
	}
	
	/** == Prévisualisation == **/
	private function Preview( $event_id = 0, $start_datetime = null, $end_datetime = null )
	{
		$output = "";
		if( $event = \tiFy\Plugins\Events\Query::GetEvent( $event_id ) ) :	
		
			$event->start_datetime	= ! empty( $_POST['start_datetime'] ) 	? $_POST['start_datetime'] 	: $event->event_start_datetime;
			$event->end_datetime	= ! empty( $_POST['end_datetime'] ) 	? $_POST['end_datetime'] 	: $event->event_end_datetime;						
			$dates 					= \tiFy\Plugins\Events\Query::EventParseAttrs( $event );

			foreach( $dates as $date ) :
				$output .= tify_events_date_display( $date, false ) ."\n";
			endforeach;
		endif;
		
		return $output;
	}
	
	/** == Traitement des données == **/
	private function ParseDatas( $datas )
	{
		foreach( $datas as $k => &$data ) :
			if( isset( $data['event_start_date'] ) && isset( $data['event_start_time'] ) ) :
				$data['event_start_datetime'] = $data['event_start_date'] ." ". $data['event_start_time']; 
				unset( $datas[$k]['event_start_date'] ); unset( $datas[$k]['event_start_time'] );
			endif;
			if( isset( $data['event_end_date'] ) && isset( $data['event_end_time'] ) ) :
				$data['event_end_datetime'] = $data['event_end_date'] ." ". $data['event_end_time'];
				unset( $datas[$k]['event_end_date'] ); unset( $datas[$k]['event_end_time'] ); 
			endif;
			if( empty( $data['event_start_datetime'] ) || empty( $data['event_end_datetime'] ) ) :
				unset( $datas[$k] ); continue;
			endif;			
		endforeach;
			
		return $datas;	
	}
	
	/* = ACTIONS = */
	/** == Modification de la balise du formulaire de saisie Backoffice == **/
	final public function post_edit_form_tag( $post )
	{
		// Bypass
		if( ! \tiFy\Plugins\Events\Events::IsPostType( $post->post_type ) )
			return;
		
		echo " autocomplete=\"off\" ";
	}
	
	/** == Action Ajax == **/
	final public function wp_ajax()
	{
		$event_id 		= $_POST['event_id'];
		$start_datetime	= ! empty( $_POST['start_datetime'] ) ? $_POST['start_datetime'] : null;
		$end_datetime	= ! empty( $_POST['end_datetime'] ) ? $_POST['end_datetime'] : null;
		
		echo $this->Preview( $event_id, $start_datetime, $end_datetime );
		exit;
	}
	
	/** == Sauvegarde des posts == **/
	final public function save_post( $post_id, $post )
	{
		// Bypass
		/// Contrôle s'il s'agit d'une routine de sauvegarde automatique.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		/// Contrôle si le script est executé via Ajax.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			return;
		/// Contrôle si le requête contenant l'indication de type de post est définie 		
		if( ! $post_type = $post->post_type )
			return;
		// Vérifie si le post répond à un enregistrement d'évnements
		if( ! \tiFy\Plugins\Events\Events::IsPostType( $post_type ) )
			return;	
	
		// Contrôle des permissions d'édition de l'utilisateur courant
	  	if ( ( 'page' === $post_type ) && ! current_user_can( 'edit_page', $post_id ) )
	  		return;
	  	if( ( 'page' !== $post_type ) && ! current_user_can( 'edit_post', $post_id ) )
			return;
		
		$Db = tify_db_get( 'tify_events' );
			
		// Suppression des événements
		if( $exists = $Db->select()->col( 'event_id', array( 'post_id' => $post_id ) ) ) :
			$save = array();
			if( isset( $_POST['tify_event'] ) ) :
				foreach( $_POST['tify_event'] as $event ) :
					array_push( $save, $event['event_id'] );
				endforeach;
			endif;
				
			foreach( $exists as $id ) :
				if( empty( $save ) || ! in_array( $id, $save ) ) :
					$Db->handle()->delete_by_id( $id );
				endif;
			endforeach;
		endif;
		
		// Enregistrement des événement
		if( ! empty( $_POST['tify_event'] ) ) :
			$datas = $this->ParseDatas( $_POST['tify_event'] );	
			foreach( $datas as $id => $e ) :								
				$start = new \DateTime( $e['event_start_datetime'] ); $end = new \DateTime( $e['event_end_datetime'] );

				if( $start > $end ) :
					$e['event_end_datetime'] = $start->format( 'Y-m-d' ) .' '. $end->format( 'H:i:s' );
					$end = new \DateTime( $e['event_end_datetime'] );
				endif;
				
				if( $start->format( 'Hi' ) > $end->format( 'Hi' ) ) :
					$end->add( new \DateInterval( 'P1D' ) );
					$e['event_end_datetime'] = $end->format( 'Y-m-d H:i:s' );
				endif;

				$Db->handle()->record( $e );
			endforeach;
		endif;
	}
}