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
    $role = get_role('editor');
    $role->add_cap('manage_eg');

    $role = get_role('administrator');
    $role->add_cap('manage_eg');

    $role = get_role('capo_reparto');
    if ( $role != null ){
        $role->add_cap('abilita_eg');
    }
}

register_activation_hook(__FILE__,'rtd_manager_install');

function rtd_manager_uninstall(){
    // When a role is removed, the users who have this role lose all rights on the site.
    // remove_role('capo_reparto');
    
    $role = get_role('editor');
    $role->remove_cap('manage_eg');

    $role = get_role('administrator');
    $role->remove_cap('manage_eg');

    $role = get_role('capo_reparto');
    if ( $role != null ){
        $role->remove_cap('abilita_eg');
    }
}

register_deactivation_hook(__FILE__,'rtd_manager_uninstall');

function json_pre_insert_user_dreamers($user , $data) {
    // $user->meta = $data['meta']; return $user;
    return $user;
}
add_filter('json_pre_insert_user','json_pre_insert_user_dreamers', 10 , 2);

function user_notification_password($user_id) {
    $user = get_userdata( $user_id );

    $plaintext_pass = $user->user_pass;

    $message  = "Benvenuti in Dreamland \r\n\r\n";
    $message .= "Utilizza queste credenziali per accedere al pannello di dreamland. \n\n";
    $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n";
    $message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
    $message .= sprintf(__('Authorization code : %s'), 'dr3aml4and') . "\r\n";
    $message .= 'il campo "Yubikey OTP" va lasciato vuoto' . "\r\n";
    $message .= 'Pannello : '.wp_login_url() . "\r\n";

    if ( defined('RTD_DEVELOP') && !RTD_DEVELOP ) {
        wp_mail(get_option('admin_email'), 'New User Registration', $message);
        wp_mail($user->user_email, 'Benvenuti in Dreamland', $message);
    }
}

