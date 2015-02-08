<?php
/** @file dashboard-widgets.php
 *  Contiene le funzioni per la creazione dei widget
 *  nella dashboard per la gestione sfide, iscrizioni
 *  e news.
 */

/* **************************************  WIDGET SFIDE DISPONIBILI *********************************************   */

/** Crea il contenuto per un dashboard widget che mostra
 *  le sfide disponibili (ovvero a cui si può iscrivere)
 *  a un utente eg
 */
function sfide_disponibili_dashboard_widget(){

    global $regioni;

    $args = array(
        'posts_per_page'   => -1,
        'numberposts'      => -1,
        'offset'           => 0,
        'orderby'          => 'post_date',
        'order'            => 'DESC',
        'post_type'        => 'sfida_event',
        'post_status'      => 'publish'
    );

    $posts_array = get_posts($args);

    $printout = array();

    foreach ($posts_array as $k => $p) {

        if(!is_sfida_alive($p) || !is_sfida_for_me($p) || is_sfida_subscribed($p)) { continue; }

        $icons = get_icons_for_sfida($p);

        if ( check_validita_sfida($p) ) {

            $sfida_html = html_table_row(array(
               '<a style="font-size:14pt;" href="'. get_permalink($p->ID) . '">'. $p->post_title ."</a>",
                get_limit_sfida($p, $regioni),
                get_icons_html($icons)
            ));

            array_push($printout, $sfida_html);
        }
    }

    echo "<span style=\"text-align:right;\">Hai ". count($printout) ." sfide disponibili</span><br>";

    $colonne = array('Sfida', 'Rivolta a', 'Tipo di sfida');

    echo html_data_table('sfide-disponibili',$colonne,$printout);
}

function create_sfide_disponibili_widget(){
    global $current_user;

    // todo sostituire con una capability subscribe-sfide ?

    $admitted_role = array('utente_eg', 'administrator', 'editor');
    $roles = $current_user->roles;
    foreach ($roles as $role) {
        if(in_array($role, $admitted_role)){
            wp_add_dashboard_widget( 'sfide_disponibili', 'Sfide disponibili',
                'sfide_disponibili_dashboard_widget', 'sfide_diponibili_filter' );
            return;
        }
    }

}

add_action('wp_dashboard_setup', 'create_sfide_disponibili_widget');


/* **********************************   WIDGET SFIDE DEGLI EG CHE SEGUI ***************************************  */

/** Crea il contenuto per un dashboard widget che mostra le iscrizioni alle sfide
 *  degli utenti eg che "competono" all'utente. In particolare, per un utente IABR
 *  o Referente Regionale mosra tutte le iscrizioni della propria regione,
 *  per uno IABZ tutte quelle della propria zona, per un Caporeparto tutte quelle
 *  del proprio Gruppo.
 *
 */
