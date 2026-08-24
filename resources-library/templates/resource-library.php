<?php
/**
 * Template Name: Resource Library
 */

defined( 'ABSPATH' ) || exit;

get_header();
echo RL_Render::render_library();
get_footer();
