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

function is_sfida_for_me($p, $debug=False){

	if(!is_user_logged_in()){
		return False;
	}

	$curr_user = wp_get_current_user();

	$admitted_roles = array('utente_eg', 'administrator', 'editor');

	$is_admitted = False;
	foreach ($admitted_roles as $role) {
		$is_admitted = $is_admitted || in_array($role, $admitted_roles);
	}
	if(!$is_admitted){
		return False;
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

function get_iscrizioni(){
	return get_user_meta(get_current_user_id(), '_iscrizioni');
}

function is_sfida_subscribed($p, $iscrizioni=False){
	
	if( $iscrizioni === False){
		$iscrizioni = get_iscrizioni();
	}

	return $iscrizioni && in_array($post->ID, $iscrizioni);
}

function get_iscrizione_status($p){
	
	$q = get_user_meta( get_current_user_id(),'_iscrizione_' . $p->ID);
	
	return ($q) ? $q[0] : False;
}

function set_iscrizione_status($p, $s){
	update_user_meta(get_current_user_id(), '_iscrizione_' . $p->ID, $s);
}

function get_icons_for_sfida($p){
	$terms = wp_get_object_terms($p->ID, 'tipologiesfide');
        $icons = array();
        $captions = array();
        $has_shield = False;

        if($terms && ! is_wp_error($terms)){
            foreach ($terms as $term_key => $term_value) {
                switch ($term_value->name) {
                    case 'Avventura':
                        array_push($icons, array(
                            'src' => 'http://returntodreamland.agesci.org/blog/wp-content/uploads/2014/10/5.png',
                            'caption' => $term_value->name
                            )
                        );                        
                        break;
                    case 'Originalita':
                        array_push($icons, array(
                            'src' => 'http://returntodreamland.agesci.org/blog/wp-content/uploads/2014/10/3.png',
                            'caption' => $term_value->name
                            )
                        );
                        
                        break;
                    case 'Grande Impresa':
                        array_push($icons, array(
                            'src' => 'http://returntodreamland.agesci.org/blog/wp-content/uploads/2014/10/1.png',
                            'caption' => $term_value->name
                            )
                        );
                        
                        break;
                    case 'Traccia nel Mondo':
                        array_push($icons, array(
                            'src' => 'http://returntodreamland.agesci.org/blog/wp-content/uploads/2014/10/2.png',
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
                            'src' => 'http://returntodreamland.agesci.org/blog/wp-content/uploads/2014/10/6.png',
                            'caption' => 'Altro'
                            )
                        );
                        break;
                }
            }
        }

        return $icons;
}

/*
	REDIRECT DOPO IL LOGIN
*/

function send_to_dashboard($user_login, $user){
	wp_redirect(get_admin_url());
}

add_action('wp_login', 'send_to_dashboard', 10, 2);