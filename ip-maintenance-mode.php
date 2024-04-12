<?php
/*
 * Plugin Name: IP Maintenance Mode
 * Version: 1.2.10
 * Description: Display a maintenance mode page, except when logged in as an administrator or using the /?view=1 parameter in the URL.
 * Author: Ivan Petermann
 * Author URI: https://ivanpetermann.com
 * Requires at least: 4.0
 * Tested up to: 6.4
 *
 * Text Domain: ip-maintenance-mode
 * Domain Path: /languages/
 *
 * @package WordPress
 * @author Ivan Petermann
 * @since 1.2.10
 */

/*
 * Copyright (c) 2023
 * Author: Ivan Petermann
 * Email: contato@ivanpetermann.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Maintenance Page
 *
 * Displays the coming soon page for visitors who are not logged in.
 * The login page is excluded, allowing you to log in if necessary.
 *
 * @return void
 */

// Check if the request is from a local address (e.g., '127.0.0.1') or via wp-cli, and if so, exit early
if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || defined('WP_CLI')) {
    return; // Local and wp-cli requests are handled differently; no further processing needed
}

// Check if it's a POST request to the wp-json endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) {
    // If it is, skip further processing for this case
    return; // No action needed for POST requests to wp-json
}

/**
 * For Oxygen Builder CSS
 * Check if the query string contains "xlink=css"
 */
if (isset($_GET['xlink']) && $_GET['xlink'] === 'css') {
    return;
}

function ip_maintenance_mode()
{
    // Start the session
    if (!session_id())
        @session_start();

    global $pagenow;
    $_ip_view_site   = (isset($_SESSION['_ipmp_view_site_']) and $_SESSION['_ipmp_view_site_'] == 'true') ? true : false;

    if ((isset($_GET['view']) and $_GET['view'] == '0') or (isset($_GET['versite']) and $_GET['versite'] == '0')) {
        $_SESSION['_ipmp_view_site_'] = false;
        wp_redirect(home_url());
        exit;
    } elseif ((isset($_GET['view']) and $_GET['view'] == '1') or (isset($_GET['versite']) and $_GET['versite'] == '1')) {
        $_SESSION['_ipmp_view_site_'] = true;
        wp_redirect(home_url());
        exit;
    }

    if ($pagenow !== 'wp-login.php' && $_ip_view_site !== true && !current_user_can('manage_options') && !is_admin() && !is_user_logged_in()) {

        $_ip_previa_path = $_SERVER['DOCUMENT_ROOT'] . '/previa/';
        if (is_dir($_ip_previa_path)) {
            wp_redirect('/previa/');
        } else {

            // Define $page
            $development_page_id = 0;

            // Check if the function "get_page_by_path" is available (introduced in newer WordPress versions)
            if (function_exists('get_page_by_path')) {
                // Array containing possible slugs
                $slugs = array('under-development', 'em-desenvolvimento');

                // Iterate over the slugs
                foreach ($slugs as $slug) {
                    // Try to get the page by slug
                    $development_page = get_page_by_path($slug);
                    // If the page is found, stop the iteration
                    if ($development_page && isset($development_page->ID)) {
                        $development_page_id = $development_page->ID;
                        break;
                    }
                }
            }

            // Check if the page is found
            if ($development_page_id > 0) {

                $_GET['page_id'] = $development_page_id;

                add_action('send_headers', 'ip_maintenance_mode_send_header', 99);

                /** Loads the WordPress Environment and Template */
                require $_SERVER['DOCUMENT_ROOT'] . '/wp-blog-header.php';
            } elseif (file_exists(plugin_dir_path(__FILE__) . 'views/maintenance.php')) {
                ip_maintenance_mode_send_header();
                @header('Content-Type: text/html; charset=utf-8');
                require_once plugin_dir_path(__FILE__) . 'views/maintenance.php';
            }
        }
        exit();
    }
}

add_action('wp_loaded', 'ip_maintenance_mode');

function ip_maintenance_mode_send_header()
{
    @header('HTTP/1.1 503 Service Temporarily Unavailable');
    @header('Status: 503 Service Temporarily Unavailable');
    @header('Retry-After: 300');
    @header('Cache-Control: no-store, must-revalidate');
    @header('Pragma: no-cache');
    @header('Expires: 0');
}
