<?php
/**
 * Function call by WordPress when plugin is actived, options + role
 *
 * @return void
 * @author Amaury Balmer
 */
function PunctualTranslation_Install() {
	$current_options = array();
	$current_options['cpt'] = array('post',' page');
	$current_options['mode'] = 'manual';
	$current_options['rewrite'] = 'classic';
	$current_options['original_lang_name'] = __('English', 'punctual-translation');
	$current_options['auto'] = array();
	add_option( SPTRANS_OPTIONS_NAME, $current_options );

	// Remove old role if needed to reset the caps
	remove_role( 'translator' );
	
	// Create the new role
	add_role( 'translator', __('Translator', 'punctual-translation') );
	
	// Get the role and add the caps
	$role = &get_role( 'translator' );
	$role->add_cap( 'upload_files' );
	$role->add_cap( 'read' );
	
	// Attachements
	$role->remove_cap( 'edit_others_attachment' );
	$role->remove_cap( 'read_others_attachment' );
	$role->remove_cap( 'delete_others_attachment' );
	$role->remove_cap( 'delete_attachment' );
	
	// Add caps translation
	PunctualTranslation_Translation_Cap( $role );
	
	// Administrator
	$role = &get_role( 'administrator' );
	PunctualTranslation_Translation_Cap( $role );
	
	// Editor
	$role = &get_role( 'editor' );
	PunctualTranslation_Translation_Cap( $role );
}

/**
 * Add caps translation for a role
 *
 * @param object $role 
 * @return void
 * @author Amaury Balmer
 */
function PunctualTranslation_Translation_Cap( &$role ) {
	$role->add_cap( 'edit_translation' );
	$role->add_cap( 'read_translation' );
	$role->add_cap( 'delete_translation' );
	
	$role->add_cap( 'edit_translations' );
	$role->add_cap( 'edit_others_translations' );
	$role->add_cap( 'publish_translations' );
	$role->add_cap( 'read_private_translations' );
	
	$role->add_cap( 'delete_translations' );
	$role->add_cap( 'delete_private_translations' );
	$role->add_cap( 'delete_published_translations' );
	$role->add_cap( 'delete_others_translations' );
	$role->add_cap( 'edit_private_translations' );
	$role->add_cap( 'edit_published_translations' );
}

/**
 * Function call by WordPress when plugin is uninstalled
 * Todo: remove specific caps ?
 *
 * @return void
 * @author Amaury Balmer
 */
function PunctualTranslation_Uninstall() {
	delete_option( SPTRANS_OPTIONS_NAME );
	remove_role( 'translator' );
}
?>