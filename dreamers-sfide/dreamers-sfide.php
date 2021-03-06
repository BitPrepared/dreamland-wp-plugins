<?php
/**
 * Plugin Name: Return to Dreamland - Sfide
 * Plugin URI: http://github.com/BitPrepared/dreamland-wp-plugins
 * Description: Plugin per la gestione dei "Racconti Sfida"
 * Version: 0.2
 * Author: Bit Prepared
 * Author URI: http://github.com/BitPrepared 
 * License: GPLv3
 */

require_once('iscrizioni-utils.php');
require_once('dreamers-sfide-utils.php');
require_once("dreamers-sfide-widget.php");
require_once('dashboard-widgets.php');

define("USER_META_KEY_REGIONE" , 'region');
define("USER_META_KEY_REGIONE_DISPLAY" , 'regionDisplay');
define("SFIDA_META_KEY_REGIONE" , '_regione');

define("USER_META_KEY_ZONA" , 'zone');
define("USER_META_KEY_ZONA_DISPLAY" , 'zoneDisplay');
define("SFIDA_META_KEY_ZONA" , '_zona');

define("USER_META_KEY_GROUP_DISPLAY" , 'groupDisplay');
define("USER_META_KEY_GROUP" , 'group');


function alert_regional_data_missing(){
    $screen = get_current_screen();
    global $regioni;
    global $zone;

    //if($screen->base != 'post-new.php' /*|| $screen->post_type != 'sfida_event' */ ){
    //    return;
    // }

    $msg = "";
    if(! file_exists(plugin_dir_path(__FILE__) . 'regioni_e_zone.php') ){
        $msg = "Il file <i>regioni_e_zone.php</i> è mancante. ";
	    $msg = $msg . "Puoi crearlo copiando il file <i>regioni_e_zone_sample.php</i>.";
    } else {
	   require_once('regioni_e_zone.php');
       require_once('regioni_zone_utils.php');
	   if(!isset($regioni) || !isset($zone)){
	       $msg = "Il file <i>regioni_e_zone.php</i> contiente informazioni errate.";
	   }
    }
    if($msg != "" && $screen->post_type == "sfida_event"){
	echo '<div class="error">';
	echo $msg;
	echo '</div>';
    }
}

add_action( 'admin_notices', 'alert_regional_data_missing' );

