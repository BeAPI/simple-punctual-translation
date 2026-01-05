jQuery(document).ready(function () {
    let translationSelect = null;
    let searchTimeout = null;
    let currentPostType = null;
    let currentValue = null;
    let valueBeforeSearch = null; // Store the actual selected value before starting a search
    const choicesOptions = {
        searchEnabled: true,
        searchResultLimit: -1,
        noChoicesText: translationL10n.pleaseSearch || 'Saisissez au moins 2 caractères pour rechercher',
        removeItemButton: true,
        itemSelectText: '',
        searchChoices: false, // Disable local search, we'll use AJAX
        shouldSort: false,
        shouldSortItems: false,
        placeholder: true,
        placeholderValue: translationL10n.searchPlaceholder || 'Rechercher un article...',
        searchPlaceholderValue: translationL10n.searchPlaceholder || 'Rechercher un article...',
        fuseOptions: {
            threshold: 1.0 // Effectively disable Fuse.js search
        }
    }

    /**
     * Sync the selected value from Choices.js to the hidden select used for form submission
     */
    function syncSelectParentBox() {
        if (translationSelect === null) return;
        
        // Get value directly from the select element (most reliable)
        const selectElement = jQuery('#post_parent_js')[0];
        let selectedValue = '';
        
        if (selectElement) {
            selectedValue = selectElement.value || '';
        }
        
        // Update the hidden select used for form submission
        jQuery("select#parent_id").val(selectedValue);
        
        checkUnicityTranslation(jQuery("select#language_translation").val());
    }
    
    // Attach Choices.js native event handlers
    function attachChoicesHandlers() {
        if (translationSelect === null) return;
        
        const selectElement = jQuery('#post_parent_js')[0];
        if (!selectElement) {
            setTimeout(attachChoicesHandlers, 100);
            return;
        }
        
        // Listen to choice selection
        selectElement.addEventListener('choice', function() {
            // Update stored value when user actually selects something
            const selectElement = jQuery('#post_parent_js')[0];
            if (selectElement && selectElement.value) {
                valueBeforeSearch = selectElement.value;
            }
            syncSelectParentBox();
        });
        
        // Also listen to native change event as fallback
        selectElement.addEventListener('change', function() {
            // Update stored value when user actually selects something
            const selectElement = jQuery('#post_parent_js')[0];
            if (selectElement && selectElement.value) {
                valueBeforeSearch = selectElement.value;
            }
            syncSelectParentBox();
        });
        
        // Listen to dropdown open/close events (native Choices.js events)
        selectElement.addEventListener('showDropdown', function() {
            // Store the value when dropdown opens (before any search)
            const selectElement = jQuery('#post_parent_js')[0];
            valueBeforeSearch = selectElement && selectElement.value ? selectElement.value : null;
            setTimeout(hideSelectedFromDropdown, 10);
        });
        
        selectElement.addEventListener('hideDropdown', function() {
            resetSearchOnClose();
        });
        
        // Listen to search event (native Choices.js event)
        selectElement.addEventListener('search', function(event) {
            const searchTerm = event.detail.value.trim();
            
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            if (searchTerm.length < 2) {
                return;
            }

            searchTimeout = setTimeout(function() {
                loadPostParentAjaxSearch(searchTerm);
            }, 300);
        });
    }

    // Get initial values - check both selects for compatibility
    currentPostType = jQuery('select#original_post_type_js').val();
    currentValue = jQuery('select#post_parent_js').val() || jQuery('select#parent_id').val();

    /**
     * Hide the currently selected option from the dropdown list
     */
    function hideSelectedFromDropdown() {
        const choicesContainer = jQuery('#post_parent_js').closest('.choices');
        if (choicesContainer.length === 0) return;
        
        const selectElement = jQuery('#post_parent_js')[0];
        const currentSelected = selectElement.value;
        
        if (!currentSelected || currentSelected === '__searching__') return;
        
        choicesContainer.find('.choices__list--dropdown .choices__item').each(function() {
            const item = jQuery(this);
            const itemValue = item.attr('data-value');
            if (itemValue === currentSelected) {
                item.css('display', 'none');
            }
        });
    }

    /**
     * Reset search when dropdown closes
     */
    function resetSearchOnClose() {
        if (translationSelect === null) return;
        
        const selectElement = jQuery('#post_parent_js')[0];
        
        // Clear search input
        const choicesContainer = jQuery('#post_parent_js').closest('.choices');
        const searchInput = choicesContainer.find('.choices__input--cloned, .choices__input');
        searchInput.val('');
        
        // Use the value that was selected BEFORE the search started, not the current value
        // (which might be from search results or auto-selected)
        if (valueBeforeSearch && valueBeforeSearch !== '__searching__' && valueBeforeSearch !== '') {
            loadSelectedValueOnly(currentPostType, valueBeforeSearch);
        } else {
            // No selection - clear everything and reset to empty state
            translationSelect.clearChoices();
            if (selectElement) {
                selectElement.value = '';
            }
            syncSelectParentBox();
        }
    }


    // Initialize Choices.js with empty select (no default load)
    initializeChoices();

    // If a value is already selected, load only that option for retrocompatibility
    if (currentValue && parseInt(currentValue) > 0) {
        loadSelectedValueOnly(currentPostType, currentValue);
    }

    // Liste change on select post_type, reset and clear choices
    jQuery('select#original_post_type_js').on('change', function () {
        currentPostType = jQuery(this).val();
        currentValue = 0;
        
        // Clear and reset Choices
        if (translationSelect !== null) {
            translationSelect.destroy();
            translationSelect = null;
        }
        
        const selectElement = jQuery('#post_parent_js')[0];
        selectElement.innerHTML = ''; // Choices.js will handle the placeholder natively
        
        initializeChoices();
    });

    // Ajax test for help usage on admin
    jQuery('select#language_translation').on('change', function () {
        checkUnicityTranslation(jQuery(this).val());
    });

    /**
     * Initialize Choices.js with empty select
     */
    function initializeChoices() {
        const selectElement = jQuery('#post_parent_js')[0];
        
        if (!selectElement.innerHTML.trim() || selectElement.innerHTML === ' AJAX Values ') {
            selectElement.innerHTML = '';
        }

        if (translationSelect !== null) {
            translationSelect.destroy();
        }

        translationSelect = new Choices(selectElement, choicesOptions);
        attachChoicesHandlers();
    }


    /**
     * Load only the selected value for retrocompatibility
     */
    function loadSelectedValueOnly(post_type, selected_value) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                'action': 'load_original_content',
                'post_type': post_type,
                'current_value': selected_value,
                'limit': 1,
                'load_selected_only': true
            },
            success: function(response) {
                const tempSelect = jQuery('<select>').html(response);
                const options = tempSelect.find('option');

                if (options.length > 0) {
                    const selectedOption = options.first();
                    const selectElement = jQuery('#post_parent_js')[0];
                    selectElement.innerHTML = '';

                    if (translationSelect !== null) {
                        translationSelect.destroy();
                    }

                    translationSelect = new Choices(selectElement, choicesOptions);
                    translationSelect.setChoices([
                        {
                            value: selected_value.toString(),
                            label: selectedOption.text(),
                            selected: true
                        }
                    ], 'value', 'label', false);
                    
                    setTimeout(function() {
                        hideSelectedFromDropdown();
                        attachChoicesHandlers();
                        syncSelectParentBox();
                    }, 100);
                }
            },
            error: function() {
                console.error('Error loading selected value');
            }
        });
    }

    /**
     * Load posts via AJAX search
     */
    function loadPostParentAjaxSearch(searchTerm) {
        if (!currentPostType) {
            currentPostType = jQuery('select#original_post_type_js').val();
        }
        
        if (!currentPostType) return;

        // Use the value that was stored when dropdown opened (before search)
        const currentSelectedValue = valueBeforeSearch;
        
        translationSelect.setChoices([
            {
                value: '__searching__',
                label: translationL10n.searchingText || 'Recherche en cours...',
                disabled: false
            }
        ], 'value', 'label', true);

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                'action': 'load_original_content',
                'post_type': currentPostType,
                'current_value': currentValue || 0,
                'search': searchTerm,
                'limit': 100
            },
            success: function(response) {
                const tempSelect = jQuery('<select>').html(response);
                const options = tempSelect.find('option');

                if (options.length === 0) {
                    translationSelect.setChoices([
                        {
                            value: '',
                            label: translationL10n.noResultsText || 'Aucun résultat trouvé',
                            disabled: true
                        }
                    ], 'value', 'label', true);
                    return;
                }

                const choices = [];
                options.each(function() {
                    const optionValue = jQuery(this).attr('value');
                    if (!optionValue || optionValue === currentSelectedValue) return;
                    
                    choices.push({
                        value: optionValue,
                        label: jQuery(this).text(),
                        selected: false
                    });
                });

                translationSelect.setChoices(choices, 'value', 'label', true);
                
                if (currentSelectedValue && currentSelectedValue !== '__searching__' && currentSelectedValue !== '') {
                    const selectElement = jQuery('#post_parent_js')[0];
                    selectElement.value = currentSelectedValue.toString();
                    translationSelect.setChoiceByValue(currentSelectedValue.toString());
                    syncSelectParentBox();
                }
                
                setTimeout(function() {
                    hideSelectedFromDropdown();
                }, 100);
            },
            error: function() {
                alert("Sorry but an error occured with AJAX search method");
            }
        });
    }
});

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