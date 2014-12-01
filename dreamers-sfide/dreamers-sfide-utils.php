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

	$user = array();
	$user['region'] = reset(get_user_meta('region'));
	$user['zone'] = reset(get_user_meta('zone'));

	if($debug){
		echo "<!-- Meta utente regione : " . $user['region'] . ", zona: " . $user['zone'];
	}

	$sfida = array();
	$sfida['region'] = reset(get_post_meta($p->ID, '_regione'));
	$sfida['zone'] = reset(get_post_meta($p->ID, '_zona'));

	if($debug){
		echo "<!-- Meta post regione : " . $sfida['region'] . ", zona: " . $sfida['zone'];
	}

	return $sfida['region'] == "CM_NAZ" || ( $sfida['region'] == $user['region'] &&
		($sfida['zone'] == "A1" || $sfida['zone'] == $user['zone']));
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
                        var_dump($term_value);
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