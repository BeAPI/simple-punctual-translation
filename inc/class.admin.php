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
		// Style, Javascript
		add_action( 'admin_enqueue_scripts', array(&$this, 'addCSS') );
		
		// Metadatas
		add_action( 'add_meta_boxes_' . $this->post_type, array(&$this, 'registerMetaBox') );
		add_action( 'save_post', array(&$this, 'saveDatasMetaBoxes'), 10, 2 );
		
		// Listing
		add_filter( 'manage_posts_columns', array( &$this, 'addColumns'), 10 ,2 );
		add_action( 'manage_posts_custom_column', array(&$this, 'addColumnValue' ), 10, 2 );
		add_filter( 'post_row_actions', array(&$this, 'extendActionsList'), 10, 2 );
		
		// CSV Users
		add_filter( 'bulk_actions-'.'edit-post', array(&$this, 'addTranslateActions') );
	}
	
	/**
	 * Add a link for translate content
	 *
	 * @param array $actions 
	 * @return array
	 * @author Amaury Balmer
	 */
	function addTranslateActions( $actions ) {
		$actions['translate'] = __('Translate', 'punctual-translation');
		return $actions;
	}
	
	/**
	 * Register CSS for correct post type
	 *
	 * @param string $hook_suffix 
	 * @return void
	 * @author Amaury Balmer
	 */
	function addCSS( $hook_suffix = '' ) {
		global $post;
		
		if ( 
			( $hook_suffix == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == $this->post_type ) || 
			( $hook_suffix == 'post.php' && isset($_GET['post']) && $post->post_type == $this->post_type ) ||
			( $hook_suffix == 'edit.php' && $_GET['post_type'] == $this->post_type ) 
		) {
			wp_enqueue_style( 'admin-translation', SPTRANS_URL.'/ressources/admin.css', array(), SPTRANS_VERSION, 'all' );
		}
	}
	
	/**
	 * Save datas of translation databox
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function saveDatasMetaBoxes( $object_id = 0, $object = null ) {
		if ( isset($_POST[$this->post_type.'_meta_translation']) && $_POST[$this->post_type.'_meta_translation'] == 'true' ) {
			// update_post_meta( $object_id, 'subtitle', stripslashes($_POST['subtitle']) );
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
		add_meta_box($this->post_type.'div1', __('Translation info', 'punctual-translation'), array(&$this, 'MetaboxTranslation'), $this->post_type, 'normal', 'core');
	}
	
	/**
	 * Display HTML of translation
	 *
	 * @param object $post 
	 * @return void
	 * @author Amaury Balmer
	 */
	function MetaboxTranslation( $post ) {
		
		?>
		<div class="form-life">
			
			<input type="hidden" name="<?php echo $this->post_type.'_meta_translation'; ?>" value="true" />
		</div>
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
		if ( $post_type != $this->post_type ) {
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
			case 'original':
				$translation = get_post($object_id);
				echo get_post_status_object($translation->post_status)->label;
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
		$actions['translate'] = '<a href="'.admin_url('edit.php?post_type=lifeorder&post_parent='.$object->ID).'">'.__('Translate', 'punctual-translation').'</a>' . "\n";

		return $actions;
	}
}
?>