<?php

/**
 * Plugin Name: Bill Catch Errors
 * Description: Captures JavaScript errors and logs them on the server.
 * Version: 4.1
 * Author: Bill Minozzi
 * Author URI: https://BillMinozzi.com
 * Text Domain: bill-catch-errors
 * License:     GPL2
 */
if (!defined("ABSPATH")) {
    die("Invalid request.");
}
// 2 2025 ==========================
if (function_exists('is_multisite') && is_multisite()) {
    return;
}
$bill_format_error_log_data = get_transient('bill_error_log_date_format');
if (!$bill_format_error_log_data) {
    $bill_format_error_log_info = bill_get_error_log_date_string();
    $bill_format_error_log_data = bill_detect_error_log_date_format($bill_format_error_log_info);
}

/*
die(var_dump($bill_format_error_log_data));
string(11) "d-M-Y H:i:s"
*/


function bill_get_error_log_date_string()
{

    $error_log_path = ini_get("error_log");
    if (!empty($error_log_path)) {
        $error_log_path = trim($error_log_path);
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $error_log_path = trailingslashit(WP_CONTENT_DIR) . 'debug.log';
        } else {
            $error_log_path = trailingslashit(ABSPATH) . 'error_log';
        }
    }
    $logFile = $error_log_path;


    // Check if log file exists and is readable
    if (!file_exists($logFile) || !is_readable($logFile)) {
        // debug5("Log file not found or not readable: $logFile");
        return bill_fallback_date_format();
    }
    $logFileHandle = fopen($logFile, 'r'); // Open file
    if (!$logFileHandle) {
        // debug5("Failed to open the log file: $logFile");
        return bill_fallback_date_format();
    }
    $firstLine = fgets($logFileHandle); // Read first line
    fclose($logFileHandle); // Close the file handle
    // debug5("First line of log file: " . var_export($firstLine, true));
    // Extract date from log line
    if (preg_match('/^\[(.*?)\]/', trim($firstLine), $matches)) {
        $dateString = $matches[1];
        // debug5("Extracted date string: $dateString");
        return $dateString;
    } else {
        // debug5("No date found in the first line. Falling back.");
        return bill_fallback_date_format();
    }
}

