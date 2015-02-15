<?php

define("RACCONTO_SFIDA_META_KEY", "racconto_sfida_");

abstract class StatusIscrizione {

    /* L'utente ha richiesto l'iscrizione */
    const RICHIESTA = 'Attiva';

    /* La sfida è stata portata a termine e caricato il resoconto*/
    const COMPLETATA = 'Conclusa';

    /* La sfida è approvata dal capo reparto */
    const APPROVATA = 'Approvata';

    /* Disiscrizione da parte dell'utente */
    const ANNULLATA = 'Annullata';

    /* Sfida conclusa ma non superata */
    const NON_SUPERATA = 'Non superata';

    /* Restitisce la costante a partire da una string
       Nota: controllare che il risultato non sia null
    */
    function get_value_from_string($s){
        switch ($s) {
            case 'Attiva': return StatusIscrizione::RICHIESTA;
            case 'Conclusa': return StatusIscrizione::COMPLETATA;
            case 'Approvata': return StatusIscrizione::APPROVATA;
            case 'Annullata': return StatusIscrizione::ANNULLATA;
            case 'Non superata' : return StatusIscrizione::NON_SUPERATA;
            default: return NULL;
        }
    }

    function as_array(){
        return array(RICHIEST, COMPLETATA, APPROVATA, ANNULLATA, NONSUPERATA);

    }
}

/** Ritorna la lista delle iscrizioni per un utente
 * @param null $user_id (Optional) L'ID dell'utente per cui cercare le iscrizioni, se null get_current_user_id() viene usato
 * @return array|mixed L'array con gli (come stringhe) delle sfide a cui è iscritto l'utente
 */
function get_iscrizioni($user_id = NULL){
    if($user_id == NULL){
        $user_id = get_current_user_id();
    } else {
        if(! $user_id instanceof WP_User){
            $aux = get_userdata( $user_id );
            if($aux === false){
                _log("get_iscrizioni: Utente " . $user_id . " non trovato");
                return array();
            }
        }
    }
    $res = get_user_meta($user_id, '_iscrizioni', False);
    return $res;
}

/** Stabilisce se l'utente è iscritto o meno alla sfida. Se non viene fornito un vettore di iscrizioni
 *  scarica le iscrizione dell'*utente corrente* con get_iscrizioni(). Nota che per test in successione è
 *  più efficente scaricare una volta sola il vettore delle iscrizioni.
 *
 * @param $p WP_Post object della sfida
 * @param bool|array $iscrizioni () Le iscrizioni a cui è iscritto l'utente, default false
 * @return bool True se l'utente è iscritto alla sfida
 */
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

    return false;
}

function rdt_iscrivi_utente_a_sfida($sfida, $user_id = NULL){
    if($user_id == NULL){
        $user_id = get_current_user_id();
    }

    _log("Iscrizione sfida " . $sfida->ID . " per utente " . $user_id);
    add_user_meta($user_id, '_iscrizioni', $sfida->ID, False);
    add_user_meta($user_id,'_iscrizione_'.$sfida->ID, StatusIscrizione::RICHIESTA, True);
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


function get_iscrizione_status($p, $user_id = NULL){

    if($user_id == NULL){
        $user_id = get_current_user_id();
    }

    return get_user_meta( $user_id,'_iscrizione_' . $p->ID, True);
}

function set_iscrizione_status($p, $s, $user_id = NULL){

    if($user_id == null){
        $user_id = get_current_user_id();
    }

    update_user_meta($user_id, '_iscrizione_' . $p->ID, $s);
}