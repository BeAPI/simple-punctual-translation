<?php
/*
Plugin Name: Simple Punctual Translation
Version: 1.0.2
Plugin URI: http://www.beapi.fr
Description: A small plugin for WordPress that allow to translate any post type in another languages. This plugin is not usable out of the box. It's require some changes on your theme.
Author: BeAPI
Author URI: http://www.beapi.fr

Copyright 2010 - BeAPI Team (technique@beapi.fr)

TODO :
	Admin
	Widget
	Javascript
*/

define( 'SPTRANS_VERSION', 		'1.0.2' );
define( 'SPTRANS_FOLDER', 		'punctual-translation' );
define( 'SPTRANS_OPTIONS_NAME', 'punctual-translation' ); // Option name for save settings

define( 'SPTRANS_QVAR', 		'lang' );
define( 'SPTRANS_CPT', 			'translation' );
define( 'SPTRANS_TAXO', 		'_language' );

define( 'SPTRANS_URL', 			plugins_url('', __FILE__) );
define( 'SPTRANS_DIR', 			dirname(__FILE__) );

require( SPTRANS_DIR . '/inc/functions.plugin.php');
require( SPTRANS_DIR . '/inc/functions.template.php');
require( SPTRANS_DIR . '/inc/class.client.php');
require( SPTRANS_DIR . '/inc/class.widget.php');

// Activation, uninstall
register_activation_hook( __FILE__, 'PunctualTranslation_Install'   );
register_uninstall_hook ( __FILE__, 'PunctualTranslation_Uninstall' );

// Init LifeDeal
function PunctualTranslation_Init() {
	global $punctual_translation;

	// Load translations
	load_plugin_textdomain ( 'punctual-translation', false, basename(rtrim(dirname(__FILE__), '/')) . '/languages' );
	
	// Load client
	$punctual_translation['client'] = new PunctualTranslation_Client();
	
	// Admin
	if ( is_admin() ) {
		require( SPTRANS_DIR . '/inc/class.admin.php' );
		require( SPTRANS_DIR . '/inc/class.admin.settings.php' );
		$punctual_translation['admin'] = new PunctualTranslation_Admin();
		$punctual_translation['admin-settings'] = new PunctualTranslation_Admin_Settings();
	}
	
	// Widget
	add_action( 'widgets_init', create_function('', 'return register_widget("PunctualTranslation_Widget");') );
}
add_action( 'plugins_loaded', 'PunctualTranslation_Init' );
?>
