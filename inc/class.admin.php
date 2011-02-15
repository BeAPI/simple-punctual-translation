<?php
class PunctualTranslation_Admin {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function PunctualTranslation_Admin() {
		// Fix WordPress process
		add_action( 'post_updated', array(&$this, 'fixPostparentQuickEdit'), 10, 3 );
		
		// Style, Javascript
		add_action( 'admin_enqueue_scripts', array(&$this, 'addRessources') );
		
		// Metadatas
		add_action( 'add_meta_boxes', array(&$this, 'registerMetaBox'), 999 );
		add_action( 'save_post', array(&$this, 'saveDatasMetaBoxes'), 10, 2 );
		
		// Listing
		add_filter( 'manage_posts_columns', array( &$this, 'addColumns'), 10 ,2 );
		add_action( 'manage_posts_custom_column', array(&$this, 'addColumnValue' ), 10, 2 );
		
		add_filter( 'post_row_actions', array(&$this, 'extendActionsList'), 10, 2 );
		add_filter( 'page_row_actions', array(&$this, 'extendActionsList'), 10, 2 );
		
		// Ajax
		add_action( 'wp_ajax_' . 'load_original_content', array(&$this, 'ajaxBuildSelect' ) );
		add_action( 'wp_ajax_' . 'test_once_translation', array(&$this, 'ajaxTestUnicity' ) );

		// Rewriting
		add_action( 'created_' . SPTRANS_TAXO, array(&$this, 'resetRewritingRules') );
		add_action( 'edited_'  . SPTRANS_TAXO, array(&$this, 'resetRewritingRules') );
	}
	
	/**
	 * Keep the current post_parent when user update translation with quick edit.
	 *
	 * @param integer $post_ID 
	 * @param object $post_after 
	 * @param object $post_before 
	 * @return void
	 * @author Amaury Balmer
	 */
	function fixPostparentQuickEdit( $post_ID, $post_after, $post_before ) {
		global $wpdb;
		
		if ( $post_before->post_type == SPTRANS_CPT ) {
			if ( $post_before->post_parent != 0 && $post_after->post_parent != $post_before->post_parent && !isset($_POST['parent_id']) ) {
				$wpdb->update( $wpdb->posts, array('post_parent' => (int) $post_before->post_parent), array('ID' => $post_ID) );
			}
		}
	}
	
	/**
	 * Register JS/CSS for correct post type
	 *
	 * @param string $hook_suffix 
	 * @return void
	 * @author Amaury Balmer
	 */
	function addRessources( $hook_suffix = '' ) {
		global $post;
		
		if ( 
			( $hook_suffix == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == SPTRANS_CPT ) || 
			( $hook_suffix == 'post.php' && isset($_GET['post']) && $post->post_type == SPTRANS_CPT ) ||
			( $hook_suffix == 'edit.php' && $_GET['post_type'] == SPTRANS_CPT ) 
		) {
			wp_enqueue_style  ( 'admin-translation', SPTRANS_URL.'/ressources/admin.css', array(), SPTRANS_VERSION, 'all' );
			wp_enqueue_script ( 'admin-translation', SPTRANS_URL.'/ressources/admin.js', array('jquery'), SPTRANS_VERSION );
			wp_localize_script( 'admin-translation', 'translationL10n', array(
				'successText' => __('This translation is unique, fine...', 'punctual-translation'),
				'errorText' => __('Duplicate translation detected !', 'punctual-translation')
			) );
		}
	}
	
	/**
	 * Save datas of translation databox
	 *
	 * @param integer $object_id 
	 * @param object $object 
	 * @return void
	 * @author Amaury Balmer
	 */
	function saveDatasMetaBoxes( $object_id = 0, $object = null ) {
		global $wpdb;
		
		if ( !isset($object) || $object == null ) {
			$object = get_post( $object_id );
		}
		
		if ( isset($_POST['_meta_translation']) && $_POST['_meta_translation'] == 'true' ) {
			// Nothing actually
		}
		
		if ( isset($_POST['_meta_original_translation']) && $_POST['_meta_original_translation'] == 'true' ) {
			// Nothing actually too.
		}
		
		if( isset($_POST['_meta_language']) && $_POST['_meta_language'] == 'true' ) {
			if ( isset($_POST['language_translation']) && !empty($_POST['language_translation']) ) {
				wp_set_object_terms($object_id, array( (int) $_POST['language_translation'] ), SPTRANS_TAXO, false);
			} else {
				wp_delete_object_term_relationships( $object_id, array(SPTRANS_TAXO) );
			}
		}
	}
	
