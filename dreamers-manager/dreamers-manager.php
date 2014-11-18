<?php
/**
 * Plugin Name: Gestori persone in Return to Dreamland
 * Plugin URI: http://github.com/BitPrepared/dreamland-wp-plugins
 * Description: Plugin per la gestione dei "Sognatori"
 * Version: 0.1
 * Author: Bit Prepared
 * Author URI: http://github.com/BitPrepared 
 * License: GPLv3
 */

//SETUP
function rtd_manager_install(){

    /**
     * Detect plugin. For use in Admin area only.
     */
    if ( !is_plugin_active( 'dreamers-game/dreamers-game.php' ) ) {
      //plugin is activated
      wp_die('<p>The <strong>Dreamers Manager</strong> plugin requires plugin Dreamers-game','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
    }

    //add_role( $role_name, $display_name, $capabilities );
    // add_role( 'capo_reparto', 'Capo Reparto', array( 'manage_eg' ) );
    $role = get_role('author');
    if ( null != $role ){
    	$role->add_cap('manage_eg');
    }
}

register_activation_hook(__FILE__,'rtd_install');

function rtd_manager_uninstall(){
    // When a role is removed, the users who have this role lose all rights on the site.
    // remove_role('capo_reparto');
	$role = get_role('author');
    if ( null != $role ){
    	$role->remove_cap('manage_eg');
    }
}

register_deactivation_hook(__FILE__,'rtd_uninstall');

function json_pre_insert_user_dreamers($user , $data) {
    // $user->meta = $data['meta']; return $user;
}
add_filter('json_pre_insert_user','json_pre_insert_user_dreamers', 10 , 2);

function inserted_user_dreamers($user , $data, $update) {
    // $user->meta = $data['meta']; return $user;
    if ( !$update ) {
        if ( isset($data['meta'] )) {
            foreach ($data['meta'] as $key => $value) {
                //add_user_meta( $user_id, $meta_key, $meta_value, $unique );
                //http://codex.wordpress.org/Function_Reference/add_user_meta
                add_user_meta( $user_id, $key, $value, true);
            }
            //  wp_mail( $to, $subject, $message, $headers, $attachments )
            if ( wp_mail( $to, $subject, $message, $headers, $attachments ) ) {

            } else {
                // cosa faccio???
            }
        }
    }
}
// add_action( $tag, $function_to_add, $priority, $accepted_args );
add_action( 'json_insert_user', 'inserted_user_dreamers', 10, 3 );

function gestione_ruoli_menu_page(){

// $args = array(
//     'blog_id'      => $GLOBALS['blog_id'],
//     'role'         => '',
//     'meta_key'     => '',
//     'meta_value'   => '',
//     'meta_compare' => '',
//     'meta_query'   => array(),
//     'include'      => array(),
//     'exclude'      => array(),
//     'orderby'      => 'login',
//     'order'        => 'ASC',
//     'offset'       => '',
//     'search'       => '',
//     'number'       => '',
//     'count_total'  => false,
//     'fields'       => 'all',
//     'who'          => ''
//  );

    echo "Ragazzi da Autorizzare";
    $args = array(
        'role' => 'subscriber',
        'meta_key'     => '',
        'meta_value'   => '',
        'orderby'      => 'lastname',
        'order'        => 'ASC'
    );
    get_users( $args );

    echo "Ragazzi autorizzati";
    $args = array(
        'role' => 'utente_eg',
        'orderby'      => 'lastname',
        'order'        => 'ASC'
    );
    get_users( $args );

}

function gestione_ruoli_menu() {
    //add_dashboard_page( $page_title, $menu_title, $capability, $menu_slug, $function);
    add_dashboard_page('Dreames Manager', 'Dreames Manager', 'manage_eg', 'dreams-manage-dashboard', 'gestione_ruoli_menu_page');
}

add_action('admin_menu', 'gestione_ruoli_menu');

