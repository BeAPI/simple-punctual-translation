jQuery(document).ready(function () {

    // Choices.js instance - will be initialized after AJAX load
    let translationSelect = null;

    // Listen change on select AJAX, and clone value on no-js select
    // Todo, manage case if value not exist, from a DB change from AJAX and HTML values
    jQuery('select#post_parent_js').on('change', function () {
        syncSelectParentBox();
    });

    // Load default content for post_parent_js select
    loadPostParentAjax(jQuery('select#original_post_type_js').val(), jQuery('select#parent_id').val());

    // Liste change on select post_type, for reload ajax content
    jQuery('select#original_post_type_js').on('change', function () {
        loadPostParentAjax(jQuery(this).val(), 0);
    });

    // Ajax test for help usage on admin
    jQuery('select#language_translation').on('change', function () {
        checkUnicityTranslation(jQuery(this).val());
    });

    // Store search timeout globally to avoid conflicts
    let searchTimeout = null;

    function loadPostParentAjax(post_type, current_value) {
        jQuery("select#post_parent_js").load(ajaxurl, {
            'action': 'load_original_content',
            'post_type': post_type,
            'current_value': current_value,
            'limit': 100
        }, function (response, status, xhr) {
            if (status == "error") {
                alert("Sorry but an error occured with AJAX method");
            } else {
                // Destroy previous Choices instance if exists
                if (translationSelect !== null) {
                    translationSelect.destroy();
                }
                
                // Initialize Choices.js after options are loaded
                const selectElement = jQuery('#post_parent_js')[0];
                translationSelect = new Choices(selectElement, {
                    searchEnabled: true,
                    itemSelectText: '',
                    searchChoices: false, // Disable local search, we'll use AJAX
                    shouldSort: true,
                    shouldSortItems: true
                });

                // Wait for Choices.js to render, then attach search handler
                setTimeout(function() {
                    attachSearchHandler(post_type, current_value);
                }, 100);

                syncSelectParentBox();
            }
        });
    }

    function attachSearchHandler(post_type, current_value) {
        // Find the search input in Choices.js
        const choicesContainer = jQuery('#post_parent_js').closest('.choices');
        const searchInput = choicesContainer.find('input[type="text"]');

        // Remove any existing handlers
        searchInput.off('input.ajaxSearch');

        // Attach new search handler
        searchInput.on('input.ajaxSearch', function() {
            const searchTerm = jQuery(this).val().trim();
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            // If search is cleared, reload initial list
            if (searchTerm.length < 2) {
                loadPostParentAjax(post_type, current_value);
                return;
            }

            // Debounce search requests (wait 300ms after user stops typing)
            searchTimeout = setTimeout(function() {
                loadPostParentAjaxSearch(post_type, current_value, searchTerm);
            }, 300);
        });
    }

    function loadPostParentAjaxSearch(post_type, current_value, searchTerm) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                'action': 'load_original_content',
                'post_type': post_type,
                'current_value': current_value,
                'search': searchTerm,
                'limit': 100
            },
            success: function(response) {
                // Parse the response to get options
                const tempSelect = jQuery('<select>').html(response);
                const options = tempSelect.find('option');

                // Destroy and recreate Choices instance with new options
                if (translationSelect !== null) {
                    translationSelect.destroy();
                }

                // Clear and repopulate select
                const selectElement = jQuery('#post_parent_js')[0];
                selectElement.innerHTML = '';
                options.each(function() {
                    selectElement.appendChild(this);
                });

                // Reinitialize Choices.js
                translationSelect = new Choices(selectElement, {
                    searchEnabled: true,
                    itemSelectText: '',
                    searchChoices: false,
                    shouldSort: true,
                    shouldSortItems: true
                });

                // Reattach search handler
                setTimeout(function() {
                    attachSearchHandler(post_type, current_value);
                }, 100);

                syncSelectParentBox();
            },
            error: function() {
                alert("Sorry but an error occured with AJAX search method");
            }
        });
    }
});


function syncSelectParentBox() {
    jQuery("select#parent_id").val(jQuery("select#post_parent_js").val());
    checkUnicityTranslation(jQuery("select#language_translation").val());
}

function checkUnicityTranslation(current_value) {
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: "action=test_once_translation&parent_id=" + jQuery("select#parent_id").val() + "&current_id=" + jQuery("input#post_ID").val() + "&current_value=" + current_value,
        success: function (msg) {
            if (msg != 'ok') {
                jQuery("#language_duplicate_ajax").html("<div class='message error' style='margin: 5px 0 0;'><p>" + translationL10n.errorText + "</p></div>");
            } else {
                jQuery("#language_duplicate_ajax").html("<div class='message updated' style='margin: 5px 0 0;'><p>" + translationL10n.successText + "</p></div>");
            }
        }
    });
}