//SETUP
function rtd_sfide_install(){

    global $regioni;

    /**
     * Detect plugin. For use in Admin area only.
     */
    if ( !is_plugin_active( 'dreamers-game/dreamers-game.php' ) ) {
      //plugin is activated
      wp_die('<p>The <strong>Dreamers Sfide</strong> plugin requires plugin Dreamers-game','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
    } 

    /**
     * Detect plugin. For use in Admin area only.
     */
    if ( !is_plugin_active( 'dreamers-manager/dreamers-manager.php' ) ) {
      //plugin is activated
      wp_die('<p>The <strong>Dreamers Sfide</strong> plugin requires plugin Dreamers-manager','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
    } 

    $role = get_role('referente_regionale');
    $role->add_cap('view_other_sfide_review');
    $role->add_cap('view_sfide_review');
    //$role->add_cap('insert_sfide');
    //$role->add_cap('manage_sfide');
    $role->add_cap('promuovi_sfide_review');
    
    $role = get_role('utente_eg');
    $role->add_cap('view_sfide_review');
    $role->remove_cap('delete_sfide_review');
    $role->add_cap('insert_sfide_review');
    $role->add_cap('upload_files');
    
    $role = get_role('capo_reparto');
    $role->add_cap('view_sfide_review');
    $role->add_cap('insert_sfide_review');
    // $role->remove_cap('delete_sfide_review');
    $role->add_cap('conferma_sfide_review');
    
    $role = get_role('editor');
    $role->add_cap('insert_sfide');
    $role->add_cap('manage_sfide');
    $role->add_cap('promuovi_sfide_review');
    $role->add_cap('view_sfide_review');
    $role->add_cap('view_other_sfide_review');
    $role->add_cap('conferma_sfide_review');
    $role->add_cap('edit_iscrizioni');

    $role = get_role('administrator');
    $role->add_cap('insert_sfide');
    $role->add_cap('manage_sfide');
    $role->add_cap('promuovi_sfide_review');
    $role->add_cap('insert_sfide_review');
    $role->add_cap('view_sfide_review');
    $role->add_cap('view_other_sfide_review');
    $role->add_cap('conferma_sfide_review');
    $role->add_cap('edit_iscrizioni');

}

function rtd_sfide_uninstall(){
//    se venisse eseguito pulirebbe anche i post creati, e questo potrebbe essere un problema
//    $taxonomy = 'tipologiesfide';
//    $terms = get_terms($taxonomy);
//    foreach ($terms as $term) {
//        wp_delete_term( $term->term_id, $taxonomy );
//    }

    $role = get_role('referente_regionale');
    $role->remove_cap('view_other_sfide_review');
    $role->remove_cap('view_sfide_review');
    $role->remove_cap('insert_sfide');
    $role->remove_cap('manage_sfide');
    $role->remove_cap('promuovi_sfide_review');

    $role = get_role('utente_eg');
    $role->remove_cap('view_sfide_review');
    $role->remove_cap('insert_sfide_review');

    $role = get_role('capo_reparto');
    $role->remove_cap('view_sfide_review');
    $role->remove_cap('insert_sfide_review');
    $role->remove_cap('conferma_sfide_review');

    $role = get_role('editor');
    $role->remove_cap('insert_sfide');
    $role->remove_cap('promuovi_sfide_review');
    $role->remove_cap('view_sfide_review');
    $role->remove_cap('view_other_sfide_review');
    $role->remove_cap('conferma_sfide_review');

    $role = get_role('administrator');
    $role->remove_cap('insert_sfide');
    $role->remove_cap('promuovi_sfide_review');
    $role->remove_cap('insert_sfide_review');
    $role->remove_cap('view_sfide_review');
    $role->remove_cap('view_other_sfide_review');
    $role->remove_cap('conferma_sfide_review');

    // When a role is removed, the users who have this role lose all rights on the site.
    // remove_role('nome')

}

register_activation_hook(__FILE__,'rtd_sfide_install');

register_deactivation_hook(__FILE__,'rtd_sfide_uninstall');

function sfide_init(){
    register_cpt_sfida_review();
    register_cpt_sfida_event();
    tipologiesfide_taxonomy();
}

//HOOK INIT
add_action('init','sfide_init');

function register_cpt_sfida_review() {
 
    $labels = array(
        'name' => _x( 'Racconti Sfide', 'sfida_review' ),
        'singular_name' => _x( 'Racconto Sfida', 'sfida_review' ),
        'add_new' => _x( 'Aggiungi Racconto', 'sfida_review' ),
        'add_new_item' => _x( 'Aggiungi Racconto di una Sfida', 'sfida_review' ),
        'edit_item' => _x( 'Modifica Racconti', 'sfida_review' ),
        'new_item' => _x( 'New Racconto Sfida', 'sfida_review' ),
        'view_item' => _x( 'Visualizza Racconti', 'sfida_review' ),
        'search_items' => _x( 'Ricerca Racconto di Sfida', 'sfida_review' ),
        'not_found' => _x( 'Nessun racconto di sfida trovato', 'sfida_review' ),
        'not_found_in_trash' => _x( 'Nessun racconto di sfida nel cestino', 'sfida_review' ),
        'parent_item_colon' => _x( 'Parent Racconto di Sfida:', 'sfida_review' ),
        'menu_name' => _x( 'Racconti Sfide', 'sfida_review' ),
    );
 
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'description' => 'Sfide filtrabili per tipologia',
        'supports' => array( 'title', 'editor', 'thumbnail', 'revisions' ),
        'taxonomies' => array( 'tipologiesfide', 'post_tag' ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-format-audio',
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capabilities' => array(
            'edit_post'          => 'insert_sfide_review',
            'read_post'          => 'view_sfide_review',
            'delete_post'        => 'delete_sfide_review',
            'edit_posts'         => 'insert_sfide_review',
            'edit_others_posts'  => 'update_core',
            'publish_posts'      => 'conferma_sfide_review',
            'read_private_posts' => 'view_other_sfide_review'
        ),
        'capability_type' => 'post'
    );

    // @see: http://wordpress.stackexchange.com/questions/54959/restrict-custom-post-type-to-only-site-administrator-role
    // By default, seven keys are accepted as part of the capabilities array:

    // edit_post, read_post, and delete_post are meta capabilities, which are then generally mapped to corresponding primitive capabilities depending on the context, which would be the post being edited/read/deleted and the user or role being checked. Thus these capabilities would generally not be granted directly to users or roles.
    // edit_posts - Controls whether objects of this post type can be edited.
    // edit_others_posts - Controls whether objects of this type owned by other users can be edited. If the post type does not support an author, then this will behave like edit_posts.
    // publish_posts - Controls publishing objects of this post type.
    // read_private_posts - Controls whether private objects can be read.
 
    register_post_type( 'sfida_review', $args );
}

function register_cpt_sfida_event() {
 
    $labels = array(
        'name' => _x( 'Sfide', 'sfida_event' ),
        'singular_name' => _x( 'Sfida', 'sfida_event' ),
        'add_new' => _x( 'Aggiungi Sfida', 'sfida_event' ),
        'add_new_item' => _x( 'Aggiungi Sfida', 'sfida_event' ),
        'edit_item' => _x( 'Modifica Sfida', 'sfida_event' ),
        'new_item' => _x( 'New Sfida', 'sfida_event' ),
        'view_item' => _x( 'Visualizza Sfide', 'sfida_event' ),
        'search_items' => _x( 'Ricerca Sfida', 'sfida_event' ),
        'not_found' => _x( 'Nessuna sfida trovato', 'sfida_event' ),
        'not_found_in_trash' => _x( 'Nessuna sfida nel cestino', 'sfida_event' ),
        'parent_item_colon' => _x( 'Parent Sfida:', 'sfida_event' ),
        'menu_name' => _x( 'Sfide', 'sfida_event' ),
    );
 
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'description' => 'Sfide filtrabili per tipologia',
        'supports' => array( 'title', 'editor', 'thumbnail', 'revisions' ),
        'taxonomies' => array( 'tipologiesfide' ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-format-audio',
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        // 'rewrite' => false,
        'rewrite' => array( 'slug' => 'sfida_event', 'with_front' => false ),
        'capabilities' => array(
            'edit_post'          => 'insert_sfide',
            'read_post'          => 'read',
            'delete_post'        => 'insert_sfide',
            'edit_posts'         => 'manage_sfide',
            'edit_others_posts'  => 'manage_sfide',
            'publish_posts'      => 'insert_sfide',
            'read_private_posts' => 'manage_sfide'
        ),
        'capability_type' => 'post'
    );
 
    register_post_type( 'sfida_event', $args );
}

//TAXONOMIES
function tipologiesfide_taxonomy() {

    if (!taxonomy_exists('tipologiesfide')) {

        register_taxonomy(
            'tipologiesfide',
            array( 'sfida_review','sfida_event' ),
            array(
                'hierarchical' => true,
                'label' => 'Tipi Sfida',
                'query_var' => true,
                'rewrite' => array(
                    'slug' => 'typesfida',
                    'with_front' => false
                ),
                'public' => true,
                'capabilities' => array(
                    'assign_terms' => 'read'
                )
            )
        );

        $res = wp_insert_term(
          'Grande Sfida', // the term
          'tipologiesfide', // the taxonomy
          array(
            'description'=> 'Grande Sfida',
            'slug' => 'grande-sfida',
            'parent'=> null
          )
        );

        //array('term_id'=>12,'term_taxonomy_id'=>34)

    //    LA CATEGORIA DELLA GRANDE SFIDA VA SCELTA QUANDO CI SI ISCRIVE
    //    if ( is_array($res) ) {
    //
    //        wp_insert_term(
    //          'Avventura', // the term
    //          'tipologiesfide', // the taxonomy
    //          array(
    //            'description'=> 'Avventura',
    //            'slug' => 'grande-sfida-avventura',
    //            'parent'=> $res['term_id']
    //          )
    //        );
    //
    //        wp_insert_term(
    //          'Grande Impresa', // the term
    //          'tipologiesfide', // the taxonomy
    //          array(
    //            'description'=> 'Grande Impresa',
    //            'slug' => 'grande-sfida-impresa',
    //            'parent'=> $res['term_id']
    //          )
    //        );
    //
    //        wp_insert_term(
    //          'Originalita', // the term
    //          'tipologiesfide', // the taxonomy
    //          array(
    //            'description'=> 'Originalità',
    //            'slug' => 'grande-sfida-originalita',
    //            'parent'=> $res['term_id']
    //          )
    //        );
    //
    //        wp_insert_term(
    //          'Traccia nel Mondo', // the term
    //          'tipologiesfide', // the taxonomy
    //          array(
    //            'description'=> 'Traccia nel Mondo',
    //            'slug' => 'grande-sfida-traccia-nel-mondo',
    //            'parent'=> $res['term_id']
    //          )
    //        );
    //
    //    }

        $res_speciale = wp_insert_term(
          'Sfida Speciale', // the term
          'tipologiesfide', // the taxonomy
          array(
            'description'=> 'Sfida Speciale',
            'slug' => 'sfida-speciale',
            'parent'=> null
          )
        );

        //array('term_id'=>12,'term_taxonomy_id'=>34)

        if ( is_array($res_speciale) ) {

            wp_insert_term(
              'Avventura', // the term
              'tipologiesfide', // the taxonomy
              array(
                'description'=> 'Avventura',
                'slug' => 'sfida-speciale-avventura',
                'parent'=> $res_speciale['term_id']
              )
            );

            wp_insert_term(
              'Altro', // the term
              'tipologiesfide', // the taxonomy
              array(
                'description'=> 'Altro',
                'slug' => 'sfida-speciale-altro',
                'parent'=> $res_speciale['term_id']
              )
            );

            wp_insert_term(
              'Originalita', // the term
              'tipologiesfide', // the taxonomy
              array(
                'description'=> 'Originalità',
                'slug' => 'sfida-speciale-originalita',
                'parent'=> $res_speciale['term_id']
              )
            );

            wp_insert_term(
              'Traccia nel Mondo', // the term
              'tipologiesfide', // the taxonomy
              array(
                'description'=> 'Traccia nel Mondo',
                'slug' => 'sfida-speciale-traccia-nel-mondo',
                'parent'=> $res_speciale['term_id']
              )
            );

        }

    }
    
}

// Display any errors
function sfide_admin_notice_handler() {
    $errors = get_option('sfide_admin_errors');
    if($errors) {
        echo '<div class="error"><p>' . $errors . '</p></div>';
    }
}
add_action( 'admin_notices', 'sfide_admin_notice_handler' );

// Clear any errors
function sfide_clear_errors() {

    update_option('sfide_admin_errors', false);

}
add_action( 'admin_footer', 'sfide_clear_errors' );

function default_comments_off( $data ) {
    if( $data['post_type'] == 'sfida_review' || $data['post_type'] == 'sfida_event' ) {
        $data['comment_status'] = 0;
        $data['ping_status'] = 0;
    }
    return $data;
}
add_filter( 'wp_insert_post_data', 'default_comments_off' );

function sfide_custom_meta() {
    if ( current_user_can('insert_sfide') )
    {
        // add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
        // context -> (string) (optional) The part of the page where the edit screen section should be shown ('normal', 'advanced', or 'side'). (Note that 'side' doesn't exist before 2.7)
        add_meta_box( 'sfide_event_start_meta', 'Inizio Evento', 'sfide_event_date', 'sfida_event', 'normal', 'default', array( 'id' => '_start') );
        add_meta_box( 'sfide_event_end_meta', 'Fine Evento', 'sfide_event_date', 'sfida_event', 'normal', 'default', array('id'=>'_end') );
	    add_meta_box( 'sfide_event_limit_meta', 'Rivolta a', 'sfide_event_limit', 'sfida_event', 'normal', 'default', array());
    }
    add_meta_box( 'racconti_sfide_meta', 'Avanzate Sfida', 'racconti_sfide_meta_callback', 'sfida_review' );
}
add_action( 'add_meta_boxes', 'sfide_custom_meta' );

function sfide_event_date($post, $args) {
    $metabox_id = $args['args']['id'];
    global $post, $wp_locale;

    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'stida_event_nonce' );

    $time_adj = current_time( 'timestamp' );
    $month = get_post_meta( $post->ID, $metabox_id . '_month', true );

    if ( empty( $month ) ) {
        $month = gmdate( 'm', $time_adj );
    }

    $day = get_post_meta( $post->ID, $metabox_id . '_day', true );

    if ( empty( $day ) ) {
        $day = gmdate( 'd', $time_adj );
    }

    $year = get_post_meta( $post->ID, $metabox_id . '_year', true );

    if ( empty( $year ) ) {
        $year = gmdate( 'Y', $time_adj );
    }
    
    $hour = get_post_meta($post->ID, $metabox_id . '_hour', true);
 
    if ( empty($hour) ) {
        $hour = gmdate( 'H', $time_adj );
    }
 
    $min = get_post_meta($post->ID, $metabox_id . '_minute', true);
 
    if ( empty($min) ) {
        $min = '00';
    }

    $month_s = '<select name="' . $metabox_id . '_month">';
    for ( $i = 1; $i < 13; $i = $i +1 ) {
        $month_s .= "\t\t\t" . '<option value="' . zeroise( $i, 2 ) . '"';
        if ( $i == $month )
            $month_s .= ' selected="selected"';
        $month_s .= '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
    }
    $month_s .= '</select>';

    echo $month_s;
    echo '<input type="text" name="' . $metabox_id . '_day" value="' . $day  . '" size="2" maxlength="2" />';
    echo '<input type="text" name="' . $metabox_id . '_year" value="' . $year . '" size="4" maxlength="4" /> @ ';
    echo '<input type="text" name="' . $metabox_id . '_hour" value="' . $hour . '" size="2" maxlength="2"/>:';
    echo '<input type="text" name="' . $metabox_id . '_minute" value="' . $min . '" size="2" maxlength="2" />';
 
}

