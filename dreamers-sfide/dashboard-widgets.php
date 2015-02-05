<?php

/*  WIDGET SFIDE DISPONIBILI   */

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

        if(!is_sfida_alive($p)) { continue; }
        if(!is_sfida_for_me($p)) { continue; }
        if(is_sfida_subscribed($p)) {continue; }
        $icons = get_icons_for_sfida($p);

        if ( check_validita_sfida($p) ) {

            $sfida_html = '<td><a style="font-size:14pt;" href="'. get_permalink($p->ID) . '">'. $p->post_title ."</a></td>\n";
            $sfida_html = $sfida_html . "<td>". get_limit_sfida($p, $regioni) . "</td>\n<td>";
            foreach ($icons as $icon) {
                $sfida_html = $sfida_html . '<img alt="'. $icon['caption'] . '" '
                . 'title="'. $icon['caption'] . '"'
                .' style="height:25px;margin:5px 5px -5px 5px;" src="'. $icon['src'] . '" \>';
            }

            $sfida_html = $sfida_html . "</td>";
            array_push($printout, $sfida_html);
        }
    }

    echo "<span style=\"text-align:right;\">Hai ". count($printout) ." sfide disponibili</span><br>";
    echo "<table id=\"sfide-disponibili\">";
    echo "<thead><tr><th>Sfida</th><th>Rivolta a</th><th>Tipo di sfida</th></tr><thead>\n";
    echo "<tbody>\n";
    foreach ($printout as $key => $value) {
    echo "<tr>";
        echo $value;
        echo "</tr>";
    }
    echo "</tbody>\n";
    echo "<tfoot><tr><th>Sfida</th><th>Rivolta a</th><th>Tipo di sfida</th></tr><tfoot>\n";
    echo "</table>";
    ?>

    <script type="text/javascript">
        jQuery(document).ready(function($){
            $('#sfide-disponibili').DataTable();
        });
    </script>
<?php

}

function create_sfide_disponibili_widget(){
    global $current_user;

    // todo sostituire con una capability subscribe-sfide ?

    $admitted_role = array('utente_eg', 'administrator', 'editor');
    $roles = $current_user->roles;
    foreach ($roles as $role) {
        if(in_array($role, $admitted_role)){
            wp_add_dashboard_widget( 'sfide_disponibili', 'Sfide disponibili', 'sfide_disponibili_dashboard_widget', 'sfide_diponibili_filter' );
            return;
        }
    }

}

add_action('wp_dashboard_setup', 'create_sfide_disponibili_widget');


/* ******************   WIDGET SFIDE DEGLI EG CHE SEGUI *******************  */

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

    switch ($roles[0]) {
        case 'iabr':
        case 'referente_regionale':
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

    if($m_key){
        $m_value = get_user_meta($current_user->ID, $m_key, 1);
    } else {
        $m_value = NULL;
    }

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

    $c = 0;
    $printout = array();
    $all_posts = get_posts(array( 'post_type' => 'sfida_event', 'numberposts' => -1, 'posts_per_page' => -1));

    foreach ($query_users as $u) {
        $iscrizioni_user = get_iscrizioni($u->ID);
        foreach ($all_posts as $sfida) {
            if(!is_sfida_subscribed($sfida, $iscrizioni_user)){
                continue;
            }
            $line = "";
            $line .= "<tr>";
            $line .= "<td>" . $sfida->post_title . "</td>";
            $line .= "<td>" . get_limit_sfida($sfida, $regioni) . "</td>";
            $line .= "<td>" . get_icons_html(get_icons_for_sfida($sfida)) . "</td>";
            $line .= "<td>" . get_iscrizione_status($sfida, $u->ID) . "</td>";
            $line .= "<td>" . $u->last_name . "</td>";
            $line .= "<td>" . $u->first_name . "</td>";
            $line .= "<td>";
            $r_id = get_racconto_sfida($u->ID, $sfida->ID);
            if($r_id){
                $line .= '<a href="'. get_permalink($r_id) .'">Vedi</a>'; //  <a href="'. get_edit_post_link($r_id).'">Modifica</a>';
            }
            $line .= "</td>";
            $line .= "</tr>";
            array_push($printout, $line);
            $c += 1;
        }
    }

    echo "<span style=\"text-align:right;\">Hai ". $c ." sfide a cui sono iscritti gli eg ";
    echo $msg . "</span><br>";
    echo "<table id=\"miei-eg-sfide\">";
    echo "<thead><tr><th>Sfida</th><th>Rivolta a</th>";
    echo "<th>Tipo di sfida</th><th>Stato</th><th>Gruppo</th><th>Squadriglia</th><th>Racconto</th></tr><thead>\n";
    echo "<tbody>\n";
    foreach ($printout as $key => $value) {
        echo $value;
    }
    echo "</tbody>\n";
    echo "<tfoot><tr><th>Sfida</th><th>Rivolta a</th><th>Tipo di sfida</th><th>Stato</th><th>Gruppo</th><th>Squadriglia</th><th>Racconto</th></tr><tfoot>\n";
    echo "</table>";
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($){
            $('#miei-eg-sfide').DataTable();
        });
    </script>
