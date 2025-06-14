<?php

/**
 * Anti Hacker - Installer File
 *
 * This file handles the initial setup process for the Anti Hacker plugin,
 * guiding the user through a multi-step installation wizard.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


// ======================= DEBUGGING CODE - START =======================
// To test, add &debug_reset_installer=true to an admin panel URL.
if (isset($_GET['debug_reset_installer']) && $_GET['debug_reset_installer'] === 'true' && current_user_can('manage_options')) {
    delete_option('antihacker_setup_complete');
    set_transient('antihacker_redirect_to_installer', true, 30);
    die();
}
// Exemplo: https://seusite.com/wp-admin/index.php?debug_reset_installer=true
// ======================= DEBUGGING CODE - END =========================


if (function_exists('ini_set')) {
    @ini_set('memory_limit', '256M'); // ou '512M', '1G', etc.
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    @ini_set('max_execution_time', 300); // 300 segundos = 5 minutos
}

error_reporting(0);

if (defined('WP_DEBUG') && WP_DEBUG) {
        if (!defined('WP_DEBUG_DISPLAY')) {
            define('WP_DEBUG_DISPLAY', false);
        }
    }


// Suprimir todas as mensagens administrativas e notificações
remove_all_actions('admin_notices');
remove_all_actions('all_admin_notices');
remove_all_actions('network_admin_notices');
add_filter('wp_get_admin_notice_messages', '__return_empty_array', PHP_INT_MAX);

/*
// Bloquear notificações de atualização do WordPress, plugins e temas
add_filter('pre_site_transient_update_core', '__return_null', PHP_INT_MAX);
add_filter('site_transient_update_core', '__return_null', PHP_INT_MAX);
add_filter('pre_site_transient_update_plugins', '__return_null', PHP_INT_MAX);
add_filter('site_transient_update_plugins', '__return_null', PHP_INT_MAX);
add_filter('pre_site_transient_update_themes', '__return_null', PHP_INT_MAX);
add_filter('site_transient_update_themes', '__return_null', PHP_INT_MAX);
*/




/**
 * =================================================================
 * INSTALLER ARCHITECTURE (HOOKS & REDIRECTION)
 * =================================================================
 */

/**
 * Sets a transient on plugin activation to trigger the installer redirect.
 * REMINDER: The `register_activation_hook(__FILE__, 'antihacker_activate_plugin');`
 * line must be in your main plugin file (e.g., antihacker.php).
 */
function antihacker_activate_plugin()
{
    set_transient('antihacker_redirect_to_installer', true, 30);
}

/**
 * Redirects the user to the installer page for the first time.
 * This runs on 'admin_init'.
 */
function antihacker_installer_redirect()
{
    // Don't redirect if we are already on the installer page.
    if (isset($_GET['page']) && $_GET['page'] === 'antihacker-installer') {
        return;
    }

    // Don't redirect if the transient doesn't exist.
    if (!get_transient('antihacker_redirect_to_installer')) {
        return;
    }

    // Delete the transient to prevent redirect loops.
    delete_transient('antihacker_redirect_to_installer');

    // Perform the redirect only for users with the right capabilities and not during an AJAX request.
    if (!wp_doing_ajax() && current_user_can('manage_options')) {
        wp_safe_redirect(admin_url('admin.php?page=antihacker-installer'));
        exit;
    }
}
add_action('admin_init', 'antihacker_installer_redirect');

/**
 * Registers the hidden installer admin page.
 * It will not be added if the setup is already marked as complete.
 */
function antihacker_inst_add_admin_page()
{
    if (get_option('antihacker_setup_complete', false)) {
        return;
    }
    add_submenu_page(
        'antihacker-installer',
        __('AntiHacker Installer', 'antihacker'),
        __('AntiHacker Installer', 'antihacker'),
        'manage_options',
        'antihacker-installer',
        'antihacker_inst_render_installer'
    );
}
add_action('admin_menu', 'antihacker_inst_add_admin_page');

/**
 * Enqueues CSS styles specifically for the installer page.
 */
