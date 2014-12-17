<?php
/**
 * Plugin Name: Return To Dreamland Portal bridge
 * Plugin URI: http://github.com/BitPrepared/dreamland-wp-plugins
 * Description: Plugin ponte tra portal e wordpress
 * Version: 0.1
 * Author: Bit Prepared
 * Author URI: http://github.com/BitPrepared 
 * License: GPLv3
 */

//SETUP
function rtd_portal_install(){
    /**
     * Detect plugin. For use in Admin area only.
     */
    if ( !is_plugin_active( 'dreamers-game/dreamers-game.php' ) ) {
      //plugin is activated
      wp_die('<p>The <strong>Dreamers Portal Bridge</strong> plugin requires plugin Dreamers-game','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
    }
}

register_activation_hook(__FILE__,'rtd_portal_install');

add_filter('register', 'register_url_portal');
function register_url_portal($link) {
    if(!is_user_logged_in()) {
        $link = '<a href="' . site_url('../portal/') . '">' . __('Register') . '</a>';
    }

    return $link;
}

// This function wraps around the main redirect function to determine whether or not to bypass the WordPress local URL limitation
function redirect_wrapper_after_login( $redirect_to, $requested_redirect_to, $user ) {
  // If they're on the login page, don't do anything
  if( !isset( $user->user_login ) ) {
      return $redirect_to;
  }

  if( is_array( $user->roles ) ) {
    
    if( in_array( 'administrator', $user->roles )
        ||
        in_array( 'utente_eg', $user->roles )
        ||
        in_array( 'capo_reparto', $user->roles )
        ||
        in_array( 'referente_regionale', $user->roles )
         ) {
      return admin_url();

    } else {

      if( in_array( 'iabz', $user->roles ) || in_array( 'iabr', $user->roles ) ) {
        // LINK ESTERNO
        // wp_redirect( $rul_url );
        // die();
      }

      return site_url();
    }
  }

}
add_filter( 'login_redirect', 'redirect_wrapper_after_login', 10, 3 );

function prevent_no_portal_register( $user_login, $user_email, $errors ) {
  $errors->add( 'bad_email_domain', '<strong>ERROR</strong>: Usare il corretto url di registrazione' );
}
add_action( 'register_post', 'prevent_no_portal_register', 10, 3 );

function evaluateUserState($id) {
  $user_info = get_userdata( $id );

  $all_meta_for_user = get_user_meta( $id );

  $codicecensimento = null;
  if ( isset($all_meta_for_user['codicecensimento'][0]) ) {
    $codicecensimento = $all_meta_for_user['codicecensimento'][0];
  }
 
  //session_regenerate_id(true); 

  $_SESSION['wordpress'] = array(
    'user_id' => $id,
    'user_info' => array(
        'user_login' => $user_info->data->user_login,
        'user_registered' => $user_info->data->user_registered,
        'roles' => $user_info->roles,
        'email' => $user_info->user_email,
        'codicecensimento' => $codicecensimento
    ),
    'logout_url' => wp_logout_url( home_url() )
  );
}

// @see: http://wordpress.stackexchange.com/questions/72481/hook-for-fail-and-successful-login-actions
// @see: http://wordpress.stackexchange.com/questions/101637/wp-login-action-hook-not-working
// $user e' WP_User 
function login_portal($user_login, $user) {
  $id = $user->ID;
  _log('Utente '.$user_login.' id : '.$id);
  evaluateUserState($id);
}
add_action('wp_login', 'login_portal', 10, 2);

function logout_portal() {
  // Desetta tutte le variabili di sessione.
  $_SESSION = array();
  session_unset();
  // Infine , distrugge la sessione.
  session_destroy();
  _log('Logout');
}
add_action('wp_logout', 'logout_portal');

function refresh_login() {
  if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    evaluateUserState($current_user->ID);
  }
}

add_action('init', 'refresh_login');
