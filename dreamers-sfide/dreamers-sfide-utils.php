<?php


/** Utility. Se l'argomento è un array (non stringa) ritorna il primo elemento.
 *  Altrimenti ritorna l'argomento stesso.
 * @param $maybe_arr
 * @return string
 */
function handle_array($maybe_arr){
    return is_array($maybe_arr) && ! is_string($maybe_arr) ? $maybe_arr[0] : $maybe_arr;
}

/** Controlla se una sfida è attiva o se le iscrizioni
 *  sono chiuse.
 *
 * @param $p Oggetto WP_Post della sfida
 * @return bool True se la sfida non è scaduta
 */
function is_sfida_alive($p){

    $all_meta = get_post_meta($p->ID);
    $format = "%s-%s-%s %s:%s";

    $year = handle_array($all_meta['_end_year']);
    $month = handle_array($all_meta['_end_month']);
    $day = handle_array($all_meta['_end_day']);
    $hour = handle_array($all_meta['_end_hour']);
    $min = handle_array($all_meta['_end_minute']);

    $time_string = sprintf($format, $year, $month, $day, $hour, $min);
    try {
        $scadenza = new DateTime($time_string);
        $now = new DateTime();
        return ($scadenza > $now);
    }
    catch(Exception $e){
        return false;
    }

}

/** Descrive a quali utenti è rivolta la sfida
 * @param $p WP_Post della sfida
 * @return string La regione e zona a cui è rivolta oppure 'Tutti' per le sfide nazionali
 */
function get_limit_sfida($p){

	$sfida = array();

	$sfida['region'] = get_post_meta($p->ID, '_regione', true);
	$sfida['zone'] = get_post_meta($p->ID, '_zona', true);

	if ($sfida['region'] == 'CM_NAZ'){
		return "Tutti";
	}

	$res = get_nome_regione_by_code($sfida['region']);

	if($sfida['zone'] != '-- TUTTE LE ZONE --'){
		$res = $res . ", zona " . $sfida['zone'];
	}

	return $res;
}

/** Controlla se un utente può iscriversi ad una certa sfida,
 *  in particolare se l'utente è loggato, è un utente_eg e fa parte della regione o zona
 *  a cui la sfida è rivolta. Ritorna true per le sfide nazionali.
 * @param $p WP_Post della sfida
 * @param bool $debug Stampa info per il debug
 * @param null $user_id ID dell'utente
 * @return bool true se l'utente può iscriversi alla sfida
 */
function is_sfida_for_me($p, $debug=false, $user_id = null){

	if(!is_user_logged_in()){
		return false;
	}

	$curr_user = wp_get_current_user();

	$admitted_roles = array('utente_eg', 'administrator', 'editor');

	$is_admitted = false;
	foreach ($admitted_roles as $role) {
		$is_admitted = $is_admitted || in_array($role, $curr_user->roles);
	}

	if(!$is_admitted){
		return false;
	}

	$user = array();
    if($user_id == null){
	$u_r = get_user_meta($curr_user->ID,'regionShort');
	$u_z = get_user_meta($curr_user->ID ,'zone');
    } else {
        $u_r = get_user_meta($user_id,'regionShort');
        $u_z = get_user_meta($user_id ,'zone');
    }

	$user['region'] = ($u_r) ? reset($u_r) : "Nessuna";
	$user['zone'] = ($u_z) ? reset($u_z) : "Nessuna";

	$sfida = array();

	$s_r = get_post_meta($p->ID, '_regione');
	$s_z = get_post_meta($p->ID, '_zona');

	$sfida['region'] = ($s_r) ? reset($s_r) : "Nessuna";
	$sfida['zone'] = ($s_z) ? reset($s_z) : "Nessuna" ;
	
	return $sfida['region'] == "CM_NAZ" || // Se la sfida è nazionale oppure
			( $sfida['region'] == $user['region'] && // Se la regione è la stessa
				($sfida['zone'] == "-- TUTTE LE ZONE --" || $sfida['zone'] == $user['zone']));

}