function antihacker_inst_enqueue_styles()
{
    if (isset($_GET['page']) && $_GET['page'] === 'antihacker-installer') {
        // Assuming ANTIHACKERURL and ANTIHACKERVERSION constants are defined elsewhere.

        // Only load these styles on our specific installer page.

        wp_enqueue_style(
            'antihacker-inst-styles',
            ANTIHACKERURL . 'includes/install/install.css',
            array('dashicons'),
            ANTIHACKERVERSION
        );
    }
}
add_action('admin_enqueue_scripts', 'antihacker_inst_enqueue_styles');

/**
 * =================================================================
 * [NEW] JAVASCRIPT REDIRECT FALLBACK (Requirement 5)
 * This function checks for a redirect transient on admin pages.
 * If found, it means the PHP redirect probably failed, so it injects
 * JavaScript to perform the redirect on the client side.
 * =================================================================
 */
function antihacker_js_redirect_fallback()
{
    // Check if our redirect transient exists.
    $redirect_url = get_transient('antihacker_redirect_fallback');

    if ($redirect_url) {
        // IMPORTANT: Delete the transient immediately to prevent redirect loops.
        delete_transient('antihacker_redirect_fallback');

        // Echo the JavaScript to perform the redirect. Use esc_url_raw for URLs in scripts.
        echo '<script type="text/javascript">window.location.href = "' . esc_url_raw($redirect_url) . '";</script>';
        // Provide a noscript fallback for browsers with JavaScript disabled.
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url($redirect_url) . '" /></noscript>';
    }
}
add_action('admin_head', 'antihacker_js_redirect_fallback');


/**
 * =================================================================
 * FORM PROCESSING FUNCTION (The Correct Approach)
 * This function runs early on 'admin_init' to handle all data saving
 * and redirection logic. This is the ideal place for wp_safe_redirect.
 * =================================================================
 */