	/**
	 * Register metabox
	 *
	 * @param string $post_type 
	 * @return void
	 * @author Amaury Balmer
	 */
	function registerMetaBox( $post_type ) {
		if ( !current_user_can('edit_'.SPTRANS_CPT) )
			return false;
			
		$current_options = get_option( SPTRANS_OPTIONS_NAME );
		if ( $post_type != SPTRANS_CPT && in_array($post_type, (array) $current_options['cpt']) == true ) {
			add_meta_box($post_type.'-translation', __('Translations', 'punctual-translation'), array(&$this, 'MetaboxTranslation'), $post_type, 'side', 'core');
		} elseif ( $post_type == SPTRANS_CPT ) {
			remove_meta_box( SPTRANS_TAXO.'div', $post_type, 'side' );
			remove_meta_box( 'tagsdiv-'.SPTRANS_TAXO, $post_type, 'side' );
			
			add_meta_box($post_type.'-language', __('Language', 'punctual-translation'), array(&$this, 'MetaboxLanguageTaxo'), $post_type, 'side', 'core');
			add_meta_box($post_type.'-translation', __('Original content', 'punctual-translation'), array(&$this, 'MetaboxOriginalContent'), $post_type, 'side', 'core');
		}
	}
	
	/**
	 * List languages available
	 *
	 * @param object $post 
	 * @return void
	 * @author Amaury Balmer
	 */
	function MetaboxLanguageTaxo( $post ) {
		$current_terms 		= wp_get_object_terms($post->ID, SPTRANS_TAXO, array('fields' => 'ids'));
		$current_term_id 	= current($current_terms);
		
		echo '<select name="language_translation" id="language_translation" style="width:99%">' . "\n";
			foreach( get_terms( SPTRANS_TAXO, array('hide_empty' => false) ) as $term ) {
				echo '<option value="'.$term->term_id.'" '.selected( $term->term_id, $current_term_id, false ).'>'.$term->name.'</option>' . "\n";
			}
		echo '</select>' . "\n";
		echo '<div id="language_duplicate_ajax"></div>' . "\n";
		
		echo '<input type="hidden" name="_meta_language" value="true" />';
	}
	
	/**
	 * List translation available and form for create new translation
	 *
	 * @param object $post 
	 * @return void
	 * @author Amaury Balmer
	 */
	function MetaboxTranslation( $post ) {
		$q_translations = new WP_Query( array('post_type' => SPTRANS_CPT, 'post_status' => 'any', 'post_parent' => $post->ID) );
		if ( $q_translations->have_posts() ) {
			echo '<h4 style="margin:0;">'.__('Existings translations :', 'punctual-translation').'</h4>' . "\n";
			echo '<ul class="current_translations ul-square">' . "\n";
			foreach( $q_translations->posts as $translation ) {
				$language = get_the_terms( $translation->ID, SPTRANS_TAXO );
				if ( $language == false )
					continue;
				$language = current($language); // Take only the first...
				
				echo '<li><a href="'.get_edit_post_link($translation->ID).'">'.sprintf( __('%1$s - %2$s - %3$s', 'punctual-translation'), $language->name, $translation->ID, $translation->post_title ).'</a></li>' . "\n";
			}
			echo '</ul>' . "\n";
		}
		
		echo '<p><a href="'.admin_url('post-new.php?post_type=translation&post_parent='.$post->ID).'">'.__('Translate this content', 'punctual-translation').'</a></p>' . "\n";
		echo '<input type="hidden" name="_meta_translation" value="true" />';
	}
	
	/**
	 * Display a select list for choose the content translated
	 *
	 * @param object $post 
	 * @return void
	 * @author Amaury Balmer
	 */
	function MetaboxOriginalContent( $post ) {
		$current_parent = false;
		$current_parent_id = 0;
		if ( (int) $post->post_parent == 0 && isset($_GET['post_parent']) && (int) $_GET['post_parent'] != 0 ) {
			$current_parent = get_post( $_GET['post_parent'] );
			$current_parent_id = $current_parent->ID;
		} elseif ( (int) $post->post_parent != 0 ) {
			$current_parent = get_post( $post->post_parent );
			$current_parent_id = $current_parent->ID;
		}
		?>
		<div id="ajax-filter-original" class="hide-if-no-js">
			<p>
				<label for="original_post_type_js"><?php _e('Post types', 'punctual-translation'); ?></label>
				<br />
				<select name="original_post_type_js" id="original_post_type_js" style="width:99%">
					<?php
					$current_options = get_option( SPTRANS_OPTIONS_NAME );
					foreach( (array) $current_options['cpt'] as $cpt ) {
						$cpt = get_post_type_object( $cpt );
						
						$selected = '';
						if ( $current_parent != false )
							$selected = selected( $cpt->name, $current_parent->post_type, false );
							
						echo '<option value="'.$cpt->name.'" '.$selected.'>'.$cpt->labels->name.'</option>' . "\n";
					}
					?>
				</select>
			</p>
			<p>
				<label for="post_parent_js"><?php _e('Original', 'punctual-translation'); ?></label>
				<br />
				<span id="ajax-destination-select-original"></span>
				<select name="post_parent_js" id="post_parent_js" style="width:99%"> AJAX Values </select>
			</p>
		</div>
		
		<div id="original-content" class="hide-if-js">
			<label for="parent_id"><?php _e('Original', 'punctual-translation'); ?></label>
			<br />
			<select name="parent_id" id="parent_id" style="width:99%">
				<option value="-"><?php _e('Please choose a content', 'punctual-translation'); ?></option>
				<?php
				// Current selected value
				if ( $current_parent != false )
					echo '<option selected="selected" value="'.$current_parent->ID.'">'.$current_parent->ID.' - '.$current_parent->post_title.'</option>' . "\n"; 
				
				// List all other content
				$q_all_content = new WP_Query( array('post_type' => 'any', 'post_status' => 'any', 'showposts' => 500, 'post__not_in' => array($current_parent_id)) );
				if ( $q_all_content->have_posts() ) {
					foreach( $q_all_content->posts as $object ) {
						echo '<option value="'.$object->ID.'">'.$object->ID.' - '.$object->post_title.'</option>' . "\n"; 
					}
				}
				?>
			</select>
		</div>
		
		<input type="hidden" name="_meta_original_translation" value="true" />
		<?php
	}
	
