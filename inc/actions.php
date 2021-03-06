<?php



/**
 * Hide content editor for post types defined in settings
 */
add_action( 'admin_init', 'dsi_hide_editor' );

function dsi_hide_editor() {
    global $pagenow;
    if ( $pagenow == "post.php" ) {
        // Get the Post ID.
        if(isset($_GET['post']))
            $post_id = $_GET['post'];
        else if(isset($_POST['post_ID']))
            $post_id = $_POST['post_ID'];

        if ( ! isset( $post_id ) ) {
            return;
        }

        // Get the name of the Page Template file.
        $template_file = get_post_meta( $post_id, '_wp_page_template', true );

        if ( $template_file == 'page-templates/la-scuola.php' ) { // edit the template name
            remove_post_type_support( 'page', 'editor' );
        }

        if ( $template_file == 'page-templates/notizie.php' ) { // edit the template name
            remove_post_type_support( 'page', 'editor' );
        }

        if ( $template_file == 'page-templates/servizi.php' ) { // edit the template name
            remove_post_type_support( 'page', 'editor' );
        }

        if ( $template_file == 'page-templates/didattica.php' ) { // edit the template name
            remove_post_type_support( 'page', 'editor' );
        }

        if ( $template_file == 'page-templates/persone.php' ) { // edit the template name
            remove_post_type_support( 'page', 'editor' );
        }

        if ( $template_file == 'page-templates/numeri.php' ) { // edit the template name
            //  remove_post_type_support( 'page', 'editor' );
        }
        if ( $template_file == 'page-templates/storia.php' ) { // edit the template name
            //  remove_post_type_support( 'page', 'editor' );
        }
    }
}

/**
 * Add css admin style
 */

function dsi_admin_css_load() {
    wp_enqueue_style( 'style-admin-css', get_stylesheet_directory_uri() . '/inc/admin-css/style-admin.css' );
}

add_action( 'admin_enqueue_scripts', 'dsi_admin_css_load' );


/**
 * filter for search
 */
function dsi_search_filters( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_search ) {
        $allowed_types = array( "any", "school", "news", "education", "service" );
        if ( isset( $_GET["type"] ) && in_array( $_GET["type"], $allowed_types ) ) {
            $type = $_GET["type"];
            $post_types = dsi_get_post_types_grouped( $type );
            $query->set( 'post_type', $post_types );

        }

        if ( isset( $_GET["post_types"] ) ) {
            $query->set( 'post_type', $_GET["post_types"] );

        }
        if ( isset( $_GET["post_terms"] ) ) {
            $query->set( 'category__in', $_GET["post_terms"]);
        }

        // associazione tra types e post_type

    }
}

add_action( 'pre_get_posts', 'dsi_search_filters' );

/**
 * customize excerpt
 * @param $length
 *
 * @return int
 */
function dsi_excerpt_length( $length ) {
    return 36;
}
add_filter( 'excerpt_length', 'dsi_excerpt_length', 999 );


/**
 * filter for events
 *  controllo le query sugli eventi e le modifico per estrarre gli eventi futuri
 */
