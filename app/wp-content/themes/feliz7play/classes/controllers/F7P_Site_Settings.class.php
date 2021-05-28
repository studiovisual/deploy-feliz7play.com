<?php 


if( function_exists('acf_add_options_page') ) {
	acf_add_options_sub_page(array(
		'page_title' 	=> 'Site Settings',
		'menu_title'	=> 'SIte Settings',
		'parent_slug'	=> 'options-general.php',
        'post_id'       => 'site_settings'
	));
}

add_action( 'rest_api_init', 'rest_site_settings' );
function rest_site_settings() {
    register_rest_route( 'wp/v2', '/site-settings', array(
        'methods' => 'GET',
        'callback' => 'get_site_settings',
    ) );
}

function get_site_settings(){

    $variable['redes_sociais'] = get_field('redes_sociais', 'site_settings');
    $variable['menus']['footer'] = get_field('footer', 'site_settings');
    $variable['menus']['sites'] = get_field('sites', 'site_settings');
    $variable['options']['logo'] = get_field('logo_footer', 'site_settings');
    $variable['options']['copyright'] = get_field('copyright', 'site_settings');
    $variable['options']['imgErro404'] = get_field('imagem_erro_404', 'site_settings');

    
    return new WP_REST_Response( $variable , 200 );
}
