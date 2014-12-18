<?php
/**
 * Plugin Name: Return To Dreamland Dreamers
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
    $role->add_cap('abilita_eg');

    $role = get_role('administrator');
    $role->add_cap('manage_eg');
    $role->add_cap('abilita_eg');

    $role = get_role('capo_reparto');
    $role->add_cap('abilita_eg');
    
}

register_activation_hook(__FILE__,'rtd_manager_install');

function rtd_manager_uninstall(){
    // When a role is removed, the users who have this role lose all rights on the site.
    
    $role = get_role('editor');
    $role->remove_cap('manage_eg');

    $role = get_role('administrator');
    $role->remove_cap('manage_eg');

    $role = get_role('capo_reparto');
    $role->remove_cap('abilita_eg');
}

register_deactivation_hook(__FILE__,'rtd_manager_uninstall');

function json_pre_insert_user_dreamers($user , $data) {
    // $user->meta = $data['meta']; return $user;
    return $user;
}
add_filter('json_pre_insert_user','json_pre_insert_user_dreamers', 10 , 2);

function user_notification_password($user_id,$plaintext_pass,$ruolo) {
    $user = get_userdata( $user_id );

    $message  = "Benvenuti in Dreamland \r\n\r\n";
    $message .= "Utilizza queste credenziali per accedere a dreamland. \n\n";
    $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n";
    $message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
    $message .= sprintf(__('Authorization code : %s'), 'dr3aml4and') . "\r\n";
    $message .= 'Pannello : '.wp_login_url() . "\r\n";
    if ( strcmp($ruolo, 'eg') == 0 ) {
        $message .= 'Per poter usare il sito dovrai aspettare che il tuo capo reparto autorizzi la tua iscrizione.' . "\r\n";
        $message .= 'Riceverai una mail di notifica quando questa operazione sarà compiuta.' . "\r\n";
    }


    if ( !defined('RTD_DEVELOP') || !RTD_DEVELOP ) {
        wp_mail(get_option('admin_email'), 'New User Registration', $message);
        wp_mail($user->user_email, 'Benvenuti in Dreamland', $message);
    } else {
        _log('skip invio mail');
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

            $ruolo = $data['meta']['ruolocensimento'];

            if ( strcmp($ruolo, 'cr') == 0 ) {
                $u = new WP_User( $user_id );
                $u->remove_role('subscriber');
                $u->add_role('capo_reparto');
            }

            if ( strcmp($ruolo, 'rr') == 0 ) {
                $u = new WP_User( $user_id );
                $u->remove_role('subscriber');
                $u->add_role( 'referente_regionale' );
            }
        }
        $random_password = wp_generate_password( 12, false );
        wp_set_password( $random_password, $user_id );
        user_notification_password($user_id,$random_password,$ruolo);
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
    <table id="join-requests" class="widefat fixed" cellspacing="0">
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

            if (  strcasecmp($all_meta_mime['group'][0], $all_meta_for_user['group'][0]) != 0 ) {
                $skip = true;
            }

        }

        if ( !$skip ) {
        
            $ruolocensimento = $all_meta_for_user['ruolocensimento'][0];

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

            if ( current_user_can('delete_users') )
            {
                echo '<form method="POST" action="'. admin_url( 'admin.php' ) .'">';
                echo '<input type="hidden" name="action" value="rtddeleteeg" />';
                echo '<input type="hidden" name="selecteduser" value="'.$id.'" />';
                echo '<input type="submit" value="Delete E/G" />';
                echo '</form>';
            }

            echo '</td>';

        }

    }
    ?>

        </tbody>
    </table>

    <h1>Dreamers</h1>
    <table id="dreamers" class="widefat fixed" cellspacing="0">
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

        if ( !current_user_can('manage_eg') ) {
            
            $myUserId = get_current_user_id();
            $all_meta_mime = get_user_meta( $myUserId );

            if (  strcasecmp($all_meta_mime['group'][0], $all_meta_for_user['group'][0]) != 0 ) {
                continue;
            }

        }

        // todo gestione dell'errore in caso di dati mancanti? 

        if(isset($all_meta_for_user['ruolocensimento'])){
            $ruolocensimento = $all_meta_for_user['ruolocensimento'];
        } else {
            $ruolocensimento = array();
        }

        $groupDisplay = (isset($all_meta_for_user['groupDisplay'])) ? $all_meta_for_user['groupDisplay'][0] : "" ;
        $codicecensimento = (isset($all_meta_for_user['codicecensimento'])) ? $all_meta_for_user['codicecensimento'][0] : "";

        echo '<td class="column-columnname">'.implode(',',$ruolocensimento).'</td>';
        echo '<td class="column-columnname">'.$groupDisplay.'</td>';
        echo '<td class="column-columnname">'.$user_info->first_name.'</td>';
        echo '<td class="column-columnname num">'.$codicecensimento.'</td>';
        echo '<td class="column-columnname">'.implode(', ', $user_info->roles).'</td>';

        echo '<td class="column-columnname">';

        if ( current_user_can('abilita_eg') || current_user_can('manage_eg') )
        {
            echo '<form method="POST" action="'. admin_url( 'admin.php' ) .'">';
            echo '<input type="hidden" name="action" value="rtdcongelaeg" />';
            echo '<input type="hidden" name="selecteduser" value="'.$id.'" />';
            echo '<input type="submit" value="Blocca E/G" />';
            echo '</form>';
        }

        if ( current_user_can('delete_users') )
        {
            echo '<form method="POST" action="'. admin_url( 'admin.php' ) .'">';
            echo '<input type="hidden" name="action" value="rtddeleteeg" />';
            echo '<input type="hidden" name="selecteduser" value="'.$id.'" />';
            echo '<input type="submit" value="Delete E/G" />';
            echo '</form>';
        }

        echo '</td>';

    }
    ?>

        </tbody>
    </table>

    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#dreamers').DataTable();
        $('#join-requests').DataTable();
    });
    </script>

    <?php

}

// @see http://wordpress.stackexchange.com/questions/10500/how-do-i-best-handle-custom-plugin-page-actions

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
        $u->add_role( 'utente_eg' );

        $message  = "Sei stato autorizzato, \r\n\r\n";
        $message .= 'Accedi al pannello : '.wp_login_url() . " e inizia la sfida.\r\n";


        if ( !defined('RTD_DEVELOP') || !RTD_DEVELOP ) {
            wp_mail(get_option('admin_email'), 'Utenza '.$u->get('codicecensimento').' attivata', $message);
            wp_mail($u->user_email, 'Utenza attivata', $message);
        } else {
            _log('skip invio mail');
        }

    } else {
        _log('invalid request rtdautorizzaeg_admin_action, missing selecteduser or invalid permission '.var_export($can, true));
    }

    wp_redirect( $_SERVER['HTTP_REFERER'] );
    exit();

}

 
add_action( 'admin_action_rtdautorizzaeg', 'rtdautorizzaeg_admin_action' );

function rtdcongelaeg_admin_action()
{

    $can = current_user_can('abilita_eg') || current_user_can('manage_eg');

    if ( isset($_POST['selecteduser']) && $can ) {

        // Do your stuff here
        $user_id = $_POST['selecteduser'];
        
        $u = new WP_User( $user_id );

        // Remove role
        $u->remove_role( 'utente_eg' );

        // Add new roles
        $u->add_role( 'subscriber' );

    } else {
        _log('invalid request rtdcongelaeg_admin_action, missing selecteduser or invalid permission '.var_export($can, true));
    }

    wp_redirect( $_SERVER['HTTP_REFERER'] );
    exit();

}

add_action( 'admin_action_rtdcongelaeg', 'rtdcongelaeg_admin_action' );

function rtddeleteeg_admin_action()
{

    if ( isset($_POST['selecteduser']) && current_user_can('delete_users') ) {

        // Do your stuff here
        $user_id = $_POST['selecteduser'];
        
        $u = new WP_User( $user_id );

        $postMigrate = get_current_user_id();

        wp_delete_user($user_id,$postMigrate); 

        _log('rimosso utente : '.$user_id.' migrato post sull\' utente : '.$postMigrate);

    } else {
        _log('invalid request rtddeleteeg_admin_action, missing selecteduser or invalid permission '.var_export($can, true));
    }

    wp_redirect( $_SERVER['HTTP_REFERER'] );
    exit();

}

add_action( 'admin_action_rtddeleteeg', 'rtddeleteeg_admin_action' );

function gestione_ruoli_menu() {

    // add_menu_page( page_title, menu_title, capability, menu_slug, function, icon_url, position );
    add_menu_page( 'Dreamers', 'Dreamers','abilita_eg', 'dreamers', 'gestione_ruoli_menu_page',plugins_url( '/dreamers-manager/images/icon-eg-16x16.png', 2 ) );
    

    //add_dashboard_page( $page_title, $menu_title, $capability, $menu_slug, $function);
    // add_dashboard_page('Dreames Dashboard', 'DreamesDashboard', 'manage_eg', 'dreams-manage-dashboard', '??dashboard_dream??');
}

// @see: https://nayeemmodi.wordpress.com/2011/06/20/creating-menus-and-submenus-in-wordpress/
add_action('admin_menu', 'gestione_ruoli_menu');

function add_datatable_scripts($hook){
    if(isset($_GET['page']) && $_GET['page'] == 'dreamers'){
        $wp_plugin_url = plugin_dir_url( __FILE__ );
        wp_enqueue_style( 'data-table-css', '//cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css');
        wp_enqueue_script( 'data-table-js', $wp_plugin_url.'js/jquery.dataTables.min.js', array('jquery'));
    }
    ?>
    <style>
    td {
        text-align: center;
    }
    </style>
    <?php
}

add_action( 'admin_enqueue_scripts', 'add_datatable_scripts' );

