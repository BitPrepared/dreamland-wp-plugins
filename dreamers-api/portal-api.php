<?php

class Portal_API
{

    public function register_routes($routes)
    {
        $routes['/portal/sfide'] = array(
            array(array($this, 'get_sfide'), WP_JSON_Server::READABLE),
            array(array($this, 'new_iscrizione'), WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON),
        );

        $routes['/portal/profilo'] = array(
            array(array($this, 'get_profilo'), WP_JSON_Server::READABLE)
        );

        $routes['/portal/pk'] = array(
            array(array($this, 'create_profilo'), WP_JSON_Server::READABLE)
        );

        $routes['/portal/cs'] = array(
            array(array($this, 'complete_sfida'), WP_JSON_Server::READABLE)
        );

        // $routes['/portal/ara/(?P<id>\d+)'] = array(
        //   array( array( $this, 'get_post'), WP_JSON_Server::READABLE ),
        //   array( array( $this, 'edit_post'), WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
        //   array( array( $this, 'delete_post'), WP_JSON_Server::DELETABLE ),
        // );
        // Add more custom routes here
        return $routes;
    }

    public function complete_sfida($filter = array(), $context = 'view', $type = null, $page = 1)
    {
        $res = array();
        // is_user_logged_in() && // SCOPRIRE PERCHE' NON VA
        if ( isset($_SESSION['portal']) ) {
            if ( isset($_SESSION['portal']['request']) ) {
                $sfidaid = $_SESSION['portal']['request']['sfidaid'];
                
                $wordpress = $_SESSION['wordpress'];
                $user_id = $wordpress['user_id'];

                // $user = wp_get_current_user();
                // $user_id = $user->ID;
                
                add_user_meta($user_id, '_iscrizioni', $sfidaid, false); //possibile array

                _log('iscrizione completata per '.$user_id.' su sfida '.$sfidaid);
                unset($_SESSION['portal']['request']);
                $url_base = '/portal/#/home/reg/ok';
                $query = 'Iscrizione%20alla%20sfida%20completata%20con%20successo.';
                wp_redirect("$url_base?msg=$query");
                exit;
            }
        } else {
            $res['error'] = 'utente non valido';
        }   
        
    }

    public function create_profilo($filter = array(), $context = 'view', $type = null, $page = 1)
    {
        $res = array();
        if ( isset($_SESSION['portal']) ) {
            if ( isset($_SESSION['portal']['request']) ) {
                $request = $_SESSION['portal']['request'];

                $userdata = array(
                    'user_login'  =>  $request['username'],
                    'user_pass'   =>  $request['password'],
                    'first_name'   =>  $request['first_name'],
                    'last_name'   =>  $request['last_name'],
                    'user_email'   =>  $request['email'],
                    'nickname'   =>  $request['nickname']
                );

                $user_id = wp_insert_user( $userdata ) ;

                if ( is_wp_error($user_id) ) {

                    _log('errore creazione utente '.$user_id->get_error_message());

                    unset($_SESSION['portal']['request']);
                    $url_base = '/portal/#/home/reg/ko'; // /portal/#/home/reg/ko?msg=oo
                    $query = "Contattare%20l%27assistenza".'%20a%20iscrizioni.rtd%40agesci.it%20:%20'.urlencode($user_id->get_error_message(). '');
                    wp_redirect("$url_base?msg=$query");

                    exit;

                } else {

                    _log('Creato '.$user_id);

                    $user = new WP_User( $user_id );

                    if ( false === $user ) {
                        $res['error'] = 'User non trovato..';
                    } else {
                        inserted_user_dreamers($user , $request, false);
                        unset($_SESSION['portal']['request']);
                        $url_base = '/portal/#/home/reg/ok';
                        $query = 'Registrazione%20completata%20con%20successo.%20Riceverai%20una%20mail%20con%20le%20nuove%20credenziali.';
                        wp_redirect("$url_base?msg=$query");
                        exit;
                    }

                }
            }
        } else {
            $res['error'] = 'utente non valido';
        }

        return $res;
    }

    public function get_profilo($filter = array(), $context = 'view', $type = null, $page = 1)
    {

        $user = wp_get_current_user();
        $user_id = $user->ID;
        if ($user_id < 1) {
            if (isset($_SESSION)) {
                $user_id = $_SESSION['wordpress']['user_id'];
            } else {
                $user_id = get_current_user_id();
            }
        }

        $res = array();

        if ($user_id > 0) {
            $res['id'] = $user_id;
            $res['meta'] = get_user_meta($user_id);
        } else {
            $res['error'] = 'utente non valido';
        }

        return $res;
    }


    public function get_sfide($filter = array(), $context = 'view', $type = null, $page = 1)
    {
        global $regioni;

        $args = array(
            'posts_per_page' => -1,
            'offset' => 0,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'include' => '',
            'exclude' => '',
            'meta_key' => '',
            'meta_value' => '',
            'post_type' => 'sfida_event',
            'post_mime_type' => '',
            'post_parent' => '',
            'post_status' => 'publish',
            'suppress_filters' => true
        );

        $posts_array = get_posts($args);

        $c = 0;
        $struct = array();
        foreach ($posts_array as $k => $p) {
            // if(!is_sfida_alive($p)) { continue; }
            if (!is_sfida_for_me($p)) {
                continue;
            }

            $limit = get_limit_sfida($p, $regioni);
            if (empty($limit)) continue;

            $meta = get_post_meta($p->ID);

            $icons = get_icons_for_sfida($p);
            $struct[$c] = array(
                'titolo' => $p->post_title,
                'permalink' => get_permalink($p->ID),
                'limiti' => $limit,
                'icone' => $icons,
                'regione' => $meta['_regione'],
                'zona' => $meta['_zona']
            );
            $c++;
        }

        return $struct;
    }

    // WP_JSON_Request $request
    public function new_iscrizione($request)
    {
        if (!current_user_can('insert_sfide_review')) {
            return new WP_Error('json_cannot_create', __('Sorry, you are not allowed to access on this challenge.'), array('status' => 403));
        }

        $user = wp_get_current_user();
        $user_id = $user->ID;
        if ($user_id < 1) {
            if (isset($_SESSION)) {
                $user_id = $_SESSION['wordpress']['user_id'];
            } else {
                $user_id = get_current_user_id();
            }
        }

        $post_id = $_SESSION['sfide']['sfida_id'];

        update_user_meta($user_id, 'punteggio_attuale', $_SESSION['sfide']['punteggio_attuale']);
        update_user_meta($user_id, 'numero_componenti', $_SESSION['sfide']['numero_componenti']);
        update_user_meta($user_id, 'numero_specialita', $_SESSION['sfide']['numero_specialita']);
        update_user_meta($user_id, 'numero_brevetti', $_SESSION['sfide']['numero_brevetti']);

        update_user_meta($user_id, 'sfida_corrente', $post_id);

        $response = json_ensure_response( array() );
        $response->set_status(201);
        $response->header('Location', $_SESSION['sfide']['sfida_url']);

        return $response;
    }

}