/**
 * Anti Hacker - Installer AJAX Script (with debugging)
 *
 * Handles the step-by-step navigation of the installer using AJAX
 * to provide a smooth, single-page experience.
 */
jQuery(document).ready(function ($) {
    // console.log('DEBUG: Installer script loaded.');
    // The main container for the installer content
    const contentContainer = $('#antihacker-inst-content-container');
    const stepIndicator = $('#antihacker-inst-step-indicator');
    const logoImg = $('#antihacker-inst-logo');
    // Store the base URL for the indicator image
    const indicatorBaseUrl = stepIndicator.attr('src').replace(/header-install-step-\d\.png$/, '');
    /**
     * Shows a loading spinner and disables buttons.
     */
    function showLoading() {
        // console.log('DEBUG: showLoading() called.');
        contentContainer.addClass('is-loading');
        contentContainer.find('button').prop('disabled', true);
        const loader = '<div class="antihacker-inst-loader"><span class="spinner is-active"></span><p>Processing...</p></div>';
        contentContainer.html(loader);
    }
    /**
     * Updates the main header step indicator image.
     * @param {number} step The current step number.
     */
    function updateStepIndicator(step) {
        // console.log(`DEBUG: updateStepIndicator() called for step: ${step}`);
        if (step > 0 && step <= 4) {
            stepIndicator.attr('src', indicatorBaseUrl + 'header-install-step-' + step + '.png');
        }
    }
    /**
     * Loads the content for a specific step via AJAX.
     * @param {number} step The step number to load.
     * @param {string} direction 'next' or 'back'.
     */
    /**
    * Loads the content for a specific step via AJAX.
    * @param {number} step The step number to load.
    * @param {string} direction 'next' or 'back'.
    */
    /**
 * Loads the content for a specific step via AJAX.
 * @param {number} step The step number to load.
 * @param {string} direction 'next' or 'back'.
 */
    /**
     * Loads the content for a specific step via AJAX.
     * @param {number} step The step number to load.
     * @param {string} direction 'next' or 'back'.
     */
    function loadStep(step, direction = 'next') {
        // console.log(`%cDEBUG: loadStep() called. Requested step: ${step}, Direction: ${direction}`, 'color: blue; font-weight: bold;');
        // --- A CORREÇÃO CRÍTICA ESTÁ AQUI ---
        // Encontramos o formulário DENTRO do nosso container.
        // Isso garante que estamos pegando o formulário que foi carregado dinamicamente.
        const form = $('#antihacker-installer-form', contentContainer);
        // --- E AQUI ---
        // Usamos a variável 'form' que acabamos de criar.
        const formData = (direction === 'next' && form.length > 0) ? form.serializeArray() : [];
        showLoading();
        updateStepIndicator(step);
        let ajaxData = {
            action: 'antihacker_installer_step',
            nonce: antihacker_installer_ajax.nonce,
            step_to_load: step,
            direction: direction,
        };
        if (formData.length > 0) {
            // console.log('DEBUG: Serialized form data:', formData);
            $.each(formData, function (i, field) {
                ajaxData[field.name] = field.value;
            });
        }
        // console.log('DEBUG: Sending final AJAX data object:', ajaxData);
        $.ajax({
            url: antihacker_installer_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function (response) {
                // console.log('DEBUG: AJAX success response received:', response);
                if (response.success) {
                    // Atualizamos o conteúdo do nosso container.
                    contentContainer.html(response.data.html);
                    contentContainer.removeClass('is-loading');
                } else {
                    const errorMessage = response.data.message || 'An unknown error occurred. Please try again.';
                    contentContainer.html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('DEBUG: AJAX error occurred.', {
                    status: textStatus,
                    error: errorThrown,
                    response: jqXHR.responseText
                });
                contentContainer.html('<div class="notice notice-error"><p>A server error occurred. Please check the browser console and refresh the page.</p></div>');
            }
        });
    }
    /**
     * Handles the final step submission which results in a redirect.
     */
    function finishInstallation() {
        // console.log('DEBUG: finishInstallation() called.');
        showLoading();
        $.ajax({
            url: antihacker_installer_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'antihacker_installer_step',
                nonce: antihacker_installer_ajax.nonce,
                step_to_load: 5, // Use 5 to signify the "finish" action on the backend
                direction: 'next'
            },
            success: function (response) {
                // console.log('DEBUG: AJAX finish response received:', response);
                if (response.success && response.data.redirect) {
                    // console.log(`DEBUG: Redirecting to ${response.data.redirect}`);
                    window.location.href = response.data.redirect;
                } else {
                    const errorMessage = response.data.message || 'Could not finalize installation. Please try again.';
                    contentContainer.html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('DEBUG: AJAX finish error occurred.', {
                    status: textStatus,
                    error: errorThrown,
                    response: jqXHR.responseText
                });
                contentContainer.html('<div class="notice notice-error"><p>A server error occurred during finalization. Please contact support.</p></div>');
            }
        });
    }
    // =========================================================================
    // Event Handlers
    // =========================================================================
    contentContainer.on('submit', '#antihacker-installer-form', function (e) {
        e.preventDefault();
        const currentStep = $(this).data('step');
        // console.log(`DEBUG: Form submitted for step ${currentStep}`);
        if (currentStep === 4) {
            finishInstallation();
        } else {
            const nextStep = currentStep + 1;
            loadStep(nextStep, 'next');
        }
    });
    contentContainer.on('click', '.antihacker-inst-back', function (e) {
        e.preventDefault();
        const previousStep = $(this).data('step');
        // console.log(`DEBUG: 'Back' button clicked. Going to step ${previousStep}`);
        loadStep(previousStep, 'back');
    });
    // =========================================================================
    // Initial Load
    // =========================================================================
    // console.log(`DEBUG: Initial load. Requested step: ${antihacker_installer_ajax.initial_step}`);
    loadStep(antihacker_installer_ajax.initial_step, 'back'); // 'back' prevents sending empty form data
});