<?php

// // CREG,NOME,NOME_SHORT,AREA
// $regioni = array(
// 	array("A","TUTTE LE REGIONI","CM_NAZ",0),
// 	array("B","ABRUZZO","ABR",3),
// 	array("C","BASILICATA","BAS",4),
// 	array("D","CALABRIA","CAL",4),
// );


// $zone = array(
// 	//CREG,CZONA,NOME
// 	array("A",1,"TUTTE LE ZONE"),
// 	array("B",1,"C.Z. CHIETI"),
// 	array("B",3,"C.Z. PESCARA"),
// 	array("C",1,"C.Z. MATERA POTENZA"),
// 	array("D",2,"C.Z. CS TIRRENICA"),
// 	array("D",3,"C.Z. COSTA GELSOMINI"),
// 	array("D",4,"C.Z. FATA MORGANA"),
// );

$zone = array();

if ( file_exists("zone.csv") ) {
	$handle = fopen("zone.csv","r");
	$row = 0;
	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	    $num = count($data);
	    $row++;
	    //CREG,CZONA,NOME
	    $zone[$row] = $data;
	}
	fclose($handle);
}

$regioni = array();

if ( file_exists("regioni.csv") ) {
	$handle = fopen("regioni.csv","r");
	$row = 0;
	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	    $num = count($data);
	    $row++;
	    // CREG,NOME,NOME_SHORT,AREA
	    $regioni[$row] = $data;
	}
	fclose($handle);
}

var_export($zone);

var_export($regioni);