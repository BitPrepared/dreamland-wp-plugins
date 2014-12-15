<?php
/**
 * Plugin Name: Return to Dreamland - Sfide
 * Plugin URI: http://github.com/BitPrepared/dreamland-wp-plugins
 * Description: Plugin per la gestione dei "Racconti Sfida"
 * Version: 0.1
 * Author: Bit Prepared
 * Author URI: http://github.com/BitPrepared 
 * License: GPLv3
 */

require_once('dreamers-sfide-utils.php');
require_once("dreamers-sfide-widget.php");

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
    $role->add_cap('insert_sfide');
    $role->add_cap('manage_sfide');
    $role->add_cap('promuovi_sfide_review');
    
    $role = get_role('utente_eg');
    $role->add_cap('view_sfide_review');
    $role->add_cap('insert_sfide_review');
    
    $role = get_role('capo_reparto');
    $role->add_cap('view_sfide_review');
    $role->add_cap('conferma_sfide_review');
    
    $role = get_role('editor');
    $role->add_cap('insert_sfide');
    $role->add_cap('manage_sfide');
    $role->add_cap('promuovi_sfide_review');
    $role->add_cap('view_sfide_review');
    $role->add_cap('view_other_sfide_review');
    $role->add_cap('conferma_sfide_review');

    $role = get_role('administrator');
    $role->add_cap('insert_sfide');
    $role->add_cap('manage_sfide');
    $role->add_cap('promuovi_sfide_review');
    $role->add_cap('insert_sfide_review');
    $role->add_cap('view_sfide_review');
    $role->add_cap('view_other_sfide_review');
    $role->add_cap('conferma_sfide_review');

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
        'rewrite' => true,
        'capabilities' => array(
            'edit_post'          => 'insert_sfide_review',
            'read_post'          => 'view_sfide_review',
            'delete_post'        => 'insert_sfide_review',
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
	    add_meta_box( 'sfide_event_limit_meta', 'Limita a', 'sfide_event_limit', 'sfida_event', 'normal', 'default', array());
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
        sel = e.target;
        selected = sel.options[sel.selectedIndex];
        if(selected.className == 'A'){ return; } 
        document
            .querySelector("#select_regione > option#" + selected.className)
            .setAttribute("selected", "selected");
    }

    function update_zone(e){
        sel = e.target;
        selected = sel.options[sel.selectedIndex];
        all = document.querySelectorAll("#select_zona > option");
        for(i = 0; i < all.length; i++){   
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
    $racconti_sfide_stored_meta = get_post_meta( $post->ID );

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
                <input type="checkbox" name="meta-visibilita-bacheca" id="meta-visibilita-bacheca" value="yes" <?php if ( isset ( $racconti_sfide_stored_meta['meta-visibilita-bacheca'] ) ) checked( $racconti_sfide_stored_meta['meta-visibilita-bacheca'][0], 'yes' ); ?> />
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
        
        $aa = $_POST[$key . '_year'];
        $mm = $_POST[$key . '_month'];
        $jj = $_POST[$key . '_day'];
        $hh = $_POST[$key . '_hour'];
        $mn = $_POST[$key . '_minute'];
        
        $aa = ($aa <= 0 ) ? date('Y') : $aa;
        $mm = ($mm <= 0 ) ? date('n') : $mm;
        $jj = sprintf('%02d',$jj);
        $jj = ($jj > 31 ) ? 31 : $jj;
        $jj = ($jj <= 0 ) ? date('j') : $jj;
        $hh = sprintf('%02d',$hh);
        $hh = ($hh > 23 ) ? 23 : $hh;
        $mn = sprintf('%02d',$mn);
        $mn = ($mn > 59 ) ? 59 : $mn;
        
        $events_meta[$key . '_year'] = $aa;
        $events_meta[$key . '_month'] = $mm;
        $events_meta[$key . '_day'] = $jj;
        $events_meta[$key . '_hour'] = $hh;
        $events_meta[$key . '_minute'] = $mn;
        $events_meta[$key . '_eventtimestamp'] = $aa . $mm . $jj . $hh . $mn;
        
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
            $y = get_post_meta($id,'_start_year',true);
            $m = get_post_meta($id,'_start_month',true);
            $d = get_post_meta($id,'_start_day',true);
            echo $d.'-'.$m.'-'.$y;
            break;
        case 'end_time_event':
            $y = get_post_meta($id,'_end_year',true);
            $m = get_post_meta($id,'_end_month',true);
            $d = get_post_meta($id,'_end_day',true);
            echo $d.'-'.$m.'-'.$y;
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
    // case 'id':
    //     echo $id;
    //         break;
    // case 'images':
    //     // Get number of images in gallery
    //     $num_images = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE post_parent = {$id};"));
    //     echo $num_images; 
    //     break;
        case 'regione':
            $r = get_post_meta($id,'_regione',true);
            echo $r;//  get_nome_regione_by_code($r);
            break;

    default:
        break;
    } // end switch
}   

