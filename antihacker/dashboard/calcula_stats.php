<?php

/**
 * @author William Sergio Minossi
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly



global $wpdb;
$table_name = $wpdb->prefix . "ah_stats";





/* debug

// 1) Pega os dados (mantenha sua query atual)
$results9 = $wpdb->get_results($wpdb->prepare("SELECT date, qtotal FROM %i", $table_name));

// 2) Normaliza e cria um mapa: 'MMDD' => qtotal
$map = [];
foreach ($results9 as $row) {
    // Garante string, remove não-dígitos e repõe zeros à esquerda
    $key = str_pad(preg_replace('/\D/', '', (string)$row->date), 4, '0', STR_PAD_LEFT);
    $map[$key] = is_numeric($row->qtotal) ? 0 + $row->qtotal : 0; // força numérico
}
//return;


// 3) Gera os últimos 15 dias (do mais antigo para o mais recente)
$dias = 15;
$serie = [];          // array de estruturas {date:'dd/mm', md:'MMDD', qtotal:n}
$labels_md = [];      // opcional: só MMDD
$labels_dm = [];      // opcional: só dd/mm
$values = [];         // opcional: só os valores


//return;


for ($i = $dias - 1; $i >= 0; $i--) {
    $tm   = strtotime("-$i days");
    $md   = date('md', $tm);     // 'MMDD' para lookup
    $dm   = date('d/m', $tm);    // 'dd/mm' para exibir
    $val  = isset($map[$md]) ? $map[$md] : 0;

    $serie[]    = ['date' => $dm, 'md' => $md, 'qtotal' => $val];
    $labels_md[] = $md;
    $labels_dm[] = $dm;
    $values[]    = $val;
}

// 4) Impressões para conferir no debug:

// a) Lista legível: "dd/mm (MMDD): valor"
foreach ($serie as $r) {
    echo $r['date'] . ' (' . $r['md'] . '): ' . $r['qtotal'] . PHP_EOL;
}

// b) Se você ainda quiser seus dois arrays sincronizados:
print_r($labels_md); // ou $labels_dm se preferir 'dd/mm'
print_r($values);
return;

*/
global $wpdb;
$table_name = $wpdb->prefix . "ah_stats";

// Pega os dados
$results9 = $wpdb->get_results("SELECT date, qtotal FROM `$table_name`");

// Converte em array simples (garante string com 4 dígitos)
$results8 = [];
foreach ($results9 as $row) {
    $results8[] = [
        'date'   => str_pad($row->date, 4, '0', STR_PAD_LEFT),
        'qtotal' => (int)$row->qtotal,
    ];
}

// Últimos 15 dias
$d = 15;
$array30d = [];
$array30  = [];

for ($x = 0; $x < $d; $x++) {
    $tm = strtotime("-$x days");
    $md = date("md", $tm);

    $mykey = array_search($md, array_column($results8, 'date'));
    $array30d[$x] = $md;
    $array30[$x]  = ($mykey !== false) ? $results8[$mykey]['qtotal'] : 0;
}

$array30  = array_reverse($array30);
$array30d = array_reverse($array30d);

return;


// OLD ...



















/*
  $sql = "CREATE TABLE ".$table. " (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `date` varchar(4) NOT NULL,
        `qlogin` text NOT NULL,
        `qfire` text NOT NULL,
        `qtotal` varchar(100) NOT NULL,
    UNIQUE (`id`),
    UNIQUE (`date`)
    ) $charset_collate;";
*/
global $wpdb;
$table_name = $wpdb->prefix . "ah_stats";

//$query = "SELECT date,qtotal FROM " . $table_name;
//$results9 = $wpdb->get_results($query);

/*
$query = $wpdb->prepare(
    "SELECT date, qtotal FROM %i",
    $table_name
);
*/

$results9 = $wpdb->get_results($wpdb->prepare("SELECT date, qtotal FROM %i", $table_name));



$results8 = json_decode(json_encode($results9), true);
unset($results9);
$x = 0;
$d = 15;
for ($i = $d; $i > 0; $i--) {
    $timestamp = time();
    $tm = 86400 * ($x); // 60 * 60 * 24 = 86400 = 1 day in seconds
    $tm = $timestamp - $tm;
    $the_day = date("d", $tm);
    $this_month = date('m', $tm);
    $array30d[$x] = $this_month . $the_day;
    //$_dia = 'dia_';
    $mykey = array_search(trim($array30d[$x]), array_column($results8, 'date'));
    // if ($mykey) {
    if ($mykey !== false) {
        // $awork = array_column( $results8 , 'qtotal');
        // $array30[$x] = $awork[$key];
        // objeto:
        // $array30[$x] = $results9[$key]->qtotal;
        // 
        $awork = $results8[$mykey]['qtotal'];
        $array30[$x] = $awork;
    } else
        $array30[$x] = 0;
    $x++;
}
$array30 = array_reverse($array30);
$array30d = array_reverse($array30d);
print_r($array30);
echo '<p></p>';
//die();
