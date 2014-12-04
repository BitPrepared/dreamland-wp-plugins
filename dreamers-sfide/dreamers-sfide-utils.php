<?php

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
		return false;
	}

	$curr_user = wp_get_current_user();

	$admitted_roles = array('utente_eg', 'administrator', 'editor');

	$is_admitted = false;
	foreach ($admitted_roles as $role) {
		$is_admitted = $is_admitted || in_array($role, $admitted_roles);
	}
	if(!$is_admitted){
		return false;
	}

	$user = array();

	$u_r = get_user_meta($curr_user->ID,'region');
	$u_z = get_user_meta($curr_user->ID ,'zone');

	$user['region'] = ($u_r) ? reset($u_r) : "Nessuna";
	$user['zone'] = ($u_z) ? reset($u_z) : "Nessuna";

	if($debug){
		echo "<!-- Meta utente regione : " . $user['region'] . ", zona: " . $user['zone'] . " -->";
	}

	$sfida = array();

	$s_r = get_post_meta($p->ID, '_regione');
	$s_z = get_post_meta($p->ID, '_zona');

	$sfida['region'] = ($s_r) ? reset($s_r) : "Nessuna";
	$sfida['zone'] = ($s_z) ? reset($s_z) : "Nessuna" ;

	if($debug){
		echo "<!-- Meta post regione : " . $sfida['region'] . ", zona: " . $sfida['zone']. " -->";
	}

	return $sfida['region'] == "CM_NAZ" || // Se la sfida è nazionale oppure
			( $sfida['region'] == $user['region'] && // Se la regione è la stessa
				($sfida['zone'] == "-- TUTTE LE ZONE --" || $sfida['zone'] == $user['zone']));
}

function get_icons_for_sfida($p){
	$terms = wp_get_object_terms($p->ID, 'tipologiesfide');
        $icons = array();
        $has_shield = false;

        // http://codex.wordpress.org/Determining_Plugin_and_Content_Directories
        // in particolare http://codex.wordpress.org/Function_Reference/plugin_dir_url
        $wp_plugin_url = plugin_dir_url( __FILE__ );

        if($terms && ! is_wp_error($terms)){
            foreach ($terms as $term_key => $term_value) {
                switch ($term_value->name) {
                    case 'Avventura':
                        array_push($icons, array(
                            'src' => $wp_plugin_url.'images/5.png',
                            'caption' => $term_value->name
                            )
                        );                        
                        break;
                    case 'Originalita':
                        array_push($icons, array(
                            'src' => $wp_plugin_url.'images/3.png',
                            'caption' => $term_value->name
                            )
                        );
                        
                        break;
                    case 'Grande Impresa':
                        array_push($icons, array(
                            'src' => $wp_plugin_url.'images/1.png',
                            'caption' => $term_value->name
                            )
                        );
                        
                        break;
                    case 'Traccia nel Mondo':
                        array_push($icons, array(
                            'src' => $wp_plugin_url.'images/2.png',
                            'caption' => $term_value->name
                            )
                        );
                        break;        
                    case 'Grande Sfida':
                    case 'Sfida Speciale':
                        break;
                    default:
                        // var_dump($term_value);
                        if($has_shield)
                            break;
                        $has_shield = True;
                        array_push($icons, array(
                            'src' => $wp_plugin_url.'images/6.png',
                            'caption' => 'Altro'
                            )
                        );
                        break;
                }
            }
        }

        return $icons;
}