function bill_fallback_date_format()
{
    $default_format = 'Y-m-d H:i:s';
    return $default_format;
}
function bill_splitDate($date)
{
    // Lista de padrões para diferentes formatos
    $patterns = [
        '/^(\d{1,2})[-\/\s]([A-Za-z]+)[-\/\s](\d{4})$/',  // 22-Jan-2025, 22/Jan/2025, 22 Jan 2025
        '/^(\d{1,2})[-\/\s](\d{1,2})[-\/\s](\d{4})$/',     // 22-01-2025, 22/01/2025, 22 01 2025
        '/^(\d{4})[-\/\s](\d{1,2})[-\/\s](\d{1,2})$/'      // 2025-01-22, 2025/01/22, 2025 01 22
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $date, $matches)) {
            return array_slice($matches, 1, 3); // Retorna apenas as 3 partes da data
        }
    }
    return false;
}
function bill_split_line($logLine)
{
    $fuso = false;
    $numeroDeEspacos = substr_count($logLine, ' ');
    // debug5("Número de espaços em branco: $numeroDeEspacos");
    if ($numeroDeEspacos < 1) {
        return false;  // Se não houver data e hora adequadas
    }
    // Verificar o número de espaços e tratar de forma distinta
    if ($numeroDeEspacos > 2) {
        $partes = preg_split('/\s+/', $logLine);  // Divide por espaços em branco
        // debug5($partes);
        $data = array_slice($partes, 0, 3);
        $horaArray = preg_grep('/:/', $partes);
        $hora = reset($horaArray); // Pega o primeiro valor encontrado
        // debug5($hora);
        $lastElement = end($partes); // Obtém o último elemento
        if (strpos($lastElement, ':') === false) {
            $fuso = $lastElement;
        }
        // debug5($fuso);
        $listaFusos = timezone_identifiers_list();
        if ($fuso && !in_array($fuso, $listaFusos)) {
            // debug5("Fuso inválido detectado: $fuso");
            $fuso = null;  // Se não for um fuso válido, descartamos
        } else {
            // debug5("Fuso válido: $fuso");
        }
        return [
            'data' => $data,
            'fuso' => $fuso
        ];
    } elseif ($numeroDeEspacos <= 2) {
        // Se tiver até 2 espaços, apenas data e hora e talvez Fuso
        $partes = preg_split('/\s+/', $logLine);  // Divide por espaços em branco
        // debug5("Partes separadas: " . print_r($partes, true));
        if (strlen($partes[0]) > 4) {
            //      $partes = ['02-Feb-2025', '12:34:56', 'UTC'];
            $r = bill_splitDate($partes[0]);
            // Substituir o primeiro elemento por 3 novos elementos
            // array_splice($partes, 0, 1, ['NovaData', 'NovaHora', 'NovaUTC']);
            array_splice($partes, 0, 1, $r);
        }
        // debug5($partes);
        if (count($partes) < 4) {
            //$retornar =  bill_splitDate($partes[0]);
            // debug5($partes);
            // return $retornar;
            return [
                'data' => $partes,
                'fuso' => ''
            ];
            return $return;
        }
        // Identifica a hora (parte sempre estará em segundo lugar)
        $horaArray = preg_grep('/:/', $partes);
        //$hora = reset($horaArray); // Pega o primeiro valor encontrado
        // debug5($hora);
        $lastElement = end($partes); // Obtém o último elemento
        if (strpos($lastElement, ':') === false) {
            $fuso = $lastElement;
        }
        $retornar_data = array_slice($partes, 0, 3);
        return [
            'data' => $retornar_data,
            'fuso' => $fuso
        ];
    } else {
        // Se tiver apenas 1 espaço (ou nenhum), não há informações suficientes
        return null;
    }
}

