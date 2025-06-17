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
function antihacker_inst_add_admin_page()
{
    if (get_option('antihacker_setup_complete', false)) {
        return;
    }
    add_submenu_page(
        'tools.php', // Changed from index.php to a more appropriate location
        'AntiHacker Installer',
        'AntiHacker Installer',
        'manage_options',
        'antihacker-installer',
        'antihacker_inst_render_installer'
    );
}
add_action('admin_menu', 'antihacker_inst_add_admin_page');
/**
 * Enqueues CSS and JS for the installer page.
 * This is now also responsible for localizing the script with necessary data.
 */
function antihacker_inst_enqueue_scripts($hook)
{
    // Only load on our installer page
    if ($hook !== 'tools_page_antihacker-installer') {
        return;
    }
    // Enqueue CSS
    wp_enqueue_style(
        'antihacker-inst-styles',
        ANTIHACKERURL . 'includes/install/install.css',
        ['dashicons'],
        ANTIHACKERVERSION
    );
    // Enqueue the new JavaScript file
    wp_enqueue_script(
        'antihacker-inst-script',
        ANTIHACKERURL . 'includes/install/install.js',
        ['jquery'],
        ANTIHACKERVERSION,
        true // Load in the footer
    );
    // Pass data to the JavaScript file
    wp_localize_script(
        'antihacker-inst-script',
        'antihacker_installer_ajax', // Object name in JavaScript
        [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('antihacker-installer-ajax-nonce'),
            'initial_step' => isset($_GET['step']) ? intval($_GET['step']) : 1,
        ]
    );
}
add_action('admin_enqueue_scripts', 'antihacker_inst_enqueue_scripts');
/**
 * The main AJAX handler for the installer.
 * It processes form data and returns the HTML for the next step.
 */
function antihacker_ajax_installer_handler()
{
    // 1. Security checks
    check_ajax_referer('antihacker-installer-ajax-nonce', 'nonce');
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
                    $experience_level = isset($_POST['antihacker_inst_experience_level']) ? sanitize_key($_POST['antihacker_inst_experience_level']) : 'one-click';
                    update_option('antihacker_inst_experience_level', $experience_level);
                }
                if ($step_to_process === 3) {
                    update_option('antihacker_my_email_to', isset($_POST['antihacker_my_email_to']) ? sanitize_email($_POST['antihacker_my_email_to']) : '');
                    update_option('my_radio_xml_rpc', isset($_POST['my_radio_xml_rpc']) ? sanitize_key($_POST['my_radio_xml_rpc']) : 'yes');
                    update_option('antihacker_rest_api', isset($_POST['antihacker_rest_api']) ? sanitize_key($_POST['antihacker_rest_api']) : 'no');
                    update_option('antihacker_block_all_feeds', isset($_POST['antihacker_block_all_feeds']) ? sanitize_key($_POST['antihacker_block_all_feeds']) : 'no');
                    update_option('antihacker_show_widget', isset($_POST['antihacker_show_widget']) ? sanitize_key($_POST['antihacker_show_widget']) : 'yes');
                    update_option('antihacker_my_whitelist', isset($_POST['antihacker_my_whitelist']) ? sanitize_textarea_field($_POST['antihacker_my_whitelist']) : '');
                    update_option('antihacker_keep_log', isset($_POST['antihacker_keep_log']) ? sanitize_key($_POST['antihacker_keep_log']) : '7');
                    update_option('antihacker_checkversion', isset($_POST['antihacker_checkversion']) ? sanitize_text_field($_POST['antihacker_checkversion']) : '');
                }
                break; // Break after processing steps 1-3
            case 4:
                $experience_level = get_option('antihacker_inst_experience_level', 'one-click');
                $redirect_url = ($experience_level === 'one-click')
                    ? admin_url('admin.php?page=anti_hacker_plugin')
                    : admin_url('admin.php?page=anti-hacker');
                // Mark setup as complete
                update_option('antihacker_setup_complete', true);
                wp_send_json_success(['redirect' => esc_url_raw($redirect_url)]);
                break; // tecnicamente redundante por causa do wp_send_json_success, mas bom para clareza.
        }
    }
    // 4. Render HTML - Esta parte só é alcançada para os passos 1, 2, 3, 4.
    ob_start();
    antihacker_inst_render_step_html($step_to_load);
    $html = ob_get_clean();
    // 5. Send the HTML back to JavaScript.
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_antihacker_installer_step', 'antihacker_ajax_installer_handler');
/**
 * Renders the main installer shell.
 * The content will be loaded via AJAX.
 */