function antihacker_process_installer_form()
{
    // Basic checks to ensure we are on the correct page and context.
    if (!isset($_GET['page']) || $_GET['page'] !== 'antihacker-installer' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['antihacker_inst_nonce'])) {
        return;
    }

    if (!current_user_can('manage_options')) {
        // [MODIFIED] Added translation and sanitization. (Requirements 2 & 3)
        wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'antihacker'));
    }

    $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
    if ($step < 1 || $step > 4) $step = 1;

    // [MODIFIED] Added translation and sanitization. (Requirements 2 & 3)
    if (!wp_verify_nonce($_POST['antihacker_inst_nonce'], 'antihacker_inst_form_step_' . $step)) {
        wp_die(esc_html__('Security check failed. Please go back and try again.', 'antihacker'));
    }

    // Save data based on the current step.
    if ($step === 2) {
        $experience_level = isset($_POST['antihacker_inst_experience_level']) ? sanitize_key($_POST['antihacker_inst_experience_level']) : 'one-click';
        update_option('antihacker_inst_experience_level', $experience_level);
    } elseif ($step === 3) {
        // All options are sanitized upon saving.
        update_option('antihacker_my_email_to', isset($_POST['antihacker_my_email_to']) ? sanitize_email($_POST['antihacker_my_email_to']) : '');
        update_option('my_radio_xml_rpc', isset($_POST['my_radio_xml_rpc']) ? sanitize_key($_POST['my_radio_xml_rpc']) : 'yes');
        update_option('antihacker_rest_api', isset($_POST['antihacker_rest_api']) ? sanitize_key($_POST['antihacker_rest_api']) : 'no');
        update_option('antihacker_block_all_feeds', isset($_POST['antihacker_block_all_feeds']) ? sanitize_key($_POST['antihacker_block_all_feeds']) : 'no');
        update_option('antihacker_show_widget', isset($_POST['antihacker_show_widget']) ? sanitize_key($_POST['antihacker_show_widget']) : 'yes');
        update_option('antihacker_my_whitelist', isset($_POST['antihacker_my_whitelist']) ? sanitize_textarea_field($_POST['antihacker_my_whitelist']) : '');
        update_option('antihacker_keep_log', isset($_POST['antihacker_keep_log']) ? sanitize_key($_POST['antihacker_keep_log']) : '7');
        update_option('antihacker_checkversion', isset($_POST['antihacker_checkversion']) ? sanitize_text_field($_POST['antihacker_checkversion']) : '');
    }

    // Redirect to the next page or finish.
    if ($step === 4) {
        // Set default options on the final step.
        $default_options = [
            'antihacker_replace_login_error_msg' => 'yes', 'antihacker_disallow_file_edit' => 'yes', 'antihacker_debug_is_true' => 'no',
            'antihacker_firewall' => 'yes', 'antihacker_hide_wp' => 'yes', 'antihacker_block_enumeration' => 'yes', 'antihacker_new_user_subscriber' => 'yes',
            'antihacker_block_falsegoogle' => 'yes', 'antihacker_block_false_google' => 'yes', 'antihacker_block_search_plugins' => 'yes',
            'antihacker_block_search_themes' => 'yes', 'antihacker_block_tor' => 'yes', 'antihacker_application_password' => 'yes',
            'antihacker_disable_sitemap' => 'yes', 'antihacker_my_radio_report_all_logins' => 'no', 'antihacker_checkbox_all_fail' => 'no',
            'antihacker_Blocked_Firewall' => 'no', 'antihacker_Blocked_else_email' => 'no', 'antihacker_update_http_tools' => 'no',
        ];
        if (!empty(get_option('antihacker_checkversion', ''))) {
            $default_options = array_merge($default_options, [
                'antihacker_block_http_tools' => 'yes', 'antihacker_blank_ua' => 'yes', 'antihacker_radio_limit_visits' => 'yes',
                'antihacker_rate_limiting_day' => '50', 'antihacker_rate_limiting' => '10', 'antihacker_rate404_limiting' => '10',
            ]);
        }
        foreach ($default_options as $key => $value) {
            update_option($key, $value);
        }

        update_option('antihacker_setup_complete', true);

        $experience_level = get_option('antihacker_inst_experience_level', 'one-click');
        $redirect_url = ($experience_level === 'one-click')
            ? admin_url('admin.php?page=anti_hacker_plugin')
            : admin_url('admin.php?page=anti-hacker');

        // [MODIFIED] Set transient before redirect. (Requirement 5)
        set_transient('antihacker_redirect_fallback', $redirect_url, 60);
        wp_safe_redirect($redirect_url);
        exit;
    } else {
        $next_step = $step + 1;
        $redirect_url = admin_url('admin.php?page=antihacker-installer&step=' . $next_step);

        // [MODIFIED] Set transient before redirect. (Requirement 5)
        set_transient('antihacker_redirect_fallback', $redirect_url, 60);
        wp_safe_redirect($redirect_url);
        exit;
    }
}
add_action('admin_init', 'antihacker_process_installer_form');


/**
 * =================================================================
 * HTML RENDERING FUNCTION
 * This function now ONLY displays the HTML. All processing was moved.
 * =================================================================
 */
function antihacker_inst_render_installer()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'antihacker'));
    }
    $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
    if ($step < 1 || $step > 4) $step = 1;

