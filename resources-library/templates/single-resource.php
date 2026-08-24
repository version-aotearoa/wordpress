<?php
defined( 'ABSPATH' ) || exit;

$post = get_queried_object();

get_header();
echo RL_Render::single_page_html( $post, RL_Page_Template::library_url() );
get_footer();
