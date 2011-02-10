<?php
class PunctualTranslation_Admin {
	private $post_type = 'translation';
	
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
		add_action( 'add_meta_boxes', array(&$this, 'registerMetaBox') );
		//add_action( 'save_post', array(&$this, 'saveDatasMetaBoxes'), 10, 2 );
		
		// Listing
		add_filter( 'manage_posts_columns', array( &$this, 'addColumns'), 10 ,2 );
		add_action( 'manage_posts_custom_column', array(&$this, 'addColumnValue' ), 10, 2 );
		
		add_filter( 'post_row_actions', array(&$this, 'extendActionsList'), 10, 2 );
		add_filter( 'page_row_actions', array(&$this, 'extendActionsList'), 10, 2 );
		
		// Ajax
		add_action( 'wp_ajax_' . 'load_original_content', array(&$this, 'ajaxBuildSelect' ) );
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
		
		if ( $post_before->post_type == 'translation' ) {
			if ( $post_before->post_parent != 0 && $post_after->post_parent != $post_before->post_parent ) {
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
			( $hook_suffix == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == $this->post_type ) || 
			( $hook_suffix == 'post.php' && isset($_GET['post']) && $post->post_type == $this->post_type ) ||
			( $hook_suffix == 'edit.php' && $_GET['post_type'] == $this->post_type ) 
		) {
			wp_enqueue_style ( 'admin-translation', SPTRANS_URL.'/ressources/admin.css', array(), SPTRANS_VERSION, 'all' );
			wp_enqueue_script( 'admin-translation', SPTRANS_URL.'/ressources/admin.js', array('jquery'), SPTRANS_VERSION );
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
		} elseif ( isset($_POST['_meta_original_translation']) && $_POST['_meta_original_translation'] == 'true' ) {
			// Nothing actualluy too.
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
		if ( $post_type != $this->post_type )
			add_meta_box($post_type.'-translation', __('Translations', 'punctual-translation'), array(&$this, 'MetaboxTranslation'), $post_type, 'side', 'core');
		else
			add_meta_box($post_type.'-translation', __('Original content', 'punctual-translation'), array(&$this, 'MetaboxOriginalContent'), $post_type, 'side', 'core');
	}
	
	/**
	 * List translation available and form for create new translation
	 *
	 * @param object $post 
	 * @return void
	 * @author Amaury Balmer
	 */
	function MetaboxTranslation( $post ) {
		$q_translations = new WP_Query( array('post_type' => 'translation', 'post_status' => 'any', 'post_parent' => $post->ID) );
		if ( $q_translations->have_posts() ) {
			echo '<h4 style="margin:0;">'.__('Existings translations :', 'punctual-translation').'</h4>' . "\n";
			echo '<ul class="current_translations ul-square">' . "\n";
			foreach( $q_translations->posts as $translation ) {
				$language = get_the_terms( $translation->ID, 'language' );
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
				<select name="original_post_type_js" id="original_post_type_js">
					<?php
					foreach( get_post_types( array('public' => true), 'objects' ) as $cpt ) {
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
				<div id="ajax-destination-select-original"></div>
				<select name="post_parent_js" id="post_parent_js"> AJAX Values </select>
			</p>
		</div>
		
		<div id="original-content" class="hide-if-js">
			<label for="parent_id"><?php _e('Original', 'punctual-translation'); ?></label>
			<br />
			<select name="parent_id" id="parent_id">
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
		if ( $post_type == $this->post_type ) {
			$defaults['original-translation'] = __('Original', 'punctual-translation');
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
		if ( $object->post_type != $this->post_type )
			$actions['translate'] = '<a href="'.admin_url('post-new.php?post_type=translation&post_parent='.$object->ID).'">'.__('Translate', 'punctual-translation').'</a>' . "\n";
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
}
?>