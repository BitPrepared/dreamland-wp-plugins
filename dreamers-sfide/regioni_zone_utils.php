<?php

function get_nome_regione($r){
	return $r[1];
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