/* Detect date format 
A função analisa uma linha de log ($logLine) para detectar o formato de data em inglês 
(ex.: "29 January 2025 12:44:30" ou "02-Feb-2025 12:34:56 UTC") 
e retorna uma string no formato reconhecido com hora e, opcionalmente, fuso horário. 
Ela armazena o formato detectado em um transient para uso futuro.
*/
function bill_detect_error_log_date_format_old($logLine)
{
    /*
    $logLine = "29 janeiro 2025";  // Exemplo de data
    $logLine = "02-Feb-2025 12:34:56 UTC";
    $logLine = "21 03 2025 12:34:56 UTC";
    $logLine = "22-Jan-2025 15:51:28 UTC";
    $logLine = "29 janeiro 2025 12:44:30";
    $logLine = "29 january 2025 12:44:30";
    */
    // Passo 1: Separar a string por espaços em branco
    $data_inteira = $logLine;
    $bill_used_separators = ['-', '/', ' '];
    $bill_separador_used_in_error_log_date = null;
    foreach ($bill_used_separators as $separator) {
        if (strpbrk($data_inteira, $separator) !== false) {
            $bill_separador_used_in_error_log_date = $separator;
            break;
        }
    }
    // Passo 1: Separar a string por espaços em branco e retorna array.
    $r = bill_split_line($data_inteira);
    $data_partes = $r['data'];
    $fuso = $r['fuso'];
    // debug5($data_partes);
    // get dia, mes, ano
    foreach ($data_partes as $data_parte) {
        // debug5($data_parte);
        // 1a parte... 22-Jan-2025
        if (isset($dia) && isset($mes) && isset($ano)) {
            break;
        }
        try {
            if (!isset($mes) && preg_match('/[a-zA-Z]+/', $data_parte)) {
                // Se contém letras, é o mês
                $mes = $data_parte;
                // debug5($mes);
            } elseif (!isset($ano) && strlen($data_parte) == 4 && is_numeric($data_parte)) {
                // Se tem 4 caracteres e é numérico, é o ano
                $ano = $data_parte;
                // debug5($ano);
            } elseif (!isset($dia) && is_numeric($data_parte) && strlen($data_parte) <= 2) {
                // Se tem 1 ou 2 caracteres e é numérico, pode ser o dia
                if (intval($data_parte) > 12 && intval($data_parte) <= 31) {
                    $dia = $data_parte;
                    // debug5($dia);
                }
            } elseif (!isset($mes)) {
                // Se for <= 12, pode ser mês ou dia, mas assumimos mês por enquanto
                $mes = $data_parte;
                // debug5($mes);
            }
            if (!isset($dia)) {
                //return;
                $dia = $data_parte;
                // debug5($dia);
            }
            // Se todos os elementos foram identificados, podemos sair do loop
            if (isset($dia) && isset($mes) && isset($ano)) {
                break;
            }
        } catch (Exception $e) {
            $format = '';
            break;
            // echo 'Message: ' .$e->getMessage();
        }
    }
    $original = $logLine; // Data original
    // Comparar e montar o formato
    if ($dia && $mes && $ano) {
        try {
            // Definir o separador padrão
            $separador = ($bill_separador_used_in_error_log_date !== null) ? $bill_separador_used_in_error_log_date : ' ';
            if (strpos($original, $dia) !== false && strpos($original, $mes) !== false && strpos($original, $ano) !== false) {
                // Se estiver na ordem dia-mês-ano
                // debug5();
                if (preg_match('/[a-zA-Z]/', $mes)) {
                    // Se o mês tem letras (ex: janeiro, feb, etc.)
                    $format = "d{$separador}M{$separador}Y";  // Exemplo: 29-jan-2025
                } else {
                    // Se o mês tem apenas números (ex: 01, 02, 03)
                    $format = "d{$separador}m{$separador}Y";  // Exemplo: 29-01-2025
                }
            } elseif (strpos($original, $mes) !== false && strpos($original, $dia) !== false && strpos($original, $ano) !== false) {
                // Se o mês e o dia estiverem invertidos
                // debug5();
                if (preg_match('/[a-zA-Z]/', $mes)) {
                    // Se o mês tem letras
                    $format = "M{$separador}d{$separador}Y";  // Exemplo: jan-29-2025
                } else {
                    // Se o mês tem números
                    $format = "m{$separador}d{$separador}Y";  // Exemplo: 01-29-2025
                }
            } elseif (strpos($original, $ano) !== false && strpos($original, $mes) !== false && strpos($original, $dia) !== false) {
                // Caso o ano esteja antes do mês e do dia
                // debug5();
                if (strlen($ano) == 4) {
                    // Se o ano tem 4 caracteres (ex: 2025)
                    if (preg_match('/[a-zA-Z]/', $mes)) {
                        // Se o mês tem letras
                        $format = "Y{$separador}M{$separador}d";  // Exemplo: 2025-jan-29
                    } else {
                        // Se o mês tem números
                        $format = "Y{$separador}m{$separador}d";  // Exemplo: 2025-01-29
                    }
                } else {
                    // Caso o ano seja representado com 2 dígitos (ex: 25)
                    if (preg_match('/[a-zA-Z]/', $mes)) {
                        $format = "y{$separador}M{$separador}d";  // Exemplo: 25-jan-29
                    } else {
                        $format = "y{$separador}m{$separador}d";  // Exemplo: 25-01-29
                    }
                }
            }
        } catch (Exception $e) {
            $format = '';
            // echo 'Message: ' .$e->getMessage();
        }
    }
    if ($format !== null) {
        $format = trim($format);
    }
    if (empty($format)) {
        $format = bill_fallback_date_format();
    }
    // Garantir que `H:i:s` seja adicionado apenas uma vez
    if (strpos($format, 'H:i:s') === false) {
        $format .= " H:i:s";
    }
    set_transient('bill_error_log_date_format', $format, 30 * DAY_IN_SECONDS);
    // Verificar se o fuso horário é válido
    $listaFusos = timezone_identifiers_list();
    if (!empty($fuso) && in_array($fuso, $listaFusos)) {
        $retorno = date($format) . ' ' . $fuso;
    } else {
        $retorno =  date($format);
    }
    $date_test = date($format);
    // Verifica se a data gerada corresponde ao formato esperado
    if ($date_test) {
        return $retorno;
    } else {
        return bill_fallback_date_format();
    }
} // end function