// Add to admin_init function manage_{custom_type}_posts_custom_column
add_action('manage_sfida_event_posts_custom_column', 'manage_gallery_columns', 10, 2);

/*
        WIDGET SFIDE DISPONIBILI
*/

function sfide_disponibili_dashboard_widget(){

    global $regioni;

    $args = array(
        'posts_per_page'   => -1,
        'numberposts'      => -1,
        'offset'           => 0,
        'orderby'          => 'post_date',
        'order'            => 'DESC',
        // 'include'          => '',
        // 'exclude'          => '',
        'post_type'        => 'sfida_event',
        'post_status'      => 'publish'
        // , 'suppress_filters' => true
    );

    $posts_array = get_posts($args);

    $printout = array();

    foreach ($posts_array as $k => $p) {
        
        if(!is_sfida_alive($p)) { continue; }
        if(!is_sfida_for_me($p)) { continue; }

        $icons = get_icons_for_sfida($p);

        if ( check_validita_sfida($p) ) {

            $sfida_html = '<td><a style="font-size:14pt;" href="'. get_permalink($p->ID) . '">'. $p->post_title ."</a></td>\n";
            $sfida_html = $sfida_html . "<td>". get_limit_sfida($p, $regioni) . "</td>\n<td>";
            foreach ($icons as $icon) {
                $sfida_html = $sfida_html . '<img alt="'. $icon['caption'] . '" '
                . 'title="'. $icon['caption'] . '"'
                .' style="height:25px;margin:5px 5px -5px 5px;" src="'. $icon['src'] . '" \>';
            }
            $sfida_html = $sfida_html . "</td>";
            array_push($printout, $sfida_html);

        }
    }

    echo "<span style=\"text-align:right;\">Hai ". count($printout) ." sfide disponibili</span><br>";
    echo "<table id=\"sfide-disponibili\">";
    echo "<thead><tr><th>Sfida</th><th>Limitata a</th><th>Tipo di sfida</th></tr><thead>\n";
    echo "<tbody>\n";
    foreach ($printout as $key => $value) {
        echo "<tr>";
        echo $value;
        echo "</tr>";
    }
    echo "</tbody>\n";
    echo "<tfoot><tr><th>Sfida</th><th>Limitata a</th><th>Tipo di sfida</th></tr><tfoot>\n";
    echo "</table>";
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#sfide-disponibili').DataTable();
    });
    </script>
    <?php

}

function create_sfide_disponibili_widget(){
    global $current_user;

    // todo sostituire con una capability subscribe-sfide

    $admitted_role = array('utente_eg', 'administrator', 'editor');
    $roles = $current_user->roles;
    foreach ($roles as $role) {
        if(in_array($role, $admitted_role)){
            wp_add_dashboard_widget( 'sfide_disponibili', 'Sfide disponibili', 'sfide_disponibili_dashboard_widget', 'sfide_diponibili_filter' );
            return;
        }
    }

}

add_action('wp_dashboard_setup', 'create_sfide_disponibili_widget');


/* 
        WIDGET SFIDE DEGLI EG CHE SEGUI
*/
function sfide_dei_miei_eg_dashboard_widget(){
    global $current_user;
    global $regioni;

    $roles = $current_user->roles;

    switch ($roles) {
            case 'iabr':
            case 'referente_regionale':
                $m_key = USER_META_KEY_REGIONE;
                $msg = "della tua regione";
                break;
            case 'iabz':
                $m_key = USER_META_KEY_ZONA;
                $msg = "della tua zona";
                break;
            case 'capo_reparto':
            default:
                $m_key = USER_META_KEY_GROUP;
                $msg = "del tuo reparto ";
                break;
        }

    $m_value = get_user_meta($current_user->ID, $m_key, 1);

    if($m_value){
        $users_args = array(
            'meta_key' => $m_key,
            'meta_value' => $m_value
        );
        $query_users = get_users($users_args);
    } else {
        $query_users = array();
    }

    $c = 0;
    $printout = array();
    $all_posts = get_posts(array( 'post_type' => 'sfida_event', 'numberposts' => -1, 'posts_per_page' => -1));
    
    foreach ($query_users as $u) {
        $iscrizioni_user = get_iscrizioni($u->ID);
        foreach ($all_posts as $sfida) {          
            if(!is_sfida_subscribed($sfida, $iscrizioni_user)){
                continue;
            }
            $line = "";
            $line .= "<tr>";
            $line .= "<td>" . $sfida->post_title . "</td>";
            $line .= "<td>" . get_limit_sfida($sfida, $regioni) . "</td>";
            $line .= "<td>" . get_icons_html(get_icons_for_sfida($sfida)) . "</td>";
            $line .= "<td>" . get_iscrizione_status($sfida, $u->ID) . "</td>";
            $line .= "<td>" . $u->last_name . "</td>";
            $line .= "<td>" . $u->first_name . "</td>";
            $line .= "</tr>";
            array_push($printout, $line);
            $c += 1;
         } 
    }

    echo "<span style=\"text-align:right;\">Hai ". $c ." sfide a cui sono iscritti gli eg ";
    echo $msg . "</span><br>";
    echo "<table id=\"miei-eg-sfide\">";
    echo "<thead><tr><th>Sfida</th><th>Limitata a</th>";
    echo "<th>Tipo di sfida</th><th>Stato</th><th>Gruppo</th><th>Squadriglia</th></tr><thead>\n";
    echo "<tbody>\n";
    foreach ($printout as $key => $value) {
        echo $value;
    }
    echo "</tbody>\n";
    echo "<tfoot><tr><th>Sfida</th><th>Limitata a</th><th>Tipo di sfida</th><th>Stato</th><th>Gruppo</th><th>Squadriglia</th></tr><tfoot>\n";
    echo "</table>";
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#miei-eg-sfide').DataTable();
    });
    </script>
    <?php
  
}

