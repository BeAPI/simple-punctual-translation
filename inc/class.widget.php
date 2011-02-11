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
		extract( $args );
		
		// Singular
		if ( !is_singular() )
			return false;
			
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();
			
		// Display mode
		if ( $instance['display'] == 'list' ) {
			$wrap 	= 'ul';
			$before = '<li>';
			$separe = '</li><li>';
			$after	= '</li>';
		} else { // comma
			$wrap 	= 'p';
			$before = __('Also available in : ', 'punctual-translation');
			$separe = ', ';
			$after	= '';
		}
			
		// Build HTML output
		$html = get_the_post_available_languages( $before, $separe, $after );
		if ( empty($html) )
			return false;
		
		// Build the name of the widget
		$title = apply_filters('widget_title', ( !empty($instance['title']) ) ? $instance['title'] : __('Languages selector', 'punctual-translation'), $instance, $this->id_base);
		
		echo $before_widget;
		if ( isset($title) )
			echo $before_title . $title . $after_title;
			
		echo '<'.$wrap.' class="lang-switcher">' . "\n";
			echo $html;
		echo '</'.$wrap.'>' . "\n";
			
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
		$instance['display'] = strip_tags( $new_instance['display'] );
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
			'title' => __('Languages selector', 'punctual-translation'),
			'display' => 'list'
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label>
				<?php _e('Title', 'punctual-translation'); ?>
				<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
			</label>
		</p>
		<p>
			<label>
				<?php _e('Display as', 'punctual-translation'); ?>
				<select id="<?php echo $this->get_field_id( 'display' ); ?>" name="<?php echo $this->get_field_name( 'display' ); ?>" class="widefat">
					<option value="list" <?php selected('list', $instance['display']); ?>><?php _e('List', 'punctual-translation'); ?></option>
					<option value="comma" <?php selected('comma', $instance['display']); ?>><?php _e('Separed with comma', 'punctual-translation'); ?></option>
				</select>
			</label>
		</p>
	<?php
	}
}
?>