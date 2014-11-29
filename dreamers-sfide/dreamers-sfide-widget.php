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
	        'posts_per_page'   => 10,
	        'offset'           => 0,
	        'orderby'          => 'post_date',
	        'order'            => 'DESC',
	        'include'          => '',
	        'exclude'          => '',
	        'meta_key'         => '',
	        'meta_value'       => '',
	        'post_type'        => 'sfida_event',
	        'post_mime_type'   => '',
	        'post_parent'      => '',
	        'post_status'      => 'publish',
	        'suppress_filters' => true
    	);

    	$posts_array = get_posts($args);

    	foreach ($posts_array as $key => $value) {
    		$r = get_post_meta($value->ID, '_regione');
    		if($r[0] === "CM_NAZ" && is_sfida_alive($value)){
    			echo "<p><a href=\"" . get_permalink($value->ID) . " \">" . $value->post_title . "</a></p>";
    		}
    	}

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
