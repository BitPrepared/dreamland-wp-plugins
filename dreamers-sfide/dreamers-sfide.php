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

require_once('dreamers-sfide-utils.php');
require_once("dreamers-sfide-widget.php");
require_once('generate-users.php');

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
    // $role->add_cap('view_sfide_review');
    // $role->remove_cap('delete_sfide_review');
    // $role->add_cap('conferma_sfide_review');
    
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

    generate_referenti_regionali($regioni);

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
        if(is_sfida_subscribed($p)) {continue; }
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
    echo "<thead><tr><th>Sfida</th><th>Rivolta a</th><th>Tipo di sfida</th></tr><thead>\n";
    echo "<tbody>\n";
    foreach ($printout as $key => $value) {
        echo "<tr>";
        echo $value;
        echo "</tr>";
    }
    echo "</tbody>\n";
    echo "<tfoot><tr><th>Sfida</th><th>Rivolta a</th><th>Tipo di sfida</th></tr><tfoot>\n";
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

    switch ($roles[0]) {
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
                $m_key = USER_META_KEY_GROUP;
                $msg = "del tuo reparto ";
                break;
            default:
                $m_key = False;
                $msg = "";
                break;
        }

    if($m_key){
        $m_value = get_user_meta($current_user->ID, $m_key, 1);
    } else {
        $m_value = NULL;
    }

    if($m_value){
        $user_args = array(
            'meta_key' => $m_key,
            'meta_value' => $m_value,
             'role' => 'utente_eg'
        );
    } else {
        $user_args = array('role' => 'utente_eg');
    }
    $query_users = get_users($user_args);

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
            $line .= "<td>";
            $r_id = get_racconto_sfida($u->ID, $sfida->ID);
            if($r_id){
                $line .= '<a href="'. get_permalink($r_id) .'">Vedi</a>'; //  <a href="'. get_edit_post_link($r_id).'">Modifica</a>';
            }
            $line .= "</td>";
            $line .= "</tr>";
            array_push($printout, $line);
            $c += 1;
         } 
    }

    echo "<span style=\"text-align:right;\">Hai ". $c ." sfide a cui sono iscritti gli eg ";
    echo $msg . "</span><br>";
    echo "<table id=\"miei-eg-sfide\">";
    echo "<thead><tr><th>Sfida</th><th>Rivolta a</th>";
    echo "<th>Tipo di sfida</th><th>Stato</th><th>Gruppo</th><th>Squadriglia</th><th>Racconto</th></tr><thead>\n";
    echo "<tbody>\n";
    foreach ($printout as $key => $value) {
        echo $value;
    }
    echo "</tbody>\n";
    echo "<tfoot><tr><th>Sfida</th><th>Rivolta a</th><th>Tipo di sfida</th><th>Stato</th><th>Gruppo</th><th>Squadriglia</th><th>Racconto</th></tr><tfoot>\n";
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
    $admitted_roles = array('iabr', 'iabz', 'administrator', 'capo_reparto', 'referente_regionale', 'editor');
    $roles = $current_user->roles;

    foreach ($roles as $r) {
        if(in_array($r, $admitted_roles)){
            add_meta_box('notizie_capirep_da_dreamland', 'Notizie da Dreamland (Capi)', 'notizie_capirep_dashboard_widget', 'dashboard', 'normal', 'high');
            add_meta_box('sfide_dei_miei_eg', 'Le sfide degli EG che segui', 'sfide_dei_miei_eg_dashboard_widget', 'dashboard', 'normal', 'high');
            return;
        }
    }
}

add_action('wp_dashboard_setup', 'create_mie_sfide_widget');

function notizie_capirep_dashboard_widget(){

    $posts = get_posts(array('category_name'  => "news,news-capi"));
    foreach ($posts as $p) {
    ?>
        <p>
        <?php echo $p->post_date; ?>
        <span style="font-size: 15pt; font-weight:bold; margin-left: 10px">   
              <a href="<?php echo post_permalink($p) ?>">
                <?php echo $p->post_title; ?>
            </a>
        </span>
        </p>
    <?php
    }
}

