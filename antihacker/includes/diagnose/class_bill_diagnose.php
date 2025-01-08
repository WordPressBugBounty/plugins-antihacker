<?php

namespace antihacker_BillDiagnose;
// 2023-08 upd: 2023-10-17 2024-06=21 2024-31-12 2025-01-05
if (!defined('ABSPATH')) {
    die('Invalid request.');
}
if (function_exists('is_multisite') and is_multisite()) {
    return;
}




/*
// >>>>>>>>>>>>>>>> call
function antihacker_bill_hooking_diagnose()
{
    if (function_exists('is_admin') && function_exists('current_user_can')) {
        if(is_admin() and current_user_can("manage_options")){
            $notification_url = "https://wpmemory.com/fix-low-memory-limit/";
            $notification_url2 =
                "https://wptoolsplugin.com/site-language-error-can-crash-your-site/";
            require_once dirname(__FILE__) . "/includes/diagnose/class_bill_diagnose.php";
        }
    }
}
add_action("plugins_loaded", "antihacker_bill_hooking_diagnose");
// end >>>>>>>>>>>>>>>>>>>>>>>>>
*/


$plugin_file_path = __DIR__ . '/function_time_loading.php';

if (file_exists($plugin_file_path)) {
    include_once($plugin_file_path);
} else {
    error_log("File not found: " . $plugin_file_path);
}


