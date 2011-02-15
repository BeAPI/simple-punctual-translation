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
	
	// Ajax test for help usage on admin
	jQuery('select#language_translation').live('change', function() {
		checkUnicityTranslation( jQuery(this).val() );
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
	checkUnicityTranslation( jQuery("select#language_translation").val() );
}

function checkUnicityTranslation( current_value ) {
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: "action=test_once_translation&parent_id="+jQuery("select#parent_id").val()+"&current_id="+jQuery("input#post_ID").val()+"&current_value="+current_value,
		success: function(msg) {
			if ( msg != 'ok' ) {
				jQuery("#language_duplicate_ajax").html("<div class='message error' style='margin: 5px 0 0;'><p>"+translationL10n.errorText+"</p></div>");
			} else {
				jQuery("#language_duplicate_ajax").html("<div class='message updated' style='margin: 5px 0 0;'><p>"+translationL10n.successText+"</p></div>");
			}
		}
	});
}