function sfide_event_limit($post, $args) { 
    global $regioni;
    global $zone;

    $curr_reg = get_post_meta($post->ID, SFIDA_META_KEY_REGIONE, 1);
    $curr_zon = get_post_meta($post->ID, SFIDA_META_KEY_ZONA, 1);

    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'stida_event_nonce' );

    // create menu input per regione 
    echo '<select id="select_regione" name="_regione" onchange="update_zone(event)">'."\n";

    usort($regioni, "ordina_regioni_per_nome");
    foreach($regioni as $r){
        echo '<option value="'.$r[2];
        if($curr_reg == $r[2]){
            echo '" selected="selected';
        }
        echo '" id="'. get_codice_regione($r) .'">'.$r[1]."</option>\n";
    }
    echo "</select>\n";

    echo '<select id="select_zona" name="_zona"  onchange="update_regione(event)">';
    usort($zone, "ordina_zone_per_nome");
    foreach($zone as $z){
        echo '<option value="'.get_nome_zona($z);
        if ($curr_zon == get_nome_zona($z)){
            echo '" selected="selected';
        }
        echo '" class="'. get_codice_regione_zona($z) .'">' . get_nome_zona($z) .'</option>' . "\n";
    }
    echo '</select>';
    ?>
    <script language="javascript">
    function update_regione(e){
        var sel = e.target;
        var selected = sel.options[sel.selectedIndex];
        if(selected.className == 'A'){ return; } 
        document
            .querySelector("#select_regione > option#" + selected.className)
            .setAttribute("selected", "selected");
    }

    function update_zone(e){
        var sel = e.target;
        var selected = sel.options[sel.selectedIndex];
        var all = document.querySelectorAll("#select_zona > option");
        for(var i = 0; i < all.length; i++){
            if(all[i].className !== selected.id && all[i].className != 'A' && selected.id != 'A'){
                all[i].setAttribute('hidden', true);
            } else {
                all[i].removeAttribute('hidden');
            }
            if(all[i].className == 'A' && selected.id == 'A'){
                all[i].setAttribute("selected", "selected");
            }
        }
    }




    </script>
    <?php

}

// @see http://codex.wordpress.org/Post_Status_Transitions  
function change_new_sfida_review(){
    // I NUOVI POST SONO CREATI AD ARTE ALLA CHIUSURA DELLA SFIDA PER POI ESSERE EDITATI SOLTANTO
    wp_redirect( admin_url().'?errore=no_new_sfida' );
}
add_action('new_sfida_review','change_new_sfida_review');
add_action('auto-draft_sfida_review','change_new_sfida_review');

/**
 * Outputs the content of the meta box ($post e' sfida_review) --> http://themefoundation.com/wordpress-meta-boxes-guide/ x le tipologie
 */