$plugin_file_path = ABSPATH . 'wp-admin/includes/plugin.php';
if (file_exists($plugin_file_path)) {
    include_once($plugin_file_path);
}
if (function_exists('is_plugin_active')) {
    $bill_plugins_to_check = array(
        'wptools/wptools.php',
    );
    foreach ($bill_plugins_to_check as $plugin_path) {
        if (is_plugin_active($plugin_path)) {
            return;
        }
    }
}
$logErrors = ini_get('log_errors');
$errorLog = ini_get('error_log');
if ($logErrors) {
    if (!$errorLog) {
        ini_set('error_log', ABSPATH . 'error_log');
    }
} else {
    ini_set('log_errors', 'On');
}
// -- Help
// Função para exibir o ID da tela
function debug_screen_id_current_screen($screen)
{
    if ($screen) {
        error_log('Screen ID: ' . $screen->id);
    }
}
//add_action('current_screen', __NAMESPACE__ . '\\debug_screen_id_current_screen');
// Função para adicionar uma aba de ajuda
function add_help_tab_to_screen()
{
    // Verifica se estamos na tela correta
    $screen = get_current_screen();
    // Verifica se o screen é o 'site-health' antes de adicionar a aba
    if ($screen && 'site-health' === $screen->id) {
        $hmessage = esc_attr__(
            'Here are some details about error and memory monitoring for your plugin. Errors and low memory can prevent your site from functioning properly. On this page, you will find a partial list of the most recent errors and warnings. If you need more details, use the chat form, which will search for additional information using Artificial Intelligence.  
If you need to dive deeper, install the free plugin WPTools, which provides more in-depth insights.',
            'antihacker'
        );
        // Adiciona a aba de ajuda
        $screen->add_help_tab([
            'id'      => 'site-health', // ID único para a aba
            'title'   => esc_attr__('Memory & Error Monitoring', 'antihacker'), // Título da aba
            'content' => '<p>' . esc_attr__('Welcome to plugin Insights!', 'antihacker') . '</p>
                          <p>' . $hmessage . '</p>',
        ]);
    }
}
// Adiciona a aba de ajuda quando a tela 'site-health' for carregada
add_action('current_screen', __NAMESPACE__ . '\\add_help_tab_to_screen');
class ErrorChecker
{
    public function bill_parseDate($dateString, $locale)
    {
        // Mapeamento de formatos de data por idioma
        $dateFormatsByLanguage = [
            'pt' => 'd/m/Y', // 31/12/2024 (Português)
            'en' => 'm/d/Y', // 12/31/2024 (Inglês)
            'fr' => 'd/m/Y', // 31/12/2024 (Francês)
            'de' => 'd.m.Y', // 31.12.2024 (Alemão)
            'es' => 'd/m/Y', // 31/12/2024 (Espanhol)
            'nl' => 'd-m-Y', // 31-12-2024 (Holandês)
        ];
        // Extrai o código de idioma do locale (ex: 'pt_BR' -> 'pt')
        $language = substr($locale, 0, 2);
        // Obtém o formato de data correspondente ao idioma
        $format = $dateFormatsByLanguage[$language] ?? 'Y-m-d'; // Fallback para um formato padrão
        // Tenta criar o DateTime com o formato correspondente
        $date = \DateTime::createFromFormat($format, $dateString);
        if ($date !== false) {
            return $date;
        }
        // Se o formato específico do idioma falhar, tenta detectar o formato automaticamente
        $possibleFormats = [
            'd/m/Y', // 31/12/2024
            'm/d/Y', // 12/31/2024
            'Y-m-d', // 2024-12-31
            'd-M-Y', // 31-Dec-2024
            'd F Y', // 31 December 2024
            'd.m.Y', // 31.12.2024 (Alemão)
            'd-m-Y', // 31-12-2024 (Holandês)
        ];
        foreach ($possibleFormats as $format) {
            $date = \DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date;
            }
        }
        // Se nenhum formato funcionar, lança uma exceção
        // throw new \Exception("Falha ao parsear a data: " . $dateString);
        return false;
    }
    public function bill_check_errors_today($num_days, $filter = null)
    {
        // return true;


        $bill_count = 0;
        $bill_folders = [];
        //$bill_themePath = get_theme_root();
        $error_log_path = trim(ini_get("error_log"));
        if (!is_null($error_log_path) and $error_log_path != trim(ABSPATH . "error_log")) {
            $bill_folders[] = $error_log_path;
        }
        $bill_folders[] = ABSPATH . "error_log";
        $bill_folders[] = ABSPATH . "php_errorlog";
        $bill_folders[] = plugin_dir_path(__FILE__) . "/error_log";
        $bill_folders[] = plugin_dir_path(__FILE__) . "/php_errorlog";
        $bill_folders[] = get_theme_root() . "/error_log";
        $bill_folders[] = get_theme_root() . "/php_errorlog";
        // Adicionar caminhos específicos de administração se existirem
        $bill_admin_path = str_replace(
            get_bloginfo("url") . "/",
            ABSPATH,
            get_admin_url()
        );
        $bill_folders[] = $bill_admin_path . "/error_log";
        $bill_folders[] = $bill_admin_path . "/php_errorlog";
        $bill_folders[] = WP_CONTENT_DIR . "/debug.log";
        // Adicionar diretórios de plugins
        $bill_plugins = array_slice(scandir(plugin_dir_path(__FILE__)), 2);
        foreach ($bill_plugins as $bill_plugin) {
            if (is_dir(plugin_dir_path(__FILE__) . "/" . $bill_plugin)) {
                $bill_folders[] = plugin_dir_path(__FILE__) . "/" . $bill_plugin . "/error_log";
                $bill_folders[] = plugin_dir_path(__FILE__) . "/" . $bill_plugin . "/php_errorlog";
            }
        }
        $bill_themes = array_slice(scandir(get_theme_root()), 2);
        foreach ($bill_themes as $bill_theme) {
            if (is_dir(get_theme_root() . "/" . $bill_theme)) {
                $bill_folders[] = get_theme_root() . "/" . $bill_theme . "/error_log";
                $bill_folders[] = get_theme_root() . "/" . $bill_theme . "/php_errorlog";
            }
        }
        // Data limite para comparação
        //$dateThreshold = new DateTime('now');
        $dateThreshold = new \DateTime('now');
        // $dateThreshold->modify('-3 days');
        $dateThreshold->modify("-{$num_days} days");
        // $dateThreshold->modify("-$num_days days");
        // Regex para identificar diferentes formatos de data
        $datePatterns = [
            '/\d{2}-[a-zA-ZÀ-ÿ]{3}-\d{4}/',  // DD-Mon-YYYY (ex: 31-Dec-2024)
            '/\d{2}\s+[a-zA-ZÀ-ÿ]+\s+\d{4}/', // DD Month YYYY (ex: 31 December 2024)
            '/\d{4}-\d{2}-\d{2}/',           // YYYY-MM-DD (ex: 2024-12-31)
            '/\d{2}\/\d{2}\/\d{4}/',         // DD/MM/YYYY (ex: 31/12/2024)
            '/\d{2}-\d{2}-\d{4}/',           // DD-MM-YYYY (ex: 31-12-2024)
            '/\d{2}\.\d{2}\.\d{4}/',         // DD.MM.YYYY (ex: 31.12.2024)
            '/\d{4}\/\d{2}\/\d{2}/',         // YYYY/MM/DD (ex: 2024/12/31)
        ];

    
        // Obtém o locale do WordPress
        $locale = get_locale(); // Exemplo: 'pt_BR', 'en_US', etc.
        $language = substr($locale, 0, 2); // Extrai o código de idioma (ex: 'pt', 'en')
        // Itera sobre as pastas de faturas
        foreach ($bill_folders as $bill_folder) {
            if (!empty($bill_folder) && file_exists($bill_folder) && filesize($bill_folder) > 0) {
                $bill_count++;
                $marray = $this->bill_read_file($bill_folder, 20);
                if (is_array($marray) && !empty($marray)) {
                    foreach ($marray as $line) {
                        if (empty($line)) {
                            continue;
                        }
                        if ($filter !== null && stripos($line, $filter) === false) {
                            continue;
                        }
                        if (substr($line, 0, 1) !== '[') {
                            continue;
                        }
                        // Verifica se a linha corresponde a algum padrão de data
                        foreach ($datePatterns as $pattern) {
                            if (preg_match($pattern, $line, $matches)) {
                                try {
                                    // Usa a função parseDate para interpretar a data
                                    // $date = bill_parseDate($matches[0], $locale);
                                    $date = $this->bill_parseDate($matches[0], $locale);

                                    if (!$date)
                                        return false;
                                    // Verifica se a data é anterior ao limite
                                    // debug2($date);
                                    if ($date < $dateThreshold) {
                                        // debug2('Antiga');
                                        // debug2("Data antiga encontrada: " . $date->format('Y-m-d'));
                                    } else {
                                        // debug2('Data Nova encontrada');
                                        return true;
                                    }
                                } catch (Exception $e) {
                                    // Ignorar linhas com datas inválidas
                                    // debug2("Erro ao processar a data: " . $e->getMessage());
                                    continue;
                                }
                            } else {
                                // debug2('nao bateu');
                            }
                        }
                        return false;
                    }
                }
            }
        }
        return false;
    }
    public function bill_read_file($file, $lines)
    {
        $handle = fopen($file, "r");
        if (!$handle) {
            return "";
        }
        $bufferSize = 8192; // Tamanho do bloco de leitura (8KB)
        $text = [];
        $currentChunk = '';
        $linecounter = 0;
        // Move para o final do arquivo e começa a leitura para trás
        fseek($handle, 0, SEEK_END);
        $filesize = ftell($handle); // Tamanho do arquivo
        // Ajustar bufferSize para o tamanho do arquivo se for menor que 8KB
        if ($filesize < $bufferSize) {
            $bufferSize = $filesize;
        }
        if ($bufferSize < 1) {
            return '';
        }
        $pos = $filesize - $bufferSize;
        while ($pos >= 0 && $linecounter < $lines) {
            if ($pos < 0) {
                $pos = 0;
            }
            fseek($handle, $pos);
            $chunk = fread($handle, $bufferSize);
            $currentChunk = $chunk . $currentChunk;
            $linesInChunk = explode("\n", $currentChunk);
            $currentChunk = array_shift($linesInChunk);
            foreach (array_reverse($linesInChunk) as $line) {
                $text[] = $line;
                $linecounter++;
                if ($linecounter >= $lines) {
                    break 2;
                }
            }
            $pos -= $bufferSize;
        }
        if (!empty($currentChunk)) {
            $text[] = $currentChunk;
        }
        fclose($handle);
        return $text;
    }
} // end class error checker
class MemoryChecker
{
    public function check_memory()
    {
        try {
            // Check if ini_get function exists
            if (!function_exists('ini_get')) {
                $wpmemory["msg_type"] = "notok";
                return $wpmemory;
            } else {
                // Get the PHP memory limit
                $wpmemory["limit"] = (int) ini_get("memory_limit");
            }
            // Check if the memory limit is numeric
            if (!is_numeric($wpmemory["limit"])) {
                $wpmemory["msg_type"] = "notok";
                return $wpmemory;
            }
            // Convert the memory limit from bytes to megabytes if it is excessively high
            if ($wpmemory["limit"] > 9999999) {
                $wpmemory["limit"] = $wpmemory["limit"] / 1024 / 1024;
            }
            // Check if memory_get_usage function exists
            if (!function_exists('memory_get_usage')) {
                $wpmemory["msg_type"] = "notok";
                return $wpmemory;
            } else {
                // Get the current memory usage
                $wpmemory["usage"] = memory_get_usage();
            }
            // Check if the memory usage is valid
            if ($wpmemory["usage"] < 1) {
                $wpmemory["msg_type"] = "notok";
                return $wpmemory;
            } else {
                // Convert the memory usage to megabytes
                $wpmemory["usage"] = round($wpmemory["usage"] / 1024 / 1024, 0);
            }
            // Check if the usage value is numeric
            if (!is_numeric($wpmemory["usage"])) {
                $wpmemory["msg_type"] = "notok";
                return $wpmemory;
            }
            // Check if WP_MEMORY_LIMIT is defined
            if (!defined("WP_MEMORY_LIMIT")) {
                $wpmemory["wp_limit"] = 40; // Default value of 40M
            } else {
                $wp_memory_limit = WP_MEMORY_LIMIT;
                $wpmemory["wp_limit"] = (int) $wp_memory_limit;
            }
            // Calculate the percentage of memory usage
            $wpmemory["percent"] = $wpmemory["usage"] / $wpmemory["wp_limit"];
            $wpmemory["color"] = "font-weight:normal;";
            if ($wpmemory["percent"] > 0.7) {
                $wpmemory["color"] = "font-weight:bold;color:#E66F00";
            }
            if ($wpmemory["percent"] > 0.85) {
                $wpmemory["color"] = "font-weight:bold;color:red";
            }
            // Calculate the available free memory
            $wpmemory["free"] = $wpmemory["wp_limit"] - $wpmemory["usage"];
            $wpmemory["msg_type"] = "ok";
        } catch (Exception $e) {
            $wpmemory["msg_type"] = "notok";
            return $wpmemory;
        }
        return $wpmemory;
    }
}
class antihacker_Bill_Diagnose
{
    protected $global_plugin_slug;
    