function antihacker_inst_render_installer()
{
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
?>
    <div class="antihacker-inst-wrap">
        <header class="antihacker-inst-header">
            <img id="antihacker-inst-logo" alt="AntiHacker Logo" src="<?php echo esc_url(ANTIHACKERIMAGES . '/logo.png'); ?>" width="250px" />
            <img id="antihacker-inst-step-indicator" alt="Step Indicator" src="<?php echo esc_url(ANTIHACKERIMAGES . '/header-install-step-1.png'); ?>" />
        </header>
        <main id="antihacker-inst-content-container" class="antihacker-inst-content">
            <!-- Initial content is loaded via JS -->
            <div class="antihacker-inst-loader">
                <span class="spinner is-active"></span>
                <p><?php esc_html_e('Loading', 'antihacker'); ?>...</p>
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
function antihacker_inst_render_step_html($step = 1)
{
    if ($step < 1 || $step > 4) $step = 1;
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
            <form id="antihacker-installer-form" data-step="1">
                <div class="antihacker-inst-buttons">
                    <button type="submit" class="antihacker-inst-button antihacker-inst-next">Next</button>
                </div>
            </form>
        <?php
            break;
        case 2:
            $experience_level = get_option('antihacker_inst_experience_level', 'one-click');
        ?>
            <h1>2.&nbsp;<?php esc_html_e('Your Experience Level', 'antihacker'); ?></h1>
            <p><?php esc_html_e('What is your level of experience with WordPress? This will help us tailor the setup process for you. You can always change this in the future.', 'antihacker'); ?></p>
            <form id="antihacker-installer-form" data-step="2">
                <label>
                    <input type="radio" name="antihacker_inst_experience_level" value="one-click" <?php checked($experience_level, 'one-click'); ?> />
                    One-Click Setup (I'm a beginner, set it up for me!)
                </label>
                <p class="antihacker-inst-description">We'll automatically apply the best-practice settings for you...</p>
                <label>
                    <input type="radio" name="antihacker_inst_experience_level" value="manual" <?php checked($experience_level, 'manual'); ?> />
                    Manual Setup (I'm an experienced user, I want to configure it myself.)
                </label>
                <p class="antihacker-inst-description">You will be able to configure all the basic and advanced settings manually...</p>
                <div class="antihacker-inst-buttons">
                    <button type="button" class="antihacker-inst-button antihacker-inst-back" data-step="1">Back</button>
                    <button type="submit" class="antihacker-inst-button antihacker-inst-next">Next</button>
                </div>
            </form>
        <?php
            break;
        case 3:
            $antihacker_ip = antihacker_get_installer_ip();
            // [MODIFIED] Requirement 1: Add current user's IP to the whitelist if it's not there.
            // This happens BEFORE we get the value to display in the form.
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
            <form id="antihacker-installer-form" data-step="3">
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
                    <button type="button" class="antihacker-inst-button antihacker-inst-back" data-step="2"><?php esc_html_e('Back', 'antihacker'); ?></button>
                    <button type="submit" class="antihacker-inst-button antihacker-inst-next"><?php esc_html_e('Next', 'antihacker'); ?></button>
                </div>
            </form>
        <?php
            break;
        case 4:
            $experience_level = get_option('antihacker_inst_experience_level', 'one-click');
        ?>
            <form id="antihacker-installer-form" data-step="4">
                <?php if ($experience_level === 'one-click') : ?>
                    <h1>4. <?php esc_html_e('All Done!', 'antihacker'); ?></h1>
                    <p><?php esc_html_e('AntiHacker has been successfully configured with our recommended settings! You are all set. You can visit your dashboard now or fine-tune the options anytime from the plugin\'s settings menu.', 'antihacker'); ?></p>
                    <div class="antihacker-inst-buttons">
                        <button type="button" class="antihacker-inst-button antihacker-inst-back" data-step="3"><?php esc_html_e('Back', 'antihacker'); ?></button>
                        <button type="submit" class="antihacker-inst-button antihacker-inst-next"><?php esc_html_e('Go to Dashboard', 'antihacker'); ?></button>
                    </div>
                <?php else : // This is for 'manual' setup 
                ?>
                    <h1>4. <?php esc_html_e('Ready for Manual Configuration', 'antihacker'); ?></h1>
                    <p><?php esc_html_e('Great! Your initial information has been saved. The basics are configured and ready to go. Please proceed to the settings dashboard to fine-tune options and explore all available features.', 'antihacker'); ?></p>
                    <div class="antihacker-inst-buttons">
                        <button type="button" class="antihacker-inst-button antihacker-inst-back" data-step="3"><?php esc_html_e('Back', 'antihacker'); ?></button>
                        <button type="submit" class="antihacker-inst-button antihacker-inst-next"><?php esc_html_e('Go to Settings Dashboard', 'antihacker'); ?></button>
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
function antihacker_get_installer_ip()
{
    $raw_ip = '';
    if (function_exists('antihacker_findip')) {
        $raw_ip = antihacker_findip();
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $raw_ip = $_SERVER['REMOTE_ADDR'];
    }
    $sanitized_ip = filter_var(trim($raw_ip), FILTER_VALIDATE_IP);
    return ($sanitized_ip) ? $sanitized_ip : '';
}
