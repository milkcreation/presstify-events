<?php
namespace tiFy\Plugins\Events;

class Calendar extends \tiFy\Lib\Calendar
{
	/* = ARGUMENTS = */
	// Classe de rappel des événements d'une journée
	public $QueryDayEvents = null;
	
	// Evénénements d'une journée
	public $DayEvents		= null;
	
	/* = AFFICHAGE = */
	/** == Affichage du calendrier == **/
	public function display( $date = null, $echo = true ) 
	{		
		$this->_parse_date( $date );
				
		$output = 	"<div id=\"tify_calendar\" class=\"tify_calendar\" data-action=\"{$this->id}\">".
						$this->overlay() .
						$this->nav() .
						$this->header() .
						$this->dates().
						$this->selected_events_lists();
		$output  .=	"</div>\n";
											
		if( $echo )
			echo $output;
		else
			return $output; 
    }  
    
    /** == Liste des événements du jour sélectionné == **/
    public function selected_events_lists()
    {
    	$this->QueryDayEvents();
    	
     	$output = "";
     	if( $this->QueryDayEvents->have_posts() ) :
			$output .= "\t<ul class=\"events_list\">";
			while( $this->QueryDayEvents->have_posts() ) : $this->QueryDayEvents->the_post();
				$output .= 	"\t\t<li>\n";				
				$output .= 	"\t\t\t<a href=\"". get_permalink() ."\">";
				$output .= 	"\t\t\t\t". get_the_title() ."\n".
							"\t\t\t</a>\n".
							"\t\t</li>\n";							
			endwhile;
			$output .= "\t</ul>";		
		endif; 
		wp_reset_postdata();
		
		return $output;
    }
    
	/** == Rendu d'un jour == **/
	public function day_render( $day )
	{
		$query_args = array( 
			'post_type' 		=> \tiFy\Plugins\Events\Events::GetPostTypes(),
			'posts_per_page'	=> 1,
			'tyevquery' 		=> 'uniq', 
			'tyevfrom' 			=> $day->format( 'Y-m-d 00:00:00' ), 
			'tyevto' 			=> $day->format( 'Y-m-d  23:59:59' )
		);
		$DayExists = new \WP_Query( $query_args );
		
		// Vérification d'existance d'un événement
		if( $DayExists->have_posts() ) :
		//if( Query::RangeCount( $day->format( 'Y-m-d 00:00:00' ), $day->format( 'Y-m-d  23:59:59' ) ) ) :
			return "<a href=\"#\" data-toggle=\"". $day->format( 'Y-m-d' ) ."\" class=\"has_event\">". date_i18n( 'd', $day->getTimestamp() ) ."</a>";
		else :
			return "<span>". date_i18n( 'd', $day->getTimestamp() ) ."</span>";
		endif;
		
		wp_reset_postdata();
	}  
	   
    /* = CONTRÔLEUR = */
    /** == Récupération des événement lié à une journée == **/
    public function QueryDayEvents( $date = null, $query_args = array() )
    {
    	if( $date ) :
    		$Day	= new \DateTime( $date );
    	else :
    		$Day	= $this->selected;
    	endif;
    	
		$from 		= new \DateTime( $Day->format( 'Y-m-d' ) );
		$from->setTime( 0, 0, 0 ); 
		$to			= new \DateTime( $Day->format( 'Y-m-d' ) );
		$to->setTime( 23, 59, 59 );

		$defaults = array( 
			'post_type' 	=> \tiFy\Plugins\Events\Events::GetPostTypes(), 
			'tyevquery' 	=> 'uniq', 
			'tyevfrom' 		=> $from->format( 'Y-m-d H:i:s' ), 
			'tyevto' 		=> $to->format( 'Y-m-d H:i:s' ) 
		);
		$query_args = wp_parse_args( $query_args, $defaults );
		
		$this->QueryDayEvents 	= new \WP_Query;
		$this->DayEvents		= $this->QueryDayEvents->query( $query_args );
    }
}