function inserted_user_dreamers($user , $data, $update) {
    if ( !$update ) {
        $user_id = $user->ID;
        _log('inserted_user_dreamers '.$user_id);

        // @see http://codex.wordpress.org/Function_Reference/wp_update_user

        if ( isset($data['meta'] )) {
            foreach ($data['meta'] as $key => $value) {
                //add_user_meta( $user_id, $meta_key, $meta_value, $unique );
                //http://codex.wordpress.org/Function_Reference/add_user_meta
                if ( add_user_meta( $user_id, $key, $value, true) === false ){
                    _log('impossibile inserire  '.$key.' con value '.$value.' per user '.$user_id);
                }
            }

            if ( strcmp($data['meta']['ruolocensimento'], 'capo_reparto') == 0 ) {
                $u = new WP_User( $user_id );
                $u->add_role( 'capo_reparto' );
            }
        }
        $random_password = wp_generate_password( 12, false );
        wp_set_password( $random_password, $user_id );
        user_notification_password($user_id);
        _log('generato '.$random_password);
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

    ?>

    <h1>Dreamers Join Request</h1>
    <table class="widefat fixed" cellspacing="0">
        <thead>
        <tr>
            <th id="columnname" class="manage-column column-columnname" scope="col">R</th> 
            <th id="columnname" class="manage-column column-columnname" scope="col">Gruppo</th> 
            <th id="columnname" class="manage-column column-columnname" scope="col">Squadriglia</th>
            <th id="columnname" class="manage-column column-columnname num" scope="col">Censimento</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Ruolo</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Azioni</th> 
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th class="manage-column column-columnname" scope="col"></th>
            <th class="manage-column column-columnname" scope="col"></th>
            <th class="manage-column column-columnname" scope="col"></th>
            <th class="manage-column column-columnname num" scope="col"></th>
            <th class="manage-column column-columnname" scope="col"></th>
            <th class="manage-column column-columnname" scope="col"></th>
        </tr>
        </tfoot>
        <tbody>
    <?php

    //@see http://wordpress.stackexchange.com/questions/66486/return-all-users-with-a-specific-meta-key
    $args = array(
        'role' => 'subscriber',
        'meta_key'     => 'codicecensimento',
        // 'meta_value'   => '*',
        'orderby'      => 'lastname',
        'order'        => 'ASC'
    );

    $res = get_users( $args ); //WP_User array
    foreach ($res as $rownumber => $user) {
        
        if ( $rownumber % 2 == 0 ) {
            echo '<tr class="alternate">';
        } else {
            echo '<tr>';
        }

        $skip = false;

        $id = $user->ID;
        $all_meta_for_user = get_user_meta( $id );
        $user_info = get_userdata( $id );

        $gruppo = $all_meta_for_user['groupDisplay'][0];
        if ( !current_user_can('manage_eg') ) {
            
            $myUserId = get_current_user_id();
            $all_meta_mime = get_user_meta( $myUserId );

            if (  strcasecmp($all_meta_mime['group'], $all_meta_for_user['group']) != 0 ) {
                $skip = true;
            }

        }

        if ( !$skip ) {
        
            $ruolocensimento = $all_meta_for_user['ruolocensimento'];

            echo '<td class="column-columnname">'.$ruolocensimento.'</td>';
            echo '<td class="column-columnname">'.$gruppo.'</td>';
            echo '<td class="column-columnname">'.$user_info->first_name.'</td>';
            echo '<td class="column-columnname num">'.$all_meta_for_user['codicecensimento'][0].'</td>';
            echo '<td class="column-columnname">'.implode(', ', $user_info->roles).'</td>';

            echo '<td class="column-columnname">';

            if ( current_user_can('abilita_eg') || current_user_can('manage_eg') )
            {
                echo '<form method="POST" action="'. admin_url( 'admin.php' ) .'">';
                echo '<input type="hidden" name="action" value="rtdautorizzaeg" />';
                echo '<input type="hidden" name="selecteduser" value="'.$id.'" />';
                echo '<input type="submit" value="Autorizza E/G" />';
                echo '</form>';
            }
            echo '</td>';

        }

    }
    ?>

        </tbody>
    </table>

    <h1>Dreamers</h1>
    <table class="widefat fixed" cellspacing="0">
        <thead>
        <tr>
            <th id="columnname" class="manage-column column-columnname" scope="col">R</th> 
            <th id="columnname" class="manage-column column-columnname" scope="col">Gruppo</th> 
            <th id="columnname" class="manage-column column-columnname" scope="col">Squadriglia</th>
            <th id="columnname" class="manage-column column-columnname num" scope="col">Censimento</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Ruolo</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Azioni</th> 
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th class="manage-column column-columnname" scope="col"></th>
            <th class="manage-column column-columnname" scope="col"></th>
            <th class="manage-column column-columnname" scope="col"></th>
            <th class="manage-column column-columnname num" scope="col"></th>
            <th class="manage-column column-columnname" scope="col"></th>
            <th class="manage-column column-columnname" scope="col"></th>
        </tr>
        </tfoot>
        <tbody>
    <?php

    $args = array(
        'role' => 'utente_eg',
        // 'meta_key'     => 'codicecensimento',
        // 'meta_value'   => '*',
        'orderby'      => 'lastname',
        'order'        => 'ASC'
    );

    $res = get_users( $args ); //WP_User array
    foreach ($res as $rownumber => $user) {
        
        if ( $rownumber % 2 == 0 ) {
            echo '<tr class="alternate">';
        } else {
            echo '<tr>';
        }

        $id = $user->ID;
        $all_meta_for_user = get_user_meta( $id );
        $user_info = get_userdata( $id );

        $ruolocensimento = $all_meta_for_user['ruolocensimento'];

        echo '<td class="column-columnname">'.$ruolocensimento.'</td>';
        echo '<td class="column-columnname">'.$all_meta_for_user['groupDisplay'][0].'</td>';
        echo '<td class="column-columnname">'.$user_info->first_name.'</td>';
        echo '<td class="column-columnname num">'.$all_meta_for_user['codicecensimento'][0].'</td>';
        echo '<td class="column-columnname">'.implode(', ', $user_info->roles).'</td>';

        echo '<td class="column-columnname">';

        if ( current_user_can('abilita_eg') || current_user_can('manage_eg') )
        {
            echo '<form method="POST" action="'. admin_url( 'admin.php' ) .'">';
            echo '</form>';
        }
        echo '</td>';

    }
    ?>

        </tbody>
    </table>

    <?php

}

// @see http://wordpress.stackexchange.com/questions/10500/how-do-i-best-handle-custom-plugin-page-actions

add_action( 'admin_action_rtdautorizzaeg', 'rtdautorizzaeg_admin_action' );

function rtdautorizzaeg_admin_action()
{

    $can = current_user_can('abilita_eg') || current_user_can('manage_eg');

    if ( isset($_POST['selecteduser']) && $can ) {

        // Do your stuff here
        $user_id = $_POST['selecteduser'];
        
        $u = new WP_User( $user_id );

        // Remove role
        $u->remove_role( 'subscriber' );

        // Add new roles
        $u->add_role( 'contributor' );
        $u->add_role( 'utente_eg' );

    } else {
        _log('invalid request rtdautorizzaeg_admin_action, missing selecteduser or invalid permission '.var_export($can, true));
    }

    wp_redirect( $_SERVER['HTTP_REFERER'] );
    exit();

}

function gestione_ruoli_menu() {

    // add_menu_page( page_title, menu_title, capability, menu_slug, function, icon_url, position );
    add_menu_page( 'Dreamers', 'Dreamers','manage_eg', 'dreamers', 'gestione_ruoli_menu_page',plugins_url( '/dreamers-manager/images/icon-eg-16x16.png', 2 ) );
    

    //add_dashboard_page( $page_title, $menu_title, $capability, $menu_slug, $function);
    // add_dashboard_page('Dreames Dashboard', 'DreamesDashboard', 'manage_eg', 'dreams-manage-dashboard', '??dashboard_dream??');
}

// @see: https://nayeemmodi.wordpress.com/2011/06/20/creating-menus-and-submenus-in-wordpress/
add_action('admin_menu', 'gestione_ruoli_menu');