function create_sfide_miei_eg_widget(){

    global $current_user;

    // todo sostituire con capability follow_sfide
    $admitted_roles = array('iabr', 'iabz', 'administrator', 'capo_reparto', 'referente_regionale');
    $roles = $current_user->roles;

    foreach ($roles as $r) {
        if(in_array($r, $admitted_roles)){ 
            add_meta_box('sfide_dei_miei_eg', 'Le sfide degli EG che segui', 'sfide_dei_miei_eg_dashboard_widget', 'dashboard', 'normal', 'high');
            return;
        }
    }
}

add_action('wp_dashboard_setup', 'create_mie_sfide_widget');


/*
        WIDGET LE MIE SFIDE
*/

function mie_sfide_dashboard_widget(){

    global $regioni;
    global $current_user;

    $posts_array = array();

/*    if(in_array('utente_eg', $current_user->roles)){
        $iscrizioni = get_iscrizioni();
        $args = array( 'post__in' => $iscrizioni, 'post_type' => 'sfida_event' );
        $posts_array = WP_Query( $args );
    }
*/
    $posts_array = get_posts(array('posts_per_page' => -1, 'numberposts' => -1, 'post_type' => 'sfida_event'));
    // _log("Mie sfide: query: " . count($posts_array)  . " record");
    $c = 0;
    $printout = array();

    $iscrizioni = get_iscrizioni();
    // _log($iscrizioni);
    foreach ($posts_array as $k => $p) {
        if(!is_sfida_subscribed($p, $iscrizioni)) { continue; }

        $user_r = get_user_meta('regione');
        $user_z = get_user_meta('zona');

        $icons = get_icons_for_sfida($p);

        $sfida_html = '<td><a style="font-size:14pt;" href="'. get_permalink($p->ID) . '">'. $p->post_title ."</a></td>\n";
        $sfida_html = $sfida_html . "<td>". get_limit_sfida($p, $regioni) . "</td>\n";
        $sfida_html = $sfida_html . "<td>" .  get_icons_html($icons) . "</td>";
        $sfida_html .= '<td>' . get_iscrizione_status($p) . '</td>';
        array_push($printout, $sfida_html);
        $c++;
    }

    echo "<span style=\"text-align:right;\">Hai ". $c ." sfide a cui sei iscritto</span><br>";
    echo "<table id=\"le-mie-sfide\">";
    echo "<thead><tr><th>Sfida</th><th>Limitata a</th>"; 
    echo "<th>Tipo di sfida</th>"; 
    echo "<th>Stato</th></tr><thead>\n";
    echo "<tbody>\n";
    foreach ($printout as $key => $value) {
        echo "<tr>";
        echo $value;
        echo "</tr>";
    }
    echo "</tbody>\n";
    echo "<tfoot><tr><th>Sfida</th><th>Limitata a</th>"; 
    echo "<th>Tipo di sfida</th>"; 
    echo "<th>Stato</th></tr><tfoot>\n";
    echo "</table>";
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#le-mie-sfide').DataTable();
    });
    </script>
    <?php

}

function create_mie_sfide_widget(){
    global $current_user;

    $admitted_roles = array('utente_eg', 'administrator', 'editor');
    $roles = $current_user->roles;

    foreach ($roles as $role) {
        if(in_array($role, $admitted_roles)){
            add_meta_box('le_mie_sfide', 'Le tue sfide', 'mie_sfide_dashboard_widget', 'dashboard', 'normal', 'high');
            return;
        }
    }
}

add_action('wp_dashboard_setup', 'create_sfide_miei_eg_widget');


function add_datatable(){
    $wp_plugin_url = plugin_dir_url( __FILE__ );
    wp_enqueue_style( 'data-table-css', '//cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css');
    wp_enqueue_script( 'data-table-js', $wp_plugin_url.'js/jquery.dataTables.min.js', array('jquery'));
}

add_action('wp_dashboard_setup', "add_datatable");

function add_custom_style(){
    ?>
    <style>
    td {
        text-align: center;
    }
    </style>
    <?php
}

add_action( 'admin_enqueue_scripts', 'add_custom_style' );



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

/* END FORCE DASHBOARD TO BE ONE COLUMN */

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