function racconti_sfide_meta_callback( $post ) {
    wp_nonce_field( basename( __FILE__ ), 'racconti_sfida_nonce' );
    $racc_sf_stored_meta = get_post_meta( $post->ID );

    $sfida_corrente = get_post_meta($post->ID,'sfida_corrente',true);

    if ( !empty($sfida_corrente) ) {

    ?>

    <p>
        <span class="racconti-sfiderow-title">
            <div class="racconti-sfiderow-content">
                <label for="meta-visibilita-bacheca">
                    Codice sfida : <?php echo $sfida_corrente; ?>
                </label>
            </div>
        </span>

    </p>
    
    <?php
    } else {
        update_post_meta( $post->ID, 'sfida_corrente', $sfida_corrente);
    }
    if ( current_user_can('promuovi_sfide_review') ) {
    ?>

    <p>
        <span class="racconti-sfiderow-title">Visibilita su bacheca e/g</span>
        <div class="racconti-sfiderow-content">
            <label for="meta-visibilita-bacheca">
                <input type="checkbox" name="meta-visibilita-bacheca" id="meta-visibilita-bacheca" value="yes" <?php if ( isset ( $racc_sf_stored_meta['meta-visibilita-bacheca'] ) ) checked( $racc_sf_stored_meta['meta-visibilita-bacheca'][0], 'yes' ); ?> />
                Promuovi
            </label>
        </div>
    </p>

    <?php

    }
}

/**
 * Saves the custom meta input
 */
function racconti_sfide_meta_save( $post_id ) {

    // Checks save status
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST[ 'racconti_sfida_nonce' ] ) && wp_verify_nonce( $_POST[ 'racconti_sfida_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
 
    // Exits script depending on save status
    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
        return;
    }
 
    // Checks for input and saves
    if( isset( $_POST[ 'meta-visibilita-bacheca' ] ) ) {
        update_post_meta( $post_id, 'meta-visibilita-bacheca', 'yes' );
    } else {
        update_post_meta( $post_id, 'meta-visibilita-bacheca', '' );
    }
     
//    // Checks for input and saves if needed
//    if( isset( $_POST[ 'meta-radio' ] ) ) {
//        update_post_meta( $post_id, 'meta-radio', $_POST[ 'meta-radio' ] );
//    }
 
}
add_action( 'save_post_sfida_review', 'racconti_sfide_meta_save' );

function stida_event_save_meta( $post_id, $post ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    if ( !isset( $_POST['stida_event_nonce'] ) )
        return;

    if ( !wp_verify_nonce( $_POST['stida_event_nonce'], plugin_basename( __FILE__ ) ) )
        return;

    // Is the user allowed to edit the post or page?
    if ( !current_user_can( 'edit_post', $post->ID ) )
        return;

    // OK, we're authenticated: we need to find and save the data
    // We'll put it into an array to make it easier to loop though

    if ( count(get_icons_for_sfida($post)) == 0 ) {
        $events_meta['_validita'] = 'false';
        _log('Sfida '.$post->ID.' non valida');
        update_option('sfide_admin_errors', 'Sfida '.$post->ID.' non valida: non e\' stato scelta una categoria appropriata');
    } else {
        $events_meta['_validita'] = 'true';
    }

    $metabox_ids = array( '_start', '_end' );

    foreach ($metabox_ids as $key ) {
        
        $year = $_POST[$key . '_year'];
        $month = $_POST[$key . '_month'];
        $day = $_POST[$key . '_day'];
        $hour = $_POST[$key . '_hour'];
        $min = $_POST[$key . '_minute'];
        
        $year = ($year <= 0 ) ? date('Y') : $year;
        $month = ($month <= 0 ) ? date('n') : $month;
        $day = sprintf('%02d',$day);
        $day = ($day > 31 ) ? 31 : $day;
        $day = ($day <= 0 ) ? date('j') : $day;
        $hour = sprintf('%02d',$hour);
        $hour = ($hour > 23 ) ? 23 : $hour;
        $min = sprintf('%02d',$min);
        $min = ($min > 59 ) ? 59 : $min;
        
        $events_meta[$key . '_year'] = $year;
        $events_meta[$key . '_month'] = $month;
        $events_meta[$key . '_day'] = $day;
        $events_meta[$key . '_hour'] = $hour;
        $events_meta[$key . '_minute'] = $min;
        $events_meta[$key . '_eventtimestamp'] = $year . $month . $day . $hour . $min;
        
    }
    
    $events_meta[SFIDA_META_KEY_REGIONE] = $_POST[SFIDA_META_KEY_REGIONE];
    $events_meta[SFIDA_META_KEY_ZONA] = $_POST[SFIDA_META_KEY_ZONA];

    // Save Locations Meta
    // $events_meta['_event_location'] = $_POST['_event_location'];   

    // Add values of $events_meta as custom fields

    foreach ( $events_meta as $key => $value ) { // Cycle through the $events_meta array!
        if ( $post->post_type == 'revision' ) return; // Don't store custom data twice
        $value = implode( ',', (array)$value ); // If $value is an array, make it a CSV (unlikely)
        if ( get_post_meta( $post->ID, $key, false ) ) { // If the custom field already has a value
            update_post_meta( $post->ID, $key, $value );
        } else { // If the custom field doesn't have a value
            add_post_meta( $post->ID, $key, $value );
        }
        if ( !$value ) delete_post_meta( $post->ID, $key ); // Delete if blank
    }

}

add_action( 'save_post_sfida_event', 'stida_event_save_meta', 1, 2 );


//function save_post_sfida_event($post_id,$post) {
//
//    print_r($post);
//    exit;
//
//}
//
//add_action('save_post_sfida_event', 'save_post_sfida_event', 1, 2);

function add_new_sfida_event_columns($gallery_columns) {
    
    $new_columns['title'] = 'Evento';
    $new_columns['author'] = 'Autore';
    $new_columns['tags'] = 'Tags';
    $new_columns['start_time_event'] = 'Inizio Evento';
    $new_columns['end_time_event'] = 'Fine Evento';
    $new_columns['category_event'] = 'Categoria';
    $new_columns['region'] = 'Regione';
//    $new_columns['validita_event'] = 'Validita';
    // $new_columns['date'] = 'Published';
 
    return $new_columns;
    // return array_merge($gallery_columns,$new_columns);

}

// Add to admin_init function manage_edit-{custom_type}_columns
add_filter('manage_edit-sfida_event_columns', 'add_new_sfida_event_columns');


// Register the column as sortable
function sfida_event_column_register_sortable( $columns ) {
    $columns['start_time_event'] = 'Inizio Evento';
    $columns['end_time_event'] = 'Fine Evento';
    $columns['regione'] = 'Regione';

    return $columns;
}
// manage_edit-{custom type}_sortable_columns
add_filter( 'manage_edit-sfida_event_sortable_columns', 'sfida_event_column_register_sortable' );

