<?php

require_once('dreamers-sfide-utils.php');

class rtd_sfide_widget extends WP_Widget {

	function __construct() {
	parent::__construct(

		// Base ID of your widget
		'rtd_sfide_widget', 

		// Widget name will appear in UI
		__('Trova le sfide', 'rtd_sfide_widget_domain'), 

		// Widget description
		array( 'description' => __( 'Un widget per trovare le sfide a cui partecipare', 'rtd_sfide_widget_domain' ), ) 
		);
	}

	// Creating widget front-end
	// This is where the action happens
	public function widget( $args, $instance ) {
	

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];

		if(isset($args['before_title']))
			echo $args['before_title'];
		
		echo "SFIDE NAZIONALI" ;

		if(isset($args['after_title']))
			echo $args['after_title'];

		// This is where you run the code and display the output
		$args = array(
	        'posts_per_page'   => 20,
	        'offset'           => 0,
	        'orderby'          => 'post_date',
	        'order'            => 'DESC',
	        'include'          => '',
	        'exclude'          => '',
	        'meta_key'         => '_regione',
	        'meta_value'       => 'CM_NAZ',
	        'post_type'        => 'sfida_event',
	        'post_mime_type'   => '',
	        'post_parent'      => '',
	        'post_status'      => 'publish',
	        'suppress_filters' => true
    	);

    	$posts_array = get_posts($args);

    	echo "<ul>";
    	foreach ($posts_array as $key => $value) {
    		if(is_sfida_alive($value)){
    			$icons = get_icons_for_sfida($value);
    			$res = "<li><a href=\"" . get_permalink($value->ID) . " \">" . $value->post_title . "</a>";
    			foreach ($icons as $key => $icon) {
    				$res = $res . '<img style="width:25px; margin: 3px 0px -5px 0px;" src="' . $icon['src'] .
    				 '" alt="' . $icon['caption'] . '" title="'. $icon['caption'] .'" />'; 
    			}
    			$res = $res . "</li>";
    			echo $res;
    		}
    	}
    	echo "</ul>";

    	if(isset($args['after_widget']))
			echo $args['after_widget'];
	}
		
	// Widget Backend 
	public function form( $instance ) {
	 
	}
	
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
} // Class wpb_widget ends here

/*
class Rtd_Sfide_Popolari extends WP_Widget {

	public $query = "SELECT um.meta_value, p.post_title, count(*) ".
		"FROM dr_usermeta AS um ".
		"LEFT JOIN dr_posts AS p ON um.meta_value = p.ID ".
		"WHERE um.meta_key LIKE '_iscrizioni' ".
		"AND p.post_type LIKE 'sfida_event' ".
		"GROUP BY meta_value ".
		"ORDER BY count(*) DESC";


}
*/
