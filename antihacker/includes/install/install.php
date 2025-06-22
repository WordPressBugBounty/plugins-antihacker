<?php

/**
 * Anti Hacker - AJAX Installer File
 *
 * This file handles the initial setup process for the Anti Hacker plugin,
 * guiding the user through a multi-step AJAX-powered installation wizard.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}







// Configurações do Plugin StopBadBots
$stopBadBotsConfig = [
    // --- Configurações Gerais ---
    'application_password'       => 'yes',                  // Senha da aplicação ativada
    'bill_show_warnings'         => '2025-06-22',             // Data para exibir avisos do Bill
    'engine_option'              => 'conservative',           // Opção do motor: conservador
    'go_pro_hide'                => '1750000232',             // Ocultar "Go Pro" até esta data/timestamp
    'hide_wp'                    => 'yes',                  // Ocultar caminhos do WordPress
    'inst_experience_level'      => 'manual',               // Nível de experiência da instalação: manual
    'installed'                  => '1744369061',             // Timestamp da instalação
    'keep_log'                   => '360',                  // Manter logs por 360 dias
    'last_checked'               => '1743677864',             // Última verificação (timestamp)
    'last_checked2'              => '1743677864',             // Segunda última verificação (timestamp)
    'last_feedback'              => '1750572747',             // Último feedback (timestamp)
    'show_widget'                => 'yes',                  // Exibir widget
    'version'                    => '11.33',                // Versão do plugin
    'was_activated'              => '1',                    // Plugin foi ativado

    // --- Configurações de Bloqueio e Filtros ---
    'block_all_feeds'            => 'no',                   // Bloquear todos os feeds
    'block_china'                => 'yes',                  // Bloquear tráfego da China
    'block_enumeration'          => 'yes',                  // Bloquear enumeração de usuários/dados
    'block_false_google'         => '',                     // Bloquear falsos Googlebots (vazio, padrão talvez)
    'block_falsegoogle'          => 'yes',                  // Bloquear falsos Googlebots (ativado)
    'block_media_comments'       => 'yes',                  // Bloquear comentários em mídias
    'block_pingbackrequest'      => 'yes',                  // Bloquear solicitações de pingback
    'block_search_plugins'       => 'yes',                  // Bloquear busca por plugins
    'block_search_themes'        => 'yes',                  // Bloquear busca por temas
    'block_spam_comments'        => 'yes',                  // Bloquear comentários de spam
    'block_spam_contacts'        => 'yes',                  // Bloquear contatos de spam
    'block_spam_login'           => 'yes',                  // Bloquear tentativas de login de spam
    'block_tor'                  => 'yes',                  // Bloquear tráfego de TOR
    'Blocked_else_email'         => 'no',                   // Bloqueados (outros e-mails)
    'Blocked_Firewall'           => 'no',                   // Bloqueado pelo Firewall
    'http_tools'                 => '4D_HTTP_Client android-async-http axios andyhttp Aplix akka-http attohttpc curl CakePHP Cowblog DAP/NetHTTP Dispatch fasthttp FireEyeHttpScan Go-http-client Go1.1packagehttp Go 1.1 package http Go http package Go-http-client Gree_HTTP_Loader grequests GuzzleHttp hyp_http_request HTTPConnect http generic Httparty HTTPing http-ping http.rb/ HTTPREAD Java-http-client Jodd HTTP raynette_httprequest java/ kurl Laminas_Http_Client libsoup lua-resty-http mozillacompatible nghttp2 mio_httpc Miro-HttpClient php/ phpscraper PHX HTTP PHX HTTP Client python-requests Python-urllib python-httpx restful rpm-bot RxnetHttp scalaj-http SP-Http-Client Stilo OMHTTP tiehttp Valve/Steam Wget WP-URLDetails Zend_Http_Client ZendHttpClient ', // Lista de User-Agents/ferramentas HTTP a serem bloqueadas/monitoradas
    'my_blacklist'               => '',                     // Minha lista negra personalizada (vazio)
    'my_whitelist'               => '148.71.176.67',          // Minha lista branca personalizada de IPs
    'string_whitelist'           => 'AOL Baidu Bingbot msn DuckDuck facebook GTmetrix google Lighthouse msn paypal Stripe SiteUptime Teoma Yahoo slurp seznam Twitterbot webgazer Yandex ', // Lista branca de strings/User-Agents (para bots bons)
    'update_http_tools'          => 'no',                   // Atualizar lista de ferramentas HTTP

    // --- Configurações de Login e Usuário ---
    'new_user_subscriber'        => 'yes',                  // Bloquear novos usuários como assinantes
    'replace_login_error_msg'    => 'yes',                  // Substituir mensagem de erro de login
    'my_radio_report_all_logins' => 'No',                   // Relatar todos os logins
    'my_radio_report_all_visits' => '',                     // Relatar todas as visitas (vazio)

    // --- Configurações Técnicas/Debug ---
    'checkbox_all_fail'          => '0',                    // Checkbox para falha geral (valor 0)
    'checkversion'               => '',                     // Checar versão (vazio)
    'debug_is_true'              => 'no',                   // Modo debug
    'disable_sitemap'            => 'yes',                  // Desativar sitemap
    'disallow_file_edit'         => 'yes',                  // Desabilitar edição de arquivos via WP admin
    'firewall'                   => '',                     // Firewall (vazio, talvez em uso para detecção)
    'my_email_to'                => '',                     // Email para relatórios (vazio)
    'rest_api'                   => 'No',                   // Rest API (desativado)
    'tables_empty'               => 'no',                   // Tabelas vazias (status)
];













//?debug_reset_installer=true
if (function_exists('ini_set')) {
    @ini_set('memory_limit', '256M');
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    @ini_set('max_execution_time', 300);
}
error_reporting(0);
if (defined('WP_DEBUG') && WP_DEBUG && !defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}
remove_all_actions('admin_notices');
remove_all_actions('all_admin_notices');
remove_all_actions('network_admin_notices');
add_filter('wp_get_admin_notice_messages', '__return_empty_array', PHP_INT_MAX);
/**
 * Registers the hidden installer admin page.
 */
