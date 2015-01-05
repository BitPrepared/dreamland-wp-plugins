<?php

abstract class StatusIscrizione {

    /* L'utente ha richiesto l'iscrizione */
    const Richiesta = 'Attiva';
    
    /* La sfida è stata portata a termine e caricato il resoconto*/
    const Completata = 'Conclusa';

    /* La sfida è approvata dal capo reparto */
    const Approvata = 'Approvata';

    /* Disiscrizione da parte dell'utente */
    const Annullata = 'Annullata';

    /* Restitisce la costante a partire da una string 
       Nota: controllare che il risultato non sia null
    */
    function get_value_from_string($s){
        switch ($s) {
            case 'Attiva': return StatusIscrizione::Richiesta;
            case 'Conclusa': return StatusIscrizione::Completata;
            case 'Approvata': return StatusIscrizione::Approvata;
            case 'Annullata': return StatusIscrizione::Annullata;
            default: return NULL;
        }
    }

    function as_array(){
        return array(Richiesta, Completata, Approvata, Annullata);
    }
}

function is_sfida_alive($p){

	$per = array('_year', '_month', '_day', '_hour', '_minute');

	$data = array();
    foreach ($per as $key => $value) {
        $data[$value] = get_post_meta($p->ID, '_end' . $value);
    }

    $d = new DateTime($data['_year'][0].'-'.$data['_month'][0].'-'.$data['_day'][0].' '.
    	$data['_month'][0].':'.$data['_minute'][0]);
    $now = new DateTime();

    return ($d > $now);

}

function get_limit_sfida($p, $regioni){

	$sfida = array();

	$r = get_post_meta($p->ID, '_regione');
	$z = get_post_meta($p->ID, '_zona');

    if ( empty($r) ) {
        _log('Problema con la sfida '.$p->ID.' e\' senza campi meta obbligatori ');
        return array();
    }

	$sfida['region'] = $r[0];
	$sfida['zone'] = $z[0];

	if ($sfida['region'] == 'CM_NAZ'){
		return "Tutti";
	}

	$res = get_nome_regione_by_code($sfida['region']);

	if($sfida['zone'] != '-- TUTTE LE ZONE --'){
		$res = $res . ", zona " . $sfida['zone'];
	}

	return $res;
}

function is_sfida_for_me($p, $debug=false){

	if(!is_user_logged_in()){
        _log('utente non autenticato');
		return false;
	}

	$curr_user = wp_get_current_user();

	$admitted_roles = array('utente_eg', 'administrator', 'editor');

	$is_admitted = false;
	foreach ($admitted_roles as $role) {
		$is_admitted = $is_admitted || in_array($role, $curr_user->roles);
	}

	if(!$is_admitted){
        _log('Permessi insufficienti per iscriversi');
		return false;
	}

	$user = array();

	$u_r = get_user_meta($curr_user->ID,'regionShort');
	$u_z = get_user_meta($curr_user->ID ,'zone');

	$user['region'] = ($u_r) ? reset($u_r) : "Nessuna";
	$user['zone'] = ($u_z) ? reset($u_z) : "Nessuna";

    // _log("<!-- Meta utente regione : " . $user['region'] . ", zona: " . $user['zone'] . " -->");

	$sfida = array();

	$s_r = get_post_meta($p->ID, '_regione');
	$s_z = get_post_meta($p->ID, '_zona');

	$sfida['region'] = ($s_r) ? reset($s_r) : "Nessuna";
	$sfida['zone'] = ($s_z) ? reset($s_z) : "Nessuna" ;

    // _log("<!-- Meta post regione : " . $sfida['region'] . ", zona: " . $sfida['zone']. " -->");
	
	return $sfida['region'] == "CM_NAZ" || // Se la sfida è nazionale oppure
			( $sfida['region'] == $user['region'] && // Se la regione è la stessa
				($sfida['zone'] == "-- TUTTE LE ZONE --" || $sfida['zone'] == $user['zone']));

}

function get_iscrizioni($user_id = NULL){
    if($user_id == NULL){
        $user_id = get_current_user_id();
    } else {
        $aux = get_userdata( $user_id );
        if($aux == false){
            return array();
        }
    }
    $res = get_user_meta($user_id, '_iscrizioni', False);
    return $res;
}

