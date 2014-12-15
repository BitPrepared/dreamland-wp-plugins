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

function get_iscrizione_status($p, $user_id = NULL){
	
    if($user_id == NULL){
        $user_id = get_current_user_id();
    }

	return get_user_meta( $user_id,'_iscrizione_' . $p->ID, True);
}

function set_iscrizione_status($p, $s){
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