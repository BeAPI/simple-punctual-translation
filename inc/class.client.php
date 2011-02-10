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
		// CPT, Taxo
		add_action( 'init', array(&$this, 'Register_CPT'), 1 );
		
		// Rewrite
		$current_options = get_option( SPTRANS_OPTIONS_NAME );
		if ( $current_options['rewrite'] == 'rewrite' )
			add_action( 'generate_rewrite_rules', array(&$this, 'createRewriteRules'), 99 );
			
		// WP_Query
		add_action( 'parse_query', array(&$this, 'parseQuery') );
		add_filter( 'query_vars', array(&$this, 'addQueryVar') );
		
		// Admin messages
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
	
	/**
	 * Depending settings of plugin, clone all rules for prefix with language slug.
	 *
	 * @param object $wp_rewrite 
	 * @return void
	 * @author Amaury Balmer
	 */
	function createRewriteRules( $wp_rewrite ) {
		$base_rules = $wp_rewrite->rules;
		foreach( get_terms( 'language', array('hide_empty' => true) ) as $term ) {
			$new_rules = array();
			
			// Prefix with term slug
			foreach( $base_rules as $key => $value ) {
				$key = $term->slug . '/' . $key;
				
				$new_rules[$key] = $value . '&lang=' . $term->slug;
			}
			
			// Merge with WP rules
			$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		}
	}
	
	/**
	 * Add query word "lang"
	 *
	 * @param array $wpvar 
	 * @return array
	 * @author Amaury Balmer
	 */
	function addQueryVar( $wpvar ) {
		$wpvar[] = 'lang';
		return $wpvar;
	}
	
	/**
	 * Analyse query for detect if lang var isset or not.
	 *
	 * @param object $query 
	 * @return void
	 * @author Amaury Balmer
	 */
	function parseQuery( $query ) {
		$query->is_translation = false;
		
		if ( isset($query->query_vars['lang']) && $query->is_singular == true ) {
			$language = get_term_by( 'slug', $query->query_vars['lang'], 'language' );
			if ( $language == false ) {
				wp_redirect( remove_query_arg( array('lang'), stripslashes( $_SERVER['REQUEST_URI'] ) ) ); // TODO: manage case with rewriting method
				exit();
			}
			
			$query->is_translation = true;
			
			$current_options = get_option( SPTRANS_OPTIONS_NAME );
			if ( $current_options['mode'] == 'auto' )
				add_filter('the_posts', array(&$this, 'translateQueryPosts'), 10, 2 );
		}
	}
	
	/**
	 * Translate objects content in array, do that after main query SQL.
	 *
	 * @param array $objects 
	 * @return array
	 * @author Amaury Balmer
	 */
	function translateQueryPosts( $objects, $query ) {
		remove_filter('the_posts', array(&$this, 'translateQueryPosts'), 10, 2 );
		
		foreach( $objects as $object ) {
			$translation = $this->getTranslateObject( $object->ID, $query->query_vars['lang'], 'object' );
			if ( $translation == false )
				continue;
				
			$object = $this->translateObject( $object, $translation );
		}
		
		return $objects;
	}
	
	/**
	 * Translate some fields from original
	 *
	 * @param object $original 
	 * @param object $translation 
	 * @return object
	 * @author Amaury Balmer
	 */
	function translateObject( $original, $translation ) {
		$translated = $original;
		
		$translated->post_title 	= $translation->post_title;
		$translated->post_content 	= $translation->post_content;
		$translated->post_excerpt 	= $translation->post_excerpt;
		
		return apply_filters( 'translate_object', $translated, $original, $translation );
	}
	
	/**
	 * Get the object translated for a specific object, precise lang.
	 *
	 * @param string $parent_id 
	 * @param string $language 
	 * @param string $fields 
	 * @return void
	 * @author Amaury Balmer
	 */
	function getTranslateObject( $parent_id = 0, $language = '', $fields = 'object' ) {
		global $wpdb;
		
		// Get language term_id
		$language = get_term_by( 'slug', $language, 'language' );
		if ( $language == false )
			return false;
			
		// Get object_id translated
		$object_id = $wpdb->get_var("SELECT tr.object_id 
			FROM $wpdb->term_relationships AS tr 
			INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
			INNER JOIN $wpdb->posts AS p ON tr.object_id = p.ID 
			WHERE tt.taxonomy = 'language'
			AND tt.term_id = {$language->term_id}
			AND p.post_parent = {$parent_id}
			LIMIT 1");
			
		if ( $object_id == false )
			return false;
			
		if ( $fields == 'object' )
			return get_post( $object_id );
		
		return $object_id;
	}
	
	/**
	 * Get the object translateds for a specific object
	 *
	 * @param string $parent_id 
	 * @param string $fields 
	 * @return void
	 * @author Amaury Balmer
	 */
	function getTranslateObjects( $parent_id = 0, $fields = 'objects' ) {
		global $wpdb;
		
		// Choose data to get
		switch($fields) {
			case 'terms_objects' :
				$fields = 't.*';
				break;
			case 'objects' :
				$fields = 'p.*, t.*';
				break;
			default :
			case 'ids' :
				$fields = 'p.ID, t.term_id';
				break;
		}
		
		$objects = $wpdb->get_results("SELECT {$fields}
			FROM $wpdb->term_relationships AS tr 
			INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
			INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id 
			INNER JOIN $wpdb->posts AS p ON tr.object_id = p.ID 
			WHERE tt.taxonomy = 'language'
			AND p.post_parent = {$parent_id}");
			
		return $objects;
	}
}
?>