function is_sfida_completed($p){

    if(!$p || !isset($p->ID)){
        return false;
    }

    return get_iscrizione_status($p) === StatusIscrizione::COMPLETATA;
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


function rtd_tagify($s){
    
    $res = strtolower($s);

    $res = trim($res);

    // wp sanitize title (used to create slugs). see http://codex.wordpress.org/Function_Reference/sanitize_title
    // Removes special characters and accents
    $res = sanitize_title($res);

    // see http://codex.wordpress.org/Function_Reference/sanitize_title_with_dashes
    // Removes whitespaces and changes to dashes
    $res = sanitize_title_with_dashes($res);

    return $res;
}

/* 
    Ritorna l'id del resoconto in modo che si possa fare un redirect alla pagina 
    di modifica.
*/
function rtd_completa_sfida($sfida, $user_id = NULL, $is_sfida, $tiposfida, $superata){
    
    if($user_id == NULL){
        $user_id = get_current_user_id();
    }
    $usm = get_user_meta($user_id);
    $sqd = handle_array($usm['squadriglia']);
    $grp = handle_array($usm['groupDisplay']);

    if( $superata == 'false'){
        set_iscrizione_status($sfida, StatusIscrizione::NON_SUPERATA, $user_id);
        return -1;
    }

    set_iscrizione_status($sfida, StatusIscrizione::COMPLETATA, $user_id);

    // I tag associati al resoconto
    $post_tags_values = array(
        $sqd,
        $grp,
        handle_array($usm['zoneDisplay']),
        handle_array($usm['regionDisplay']),
        $tiposfida
    );

    // Normalizzati
    $post_tags = array_map("rtd_tagify", $post_tags_values);
    $post_slug = "racconto-" . rtd_tagify($sqd) . "-" . rtd_tagify($grp) ."-sfida-" . $sfida->post_slug;

    $post = array(
      'post_content'   => "", // The full text of the post.
      'post_title'     => $sqd . " " . $grp . ": " . $sfida->post_title, // The title of your post.
      'post_status' => 'draft',
      'post_type'      => 'sfida_review',
      'post_author'    => $user_id, // The user ID number of the author. Default is the current user ID.
      'post_excerpt'   => "La sq. " . $sqd . " ha completato la sfida \"" . $sfida->post_title . "\". Leggi il loro racconto.",
      'tags_input'     => $post_tags
    );

    $new_post_id = wp_insert_post( $post );

    // Salva connessione con la sfida
    add_post_meta($new_post_id, 'sfida', $sfida->ID, True);

    // Tieni traccia delle missioni
    $is_missione_string = ( (!$is_sfida) && $tiposfida == 'missione') ? 'true' : 'false';
    add_post_meta($new_post_id, 'is_missione', $is_missione_string);

    // Salva relazione tra utente e racconto
    add_user_meta($user_id, RACCONTO_SFIDA_META_KEY . $sfida->ID, $new_post_id);

    // Salva utente originale (perchè il valore author verrà cambiato in seguito)
    add_post_meta($new_post_id, 'utente_originale', $user_id);

    // Crea uno slug per il post
    $created_post = get_post($new_post_id);
    $created_post->post_name = wp_unique_post_slug($post_slug, $new_post_id, 'draft','sfida_review', 0);
    wp_update_post($created_post);

    _log("Completata sfida: " . $sfida->ID . " dall'utente " . $user_id .  "(is_missione =" .$is_missione_string .") . Creato resoconto " . $new_post_id );
    return $new_post_id;
}

/* Ritorna i post come oggetti WP_Post
*/
function rdt_get_all_sfide(){
    return get_posts( array('post_type' => 'sfida_event' ));
}

function check_validita_sfida($p) {
    $ids = $p;
    if ( is_object($p) ) {
        $ids = $p->ID;
    }
    $validita = get_post_meta($ids,'_validita',true);
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

/** Trova il post con il racconto della sfida per un utente e una
 *  sfida. Il valore si trova nelle usermeta dell'utente WP
 *  con chiave definita come RACCONTO_SFIDA_META_KEY . $sfida_id
 * @param $user_id ID dell'utente che ha scritto il racconto
 * @param $sfida_id ID della sfida
 * @return bool|mixed ID del WP_Post del racconto se il racconto esiste, false altrimenti
 */
function get_racconto_sfida($user_id, $sfida_id){
    $racconto_id = get_user_meta($user_id, RACCONTO_SFIDA_META_KEY.$sfida_id, true);
    if($racconto_id != '') return $racconto_id;
    return false;
}

/**
 * @param $post post object
 * @param $user user object or ID
 * @return bool true se l'utente è autorizzato, false altrimenti
 */
function can_see_caporep_comments($post, $user){

    if(!isset($user->ID)) {
        $user = get_user_by('id', $user);
    }

    if(user_can($user,'manage_options')) {
        return true;
    }

    $caporep_id = get_post_meta($post->ID, 'caporeparto', true);
    // $caporep = get_user_by('id', $caporep_id);
    $caporep_data = get_user_meta($caporep_id);

    $ruolo = $user->roles[0];
    switch($ruolo){
        case 'editor': return true;
        case 'capo_reparto' : return ($caporep_id == $user->ID);
        case 'referente_regionale':
            $regione = get_user_meta($user->ID, 'region');
            return $regione == $caporep_data['region'];
        default: return false;
    }
}

function get_icons_for_sfida($p){
	    $terms = wp_get_object_terms($p->ID, 'tipologiesfide');
        $icons = array();

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