function stopbadbots_inst_add_admin_page()
{
    if (get_option('stopbadbots_setup_complete', false)) {
        return;
    }
    add_submenu_page(
        'tools.php', // Changed from index.php to a more appropriate location
        'Stopbadbots Installer',
        'Stopbadbots Installer',
        'manage_options',
        'stopbadbots-installer',
        'stopbadbots_inst_render_installer'
    );
}
add_action('admin_menu', 'stopbadbots_inst_add_admin_page');
/**
 * Enqueues CSS and JS for the installer page.
 * This is now also responsible for localizing the script with necessary data.
 */
function stopbadbots_inst_enqueue_scripts($hook)
{
    // Only load on our installer page
    if ($hook !== 'tools_page_stopbadbots-installer') {
        return;
    }
    // Enqueue CSS
    wp_enqueue_style(
        'stopbadbots-inst-styles',
        STOPBADBOTSURL . 'includes/install/install.css',
        ['dashicons'],
        STOPBADBOTSVERSION
    );
    // Enqueue the new JavaScript file
    wp_enqueue_script(
        'stopbadbots-inst-script',
        STOPBADBOTSURL . 'includes/install/install.js',
        ['jquery'],
        STOPBADBOTSVERSION,
        true // Load in the footer
    );
    // Pass data to the JavaScript file
    wp_localize_script(
        'stopbadbots-inst-script',
        'stopbadbots_installer_ajax', // Object name in JavaScript
        [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('stopbadbots-installer-ajax-nonce'),
            'initial_step' => isset($_GET['step']) ? intval($_GET['step']) : 1,
            'dashboard_url' => admin_url('index.php'),
        ]
    );
}
add_action('admin_enqueue_scripts', 'stopbadbots_inst_enqueue_scripts');
/**
 * The main AJAX handler for the installer.
 * It processes form data and returns the HTML for the next step.
 */
