<?php

namespace antihacker_BillCatchErrors;
// created 06/23/23
// upd: 2023-10-16 -  2024-06-17

if (!defined("ABSPATH")) {
    die("Invalid request.");
}
if (function_exists('is_multisite') and is_multisite()) {
    return;
}


// return;




/*
call it
function antihacker_bill_hooking_catch_errors()
{
        if (function_exists('is_admin') && function_exists('current_user_can')) {
            if(is_admin() and current_user_can("manage_options")){
                $declared_classes = get_declared_classes();
                foreach ($declared_classes as $class_name) {
                    if (strpos($class_name, "bill_catch_errors") !== false) {
                        return;
                    }
                }
                require_once dirname(__FILE__) . "/includes/catch-errors/class_bill_catch_errors.php";
            }
        }
}
add_action("init", "antihacker_bill_hooking_catch_errors",15);

*/

/*
if (file_exists(WPMU_PLUGIN_DIR . '/bill-catch-errors.php')) {
    return;
}
    */



$plugin_file_path = ABSPATH . 'wp-admin/includes/plugin.php';
if (file_exists($plugin_file_path)) {
    include_once($plugin_file_path);
}

/*
    if (function_exists('is_plugin_active')){
        $bill_plugins_to_check = array(
            'antihacker/antihacker.php',  
        );
        foreach ($bill_plugins_to_check as $plugin_path) {
            if (is_plugin_active($plugin_path)) 
            return;
        }
    }
    */


// debug4();

$plugin_name = 'bill-catch-errors.php';

// Retrieve all must-use plugins
$wp_mu_plugins = get_mu_plugins();

// Check if the plugin exists in the list of mu-plugins

if (isset($wp_mu_plugins[$plugin_name])) {
    //debug4();

    // Get the plugin's data
    $plugin_data = $wp_mu_plugins[$plugin_name];
    $plugin_version = $plugin_data['Version'];


    // Check the version

    if (version_compare($plugin_version, '1.4', '>=')) {
        // A versão do plugin é 1.4 ou superior
        // nada a fazer, deixa rolar...
        return;
    }
} else {
    //debug4();

    //debug4('The Bill Catch Errors plugin is not installed or loaded.');
    bill_install_mu_plugin();
    return;
}

function bill_install_mu_plugin()
{
    //debug4();

    $plugin_file = 'bill-catch-errors.php';
    //'bill-catch-errors.php'; // Name of the plugin file to be copied
    $antihacker_mu_plugin_dir = WP_PLUGIN_DIR . '/antihacker/includes/mu-plugins'; // Current path inside antihacker
    $mu_plugins_dir = WPMU_PLUGIN_DIR; // MU-Plugins directory


    try {
        // Check if the MU-Plugins directory exists
        if (!is_dir($mu_plugins_dir)) {
            // Try to create the directory with the appropriate permissions
            if (!mkdir($mu_plugins_dir, 0755, true)) {
                error_log("Unable to create the MU-Plugins directory: " . $mu_plugins_dir);
            }
        }

        // Check if the MU-Plugins directory is readable and writable
        if (!is_readable($mu_plugins_dir) || !is_writable($mu_plugins_dir)) {
            error_log("The MU-Plugins directory does not have the appropriate permissions: " . $mu_plugins_dir);
        }

        // Define the plugin file path in the antihacker directory
        $source = $antihacker_mu_plugin_dir . '/' . $plugin_file;
        $destination = $mu_plugins_dir . '/' . $plugin_file;

        //debug4($source);
        //debug4($destination);

        // Check if the plugin file exists in the source directory
        if (!file_exists($source)) {
            error_log("The plugin file was not found in the source directory: " . $source);
        }

        // Copy the plugin file to the MU-Plugins directory
        if (!copy($source, $destination)) {
            error_log("Unable to copy the plugin file to the MU-Plugins directory: " . $destination);
        }

        // Success


        return true;
    } catch (Exception $e) {
        // Log the error
        error_log("Error copying the plugin file to the MU-Plugins directory: " . $e->getMessage());
        return false;
    }
}


