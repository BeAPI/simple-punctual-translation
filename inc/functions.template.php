<?php
/**
 * Translate the current post on an another language
 *
 * @param string $language_slug 
 * @return boolean
 * @author Amaury Balmer
 */
function switch_to_language( $language_slug = null ) {
	
}

/**
 * Restore the original language of the blog
 *
 * @return void
 * @author Amaury Balmer
 */
function restore_original_language() {
	
}

/**
 * Get the permalink for a translated version of a content
 *
 * @param integer $post 
 * @param string $language_code 
 * @return void
 * @author Amaury Balmer
 */
function get_translation_permalink( $post_id, $language_code = '' ) {
	if ( empty($language_code) )
		return get_permalink( $post_id );
	
	$permalink = get_option('permalink_structure');
	if ( '' != $permalink ) { // Rewriting enabled ?
		$link = get_permalink( $post_id );
		$link = str_replace( home_url('/'), '', $link );
		$link =  home_url('/') . $language_code . '/' . $link;
	} else {
		$link = add_query_arg( array('lang' => $language_code), get_permalink( $post_id ) );
	}
	
	return apply_filters( 'get_translation_permalink', $link, $post_id, $language_code );
}

/**
 * Get a PHP array with all languages availables for a content id specific
 *
 * @param integer $object_id 
 * @return void
 * @author Amaury Balmer
 */
function get_post_available_languages( $object_id = 0 ) {
	global $punctual_translation;
	return $punctual_translation['client']->getTranslateObjects( $object_id, 'objects' );
}

/**
 * Get HTML of language available for a post 
 *
 * @param string $before 
 * @param string $sep 
 * @param string $after 
 * @return string
 * @author Amaury Balmer
 */
function get_the_post_available_languages( $before = '', $sep = ', ', $after = '' ) {
	global $post;
	
	$languages = get_post_available_languages( $post->ID );
	if ( is_wp_error($languages) || empty($languages) )
		return '';

	$links = array();
	foreach ( $languages as $language ) {
		$link = get_translation_permalink( $post->post_parent, $language->slug );
		if ( is_wp_error( $link ) )
			return '';
		$links[] = '<a href="' . $link . '" rel="alternate" hreflang="'.$language->slug.'">' . $language->name . '</a>';
	}

	$links = apply_filters( "get_the_post_available_languages", $links );

	return $before . join( $sep, $links ) . $after;
}

/**
 * Just echo the get_the_post_available_languages() function
 *
 * @param string $before 
 * @param string $sep 
 * @param string $after 
 * @return void
 * @author Amaury Balmer
 */
function the_post_available_languages( $before = '', $sep = ', ', $after = '' ) {
	echo get_the_post_available_languages( $before, $sep, $after );
}
?>