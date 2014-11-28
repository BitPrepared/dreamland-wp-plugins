<?php
/**
 * Plugin Name: Sfide Return to Dreamland
 * Plugin URI: http://github.com/BitPrepared/dreamland-wp-plugins
 * Description: Plugin per la gestione dei "Racconti Sfida"
 * Version: 0.1
 * Author: Bit Prepared
 * Author URI: http://github.com/BitPrepared 
 * License: GPLv3
 */


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
    if ( null !== $role ) {
        $role->add_cap('view_other_sfide_review');
        $role->add_cap('view_sfide_review');
        $role->add_cap('insert_sfide');
        $role->add_cap('promuovi_sfide_review');
    }

    $role = get_role('utente_eg');
    if ( null !== $role ) {
        $role->add_cap('view_sfide_review');
        $role->add_cap('insert_sfide_review');
    }

    $role = get_role('capo_reparto');
    if ( null !== $role ) {
        $role->add_cap('view_sfide_review');
        $role->add_cap('conferma_sfide_review');
    }
    
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
    $taxonomy = 'tipologiesfide';
    $terms = get_terms($taxonomy); 
    foreach ($terms as $term) {
        wp_delete_term( $term->term_id, $taxonomy );
    }

    remove_role('referente_regionale');

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

    $role = get_role('capo_reparto');
    if ( null !== $role ) {
        $role->remove_cap('view_sfide_review');
        $role->remove_cap('conferma_sfide_review');
    }

    // When a role is removed, the users who have this role lose all rights on the site.
    // remove_role('nome')

    // $role = get_role('author');
    // $role->remove_cap('edit_others_pages');
    // $role->remove_cap('edit_others_posts');
    // $role->remove_cap('delete_others_pages');
    // $role->remove_cap('delete_others_posts');

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
            'edit_posts'         => 'update_core',
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
        'rewrite' => true,
        'capabilities' => array(
            'edit_post'          => 'insert_sfide',
            'read_post'          => 'insert_sfide',
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

    if ( is_array($res) ) {

        wp_insert_term(
          'Avventura', // the term 
          'tipologiesfide', // the taxonomy
          array(
            'description'=> 'Avventura',
            'slug' => 'grande-sfida-avventura',
            'parent'=> $res['term_id']
          )
        );

        wp_insert_term(
          'Grande Impresa', // the term 
          'tipologiesfide', // the taxonomy
          array(
            'description'=> 'Grande Impresa',
            'slug' => 'grande-sfida-impresa',
            'parent'=> $res['term_id']
          )
        );

        wp_insert_term(
          'Originalita', // the term 
          'tipologiesfide', // the taxonomy
          array(
            'description'=> 'Originalità',
            'slug' => 'grande-sfida-originalita',
            'parent'=> $res['term_id']
          )
        );

        wp_insert_term(
          'Traccia nel Mondo', // the term 
          'tipologiesfide', // the taxonomy
          array(
            'description'=> 'Traccia nel Mondo',
            'slug' => 'grande-sfida-traccia-nel-mondo',
            'parent'=> $res['term_id']
          )
        );

    }

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
          'Grande Impresa', // the term 
          'tipologiesfide', // the taxonomy
          array(
            'description'=> 'Grande Impresa',
            'slug' => 'sfida-speciale-impresa',
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
    if ( current_user_can('promuovi_sfide_review') )
    {
        add_meta_box( 'racconti_sfide_meta', 'Avanzate Sfida', 'racconti_sfide_meta_callback', 'sfida_review' );
    }
}
add_action( 'add_meta_boxes', 'sfide_custom_meta' );

function sfide_event_date($post, $args) {
    $metabox_id = $args['args']['id'];
    global $post, $wp_locale;

    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'ep_eventposts_nonce' );

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

    $curr_reg = get_post_meta($post->ID, '_regione', 1);
    $curr_zon = get_post_meta($post->ID, '_zona', 1);

    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'ep_eventposts_nonce' );

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

/**
 * Outputs the content of the meta box ($post e' sfida_review) --> http://themefoundation.com/wordpress-meta-boxes-guide/ x le tipologie
 */