function bill_detect_error_log_date_format($logLine)
{
    $bill_used_separators = ['-', '/', ' '];
    $bill_separador_used_in_error_log_date = null;
    foreach ($bill_used_separators as $separator) {
        if (strpbrk($logLine, $separator) !== false) {
            $bill_separador_used_in_error_log_date = $separator;
            break;
        }
    }

    $r = bill_split_line($logLine);
    $data_partes = $r['data'];
    $fuso = $r['fuso'];

    $dia = null;
    $mes = null;
    $ano = null;
    $numeros_menores_12 = 0; // Contador para detectar ambiguidade

    foreach ($data_partes as $data_parte) {
        if (isset($dia) && isset($mes) && isset($ano)) {
            break;
        }
        try {
            if (!isset($mes) && preg_match('/[a-zA-Z]+/', $data_parte)) {
                $mes = $data_parte; // Mês com letras, sem ambiguidade
            } elseif (!isset($ano) && strlen($data_parte) == 4 && is_numeric($data_parte)) {
                $ano = $data_parte; // Ano com 4 dígitos, sem ambiguidade
            } elseif (!isset($dia) && is_numeric($data_parte) && strlen($data_parte) <= 2) {
                $valor = intval($data_parte);
                if ($valor > 12 && $valor <= 31) {
                    $dia = $data_parte; // Dia claro, sem ambiguidade
                } elseif ($valor <= 12) {
                    $numeros_menores_12++; // Incrementa contador de ambiguidade
                    if (!isset($dia)) {
                        $dia = $data_parte; // Pode ser dia ou mês
                    } elseif (!isset($mes)) {
                        $mes = $data_parte; // Pode ser dia ou mês
                    }
                }
            }
        } catch (Exception $e) {
            return bill_fallback_date_format();
        }
    }

    // Se houver ambiguidade (dois números <= 12), retorna fallback
    if ($numeros_menores_12 >= 2 && !isset($mes) || (isset($dia) && isset($mes) && intval($dia) <= 12 && intval($mes) <= 12)) {
        //debug4("Ambiguidade detectada na data: $logLine");
        return bill_fallback_date_format();
    }

    $original = $logLine;
    $format = '';
    if ($dia && $mes && $ano) {
        $separador = $bill_separador_used_in_error_log_date ?? ' ';
        if (strpos($original, $dia) !== false && strpos($original, $mes) !== false && strpos($original, $ano) !== false) {
            if (preg_match('/[a-zA-Z]/', $mes)) {
                $format = "d{$separador}M{$separador}Y";
            } else {
                $format = "d{$separador}m{$separador}Y";
            }
        } elseif (strpos($original, $mes) !== false && strpos($original, $dia) !== false && strpos($original, $ano) !== false) {
            if (preg_match('/[a-zA-Z]/', $mes)) {
                $format = "M{$separador}d{$separador}Y";
            } else {
                $format = "m{$separador}d{$separador}Y";
            }
        } elseif (strpos($original, $ano) !== false && strpos($original, $mes) !== false && strpos($original, $dia) !== false) {
            if (strlen($ano) == 4) {
                $format = preg_match('/[a-zA-Z]/', $mes) ? "Y{$separador}M{$separador}d" : "Y{$separador}m{$separador}d";
            } else {
                $format = preg_match('/[a-zA-Z]/', $mes) ? "y{$separador}M{$separador}d" : "y{$separador}m{$separador}d";
            }
        }
    }

    if (empty($format)) {
        $format = bill_fallback_date_format();
    }
    if (strpos($format, 'H:i:s') === false) {
        $format .= " H:i:s";
    }

    set_transient('bill_error_log_date_format', $format, 30 * DAY_IN_SECONDS);
    $listaFusos = timezone_identifiers_list();
    $retorno = !empty($fuso) && in_array($fuso, $listaFusos) ? date($format) . ' ' . $fuso : date($format);

    return $retorno;
}



