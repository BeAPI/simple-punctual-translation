<?php

/**
 * Client class
 *
 * @package LifeTranslation
 * @author  Amaury Balmer
 */
class PunctualTranslation_Client {
	/**
	 * Constructor, register hooks
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// CPT, Taxo
		add_action( 'init', [ $this, 'Register_CPT' ], 1 );

		// Rewrite
		$current_options = get_option( SPTRANS_OPTIONS_NAME );
		if ( 'rewrite' === $current_options['rewrite'] ) {
			add_action( 'generate_rewrite_rules', [ $this, 'createRewriteRules' ], 99 );
		}

		// WP_Query
		add_action( 'parse_query', [ $this, 'parseQuery' ] );
		add_filter( 'query_vars', [ $this, 'addQueryVar' ] );

		// Admin messages
		add_filter( 'post_updated_messages', [ $this, 'updateMessages' ] );

		// Auto add languages ?
		add_action( 'template_redirect', [ $this, 'determineAutoLanguages' ] );
		add_action( 'widgets_init', [ $this, 'widgets_init' ] );
	}

	/**
	 * Register the widget
	 *
	 *
	 */
	public function widgets_init() {
		register_widget( 'PunctualTranslation_Widget' );
	}

	/**
	 * This method will register post type and post status
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function Register_CPT() {
		register_post_type(
			SPTRANS_CPT,
			[
				'labels'              => [
					'name'               => _x( 'Translations', 'punctual-translation post type general name', 'punctual-translation' ),
					'singular_name'      => _x( 'Translation', 'punctual-translation post type singular name', 'punctual-translation' ),
					'add_new'            => _x( 'Add New', 'punctual-translation', 'punctual-translation' ),
					'add_new_item'       => __( 'Add New Translation', 'punctual-translation' ),
					'edit_item'          => __( 'Edit Translation', 'punctual-translation' ),
					'new_item'           => __( 'New Translation', 'punctual-translation' ),
					'view_item'          => __( 'View Translation', 'punctual-translation' ),
					'search_items'       => __( 'Search Translations', 'punctual-translation' ),
					'not_found'          => __( 'No Translations found', 'punctual-translation' ),
					'not_found_in_trash' => __( 'No Translations found in Trash', 'punctual-translation' ),
					'parent_item_colon'  => __( 'Parent Translation:', 'punctual-translation' ),
				],
				'description'         => 'Translations for Simple Punctual Translation',
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'public'              => false,
				'capability_type'     => SPTRANS_CPT,
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => SPTRANS_CPT,
				'supports'            => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions' ],
				'taxonomies'          => [],
				'show_ui'             => true,
				'menu_position'       => 100,
				'has_archive'         => false,
				'can_export'          => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => true,
			]
		);

		register_taxonomy(
			SPTRANS_TAXO,
			SPTRANS_CPT,
			[
				'hierarchical'          => false,
				'update_count_callback' => '_update_post_term_count',
				'labels'                => [
					'name'                       => __( 'Languages', 'punctual-translation' ),
					'singular_name'              => __( 'Language', 'punctual-translation' ),
					'search_items'               => __( 'Search Languages', 'punctual-translation' ),
					'popular_items'              => null,
					'all_items'                  => __( 'All Languages', 'punctual-translation' ),
					'edit_item'                  => __( 'Edit Language', 'punctual-translation' ),
					'update_item'                => __( 'Update Language', 'punctual-translation' ),
					'add_new_item'               => __( 'Add New Language', 'punctual-translation' ),
					'new_item_name'              => __( 'New Language Name', 'punctual-translation' ),
					'separate_items_with_commas' => null,
					'add_or_remove_items'        => null,
					'choose_from_most_used'      => null,
				],
				'query_var'             => SPTRANS_TAXO,
				'rewrite'               => false,
				'public'                => false,
				'show_ui'               => true,
			]
		);
	}

	/**
	 * Display correct message when user update object
	 *
	 * @param array $messages
	 *
	 * @return array
	 * @author Amaury Balmer
	 */
	public function updateMessages( array $messages ) {
		global $post, $post_ID;

		$current_terms     = wp_get_object_terms( $post_ID, SPTRANS_TAXO, [ 'fields' => 'slug' ] );
		$current_term_slug = current( $current_terms );

		$messages[ SPTRANS_CPT ] = [
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( __( 'Translation updated. <a href="%s">View translation</a>', 'punctual-translation' ), esc_url( get_translation_permalink( $post_ID, $current_term_slug ) ) ),
			2  => __( 'Custom field updated.', 'punctual-translation' ),
			3  => __( 'Custom field deleted.', 'punctual-translation' ),
			4  => __( 'Translation updated.', 'punctual-translation' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Translation restored to revision from %s', 'punctual-translation' ), wp_translation_revision_title( (int) $_GET['revision'], false ) ) : false, /* translators: %s: date and time of the revision */
			6  => sprintf( __( 'Translation published. <a href="%s">View translation</a>', 'punctual-translation' ), esc_url( get_translation_permalink( $post_ID, $current_term_slug ) ) ),
			7  => __( 'Translation saved.', 'punctual-translation' ),
			8  => sprintf( __( 'Translation submitted. <a target="_blank" href="%s">Preview translation</a>', 'punctual-translation' ), esc_url( add_query_arg( 'preview', 'true', get_translation_permalink( $post_ID, $current_term_slug ) ) ) ),
			9  => sprintf(
				__( 'Translation scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview translation</a>', 'punctual-translation' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'j F Y @ G:i' ), strtotime( $post->post_date ) ),
				esc_url( get_translation_permalink( $post_ID, $current_term_slug ) )
			),
			10 => sprintf( __( 'Translation draft updated. <a target="_blank" href="%s">Preview translation</a>', 'punctual-translation' ), esc_url( add_query_arg( 'preview', 'true', get_translation_permalink( $post_ID, $current_term_slug ) ) ) ),
		];

		return $messages;
	}

