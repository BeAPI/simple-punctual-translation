<?php

class PunctualTranslation_Admin_Settings {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Menu setting
		add_action( 'admin_menu', [ $this, 'addMenu' ] );
		add_action( 'admin_init', [ $this, 'registerSettings' ] );
	}

	/**
	 * Add menu for settings plugin
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function addMenu() {
		add_options_page( __( 'Simple Punctual Translation', 'punctual-translation' ), __( 'Translations', 'punctual-translation' ), 'manage_options', 'punctual-translation-settings', [ $this, 'pageSettings' ] );
	}

	/**
	 * Register setting on options API
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function registerSettings() {
		register_setting( 'punctual-translation-settings-group', SPTRANS_OPTIONS_NAME );
	}

	/**
	 * Make HTML for settings
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function pageSettings() {
		$current_options = get_option( SPTRANS_OPTIONS_NAME );
		if ( ! isset( $current_options['auto'] ) ) {
			$current_options['auto'] = [];
		}
		?>
		<div class="wrap">
			<h2><?php _e( 'Simple Punctual Translation', 'punctual-translation' ); ?></h2>

			<form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php settings_fields( 'punctual-translation-settings-group' ); ?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Original language name', 'punctual-translation' ); ?></th>
						<td>
							<input type="text" name="punctual-translation[original_lang_name]" value="<?php echo esc_attr( $current_options['original_lang_name'] ); ?>"/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Post types translatable :', 'punctual-translation' ); ?></th>
						<td>
							<?php
							foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $cpt ) {
								echo '<label style="display:block;"><input type="checkbox" name="punctual-translation[cpt][]" value="' . esc_attr( $cpt->name ) . '" ' . checked( in_array( $cpt->name, (array) $current_options['cpt'], true ), true, false ) . ' /> ' . esc_html( $cpt->labels->name ) . '</label>' . "\n";
							}
							?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Translation mode :', 'punctual-translation' ); ?></th>
						<td>
							<label style="display:block;">
								<input type="radio" name="punctual-translation[mode]" value="manual" <?php checked( 'manual', $current_options['mode'] ); ?> />
								<?php _e( 'Manual', 'punctual-translation' ); ?>
							</label>
							<label style="display:block;">
								<input type="radio" name="punctual-translation[mode]" value="auto" <?php checked( 'auto', $current_options['mode'] ); ?> />
								<?php _e( 'Auto (singular only)', 'punctual-translation' ); ?>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Address mode :', 'punctual-translation' ); ?></th>
						<td>
							<label style="display:block;">
								<input type="radio" name="punctual-translation[rewrite]" value="classic" <?php checked( 'classic', $current_options['rewrite'] ); ?> />
								<?php _e( 'Classic, adding "?lang=fr" for the URL.', 'punctual-translation' ); ?>
							</label>
							<label style="display:block;">
								<input type="radio" name="punctual-translation[rewrite]" value="rewrite" <?php checked( 'rewrite', $current_options['rewrite'] ); ?> />
								<?php _e( 'Rewrite, prefix URL with "/fr/my-content/"', 'punctual-translation' ); ?>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Auto add languages available at the end of post', 'punctual-translation' ); ?></th>
						<td>
							<label style="display:block;">
								<input type="checkbox" name="punctual-translation[auto][]" value="content" <?php checked( in_array( 'content', (array) $current_options['auto'], true ), true ); ?> />
								<?php _e( 'On hook "the_content". (page, post single, etc.)', 'punctual-translation' ); ?>
							</label>
							<label style="display:block;">
								<input type="checkbox" name="punctual-translation[auto][]" value="excerpt" <?php checked( in_array( 'excerpt', (array) $current_options['auto'], true ), true ); ?> />
								<?php _e( 'On hook "the_excerpt" (category, tags, home, etc.)', 'punctual-translation' ); ?>
							</label>
							<label style="display:block;">
								<input type="checkbox" name="punctual-translation[auto][]" value="feed" <?php checked( in_array( 'feed', (array) $current_options['auto'], true ), true ); ?> />
								<?php _e( 'On feed hook "the_content_feed" and "the_excerpt_rss" (rss2, atom)', 'punctual-translation' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'punctual-translation' ); ?>"/>
				</p>
			</form>
		</div>
		<?php
	}
}
