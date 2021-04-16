<?php

class PunctualTranslation_Admin {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Fix WordPress process
		add_action( 'post_updated', [ $this, 'fixPostparentQuickEdit' ], 10, 3 );

		// Style, Javascript
		add_action( 'admin_enqueue_scripts', [ $this, 'addRessources' ] );

		// Metadatas
		add_action( 'add_meta_boxes', [ $this, 'registerMetaBox' ], 999 );
		add_action( 'save_post', [ $this, 'saveDatasMetaBoxes' ], 10 );

		// Listing
		add_filter( 'manage_posts_columns', [ $this, 'addColumns' ], 10, 2 );
		add_action( 'manage_posts_custom_column', [ $this, 'addColumnValue' ], 10, 2 );

		add_filter( 'post_row_actions', [ $this, 'extendActionsList' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'extendActionsList' ], 10, 2 );

		// Ajax
		add_action( 'wp_ajax_' . 'load_original_content', [ $this, 'ajaxBuildSelect' ] );
		add_action( 'wp_ajax_' . 'test_once_translation', [ $this, 'ajaxTestUnicity' ] );

		// Rewriting
		add_action( 'created_' . SPTRANS_TAXO, [ $this, 'resetRewritingRules' ] );
		add_action( 'edited_' . SPTRANS_TAXO, [ $this, 'resetRewritingRules' ] );
	}

	/**
	 * Keep the current post_parent when user update translation with quick edit.
	 *
	 * @param integer $post_ID
	 * @param object  $post_after
	 * @param object  $post_before
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function fixPostparentQuickEdit( $post_ID, $post_after, $post_before ) {
		if ( SPTRANS_CPT !== $post_before->post_type ) {
			return;
		}

		if ( 0 !== $post_before->post_parent && $post_after->post_parent !== $post_before->post_parent && ! isset( $_POST['parent_id'] ) ) {
			$wpdb->update( $wpdb->posts, [ 'post_parent' => (int) $post_before->post_parent ], [ 'ID' => $post_ID ] );
		}

	}

	/**
	 * Register JS/CSS for correct post type
	 *
	 * @param string $hook_suffix
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function addRessources( $hook_suffix = '' ) {
		global $post;

		if (
			( 'post-new.php' === $hook_suffix && isset( $_GET['post_type'] ) && SPTRANS_CPT === $_GET['post_type'] ) ||
			( 'post.php' === $hook_suffix && isset( $_GET['post'] ) && SPTRANS_CPT === $post->post_type ) ||
			( 'edit.php' === $hook_suffix && SPTRANS_CPT === $_GET['post_type'] )
		) {
			wp_enqueue_style( 'admin-translation', SPTRANS_URL . '/ressources/admin.css', [], SPTRANS_VERSION, 'all' );
			wp_enqueue_script( 'admin-translation', SPTRANS_URL . '/ressources/admin.js', [ 'jquery' ], SPTRANS_VERSION );
			wp_localize_script(
				'admin-translation',
				'translationL10n',
				[
					'successText' => __( 'This translation is unique, fine...', 'punctual-translation' ),
					'errorText'   => __( 'Duplicate translation detected !', 'punctual-translation' ),
				]
			);
		}
	}

	/**
	 * Save datas of translation databox
	 *
	 * @param integer $object_id
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function saveDatasMetaBoxes( $object_id = 0 ) {
		if ( isset( $_POST['_meta_translation'] ) && 'true' === $_POST['_meta_translation'] ) {
			return;
		}

		if ( ! isset( $_POST['_meta_original_translation'] ) || 'true' !== $_POST['_meta_original_translation'] ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( 'page' === $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $object_id ) ) {
				return;
			}
		} elseif ( ! current_user_can( 'edit_post', $object_id ) ) {
			return;
		}

		if ( isset( $_POST['_meta_language'] ) && 'true' === $_POST['_meta_language'] ) {
			if ( isset( $_POST['language_translation'] ) && ! empty( $_POST['language_translation'] ) ) {
				wp_set_object_terms( $object_id, [ (int) $_POST['language_translation'] ], SPTRANS_TAXO, false );
			} else {
				wp_delete_object_term_relationships( $object_id, [ SPTRANS_TAXO ] );
			}
		}
	}

	/**
	 * Register metabox
	 *
	 * @param string $post_type
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function registerMetaBox( $post_type ) {
		if ( ! current_user_can( 'edit_' . SPTRANS_CPT . 's' ) ) {
			return;
		}

		$current_options = get_option( SPTRANS_OPTIONS_NAME );
		if ( SPTRANS_CPT !== $post_type && true === in_array( $post_type, (array) $current_options['cpt'] ) ) {
			add_meta_box( $post_type . '-translation', __( 'Translations', 'punctual-translation' ), [ $this, 'MetaboxTranslation' ], $post_type, 'side', 'core' );
		} elseif ( SPTRANS_CPT === $post_type ) {
			remove_meta_box( SPTRANS_TAXO . 'div', $post_type, 'side' );
			remove_meta_box( 'tagsdiv-' . SPTRANS_TAXO, $post_type, 'side' );

			add_meta_box( $post_type . '-language', __( 'Language', 'punctual-translation' ), [ $this, 'MetaboxLanguageTaxo' ], $post_type, 'side', 'core' );
			add_meta_box( $post_type . '-translation', __( 'Original content', 'punctual-translation' ), [ $this, 'MetaboxOriginalContent' ], $post_type, 'side', 'core' );
		}
	}

	/**
	 * List languages available
	 *
	 * @param object $post
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function MetaboxLanguageTaxo( $post ) {
		$current_terms   = wp_get_object_terms( $post->ID, SPTRANS_TAXO, [ 'fields' => 'ids' ] );
		$current_term_id = current( $current_terms );

		echo '<select name="language_translation" id="language_translation" class="widefat">' . "\n";
		foreach ( get_terms( SPTRANS_TAXO, [ 'hide_empty' => false ] ) as $term ) {
			echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( $term->term_id, $current_term_id, false ) . '>' . esc_html( $term->name ) . '</option>' . "\n";
		}
		echo '</select>' . "\n";
		echo '<div id="language_duplicate_ajax"></div>' . "\n";

		echo '<input type="hidden" name="_meta_language" value="true" />';
	}

	/**
	 * List translation available and form for create new translation
	 *
	 * @param object $post
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function MetaboxTranslation( $post ) {
		$q_translations = new WP_Query(
			[
				'post_type'   => SPTRANS_CPT,
				'post_status' => 'any',
				'post_parent' => $post->ID,
			]
		);
		if ( $q_translations->have_posts() ) {
			echo '<h4 style="margin:0;">' . __( 'Existings translations :', 'punctual-translation' ) . '</h4>' . "\n";
			echo '<ul class="current_translations ul-square">' . "\n";
			foreach ( $q_translations->posts as $translation ) {
				$language = get_the_terms( $translation->ID, SPTRANS_TAXO );
				if ( false === $language ) {
					continue;
				}
				$language = current( $language ); // Take only the first...

				echo '<li><a href="' . get_edit_post_link( $translation->ID ) . '">' . sprintf( __( '%1$s - %2$s - %3$s', 'punctual-translation' ), esc_html( $language->name ), esc_html( $translation->ID ), esc_html( $translation->post_title ) ) . '</a></li>' . "\n";
			}
			echo '</ul>' . "\n";
		}

		echo '<p><a href="' . admin_url( 'post-new.php?post_type=translation&post_parent=' . $post->ID ) . '">' . __( 'Translate this content', 'punctual-translation' ) . '</a></p>' . "\n";
		echo '<input type="hidden" name="_meta_translation" value="true" />';
	}

	/**
	 * Display a select list for choose the content translated
	 *
	 * @param object $post
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function MetaboxOriginalContent( $post ) {
		$current_parent    = false;
		$current_parent_id = 0;
		if ( 0 === (int) $post->post_parent && isset( $_GET['post_parent'] ) && 0 !== (int) $_GET['post_parent'] ) {
			$current_parent    = get_post( $_GET['post_parent'] );
			$current_parent_id = $current_parent->ID;
		} elseif ( 0 !== (int) $post->post_parent ) {
			$current_parent    = get_post( $post->post_parent );
			$current_parent_id = $current_parent->ID;
		}
		?>
		<div id="ajax-filter-original" class="hide-if-no-js">
			<p>
				<label for="original_post_type_js"><?php _e( 'Post types', 'punctual-translation' ); ?></label>
				<br/>
				<select name="original_post_type_js" id="original_post_type_js" class="widefat">
					<?php
					$current_options = get_option( SPTRANS_OPTIONS_NAME );
					foreach ( (array) $current_options['cpt'] as $cpt ) {
						$cpt = get_post_type_object( $cpt );

						$selected = '';
						if ( false !== $current_parent ) {
							$selected = selected( $cpt->name, $current_parent->post_type, false );
						}

						echo '<option value="' . esc_attr( $cpt->name ) . '" ' . $selected . '>' . esc_html( $cpt->labels->name ) . '</option>' . "\n";
					}
					?>
				</select>
			</p>
			<p>
				<label for="post_parent_js"><?php _e( 'Original', 'punctual-translation' ); ?></label>
				<br/>
				<span id="ajax-destination-select-original"></span>
				<select name="post_parent_js" id="post_parent_js" class="widefat"> AJAX Values </select>
			</p>
		</div>

		<div id="original-content" class="hide-if-js">
			<label for="parent_id"><?php _e( 'Original', 'punctual-translation' ); ?></label>
			<br/>
			<select name="parent_id" id="parent_id" class="widefat">
				<option value="-"><?php _e( 'Please choose a content', 'punctual-translation' ); ?></option>
				<?php
				// Current selected value
				if ( false !== $current_parent ) {
					echo '<option selected="selected" value="' . esc_attr( $current_parent->ID ) . '">' . esc_html( $current_parent->ID ) . ' - ' . esc_html( $current_parent->post_title ) . '</option>' . "\n";
				}

				$current_options = get_option( SPTRANS_OPTIONS_NAME );

				// List all other content
				$q_all_content = new WP_Query(
					[
						'post_type'      => ! empty( $current_options['cpt'] ) ? $current_options['cpt'] : 'any',
						'post_status'    => 'any',
						'posts_per_page' => 2000,
						'no_found_rows'  => true,
						'post__not_in'   => [ $current_parent_id ],
						'orderby'        => 'title',
						'order'          => 'ASC',
					]
				);
				if ( $q_all_content->have_posts() ) {
					foreach ( $q_all_content->posts as $object ) {
						echo '<option value="' . esc_attr( $object->ID ) . '">' . esc_html( $object->ID ) . ' - ' . esc_html( $object->post_title ) . '</option>' . "\n";
					}
				}
				?>
			</select>
		</div>

		<input type="hidden" name="_meta_original_translation" value="true"/>
		<?php
	}

	/**
	 * Add columns for post type
	 *
	 * @param array  $defaults
	 * @param string $post_type
	 *
	 * @return array
	 * @author Amaury Balmer
	 */
	public function addColumns( $defaults, $post_type ) {
		if ( SPTRANS_CPT === $post_type && current_user_can( 'edit_' . SPTRANS_CPT . 's' ) ) {
			$defaults['original-translation'] = __( 'Original', 'punctual-translation' );
			$defaults['_taxo-language']       = __( 'Language', 'punctual-translation' );
		}

		return $defaults;
	}

	/**
	 * Display value of each custom column for translation
	 *
	 * @param string  $column_name
	 * @param integer $object_id
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function addColumnValue( $column_name, $object_id ) {
		switch ( $column_name ) {
			case 'original-translation':
				$translation = get_post( $object_id );
				echo '<a href="' . get_edit_post_link( $translation->post_parent ) . '">' . get_the_title( $translation->post_parent ) . '</a>';
				break;
			case '_taxo-language':
				$translation = get_post( $object_id );
				$terms       = get_the_terms( $object_id, SPTRANS_TAXO );
				if ( ! empty( $terms ) ) {
					$output = [];
					foreach ( $terms as $term ) {
						$output[] = "<a href='edit-tags.php?action=edit&taxonomy=" . SPTRANS_TAXO . '&post_type=' . $translation->post_type . "&tag_ID=$term->term_id'> " . esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, SPTRANS_TAXO, 'display' ) ) . '</a>';
					}
					echo join( ', ', $output );
				} else {
					//_e('No term.','simple-case');
				}
				break;
		}
	}

	/**
	 * Add features for this post deal
	 *
	 * @param array  $actions
	 * @param object $object
	 *
	 * @return array
	 * @author Amaury Balmer
	 */
	public function extendActionsList( $actions, $object ) {
		$current_options = get_option( SPTRANS_OPTIONS_NAME );
		if ( $object->post_type != SPTRANS_CPT && current_user_can( 'edit_' . SPTRANS_CPT . 's' ) && in_array( $object->post_type, (array) $current_options['cpt'] ) == true ) {
			$actions['translate'] = '<a href="' . admin_url( 'post-new.php?post_type=' . SPTRANS_CPT . '&post_parent=' . $object->ID ) . '">' . __( 'Translate', 'punctual-translation' ) . '</a>' . "\n";
		}

		return $actions;
	}

	/**
	 * Build HTML for Ajax Request
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function ajaxBuildSelect() {
		if ( ! isset( $_REQUEST['post_type'] ) ) {
			status_header( '404' );
			die();
		}
		$q_all_content = new WP_Query(
			[
				'post_type'      => $_REQUEST['post_type'],
				'post_status'    => 'any',
				'posts_per_page' => 1000,
				'no_found_rows'  => true,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);
		if ( $q_all_content->have_posts() ) {
			foreach ( $q_all_content->posts as $object ) {
				echo '<option value="' . esc_attr( $object->ID ) . '" ' . selected( $object->ID, (int) $_REQUEST['current_value'], false ) . '>' . esc_html( $object->ID ) . ' - ' . esc_html( $object->post_title ) . '</option>' . "\n";
			}
		}
	}

	/**
	 * Test if the translation exist already for a content, exclude current ID...
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function ajaxTestUnicity() {
		if ( ! isset( $_REQUEST['parent_id'], $_REQUEST['current_id'], $_REQUEST['current_value'] ) ) {
			status_header( '404' );
			die();
		}
		$test_flag = false;

		// Prevent current post without parent in translation dropdown
		if ( null === $_REQUEST['parent_id'] || 0 === absint( $_REQUEST['parent_id'] ) ) {
			die( 'ok' );
		}

		// Get translations for original content
		$q_translations = new WP_Query(
			[
				'post_type'    => SPTRANS_CPT,
				'post_status'  => 'any',
				'post_parent'  => (int) $_REQUEST['parent_id'],
				'post__not_in' => [ (int) $_REQUEST['current_id'] ],
			]
		);
		if ( $q_translations->have_posts() ) {
			foreach ( $q_translations->posts as $translation ) { // Test language of theses translations
				$language = get_the_terms( $translation->ID, SPTRANS_TAXO );
				if ( false === $language ) {
					continue;
				}
				$language = current( $language ); // Take only the first...

				if ( (int) $_REQUEST['current_value'] === $language->term_id ) {
					$test_flag = true;
					break;
				}
			}
		}

		if ( true === $test_flag ) {
			die( 'ko' );
		}

		die( 'ok' );
	}

	/**
	 * Flush rewriting rules when a term language is insert or update for build correct URLs.
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function resetRewritingRules() {
		flush_rewrite_rules( false );
	}
}
