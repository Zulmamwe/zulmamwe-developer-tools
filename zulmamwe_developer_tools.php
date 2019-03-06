<?php
/**
* Plugin Name: Zulmamwe Developer Tools
* Description: Some developer tools to make life easier
* Version: 1.0
* Author: John Zuxer
* Author URI: https://www.upwork.com/o/profiles/users/_~01f35acec4c4e5f366/
* License: GPLv2 or later
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Zulmamwe_Developer_Tools();
class Zulmamwe_Developer_Tools{
	
	public function __construct(){
		require 'includes/class-easy_admin_settings.php';
		require 'includes/class-easy_ajax_handler.php';
		
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	}
	
	public function load_plugin_textdomain(){
		load_plugin_textdomain( 'zulmamwe', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}
	
}