	/**
	 * Depending settings of plugin, clone all rules for prefix with language slug.
	 *
	 * @param object $wp_rewrite
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function createRewriteRules( $wp_rewrite ) {
		$base_rules = $wp_rewrite->rules;
		foreach ( get_terms( SPTRANS_TAXO, [ 'hide_empty' => true ] ) as $term ) {
			$new_rules = [];

			// Prefix with term slug
			foreach ( $base_rules as $key => $value ) {
				$key = $term->slug . '/' . $key;

				$new_rules[ $key ] = $value . '&lang=' . $term->slug;
			}

			// Merge with WP rules
			$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		}
	}

	/**
	 * Add query word "lang"
	 *
	 * @param array $wpvar
	 *
	 * @return array
	 * @author Amaury Balmer
	 */
	public function addQueryVar( $wpvar ) {
		$wpvar[] = SPTRANS_QVAR;

		return $wpvar;
	}

	/**
	 * Analyse query for detect if lang var isset or not.
	 *
	 * @param object $query
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function parseQuery( $query ) {

		if ( is_admin() || defined( 'REST_REQUEST' ) || defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		$query->is_translation = false;

		if ( isset( $query->query_vars[ SPTRANS_QVAR ] ) && true === $query->is_singular ) {
			$language = get_term_by( 'slug', $query->query_vars[ SPTRANS_QVAR ], SPTRANS_TAXO );
			if ( false === $language ) {
				wp_redirect( remove_query_arg( [ SPTRANS_QVAR ], stripslashes( $_SERVER['REQUEST_URI'] ) ) ); // TODO: manage case with rewriting method
				exit();
			}

			$query->is_translation = true;

			$current_options = get_option( SPTRANS_OPTIONS_NAME );
			if ( 'auto' === $current_options['mode'] ) {
				add_filter( 'the_posts', [ &$this, 'translateQueryPosts' ], 10, 2 );
			}
		}
	}

	/**
	 * Translate objects content in array, do that after main query SQL.
	 *
	 * @param array $objects
	 *
	 * @return array
	 * @author Amaury Balmer
	 */
	public function translateQueryPosts( $objects, $query ) {
		remove_filter( 'the_posts', [ $this, 'translateQueryPosts' ], 10 );

		foreach ( $objects as $object ) {
			$translation = $this->getTranslateObject( $object->ID, $query->query_vars[ SPTRANS_QVAR ], 'object' );
			if ( false === $translation ) {
				continue;
			}

			$object = $this->translateObject( $object, $translation );
		}

		return $objects;
	}