function racconti_sfide_meta_callback( $post ) {
    wp_nonce_field( basename( __FILE__ ), 'racconti_sfida_nonce' );
    $racconti_sfide_stored_meta = get_post_meta( $post->ID );
    ?>
 
    <p>
        <span class="racconti-sfiderow-title">A cura del capo reparto</span>
        <div class="racconti-sfiderow-content">    
            <label for="meta-radio-one">
                <input type="radio" name="meta-radio" id="meta-radio-one" value="radio-one" <?php if ( isset ( $racconti_sfide_stored_meta['meta-radio'] ) ) checked( $racconti_sfide_stored_meta['meta-radio'][0], 'radio-one' ); ?>>
                Conferma
            </label>
            <label for="meta-radio-two">
                <input type="radio" name="meta-radio" id="meta-radio-two" value="radio-two" <?php if ( isset ( $racconti_sfide_stored_meta['meta-radio'] ) ) checked( $racconti_sfide_stored_meta['meta-radio'][0], 'radio-two' ); ?>>
                Rigetta
            </label>
        </div>
    </p>
    <p>

        <span class="racconti-sfiderow-title">A cura degli IRO</span>
        <div class="racconti-sfiderow-content">
            <label for="meta-checkbox">
                <input type="checkbox" name="meta-checkbox" id="meta-checkbox" value="yes" <?php if ( isset ( $racconti_sfide_stored_meta['meta-checkbox'] ) ) checked( $racconti_sfide_stored_meta['meta-checkbox'][0], 'yes' ); ?> />
                Promuovi
            </label>
        </div>
    </p>

    <?php
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
    if( isset( $_POST[ 'meta-checkbox' ] ) ) {
        update_post_meta( $post_id, 'meta-checkbox', 'yes' );
    } else {
        update_post_meta( $post_id, 'meta-checkbox', '' );
    }
     
    // Checks for input and saves if needed
    if( isset( $_POST[ 'meta-radio' ] ) ) {
        update_post_meta( $post_id, 'meta-radio', $_POST[ 'meta-radio' ] );
    }
 
}
add_action( 'save_post', 'racconti_sfide_meta_save' );

function ep_eventposts_save_meta( $post_id, $post ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    if ( !isset( $_POST['ep_eventposts_nonce'] ) )
        return;

    if ( !wp_verify_nonce( $_POST['ep_eventposts_nonce'], plugin_basename( __FILE__ ) ) )
        return;

    // Is the user allowed to edit the post or page?
    if ( !current_user_can( 'edit_post', $post->ID ) )
        return;

    // OK, we're authenticated: we need to find and save the data
    // We'll put it into an array to make it easier to loop though
    
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
    
    $events_meta['_regione'] = $_POST['_regione'];
    $events_meta['_zona'] = $_POST['_zona'];

    // Save Locations Meta
    // $events_meta['_event_location'] = $_POST['_event_location'];   
 

    // Add values of $events_meta as custom fields

    foreach ( $events_meta as $key => $value ) { // Cycle through the $events_meta array!
        if ( $post->post_type == 'revision' ) return; // Don't store custom data twice
        $value = implode( ',', (array)$value ); // If $value is an array, make it a CSV (unlikely)
        if ( get_post_meta( $post->ID, $key, FALSE ) ) { // If the custom field already has a value
            update_post_meta( $post->ID, $key, $value );
        } else { // If the custom field doesn't have a value
            add_post_meta( $post->ID, $key, $value );
        }
        if ( !$value ) delete_post_meta( $post->ID, $key ); // Delete if blank
    }

}

add_action( 'save_post', 'ep_eventposts_save_meta', 1, 2 );


function add_new_sfida_event_columns($gallery_columns) {
    
    $new_columns['title'] = 'Evento';
    $new_columns['author'] = 'Autore';
    $new_columns['tags'] = 'Tags';
    $new_columns['start_time_event'] = 'Inizio Evento';
    $new_columns['end_time_event'] = 'Fine Evento';
    $new_columns['category_event'] = 'Categoria';
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
add_filter( 'request', 'time_event_column_orderby' );

 
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
            $array = get_the_terms( $id, 'tipologiesfide' );
            $elenco = '';
            foreach ($array as $key => $value) {
                if ( $array[$key]->parent == false ) {
                    $parent = $array[$key]->name;
                } else {
                    $elenco .= ','.$array[$key]->name;
                }
            }
            echo $parent.' ['.substr($elenco, 1).']';
            break;
    // case 'id':
    //     echo $id;
    //         break;
    // case 'images':
    //     // Get number of images in gallery
    //     $num_images = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE post_parent = {$id};"));
    //     echo $num_images; 
    //     break;
    default:
        break;
    } // end switch
}   

// Add to admin_init function manage_{custom_type}_posts_custom_column
add_action('manage_sfida_event_posts_custom_column', 'manage_gallery_columns', 10, 2);

function sfide_disponibili_dashboard_widget(){

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
    echo "<span style=\"text-align:right;\">Hai ". count($posts_array) ." sfide disponibili</span><br>";
    echo "<ul>";
    foreach ($posts_array as $k => $p) {
        echo '<li><a style="font-size:14pt;" href="'. get_permalink($p->ID) . '">'. $p->post_title ."</a></li>";
    }
    echo "</ul>";

}

function create_sfide_disponibili_widget(){
    wp_add_dashboard_widget( 'sfide_disponibili', 'Sfide disponibili', 'sfide_disponibili_dashboard_widget', 'sfide_diponibili_filter' );
}

add_action('wp_dashboard_setup', 'create_sfide_disponibili_widget');


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