function is_sfida_subscribed($p, $iscrizioni=False){
	
    if(!$p || !isset($p->ID)){
        return false;
    }

	if( $iscrizioni === False){
		$iscrizioni = get_iscrizioni();
	}

	if ($iscrizioni && in_array($p->ID, $iscrizioni)){
        return true;
    }
}

function is_sfida_speciale($p) {
    
    $terms = wp_get_object_terms($p->ID, 'tipologiesfide');
    if($terms && ! is_wp_error($terms)){
        foreach ($terms as $term_key => $term_value) {
            if ($term_value->name == "Sfida Speciale") {
                return true;
            }
        }
    }

    return false;
}

function rdt_iscrivi_utente_a_sfida($sfida, $user_id = NULL){
    if($user_id == NULL){
        $user_id = get_current_user_id();
    }

    _log("Iscrizione sfida " . $sfida->ID . " per utente " . $user_id);
    add_user_meta($user_id, '_iscrizioni', $sfida->ID, False);
    add_user_meta($user_id,'_iscrizione_'.$sfida->ID, StatusIscrizione::Richiesta, True);
}

/* 
    Ritorna l'id del resoconto in modo che si possa fare un redirect alla pagina 
    di modifica.
*/
function rtd_completa_sfida($sfida, $user_id = NULL){
    
    if($user_id == NULL){
        $user_id = get_current_user_id();
    }

    $sq = get_user_meta($user_id, 'squadriglia', True);

    set_iscrizione_status($sfida, StatusIscrizione::Completata, $user_id);
    $post = array(
      'post_content'   => "<i>Inserisci qui il racconto della sfida! Non dimenticare foto e video :)</i>", // The full text of the post.
      // 'post_name'      => "", // The name (slug) for your post
      'post_title'     => "La sq. " . $sq . " ha completato la sfida " . $sfida->post_title, // The title of your post.
      // 'post_status'    => [ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
      // 'post_status'    => [ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
      'post_status' => 'draft',
      'post_type'      => 'sfida_review',
      'post_author'    => $user_id, // The user ID number of the author. Default is the current user ID.
      // 'ping_status'    => [ 'closed' | 'open' ] // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
      // 'post_parent'    => [ <post ID> ] // Sets the parent of the new post, if any. Default 0.
      // 'menu_order'     => [ <order> ] // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
      // 'to_ping'        => // Space or carriage return-separated list of URLs to ping. Default empty string.
      // 'pinged'         => // Space or carriage return-separated list of URLs that have been pinged. Default empty string.
      // 'post_password'  => [ <string> ] // Password for post, if any. Default empty string.
      // 'guid'           => // Skip this and let Wordpress handle it, usually.
      // 'post_content_filtered' => // Skip this and let Wordpress handle it, usually.
      'post_excerpt'   => "La sq" . $sq . "ha completato la sfida \"" . $sfida->post_title . "\". Leggi il loro racconto.",
      // 'post_date'      => [ Y-m-d H:i:s ], // The time post was made.
      // 'post_date_gmt'  => [ Y-m-d H:i:s ], // The time post was made, in GMT.
      // 'comment_status' => [ 'closed' | 'open' ] // Default is the option 'default_comment_status', or 'closed'.
      // 'post_category'  => [ array(<category id>, ...) ] // Default empty.
      // 'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
      // 'tax_input'      => [ array( <taxonomy> => <array | string> ) ] // For custom taxonomies. Default empty.
      // 'page_template'  => [ <string> ] // Requires name of template file, eg template.php. Default empty.
    );

    $new_post_id = wp_insert_post( $post );  
    add_post_meta($new_post_id, 'sfida', $sfida->ID, True);
    _log("Completata sfida: " . $sfida->ID . " dall'utente " . $user_id . ". Creato resoconto " . $new_post_id );
    return $new_post_id;
}