<?php

}

function create_sfide_miei_eg_widget(){

    global $current_user;

    // todo sostituire con capability follow_sfide ?
    $admitted_roles = array('iabr', 'iabz', 'administrator', 'capo_reparto', 'referente_regionale', 'editor');
    $roles = $current_user->roles;

    foreach ($roles as $r) {
        if(in_array($r, $admitted_roles)){
            add_meta_box('notizie_capirep_da_dreamland', 'Notizie da Dreamland (Capi)', 'notizie_capirep_dashboard_widget', 'dashboard', 'normal', 'high');
            add_meta_box('sfide_dei_miei_eg', 'Le sfide degli EG che segui', 'sfide_dei_miei_eg_dashboard_widget', 'dashboard', 'normal', 'high');
            return;
        }
    }
}

add_action('wp_dashboard_setup', 'create_mie_sfide_widget');

/* ************************** NOTIZIE PER CAPIREPARTO ************************************ */

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

/* ************************** WIDGET DELLE NOTIZIE **************************************** */

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

/* *****************************   WIDGET LE MIE SFIDE     ******************************** */

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

    $c = 0;
    $printout = array();

    $iscrizioni = get_iscrizioni();
    foreach ($posts_array as $k => $p) {
        if(!is_sfida_subscribed($p, $iscrizioni)) { continue; }

        $icons = get_icons_for_sfida($p);

        $sfida_html = '<td><a style="font-size:14pt;" href="'. get_permalink($p->ID) . '">'. $p->post_title ."</a></td>\n";
        $sfida_html .= "<td>". get_limit_sfida($p, $regioni) . "</td>\n";
        $sfida_html .= "<td>" .  get_icons_html($icons) . "</td>";
        $sfida_html .= '<td>' . get_iscrizione_status($p) . '</td>';
        $sfida_html .= '<td>';
        $racc_id = get_racconto_sfida($current_user->ID, $p->ID);
        if($racc_id){
            $racc = get_post($racc_id);
            if($racc->post_status == 'publish'){
                $sfida_html .= '<a href="' . get_permalink($racc_id). '">Vedi</a>';
            } elseif($racc->post_status == 'draft'){
                $sfida_html .= '"<a href="'. get_edit_post_link($racc_id).'">Modifica</a>';
            } else {
                $sfida_html .= "In revisione";
            }
        }
        $sfida_html .= '</td>';
        array_push($printout, $sfida_html);
        $c++;
    }

    echo "<span style=\"text-align:right;\">Hai ". $c ." sfide a cui sei iscritto</span><br>";
    echo "<table id=\"le-mie-sfide\">";
    echo "<thead><tr><th>Sfida</th><th>Rivolta a</th>";
    echo "<th>Tipo di sfida</th>";
    echo "<th>Stato</th><th>Racconto</th></tr><thead>\n";
    echo "<tbody>\n";
    foreach ($printout as $key => $value) {
        echo "<tr>";
        echo $value;
        echo "</tr>";
    }
    echo "</tbody>\n";
    echo "<tfoot><tr><th>Sfida</th><th>Rivolta a</th>";
    echo "<th>Tipo di sfida</th>";
    echo "<th>Stato</th><th>Racconto</th></tr><tfoot>\n";
    echo "</table>";
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($){
            $('#le-mie-sfide').DataTable();
        });
    </script>
<?php

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

// add_action( 'admin_enqueue_scripts', 'add_custom_style' );
