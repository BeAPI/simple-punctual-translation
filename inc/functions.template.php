<?php
/**
 * Translate the current post on an another language
 *
 * @param string $language_slug 
 * @return boolean
 * @author Amaury Balmer
 */
function switch_to_language( $language_slug = null ) {
	global $post, $punctual_translation, $translation_flag, $original_object;
	
	// Restore original language before switch again...
	if ( $translation_flag === true ) {
		restore_original_language();
	}
	
	// Param or Query lang ?
	$lang = get_query_var(SPTRANS_QVAR);
	if ( empty($language_slug) && empty($lang) ) {
		$translation_flag = false;
		return false;
	} elseif( empty($language_slug) && !empty($lang) ) {
		$language_slug = $lang;
	}
	
	// All is fine ? try to switch
	if ( !empty($language_slug) ) {
		$translation = $punctual_translation['client']->getTranslateObject( $post->ID, $language_slug, 'object' );
		if ( $translation != false ) {
			$original_object = $post;
			$translation_flag = true;
			$post = $translation;
			setup_postdata($post);
			
			return true;
		}
	}
	
	return false;
}

/**
 * Restore the original language of the blog
 *
 * @return boolean
 * @author Amaury Balmer
 */
function restore_original_language() {
	global $post, $translation_flag, $original_object;
	
	if ( $translation_flag === true ) {
		$post = $original_object;
		setup_postdata($post);
		$translation_flag = false;
		
		return true;
	}
	
	return false;
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
	$current_options = get_option( SPTRANS_OPTIONS_NAME );
	if ( '' != $permalink && $current_options['rewrite'] == 'rewrite' ) { // Rewriting enabled ?
		$link = get_permalink( $post_id );
		$link = str_replace( home_url('/'), '', $link );
		$link =  home_url('/') . $language_code . '/' . $link;
	} else {
		$link = add_query_arg( array(SPTRANS_QVAR => $language_code), get_permalink( $post_id ) );
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
	return $punctual_translation['client']->getTranslateObjects( $object_id, 'terms_objects' );
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
	
	// Get available languages
	$languages = get_post_available_languages( $post->ID );
	if ( is_wp_error($languages) || empty($languages) )
		return '';

	// Build array with all lang
	$links = array();
	foreach ( $languages as $language ) {
		$link = get_translation_permalink( $post->ID, $language->slug );
		if ( is_wp_error( $link ) )
			return '';
		$links[$language->slug] = '<a class="lang-'.$language->slug.'" href="' . $link . '" rel="alternate" hreflang="'.$language->slug.'">' . $language->name . '</a>';
	}
	
	// Add original lang if a lang is already load, and delete current lang display
	$lang = get_query_var(SPTRANS_QVAR);
	if( !empty($lang) ) {
		$current_options = get_option( SPTRANS_OPTIONS_NAME );
		$links['original'] = '<a class="lang-original" href="' . get_permalink( $post->ID ) . '" rel="alternate" hreflang="'.esc_attr($current_options['original_lang_name']).'">' . esc_html($current_options['original_lang_name']) . '</a>';
		unset($links[$lang]);
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