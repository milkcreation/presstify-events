<?php
namespace tiFy\Plugins\Events\Components\CustomColumns\PostType;

use tiFy\Components\CustomColumns\Factory;

class EventsDate extends Factory
{
	/* = DEFINITION DES ARGUMENTS PAR DEFAUT = */
	public function getDefaults()
	{
		return array(
			'title'		=> 	__( 'Programmation', 'tify' ),
			'position'	=> 3
		);
	}
			
	/* = AFFICHAGE DU CONTENU DES CELLULES DE LA COLONNE = */
	public function content( $column, $post_id )
	{
		$output = "";
		if( ! $events = tify_events_get_the_dates( $post_id ) ) :
			$output .= __( 'Aucune date programmée', 'tify' );	
		else :
			$output .= "<ul style=\"margin:0;padding:0\">";
			foreach( $events as $e ) :
				if( ! $e['end_date'] ) :
					$output .= "<li style=\"margin:0;padding:0\">". sprintf( __( 'le: %1$s | Heure de début: %2$s, Heure de fin: %3$s', 'tify' ), $e['start_date'], $e['start_time'], $e['end_time'] ) ."</li>";
				else :
					$output .= "<li style=\"margin:0;padding:0\">". sprintf( __( 'Date de début: %1$s, Date de fin: %2$s | Heure de début: %3$s, Heure de fin: %4$s', 'tify' ), $e['start_date'], $e['end_date'], $e['start_time'], $e['end_time'] ) ."</li>";
				endif;
			endforeach;
			$output .= "</ul>";
		endif;
		
		echo $output;
	}
}