    private static $instance = null;
    private $notification_url;
    private $notification_url2;
    private $global_variable_has_errors;
    private $global_variable_memory;
    public function __construct(
        $notification_url,
        $notification_url2
    ) {
        $this->setNotificationUrl($notification_url);
        $this->setNotificationUrl2($notification_url2);
        //$this->global_variable_has_errors = $this->bill_check_errors_today();
        $errorChecker = new ErrorChecker(); //
        //
        $this->global_variable_has_errors  = $errorChecker->bill_check_errors_today(1);




        //  \debug3($this->global_variable_has_errors);


        // NOT same class
        $memoryChecker = new MemoryChecker();
        $this->global_variable_memory = $memoryChecker->check_memory();
        $this->global_plugin_slug = $this->get_plugin_slug();
        // Adicionando as ações dentro do construtor
        add_action("admin_notices", [$this, "show_dismissible_notification"]);
        //add_action("admin_notices", [$this, "show_dismissible_notification2"]);
        // 2024
        if ($this->global_variable_has_errors) {
            add_action("admin_bar_menu", [$this, "add_site_health_link_to_admin_toolbar"], 999);
            // debug2('global_variable_has_errors');
        }
        add_action("admin_head", [$this, "custom_help_tab"]);
        $memory = $this->global_variable_memory;
        if (
            $memory["free"] < 30 or
            $memory["percent"] > 85 or
            $this->global_variable_has_errors
        ) {
            add_filter("site_health_navigation_tabs", [
                $this,
                "site_health_navigation_tabs",
            ]);
            add_action("site_health_tab_content", [
                $this,
                "site_health_tab_content",
            ]);
        }
    }
    public function get_plugin_slug()
    {
        // Get the plugin directory path
        $plugin_dir = plugin_dir_path(__FILE__);
        // Function to get the base directory of the plugin
        function get_base_plugin_dir($dir, $base_dir)
        {
            // Remove the base directory part from the full path
            $relative_path = str_replace($base_dir, '', $dir);
            // Get the first directory in the relative path
            $parts = explode('/', trim($relative_path, '/'));
            return $parts[0];
        }
        // Check if the plugin is in the normal plugins directory
        if (strpos($plugin_dir, WP_PLUGIN_DIR) === 0) {
            $plugin_slug = get_base_plugin_dir($plugin_dir, WP_PLUGIN_DIR);
        }
        // Check if the plugin is in the mu-plugins directory
        elseif (defined('WPMU_PLUGIN_DIR') && strpos($plugin_dir, WPMU_PLUGIN_DIR) === 0) {
            $plugin_slug = get_base_plugin_dir($plugin_dir, WPMU_PLUGIN_DIR);
        } else {
            // If the plugin is not in any expected directory, return an empty string
            return '';
        }
        return $plugin_slug;
    }
    public function setNotificationUrl($notification_url)
    {
        $this->notification_url = $notification_url;
    }
    public function setNotificationUrl2($notification_url2)
    {
        $this->notification_url2 = $notification_url2;
    }
    public function setPluginTextDomain($plugin_text_domain)
    {
        $this->plugin_text_domain = $plugin_text_domain;
    }
    public function setPluginSlug($plugin_slug)
    {
        $this->plugin_slug =  $this->get_plugin_slug();
    }
    public static function get_instance(
        $notification_url,
        $notification_url2
    ) {
        if (self::$instance === null) {
            self::$instance = new self(
                $notification_url,
                $notification_url2,
            );
        }
        return self::$instance;
    }
    //
    public function show_dismissible_notification()
    {
        if ($this->is_notification_displayed_today()) {
            return;
        }
        $memory = $this->global_variable_memory;
        if ($memory["free"] > 30 and $wpmemory["percent"] < 85) {
            return;
        }
        $message = esc_attr__("Our plugin", 'antihacker');
        $message .= ' (' . $this->plugin_slug . ') ';
        $message .= esc_attr__("cannot function properly because your WordPress Memory Limit is too low. Your site will experience serious issues, even if you deactivate our plugin.", 'antihacker');
        $message .=
            '<a href="' .
            esc_url($this->notification_url) .
            '">' .
            " " .
            esc_attr__("Learn more", 'antihacker') .
            "</a>";
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p style="color: red;">' . wp_kses_post($message) . "</p>";
        echo "</div>";
    }
    // Helper function to check if a notification has been displayed today
    public function is_notification_displayed_today()
    {
        $last_notification_date = get_option("antihacker_bill_show_warnings");
        $today = date("Y-m-d");
        return $last_notification_date === $today;
    }
    // Add Tab
    public function site_health_navigation_tabs($tabs)
    {
        // translators: Tab heading for Site Health navigation.
        $tabs["Critical Issues"] = esc_html_x(
            "Critical Issues",
            "Site Health",
            'antihacker'
        );
        return $tabs;
    }
    // Add Content
    public function site_health_tab_content($tab)
    {
        if (!function_exists('antihacker_bill_strip_strong99')) {
            function antihacker_bill_strip_strong99($htmlString)
            {
                // return $htmlString;
                // Use preg_replace para remover as tags <strong>
                $textWithoutStrongTags = preg_replace(
                    "/<strong>(.*?)<\/strong>/i",
                    '$1',
                    $htmlString
                );
                return $textWithoutStrongTags;
            }
        }
        // Do nothing if this is not our tab.
        if ("Critical Issues" !== $tab) {
            return;
        } ?>
        <div class="wrap health-check-body, privacy-settings-body">
            <p style="border: 1px solid #000; padding: 10px;">
                <strong>
                    <?php
                    echo esc_attr__("Displaying the latest recurring errors (Javascript Included) from your error log file and eventually alert about low WordPress memory limit is a courtesy of plugin", 'antihacker');
                    echo ': ' . esc_attr($this->global_plugin_slug) . '. ';
                    echo esc_attr__("Disabling our plugin does not stop the errors from occurring; it simply means you will no longer be notified here that they are happening, but they can still harm your site.", 'antihacker');
                    echo '<br>';
                    echo esc_attr__("Click the help button in the top right or go directly to the AI chat box below for more specific information on the issues listed.", 'antihacker');
                    ?>
                </strong>
            </p>
            <!-- chat -->
            <div id="chat-box">
                <div id="chat-header">
                    <h2><?php echo esc_attr__("Artificial Intelligence Support Chat for Issues and Solutions", "antihacker"); ?></h2>
                </div>
                <div id="gif-container">
                    <div class="spinner999"></div>
                </div> <!-- Onde o efeito será exibido -->
                <div id="chat-messages"></div>
                <div id="error-message" style="display:none;"></div> <!-- Mensagem de erro -->
                <form id="chat-form">
                    <div id="input-group">
                        <input type="text" id="chat-input" placeholder="<?php echo esc_attr__('Enter your message...', 'antihacker'); ?>" />
                        <button type="submit"><?php echo esc_attr__('Send', 'antihacker'); ?></button>
                    </div>
                    <div id="action-instruction" style="text-align: center; margin-top: 10px;">
                        <span><?php echo esc_attr__("Enter a message and click 'Send', or just click 'Auto Checkup' to analyze the site's error log.", 'antihacker'); ?></span>
                    </div>
                    <div class="auto-checkup-container" style="text-align: center; margin-top: 10px;">
                        <button type="button" id="auto-checkup">
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'robot2.png'; ?>" alt="" width="35" height="30">
                            <?php echo esc_attr__('Auto Checkup', 'antihacker'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <!-- end chat -->
            <h3 style="color: red;">
                <?php
                echo esc_attr__("Potential Problems", 'antihacker');
                ?>
            </h3>
            <?php
            $memory = $this->global_variable_memory;
            $wpmemory = $memory;
            if ($memory["free"] < 30 or $wpmemory["percent"] > 85) { ?>
                <h2 style="color: red;">
                    <?php $message = esc_attr__("Low WordPress Memory Limit", 'antihacker'); ?>
                </h2>
                <?php
                $mb = "MB";
                echo "<b>";
                echo "WordPress Memory Limit: " .
                    esc_attr($wpmemory["wp_limit"]) .
                    esc_attr($mb) .
                    "&nbsp;&nbsp;&nbsp;  |&nbsp;&nbsp;&nbsp;";
                $perc = $wpmemory["usage"] / $wpmemory["wp_limit"];
                if ($perc > 0.7) {
                    echo '<span style="color:' . esc_attr($wpmemory["color"]) . ';">';
                }
                echo esc_attr__("Your usage now", 'antihacker') .
                    ": " .
                    esc_attr($wpmemory["usage"]) .
                    "MB &nbsp;&nbsp;&nbsp;";
                if ($perc > 0.7) {
                    echo "</span>";
                }
                echo "|&nbsp;&nbsp;&nbsp;" .
                    esc_attr__("Total Php Server Memory", 'antihacker') .
                    " : " .
                    esc_attr($wpmemory["limit"]) .
                    "MB";
                echo "</b>";
                echo "</center>";
                echo "<hr>";
                $free = $wpmemory["wp_limit"] - $wpmemory["usage"];
                echo '<p>';
                echo esc_attr__("Your WordPress Memory Limit is too low, which can lead to critical issues on your site due to insufficient resources. Promptly address this issue before continuing.", 'antihacker');
                echo '</b>';
                ?>
                </b>
                <a href="https://wpmemory.com/fix-low-memory-limit/">
                    <?php
                    echo esc_attr__("Learn More", 'antihacker');
                    ?>
                </a>
                </p>
                <br>
            <?php
            }
            // end block memory...
            // Errors ...
            if ($this->global_variable_has_errors) { ?>
                <h2 style="color: red;">
                    <?php
                    echo esc_attr__("Site Errors", 'antihacker');
                    ?>
                </h2>
                <p>
                    <?php
                    echo esc_attr__("Your site has experienced errors for the past 2 days. These errors, including JavaScript issues, can result in visual problems or disrupt functionality, ranging from minor glitches to critical site failures. JavaScript errors can terminate JavaScript execution, leaving all subsequent commands inoperable.", 'antihacker');
                    ?>
                    <a href="https://wptoolsplugin.com/site-language-error-can-crash-your-site/">
                        <?php
                        echo esc_attr__("Learn More", 'antihacker');
                        ?>
                    </a>
                </p>
    <?php
                $bill_count = 0;
                $bill_folders = [];
                $bill_themePath = get_theme_root();
                $error_log_path = trim(ini_get("error_log"));
                if (
                    !is_null($error_log_path) and $error_log_path != trim(ABSPATH . "error_log")
                ) {
                    $bill_folders[] = $error_log_path;
                }
                $bill_folders[] = ABSPATH . "error_log";
                $bill_folders[] = ABSPATH . "php_errorlog";
                $bill_folders[] = plugin_dir_path(__FILE__) . "/error_log";
                $bill_folders[] = plugin_dir_path(__FILE__) . "/php_errorlog";
                $bill_folders[] = get_theme_root() . "/error_log";
                $bill_folders[] = get_theme_root() . "/php_errorlog";
                // Adicionar caminhos específicos de administração se existirem
                $bill_admin_path = str_replace(
                    get_bloginfo("url") . "/",
                    ABSPATH,
                    get_admin_url()
                );
                $bill_folders[] = $bill_admin_path . "/error_log";
                $bill_folders[] = $bill_admin_path . "/php_errorlog";
                // Adicionar diretórios de plugins
                //$bill_plugins = array_slice(scandir(WPTOOLSPATH), 2);
                $bill_plugins = array_slice(scandir(plugin_dir_path(__FILE__)), 2);
                foreach ($bill_plugins as $bill_plugin) {
                    if (is_dir(plugin_dir_path(__FILE__) . "/" . $bill_plugin)) {
                        $bill_folders[] = plugin_dir_path(__FILE__) . "/" . $bill_plugin . "/error_log";
                        $bill_folders[] = plugin_dir_path(__FILE__) . "/" . $bill_plugin . "/php_errorlog";
                    }
                }
                $bill_themes = array_slice(scandir(get_theme_root()), 2);
                foreach ($bill_themes as $bill_theme) {
                    if (is_dir(get_theme_root() . "/" . $bill_theme)) {
                        $bill_folders[] = get_theme_root() . "/" . $bill_theme . "/error_log";
                        $bill_folders[] = get_theme_root() . "/" . $bill_theme . "/php_errorlog";
                    }
                }
                echo "<br />";
                echo esc_attr__("This is a partial list of the errors found.", 'antihacker');
                echo "<br />";
                /**
                 * Obtém o tamanho do arquivo em bytes.
                 *
                 * @param string $bill_filename Caminho do arquivo.
                 * @return int|string O tamanho em bytes ou uma mensagem de erro.
                 */
                function getFileSizeInBytes($bill_filename)
                {
                    if (!file_exists($bill_filename) || !is_readable($bill_filename)) {
                        // return "File not readable.";
                        return esc_attr__("File not readable.", 'antihacker');
                    }
                    $fileSizeBytes = filesize($bill_filename);
                    if ($fileSizeBytes === false) {
                        //return "Size not determined.";
                        return esc_attr__("Size not determined.", 'antihacker');
                    }
                    return $fileSizeBytes;
                }
                /**
                 * Converte um tamanho em bytes para um formato legível.
                 *
                 * @param int $sizeBytes Tamanho em bytes.
                 * @return string O tamanho em formato legível.
                 */
                function convertToHumanReadableSize($sizeBytes)
                {
                    if (!is_int($sizeBytes) || $sizeBytes < 0) {
                        // Retorna uma mensagem de erro se o tamanho for inválido
                        return esc_attr__("Invalid size.", 'antihacker');
                    }
                    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                    $unitIndex = 0;
                    while ($sizeBytes >= 1024 && $unitIndex < count($units) - 1) {
                        $sizeBytes /= 1024;
                        $unitIndex++;
                    }
                    // Retorna o valor com a unidade
                    return sprintf("%.2f %s", $sizeBytes, $units[$unitIndex]);
                }
                // Comeca a mostrar erros...
                //
                foreach ($bill_folders as $bill_folder) {
                    $files = glob($bill_folder);
                    if ($files === false) {
                        continue; // skip ...
                    }
                    // foreach (glob($bill_folder) as $bill_filename) 
                    foreach ($files as $bill_filename) {
                        if (strpos($bill_filename, "backup") != true) {
                            echo "<strong>";
                            echo esc_attr($bill_filename);
                            echo "<br />";
                            echo esc_attr__("File Size: ", 'antihacker');
                            $fileSizeBytes = getFileSizeInBytes($bill_filename);
                            if (is_int($fileSizeBytes)) {
                                echo esc_attr(convertToHumanReadableSize($fileSizeBytes));
                            } else {
                                echo esc_attr($fileSizeBytes); // Exibe a mensagem de erro
                            }
                            echo "</strong>";
                            $bill_count++;
                            $errorChecker = new ErrorChecker();
                            $marray = $errorChecker->bill_read_file($bill_filename, 3000);
                            //$marray = $this->bill_read_file($bill_filename, 3000);
                            if (gettype($marray) != "array" or count($marray) < 1) {
                                continue;
                            }
                            $total = count($marray);
                            if (count($marray) > 0) {
                                echo '<textarea style="width:99%;" id="anti_hacker" rows="12">';
                                if ($total > 1000) {
                                    $total = 1000;
                                }
                                for ($i = 0; $i < $total; $i++) {
                                    if (strpos(trim($marray[$i]), "[") !== 0) {
                                        continue; // Skip lines without correct date format
                                    }
                                    $logs = [];
                                    $line = trim($marray[$i]);
                                    if (empty($line)) {
                                        continue;
                                    }
                                    //  stack trace
                                    //[30-Sep-2023 11:28:52 UTC] PHP Stack trace:
                                    $pattern = "/PHP Stack trace:/";
                                    if (preg_match($pattern, $line, $matches)) {
                                        continue;
                                    }
                                    $pattern =
                                        "/\d{4}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} UTC\] PHP \d+\./";
                                    if (preg_match($pattern, $line, $matches)) {
                                        continue;
                                    }
                                    //  end stack trace
                                    // Javascript ?
                                    if (strpos($line, "Javascript") !== false) {
                                        $is_javascript = true;
                                    } else {
                                        $is_javascript = false;
                                    }
                                    if ($is_javascript) {
                                        $matches = [];
                                        // die($line);
                                        $apattern = [];
                                        $apattern[] =
                                            "/(Error|Syntax|Type|TypeError|Reference|ReferenceError|Range|Eval|URI|Error .*?): (.*?) - URL: (https?:\/\/\S+).*?Line: (\d+).*?Column: (\d+).*?Error object: ({.*?})/";
                                        //$apattern[] =
                                        //    "/(Error|Syntax|Type|TypeError|Reference|ReferenceError|Range|Eval|URI|Error .*?): (.*?) - URL: (https?:\/\/\S+).*?Line: (\d+)/";
                                        $apattern[] =
                                            "/(SyntaxError|Error|Syntax|Type|TypeError|Reference|ReferenceError|Range|Eval|URI|Error .*?): (.*?) - URL: (https?:\/\/\S+).*?Line: (\d+)/";
                                        // Google Maps !
                                        //$apattern[] = "/Script error(?:\. - URL: (https?:\/\/\S+))?/i";
                                        $pattern = $apattern[0];
                                        for ($j = 0; $j < count($apattern); $j++) {
                                            if (
                                                preg_match($apattern[$j], $line, $matches)
                                            ) {
                                                $pattern = $apattern[$j];
                                                break;
                                            }
                                        }
                                        if (preg_match($pattern, $line, $matches)) {
                                            $matches[1] = str_replace(
                                                "Javascript ",
                                                "",
                                                $matches[1]
                                            );
                                            // $filteredDate = strstr(substr($line, 1, 26), ']', true);
                                            if (preg_match("/\[(.*?)\]/", $line, $dateMatches)) {
                                                $filteredDate = $dateMatches[1];
                                            } else {
                                                $filteredDate = '';
                                            }
                                            // die(var_export(substr($line, 1, 25)));
                                            // $filteredDate = substr($line, 1, 20);
                                            if (count($matches) == 2) {
                                                $log_entry = [
                                                    "Date" => $filteredDate,
                                                    "Message Type" => "Script error",
                                                    "Problem Description" => "N/A",
                                                    "Script URL" => $matches[1],
                                                    "Line" => "N/A",
                                                ];
                                            } else {
                                                $log_entry = [
                                                    "Date" => $filteredDate,
                                                    "Message Type" => $matches[1],
                                                    "Problem Description" => $matches[2],
                                                    "Script URL" => $matches[3],
                                                    "Line" => $matches[4],
                                                ];
                                            }
                                            $script_path = $matches[3];
                                            $script_info = pathinfo($script_path);
                                            // Dividir o nome do script com base em ":"
                                            $parts = explode(":", $script_info["basename"]);
                                            // O nome do script agora está na primeira parte
                                            $scriptName = $parts[0];
                                            $log_entry["Script Name"] = $scriptName; // Get the script name
                                            $log_entry["Script Location"] =
                                                $script_info["dirname"]; // Get the script location
                                            if ($log_entry["Script Location"] == 'http:' or $log_entry["Script Location"] == 'https:')
                                                $log_entry["Script Location"] = $matches[3];
                                            if (
                                                strpos(
                                                    $log_entry["Script URL"],
                                                    "/wp-content/plugins/"
                                                ) !== false
                                            ) {
                                                // O erro ocorreu em um plugin
                                                $parts = explode(
                                                    "/wp-content/plugins/",
                                                    $log_entry["Script URL"]
                                                );
                                                if (count($parts) > 1) {
                                                    $plugin_parts = explode("/", $parts[1]);
                                                    $log_entry["File Type"] = "Plugin";
                                                    $log_entry["Plugin Name"] =
                                                        $plugin_parts[0];
                                                    //   $log_entry["Script Location"] =
                                                    //      "/wp-content/plugins/" .
                                                    //       $plugin_parts[0];
                                                }
                                            } elseif (
                                                strpos(
                                                    $log_entry["Script URL"],
                                                    "/wp-content/themes/"
                                                ) !== false
                                            ) {
                                                // O erro ocorreu em um tema
                                                $parts = explode(
                                                    "/wp-content/themes/",
                                                    $log_entry["Script URL"]
                                                );
                                                if (count($parts) > 1) {
                                                    $theme_parts = explode("/", $parts[1]);
                                                    $log_entry["File Type"] = "Theme";
                                                    $log_entry["Theme Name"] =
                                                        $theme_parts[0];
                                                    // $log_entry["Script Location"] =
                                                    //     "/wp-content/themes/" .
                                                    //     $theme_parts[0];
                                                }
                                            } else {
                                                // Caso não seja um tema nem um plugin, pode ser necessário ajustar o comportamento aqui.
                                                //$log_entry["Script Location"] = $matches[1];
                                            }
                                            // Extrair o nome do script do URL
                                            $script_name = basename(
                                                wp_parse_url(
                                                    $log_entry["Script URL"],
                                                    PHP_URL_PATH
                                                )
                                            );
                                            $log_entry["Script Name"] = $script_name;
                                            //echo $line."\n";
                                            if (isset($log_entry["Date"])) {
                                                echo "DATE: " . esc_html($log_entry["Date"]) . "\n";
                                            }
                                            if (isset($log_entry["Message Type"])) {
                                                echo "MESSAGE TYPE: (Javascript) " . esc_html($log_entry["Message Type"]) . "\n";
                                            }
                                            if (isset($log_entry["Problem Description"])) {
                                                echo "PROBLEM DESCRIPTION: " . esc_html($log_entry["Problem Description"]) . "\n";
                                            }
                                            if (isset($log_entry["Script Name"])) {
                                                echo "SCRIPT NAME: " . esc_html($log_entry["Script Name"]) . "\n";
                                            }
                                            if (isset($log_entry["Line"])) {
                                                echo "LINE: " . esc_html($log_entry["Line"]) . "\n";
                                            }
                                            if (isset($log_entry["Column"])) {
                                                //	echo "COLUMN: {$log_entry['Column']}\n";
                                            }
                                            if (isset($log_entry["Error Object"])) {
                                                //	echo "ERROR OBJECT: {$log_entry['Error Object']}\n";
                                            }
                                            if (isset($log_entry["Script Location"])) {
                                                echo "SCRIPT LOCATION: " . esc_html($log_entry["Script Location"]) . "\n";
                                            }
                                            if (isset($log_entry["Plugin Name"])) {
                                                echo "PLUGIN NAME: " . esc_html($log_entry["Plugin Name"]) . "\n";
                                            }
                                            if (isset($log_entry["Theme Name"])) {
                                                echo "THEME NAME: " . esc_html($log_entry["Theme Name"]) . "\n";
                                            }
                                            echo "------------------------\n";
                                            continue;
                                        } else {
                                            // echo "-----------x-------------\n";
                                            echo esc_html($line);
                                            echo "\n-----------x------------\n";
                                        }
                                        continue;
                                        // END JAVASCRIPT
                                    } else {
                                        // ---- PHP // 
                                        // continue;
                                        $apattern = [];
                                        $apattern[] =
                                            "/^\[.*\] PHP (Warning|Error|Notice|Fatal error|Parse error): (.*) in \/([^ ]+) on line (\d+)/";
                                        $apattern[] =
                                            "/^\[.*\] PHP (Warning|Error|Notice|Fatal error|Parse error): (.*) in \/([^ ]+):(\d+)$/";
                                        $pattern = $apattern[0];
                                        for ($j = 0; $j < count($apattern); $j++) {
                                            if (
                                                preg_match($apattern[$j], $line, $matches)
                                            ) {
                                                $pattern = $apattern[$j];
                                                break;
                                            }
                                        }
                                        if (preg_match($pattern, $line, $matches)) {
                                            //die(var_export($matches));
                                            // $filteredDate = strstr(substr($line, 1, 26), ']', true);
                                            if (preg_match("/\[(.*?)\]/", $line, $dateMatches)) {
                                                $filteredDate = $dateMatches[1];
                                            } else {
                                                $filteredDate = '';
                                            }
                                            $log_entry = [
                                                "Date" => $filteredDate,
                                                "News Type" => $matches[1],
                                                "Problem Description" => antihacker_bill_strip_strong99(
                                                    $matches[2]
                                                ),
                                            ];
                                            $script_path = $matches[3];
                                            $script_info = pathinfo($script_path);
                                            // Dividir o nome do script com base em ":"
                                            $parts = explode(":", $script_info["basename"]);
                                            // O nome do script agora está na primeira parte
                                            $scriptName = $parts[0];
                                            $log_entry["Script Name"] = $scriptName; // Get the script name
                                            $log_entry["Script Location"] =
                                                $script_info["dirname"]; // Get the script location
                                            $log_entry["Line"] = $matches[4];
                                            // Check if the "Script Location" contains "/plugins/" or "/themes/"
                                            if (
                                                strpos(
                                                    $log_entry["Script Location"],
                                                    "/plugins/"
                                                ) !== false
                                            ) {
                                                // Extract the plugin name
                                                $parts = explode(
                                                    "/plugins/",
                                                    $log_entry["Script Location"]
                                                );
                                                if (count($parts) > 1) {
                                                    $plugin_parts = explode("/", $parts[1]);
                                                    $log_entry["File Type"] = "Plugin";
                                                    $log_entry["Plugin Name"] =
                                                        $plugin_parts[0];
                                                }
                                            } elseif (
                                                strpos(
                                                    $log_entry["Script Location"],
                                                    "/themes/"
                                                ) !== false
                                            ) {
                                                // Extract the theme name
                                                $parts = explode(
                                                    "/themes/",
                                                    $log_entry["Script Location"]
                                                );
                                                if (count($parts) > 1) {
                                                    $theme_parts = explode("/", $parts[1]);
                                                    $log_entry["File Type"] = "Theme";
                                                    $log_entry["Theme Name"] =
                                                        $theme_parts[0];
                                                }
                                            }
                                        } else {
                                            // stack trace...
                                            $pattern = "/\[.*?\] PHP\s+\d+\.\s+(.*)/";
                                            preg_match($pattern, $line, $matches);
                                            if (!preg_match($pattern, $line)) {
                                                echo "-----------y-------------\n";
                                                echo esc_html($line);
                                                echo "\n-----------y------------\n";
                                            }
                                            continue;
                                        }
                                        //$in_error_block = false; // End the error block
                                        $logs[] = $log_entry; // Add this log entry to the array of logs
                                        foreach ($logs as $log) {
                                            if (isset($log["Date"])) {
                                                echo 'DATE: ' . esc_html($log["Date"]) . "\n";
                                            }
                                            if (isset($log["News Type"])) {
                                                echo 'MESSAGE TYPE: ' . esc_html($log["News Type"]) . "\n";
                                            }
                                            if (isset($log["Problem Description"])) {
                                                echo 'PROBLEM DESCRIPTION: ' . esc_html($log["Problem Description"]) . "\n";
                                            }
                                            // Check if the 'Script Name' key exists before printing
                                            if (isset($log["Script Name"]) && !empty(trim($log["Script Name"]))) {
                                                echo 'SCRIPT NAME: ' . esc_html($log["Script Name"]) . "\n";
                                            }
                                            // Check if the 'Line' key exists before printing
                                            if (isset($log["Line"])) {
                                                echo 'LINE: ' . esc_html($log["Line"]) . "\n";
                                            }
                                            // Check if the 'Script Location' key exists before printing
                                            if (isset($log["Script Location"])) {
                                                echo 'SCRIPT LOCATION: ' . esc_html($log["Script Location"]) . "\n";
                                            }
                                            // Check if the 'File Type' key exists before printing
                                            if (isset($log["File Type"])) {
                                                // echo "FILE TYPE: " . esc_html($log["File Type"]) . "\n";
                                            }
                                            // Check if the 'Plugin Name' key exists before printing
                                            if (isset($log["Plugin Name"]) && !empty(trim($log["Plugin Name"]))) {
                                                echo 'PLUGIN NAME: ' . esc_html($log["Plugin Name"]) . "\n";
                                            }
                                            // Check if the 'Theme Name' key exists before printing
                                            if (isset($log["Theme Name"])) {
                                                echo 'THEME NAME: ' . esc_html($log["Theme Name"]) . "\n";
                                            }
                                            echo "------------------------\n";
                                        }
                                    }
                                    // end if PHP ...
                                } // end for...
                                echo "</textarea>";
                            }
                            echo "<br />";
                        }
                    } // end for next each error_log...
                } // end fo next each folder...
            } // end tem errors...
            echo "</div>";
        } // end function site_health_tab_content($tab)
        public function add_site_health_link_to_admin_toolbar($wp_admin_bar)
        {
            $logourl = plugin_dir_url(__FILE__) . "bell.png";
            $wp_admin_bar->add_node([
                "id" => "site-health",
                "title" =>
                '<span style="background-color: #ff0000; color: #fff; display: flex; align-items: center; padding: 0px 10px  0px 10px; ">' .
                    '<span style="border-radius: 50%; padding: 4px; display: inline-block; width: 20px; height: 20px; text-align: center; font-size: 12px; background-color: #ff0000; background-image: url(\'' .
                    esc_url($logourl) .
                    '\'); background-repeat: no-repeat; background-position: 0 6px; background-size: 20px;"></span> ' .
                    '<span style="background-color: #ff0000; color: #fff;">Site Health Issues</span>' .
                    "</span>",
                "href" => admin_url("site-health.php?tab=Critical+Issues"),
            ]);
        }
        public function custom_help_tab()
        {
            $screen = get_current_screen();
            // Verifique se você está na página desejada
            if ("site-health" === $screen->id) {
                // Adicione uma guia de ajuda
                $message = esc_attr__(
                    "These are critical issues that can have a significant impact on your site's performance. They can cause many plugins and functionalities to malfunction and, in some cases, render your site completely inoperative, depending on their severity. Address them promptly.",
                    'antihacker'
                );
                $screen->add_help_tab([
                    "id"      => "custom-help-tab",
                    "title"   => esc_attr__("Critical Issues", 'antihacker'),
                    "content" => "<p>" . $message . "</p>",
                ]);
            }
        }
        // add_action("admin_head", "custom_help_tab");
    } // end class
    /*
$plugin_slug = "database-backup"; // Replace with your actual text domain
$plugin_text_domain = "database-backup"; // Replace with your actual text domain
$notification_url = "https://wpmemory.com/fix-low-memory-limit/";
$notification_url2 =
    "https://billplugin.com/site-language-error-can-crash-your-site/";
*/
    $diagnose_instance = antihacker_Bill_Diagnose::get_instance(
        $notification_url,
        $notification_url2,
    );
    update_option("antihacker_bill_show_warnings", date("Y-m-d"));