add_action("wp_ajax_bill_minozzi_js_error_catched", "bill_minozzi_js_error_catched");
add_action("wp_ajax_nopriv_bill_minozzi_js_error_catched", "bill_minozzi_js_error_catched");

function bill_minozzi_js_error_catched()
{

    $bill_format_error_log_data = get_transient('bill_error_log_date_format');
    if (!$bill_format_error_log_data) {
        $bill_format_error_log_data = bill_fallback_date_format();
    }
    $error_log_updated = "NOT OK!";

    if (!isset($_REQUEST) || !isset($_REQUEST["bill_js_error_catched"])) {
        wp_die("empty error");
    }
    if (!wp_verify_nonce(sanitize_text_field($_POST["_wpnonce"]), "bill-catch-js-errors")) {
        status_header(406, "Invalid nonce");
        wp_die("Bad Nonce!");
    }

    $bill_js_error_catched = sanitize_text_field($_REQUEST["bill_js_error_catched"]);
    $bill_js_error_catched = trim($bill_js_error_catched);
    if (empty($bill_js_error_catched)) {
        wp_die("empty error");
    }


    // Split the error message
    $errors = explode(" | ", $bill_js_error_catched);

    // Configuração do arquivo de log (fora do loop)
    $logFile = ini_get("error_log");
    if (!empty($logFile)) {
        $logFile = trim($logFile);
    }
    if (empty($logFile)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $logFile = trailingslashit(WP_CONTENT_DIR) . 'debug.log';
        } else {
            $logFile = trailingslashit(ABSPATH) . 'error_log';
        }
    }

    $dir = dirname($logFile);
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            wp_die("Folder doesn't exist and unable to create: " . $dir);
        }
    }
    if (!is_writable($dir) || !is_readable($dir)) {
        if (!chmod($dir, 0755)) {
            wp_die("Log file directory does not have adequate permissions: " . $dir);
        }
        if (!is_writable($dir) || !is_readable($dir)) {
            wp_die("Log file directory does not have adequate permissions (2): " . $dir);
        }
    }


    // Loop para gravar os erros
    foreach ($errors as $error) {
        $parts = explode(" - ", $error);
        if (count($parts) < 3) {
            continue;
        }
        $errorMessage = $parts[0];
        $errorURL = $parts[1];
        $errorLine = $parts[2];
        $logMessage = "Javascript " . $errorMessage . " - " . $errorURL . " - " . $errorLine;
        $formattedMessage = "[" . date($bill_format_error_log_data) . "] - " . $logMessage . PHP_EOL;

        //$ret_error_log = false;
        if (error_log($formattedMessage, 3, $logFile)) {
            //$ret_error_log = true;
            $error_log_updated = "OK!";
        } else {
            try {
                $r = file_put_contents($logFile, $formattedMessage, FILE_APPEND | LOCK_EX);
                if ($r) {
                    $error_log_updated = "OK!";
                } else {
                    $timestamp_string = strval(time());
                    update_option('bill_minozzi_error_log_status', $timestamp_string);
                }
            } catch (Exception $e) {
                wp_die("Fail to write at error_log " . $e->getMessage());
            }
        }
    }

    die($error_log_updated);
}

class bill_minozzi_bill_catch_errors
{
    public function __construct()
    {
        add_action("wp_head", [$this, "add_bill_javascript_to_header"]);
        add_action("admin_head", [$this, "add_bill_javascript_to_header"]);
        // $this->gravar2(__LINE__);
    }