function rtd_disiscrivi_utente_da_sfida($idsfida, $user_id = NULL){
    if($user_id == NULL){
        $user_id = get_current_user_id();
    }

    // Tieni traccia della disiscrizione
    _log("Sfida annullata, utente: " . $user_id . ", sfida: " . $idsfida);
    if ( !delete_user_meta($user_id, '_iscrizione_'.$idsfida) ) {
        _log('meta non cancellato  _iscrizione_'.$idsfida);
    }

    if ( !delete_user_meta($user_id, '_iscrizioni', $idsfida) ) {
        _log('meta non cancellato  _iscrizioni : '.$idsfida);
    }

}

function rdt_get_all_iscrizioni(){
    global $wpdb;
    $results = $wpdb->get_results( 'SELECT user_id, meta_value FROM wp_usermeta WHERE meta_key = \'_iscrizioni\'', OBJECT );
    return $results;
}

/* Ritorna i post come oggetti WP_Post
*/
function rdt_get_all_sfide(){
    return get_posts( array('post_type' => 'sfida_event' ));
}

function get_iscrizione_status($p, $user_id = NULL){
	
    if($user_id == NULL){
        $user_id = get_current_user_id();
    }

	return get_user_meta( $user_id,'_iscrizione_' . $p->ID, True);
}

function set_iscrizione_status($p, $s, $user_id = NULL){

	update_user_meta(get_current_user_id(), '_iscrizione_' . $p->ID, $s);
}

function check_validita_sfida($p) {
    $id = $p;
    if ( is_object($p) ) {
        $id = $p->ID;
    }
    $validita = get_post_meta($id,'_validita',true);
    $bool = filter_var($validita, FILTER_VALIDATE_BOOLEAN);
    return $bool;
}

function get_categorie_sfida($p){
       $terms = wp_get_object_terms($p->ID, 'tipologiesfide');
       return $terms;
}

function get_elenco_categorie_sfida($p) {

    $res = array();
    $terms = wp_get_object_terms($p->ID, 'tipologiesfide');
    if($terms && ! is_wp_error($terms)){
        foreach ($terms as $term_key => $term_value) {
            if ($term_value->name != "Sfida Speciale" && $term_value->name != "Grande Sfida") {
                $res[] = $term_value->name;
            }
        }
    }
    return $res;
}

function get_icons_for_sfida($p){
	    $terms = wp_get_object_terms($p->ID, 'tipologiesfide');
        $icons = array();
        $has_shield = false;

        // http://codex.wordpress.org/Determining_Plugin_and_Content_Directories
        // in particolare http://codex.wordpress.org/Function_Reference/plugin_dir_url
        $wp_plugin_url = plugin_dir_url( __FILE__ );

        $all_icons = array(
            "Avventura" => array(
                'src' => $wp_plugin_url.'images/5.png',
                'caption' => "Avventura"
            ),
            "Originalita" => array(
                'src' => $wp_plugin_url.'images/3.png',
                'caption' => "Originalita"
            ),
            "Grande Impresa" => array(
                'src' => $wp_plugin_url.'images/1.png',
                'caption' => "Grade Impresa"
            ),
            "Traccia nel Mondo" => array(
                'src' => $wp_plugin_url.'images/2.png',
                'caption' => "Traccia nel Mondo"
            ),
            "Altro" => array(
                'src' => $wp_plugin_url.'images/6.png',
                'caption' => "Altro"
            )
        );

        if($terms && ! is_wp_error($terms)){
            foreach ($terms as $term_key => $term_value) {
                if ($term_value->name == "Grande Sfida") {
                    foreach(array("Avventura", "Originalita", "Grande Impresa", "Traccia nel Mondo") as $t){
                        $icons[$t] = $all_icons[$t];
                    }
                } else {
                    if(isset($all_icons[$term_value->name])){
                        $icons[$term_value->name] = $all_icons[$term_value->name];
                    }
                }
            }
        }

        return $icons;
}

function get_icons_html($icons){
    $res = "";
    foreach ($icons as $icon) {
            $res = $res . '<img alt="'. $icon['caption'] . '" '
            . 'title="'. $icon['caption'] . '"'
            .' style="height:25px;margin:5px 5px -5px 5px;" src="'. $icon['src'] . '" \>';
    }
    return $res;

}