function sfide_dei_miei_eg_dashboard_widget(){
    global $current_user;
    global $regioni;

    $roles = $current_user->roles;

    // Ruoli differenti hanno visibilità differenti sulle iscrizioni
    // e ognuno deve vedere solo le iscrizioni che gli competono
    switch ($roles[0]) {
        case 'iabr':
        case 'referente_regionale':
            // La meta_key nella tabella user meta che
            // descrive la regione di appartenenza
            $m_key = USER_META_KEY_REGIONE;
            $msg = "della tua regione";
            break;
        case 'iabz':
            $m_key = USER_META_KEY_ZONA;
            $msg = "della tua zona";
            break;
        case 'capo_reparto':
            $m_key = USER_META_KEY_GROUP;
            $msg = "del tuo reparto ";
            break;
        default:
            $m_key = False;
            $msg = "";
            break;
    }

    // Trova il valore per la corretta meta_key dopodichè esegue una query
    // e trova tutti gli utenti eg appartenenti alla regione/zona/gruppo

    $m_value = ($m_key) ? get_user_meta($current_user->ID, $m_key, 1) : null;

    if($m_value){
        $user_args = array(
            'meta_key' => $m_key,
            'meta_value' => $m_value,
            'role' => 'utente_eg'
        );
    } else {
        $user_args = array('role' => 'utente_eg');
    }
    $query_users = get_users($user_args);


    // Stampa l'HTML per la tabella  delle iscrizioni.
    // Scarica la lista di tutte le sfide e per ogni utente
    // nella query controlla se è iscritto e lo stato dell'iscrizione
    //
    // Salva le stringhe costruite in un array, in modo da ottenere il conto
    // totale delle iscrizioni trovate

    $count = 0;
    $printout = array();
    $all_posts = get_posts(array( 'post_type' => 'sfida_event', 'numberposts' => -1, 'posts_per_page' => -1));

    // Per ogni utente si scorrono tutte le sfide e si controlla se è iscritto
    foreach ($query_users as $u) {

        $iscrizioni_user = get_iscrizioni($u->ID);
        foreach ($all_posts as $sfida) {

            if(!is_sfida_subscribed($sfida, $iscrizioni_user)){ continue; }

            $r_id = get_racconto_sfida($u->ID, $sfida->ID);
            $html_racconto = "";
            if($r_id){
                $html_racconto ='<a href="'. get_permalink($r_id) .'">Vedi</a>';
                $racconto = get_post($r_id);
                switch($racconto->post_status){
                    // Se il post ha stato pending, segnala al camporeparto che il racconto è da approvare
                    case 'pending': $html_racconto = ' <strong style="color:#FF310F">Da approvare</strong> - ' . $html_racconto; break;
                    // Il racconto non è pronto, NON MOSTRARE IL LINK
                    case 'draft': $html_racconto = '<strong style="color: #334DFF">In attesa dell\'E/G</strong>'; break;
                    // Il racconto è pubblicato
                    case 'publish': $html_racconto = '<strong style="color: #00b700">Approvato</strong> - ' . $html_racconto; break;
                }
            }

            $line = html_table_row( array(
                $sfida->post_title,
                get_iscrizione_status($sfida, $u->ID),
                $html_racconto,
                get_limit_sfida($sfida, $regioni),
                get_icons_html(get_icons_for_sfida($sfida)),
                $u->last_name,
                $u->first_name
            ));

            array_push($printout, $line);
            $count += 1;
        }
    }

    $colonne = array('Sfida', 'Stato', 'Racconto', 'Rivolta a', 'Tipo di sfida', 'Gruppo', 'Squadriglia');

    echo '<span style="text-align:right;">Hai '. $count ." sfide a cui sono iscritti gli eg " .$msg. "</span><br>";

    // Genera e stampa l'HTML della tabella ed il Javascript per il plugin jQuery.Datatable
    echo html_data_table("miei-eg-sfide", $colonne, $printout);

}

function create_sfide_miei_eg_widget(){

    global $current_user;

    // todo sostituire con capability follow_sfide ?
    $admitted_roles = array('iabr', 'iabz', 'administrator', 'capo_reparto', 'referente_regionale', 'editor');
    $roles = $current_user->roles;

    foreach ($roles as $r) {
        if(in_array($r, $admitted_roles)){
            add_meta_box('notizie_capirep_da_dreamland', 'Notizie da Dreamland (Capi)',
                'notizie_capirep_dashboard_widget', 'dashboard', 'normal', 'high');
            add_meta_box('sfide_dei_miei_eg', 'Le sfide degli EG che segui',
                'sfide_dei_miei_eg_dashboard_widget', 'dashboard', 'normal', 'high');
            return;
        }
    }
}

add_action('wp_dashboard_setup', 'create_mie_sfide_widget');

/* ****************************************** NOTIZIE PER CAPIREPARTO ********************************************** */

/** Mostra i titoli delle news e delle news riservati
 *   ai capi reparto (categorie news e news-capi)
 */
function notizie_capirep_dashboard_widget(){

    $posts = get_posts(array('category_name'  => "news,news-capi"));
    foreach ($posts as $p) {
        ?>
        <p>
            <?php echo $p->post_date; ?>
            <span style="font-size: 15pt; font-weight:bold; margin-left: 10px">
              <a href="<?php echo post_permalink($p) ?>">
                  <?php echo $p->post_title; ?>
              </a>
        </span>
        </p>
    <?php
    }
}

/* *********************************************** WIDGET DELLE NOTIZIE ********************************************* */

/** Mostra i titoli delle news per tutti
 *  (Post con categoria news)
 *
 */