function time_event_column_orderby( $vars ) {
    if ( isset( $vars['orderby'] ) && 'InizioEvento' == $vars['orderby'] ) {
        $vars = array_merge( $vars, array(
            'meta_key' => '_start_eventtimestamp',
            'orderby' => 'meta_value'
        ) );
    }
 
    return $vars;
}

 
function manage_gallery_columns($column_name, $id) {
    global $wpdb;
    switch ($column_name) {
        case 'start_time_event':
            $year = get_post_meta($id,'_start_year',true);
            $month = get_post_meta($id,'_start_month',true);
            $day = get_post_meta($id,'_start_day',true);
            echo $day.'-'.$month.'-'.$year;
            break;
        case 'end_time_event':
            $year = get_post_meta($id,'_end_year',true);
            $month = get_post_meta($id,'_end_month',true);
            $day = get_post_meta($id,'_end_day',true);
            echo $day.'-'.$month.'-'.$year;
            break;
        case 'category_event': 
            $myarray = get_the_terms( $id, 'tipologiesfide' );
            if(!$myarray){
                break;
            }
            $elenco = '';
            foreach ($myarray as $key => $value) {
                if ( $myarray[$key]->parent == false ) {
                    $parent = $myarray[$key]->name;
                } else {
                    $elenco .= ','.$myarray[$key]->name;
                }
            }
            echo $parent.' ['.substr($elenco, 1).']';
            break;
        case 'region':
            echo get_post_meta($id,'_regione',true);
            break;
        case 'validita_event':

            if ( check_validita_sfida( $id ) ) {
                echo 'valido';
            } else {
                echo 'non valida';
            }
            break;

        case 'regione':
            $reg = get_post_meta($id,'_regione',true);
            echo $reg;//  get_nome_regione_by_code($r);
            break;

    default:
        break;
    } // end switch
}   

// Add to admin_init function manage_{custom_type}_posts_custom_column
add_action('manage_sfida_event_posts_custom_column', 'manage_gallery_columns', 10, 2);

// Registra e carica il widget del frontend per le sfide
function rtd_sfide_load_widget() {
    register_widget( 'rtd_sfide_widget' );
}

add_action( 'widgets_init', 'rtd_sfide_load_widget' );

/* 
    FORCE DASHBOARD TO BE ONE COLUMN
*/

function so_screen_layout_columns( $columns ) {
    $columns['dashboard'] = 1;
    return $columns;
}
add_filter( 'screen_layout_columns', 'so_screen_layout_columns' );

function so_screen_layout_dashboard() {
    return 1;
}
add_filter( 'get_user_option_screen_layout_dashboard', 'so_screen_layout_dashboard' );

function remove_all_metas(){
    global $current_user;

    if($current_user->roles[0] == 'utente_eg'){
        remove_meta_box('slugdiv', 'sfida_review', 'normal');
        remove_meta_box('tipologiesfidediv', 'sfida_review', 'side');
        remove_meta_box('racconti_sfide_meta', 'sfida_review', 'normal');
        add_meta_box('istruzioni_racconto_eg', 'Istruzioni: leggere attentamente', 'istruzioni_racconto_eg_callback', 'sfida_review', 'above', 'high');
    }

    if($current_user->roles[0] == 'capo_reparto'){
        remove_meta_box('slugdiv', 'sfida_review', 'normal');
        remove_meta_box('tipologiesfidediv', 'sfida_review', 'side');
        add_meta_box('istruzioni_racconto_cr', 'Istruzioni: leggere attentamente', 'istruzioni_racconto_cr_callback', 'sfida_review', 'above', 'high');
    }
}

add_action('add_meta_boxes', 'remove_all_metas');