function dsi_eventi_filters( $query ) {

    if ( ! is_admin() && $query->is_main_query() && is_post_type_archive("evento") ) {
        if(isset($_GET["date"]) && ($_GET["date"] != "")){
            $date = strtotime($_GET["date"]);
            $date_begin = strtotime($_GET["date"] ." 00:00:01");
            $date_end = strtotime($_GET["date"] ." 23:59:59");
            $query->set( 'meta_query', array(
                array(
                    'key' => '_dsi_evento_timestamp_inizio',
                    'value' => $date_end,
                    'compare' => '<',
                    'type' => 'numeric'
                ),
                array(
                    'key' => '_dsi_evento_timestamp_fine',
                    'value' => $date_begin,
                    'compare' => '>',
                    'type' => 'numeric'
                )
            ));

        }else if(isset($_GET["archive"]) && ($_GET["archive"] == "true")){
            $query->set('meta_key', '_dsi_evento_timestamp_inizio' );
            $query->set('orderby', array('meta_value' => 'DESC', 'date' => 'DESC'));
            $query->set( 'meta_query', array(
                array(
                    'key' => '_dsi_evento_timestamp_inizio'
                ),
                array(
                    'key' => '_dsi_evento_timestamp_fine',
                    'value' => time(),
                    'compare' => '<=',
                    'type' => 'numeric'
                )
            ));
        }else{
            $query->set('meta_key', '_dsi_evento_timestamp_inizio' );
            $query->set('orderby', array('meta_value' => 'DESC', 'date' => 'DESC'));
            $query->set( 'meta_query', array(
                array(
                    'key' => '_dsi_evento_timestamp_inizio'
                ),
                array(
                    'key' => '_dsi_evento_timestamp_fine',
                    'value' => time(),
                    'compare' => '>=',
                    'type' => 'numeric'
                )
            ));

        }
    }else if(! is_admin() && ! $query->is_main_query()){

        if ($query->get("post_type") == "evento"){

            $query->set('meta_key', '_dsi_evento_timestamp_inizio' );
            $query->set('orderby', array('meta_value' => 'DESC', 'date' => 'DESC'));
            $query->set( 'meta_query', array(
                array(
                    'key' => '_dsi_evento_timestamp_inizio'
                ),
                array(
                    'key' => '_dsi_evento_timestamp_fine',
                    'value' => time(),
                    'compare' => '>=',
                    'type' => 'numeric'
                )
            ));
        }
    }

}

add_action( 'pre_get_posts', 'dsi_eventi_filters' );



/**
 * filter for schede progetti
 *  controllo le query sulòle schede progetto e le modifico per estrarre quelle dell'anno in corso
 */
function dsi_schede_progetti_filters( $query ) {

    if ( ! is_admin() && $query->is_main_query() && is_post_type_archive("scheda_progetto") ) {
        if(isset($_GET["archive"]) && ($_GET["archive"] == "true")){

            $query->set( 'meta_query', array(
                'relation' => 'OR',
                array(
                    'key' => '_dsi_scheda_progetto_anno_scolastico',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_dsi_scheda_progetto_anno_scolastico',
                    'value' => dsi_get_current_anno_scolastico(),
                    'compare' => '!=',
                    'type' => 'numeric'
                )
            ));
        }else{

            $query->set( 'meta_query', array(
                array(
                    'key' => '_dsi_scheda_progetto_anno_scolastico',
                    'value' => dsi_get_current_anno_scolastico(),
                    'compare' => '=',
                    'type' => 'numeric'
                )
            ));

        }
    }else if(! is_admin() && ! $query->is_main_query()){
        if ($query->get("post_type") == "evento"){
            $query->set('meta_key', '_dsi_evento_timestamp_inizio' );
            $query->set('orderby', array('meta_value' => 'DESC', 'date' => 'DESC'));
            $query->set( 'meta_query', array(
                array(
                    'key' => '_dsi_evento_timestamp_inizio'
                ),
                array(
                    'key' => '_dsi_evento_timestamp_fine',
                    'value' => time(),
                    'compare' => '>=',
                    'type' => 'numeric'
                )
            ));
        }
    }
}

add_action( 'pre_get_posts', 'dsi_schede_progetti_filters' );

/**
 * Personalizzo archive title
 */
