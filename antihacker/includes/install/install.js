/**
 * Anti Hacker - Installer AJAX Script (with debugging)
 *
 * Handles the step-by-step navigation of the installer using AJAX
 * to provide a smooth, single-page experience.
 */
jQuery(document).ready(function ($) {
    console.log('DEBUG: Installer script loaded.');
    // The main container for the installer content
    let hasFailed = false
    const contentContainer = $('#stopbadbots-inst-content-container');
    const stepIndicator = $('#stopbadbots-inst-step-indicator');
    const logoImg = $('#stopbadbots-inst-logo');
    // Store the base URL for the indicator image
    const indicatorBaseUrl = stepIndicator.attr('src').replace(/header-install-step-\d\.png$/, '');
    /**
     * Shows a loading spinner and disables buttons.
     */
    function showLoading() {
        // console.log('DEBUG: showLoading() called.');
        contentContainer.addClass('is-loading');
        contentContainer.find('button').prop('disabled', true);
        const loader = '<div class="stopbadbots-inst-loader"><span class="spinner is-active"></span><p>Processing...</p></div>';
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
        const form = $('#stopbadbots-installer-form', contentContainer);
        const formData = (direction === 'next' && form.length > 0) ? form.serializeArray() : [];

        showLoading();
        updateStepIndicator(step);

        //alert('63');
        // console.log(step);

        let ajaxData = {
            action: 'stopbadbots_installer_step',
            nonce: stopbadbots_installer_ajax.nonce,
            step_to_load: step,
            direction: direction,
        };

        if (formData.length > 0) {
            $.each(formData, function (i, field) {
                ajaxData[field.name] = field.value;
            });
        }

        // Inicia a chamada AJAX
        $.ajax({
            url: stopbadbots_installer_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            timeout: 20000,
        })
            // AGORA, ENCADEAMOS OS MANIPULADORES FORA DO OBJETO
            .done(function (response) { // .done() substitui 'success'
                if (response.success) {
                    contentContainer.html(response.data.html);
                    contentContainer.removeClass('is-loading');
                } else {
                    const errorMessage = response.data.message || 'An unknown error occurred. Please try again.';
                    contentContainer.html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
                }
            })
            .fail(function () { // .fail() substitui 'error'
                // Uma flag para garantir que esta lógica não rode múltiplas vezes
                // (Declare 'let hasFailed = false;' no topo do seu script)
                if (hasFailed) return;
                hasFailed = true;


                // 1. Mostra o alert. O script pausa aqui até o usuário clicar "OK".
                alert('The installer could not be completed because your site has issues. Please install and run our free site-checkup plugin.');
                // max-age=3000 -> Define o tempo de vida do cookie para 300 segundos (50 minutos). Isso é uma medida de segurança para que ele expire caso algo dê errado e o PHP não o apague.
                document.cookie = "stopbadbots_setup_aborted=true; path=/; max-age=3000";
                // 2. Após o alert, faz a chamada AJAX para finalizar a instalação.
                /*
                $.ajax({
                    url: stopbadbots_installer_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'stopbadbots_force_complete', // A nova action que você criará no PHP
                        nonce: stopbadbots_installer_ajax.nonce
                    }
                })
                    // 3. Este bloco .always() executa DEPOIS que a chamada acima terminar.
                    .always(function () {
                        // Redireciona o usuário para o dashboard do WordPress.
                        window.location.href = stopbadbots_installer_ajax.dashboard_url;
                    });
                    */
            }); // O ponto e vírgula final fecha toda a instrução $.ajax()...
    }
    /**
     * Handles the final step submission which results in a redirect.
     */
    function finishInstallation() {
        // console.log('DEBUG: finishInstallation() called.');
        showLoading();
        console.log();
        $.ajax({
            url: stopbadbots_installer_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'stopbadbots_installer_step',
                nonce: stopbadbots_installer_ajax.nonce,
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
    contentContainer.on('submit', '#stopbadbots-installer-form', function (e) {
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
    contentContainer.on('click', '.stopbadbots-inst-back', function (e) {
        e.preventDefault();
        const previousStep = $(this).data('step');
        // console.log(`DEBUG: 'Back' button clicked. Going to step ${previousStep}`);
        loadStep(previousStep, 'back');
    });
    // =========================================================================
    // Initial Load
    // =========================================================================
    // console.log(`DEBUG: Initial load. Requested step: ${stopbadbots_installer_ajax.initial_step}`);
    loadStep(stopbadbots_installer_ajax.initial_step, 'back'); // 'back' prevents sending empty form data
});