add_action("wp_ajax_bill_minozzi_js_error_catched", "antihacker_BillCatchErrors\\bill_minozzi_js_error_catched");
add_action("wp_ajax_nopriv_bill_minozzi_js_error_catched", "antihacker_BillCatchErrors\\bill_minozzi_js_error_catched");
function bill_minozzi_js_error_catched()
{
    global $antihacker_plugin_slug;
    if (isset($_REQUEST)) {
        if (!isset($_REQUEST["bill_js_error_catched"])) {
            die("empty error");
        }
        if (
            !wp_verify_nonce(
                sanitize_text_field($_POST["_wpnonce"]),
                "bill-catch-js-errors"
            )
        ) {
            status_header(406, "Invalid nonce");
            die();
        }
        $bill_js_error_catched = sanitize_text_field(
            $_REQUEST["bill_js_error_catched"]
        );
        $bill_js_error_catched = trim($bill_js_error_catched);
        // Split the error message
        $errors = explode(" | ", $bill_js_error_catched);
        foreach ($errors as $error) {
            // Explode the error message into parts
            $parts = explode(" - ", $error);
            if (count($parts) < 3) {
                continue;
            }
            $errorMessage = $parts[0];
            $errorURL = $parts[1];
            $errorLine = $parts[2];
            $logMessage = "Javascript " . $errorMessage . " - " . $errorURL . " - " . $errorLine;
            $date_format = get_option('date_format', '');


            if (!empty($date_format)) {
                $formattedMessage = "[" . date_i18n($date_format) . ' ' . date('H:i:s') . "] - " . $logMessage;
            } else {
                $formattedMessage = "[" . date('M-d-Y H:i:s') . "] - " . $logMessage;
            }


            $logFile =  trailingslashit(ABSPATH) . 'error_log';
            if (!file_exists(dirname($logFile))) {
                mkdir(dirname($logFile), 0755, true);
            }
            $log_error = true;
            if (!function_exists('error_log')) {
                $log_error = false;
            }
            if ($log_error) {

                if ($antihacker_plugin_slug  == 'antihacker')
                    antihackerErrorHandler('Javascript', $errorMessage, $errorURL, $errorLine);

                if (error_log("\n" . $formattedMessage, 3, $logFile)) {
                    $ret_error_log = true;
                } else {
                    $ret_error_log = false;
                }
            }
            if (!$ret_error_log or !$log_error) {
                $formattedMessage = PHP_EOL . $formattedMessage;
                $r = file_put_contents($logFile, $formattedMessage, FILE_APPEND | LOCK_EX);
                if (!$r) {
                    $timestamp_string = strval(time());
                    update_option('bill_minozzi_error_log_status', $timestamp_string);
                }
            }
        }
        die("OK!");
    }
    die("NOT OK!");
}
class antihacker_bill_catch_errors
{
    public function __construct()
    {
        add_action("wp_head", [$this, "add_bill_javascript_to_header"]);
        add_action("admin_head", [$this, "add_bill_javascript_to_header"]);
    }
    public function add_bill_javascript_to_header()
    {
        $nonce = wp_create_nonce("bill-catch-js-errors");
        $ajax_url = esc_js($this->get_ajax_url()) . "?action=bill_minozzi_js_error_catched&_wpnonce=" . $nonce;
?>
        <script>
            var errorQueue = [];
            var timeout;

            function isBot() {
                const bots = ['bot', 'googlebot', 'bingbot', 'facebook', 'slurp', 'twitter', 'yahoo'];
                const userAgent = navigator.userAgent.toLowerCase();
                return bots.some(bot => userAgent.includes(bot));
            }
            window.onerror = function(msg, url, line) {
                var errorMessage = [
                    'Message: ' + msg,
                    'URL: ' + url,
                    'Line: ' + line
                ].join(' - ');
                // Filter bots errors...
                if (isBot()) {
                    return;
                }
                //console.log(errorMessage);
                errorQueue.push(errorMessage);
                if (errorQueue.length >= 5) {
                    sendErrorsToServer();
                } else {
                    clearTimeout(timeout);
                    timeout = setTimeout(sendErrorsToServer, 5000);
                }
            }

            function sendErrorsToServer() {
                if (errorQueue.length > 0) {
                    var message = errorQueue.join(' | ');
                    // console.log(message);
                    var xhr = new XMLHttpRequest();
                    var nonce = '<?php echo esc_js($nonce); ?>';
                    var ajaxurl = '<?php echo $ajax_url; ?>'; // Não é necessário esc_js aqui
                    xhr.open('POST', encodeURI(ajaxurl));
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            // console.log('Success:', xhr.responseText);
                        } else {
                            console.log('Error:', xhr.status);
                        }
                    };
                    xhr.onerror = function() {
                        console.error('Request failed');
                    };
                    xhr.send('action=bill_minozzi_js_error_catched&_wpnonce=' + nonce + '&bill_js_error_catched=' + encodeURIComponent(message));
                    errorQueue = []; // Limpa a fila de erros após o envio
                }
            }
            window.addEventListener('beforeunload', sendErrorsToServer);
        </script>
<?php
    }
    private function get_ajax_url()
    {
        return esc_attr(admin_url("admin-ajax.php"));
    }
}
new antihacker_bill_catch_errors();
//