add_filter( 'get_the_archive_title', function ($title) {
global $wp_query;
    if ( is_category() ) {
        $title = __("Argomento", "design_scuole_italia").": ".single_cat_title( '', false );
    } elseif ( is_tag() ) {
        $title = single_tag_title( '', false );
    } elseif ( is_tax("tipologia-articolo") ) {

        $title = single_term_title('', false);
        /*    if($title == "Articoli"){
                $title = "Presentazione";
            }*/
    } elseif ( is_tax("tipologia-documento") ) {
        $title = single_term_title('', false);
    } elseif ( is_tax("percorsi-di-studio") ) {
        //  $title = post_type_archive_title('', false)." ";
        //$title .= single_term_title('', false);
        $title = single_term_title('', false);
    } elseif ( is_post_type_archive("servizio") ) {
        $title = __("Tutti i servizi", "design_scuole_italia");
    }  elseif ( is_tax("tipologia-servizio") ) {
        // $title = __("Servizi per ", "design_scuole_italia").": ".single_term_title('', false);
        $title = single_term_title('', false);
    }elseif ( is_tax("tipologia-circolare") ) {
        // $title = __("Servizi per ", "design_scuole_italia").": ".single_term_title('', false);
        $title = single_term_title('', false);
    }elseif ( is_tax("tipologia-luogo") ) {
        // $title = __("Servizi per ", "design_scuole_italia").": ".single_term_title('', false);
        $title = single_term_title('', false);
    } elseif ( is_post_type_archive("luogo") ) {
        $title = __("I luoghi della scuola", "design_scuole_italia");
    } elseif ( is_post_type_archive("struttura") ) {
        $title = __("Organizzazione", "design_scuole_italia");
    } elseif ( is_post_type_archive("evento") ) {
        $title = __("Eventi", "design_scuole_italia");
        if(isset($_GET["date"]) && $_GET["date"] != ""){
            $title .= " del ".$_GET["date"];
        }
        if(isset($_GET["archive"]) && $_GET["archive"] == "true"){
            $title .= " archiviati  ";
        }
            } elseif ( is_post_type_archive() ) {
        $title = post_type_archive_title('', false);
    }

    return $title;

});


/**
 * fix plugin amministrazione aperta
 */

function dsi_ammap_getJs(){
    wp_deregister_script('ammap_functions');
    wp_dequeue_script('ammap_functions');

    wp_register_script( 'ammap_functions', plugins_url('amministrazione-aperta/js/ammap.js'));
    wp_enqueue_script( 'ammap_functions');
}
add_filter('admin_footer', 'dsi_ammap_getJs', 100);


/**
 * Fix plugin bandi
 */

add_action( 'after_setup_theme', 'dsi_replace_bandi_shortcode' );

function dsi_replace_bandi_shortcode() {
    remove_shortcode( 'avcp' );
    remove_shortcode( 'anac' );
    remove_shortcode( 'gare' );


    add_shortcode( 'avcp', 'dsi_bandi_shortcode' );
    add_shortcode( 'anac', 'dsi_bandi_shortcode' );
    add_shortcode( 'gare', 'dsi_bandi_shortcode' );

}

function dsi_bandi_shortcode($atts) {
    extract(shortcode_atts(array('anno' => 'all'), $atts));

    ob_start();
    require get_template_directory() . '/inc/tablegen.php';
    $atshortcode = ob_get_clean();
    return $atshortcode;
}

/** add responsive class to table **/

function dsi_bootstrap_responsive_table( $content ) {
    $content = str_replace( ['<table', '</table>'], ['<div class="table-responsive"><table class="table  table-striped table-bordered table-hover" ', '</table></div>'], $content );

    return $content;
}
add_filter( 'the_content', 'dsi_bootstrap_responsive_table' );




add_filter( 'gettext', 'dsi_change_externale_string', 999, 3 );

function dsi_change_externale_string( $translated, $untranslated, $domain ) {

    if ( is_admin() ) {

        // traduco il plugin degli appuntamenti
        switch ( $translated) {
            case 'Appointments' :
                $translated = 'Appuntamenti';
                break;

            case 'Locations' :
                $translated = 'Luoghi';
                break;
            case 'Location' :
                $translated = 'Luogo';
                break;

            case 'Services' :
            case 'Service' :

                $translated = 'Attività';
                break;

            case 'Workers' :
            case 'Worker' :
                $translated = 'Referenti';
                break;

            case 'Connections' :
                $translated = 'Relazioni';
                break;

            case 'Customize' :
                $translated = 'Personalizza';
                break;

            case 'Customer' :
                $translated = 'Prenotato da';
                break;

            case 'Descrtiption' :
            case 'Description' :
                $translated = 'Descrizione';
                break;

            case 'Add New Appointment' :
                $translated = 'Aggiungi una prenotazione';
                break;


            case 'Add New Connection' :
                $translated = 'Aggiungi una relazione';
                break;

            case 'Bulk Add New Connections' :
                $translated = 'Aggiungi relazione di gruppo';
                break;

            case 'Add New Location' :
                $translated = 'Aggiungi un luogo';
                break;

            case 'Add New Worker' :
                $translated = 'Aggiungi un referente';
                break;

            case 'Address' :
                $translated = 'Indirizzo';
                break;

            case 'Actions' :
                $translated = 'Azioni';
                break;

        }

    }

    return $translated;

}



