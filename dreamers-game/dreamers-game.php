<?php
/**
 * Plugin Name: Return to Dreamland
 * Plugin URI: http://github.com/BitPrepared/dreamland-wp-plugins
 * Description: Plugin base di dreamland
 * Version: 0.1
 * Author: BitPrepared
 * Author URI: http://github.com/BitPrepared 
 * License: GPLv3
 */

//SETUP
function rtd_install(){

    //add_role( $role_name, $display_name, $capabilities );
    add_role( 'utente_eg', 'Esploratore / Guida', array( "read" => true) );
    add_role( 'capo_reparto', 'Capo Reparto', array( "read" => true) );
    add_role( 'iabz', 'Incaricato alle branche di zona', array( "read" => true) );
    add_role( 'iabr', 'Incaricato alle branche di regione', array( "read" => true) );
    add_role( 'referente_regionale', 'Referente Sfide Regionali', array( "read" => true) );
}
register_activation_hook(__FILE__,'rtd_install');
function rtd_uninstall(){
    remove_role('utente_eg');
    remove_role('capo_reparto');
    remove_role('iabz');
    remove_role('iabr');
    remove_role('referente_regionale');
}
register_deactivation_hook(__FILE__,'rtd_uninstall');


//DASHBOARD
add_action( 'wp_dashboard_setup', 'remove_wp_dashboard_widgets' );
function remove_wp_dashboard_widgets() {
  
  if ( !current_user_can('create_users') ) {
    //Plugins
    wp_unregister_sidebar_widget( 'dashboard_plugins' );
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal');

    //Right Now - At Glace
    wp_unregister_sidebar_widget( 'dashboard_right_now' );
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');

    //Recent Comments
    wp_unregister_sidebar_widget( 'dashboard_recent_comments' );
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

    //Incoming Links
    wp_unregister_sidebar_widget( 'dashboard_incoming_links' );
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');

    //WordPress Blog
    wp_unregister_sidebar_widget( 'dashboard_primary' );
    remove_meta_box('dashboard_primary', 'dashboard', 'side');

    //Other WordPress News
    wp_unregister_sidebar_widget( 'dashboard_secondary' ); 
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');

    //Quick Press
    wp_unregister_sidebar_widget( 'dashboard_quick_press' ); 
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');

    //Recent Drafts
    wp_unregister_sidebar_widget( 'dashboard_recent_drafts' ); 
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');

    //Activity
    wp_unregister_sidebar_widget( 'dashboard_activity' ); 
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');//since 3.8

    if ( is_multisite() ) {
        //MIEI SITI
        remove_submenu_page('dashboard', 'my-sites');
    }

  }

}


//MESSAGING ADMIN ALERT
function showMessage($message, $errormsg = false)
{
    if ($errormsg) {
        echo '<div id="message" class="error">';
    }
    else {
        echo '<div id="message" class="updated fade">';
    }
    echo "<p><strong>$message</strong></p></div>";
}
function showAdminMessages()
{

    if ( isset($_GET) && isset($_GET['errore']) ){
        $errore = htmlentities($_GET['errore']);
        switch ($errore) {
            case 'no_new_sfida':
                $testo = 'Non puoi inserire un racconto senza prima aver richiesto la chiusura di una sfida';
                break;
            default:
                $testo = 'Errore generico';
                break;
        }
        showMessage($testo, true);
    }
}
add_action('admin_notices', 'showAdminMessages');


//SESSION SETUP
add_action('init', 'dreamer_session', 1);
function dreamer_session() {
    if(!session_id()) {
        session_start();
    }
}


//EMAIL MULTIPLE x SINGOLO SITO
function skip_email_exist($user_email){

    define( 'WP_IMPORTING', 'SKIP_EMAIL_EXIST' );
    return $user_email;
}
// This hook should run before user email validation
add_filter( 'pre_user_email', 'skip_email_exist');

// LOGIN / LOGOUT (https://github.com/gedex/wp-better-hipchat/issues/3)
add_filter( 'hipchat_get_events', function( $events ) {
    $events['user_login'] = array(
        'action'      => 'wp_login',
        'description' => __( 'When user logged in', 'better-hipchat' ),
        'message'     => function( $user_login ) {
            return sprintf( '%s is logged in', $user_login );
        }
    );
    return $events;
} );


//REDIRECT DOPO IL LOGIN
function send_to_dashboard($user_login, $user){

    if ( user_can($user,'abilita_eg') ) {
        _log('redirect to admin url '.get_admin_url());
        wp_redirect(get_admin_url().'admin.php?page=dreamers');
        exit;
    }

    if ( user_can($user,'view_sfide_review') ) {
        _log('redirect to admin url '.get_admin_url());
        wp_redirect(get_admin_url());
        exit;
    }

}
add_action('wp_login', 'send_to_dashboard', 10, 2);


// PULIZIA PROFILO
function prefix_hide_personal_options() {
    if (current_user_can('manage_options')) return false;
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function( $ ){
            $("#nickname,#first_name,#last_name,#display_name").parent().parent().remove();
        });
    </script>
<?php
}
add_action('personal_options', 'prefix_hide_personal_options');
remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );


//LOGGING
if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}

