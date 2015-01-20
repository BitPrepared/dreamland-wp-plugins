<?php
/**
 * User: Michele Carignani
 * Date: 19/01/15
 * Time: 13:33
 */

function generate_referenti_regionali(){

    $referenti = array(
        array("abruzzo.rtd@agesci.it", "B","ABRUZZO","ABR"),
        array("basilicata.rtd@agesci.it","C","BASILICATA","BAS"),
        array("calabria.rtd@agesci.it","D","CALABRIA","CAL"),
        array("campania.rtd@agesci.it","E","CAMPANIA","CAM"),
        array( "emiro.rtd@agesci.it","F","EMILIA ROMAGNA","EMR"),
        array("fvg.rtd@agesci.it", "G","FRIULI VENEZIA GIULIA", "FVG"),
        array("lazio.rtd@agesci.it", "H","LAZIO", "LAZ"),
        array("liguria.rtd@agesci.it", "I","LIGURIA" , "LIG"),
        array("lombardia.rtd@agesci.it","L", "LOMBARDIA","LOM"),
        array("marche.rtd@agesci.it", "M","MARCHE" ,"MAR"),
        array("molise.rtd@agesci.it","N","MOLISE" ,"MOL"),
        array("piemonte.rtd@agesci.it","O","PIEMONTE","PMN"),
        array("puglia.rtd@agesci.it","P","PUGLIA" ,"PUG"),
        // array("Q","SARDEGNA" ,"SAR"),
        array("sicilia.rtd@agesci.it","R","SICILIA" ,"SIC"),
        array("toscana.rtd@agesci.it","S","TOSCANA" ,"TOS"),
        array("taa.rtd@agesci.it","T","TRENTINO ALTO ADIGE","TAA"),
        //array("U","UMBRIA" ,"UMB"),
        //array("V","VALLE D AOSTA","VAO"),
        array("veneto.rtd@agesci.it", "Z", "VENETO" ,"VEN"),
    );


    foreach($referenti as $ref){

        $regione = strtolower($ref[2]);
        _log("Creo utente regione " . $regione . " email " . $ref[0]);
        $args = array(
            'user_login' => 'ref-'.$regione,
            'user_email' => $ref[0],
            'user_nicename' => 'Referente regione ' . ucfirst($regione),
            'user_pass' => wp_generate_password(8),
            'display_name' => 'Referente regione ' . ucfirst($regione),
            'role' => 'referente_regionale'
        );

        $id = username_exists($args['user_login']);
        if($id){
            _log('Utente '. $args['user_login'] .' esiste già con id ' . $id);
            continue;
        }

        $newid = wp_insert_user($args);

        if ( is_wp_error( $newid ) ) {
            $error_string = $newid->get_error_message();
            _log("Errore creazione utente ref: " .  $error_string);
            continue;
        }

        // Aggiungi il meta della regione e della zona
        $REGIONE = strtoupper($regione);
        $u_meta = array(
            'region' => $ref[1], // Codice della regione es: Z
            'regionShort' =>$ref[3], // es: VEN
            'regionDisplay' => $REGIONE, // es: VENETO
            'zone' => 'A1',
            'zoneDisplay' => '-- TUTTE LE ZONE --'
        );

        foreach($u_meta as $meta_key => $meta_value) {
            add_user_meta($newid, $meta_key, $meta_value, true);
        }

        if(false) {
            $subj = 'Utente ' . $args['user_nicename'] . ' creato';
            $msg = 'Un utente come referente regionale è stato creato per te su ' . get_site_url() . '\n';
            $msg .= 'Potrai seguire nella dashboard le sfide giocate dalle squadriglie della tua regione.\n';
            $msg .= 'Per accedere utilizza\n\tusername: ' . $args['user_login'] . '\n\tpassword: ';
            $msg .= $args['user_pass'] . '\n nella pagina di login che trovi qui ' . wp_login_url();
            $msg .= '';

            wp_mail($ref[0], $subj, $msg);
        }

        _log('Creato referente regionale ' . $args['user_nicename'] . ', id: ' . $newid);
    }
}

