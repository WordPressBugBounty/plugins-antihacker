<?php

/**
 * @author William Sergio Minozzi
 * @copyright 2021 - 2025
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the get_plugins() function
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugins = get_plugins(); // Get the list of all installed plugins
$selected_plugin_file_path = isset($_POST['plugin_file_path']) ? sanitize_text_field($_POST['plugin_file_path']) : '';

?>

<div id="antihacker-notifications-page">
    <div class="antihacker-block-title">
        <?php esc_attr_e('Plugin Integrity Checker', 'antihacker'); ?>
    </div>
    <div id="notifications-tab">
        <div class="wrap" id="antihacker-theme-help-wrapper" style="opacity: 1;">
            <?php
            if (!class_exists('ZipArchive')) {
                echo '<div class="notice notice-error"><p><strong>' . esc_html__('Critical Error:', 'antihacker') . '</strong> ' . esc_html__('The PHP ZipArchive extension is required for this feature to function, but it is not installed or enabled on your server. Please contact your server administrator.', 'antihacker') . '</p></div>';
            }
            ?>
            <p><?php esc_html_e('Select a plugin and click the button below to verify its file integrity against the WordPress repository.', 'antihacker'); ?></p>
            <form method="post" action="" id="integrity-check-form" style="margin-bottom: 20px;">
                <?php wp_nonce_field('antihacker_integrity_check_nonce', 'antihacker_integrity_check_nonce'); ?>
                <label for="plugin_file_path"><?php esc_html_e('Select a plugin:', 'antihacker'); ?></label>
                <select name="plugin_file_path" id="plugin_file_path">
                    <option value=""><?php esc_html_e('-- Select --', 'antihacker'); ?></option>
                    <?php if (is_array($plugins) && !empty($plugins)) : ?>
                        <?php foreach ($plugins as $plugin_file => $plugin_data) : ?>
                            <option value="<?php echo esc_attr($plugin_file); ?>" <?php selected($selected_plugin_file_path, $plugin_file); ?>>
                                <?php echo esc_html($plugin_data['Name']) . ' (' . esc_html($plugin_file) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <option value=""><?php esc_html_e('No plugins found', 'antihacker'); ?></option>
                    <?php endif; ?>
                </select>
                <button type="submit" name="run_integrity_check" id="run-integrity-check" class="button button-primary" style="margin-top: -1px !important">
                    <?php esc_html_e('Check Integrity', 'antihacker'); ?>
                </button>
            </form>
            <div id="integrity-results" style="margin-top: 20px;">
                <?php
                if (isset($_POST['run_integrity_check']) && isset($_POST['antihacker_integrity_check_nonce']) && wp_verify_nonce($_POST['antihacker_integrity_check_nonce'], 'antihacker_integrity_check_nonce') && current_user_can('manage_options')) {
                    if ($selected_plugin_file_path) {



                        try {
                            $current_memory_limit = ini_get('memory_limit');
                            if ($current_memory_limit !== false && $current_memory_limit !== '-1') {
                                // Convert current memory limit to bytes for comparison
                                $current_bytes = antihacker_convert_to_bytes($current_memory_limit);
                                $desired_bytes = antihacker_convert_to_bytes('256M');
                                if ($current_bytes < $desired_bytes) {
                                    $test_memory_limit = '256M';
                                    ini_set('memory_limit', $test_memory_limit);
                                    if (ini_get('memory_limit') !== $test_memory_limit) {
                                        //echo '<div class="notice notice-warning"><p>' . esc_html__('Warning: Unable to increase memory limit. Current limit is ' . $current_memory_limit . '. Contact your server administrator to increase the PHP memory_limit.', 'antihacker') . '</p></div>';
                                    }
                                }
                            } else {
                                //echo '<div class="notice notice-warning"><p>' . esc_html__('Warning: Memory limit is set to unlimited or cannot be determined. Proceeding with current settings.', 'antihacker') . '</p></div>';
                            }
                        } catch (Exception $e) {
                            //echo '<div class="notice notice-error"><p>' . esc_html__('Error checking or setting memory limit: ' . $e->getMessage() . '. Contact your server administrator.', 'antihacker') . '</p></div>';
                        }

                        set_time_limit(300); // Increase execution time
                        // Capture output of antihacker_compare_plugin_files
                        ob_start();
                        antihacker_compare_plugin_files($selected_plugin_file_path);
                        $output = ob_get_clean();
                        echo $output;
                    } else {
                        echo '<p style="color: red;">' . esc_html__('Please select a plugin to check.', 'antihacker') . '</p>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php
// Main function to compare plugin files
function antihacker_compare_plugin_files($plugin_main_file_path)
{
    // Define files to ignore for specific plugins
    $files_to_ignore = [
        [
            'plugin_slug' => 'antihacker',
            'files' => ['rules.txt', 'rules2.txt']
        ]
    ];


    if (!class_exists('ZipArchive')) {
        echo '<div class="notice notice-error"><p><strong>' . esc_html__('Error:', 'antihacker') . '</strong> ' . esc_html__('The PHP ZipArchive extension is required and not available.', 'antihacker') . '</p></div>';
        return;
    }

    // Determine the plugin slug for the API and local paths
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_main_file_path);

    $local_plugin_version = $plugin_data['Version'];
    $local_plugin_name = $plugin_data['Name'];

    // The "slug" for the API is usually the plugin directory name.
    // For single-file plugins, use the file name without .php, or a specific repository slug.
    $api_slug = '';
    if (strpos($plugin_main_file_path, '/') !== false) { // Plugin is in a subdirectory
        $api_slug = dirname($plugin_main_file_path);
    } else { // Plugin is a single file in the root of /plugins
        $api_slug = !empty($plugin_data['slug']) ? $plugin_data['slug'] : str_replace('.php', '', basename($plugin_main_file_path));
    }
    $local_plugin_directory_slug = dirname($plugin_main_file_path); // Used to build local paths. If '.', it’s the root of /plugins.

    echo '<br>';
    echo '<hr>';

    echo '<h4>' . esc_html__('Checking Plugin:', 'antihacker') . ' ' . esc_html($local_plugin_name) . ' (' . esc_html__('API Slug:', 'antihacker') . ' ' . esc_html($api_slug) . ', ' . esc_html__('Local Version:', 'antihacker') . ' ' . esc_html($local_plugin_version) . ')</h4>';

    // Query the WordPress.org API
    $args = [
        'slug'   => $api_slug,
        'fields' => [
            'version'       => true,
            'download_link' => true,
            'versions'      => true, // To get links for specific versions
        ],
    ];
    $response = plugins_api('plugin_information', $args);

    if (is_wp_error($response)) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Error querying the WordPress API for slug', 'antihacker') . ' "' . esc_html($api_slug) . '": ' . esc_html($response->get_error_message()) . '</p></div>';
        return;
    }

    if (empty($response->download_link)) {
        echo '<div class="notice notice-warning"><p>' . esc_html__('Plugin with slug', 'antihacker') . ' "' . esc_html($api_slug) . '" ' . esc_html__('not found in the official repository or download link unavailable.', 'antihacker') . '</p></div>';
        return;
    }

    // Try to get the download link for the same version as installed
    $zip_url_to_download = '';
    $version_to_compare = '';

    if (!empty($response->versions) && isset($response->versions[$local_plugin_version])) {
        // The format for a specific version link is usually: https://downloads.wordpress.org/plugin/SLUG.VERSION.zip
        if (
            preg_match('#/plugin/([^/.]+)\.(?:[0-9.]+|trunk)\.zip$#i', $response->download_link, $matches) ||
            preg_match('#/plugin/([^/.]+)\.zip$#i', $response->download_link, $matches)
        ) {
            $download_slug_in_link = $matches[1];
            $zip_url_to_download = "https://downloads.wordpress.org/plugin/{$download_slug_in_link}.{$local_plugin_version}.zip";
            $version_to_compare = $local_plugin_version;
        }
    }

    // If no specific version link was found, use the latest stable version link
    if (empty($zip_url_to_download)) {
        $zip_url_to_download = $response->download_link;
        $version_to_compare = $response->version;

        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_main_file_path;

        if ($version_to_compare !== $local_plugin_version) {
            echo '<div class="notice notice-warning"><p>' . sprintf(
                esc_html__('The local plugin version (%s) differs from the repository version used for comparison (%s). The comparison will use version %s from the repository. This may result in many differences if you are not using that version.', 'antihacker'),
                esc_html($local_plugin_version),
                esc_html($version_to_compare),
                '<strong>' . esc_html($version_to_compare) . '</strong>'
            ) . '</p></div>';
        }
    }
    echo '<p>' . esc_html__('Comparing with repository version:', 'antihacker') . ' <strong>' . esc_html($version_to_compare) . '</strong></p>';

    // Download the plugin ZIP using download_url()
    $temp_zip_file = download_url($zip_url_to_download, 300); // 300-second timeout

    if (is_wp_error($temp_zip_file)) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Error downloading the plugin ZIP file:', 'antihacker') . ' ' . esc_html($temp_zip_file->get_error_message()) . '</p>';
        echo '<p>' . sprintf(
            esc_html__('This may be due to permission issues in the server’s temporary directory (%s), firewall settings, or the download link may be incorrect/unavailable for this version.', 'antihacker'),
            '<code>' . esc_html(get_temp_dir()) . '</code>'
        ) . '</p></div>';
        return;
    }

    // Process the ZIP with ZipArchive
    $repo_files_hashes = [];
    $zip = new ZipArchive();

    if ($zip->open($temp_zip_file) === TRUE) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename_in_zip = $stat['name'];

            // Ignore directories (usually end with /)
            if (substr($filename_in_zip, -1) === '/') {
                continue;
            }

            // Normalize the file path in the ZIP to the format "plugin-slug/path/file.php"
            $path_for_comparison = $filename_in_zip;
            $parts = explode('/', $filename_in_zip);
            $zip_root_dir_or_file = $parts[0];

            // Ensure we’re dealing with files within the main plugin directory in the ZIP
            if ($zip_root_dir_or_file !== $api_slug && count($parts) === 1) {
                if (strpos($plugin_main_file_path, '/') === false) {
                    // Single-file plugin, don’t prefix
                } else {
                    $path_for_comparison = $api_slug . '/' . $filename_in_zip;
                }
            } elseif ($zip_root_dir_or_file !== $api_slug && count($parts) > 1) {
                continue;
            }

            $file_content = $zip->getFromIndex($i);
            if ($file_content !== false) {
                $repo_files_hashes[$path_for_comparison] = hash('sha256', $file_content);
            } else {
                echo '<p class="notice notice-warning">' . esc_html__('Warning: Could not read file', 'antihacker') . ' ' . esc_html($filename_in_zip) . ' ' . esc_html__('from ZIP.', 'antihacker') . '</p>';
            }
        }
        $zip->close();
    } else {
        echo '<div class="notice notice-error"><p>' . sprintf(
            esc_html__('Error opening the downloaded ZIP file (%s). The file may be corrupted, an error HTML (e.g., 404), or inaccessible.', 'antihacker'),
            '<code>' . esc_html(basename($temp_zip_file)) . '</code>'
        ) . '</p></div>';
        @unlink($temp_zip_file);
        return;
    }

    // Clean up temporary ZIP file
    @unlink($temp_zip_file);

    // Get hashes of local files
    $local_files_hashes = [];
    if ($local_plugin_directory_slug === '.') { // Single-file plugin in the root of /plugins
        $full_local_path = WP_PLUGIN_DIR . '/' . $plugin_main_file_path;
        if (is_file($full_local_path)) {
            $local_files_hashes[basename($plugin_main_file_path)] = hash_file('sha256', $full_local_path);
        }
    } else { // Plugin in a subdirectory
        $local_plugin_actual_dir = WP_PLUGIN_DIR . '/' . $local_plugin_directory_slug;
        $local_files_hashes = antihacker_get_local_files_relative($local_plugin_actual_dir, $api_slug);
    }

    // Compare files
    if (empty($local_files_hashes) && empty($repo_files_hashes)) {
        echo '<p>' . esc_html__('No files found locally or in the ZIP for comparison.', 'antihacker') . '</p>';
        return;
    }

    $all_relative_paths = array_unique(array_merge(array_keys($local_files_hashes), array_keys($repo_files_hashes)));
    sort($all_relative_paths);

    $diffs_found = false;
    $summary = ['identical' => 0, 'modified' => 0, 'local_only' => 0, 'repo_only' => 0];
    $results = [];

    foreach ($all_relative_paths as $relative_path) {
        // Check if the file should be ignored
        $ignore_file = false;
        foreach ($files_to_ignore as $ignore_entry) {
            if ($api_slug === $ignore_entry['plugin_slug'] && in_array(basename($relative_path), $ignore_entry['files'])) {
                $ignore_file = true;
                break;
            }
        }

        if ($ignore_file) {
            continue; // Skip this file if it's in the ignore list
        }

        $local_exists = isset($local_files_hashes[$relative_path]);
        $repo_exists = isset($repo_files_hashes[$relative_path]);

        if ($local_exists && $repo_exists) {
            if ($local_files_hashes[$relative_path] === $repo_files_hashes[$relative_path]) {
                $summary['identical']++;
            } else {
                $results[] = [$relative_path, esc_html__('Modified', 'antihacker')];
                $diffs_found = true;
                $summary['modified']++;
            }
        } elseif ($local_exists && !$repo_exists) {
            $results[] = [$relative_path, esc_html__('Extra local file (does not exist in repository)', 'antihacker')];
            $diffs_found = true;
            $summary['local_only']++;
        } elseif (!$local_exists && $repo_exists) {
            $results[] = [$relative_path, esc_html__('Missing locally (exists in repository)', 'antihacker')];
            $diffs_found = true;
            $summary['repo_only']++;
        }
    }

    // Display results in a table
    if (!$diffs_found) {
        echo '<p style="color: green; font-weight: bold; font-size: 1.2em;">' . sprintf(
            esc_html__('No differences found. Local plugin files match the repository (version %s).', 'antihacker'),
            esc_html($version_to_compare)
        ) . '</p>';
    } else {
        echo '<p style="color: red;">' . esc_html__('Some plugin files may have been modified, are extra, or are missing:', 'antihacker') . '</p>';
        echo '<table class="widefat striped"><thead><tr><th style="text-align: left;">' . esc_html__('File', 'antihacker') . '</th><th>' . esc_html__('Reason', 'antihacker') . '</th></tr></thead><tbody>';

        $max_results = 200;
        $count = 0;
        foreach ($results as $issue) {
            if ($count++ >= $max_results) {
                echo '<tr><td colspan="2">' . esc_html__('Additional issues not shown (limit reached).', 'antihacker') . '</td></tr>';
                break;
            }
            echo '<tr><td style="text-align: left;">' . esc_html($issue[0]) . '</td><td>' . esc_html($issue[1]) . '</td></tr>';
        }

        echo '</tbody></table>';
    }

    // Display summary
    echo '<h4>' . esc_html__('Verification Summary:', 'antihacker') . '</h4>';
    echo '<ul>';
    echo '<li>' . esc_html__('Identical files:', 'antihacker') . ' ' . esc_html($summary['identical']) . '</li>';
    if (!empty($summary['modified'])) {
        echo '<li><strong style="color: red;">' . esc_html__('Modified files:', 'antihacker') . ' ' . esc_html($summary['modified']) . '</strong></li>';
    }
    if (!empty($summary['local_only'])) {
        echo '<li><strong style="color: orange;">' . esc_html__('Extra local files:', 'antihacker') . ' ' . esc_html($summary['local_only']) . '</strong></li>';
    }
    if (!empty($summary['repo_only'])) {
        echo '<li><strong style="color: blue;">' . esc_html__('Missing local files:', 'antihacker') . ' ' . esc_html($summary['repo_only']) . '</strong></li>';
    }
    echo '</ul>';
}

// Function to list local files and calculate hashes with relative paths
function antihacker_get_local_files_relative($plugin_local_abs_dir, $expected_root_dir_in_zip)
{
    $files_hashes = [];
    if (!is_dir($plugin_local_abs_dir)) {
        echo '<p class="notice notice-error">' . sprintf(
            esc_html__('Local plugin directory (%s) does not exist.', 'antihacker'),
            '<code>' . esc_html($plugin_local_abs_dir) . '</code>'
        ) . '</p>';
        return $files_hashes;
    }

    // Normalize the base path to ensure it ends with /
    $plugin_local_abs_dir_normalized = rtrim($plugin_local_abs_dir, '/\\') . '/';

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($plugin_local_abs_dir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file_info) {
            if ($file_info->isFile()) {
                $full_path = $file_info->getPathname();
                $relative_to_plugin_dir_root = str_replace($plugin_local_abs_dir_normalized, '', $full_path);
                $relative_to_plugin_dir_root = ltrim($relative_to_plugin_dir_root, '/\\');

                // Build the comparison path, prefixed with the expected ZIP root directory
                $path_for_comparison = $expected_root_dir_in_zip . '/' . $relative_to_plugin_dir_root;

                $files_hashes[$path_for_comparison] = hash_file('sha256', $full_path);
            }
        }
    } catch (UnexpectedValueException $e) {
        echo '<p class="notice notice-error">' . sprintf(
            esc_html__('Error reading local plugin directory (%s): %s. Check server permissions or restrictions (e.g., open_basedir).', 'antihacker'),
            '<code>' . esc_html($plugin_local_abs_dir) . '</code>',
            esc_html($e->getMessage())
        ) . '</p>';
        return [];
    } catch (Exception $e) {
        echo '<p class="notice notice-error">' . sprintf(
            esc_html__('Unexpected error reading local plugin directory (%s): %s.', 'antihacker'),
            '<code>' . esc_html($plugin_local_abs_dir) . '</code>',
            esc_html($e->getMessage())
        ) . '</p>';
        return [];
    }

    ksort($files_hashes); // Sort by filename for consistency
    return $files_hashes;
}

function antihacker_convert_to_bytes($memory_string)
{
    $value = (int) $memory_string;
    $unit = strtoupper(substr(trim($memory_string), -1));
    switch ($unit) {
        case 'G':
            return $value * 1024 * 1024 * 1024; // Gigabytes to bytes
        case 'M':
            return $value * 1024 * 1024; // Megabytes to bytes
        case 'K':
            return $value * 1024; // Kilobytes to bytes
        default:
            return $value; // Assume bytes if no unit
    }
}
