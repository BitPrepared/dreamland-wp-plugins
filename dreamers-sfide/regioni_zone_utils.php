<?php

function get_nome_regione($r){
	return $r[1];
}

function get_abbrev_regione($r){
	return $r[2];
}

function get_nome_regione_by_code($r_code){
	global $regioni;
	// echo "<!-- Needle: ". $r_code. "\n";
	foreach ($regioni as $r) {
		// echo get_abbrev_regione($r) . "\n";
		if(get_abbrev_regione($r) == $r_code){
			return ucfirst(strtolower(get_nome_regione($r)));
		}
	}
	// echo "-->\n\n";
	return "";
}

function get_nome_zona_by_code($z_code){
	global $zone;
	foreach ($zone as $z) {
		if(get_codice_zona($z) == $z_code){
			return ucfirst(strtolower(get_nome_zona($z)));
		}
	}
	return "";
}

// Il codice è una lettera identificativa
function get_codice_regione($r){
	return $r[0];
}

function get_nome_zona($z){
	return trim(str_replace('C.Z. ', '', $z[2]));
}

function get_regione_zona($z, $regioni){
	$needle = $z[0];
	foreach($regioni as $r){
		if(get_codice_regione($r) == $needle){
			return $r;
		}
	}
}

function get_codice_zona($z){
	return $z[0] . $z[1];
}


function get_codice_regione_zona($z){
	return $z[0];
}

function is_zona_in_regione($z, $r){
	return $z[0] == get_codice_regione($r);
}

function ordina_regioni_per_nome($a, $b){
    return strcmp(get_nome_regione($a), get_nome_regione($b)); 
}

function ordina_zone_per_nome($a, $b){
	return strcmp(get_nome_zona($a), get_nome_zona($b));
}