function stopbadbots_ajax_installer_handler()
{
    // 1. Security checks
    check_ajax_referer('stopbadbots-installer-ajax-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }
    // 2. Sanitize incoming data
    $step_to_load = isset($_POST['step_to_load']) ? intval($_POST['step_to_load']) : 1;
    $direction    = isset($_POST['direction']) ? sanitize_key($_POST['direction']) : 'next';
    // 3. Process data or finalization logic if moving forward
    if ($direction === 'next') {
        $step_to_process = $step_to_load - 1;
        switch ($step_to_process) {
            case 1:
            case 2:
            case 3:
                if ($step_to_process === 2) {
                    $experience_level = isset($_POST['stopbadbots_inst_experience_level']) ? sanitize_key($_POST['stopbadbots_inst_experience_level']) : 'one-click';
                    update_option('stopbadbots_inst_experience_level', $experience_level);
                }
                if ($step_to_process === 3) {
                    if ($step_to_process === 3) {

                        // ===================================================================
                        // PARTE 1: Processa os dados do formulário do instalador
                        // ===================================================================

                        // --- Campo de Email ---
                        $email = isset($_POST['stopbadbots_my_email_to']) ? sanitize_email($_POST['stopbadbots_my_email_to']) : '';
                        update_option('stopbadbots_my_email_to', $email);

                        // --- Campo Radio: XML-RPC ---
                        // Tabela: 'Yes', 'Pingback', 'No' (CORRETO)
                        $xmlrpc_allowed = ['Yes', 'Pingback', 'No'];
                        $xmlrpc_default = 'Yes';
                        $xmlrpc_submitted = isset($_POST['my_radio_xml_rpc']) ? $_POST['my_radio_xml_rpc'] : $xmlrpc_default;
                        $xmlrpc_safe = in_array($xmlrpc_submitted, $xmlrpc_allowed, true) ? $xmlrpc_submitted : $xmlrpc_default;
                        update_option('my_radio_xml_rpc', $xmlrpc_safe);

                        // --- Campo: REST API ---
                        // Tabela: 'Yes', 'No' (CORRETO)
                        $rest_api_allowed = ['Yes', 'No'];
                        $rest_api_default = 'No';
                        $rest_api_submitted = isset($_POST['stopbadbots_rest_api']) ? $_POST['stopbadbots_rest_api'] : $rest_api_default;
                        $rest_api_safe = in_array($rest_api_submitted, $rest_api_allowed, true) ? $rest_api_submitted : $rest_api_default;
                        update_option('stopbadbots_rest_api', $rest_api_safe);

                        // --- Campo: Block All Feeds ---
                        // CORREÇÃO: Tabela mostra 'yes'/'no' em minúsculo para esta opção.
                        $feeds_allowed = ['yes', 'no'];
                        $feeds_default = 'no';
                        $feeds_submitted = isset($_POST['stopbadbots_block_all_feeds']) ? $_POST['stopbadbots_block_all_feeds'] : $feeds_default;
                        $feeds_safe = in_array($feeds_submitted, $feeds_allowed, true) ? $feeds_submitted : $feeds_default;
                        update_option('stopbadbots_block_all_feeds', $feeds_safe);

                        // --- Campo: Show Widget ---
                        // CORREÇÃO: Tabela mostra 'yes'/'no' em minúsculo para esta opção.
                        $widget_allowed = ['yes', 'no'];
                        $widget_default = 'yes';
                        $widget_submitted = isset($_POST['stopbadbots_show_widget']) ? $_POST['stopbadbots_show_widget'] : $widget_default;
                        $widget_safe = in_array($widget_submitted, $widget_allowed, true) ? $widget_submitted : $widget_default;
                        update_option('stopbadbots_show_widget', $widget_safe);

                        // --- Campo de Whitelist (Textarea) ---
                        $whitelist = isset($_POST['stopbadbots_my_whitelist']) ? sanitize_textarea_field($_POST['stopbadbots_my_whitelist']) : '';
                        update_option('stopbadbots_my_whitelist', $whitelist);

                        // --- Campo: Keep Log ---
                        $keep_log_allowed = ['30', '1', '3', '7', '14', '21', '90', '180', '360'];
                        $keep_log_default = '7';
                        $keep_log_submitted = isset($_POST['stopbadbots_keep_log']) ? $_POST['stopbadbots_keep_log'] : $keep_log_default;
                        $keep_log_safe = in_array($keep_log_submitted, $keep_log_allowed, true) ? $keep_log_submitted : $keep_log_default;
                        update_option('stopbadbots_keep_log', $keep_log_safe);

                        // --- Campo: Check Version (Texto) ---
                        $checkversion = isset($_POST['stopbadbots_checkversion']) ? sanitize_text_field($_POST['stopbadbots_checkversion']) : '';
                        update_option('stopbadbots_checkversion', $checkversion);


                        // ===================================================================
                        // PARTE 2: Define as configurações padrão (One-Click Setup)
                        // ===================================================================

                        // CORREÇÃO GERAL: Ajustando todos os valores para corresponderem à tabela ('yes'/'no' minúsculo para a maioria).
                        update_option('stopbadbots_replace_login_error_msg', 'yes');
                        update_option('stopbadbots_disallow_file_edit', 'yes');
                        update_option('stopbadbots_debug_is_true', 'no');
                        update_option('stopbadbots_firewall', 'yes');
                        update_option('stopbadbots_hide_wp', 'yes');
                        update_option('stopbadbots_block_enumeration', 'yes');
                        update_option('stopbadbots_new_user_subscriber', 'yes');
                        update_option('stopbadbots_block_falsegoogle', 'yes');
                        update_option('stopbadbots_block_search_plugins', 'yes');
                        update_option('stopbadbots_block_search_themes', 'yes');
                        update_option('stopbadbots_block_tor', 'yes');
                        update_option('stopbadbots_application_password', 'yes');
                        update_option('stopbadbots_disable_sitemap', 'yes');
                        update_option('stopbadbots_block_media_comments', 'yes');

                        // Opção com capitalização correta conforme a tabela.
                        update_option('stopbadbots_my_radio_report_all_logins', 'No');

                        // CORREÇÃO: Essas opções são RADIO com valores 'yes'/'no', e não checkboxes com '1'/'0'.
                        update_option('stopbadbots_checkbox_all_fail', '0'); // Esta é a única que usa '1'/'0'
                        update_option('stopbadbots_Blocked_Firewall', 'no'); // Tabela mostra 'yes'/'no'
                        update_option('stopbadbots_Blocked_else_email', 'no'); // Tabela mostra 'yes'/'no'
                        update_option('stopbadbots_update_http_tools', 'no'); // Tabela mostra 'yes'/'no'
                    }
                }
                break; // Break after processing steps 1-3
            case 4:
                $experience_level = get_option('stopbadbots_inst_experience_level', 'one-click');
                $redirect_url = ($experience_level === 'one-click')
                    ? admin_url('admin.php?page=anti_hacker_plugin')
                    : admin_url('admin.php?page=anti-hacker');
                // Mark setup as complete
                update_option('stopbadbots_setup_complete', true);
                wp_send_json_success(['redirect' => esc_url_raw($redirect_url)]);
                break; // tecnicamente redundante por causa do wp_send_json_success, mas bom para clareza.
        }
    }
    // 4. Render HTML - Esta parte só é alcançada para os passos 1, 2, 3, 4.
    ob_start();
    stopbadbots_inst_render_step_html($step_to_load);
    $html = ob_get_clean();
    // 5. Send the HTML back to JavaScript.
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_stopbadbots_installer_step', 'stopbadbots_ajax_installer_handler');
/**
 * Renders the main installer shell.
 * The content will be loaded via AJAX.
 */
function stopbadbots_inst_render_installer()
{
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // die(var_dump(__LINE__));

?>
    <div class="stopbadbots-inst-wrap">
        <header class="stopbadbots-inst-header">
            <img id="stopbadbots-inst-logo" alt="stopbadbots Logo" src="<?php echo esc_url(STOPBADBOTSIMAGES . '/logo.png'); ?>" width="250px" />
            <img id="stopbadbots-inst-step-indicator" alt="Step Indicator" src="<?php echo esc_url(STOPBADBOTSIMAGES . '/header-install-step-1.png'); ?>" />
        </header>
        <main id="stopbadbots-inst-content-container" class="stopbadbots-inst-content">
            <!-- Initial content is loaded via JS -->
            <div class="stopbadbots-inst-loader">
                <span class="spinner is-active"></span>
                <p><?php esc_html_e('Loading', 'stopbadbots'); ?>...</p>
            </div>
        </main>
    </div>
    <?php
}
/**
 * Renders the HTML for a specific step.
 * This function can be called on initial load or via AJAX.
 *
 * @param int $step The step number to render.
 */
function stopbadbots_inst_render_step_html($step = 1)
{
    if ($step < 1 || $step > 4) $step = 1;
    switch ($step):
        case 1:
    ?>
            <h1>1.&nbsp;<?php esc_html_e('Welcome', 'stopbadbots'); ?></h1>
            <p><?php esc_html_e('Thank you for choosing StopBadBots plugin, your all-in-one security solution to harden and protect your WordPress site. It provides multi-layered defense against a wide range of attacks, featuring a robust firewall, malware scanner, and advanced login security. We also block common vulnerabilities by disabling user enumeration, TOR access, XML-RPC, REST API, and much more.', 'stopbadbots'); ?></p>
            <p><?php esc_html_e('Please follow steps 1 through 4 to complete the plugin installation. This installer will allow you to install the software more securely, easily, and quickly.', 'stopbadbots'); ?></p>
            <p>
                <?php
                printf(
                    wp_kses(
                        __('By using our plugins and themes, you agree to the <a href="%s" target="_blank" rel="noopener noreferrer">terms of use</a>.', 'stopbadbots'),
                        ['a' => ['href' => [], 'target' => [], 'rel' => []]]
                    ),
                    esc_url('https://siterightaway.net/terms-of-use-of-our-plugins-and-themes/')
                );
                ?>
            <form id="stopbadbots-installer-form" data-step="1">
                <div class="stopbadbots-inst-buttons">
                    <button type="submit" class="stopbadbots-inst-button stopbadbots-inst-next">Next</button>
                </div>
            </form>
        <?php
            break;
        case 2:
            $experience_level = get_option('stopbadbots_inst_experience_level', 'one-click');
        ?>
            <h1>2.&nbsp;<?php esc_html_e('Your Experience Level', 'stopbadbots'); ?></h1>
            <p><?php esc_html_e('What is your level of experience with WordPress? This will help us tailor the setup process for you. You can always change this in the future.', 'stopbadbots'); ?></p>
            <form id="stopbadbots-installer-form" data-step="2">
                <label>
                    <input type="radio" name="stopbadbots_inst_experience_level" value="one-click" <?php checked($experience_level, 'one-click'); ?> />
                    One-Click Setup (I'm a beginner, set it up for me!)
                </label>
                <p class="stopbadbots-inst-description">We'll automatically apply the best-practice settings for you...</p>
                <label>
                    <input type="radio" name="stopbadbots_inst_experience_level" value="manual" <?php checked($experience_level, 'manual'); ?> />
                    Manual Setup (I'm an experienced user, I want to configure it myself.)
                </label>
                <p class="stopbadbots-inst-description">You will be able to configure all the basic and advanced settings manually...</p>
                <div class="stopbadbots-inst-buttons">
                    <button type="button" class="stopbadbots-inst-button stopbadbots-inst-back" data-step="1">Back</button>
                    <button type="submit" class="stopbadbots-inst-button stopbadbots-inst-next">Next</button>
                </div>
            </form>
        <?php
            break;
        case 3:

            $stopbadbots_ip = stopbadbots_get_installer_ip();

            $whitelist_string   = get_option('stopbadbots_my_whitelist', '');
            $whitelist_array    = array_filter(array_map('trim', explode("\n", $whitelist_string)));
            if (!in_array($stopbadbots_ip, $whitelist_array)) {
                $whitelist_array[]    = $stopbadbots_ip;
                $new_whitelist_string = implode("\n", $whitelist_array);
                update_option('stopbadbots_my_whitelist', $new_whitelist_string);
            }

            // ===================================================================
            // CORREÇÃO: Definindo os padrões de get_option() para corresponderem
            // exatamente à tabela definitiva (maiúsculas vs. minúsculas).
            // ===================================================================
            $my_email_to           = get_option('stopbadbots_my_email_to', get_option('admin_email'));
            $xml_rpc               = get_option('my_radio_xml_rpc', 'Yes'); // Tabela: Yes/No
            $rest_api              = get_option('stopbadbots_rest_api', 'No'); // Tabela: Yes/No
            $block_all_feeds       = get_option('stopbadbots_block_all_feeds', 'no'); // Tabela: yes/no
            $show_widget           = get_option('stopbadbots_show_widget', 'yes'); // Tabela: yes/no
            $whitelist_for_display = get_option('stopbadbots_my_whitelist', '');
            $keep_log              = get_option('stopbadbots_keep_log', '7');
            $checkversion          = get_option('stopbadbots_checkversion', '');
        ?>
            <h1>3. <?php esc_html_e('Basic Information', 'stopbadbots'); ?></h1>
            <p><?php esc_html_e('Please fill in and answer all fields.', 'stopbadbots'); ?></p>
            <form id="stopbadbots-installer-form" data-step="3">
                <div class="stopbadbots-inst-field">
                    <label for="stopbadbots_my_email_to"><?php esc_html_e('Email to send notifications. Leave blank to use your default WordPress email.', 'stopbadbots'); ?></label>
                    <input type="email" id="stopbadbots_my_email_to" name="stopbadbots_my_email_to" value="<?php echo esc_attr($my_email_to); ?>" />
                </div>

                <!-- =================================================================== -->
                <!-- CORREÇÃO: Harmonizando o 'value' e o 'checked()' com a tabela. -->
                <!-- =================================================================== -->

                <div class="stopbadbots-inst-field">
                    <h3>
                        <?php esc_html_e('Disable XML-RPC', 'stopbadbots'); ?>
                        <a href="<?php echo esc_url('https://antihackerplugin.com/what-means-xml-rpc-should-i-disable-it/'); ?>" target="_blank" rel="noopener noreferrer" class="stopbadbots-inst-learn-more" aria-label="<?php esc_attr_e('Learn more about disabling XML-RPC', 'stopbadbots'); ?>">
                            <img src="<?php echo esc_url(STOPBADBOTSIMAGES . '/info-icon.png'); ?>" alt="<?php esc_attr_e('Information icon', 'stopbadbots'); ?>" class="stopbadbots-inst-info-icon" width="16" height="16" />
                            <?php esc_html_e('Learn more', 'stopbadbots'); ?>
                        </a>
                    </h3>
                    <p><?php esc_html_e('Disabling XML-RPC in WordPress boosts security. It primarily stops brute-force login attacks and prevents DDoS attacks via pingbacks. Since the REST API is now the standard, XML-RPC is largely outdated. Disable it unless an old app specifically needs it.', 'stopbadbots'); ?></p>
                    <!-- Tabela: Yes/No (Maiúsculas) -->
                    <label><input type="radio" name="my_radio_xml_rpc" value="Yes" <?php checked($xml_rpc, 'Yes'); ?> /> <?php esc_html_e('Yes', 'stopbadbots'); ?></label>
                    <label><input type="radio" name="my_radio_xml_rpc" value="No" <?php checked($xml_rpc, 'No'); ?> /> <?php esc_html_e('No', 'stopbadbots'); ?></label>
                </div>

                <div class="stopbadbots-inst-field">
                    <h3>
                        <?php esc_html_e('Disable JSON WordPress REST API', 'stopbadbots'); ?>
                        <a href="<?php echo esc_url('https://antihackerplugin.com/why-disable-json-wordpress-rest-api/'); ?>" target="_blank" rel="noopener noreferrer" class="stopbadbots-inst-learn-more" aria-label="<?php esc_attr_e('Learn more about disabling the REST API', 'stopbadbots'); ?>">
                            <img src="<?php echo esc_url(STOPBADBOTSIMAGES . '/info-icon.png'); ?>" alt="<?php esc_attr_e('Information icon', 'stopbadbots'); ?>" class="stopbadbots-inst-info-icon" width="16" height="16" />
                            <?php esc_html_e('Learn more', 'stopbadbots'); ?>
                        </a>
                    </h3>
                    <p><?php esc_html_e('Disabling the WordPress REST API is a drastic security step, often not recommended, as it breaks many core functions (like the Gutenberg editor). Only disable it if you understand the implications and your site doesn\'t rely on API-dependent features.', 'stopbadbots'); ?></p>
                    <!-- Tabela: Yes/No (Maiúsculas) -->
                    <label><input type="radio" name="stopbadbots_rest_api" value="Yes" <?php checked($rest_api, 'Yes'); ?> /> <?php esc_html_e('Yes', 'stopbadbots'); ?></label>
                    <label><input type="radio" name="stopbadbots_rest_api" value="No" <?php checked($rest_api, 'No'); ?> /> <?php esc_html_e('No', 'stopbadbots'); ?></label>
                </div>

                <div class="stopbadbots-inst-field">
                    <h3>
                        <?php esc_html_e('Block all Feeds to avoid bot exploitation', 'stopbadbots'); ?>
                        <a href="<?php echo esc_url('https://antihackerplugin.com/why-to-block-all-feeds/'); ?>" target="_blank" rel="noopener noreferrer" class="stopbadbots-inst-learn-more" aria-label="<?php esc_attr_e('Learn more about blocking feeds', 'stopbadbots'); ?>">
                            <img src="<?php echo esc_url(STOPBADBOTSIMAGES . '/info-icon.png'); ?>" alt="<?php esc_attr_e('Information icon', 'stopbadbots'); ?>" class="stopbadbots-inst-info-icon" width="16" height="16" />
                            <?php esc_html_e('Learn more', 'stopbadbots'); ?>
                        </a>
                    </h3>
                    <p><?php esc_html_e('Blocking RSS/Atom feeds can prevent bots from scraping content and discovering usernames, slightly improving security if you don\'t use feeds for syndication.', 'stopbadbots'); ?></p>
                    <!-- Tabela: yes/no (Minúsculas) -->
                    <label><input type="radio" name="stopbadbots_block_all_feeds" value="yes" <?php checked($block_all_feeds, 'yes'); ?> /> <?php esc_html_e('Yes', 'stopbadbots'); ?></label>
                    <label><input type="radio" name="stopbadbots_block_all_feeds" value="no" <?php checked($block_all_feeds, 'no'); ?> /> <?php esc_html_e('No', 'stopbadbots'); ?></label>
                </div>

                <div class="stopbadbots-inst-field">
                    <h3><?php esc_html_e('Show StopBadBots Widget on the main Dashboard (Admin users only)', 'stopbadbots'); ?></h3>
                    <!-- Tabela: yes/no (Minúsculas) -->
                    <label><input type="radio" name="stopbadbots_show_widget" value="yes" <?php checked($show_widget, 'yes'); ?> /> <?php esc_html_e('Yes', 'stopbadbots'); ?></label>
                    <label><input type="radio" name="stopbadbots_show_widget" value="no" <?php checked($show_widget, 'no'); ?> /> <?php esc_html_e('No', 'stopbadbots'); ?></label>
                </div>

                <div class="stopbadbots-inst-field">
                    <label for="stopbadbots_my_whitelist"><?php esc_html_e('Add IPs that can access the admin area without email authentication (one per line).', 'stopbadbots'); ?></label>
                    <textarea id="stopbadbots_my_whitelist" name="stopbadbots_my_whitelist" rows="5"><?php echo esc_textarea($whitelist_for_display); ?></textarea>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: Current user IP address. */
                            esc_html__('Your current IP address is %s. It has been automatically added to the whitelist for your convenience.', 'stopbadbots'),
                            '<strong>' . esc_html($stopbadbots_ip) . '</strong>'
                        );
                        ?>
                    </p>
                </div>
                <div class="stopbadbots-inst-field">
                    <h3><?php esc_html_e('How long to keep visitor logs?', 'stopbadbots'); ?></h3>
                    <p><?php esc_html_e('If you have heavy traffic, select 1 day. Your choices may affect the blocked visits log.', 'stopbadbots'); ?></p>
                    <select id="stopbadbots_log" name="stopbadbots_keep_log">
                        <option value="1" <?php selected($keep_log, '1'); ?>><?php esc_html_e('1 day', 'stopbadbots'); ?></option>
                        <option value="3" <?php selected($keep_log, '3'); ?>><?php esc_html_e('3 days', 'stopbadbots'); ?></option>
                        <option value="7" <?php selected($keep_log, '7'); ?>><?php esc_html_e('7 days', 'stopbadbots'); ?></option>
                        <option value="14" <?php selected($keep_log, '14'); ?>><?php esc_html_e('14 days', 'stopbadbots'); ?></option>
                        <option value="21" <?php selected($keep_log, '21'); ?>><?php esc_html_e('21 days', 'stopbadbots'); ?></option>
                        <option value="30" <?php selected($keep_log, '30'); ?>><?php esc_html_e('30 days', 'stopbadbots'); ?></option>
                        <option value="90" <?php selected($keep_log, '90'); ?>><?php esc_html_e('90 days', 'stopbadbots'); ?></option>
                        <option value="180" <?php selected($keep_log, '180'); ?>><?php esc_html_e('180 days', 'stopbadbots'); ?></option>
                        <option value="360" <?php selected($keep_log, '360'); ?>><?php esc_html_e('360 days', 'stopbadbots'); ?></option>
                    </select>
                </div>
                <div class="stopbadbots-inst-field">
                    <h3><?php esc_html_e('Purchase Code', 'stopbadbots'); ?></h3>
                    <p><?php esc_html_e('Paste the Item Purchase Code received via email when you purchased the premium version. Or leave blank for the free version.', 'stopbadbots'); ?></p>
                    <input type="text" id="stopbadbots_checkversion" name="stopbadbots_checkversion" value="<?php echo esc_attr($checkversion); ?>" maxlength="30" />
                </div>
                <div class="stopbadbots-inst-buttons">
                    <button type="button" class="stopbadbots-inst-button stopbadbots-inst-back" data-step="2"><?php esc_html_e('Back', 'stopbadbots'); ?></button>
                    <button type="submit" class="stopbadbots-inst-button stopbadbots-inst-next"><?php esc_html_e('Next', 'stopbadbots'); ?></button>
                </div>
            </form>
        <?php
            break;

        case 4:
            $experience_level = get_option('stopbadbots_inst_experience_level', 'one-click');
        ?>
            <form id="stopbadbots-installer-form" data-step="4">
                <?php if ($experience_level === 'one-click') : ?>
                    <h1>4. <?php esc_html_e('All Done!', 'stopbadbots'); ?></h1>
                    <p><?php esc_html_e('StopBadBots has been successfully configured with our recommended settings! You are all set. You can visit your dashboard now or fine-tune the options anytime from the plugin\'s settings menu.', 'stopbadbots'); ?></p>
                    <div class="stopbadbots-inst-buttons">
                        <button type="button" class="stopbadbots-inst-button stopbadbots-inst-back" data-step="3"><?php esc_html_e('Back', 'stopbadbots'); ?></button>
                        <button type="submit" class="stopbadbots-inst-button stopbadbots-inst-next"><?php esc_html_e('Go to Dashboard', 'stopbadbots'); ?></button>
                    </div>
                <?php else : // This is for 'manual' setup 
                ?>
                    <h1>4. <?php esc_html_e('Ready for Manual Configuration', 'stopbadbots'); ?></h1>
                    <p><?php esc_html_e('Great! Your initial information has been saved. The basics are configured and ready to go. Please proceed to the settings dashboard to fine-tune options and explore all available features.', 'stopbadbots'); ?></p>
                    <div class="stopbadbots-inst-buttons">
                        <button type="button" class="stopbadbots-inst-button stopbadbots-inst-back" data-step="3"><?php esc_html_e('Back', 'stopbadbots'); ?></button>
                        <button type="submit" class="stopbadbots-inst-button stopbadbots-inst-next"><?php esc_html_e('Go to Settings Dashboard', 'stopbadbots'); ?></button>
                    </div>
                <?php endif; ?>
            </form>
<?php
            break;
    endswitch;
}
/**
 * Safely gets the current user's IP address.
 *
 * @return string The sanitized IP address, or an empty string if invalid.
 */
function stopbadbots_get_installer_ip()
{
    $raw_ip = '';
    if (function_exists('stopbadbots_findip')) {
        $raw_ip = stopbadbots_findip();
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $raw_ip = $_SERVER['REMOTE_ADDR'];
    }
    $sanitized_ip = filter_var(trim($raw_ip), FILTER_VALIDATE_IP);
    return ($sanitized_ip) ? $sanitized_ip : '';
}
/**
 * AJAX endpoint minimalista para forçar a conclusão do instalador.
 *
 * Esta função é chamada pelo JavaScript quando a chamada principal do instalador
 * falha (ex: erro 500). Sua única responsabilidade é marcar a instalação
 * como concluída para que o usuário não fique preso no wizard.
 */
/*
function stopbadbots_ajax_force_complete_installer() {
    // 1. Verificação de Segurança: Garante que a requisição é legítima.
    //    Usamos o mesmo nonce da ação principal para simplificar.
    //    O 'false' no final previne que a função morra (die) e permite que nós controlemos a resposta.
    if (
        !check_ajax_referer('stopbadbots-installer-ajax-nonce', 'nonce', false) ||
        !current_user_can('manage_options')
    ) {
        // Se a segurança falhar, enviamos uma resposta de erro.
        // O JavaScript vai redirecionar de qualquer maneira, mas é uma boa prática.
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }

    // 2. Ação Principal: Atualiza a opção no banco de dados.
    update_option('stopbadbots_setup_complete', true);

    // 3. Resposta de Sucesso: Informa ao JavaScript que a operação foi bem-sucedida.
    wp_send_json_success(['message' => 'Installer marked as complete.']);
}
add_action('wp_ajax_stopbadbots_force_complete', 'stopbadbots_ajax_force_complete_installer');
*/