	/**
	 * Add columns for post type
	 *
	 * @param array $defaults 
	 * @param string $post_type 
	 * @return array
	 * @author Amaury Balmer
	 */
	function addColumns( $defaults, $post_type ) {
		if ( $post_type == SPTRANS_CPT && current_user_can('edit_'.SPTRANS_CPT) ) {
			$defaults['original-translation'] = __('Original', 'punctual-translation');
			$defaults['_taxo-language'] = __('Language', 'punctual-translation');
		}
		
		return $defaults;
	}
	
	/**
	 * Display value of each custom column for translation
	 *
	 * @param string $column_name 
	 * @param integer $object_id 
	 * @return void
	 * @author Amaury Balmer
	 */
	function addColumnValue( $column_name, $object_id ) {
		switch( $column_name ) {
			case 'original-translation':
				$translation = get_post($object_id);
				echo '<a href="'.get_edit_post_link($translation->post_parent).'">'.get_the_title($translation->post_parent).'</a>';
				break;
			case '_taxo-language':
				$translation = get_post($object_id);
				$terms = get_the_terms($object_id, SPTRANS_TAXO);
				if ( !empty( $terms ) ) {
					$output = array();
					foreach ( $terms as $term ) {
						$output[] = "<a href='edit-tags.php?action=edit&taxonomy=".SPTRANS_TAXO."&post_type=".$translation->post_type."&tag_ID=$term->term_id'> " . esc_html(sanitize_term_field('name', $term->name, $term->term_id, SPTRANS_TAXO, 'display')) . "</a>";
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
	 * @param array $actions 
	 * @param object $object 
	 * @return array
	 * @author Amaury Balmer
	 */
	function extendActionsList( $actions, $object ) {
		$current_options = get_option( SPTRANS_OPTIONS_NAME );
		if ( $object->post_type != SPTRANS_CPT && current_user_can('edit_'.SPTRANS_CPT) && in_array($object->post_type, (array) $current_options['cpt']) == true )
			$actions['translate'] = '<a href="'.admin_url('post-new.php?post_type='.SPTRANS_CPT.'&post_parent='.$object->ID).'">'.__('Translate', 'punctual-translation').'</a>' . "\n";
		return $actions;
	}
	
	/**
	 * Build HTML for Ajax Request
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function ajaxBuildSelect() {
		if ( !isset($_REQUEST['post_type']) ) {
			status_header ('404');
			die();
		} else {
			$q_all_content = new WP_Query( array('post_type' => $_REQUEST['post_type'], 'post_status' => 'any', 'showposts' => 500) );
			if ( $q_all_content->have_posts() ) {
				foreach( $q_all_content->posts as $object ) {
					echo '<option value="'.$object->ID.'" '.selected( $object->ID, (int) $_REQUEST['current_value'], false ).'>'.$object->ID.' - '.$object->post_title.'</option>' . "\n"; 
				}
			}
		}
	}
	
	/**
	 * Test if the translation exist already for a content, exclude current ID...
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function ajaxTestUnicity() {
		if ( !isset($_REQUEST['parent_id']) || !isset($_REQUEST['current_id']) || !isset($_REQUEST['current_value']) ) {
			status_header ('404');
			die();
		} else {
			$test_flag = false;
			
			// Get translations for original content
			$q_translations = new WP_Query( array('post_type' => SPTRANS_CPT, 'post_status' => 'any', 'post_parent' => (int) $_REQUEST['parent_id'], 'post__not_in' => array((int) $_REQUEST['current_id']) ) );
			if ( $q_translations->have_posts() ) {
				foreach( $q_translations->posts as $translation ) { // Test language of theses translations
					$language = get_the_terms( $translation->ID, SPTRANS_TAXO );
					if ( $language == false )
						continue;
					$language = current($language); // Take only the first...
					
					if ( $language->term_id == (int) $_REQUEST['current_value'] ) {
						$test_flag = true;
						break;
					}
				}
			}
			
			if ( $test_flag == true ) {
				die('ko');
			} else {
				die('ok');
			}
		}
	}
	
	/**
	 * Flush rewriting rules when a term language is insert or update for build correct URLs.
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function resetRewritingRules() {
		flush_rewrite_rules( false );
	}
}
?>