?>
    <div class="antihacker-inst-wrap">
        <header class="antihacker-inst-header">
            <img id="antihacker-inst-logo" alt="<?php esc_attr_e('AntiHacker Logo', 'antihacker'); ?>" src="<?php echo esc_url(ANTIHACKERIMAGES . '/logo.png'); ?>" width="250px" />
            <img id="antihacker-inst-step-indicator" alt="<?php esc_attr_e('Step Indicator', 'antihacker'); ?>" src="<?php echo esc_url(ANTIHACKERIMAGES . '/header-install-step-' . $step . '.png'); ?>" />
        </header>
        <main class="antihacker-inst-content">
            <?php
            // [MODIFIED] All strings are now translatable and sanitized. (Requirements 2, 3, 4)
            switch ($step):
                case 1:
            ?>
                    <h1>1.&nbsp;<?php esc_html_e('Welcome', 'antihacker'); ?></h1>
                    <p><?php esc_html_e('Thank you for choosing AntiHacker plugin, your all-in-one security solution to harden and protect your WordPress site. It provides multi-layered defense against a wide range of attacks, featuring a robust firewall, malware scanner, and advanced login security. We also block common vulnerabilities by disabling user enumeration, TOR access, XML-RPC, REST API, and much more.', 'antihacker'); ?></p>
                    <p><?php esc_html_e('Please follow steps 1 through 4 to complete the plugin installation. This installer will allow you to install the software more securely, easily, and quickly.', 'antihacker'); ?></p>
                    <p>
                        <?php
                        printf(
                            wp_kses(
                                __('By using our plugins and themes, you agree to the <a href="%s" target="_blank" rel="noopener noreferrer">terms of use</a>.', 'antihacker'),
                                ['a' => ['href' => [], 'target' => [], 'rel' => []]]
                            ),
                            esc_url('https://siterightaway.net/terms-of-use-of-our-plugins-and-themes/')
                        );
                        ?>
                    </p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=antihacker-installer&step=1')); ?>">
                        <?php wp_nonce_field('antihacker_inst_form_step_1', 'antihacker_inst_nonce'); ?>
                        <div class="antihacker-inst-buttons">
                            <button type="submit" class="antihacker-inst-button antihacker-inst-next"><?php esc_html_e('Next', 'antihacker'); ?></button>
                        </div>
                    </form>
                <?php
                    break;
                case 2:
                    $experience_level = get_option('antihacker_inst_experience_level', 'one-click');
                ?>
                    <h1>2.&nbsp;<?php esc_html_e('Your Experience Level', 'antihacker'); ?></h1>
                    <p><?php esc_html_e('What is your level of experience with WordPress? This will help us tailor the setup process for you. You can always change this in the future.', 'antihacker'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=antihacker-installer&step=2')); ?>">
                        <?php wp_nonce_field('antihacker_inst_form_step_2', 'antihacker_inst_nonce'); ?>
                        <label>
                            <input type="radio" name="antihacker_inst_experience_level" value="one-click" <?php checked($experience_level, 'one-click'); ?> />
                            <?php esc_html_e('One-Click Setup (I\'m a beginner, set it up for me!)', 'antihacker'); ?>
                        </label>
                        <p class="antihacker-inst-description"><?php esc_html_e('We\'ll automatically apply the best-practice settings for you. It\'s the fastest way to get started.', 'antihacker'); ?></p>
                        <label>
                            <input type="radio" name="antihacker_inst_experience_level" value="manual" <?php checked($experience_level, 'manual'); ?> />
                            <?php esc_html_e('Manual Setup (I\'m an experienced user, I want to configure it myself.)', 'antihacker'); ?>
                        </label>
                        <p class="antihacker-inst-description"><?php esc_html_e('You will be able to configure all the basic and advanced settings manually from the plugin\'s dashboard.', 'antihacker'); ?></p>
                        <div class="antihacker-inst-buttons">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=antihacker-installer&step=1')); ?>" class="antihacker-inst-button antihacker-inst-back"><?php esc_html_e('Back', 'antihacker'); ?></a>
                            <button type="submit" class="antihacker-inst-button antihacker-inst-next"><?php esc_html_e('Next', 'antihacker'); ?></button>
                        </div>
                    </form>
                <?php
                    break;
                case 3:
                    // [MODIFIED] Requirement 1: Add current user's IP to the whitelist if it's not there.
                    // This happens BEFORE we get the value to display in the form.
                    // Note: $_SERVER['REMOTE_ADDR'] is a basic way. For sites behind proxies/load balancers,
                    // you might need a more advanced function to find the real IP.
                    $antihacker_ip      = $_SERVER['REMOTE_ADDR'];
                    $whitelist_string   = get_option('antihacker_my_whitelist', '');
                    $whitelist_array    = array_filter(array_map('trim', explode("\n", $whitelist_string)));

                    if (!in_array($antihacker_ip, $whitelist_array)) {
                        $whitelist_array[]    = $antihacker_ip;
                        $new_whitelist_string = implode("\n", $whitelist_array);
                        update_option('antihacker_my_whitelist', $new_whitelist_string);
                    }

                    // Now, get the final, potentially updated, values to display in the form.
                    $my_email_to = get_option('antihacker_my_email_to', get_option('admin_email'));
                    $xml_rpc = get_option('my_radio_xml_rpc', 'yes');
                    $rest_api = get_option('antihacker_rest_api', 'no');
                    $block_all_feeds = get_option('antihacker_block_all_feeds', 'no');
                    $show_widget = get_option('antihacker_show_widget', 'yes');
                    $whitelist_for_display = get_option('antihacker_my_whitelist', ''); // Get the fresh value.
                    $keep_log = get_option('antihacker_keep_log', '7');
                    $checkversion = get_option('antihacker_checkversion', '');
                ?>
                    <h1>3.&nbsp;<?php esc_html_e('Basic Information', 'antihacker'); ?></h1>
                    <p><?php esc_html_e('Please fill in and answer all fields.', 'antihacker'); ?></p>

                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=antihacker-installer&step=3')); ?>">
                        <?php wp_nonce_field('antihacker_inst_form_step_3', 'antihacker_inst_nonce'); ?>

                        <div class="antihacker-inst-field">
                            <label for="antihacker_my_email_to"><?php esc_html_e('Email to send notifications. Leave blank to use your default WordPress email.', 'antihacker'); ?></label>
                            <input type="email" id="antihacker_my_email_to" name="antihacker_my_email_to" value="<?php echo esc_attr($my_email_to); ?>" />
                        </div>
                        <div class="antihacker-inst-field">
                            <h3>
                                <?php esc_html_e('Disable XML-RPC', 'antihacker'); ?>
                                <a href="<?php echo esc_url('https://antihackerplugin.com/what-means-xml-rpc-should-i-disable-it/'); ?>" target="_blank" rel="noopener noreferrer" class="antihacker-inst-learn-more" aria-label="<?php esc_attr_e('Learn more about disabling XML-RPC', 'antihacker'); ?>">
                                    <img src="<?php echo esc_url(ANTIHACKERIMAGES . '/info-icon.png'); ?>" alt="<?php esc_attr_e('Information icon', 'antihacker'); ?>" class="antihacker-inst-info-icon" width="16" height="16" />
                                    <?php esc_html_e('Learn more', 'antihacker'); ?>
                                </a>
                            </h3>
                            <p><?php esc_html_e('Disabling XML-RPC in WordPress boosts security. It primarily stops brute-force login attacks and prevents DDoS attacks via pingbacks. Since the REST API is now the standard, XML-RPC is largely outdated. Disable it unless an old app specifically needs it.', 'antihacker'); ?></p>
                            <label><input type="radio" name="my_radio_xml_rpc" value="yes" <?php checked($xml_rpc, 'yes'); ?> /> <?php esc_html_e('Yes', 'antihacker'); ?></label>
                            <label><input type="radio" name="my_radio_xml_rpc" value="no" <?php checked($xml_rpc, 'no'); ?> /> <?php esc_html_e('No', 'antihacker'); ?></label>
                        </div>

                        <div class="antihacker-inst-field">
                            <h3>
                                <?php esc_html_e('Disable JSON WordPress REST API', 'antihacker'); ?>
                                <a href="<?php echo esc_url('https://antihackerplugin.com/why-disable-json-wordpress-rest-api/'); ?>" target="_blank" rel="noopener noreferrer" class="antihacker-inst-learn-more" aria-label="<?php esc_attr_e('Learn more about disabling the REST API', 'antihacker'); ?>">
                                    <img src="<?php echo esc_url(ANTIHACKERIMAGES . '/info-icon.png'); ?>" alt="<?php esc_attr_e('Information icon', 'antihacker'); ?>" class="antihacker-inst-info-icon" width="16" height="16" />
                                    <?php esc_html_e('Learn more', 'antihacker'); ?>
                                </a>
                            </h3>
                            <p><?php esc_html_e('Disabling the WordPress REST API is a drastic security step, often not recommended, as it breaks many core functions (like the Gutenberg editor). Only disable it if you understand the implications and your site doesn\'t rely on API-dependent features.', 'antihacker'); ?></p>
                            <label><input type="radio" name="antihacker_rest_api" value="yes" <?php checked($rest_api, 'yes'); ?> /> <?php esc_html_e('Yes', 'antihacker'); ?></label>
                            <label><input type="radio" name="antihacker_rest_api" value="no" <?php checked($rest_api, 'no'); ?> /> <?php esc_html_e('No', 'antihacker'); ?></label>
                        </div>

                        <div class="antihacker-inst-field">
                            <h3>
                                <?php esc_html_e('Block all Feeds to avoid bot exploitation', 'antihacker'); ?>
                                <a href="<?php echo esc_url('https://antihackerplugin.com/why-to-block-all-feeds/'); ?>" target="_blank" rel="noopener noreferrer" class="antihacker-inst-learn-more" aria-label="<?php esc_attr_e('Learn more about blocking feeds', 'antihacker'); ?>">
                                    <img src="<?php echo esc_url(ANTIHACKERIMAGES . '/info-icon.png'); ?>" alt="<?php esc_attr_e('Information icon', 'antihacker'); ?>" class="antihacker-inst-info-icon" width="16" height="16" />
                                    <?php esc_html_e('Learn more', 'antihacker'); ?>
                                </a>
                            </h3>
                            <p><?php esc_html_e('Blocking RSS/Atom feeds can prevent bots from scraping content and discovering usernames, slightly improving security if you don\'t use feeds for syndication.', 'antihacker'); ?></p>
                            <label><input type="radio" name="antihacker_block_all_feeds" value="yes" <?php checked($block_all_feeds, 'yes'); ?> /> <?php esc_html_e('Yes', 'antihacker'); ?></label>
                            <label><input type="radio" name="antihacker_block_all_feeds" value="no" <?php checked($block_all_feeds, 'no'); ?> /> <?php esc_html_e('No', 'antihacker'); ?></label>
                        </div>
                        <div class="antihacker-inst-field">
                            <h3><?php esc_html_e('Show AntiHacker Widget on the main Dashboard (Admin users only)', 'antihacker'); ?></h3>
                            <label><input type="radio" name="antihacker_show_widget" value="yes" <?php checked($show_widget, 'yes'); ?> /> <?php esc_html_e('Yes', 'antihacker'); ?></label>
                            <label><input type="radio" name="antihacker_show_widget" value="no" <?php checked($show_widget, 'no'); ?> /> <?php esc_html_e('No', 'antihacker'); ?></label>
                        </div>
                        <div class="antihacker-inst-field">
                            <label for="antihacker_my_whitelist"><?php esc_html_e('Add IPs that can access the admin area without email authentication (one per line).', 'antihacker'); ?></label>
                            <textarea id="antihacker_my_whitelist" name="antihacker_my_whitelist" rows="5"><?php echo esc_textarea($whitelist_for_display); ?></textarea>
                            <p class="description">
                                <?php
                                // [MODIFIED] Added an informative and translatable message.
                                printf(
                                    /* translators: %s: Current user IP address. */
                                    esc_html__('Your current IP address is %s. It has been automatically added to the whitelist for your convenience.', 'antihacker'),
                                    '<strong>' . esc_html($antihacker_ip) . '</strong>'
                                );
                                ?>
                            </p>
                        </div>
                        <div class="antihacker-inst-field">
                            <h3><?php esc_html_e('How long to keep visitor logs?', 'antihacker'); ?></h3>
                            <p><?php esc_html_e('If you have heavy traffic, select 1 day. Your choices may affect the blocked visits log.', 'antihacker'); ?></p>
                            <select id="antihacker_log" name="antihacker_keep_log">
                                <option value="1" <?php selected($keep_log, '1'); ?>><?php esc_html_e('1 day', 'antihacker'); ?></option>
                                <option value="3" <?php selected($keep_log, '3'); ?>><?php esc_html_e('3 days', 'antihacker'); ?></option>
                                <option value="7" <?php selected($keep_log, '7'); ?>><?php esc_html_e('7 days', 'antihacker'); ?></option>
                                <option value="14" <?php selected($keep_log, '14'); ?>><?php esc_html_e('14 days', 'antihacker'); ?></option>
                                <option value="21" <?php selected($keep_log, '21'); ?>><?php esc_html_e('21 days', 'antihacker'); ?></option>
                                <option value="30" <?php selected($keep_log, '30'); ?>><?php esc_html_e('30 days', 'antihacker'); ?></option>
                                <option value="90" <?php selected($keep_log, '90'); ?>><?php esc_html_e('90 days', 'antihacker'); ?></option>
                                <option value="180" <?php selected($keep_log, '180'); ?>><?php esc_html_e('180 days', 'antihacker'); ?></option>
                                <option value="360" <?php selected($keep_log, '360'); ?>><?php esc_html_e('360 days', 'antihacker'); ?></option>
                            </select>
                        </div>
                        <div class="antihacker-inst-field">
                            <h3><?php esc_html_e('Purchase Code', 'antihacker'); ?></h3>
                            <p><?php esc_html_e('Paste the Item Purchase Code received via email when you purchased the premium version. Or leave blank for the free version.', 'antihacker'); ?></p>
                            <input type="text" id="antihacker_checkversion" name="antihacker_checkversion" value="<?php echo esc_attr($checkversion); ?>" maxlength="30" />
                        </div>
                        <div class="antihacker-inst-buttons">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=antihacker-installer&step=2')); ?>" class="antihacker-inst-button antihacker-inst-back"><?php esc_html_e('Back', 'antihacker'); ?></a>
                            <button type="submit" class="antihacker-inst-button antihacker-inst-next"><?php esc_html_e('Next', 'antihacker'); ?></button>
                        </div>
                    </form>
                    <?php
                    break;
                case 4:
                    $experience_level = get_option('antihacker_inst_experience_level', 'one-click');
                    if ($experience_level === 'one-click') :
                    ?>
                        <h1><?php esc_html_e('4. All Done!', 'antihacker'); ?></h1>
                        <p><?php esc_html_e('AntiHacker plugin has been successfully configured with our recommended settings! You\'re all set. You can visit your dashboard or fine-tune the options anytime from the plugin\'s settings menu.', 'antihacker'); ?></p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=antihacker-installer&step=4')); ?>">
                            <?php wp_nonce_field('antihacker_inst_form_step_4', 'antihacker_inst_nonce'); ?>
                            <div class="antihacker-inst-buttons">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=antihacker-installer&step=3')); ?>" class="antihacker-inst-button antihacker-inst-back"><?php esc_html_e('Back', 'antihacker'); ?></a>
                                <button type="submit" class="antihacker-inst-button antihacker-inst-next"><?php esc_html_e('Go to Dashboard', 'antihacker'); ?></button>
                            </div>
                        </form>
                    <?php else : ?>
                        <h1>4.&nbsp;<?php esc_html_e('Ready for Configuration', 'antihacker'); ?></h1>
                        <p><?php esc_html_e("Great! Your initial information has been saved. The basics are now configured and ready to go. If you'd like to fine-tune settings or explore additional features, please visit the settings dashboard to configure all available options manually.", 'antihacker'); ?></p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=antihacker-installer&step=4')); ?>">
                            <?php wp_nonce_field('antihacker_inst_form_step_4', 'antihacker_inst_nonce'); ?>
                            <div class="antihacker-inst-buttons">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=antihacker-installer&step=3')); ?>" class="antihacker-inst-button antihacker-inst-back"><?php esc_html_e('Back', 'antihacker'); ?></a>
                                <button type="submit" class="antihacker-inst-button antihacker-inst-next"><?php esc_html_e('Go to the Settings Dashboard', 'antihacker'); ?></button>
                            </div>
                        </form>
            <?php
                    endif;
                    break;
            endswitch;
            ?>
        </main>
    </div>
<?php
}