function notizie_eg_dashboard_widget(){

    $posts = get_posts(array('category_name'  => "news"));
    foreach ($posts as $p) {
    ?>
        <p>
        <?php echo $p->post_date; ?>
        <span style="font-size: 15pt; font-weight:bold; margin-left: 10px">   
              <a href="<?php echo post_permalink($p) ?>">
                <?php echo $p->post_title; ?>
            </a>
        </span>
        </p>
    <?php
    }
}

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
        $sfida_html .= "<td>". get_limit_sfida($p, $regioni) . "</td>\n";
        $sfida_html .= "<td>" .  get_icons_html($icons) . "</td>";
        $sfida_html .= '<td>' . get_iscrizione_status($p) . '</td>';
        $sfida_html .= '<td>';
        $racc_id = get_racconto_sfida($current_user, $p->ID);
        if($racc_id){
            $sfida_html .= '<a href="' . get_permalink($racc_id). '">Vedi</a><a href="'. get_edit_post_link($racc_id).'">Modifica</a>';    
        }
        $sfida_html .= '</td>';
        array_push($printout, $sfida_html);
        $c++;
    }

    echo "<span style=\"text-align:right;\">Hai ". $c ." sfide a cui sei iscritto</span><br>";
    echo "<table id=\"le-mie-sfide\">";
    echo "<thead><tr><th>Sfida</th><th>Rivolta a</th>"; 
    echo "<th>Tipo di sfida</th>"; 
    echo "<th>Stato</th><th>Racconto</th></tr><thead>\n";
    echo "<tbody>\n";
    foreach ($printout as $key => $value) {
        echo "<tr>";
        echo $value;
        echo "</tr>";
    }
    echo "</tbody>\n";
    echo "<tfoot><tr><th>Sfida</th><th>Rivolta a</th>"; 
    echo "<th>Tipo di sfida</th>"; 
    echo "<th>Stato</th><th>Racconto</th></tr><tfoot>\n";
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
            add_meta_box('notizie_eg_da_dreamland', 'Notizie da Dreamland', 'notizie_eg_dashboard_widget', 'dashboard', 'normal', 'high');
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

    <p>Per caricare i files delle foto,del video ecc...  premete sul bottone <b>Aggiungi Media</b>, attenzione che
        la dimensione dei files che andrete a caricare sul sito è limitata, quindi se i file che avete a disposizione
         sono troppo grandi dovrete ridurli (immagini e video più piccoli o corti), e anche questo per qualcuno sarà un nuova sfida !
        Se proprio non riuscite a ridurli, o se la sfida lo richiedesse, potete caricarli su un altra
        piattforma (ma attenti ai diritti d'autore, al rispetto della privacy ecc..) potete
        aggiungerli come link, scegliendo tra le opzioni che apparianno premendo il bottone Aggiung Media.</p>

    <p>Una volta che avrete completato il resoconto premete il bottone <b>Racconto Completato</b> dopo di
        ché verrà mandta una mail al vostro Capo Reparto per approvarne la condivisione.
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
                        var res = confirm("Vuoi pubblicare il racconto?" +
                        "Dopo averlo pubblicato non sarà più possibile modificarlo!" +
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
    global $post;

    if ( $post->post_type == 'sfida_review' ) {
        if ( !is_user_logged_in() ) {
            $content = '<a href="'.  wp_login_url( get_permalink() ). '" title="Accedi"><h2>Accedi per poter vedere questo racconto.</h2></a>';
        }
    }

    return $content;
}

add_filter( 'the_content', 'tp_stop_guestes' );


/* GESTIONE TRANSIZIONE STATUS DEI RACCONTI SFIDA */

function rs_draft_to_pending( $post ){
    global $current_user;
    if(! $post->post_type == 'sfida_review' ) return;

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
        $query_caporep = $query->get_results();
        $caporep = $query_caporep[0];

        $sfida_id = get_post_meta($post->ID, 'sfida', true);
        $sfida = get_post($sfida_id); 

        // manda una mail al capo reparto
        
        $mail_body_format = "La %s ha completato il racconto della sfida \"%s\" a cui si era iscritta!\n".
            "Puoi vedere la sfida qui: " . get_permalink($sfida->ID) . " .\n".
             "Una volta completato il racconto, la squadriglia non può più modificarlo ".
             "e tu devi approvarlo prima che sia pubblicato (solo per gli utenti iscritti) " .
             "su Return to DreamLand.\n\n".
             "Per vedere e confermare il racconto clicca qui: %s";

        // $preview_url = http://www.beta.returntodreamland.it/blog/?post_type=sfida_review&p=16&preview=true
        $preview_url = add_query_arg(array(
                'preview' => 'true',
                'post_type' => 'sfida_review',
                'p' => $post->ID
            ),
            get_site_url());

        wp_mail($caporep->user_email, 
            $post->post_title,
            sprintf($mail_body_format, $umeta['squadriglia'][0], $sfida->post_title, $preview_url));
        // cambia ownership del post
        _log("Email inviata a " . $caporep->user_email . " racconto " . $post->ID);
        
        wp_update_post(array('ID' => $post->ID, 'post_author' => $caporep->ID));
        _log("Utente ha completato il racconto " . $post->ID);
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

    if($post->post_type == 'sfida_review' && $post->post_status == 'pending' &&
        ($post->post_author == $current_user->ID || current_user_can('manage_options'))){

        $scroll_down = "<div style=\"font-size: 12pt; border:1px solid #a9a9a9; border-radius: 5px;margin:15px\"><p>".
            " Leggi il racconto fatto dalla Squadiglia, valuta se è veriterio".
            " e se può esere condiviso e diventare visibile a gli altri utenti di dreamland, in fondo alla pagina".
            " puoi eventualmente aggiungere i tuo commenti (non saranno visibii da gli eg, e non verranno condivisi),".
            " nel caso di una missione assegnata dallo staff è necessario scrivere nella sezione dei commenti una".
            " breve relazione che ci spieghi il motivo per cui avete assegnato quella specifica missione a".
            " quegli eg e la vostra verifica su come è andata.</p></div> ";
        $cbrns = " ";

        $commento_obbligatorio = 'true' == get_post_meta($post->ID, 'is_missione', true);
        $cbrns .=  "<div style=\"padding:10px;width:600px;\">";
        $cbrns .= "<div class=\"form-group\"><label for=\"commento_capo_rep\">";
        $cbrns .= $commento_obbligatorio ? 'Verifica della missione: (Necessaria)' : 'Commento: (Facoltativo)';
        $cbrns .= '</label><textarea class=\"form-control\" name="commento_capo_rep" id="commento_capo_rep"></textarea>';
        $cbrns .= "</div>";
        $cbrns .= "<button style=\"margin:10px\" id=\"approva\" class=\"btn btn-success\">Approva</button>";
        $cbrns .= "<button style=\"margin:10px\" id=\"respingi\" class=\"btn btn-danger\">Da sistemare</button>";
        $cbrns .= "</div> ";

        $testo_conferma_approva = "Vuoi approvare il resoconto della squadriglia?";
        $test_conferma_respingi = "Vuoi rimandare il resoconto della squadriglia?".
            " Una volta premuto il bottone l'EG potrà modificarlo nuovamente e poi dovrai nuovamente approvarlo.";

        ?>
        <script>
            jQuery(document).ready(function() {
                jQuery('#approva').on('click', function () {
                    var res = confirm("<?= $testo_conferma_approva ?>");
                    if(! res ) return;
                    <?php if($commento_obbligatorio): ?>
                    if(jQuery('#commento_capo_rep').val() == ""){
                        alert("Per le sfide di tipo missione è necessario che tu compili la verfica!");
                        return;
                    }
                    <?php endif; ?>
                    window.location = window.location + "&approva";
                });
                jQuery('#respingi').on('click', function () {
                    var res = confirm("<?= $test_conferma_respingi ?>");
                    if(! res ) return;
                    window.location = window.location + "&respingi";
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
        ($post->post_author != $current_user->ID  && ! current_user_can('manage_options'))){
        return;
    }

    if(isset($_GET['approva'])){
        $commento_obbligatorio = 'true' == get_post_meta($post->ID, 'is_missione', true);
        $commento_input = filter_input(INPUT_POST, 'commento_capo_rep', FILTER_SANITIZE_STRING);
        if($commento_obbligatorio && ($commento_input == null || $commento_input == "")){
            wp_die("Devi inserire la verifica della staff perchè si tratta di una sfida missione.",
                "Verifica mancante", array('back_link' => true));
        }

        wp_publish_post($post);

        $def_slug = wp_unique_post_slug($post->post_name, $post_id, 'publish','sfida_review', 0);
        _log("Old slug per racconto " . $post->ID . ":" . $post->post_name ." e nuovo " . $def_slug );
        $post->post_name = $def_slug;

        $new_owner = get_user_by('login', 'raccontisfida');
        $caporep_id = $post->post_author;
        $post->post_author = $new_owner->ID;
        wp_update_post($post);

        add_post_meta($post->ID, 'caporeparto', $caporep_id);

        _log(print_r($commento_input) . "\n\t" . print_r($_POST['commento_capo_rep']));
        if($commento_input != null && $commento_input != "") {
            _log("Aggiungo comemnto caporep -" . $commento_input );
            add_post_meta($post->ID, 'commento_caporep', date("y-m-d H:m") . " " . $commento_input, false);
            _log("Aggiungo comemnto caporep -" . $commento_input );
        }

        _log("Racconto approvato: racconto " . $post->ID . " utente " . $current_user->ID);
        wp_die("Hai approvato il racconto! Potrai trovarlo nella pagina <a href=\"" . get_post_type_archive_link('sfida_review'). "\">Racconti sfide</a>", "Approvato!");
    } elseif (isset($_GET['respingi'])) {
        $squadriglia = get_post_meta($post->ID, 'utente_originale', true);
        $user_sq = get_userdata($squadriglia);
        $post->post_author = $squadriglia;
        $post->post_status = 'draft';
        wp_mail($user_sq->user_email, "Il racconto della sfida" . $post->post_title . " è da sistemare",
            "Ciao,\nIl tuo caporeparto ha visto il racconto sfida che hai mandato e ha trovato".
            "qualcosa da migliorare. Per favore parla direttamente con lui/lei e modificalo nuovamente.".
            "Lo puoi trovare nel menu della bacheca alla voce 'Racconti Sfida");
        _log("Inviata email per nuovo resoconto alla sq " . $squadriglia . " indirizzo " . $user_sq->user_email);
        wp_update_post($post);
        _log("Racconto respinto: racconto " . $post->ID . " utente " . $current_user->ID);
        wp_die("Hai respinto il racconto, che è di nuovo modificabile dall'esploratore/guida che lo ha creato.".
            "Assicurati di informarlo sul perchè lo hai respinto e come migliorarlo.", "Respinto!");
    }
}

add_action('wp_head', 'get_change_sfida_review');

/* FINE GESTIONE RACCONTO SFIDA LATO CAPO REPARTO */

/* MOSTRA COMMENTI CAPOREP AL RACCONTO */

function mostra_commenti_caporep($content){

    global $current_user;

    $post_id = get_the_ID();
    $post = get_post($post_id);
    if(!is_user_logged_in()) { return; }

    if(! is_single() || ! $post->post_type == "sfida_review" ){
        return;
    }
    $res = " ";

    if(can_see_caporep_comments($post, $current_user)){
        $comments = get_post_meta($post->ID, 'commento_caporep', false);
        $res .= "<div style=\"font-size: 12pt; border:1px solid #a9a9a9; border-radius: 5px; margin:15px\">";
        if(count($comments) == 0){
            $res .= "<strong>Non ci sono commenti inseriti dal caporeparto</strong>";
        } else {
            $res .= "<strong>Ecco i commenti inseriti dal caporeparto</strong>";
            $res .= implode('<br>', $comments);
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

        $all_sfide = get_posts( array( 'post_type' => 'sfida_event' ) );

        echo "<h2>Iscrizioni a sfide</h2><ul>";
        echo "<h3>Sfide a cui è iscritto</h3>";
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
            if ( is_sfida_alive( $sfida ) && is_sfida_for_me( $sfida ) && ! is_sfida_subscribed( $sfida, $iscrizioni ) ) {
                echo '<option value="' . $sfida->ID . '">' . $sfida->post_title . '</option>';
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