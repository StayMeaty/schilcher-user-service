<?php
/**
 * Debug User Data
 * Direct PHP file to display all user data and metadata in JSON format
 * Usage: /wp-content/plugins/schilcher-user-service/debug-user.php?user_id=123
 */

// Security: Basic protection against unauthorized access
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    // Only allow in debug mode or with admin token
    $admin_token = $_GET['token'] ?? '';
    if ($admin_token !== 'schilcher_debug_2025') {
        http_response_code(403);
        die(json_encode(['error' => 'Unauthorized access. Debug mode required or valid token needed.']));
    }
}

// Find WordPress installation path
$wp_load_paths = [
    __DIR__ . '/../../../../wp-load.php',  // Standard plugin path
    __DIR__ . '/../../../wp-load.php',    // Alternative path
    __DIR__ . '/../../wp-load.php',       // Another alternative
    __DIR__ . '/../wp-load.php',          // Direct in plugins
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    http_response_code(500);
    die(json_encode(['error' => 'Could not load WordPress. Please check file paths.']));
}

// Check user permissions (additional security layer)
if (!current_user_can('manage_options') && (!defined('WP_DEBUG') || !WP_DEBUG)) {
    http_response_code(403);
    die(json_encode(['error' => 'Administrator privileges required.']));
}

// Get user ID from URL parameter
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    http_response_code(400);
    die(json_encode(['error' => 'user_id parameter is required. Usage: debug-user.php?user_id=123']));
}

// Validate user ID
$user_id = intval($user_id);
if ($user_id <= 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid user_id. Must be a positive integer.']));
}

// Get user data
$user = get_user_by('ID', $user_id);

if (!$user) {
    http_response_code(404);
    die(json_encode(['error' => 'User not found with ID: ' . $user_id]));
}

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Gather all user data
$debug_data = [
    'timestamp' => current_time('c'),
    'user_id' => $user_id,
    'wp_user_data' => [
        'ID' => $user->ID,
        'user_login' => $user->user_login,
        'user_nicename' => $user->user_nicename,
        'user_email' => $user->user_email,
        'user_url' => $user->user_url,
        'user_registered' => $user->user_registered,
        'user_activation_key' => $user->user_activation_key,
        'user_status' => $user->user_status,
        'display_name' => $user->display_name,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'nickname' => $user->nickname,
        'description' => $user->description,
        'rich_editing' => $user->rich_editing,
        'syntax_highlighting' => $user->syntax_highlighting,
        'comment_shortcuts' => $user->comment_shortcuts,
        'admin_color' => $user->admin_color,
        'use_ssl' => $user->use_ssl,
        'show_admin_bar_front' => $user->show_admin_bar_front,
        'locale' => $user->locale,
    ],
    'user_roles' => $user->roles,
    'user_capabilities' => $user->caps,
    'all_user_meta' => [],
    'schilcher_b2b_data' => [],
    'b2bking_data' => [],
    'woocommerce_data' => [],
    'other_plugin_data' => []
];

// Get ALL user metadata
global $wpdb;
$all_meta = $wpdb->get_results($wpdb->prepare(
    "SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id = %d ORDER BY meta_key",
    $user_id
), ARRAY_A);

foreach ($all_meta as $meta) {
    $meta_key = $meta['meta_key'];
    $meta_value = maybe_unserialize($meta['meta_value']);
    
    // Store in main metadata array
    $debug_data['all_user_meta'][$meta_key] = $meta_value;
    
    // Categorize metadata by prefix/plugin
    if (strpos($meta_key, 'schilcher_b2b_') === 0) {
        $debug_data['schilcher_b2b_data'][$meta_key] = $meta_value;
    } elseif (strpos($meta_key, 'b2bking_') === 0) {
        $debug_data['b2bking_data'][$meta_key] = $meta_value;
    } elseif (strpos($meta_key, 'billing_') === 0 || 
              strpos($meta_key, 'shipping_') === 0 || 
              strpos($meta_key, '_woocommerce_') === 0 ||
              strpos($meta_key, 'woocommerce_') === 0) {
        $debug_data['woocommerce_data'][$meta_key] = $meta_value;
    } elseif (!in_array($meta_key, [
        'nickname', 'first_name', 'last_name', 'description',
        'rich_editing', 'syntax_highlighting', 'comment_shortcuts',
        'admin_color', 'use_ssl', 'show_admin_bar_front', 'locale',
        'wp_capabilities', 'wp_user_level'
    ])) {
        $debug_data['other_plugin_data'][$meta_key] = $meta_value;
    }
}

// Add B2B specific analysis
if (!empty($debug_data['schilcher_b2b_data']) || !empty($debug_data['b2bking_data'])) {
    $debug_data['b2b_analysis'] = [
        'is_b2b_user' => get_user_meta($user_id, 'b2bking_b2buser', true) === 'yes',
        'approval_status' => get_user_meta($user_id, 'schilcher_b2b_approval_status', true),
        'b2bking_approved' => get_user_meta($user_id, 'b2bking_account_approved', true),
        'registration_date' => get_user_meta($user_id, 'schilcher_b2b_registration_date', true),
        'company_name' => get_user_meta($user_id, 'schilcher_b2b_company_name', true),
        'email_general' => get_user_meta($user_id, 'schilcher_b2b_email_general', true),
        'phone' => get_user_meta($user_id, 'schilcher_b2b_phone', true),
        'vat_id' => get_user_meta($user_id, 'schilcher_b2b_vat_id', true),
    ];
}

// Add WordPress user analysis
$debug_data['wp_analysis'] = [
    'user_exists' => true,
    'can_edit_posts' => user_can($user_id, 'edit_posts'),
    'can_manage_options' => user_can($user_id, 'manage_options'),
    'is_super_admin' => is_super_admin($user_id),
    'last_login' => get_user_meta($user_id, 'last_login', true),
    'session_tokens' => get_user_meta($user_id, 'session_tokens', true),
];

// Check if plugins are active
$debug_data['plugin_status'] = [
    'b2bking_active' => function_exists('b2bking_get_role_id'),
    'woocommerce_active' => class_exists('WooCommerce'),
    'schilcher_plugin_active' => class_exists('Schilcher_User_Service'),
];

// Add summary counts
$debug_data['summary'] = [
    'total_meta_entries' => count($debug_data['all_user_meta']),
    'schilcher_entries' => count($debug_data['schilcher_b2b_data']),
    'b2bking_entries' => count($debug_data['b2bking_data']),
    'woocommerce_entries' => count($debug_data['woocommerce_data']),
    'other_entries' => count($debug_data['other_plugin_data']),
    'user_roles_count' => count($debug_data['user_roles']),
    'capabilities_count' => count($debug_data['user_capabilities']),
];

// Output JSON with pretty formatting
echo json_encode($debug_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>