function notizie_eg_dashboard_widget(){

    $posts = get_posts(array('category_name'  => "news"));
    foreach ($posts as $p) {
        ?>
        <p>
            <?php echo $p->post_date; ?>
            <span style="font-size: 15pt; font-weight:bold; margin-left: 10px">
              <a href="<?php echo post_permalink($p) ?>">
                  <?php echo $p->post_title; ?>
              </a>
        </span>
        </p>
    <?php
    }
}

/* **************************************   WIDGET LE MIE SFIDE     *********************************************** */

/** Crea il contenuto per un dashboard widget che mostra le sfide a cui si è iscritti. In
 *  particolare:
 *    - titolo della sfida
 *    - tipo della sfida
 *    - a chi è rivolta
 *    - lo stato dell'iscrizione
 *    - il riferimento al racconto sfida (se la sfida è conclusa con successo)
 *
 *  Viene mostrato nella dashboard degli utenti eg
 */
function mie_sfide_dashboard_widget(){

    global $regioni;
    global $current_user;

    $posts_array = get_posts(array('posts_per_page' => -1, 'numberposts' => -1, 'post_type' => 'sfida_event'));

    $count = 0;
    $printout = array();

    $iscrizioni = get_iscrizioni();
    foreach ($posts_array as $k => $p) {
        if(!is_sfida_subscribed($p, $iscrizioni)) { continue; }

        $icons = get_icons_for_sfida($p);

        $racc_id = get_racconto_sfida($current_user->ID, $p->ID);
        $racc_html = "";
        if($racc_id){
            $racc = get_post($racc_id);
            if($racc->post_status == 'publish'){
                $racc_html = ' <a href="' . get_permalink($racc_id). '">Vedi</a>';
            } elseif($racc->post_status == 'draft'){
                $racc_html = ' <a href="'. get_edit_post_link($racc_id).'">Modifica</a>';
            } else {
                $racc_html = " In revisione";
            }
        }

        $sfida_html = html_table_row(array(
            '<a style="font-size:14pt;" href="'. get_permalink($p->ID) . '">'. $p->post_title ."</a>",
            get_iscrizione_status($p),
            $racc_html,
            get_limit_sfida($p, $regioni),
            get_icons_html($icons)
        ));

        array_push($printout, $sfida_html);
        $count++;
    }

    $colonne = array('Sfida', 'Stato', 'Racconto', 'Rivolta a', 'Tipo di sfida');

    echo "<span style=\"text-align:right;\">Hai ". $count ." sfide a cui sei iscritto</span><br>";

    echo html_data_table("le-mie-sfide", $colonne, $printout);

}

function create_mie_sfide_widget(){
    global $current_user;

    $admitted_roles = array('utente_eg', 'administrator', 'editor');
    $roles = $current_user->roles;

    foreach ($roles as $role) {
        if(in_array($role, $admitted_roles)){
            add_meta_box('notizie_eg_da_dreamland', 'Notizie da Dreamland', 'notizie_eg_dashboard_widget', 'dashboard', 'normal', 'high');
            add_meta_box('le_mie_sfide', 'Le tue sfide', 'mie_sfide_dashboard_widget', 'dashboard', 'normal', 'high');
            return;
        }
    }
}

add_action('wp_dashboard_setup', 'create_sfide_miei_eg_widget');

/** Aggiunge il plugin jQuery Datatable per rendere le tabelle filtrabili,
 *  ordinabili e paginate
 */
function add_datatable(){
    $wp_plugin_url = plugin_dir_url( __FILE__ );
    wp_enqueue_style( 'data-table-css', '//cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css');
    wp_enqueue_script( 'data-table-js', $wp_plugin_url.'js/jquery.dataTables.min.js', array('jquery'));
}

add_action('wp_dashboard_setup', "add_datatable");

/** Sposta al centro il contenuto delle tabelle nei widgets
 *
 */
function add_custom_style(){
    ?>
    <style>
        div#le_mie_sfide > td,
        div#sfide_disponibili > td,
        div#sfide_dei_miei_eg > td {
            text-align: center;
        }
    </style>
<?php
}
add_action( 'admin_enqueue_scripts', 'add_custom_style' );
