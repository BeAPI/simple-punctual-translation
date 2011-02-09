<?php
/**
 * Class for display language selector
 *
 * @package Deal
 * @author Amaury Balmer
 */
class PunctualTranslation_Widget extends WP_Widget {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function PunctualTranslation_Widget() {
		$this->WP_Widget( 'punctual-translation_widget', __('Languages selector Content Widget', 'punctual-translation'), array( 'classname' => 'punctual-translation-widget', 'description' => __('Display available languages selector', 'punctual-translation') ) );
	}
	
	/**
	 * Client side widget render
	 *
	 * @param array $args
	 * @param array $instance
	 * @return void
	 * @author Amaury Balmer
	 */
	function widget( $args, $instance ) {
		global $wp_query;
		extract( $args );
		
		// Singular
		if ( !is_singular() )
			return false;
		
		// Build the name of the widget
		$title = apply_filters('widget_title', ( !empty($instance['title']) ) ? $instance['title'] : __('Languages selector', 'punctual-translation'), $instance, $this->id_base);
		
		echo $before_widget;
		if ( isset($title) )
			echo $before_title . $title . $after_title;
			
			
			
		echo $after_widget;
		return true;
	}
	
	/**
	 * Method for save widgets options
	 *
	 * @param string $new_instance
	 * @param string $old_instance
	 * @return void
	 * @author Amaury Balmer
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}
	
	/**
	 * Control for widget admin
	 *
	 * @param array $instance
	 * @return void
	 * @author Amaury Balmer
	 */
	function form( $instance ) {
		$defaults = array(
			'title' 	=> __('Languages selector', 'punctual-translation')
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title', 'punctual-translation'); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
	<?php
	}
}
?>