	/**
	 * Translate some fields from original
	 *
	 * @param object $original
	 * @param object $translation
	 *
	 * @return object
	 * @author Amaury Balmer
	 */
	public function translateObject( $original, $translation ) {
		$translated = $original;

		$translated->post_title   = $translation->post_title;
		$translated->post_content = $translation->post_content;
		$translated->post_excerpt = $translation->post_excerpt;

		return apply_filters( 'translate_object', $translated, $original, $translation );
	}

	/**
	 * Get the object translated for a specific object, precise lang.
	 *
	 * @param string $parent_id
	 * @param string $language
	 * @param string $fields
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function getTranslateObject( $parent_id = 0, $language = '', $fields = 'object' ) {
		global $wpdb;

		// Get language term_id
		$language = get_term_by( 'slug', $language, SPTRANS_TAXO );
		if ( false === $language ) {
			return false;
		}

		// Get object_id translated
		$object_id = $wpdb->get_var(
			"SELECT tr.object_id 
			FROM $wpdb->term_relationships AS tr 
			INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
			INNER JOIN $wpdb->posts AS p ON tr.object_id = p.ID 
			WHERE tt.taxonomy = '" . SPTRANS_TAXO . "'
			AND tt.term_id = {$language->term_id}
			AND p.post_parent = {$parent_id}
			AND p.post_type = '" . SPTRANS_CPT . "'
			LIMIT 1"
		);

		if ( false === $object_id ) {
			return false;
		}

		if ( 'object' === $fields ) {
			return get_post( $object_id );
		}

		return $object_id;
	}

	/**
	 * Get the object translateds for a specific object
	 * TODO: publish status for all context ? admin preview ?
	 *
	 * @param string $parent_id
	 * @param string $fields
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function getTranslateObjects( $parent_id = 0, $fields = 'objects' ) {
		global $wpdb;

		// Choose data to get
		switch ( $fields ) {
			case 'terms_objects':
				$fields = 't.*';
				break;
			case 'objects':
				$fields = 'p.*, t.*';
				break;
			default:
			case 'ids':
				$fields = 'p.ID, t.term_id';
				break;
		}

		$objects = $wpdb->get_results(
			"SELECT {$fields}
			FROM $wpdb->term_relationships AS tr 
			INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
			INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id 
			INNER JOIN $wpdb->posts AS p ON tr.object_id = p.ID 
			WHERE tt.taxonomy = '" . SPTRANS_TAXO . "'
			AND p.post_parent = {$parent_id}
			AND p.post_type = '" . SPTRANS_CPT . "'
			AND p.post_status = 'publish'"
		);

		return $objects;
	}

	/**
	 * Need to move register hook of auto display languages to template_redirect for test is_feed()
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function determineAutoLanguages() {
		$current_options = get_option( SPTRANS_OPTIONS_NAME );

		// Auto add languages ?
		if ( isset( $current_options['auto'] ) && ! empty( $current_options['auto'] ) && is_array( $current_options['auto'] ) ) {
			if ( in_array( 'content', $current_options['auto'], true ) && ! is_feed() ) {
				add_filter( 'the_content', [ &$this, 'autoDisplayLanguages' ] );
			}

			if ( in_array( 'excerpt', $current_options['auto'], true ) && ! is_feed() ) {
				add_filter( 'the_excerpt', [ &$this, 'autoDisplayLanguages' ] );
			}

			if ( in_array( 'feed', $current_options['auto'], true ) && is_feed() ) {
				add_filter( 'the_content_feed', [ &$this, 'autoDisplayLanguages' ] );
				add_filter( 'the_excerpt_rss', [ &$this, 'autoDisplayLanguages' ] );
			}
		}
	}

	/**
	 * Auto add available languages at the end of post content, excerpt.
	 *
	 * @param string $content
	 *
	 * @return string
	 * @author Amaury Balmer
	 */
	public function autoDisplayLanguages( $content = '' ) {
		global $post;

		$html = get_the_post_available_languages( '<p>' . __( 'Also available in : ', 'punctual-translation' ), ', ', '</p>' );
		if ( empty( $html ) ) {
			return $content;
		}

		return $content . '<div class="post_available_languages">' . $html . '</div>';
	}
}
