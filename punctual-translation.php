<?php
/*
Plugin Name: Simple Punctual Translation
Version: 0.2
Plugin URI: http://www.beapi.fr
Description: A small plugin for WordPress that allow to translate any post type in another languages. This plugin is not usable out of the box. It's require some changes on your theme.
Author: Be API
Author URI: http://www.beapi.fr

Copyright 2010 - BeAPI Team (technique@beapi.fr)

TODO :
	Client
		Checkbox pour afficher ou non automatiquement dans the_content les langues disponibles
			Dropdown list pour choix entre affichage dropdown ou list
		
		Création d’une fonction d’affichage des langues disponibles
			- Scan les posts dont le post parent est égal au post actuel OU post parent actuel et de type traduction.
			- Affiche “Ce contenu est également disponible dans les lanugues suivantes : XXX - YYYY - ZZZ”
			- Paramètre facultatif pour recevoir un tableau PHP avec les URL des pages filles/soeurs

		Règle de réécriture
			Si slug de langue dans URL, vérifier si un post parent existe avec la metakey lang qui vaut le slug passé dans l’URL
			Gestion avec et sans règle de réécriture (/en ou ?lang=en)
		
	Admin		
		Suppression d'un contenu :
			Supprimer automatiquement tous les articles fils de type traduction
		
		Suppresion d'une langue
			-> Implique de supprimer toutes les traductions liées ?
				=> Case à cocher ?
		
	Widget
	
	Javascript
		Ajax lors de la création d'une traduction pour savoir si une traduction existe déjà
		Ajax, ou plutot Modal Windows lorsqu'on a affiché une traduction, ouvrir une popup pour savoir si on veut automatiquement charger les articles dans la langue de son choix.
*/

define( 'SPTRANS_VERSION', '1.0' );
define( 'SPTRANS_FOLDER', 'punctual-translation' );
define( 'SPTRANS_OPTIONS_NAME', 'punctual-translation' ); // Option name for save settings
define( 'SPTRANS_URL', plugins_url('', __FILE__) );
define( 'SPTRANS_DIR', dirname(__FILE__) );

require( SPTRANS_DIR . '/inc/functions.plugin.php');
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
		$punctual_translation['admin'] = new PunctualTranslation_Admin();
	}
	
	// Widget
	add_action( 'widgets_init', create_function('', 'return register_widget("PunctualTranslation_Widget");') );
}
add_action( 'plugins_loaded', 'PunctualTranslation_Init' );
?>