    public function add_bill_javascript_to_header()
    {
        // $this->gravar2(__LINE__);
        $nonce = wp_create_nonce("bill-catch-js-errors");
        $ajax_url = esc_js($this->get_ajax_url()) . "?action=bill_minozzi_js_error_catched&_wpnonce=" . $nonce;
?>
        <script>
            var errorQueue = [];
            let timeout;

            var errorMessage = '';


            function isBot() {
                const bots = ['crawler', 'spider', 'baidu', 'duckduckgo', 'bot', 'googlebot', 'bingbot', 'facebook', 'slurp', 'twitter', 'yahoo'];
                const userAgent = navigator.userAgent.toLowerCase();
                return bots.some(bot => userAgent.includes(bot));
            }

            /*
            window.onerror = function(msg, url, line) {
            // window.addEventListener('error', function(event) {
                console.error("Linha 600");

                var errorMessage = [
                    'Message: ' + msg,
                    'URL: ' + url,
                    'Line: ' + line
                ].join(' - ');
                */


            // Captura erros síncronos e alguns assíncronos
            window.addEventListener('error', function(event) {
                var msg = event.message;
                if (msg === "Script error.") {
                    console.error("Script error detected - maybe problem cross-origin");
                    return;
                }
                errorMessage = [
                    'Message: ' + msg,
                    'URL: ' + event.filename,
                    'Line: ' + event.lineno
                ].join(' - ');
                if (isBot()) {
                    return;
                }
                errorQueue.push(errorMessage);
                handleErrorQueue();
            });

            // Captura rejeições de promessas
            window.addEventListener('unhandledrejection', function(event) {
                errorMessage = 'Promise Rejection: ' + (event.reason || 'Unknown reason');
                if (isBot()) {
                    return;
                }
                errorQueue.push(errorMessage);
                handleErrorQueue();
            });

            // Função auxiliar para gerenciar a fila de erros
            function handleErrorQueue() {
                if (errorQueue.length >= 5) {
                    sendErrorsToServer();
                } else {
                    clearTimeout(timeout);
                    timeout = setTimeout(sendErrorsToServer, 5000);
                }
            }





            function sendErrorsToServer() {
                if (errorQueue.length > 0) {
                    var message;
                    if (errorQueue.length === 1) {
                        // Se houver apenas um erro, mantenha o formato atual
                        message = errorQueue[0];
                    } else {
                        // Se houver múltiplos erros, use quebras de linha para separá-los
                        message = errorQueue.join('\n\n');
                    }
                    var xhr = new XMLHttpRequest();
                    var nonce = '<?php echo esc_js($nonce); ?>';
                    var ajaxurl = '<?php echo $ajax_url; ?>';
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

            function sendErrorsToServer() {
                if (errorQueue.length > 0) {
                    var message = errorQueue.join(' | ');
                    //console.error(message);
                    var xhr = new XMLHttpRequest();
                    var nonce = '<?php echo esc_js($nonce); ?>';
                    var ajaxurl = '<?php echo $ajax_url; ?>'; // No need to esc_js here
                    xhr.open('POST', encodeURI(ajaxurl));
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            //console.log('Success:::', xhr.responseText);
                        } else {
                            console.log('Error:', xhr.status);
                        }
                    };
                    xhr.onerror = function() {
                        console.error('Request failed');
                    };
                    xhr.send('action=bill_minozzi_js_error_catched&_wpnonce=' + nonce + '&bill_js_error_catched=' + encodeURIComponent(message));
                    errorQueue = []; // Clear the error queue after sending
                }
            }

            function sendErrorsToServer() {
                if (errorQueue.length > 0) {
                    var message = errorQueue.join('\n\n'); // Usa duas quebras de linha como separador
                    var xhr = new XMLHttpRequest();
                    var nonce = '<?php echo esc_js($nonce); ?>';
                    var ajaxurl = '<?php echo $ajax_url; ?>';
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
new bill_minozzi_bill_catch_errors();
?>