function istruzioni_racconto_eg_callback(){ ?>
    <p>In questa pagina dovete inserire il resoconto della sfida: scrivete quello che avete fatto,
        come è andata, ecc.. (una sorta di breve verifica), oltre a ciò dovete allegare foto, video,
        documenti, ecc... ovvero tutto quello che vi è stato richiesto nella sfida.</p>

    <p>Per caricare i files delle foto (NE BASTANO 5 O 6), del video ecc...  premete sul bottone <b>Aggiungi Media</b>, attenzione che
        la dimensione dei files che andrete a caricare sul sito è limitata, quindi se i file che avete a disposizione
         sono troppo grandi dovrete ridurli (immagini e video più piccoli o corti), e anche questo per qualcuno sarà un nuova sfida!
        Se proprio non riuscite a ridurli, o se la sfida lo richiedesse, potete caricarli su un'altra
        piattaforma (ma attenti ai diritti d'autore, al rispetto della privacy ecc..) potete
        aggiungerli come link, scegliendo tra le opzioni che appariranno premendo il bottone <b>Aggiungi Media</b>.</p>

    <p>Una volta che avrete completato il resoconto premete il bottone <b>Racconto Completato</b> dopo di
        ché verrà mandata una mail al vostro Capo Reparto per approvarne la condivisione.
        Nota: una volta premuto il bottone non sarete più in grado di modificare il resoconto, potrà farlo il Capo Reparto.</p>
    <?php }

function istruzioni_racconto_cr_callback() { ?>
    Istruzioni capo rep
<?php }

function foo_move_deck() {
    # Get the globals:
    global $post, $wp_meta_boxes;

    # Output the "advanced" meta boxes:
    do_meta_boxes( get_current_screen(), 'above', $post );

    # Remove the initial "advanced" meta boxes:
    unset($wp_meta_boxes['post']['above']);
}

add_action('edit_form_after_title', 'foo_move_deck');

function sfida_review_admin_css_js() {
    global $current_user;

    if($current_user->roles[0] == 'utente_eg'){
        global $post_type;
        if($post_type == 'sfida_review') {
            echo '<style type="text/css">'.
                '#titlediv,.add-new-h2,#delete-action,#edit-slug-box,#view-post-btn {display: none;}'.
                '</style>';
            ?><script>jQuery(document).ready(function(){
                jQuery("#publish").click(
                    function(e){
                        var event = e || window.event;
                        var res = confirm("Vuoi pubblicare il racconto? " +
                        "Attenzione : nel resoconto devi vedere le immagini o il video o il " +
                        "collegamento di quello che devi allegare (se non è così ritorna" +
                        " nella \"libreria dei media\" e seleziona quello che vuoi allegare).\n" +
                        "Dopo averlo pubblicato non sarà più possibile modificarlo! " +
                        "Il resoconto verrà inviato al tuo caporeparto per essere approvato.");
                        if(!res){
                            event.stopPropagation();
                            event.cancelBubble = true;
                            return false;
                        }
                 });
                jQuery("#publish").attr("value", "Racconto completato");
                jQuery("#save-action").prepend(jQuery("input#save.button"));
                jQuery("ul.subsubsub,p.search-box").hide();
            });</script><?php
        }
    }

}
add_action('admin_head', 'sfida_review_admin_css_js');

// Impedisce a chi non è loggato di vedere i resoconti sfida
function tp_stop_guestes( $content ) {

    if ( is_single() && get_post_type() == 'sfida_review' ) {
        if ( ! is_user_logged_in() ) {
            $content = '<a href="'.  wp_login_url( get_permalink() ). '" title="Accedi"><h2>Accedi per poter vedere questo racconto.</h2></a>';
        }
    }

    return $content;
}

add_filter( 'the_content', 'tp_stop_guestes' );


/* GESTIONE TRANSIZIONE STATUS DEI RACCONTI SFIDA */

function rs_draft_to_pending( $post ){
    global $current_user;
    if( $post->post_type != 'sfida_review' ) return;

    if(in_array('utente_eg', $current_user->roles)){
        _log("Utente eg " . $current_user->ID ." ha salvato il racconto " . $post->ID);
        
        // trova l'utente capo reparto
        $umeta = get_user_meta($current_user->ID);
        $qargs = array(
            'role' => 'capo_reparto',
            'meta_key' => 'group',
            'meta_value' => $umeta['group'],
            'fields' => 'all'
        );

        $query = new WP_User_Query($qargs);
        $all_capireparto = $query->get_results();
        $primo_caporep = $all_capireparto[0];

        $sfida_id = get_post_meta($post->ID, 'sfida', true);
        $sfida = get_post($sfida_id); 

        // manda una mail al capo reparto
        
        $mail_body_format = "La sq. %s ha completato il racconto della sfida \"%s\" a cui si era iscritta!\n\n".
            "Per vedere e confermare il racconto, accedi con il tuo utente al sito,".
            "vai alla tua bacheca e nel widget delle sfide che segui potrai trovare il racconto.".
            "Nella pagina del racconto troverai tutte le istruzioni per approvarlo o sistemarlo.\n".
            "Se non ricordi il testo della sfida clicca qui: " . get_permalink($sfida->ID) . " .\n\n".
             "Una volta completato il racconto, la squadriglia non può più modificarlo ".
             "e tu devi approvarlo prima che sia condiviso e visibile (ma solo agli utenti iscritti al sito).\n\n".
            "Attenzione: prima di approvare il recoconto, verifica che gli EG abbiano valorizzato quanto fatto ".
            "(e che quindi la descrizione non sia troppo corta), e che siano incluse le foto o quello".
            " che è richiesto agli EG per la sfida (canzone, documento, videoclip, ecc...),".
            " Non approvarlo e rimanda il raccondo agli EG per sitemarlo\n\n".
             "Lo Staff RTD\n";

        // $preview_url = http://www.beta.returntodreamland.it/blog/?post_type=sfida_review&p=16&preview=true
        /* $preview_url = add_query_arg(array(
                'preview' => 'true',
                'post_type' => 'sfida_review',
                'p' => $post->ID
            ), get_site_url()); */

        // Nel caso in cui l'utente non sia loggato riceve un errore 404

        foreach($all_capireparto as $caporep) {
            wp_mail($caporep->user_email,
                $post->post_title,
                sprintf($mail_body_format, $umeta['squadriglia'][0], $sfida->post_title ));
            _log("Email inviata a " . $caporep->user_email . " racconto " . $post->ID);
        }
        
        wp_update_post(array('ID' => $post->ID, 'post_author' => $primo_caporep->ID));

        _log("Utente". $current_user->ID ." ha completato il racconto " . $post->ID);
        wp_redirect( add_query_arg( 'racconto_completato', '1' ,get_admin_url()));
        exit();

    }
}

add_action('draft_to_pending', 'rs_draft_to_pending');

/*
add_filter('redirect_post_location', 'redirect_to_post_on_publish_or_save');
function redirect_to_post_on_publish_or_save($location)
{
    if (isset($_POST['save']) || isset($_POST['publish'])) {
        wp_redirect( add_query_arg( 'racconto_completato', '1' ,get_admin_url()));
    }
}
*/
add_action('admin_notices', 'review_created_admin_notice');

function review_created_admin_notice()
{
    global $pagenow;

    // Only show this message on the admin dashboard and if asked for
    if ('index.php' === $pagenow && ! empty($_GET['racconto_completato']))
    {
        echo '<div class="updated"><p>Hai completato il racconto della sfida! Aspetta ora che '.
            'il tuo caporeparto lo approvi perchè sia pubblicato nella pagina dei racconti!</p></div>';
    }
}


// Prevent users from seeing others media and posts in edit
function mypo_parse_query_useronly( $wp_query ) {
    global $current_user, $pagenow;

    if( !is_a( $current_user, 'WP_User') )
        return;

    if( (   'edit.php' != $pagenow ) &&
        (   'upload.php' != $pagenow ) &&
        ( ( 'admin-ajax.php' != $pagenow ) || ( $_REQUEST['action'] != 'query-attachments' ) ) ){
        return;
    }

    if ( !current_user_can( 'delete_pages' ) ) {
                $wp_query->set( 'author', $current_user->ID );
            }
}

add_filter('parse_query', 'mypo_parse_query_useronly' );

/* FINE GESTIONE TRANSIZIONE STATUS DEI RACCONTI SFIDA  */

/* GESTIONE RACCONTO SFIDA LATO CAPO REPARTO */

function gestisci_sfida_review( $content ){
    global $post;
    global $current_user;

    if($post->post_type == 'sfida_review' && $post->post_status == 'pending' && // Se si tratta di un racconto sfida in attesa
        can_approve_review($post, $current_user) // utente abilitato all'approvazione
    ){

        $scroll_down = "<div class=\"bs-callout bs-callout-primary\"><p>".
            "<h4>Verifica il Racconto</h4>".
            " Leggi il racconto fatto dalla Squadiglia, valuta se è veriterio".
            " e se può esere condiviso e diventare visibile a gli altri utenti di dreamland, in fondo alla pagina".
            " puoi eventualmente aggiungere i tuo commenti (non saranno visibii da gli eg, e non verranno condivisi),".
            " nel caso di una missione assegnata dallo staff è necessario scrivere nella sezione dei commenti una".
            " breve relazione che ci spieghi il motivo per cui avete assegnato quella specifica missione a".
            " quegli eg e la vostra verifica su come è andata.</p></div> ";
        $cbrns = " ";

        $comm_needed = ('true' == get_post_meta($post->ID, 'is_missione', true));
        $cbrns .=  "<div style=\"padding:10px;width:600px;\">";
        $cbrns .=  '<form id="manda-commento" action="" method="post">';
        $cbrns .= '<div class="form-group"><label for="commento_capo_rep">';
        $cbrns .= $comm_needed ? 'Commento/Relazione: (Necessario)' : 'Commento/Relazione:';
        $cbrns .= '</label> <textarea class="form-control" style="width:100%" name="commento_capo_rep" id="commento_capo_rep"></textarea>';
        $cbrns .= '<input type="hidden" id="verifica" name="verifica">';
        $cbrns .= "</div>";
        $cbrns .= "</form>";
        $cbrns .= "<button style=\"margin:10px\" id=\"approva\" class=\"btn btn-success\">Approva</button>";
        $cbrns .= "<button style=\"margin:10px\" id=\"respingi\" class=\"btn btn-danger\">Da sistemare</button>";
        $cbrns .= "</div> ";

        $conferma_approva = "Vuoi approvare il resoconto della squadriglia?".
            "Attenzione: prima di approvare il resoconto, verifica che gli EG abbiano".
            "valorizzato quanto fatto (e che quindi la descrizione non sia troppo corta) ".
            "e che siano incluse le foto o quello che è richiesto agli EG per la sfida (canzone,".
            "documento, videoclip, ecc...). Se non è così, non approvarlo e rimanda il raccondo agli EG per sitemarlo.";

        $conferma_respingi = "Vuoi rimandare il resoconto della squadriglia?".
            " Una volta premuto il bottone l'EG potrà modificarlo nuovamente e poi dovrai nuovamente approvarlo.";

        ?>
        <script>
            jQuery(document).ready(function() {
                jQuery('#manda-commento').attr('action', window.location);
                jQuery('#approva').on('click', function () {
                    var res = confirm(<?= json_encode($conferma_approva) ?>);
                    if(! res ) return;
                    <?php if($comm_needed): ?>
                    if(jQuery('#commento_capo_rep').val() == ""){
                        alert("Per le sfide di tipo missione è necessario che tu compili la relazione!");
                        return;
                    }
                    <?php endif; ?>
                    <?php
                        get_currentuserinfo();
                        $sfida_id = get_post_meta($post->ID,'sfida',true);

                        //$codicecens = $current_user->user_login;
                        $user_orig_id = get_post_meta($post->ID,'utente_originale',true);
                        $user_orig = get_userdata($user_orig_id);
                        $codicecens = $user_orig->user_login;
                    ?>
                    jQuery.ajax({
                        url: '<?php echo get_site_url(); ?>/../portal/api/sfide/conferma/<?= $sfida_id ?>/<?= $codicecens ?>',
                        type: 'PUT',
                        success: function(result) {
                            jQuery('#verifica').val('Approva');
                            jQuery('#manda-commento').submit();
                        },
                        error: function(xhr, status) {
                            alert("Il sistema ha riscontrato un errore. Per favore contatta lo Staff."+
                            "Il portale ha risposto: " + status);
                        }
                    });
                });
                jQuery('#respingi').on('click', function () {
                    var res = confirm("<?= $conferma_respingi ?>");
                    if(! res ) return;
                    jQuery('#verifica').val('Respingi');
                    jQuery('#manda-commento').submit();
                });
            });
        </script>
        <?php
        return $scroll_down . $content . $cbrns;
    }

    return $content;

}

add_action('the_content', 'gestisci_sfida_review');


function get_change_sfida_review(){

    global $current_user;

    $post_id = get_the_ID();
    $post = get_post($post_id);
    if(!is_user_logged_in()) { return; }

    if(! is_single() || ! $post->post_type == "sfida_review" || !$post->post_status == "pending" ||
        ! can_approve_review($post, $current_user->ID) || !isset($_POST['verifica'])){
        return;
    }

    $verifica = filter_input(INPUT_POST, 'verifica', FILTER_SANITIZE_STRING);

    if( $verifica == 'Approva' ){
        $serve_comm = ( 'true' == get_post_meta($post->ID, 'is_missione', true) ) ;
        $commento_input = filter_input(INPUT_POST, 'commento_capo_rep', FILTER_SANITIZE_STRING);
        if($serve_comm && ($commento_input == null || $commento_input == "")){
            wp_die("Devi inserire la verifica della staff perchè si tratta di una sfida missione.",
                "Verifica mancante", array('back_link' => true));
        }

        wp_publish_post($post);

        $def_slug = wp_unique_post_slug($post->post_name, $post_id, 'publish','sfida_review', 0);
        $post->post_name = $def_slug;

        $new_owner = get_user_by('login', 'raccontisfida');
        $caporep_id = $post->post_author;
        $post->post_author = $new_owner->ID;
        wp_update_post($post);

        add_post_meta($post->ID, 'caporeparto', $caporep_id);

        if($commento_input != null && $commento_input != "") {
            _log("Aggiungo commento caporep - " . $commento_input );
            add_post_meta($post->ID, 'commento_caporep', date("y-m-d H:m") . " " . $commento_input, false);
        }

        _log("Racconto approvato: racconto " . $post->ID . " utente " . $current_user->ID);
        wp_die("Hai approvato il racconto! Potrai trovarlo nella pagina <a href=\"" . get_post_type_archive_link('sfida_review'). "\">Racconti sfide</a>", "Approvato!");
    } elseif ($verifica == 'Respingi') {
        $squadriglia = get_post_meta($post->ID, 'utente_originale', true);
        $user_sq = get_userdata($squadriglia);

        $post->post_author = $squadriglia;
        $post->post_status = 'draft';
        wp_update_post($post);

        wp_mail($user_sq->user_email, "Il racconto della sfida" . $post->post_title . " è da sistemare",
            "Ciao,\nIl tuo caporeparto ha visto il racconto sfida che hai mandato e ha".
            " trovato qualcosa da migliorare. Parla direttamente con lui/lei e modificalo nuovamente.".
            " Puoi tovare il racconto nel menu della bacheca alla voce 'Racconti Sfida.\n\nLo Staff RTD");
        _log("Inviata email per nuovo resoconto alla sq " . $squadriglia . " indirizzo " . $user_sq->user_email);
        _log("Racconto respinto: racconto " . $post->ID . " utente " . $current_user->ID);
        wp_die("Hai respinto il racconto, che è di nuovo modificabile dall'esploratore/guida che lo ha creato.".
            "Assicurati di informarlo sul perchè lo hai respinto e come migliorarlo.<br>\n<a href=\"". admin_url() ."\">Torna alla bacheca</a>.", "Respinto!");
    }
}

add_action('wp_head', 'get_change_sfida_review');

/* FINE GESTIONE RACCONTO SFIDA LATO CAPO REPARTO */

/* MOSTRA COMMENTI CAPOREP AL RACCONTO */

function mostra_commenti_caporep($content){

    global $current_user;
    
    $post_id = get_the_ID();
    $post = get_post($post_id);
    if($post->post_type != "sfida_review") { return $content; };

    if(! is_user_logged_in() ) { return; }
    
    if(! is_single() || $post->post_type != "sfida_review" ){
        return $content;
    }

    $res = " ";

    if(can_see_caporep_comments($post, $current_user)){
        $comments = get_post_meta($post->ID, 'commento_caporep', false);
        $res .= "<div class=\"bs-callout bs-callout-default\">";
        $res .= "<h4>Commenti del Capo Reparto</h4>";
        if(count($comments) == 0){
            $res .= "Non ci sono commenti inseriti dal caporeparto";
        } else {
            $res .= implode('<br>\n', $comments);
        }
        $res .= "</div>";
    }
    return $content . $res;
}
add_action('the_content', 'mostra_commenti_caporep');
/* FINE MOSTRA COMMENTI CAPOREP AL RACCONTO */



/* GESTIONE ISCRIZIONI */

function edit_iscrizioni($user){
    if(current_user_can('edit_iscrizioni') && user_can($user, 'insert_sfide_review')) {

        $iscrizioni = get_iscrizioni( $user->ID );

        $all_sfide = get_posts( array( 'post_type' => 'sfida_event', 'posts_per_page'   => -1, 'numberposts'   => -1 ) );

        echo "<h2>Iscrizioni a sfide</h2>";
        echo "<h3>Sfide a cui è iscritto</h3><ul>";
        foreach ( $all_sfide as $sfida ) {
            if ( is_sfida_subscribed( $sfida, $iscrizioni ) ) {
                echo "<li>";
                echo $sfida->post_title . " (" . get_iscrizione_status( $sfida, $user->ID ) . ")";
                echo "</li>";
            }
        }
        echo "</ul>";
        echo "<div style=\"color:red; font-weight: bold; font-size: 14pt;\">Attenzione: questa maschera modifica i dati solo lato wordpress e non lato portal!</div>";

        echo "<h3>Cancella iscrizione</h3>";
                echo '<select name="cancella_sfida" id="cancella_sfida"> ';
        echo '<option value=""></option>';
        foreach ( $all_sfide as $sfida ) {
            if ( is_sfida_subscribed( $sfida, $iscrizioni ) ) {
                            echo '<option value="' . $sfida->ID . '">' . $sfida->post_title . " - " . $sfida->ID.  '</option>';
            }
        }
        echo '</select>';


        echo "<h3>Nuova iscrizione</h3>";
        // echo '<input type="text" hidden="true" id="nuova_sfida_id" name="nuova_sfida_id">';
        echo '<select name="nuova_sfida" id="nuova_sfida"> ';
        echo "<option value=\"\"></option>";
        foreach ( $all_sfide as $sfida ) {
            if ( is_sfida_for_me( $sfida, false, $user->ID ) && ! is_sfida_subscribed( $sfida, $iscrizioni ) ) {
                echo '<option value="' . $sfida->ID . '">' . $sfida->post_title . " - " . $sfida->ID. '</option>';
            }
        }
        echo '</select>';
    }

}
add_action('edit_user_profile', 'edit_iscrizioni');

function get_iscrizioni_change($user_id) {
    if ( current_user_can('edit_user') && isset($_POST['nuova_sfida'])) {
        $nuova_sfida = filter_input(INPUT_POST, 'nuova_sfida', FILTER_SANITIZE_NUMBER_INT);
        if($nuova_sfida != ""){
            $sfida = get_post($nuova_sfida);
            if($sfida == null) {
                echo "<div class=\"Error\">Impossibile iscrivere l'utente alla sfida ". $nuova_sfida.": sfida non esistente.</div>";
                _log("Nuova iscrizione manuale: Sfida non trovata : " . $nuova_sfida);
            } else {
                rdt_iscrivi_utente_a_sfida($sfida, $user_id);
                echo "<div class=\"Update\">Utente iscritto alla sfida ". $sfida->post_title. " (ID ". $nuova_sfida.").</div>";
                _log("Iscritto manualmente utente " . $user_id . " alla sfida " . $nuova_sfida);
            }
        }
        if ( current_user_can('edit_user') && isset($_POST['cancella_sfida'])) {
            $nuova_sfida = filter_input(INPUT_POST, 'cancella_sfida', FILTER_SANITIZE_NUMBER_INT);
            if($nuova_sfida != ""){
                $sfida = get_post($nuova_sfida);
                if($sfida == null){
                    _log("Cancellazione iscrizione manuale: Sfida non trovata : " . $nuova_sfida);
                    echo "<div class=\"Error\">Impossibile disiscrivere l'utente dalla sfida ". $nuova_sfida.": sfida non esistente.</div>";
                } else {
                    rtd_disiscrivi_utente_da_sfida($sfida->ID, $user_id);
                    echo "<div class=\"Update\">Utente disiscritto dalla sfida ". $sfida->post_title. " (ID ". $nuova_sfida.").</div>";
                    _log("Cancellato manualmente utente " . $user_id . " alla sfida " . $nuova_sfida);
                }
            }
        }
    }
}
add_action('edit_user_profile_update', 'get_iscrizioni_change');

/* FINE GESTIONE ISCRIZIONI */

/** Rimuove (o sostituisce) i tag <IMG> da una stringa
 *
 * @param $str La stringa da cui togliere i tag <IMG>
 * @param string $replace (Opzionale) testo da sostituire, default ""
 * @return mixed La stringa da cui sono rimossi i tag <IMG> o sostituiti con $replace
 */
 function remove_img_tag($str, $replace = ""){
     return preg_replace("/<[\\s]*img[^>]+>/i", $replace, $str);
 }

function no_images_for_review_excerpts( $excerpt ){

    if( is_post_type_archive( 'sfida_review' )){
        return remove_img_tag($excerpt);
    }

    return $excerpt;
}

add_filter('get_the_excerpt', "no_images_for_review_excerpts");

/* END FORCE DASHBOARD TO BE ONE COLUMN */

/* NASCONDI LA PAGINA DI MODIFICA DEI RACCONTI AI CAPOREP */
function nascondi_modifica_racconti() {
    global $current_user;
    $cur = wp_get_current_user();
    $roles = $cur->roles;
    if( $roles[0] == "capo_reparto" ){
        remove_menu_page( 'edit.php' );
        remove_menu_page( 'edit.php?post_type=sfida_review' );
    }
}
add_action( 'admin_menu', 'nascondi_modifica_racconti' );
/* FINE NASCONDI LA PAGINA DI MODIFICA DEI RACCONTI AI CAPOREP */

/* BLOCCA PAGINA MODIFICA RACCONTI AI CAPOREP */

function blocca_modifica_racconti(){
    $current_screen = get_current_screen();
    $cur = wp_get_current_user();
    $roles = $cur->roles;
    if($roles[0] == 'capo_reparto'){
        _log("E' caporep");
        if( ($current_screen->base == 'edit' || $current_screen->base == 'post') && $current_screen->post_type == 'sfida_review' ) {
            _log("è la pagina");
            wp_redirect(admin_url());
            exit();
        }
    }
}

add_action( 'current_screen', 'blocca_modifica_racconti');
/* BLOCCA PAGINA MODIFICA RACCONTI AI CAPOREP */


/* SFIDE REVIEW TAG ARCHIVE */

function show_review_tag_page( $query ){
    if( is_tag() ){
        $query->set('post_type', 'sfida_review');
    }
}

add_action('pre_get_posts', 'show_review_tag_page');

/* END SFIDE REVIEW TAG ARCHIVE */

/* */

// /**
//  * Customize Event Query using Post Meta
//  * 
//  * @link http://www.billerickson.net/customize-the-wordpress-query/
//  * @param object $query data
//  *
//  */
// function sfida_event_query( $query ) {

//     // http://codex.wordpress.org/Function_Reference/current_time
//     $current_time = current_time('mysql'); 
//     list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = split( '([^0-9])', $current_time );
//     $current_timestamp = $today_year . $today_month . $today_day . $hour . $minute;

//     global $wp_the_query;
    
//     if ( $wp_the_query === $query && !is_admin() && is_post_type_archive( 'sfida_event' ) ) { 
//         // $meta_query = array(
//         //     array(
//         //         'key' => '_start_eventtimestamp',
//         //         // 'value' => $current_timestamp,
//         //         'compare' => 'EXISTS'
//         //     )
//         // );
//         // $query->set( 'meta_query', $meta_query );
//         $query->set( 'orderby', 'InizioEvento' );
//         $query->set( 'meta_key', '_start_eventtimestamp' );
//         $query->set( 'order', 'ASC' );
//         $query->set( 'posts_per_page', '4' );
//     }

// }

// add_action( 'pre_get_posts', 'sfida_event_query' );

/*
add_filter('is_protected_meta', 'my_is_protected_meta_filter', 10, 2);

function my_is_protected_meta_filter($protected, $meta_key) {
    return $meta_key == 'custom-fields' ? true : $protected;
}
*/
