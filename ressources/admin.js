jQuery(document).ready(function() {
	// Listen change on select AJAX, and clone value on no-js select
	// Todo, manage case if value not exist, from a DB change from AJAX and HTML values
	jQuery('select#post_parent_js').live('change', function() {
		syncSelectParentBox();
	});
	
	// Load default content for post_parent_js select
	loadPostParentAjax( jQuery('select#original_post_type_js').val(), jQuery('select#parent_id').val() );
	
	// Liste change on select post_type, for reload ajax content
	jQuery('select#original_post_type_js').live('change', function() {
		loadPostParentAjax( jQuery(this).val(), 0 );
	});
});

function loadPostParentAjax( post_type, current_value ) {
	jQuery("select#post_parent_js").load( ajaxurl, { 'action': 'load_original_content', 'post_type': post_type, 'current_value': current_value }, function(response, status, xhr) {
		if ( status == "error" ) {
			alert("Sorry but an error occured with AJAX method");
		} else {
			syncSelectParentBox();
		}
	});
}

function syncSelectParentBox() {
	jQuery("select#parent_id").val( jQuery("select#post_parent_js").val() );
}