/**
 * Admin header customization
 *
 */
function dsi_admin_bar_customize_header() {
    global $wp_admin_bar;

    if ( current_user_can( 'read' ) ) {
        $about_url = self_admin_url( 'about.php' );
    } elseif ( is_multisite() ) {
        $about_url = get_dashboard_url( get_current_user_id(), 'about.php' );
    } else {
        $about_url = false;
    }

    $wp_admin_bar->add_menu(
        array(
            'id'     => 'design-scuole',
            'title' => '<span class="dsi-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="30" viewBox="0 0 92 74"><g fill="#06C"><path d="M31.799 71.9V15.7h15.1V72h-15.1zM91.099 28.5h-13.8v23.1c0 2.3.1 3.8.2 4.8.1.9.5 1.7 1.2 2.4s1.8 1 3.3 1l8.6-.2.7 12c-5 1.1-8.9 1.7-11.5 1.7-6.8 0-11.4-1.5-13.8-4.6-2.5-3-3.7-8.6-3.7-16.8V0h15.1v15.6h13.8v12.9zM9.099 32.8c-2.6 0-4.8-.9-6.5-2.7s-2.6-4-2.6-6.6.9-4.8 2.5-6.6c1.7-1.8 3.9-2.6 6.5-2.6s4.8.9 6.5 2.7 2.5 4 2.5 6.7-.8 4.8-2.5 6.6c-1.6 1.6-3.7 2.5-6.4 2.5z"></path></g></svg></span><span class="screen-reader-text">' . __( 'About Design Scuole' ) . '</span>',
            'href'   => '#'
        )
    );

    $wp_admin_bar->add_group(
        array(
            'parent' => 'design-scuole',
            'id'     => 'design-scuole-external',
            'meta'   => array(
                'class' => 'ab-sub-secondary',
            ),
        )
    );

    $wp_admin_bar->add_menu(
        array(
            'parent' => 'design-scuole-external',
            'id'     => 'dsi-about-design',
            'title'  => __( 'About Design Scuole' ),
            'href'   => 'https://designers.italia.it/progetti/siti-web-scuole/',
            'meta'  => array( 'target' => '_blank')
        )
    );


    $wp_admin_bar->add_menu(
        array(
            'parent' => 'design-scuole',
            'id'     => 'dsi-about-wp',
            'title'  => __( 'About WordPress' ),
            'href'   => $about_url,
        )
    );


    $wp_admin_bar->add_menu(
        array(
            'parent' => 'design-scuole',
            'id'     => 'dsi-github',
            'title'  => __( 'Design su GitHub' ),
            'href'   => "https://github.com/italia/design-scuole-wordpress-theme",
            'meta'  => array( 'target' => '_blank')
        )
    );


    if(current_user_can("manage_options")){
        $wp_admin_bar->add_menu(
            array(
                'id'     => 'design-scuole-conf',
                'title' => __( '<div class="dashicons-before dashicons-admin-tools" style="float:left; padding-top: 6px; padding-right:4px;"> </div>Configurazione', "design_scuole_italia" ),
                'href'   => admin_url("admin.php?page=homepage")
            )
        );
    }


}
add_action( 'admin_bar_menu', 'dsi_admin_bar_customize_header', -10 );

add_action( 'wp_before_admin_bar_render', 'dsi_admin_bar_before_customize_header', -10 );

function dsi_admin_bar_before_customize_header(){
    global $wp_admin_bar;

    $wp_admin_bar->remove_menu("wp-logo");
}