<?php

/**
 * @author William Sergio Minozzi
 * @copyright 2021
 */
if (!defined('ABSPATH'))
   exit; // Exit if accessed directly 


//global $antihacker_last_plugin_scan;



?>

<div class="antihacker-block-title">
   <?php esc_attr_e('Must Use Plugins', 'antihacker'); ?>
</div>




<?php




// Get all Must-Use plugins
$mu_plugins = get_mu_plugins();

// Start HTML table
echo '<div class="wrap">';
echo '<h2><strong>' . esc_html__('Must-Use Plugins', 'antihacker') . '</strong></h2>';
echo '<br>';
echo esc_html__("Must-Use plugins, located in the `mu-plugins` directory, are automatically activated WordPress plugins that cannot be disabled via the admin panel, often used for critical security or functionality. Regular inspection ensures they are updated and were intentionally installed and not introduced by malware or hackers. Verify their source and integrity to maintain a secure environment.", 'antihacker');
echo '<br>';
echo '<br>';
echo '<table class="wp-list-table widefat plugins striped">';
echo '<thead>';
echo '<tr>';
echo '<th scope="col"><strong>' . esc_html__('Plugin', 'antihacker') . '</strong></th>';
echo '<th scope="col"><strong>' . esc_html__('Description', 'antihacker') . '</strong></th>';
echo '<th scope="col"><strong>' . esc_html__('Version', 'antihacker') . '</strong></th>';
echo '<th scope="col"><strong>' . esc_html__('Status', 'antihacker') . '</strong></th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

// Check if there are Must-Use plugins
if (empty($mu_plugins)) {
   echo '<tr><td colspan="' . esc_attr('4') . '">' . esc_html__('No Must-Use plugins found.', 'antihacker') . '</td></tr>';
} else {
   foreach ($mu_plugins as $plugin_file => $plugin_data) {
      // Sanitize data for display
      $name = !empty($plugin_data['Name']) ? esc_html($plugin_data['Name']) : esc_html($plugin_file);
      $description = !empty($plugin_data['Description']) ? esc_html($plugin_data['Description']) : esc_html__('No description', 'antihacker');
      $version = !empty($plugin_data['Version']) ? esc_html($plugin_data['Version']) : esc_html__('Not specified', 'antihacker');
      $status = esc_html__('Active', 'antihacker'); // Mu-plugins are always active

      echo '<tr>';
      echo '<td>' . esc_html__($name) . '</td>';
      echo '<td>' . esc_html__($description) . '</td>';
      echo '<td>' . esc_html__($version) . '</td>';
      echo '<td>' . esc_html__($status) . '</td>';
      echo '</tr>';
   }
}

echo '</tbody>';
echo '</table>';
echo '</div>';











//die();
return;



// require_once(ANTIHACKERPATH . "includes/functions/plugin-check-list.php");

//debug2();

// ssh://server/home/minozzi/public_html/wp-content/plugins/antihacker/includes/functions/plugin-check-list.php




//         action: '$antihacker_check_plugins_and_display_results',
// add_action('wp_ajax_$antihacker_check_plugins_and_display_results', '$antihacker_check_plugins_and_display_results');

if (isset($_GET['notif'])) {
   $notif = sanitize_text_field($_GET['notif']);
   if ($notif == 'plugins')
      update_option('antihacker_last_plugin_scan', time());
}
if (isset($_GET['action'])) {
   $action = sanitize_text_field($_GET['action']);
   if ($action == 'scan') {
      update_option('antihacker_last_plugin_scan', time());
      flush();
      //debug2();
      antihacker_scan_plugins();
      return;
   }
}

//debug2();

$timeout = time() > ($antihacker_last_plugin_scan + 60 * 60 * 24 * 3);
$timeout = time() > ($antihacker_last_plugin_scan + 10);
$site = ANTIHACKERHOMEURL . "admin.php?page=anti_hacker_plugin&tab=plugins&notif=";


?>


<div id="antihacker-notifications-page">
   <div class="antihacker-block-title">
      <?php esc_attr_e('Check Plugins', 'antihacker'); ?>
   </div>
   <div id="notifications-tab">
      <b>
         <?php esc_attr_e('Check Plugins for updates.', 'antihacker'); ?>
      </b>
      <br>
      <?php esc_attr_e('This test will check all your plugins against WordPress repository to see 
    if they are updated last one year. Plugins not updated last one year
    are suspect to be abandoned and we suggest replacing them.', 'antihacker'); ?>
      <br>
      <br>
      <?php
      $timeout_plugin = time() > ($antihacker_last_plugin_scan + 60 * 60 * 24 * 365);

      if (!$timeout_plugin) {
         echo esc_attr__('Last check for updates made (Y-M-D):', 'antihacker') . ' ';
         echo esc_attr(date('Y-m-d', $antihacker_last_plugin_scan));
      }
      ?>
      <br>
      <br>
      <button id="check-plugins-button" class="button button-primary"><?php esc_attr_e('Check Plugins Now', 'antihacker'); ?></button>
   </div>
   <div id="result-container" style="display: none; padding:20px;">
      <!-- Conteúdo do resultado aqui -->
      <center>
         Please, wait...
         <br>
         <br>
         <img id="antihacker_spinner" alt="antihacker_spinner" src="<?php echo esc_attr(ANTIHACKERIMAGES); ?>/spinner.gif" width="50px" style="opacity:.5" ; />

      </center>
   </div>
</div>