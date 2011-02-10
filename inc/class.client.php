<?php
/**
 * Client class
 *
 * @package LifeTranslation
 * @author Amaury Balmer
 */
class PunctualTranslation_Client {
	/**
	 * Constructor, register hooks
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function PunctualTranslation_Client() {
		add_action( 'init', array(&$this, 'Register_CPT'), 1 );
		
		add_filter( 'post_updated_messages', array(&$this, 'updateMessages') );
		add_action( 'contextual_help', array(&$this, 'helpText'), 10, 3 );
	}
	
	/**
	 * This method will register post type and post status
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function Register_CPT() {
		register_post_type( 'translation', array(
			'labels' 				=> array(
				'name' => _x('Translations', 'punctual-translation post type general name', 'punctual-translation'),
				'singular_name' => _x('Translation', 'punctual-translation post type singular name', 'punctual-translation'),
				'add_new' => _x('Add New', 'punctual-translation', 'punctual-translation'),
				'add_new_item' => __('Add New Translation', 'punctual-translation'),
				'edit_item' => __('Edit Translation', 'punctual-translation'),
				'new_item' => __('New Translation', 'punctual-translation'),
				'view_item' => __('View Translation', 'punctual-translation'),
				'search_items' => __('Search Translations', 'punctual-translation'),
				'not_found' => __('No Translations found', 'punctual-translation'),
				'not_found_in_trash' => __('No Translations found in Trash', 'punctual-translation'),
				'parent_item_colon' => __('Parent Translation:', 'punctual-translation')
			),
			'description' 			=> 'Translations for Simple Punctual Translation',
			'publicly_queryable' 	=> false,
			'exclude_from_search' 	=> true,
			'public' 				=> false,
			'capability_type' 		=> array( 'translation', 'translations' ),
			//'capabilities' 		=> array(),
			'map_meta_cap'			=> true,
			'hierarchical' 			=> false,
			'rewrite' 				=> false,
			'query_var' 			=> 'translation',
			'supports' 				=> array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions' ),
			'taxonomies' 			=> array(),
			'show_ui' 				=> true,
			'menu_position' 		=> 100,
			'has_archive'			=> false,
			//'menu_icon' 			=> $custom_type['menu_icon'],
			'can_export' 			=> true,
			'show_in_nav_menus'		=> false
		) );
		
		register_taxonomy( 'language', 'translation', array(
			'hierarchical' => false,
			'labels' => array(
				'name' => __( 'Languages' ),
				'singular_name' => __( 'Language' ),
				'search_items' => __( 'Search Languages' ),
				'popular_items' => null,
				'all_items' => __( 'All Languages' ),
				'edit_item' => __( 'Edit Language' ),
				'update_item' => __( 'Update Language' ),
				'add_new_item' => __( 'Add New Language' ),
				'new_item_name' => __( 'New Language Name' ),
				'separate_items_with_commas' => null,
				'add_or_remove_items' => null,
				'choose_from_most_used' => null,
			),
			'query_var' => 'language',
			'rewrite' 	=> false,
			'public' 	=> false,
			'show_ui' 	=> true
		) );
	}
	
	/**
	 * Display correct message when user update object
	 * TODO: change preview and view link for translated version of a content
	 *
	 * @param array $messages 
	 * @return array
	 * @author Amaury Balmer
	 */
	function updateMessages( $messages ) {
		global $post, $post_ID;
		
		$messages['translation'] = array(
			 0 => '', // Unused. Messages start at index 1.
			 1 => sprintf( __('Translation updated. <a href="%s">View translation</a>', 'punctual-translation'), esc_url( get_permalink($post_ID) ) ),
			 2 => __('Custom field updated.', 'punctual-translation'),
			 3 => __('Custom field deleted.', 'punctual-translation'),
			 4 => __('Translation updated.', 'punctual-translation'),
			 5 => isset($_GET['revision']) ? sprintf( __('Translation restored to revision from %s', 'punctual-translation'), wp_translation_revision_title( (int) $_GET['revision'], false ) ) : false, /* translators: %s: date and time of the revision */
			 6 => sprintf( __('Translation published. <a href="%s">View translation</a>', 'punctual-translation'), esc_url( get_permalink($post_ID) ) ),
			 7 => __('Translation saved.', 'punctual-translation'),
			 8 => sprintf( __('Translation submitted. <a target="_blank" href="%s">Preview translation</a>', 'punctual-translation'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			 9 => sprintf( __('Translation scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview translation</a>', 'punctual-translation'),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'j F Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Translation draft updated. <a target="_blank" href="%s">Preview translation</a>', 'punctual-translation'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		
		return $messages;
	}

	/**
	 * Display contextual help for life Translation
	 *
	 * @param string $contextual_help 
	 * @param string $screen_id 
	 * @param string $screen 
	 * @return void
	 * @author Amaury Balmer
	 */
	function helpText($contextual_help, $screen_id, $screen) { 
		if ( 'translation' == $screen->id ) {
			$contextual_help = '<p>' . __('Things to remember when adding or editing a translation.', 'punctual-translation') . '</p>';
		} elseif ( 'edit-translation' == $screen->id ) {
			$contextual_help = '<p>' . __('This is the help screen displaying the table of translations.', 'punctual-translation') . '</p>' ;
		}
		
		return $contextual_help;
	}
}
?>