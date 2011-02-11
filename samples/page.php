<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php /* Simple Punctual Translation : Translate to URL language */ ?>
		<?php switch_to_language(); ?>
		
			<?php if ( is_front_page() ) { ?>
				<h2 class="entry-title"><?php the_title(); ?></h2>
			<?php } else { ?>
				<h1 class="entry-title"><?php the_title(); ?></h1>
			<?php } ?>

			<div class="entry-content">
				<?php the_content(); ?>
				<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
				<?php edit_post_link( __( 'Edit', 'twentyten' ), '<span class="edit-link">', '</span>' ); ?>
			</div><!-- .entry-content -->
		
		<?php /* Simple Punctual Translation : Restore original language */ ?>
		<?php restore_original_language(); ?>
	</div><!-- #post-## -->
	
	<?php /* Simple Punctual Translation : Display all languages available */ ?>
	<?php the_post_available_languages( '<p>'.__('Also available in : ', 'punctual-translation'), ', ', '</p>' ); ?>
	
	<?php comments_template( '', true ); ?>

<?php endwhile; // end of the loop. ?>