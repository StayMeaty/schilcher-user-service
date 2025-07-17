<?php
/**
 * Plugin Name: Schilcher User Service
 * Plugin URI: https://www.schilcher-kaese.de
 * Description: Complete user management system for Schilcher Käse with B2B registration, login, password reset, and account management features.
 * Version: 1.0.0
 * Author: Lukas Peschel
 * Author URI: https://www.schilcher-kaese.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: schilcher-user-service
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SCHILCHER_USER_SERVICE_VERSION', '1.0.0');
define('SCHILCHER_USER_SERVICE_PLUGIN_FILE', __FILE__);
define('SCHILCHER_USER_SERVICE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCHILCHER_USER_SERVICE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCHILCHER_USER_SERVICE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Schilcher_User_Service {

    /**
     * Single instance of the plugin
     *
     * @var Schilcher_User_Service
     */
    private static $instance;

    /**
     * Plugin components
     *
     * @var array
     */
    private $components = array();

    /**
     * Get single instance of the plugin
     *
     * @return Schilcher_User_Service
     */
    public static function instance() {
        if (!isset(self::$instance) && !(self::$instance instanceof Schilcher_User_Service)) {
            self::$instance = new Schilcher_User_Service();
            self::$instance->setup();
        }
        return self::$instance;
    }

    /**
     * Setup the plugin
     */
    private function setup() {
        // Load required files
        $this->load_dependencies();

        // Initialize components
        $this->init_components();

        // Setup hooks
        $this->setup_hooks();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once SCHILCHER_USER_SERVICE_PLUGIN_DIR . 'includes/class-schilcher-email-manager.php';
        require_once SCHILCHER_USER_SERVICE_PLUGIN_DIR . 'includes/class-schilcher-user-manager.php';
        require_once SCHILCHER_USER_SERVICE_PLUGIN_DIR . 'includes/class-schilcher-validator.php';
        require_once SCHILCHER_USER_SERVICE_PLUGIN_DIR . 'includes/class-schilcher-admin-manager.php';
        require_once SCHILCHER_USER_SERVICE_PLUGIN_DIR . 'includes/class-schilcher-shortcodes.php';
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->components['email_manager'] = Schilcher_Email_Manager::get_instance();
        $this->components['user_manager'] = Schilcher_User_Manager::get_instance();
        $this->components['validator'] = Schilcher_Registration_Validator::get_instance();
        $this->components['admin_manager'] = Schilcher_Admin_Manager::get_instance();
        $this->components['shortcodes'] = Schilcher_Shortcodes::get_instance();

        // Initialize admin manager
        $this->components['admin_manager']->init();
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Plugin activation/deactivation hooks
        register_activation_hook(SCHILCHER_USER_SERVICE_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(SCHILCHER_USER_SERVICE_PLUGIN_FILE, array($this, 'deactivate'));

        // Init hook
        add_action('init', array($this, 'init'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // AJAX handlers
        $this->setup_ajax_handlers();
    }

    /**
     * Setup AJAX handlers
     */
    private function setup_ajax_handlers() {
        // Login handlers
        add_action('wp_ajax_nopriv_schilcher_login', array($this, 'ajax_login_handler'));
        add_action('wp_ajax_schilcher_login', array($this, 'ajax_login_handler'));

        // User info handlers
        add_action('wp_ajax_schilcher_get_user_info', array($this, 'ajax_get_user_info'));
        add_action('wp_ajax_nopriv_schilcher_get_user_info', array($this, 'ajax_get_user_info'));

        // Logout handler
        add_action('wp_ajax_schilcher_logout', array($this, 'ajax_logout_handler'));

        // Registration handlers
        add_action('wp_ajax_nopriv_schilcher_registration', array($this, 'ajax_registration_handler'));
        add_action('wp_ajax_schilcher_registration', array($this, 'ajax_registration_handler'));
        add_action('wp_ajax_nopriv_get_registration_nonce', array($this, 'ajax_get_registration_nonce'));
        add_action('wp_ajax_get_registration_nonce', array($this, 'ajax_get_registration_nonce'));

        // Password reset handlers
        add_action('wp_ajax_nopriv_schilcher_password_reset', array($this, 'ajax_password_reset_handler'));
        add_action('wp_ajax_schilcher_password_reset', array($this, 'ajax_password_reset_handler'));
        add_action('wp_ajax_nopriv_schilcher_reset_complete', array($this, 'ajax_reset_complete_handler'));
        add_action('wp_ajax_schilcher_reset_complete', array($this, 'ajax_reset_complete_handler'));

        // Debug user data handler
        add_action('wp_ajax_debug_user_data', array($this, 'ajax_debug_user_data_handler'));
        add_action('wp_ajax_nopriv_debug_user_data', array($this, 'ajax_debug_user_data_handler'));
    }

    /**
     * Plugin initialization
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('schilcher-user-service', false, dirname(SCHILCHER_USER_SERVICE_PLUGIN_BASENAME) . '/languages');

        // Register shortcodes
        $this->components['shortcodes']->register_shortcodes();

        // Add login redirects
        add_action('template_redirect', array($this, 'redirect_logged_in_users'));

        // Add password reset redirects
        add_action('login_form_lostpassword', array($this, 'redirect_to_custom_reset'));
        add_action('login_form_rp', array($this, 'redirect_to_custom_reset_form'));
        add_action('login_form_resetpass', array($this, 'redirect_to_custom_reset_form'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue main CSS
        wp_enqueue_style(
            'schilcher-user-service',
            SCHILCHER_USER_SERVICE_PLUGIN_URL . 'assets/css/schilcher-user-service.css',
            array(),
            SCHILCHER_USER_SERVICE_VERSION
        );

        // Enqueue main JS
        wp_enqueue_script(
            'schilcher-user-service',
            SCHILCHER_USER_SERVICE_PLUGIN_URL . 'assets/js/schilcher-user-service.js',
            array('jquery'),
            SCHILCHER_USER_SERVICE_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('schilcher-user-service', 'schilcherAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('schilcher_nonce'),
            'login_nonce' => wp_create_nonce('schilcher_login_nonce'),
            'logout_nonce' => wp_create_nonce('schilcher_logout_nonce'),
            'reset_nonce' => wp_create_nonce('schilcher_reset_nonce'),
            'reset_complete_nonce' => wp_create_nonce('schilcher_reset_complete_nonce'),
            'registration_nonce' => wp_create_nonce('schilcher_registration_nonce')
        ));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary database tables or options
        $this->create_database_tables();

        // Set default options
        add_option('schilcher_user_service_version', SCHILCHER_USER_SERVICE_VERSION);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up temporary data
        $this->cleanup_temporary_data();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables if needed
     */
    private function create_database_tables() {
        // For now, we use WordPress user meta, so no custom tables needed
        // This method is here for future expansion
    }

    /**
     * Clean up temporary data
     */
    private function cleanup_temporary_data() {
        // Clean up any temporary options or cache
        wp_cache_flush();
    }

    /**
     * Get plugin component
     *
     * @param string $component
     * @return mixed|null
     */
    public function get_component($component) {
        return isset($this->components[$component]) ? $this->components[$component] : null;
    }

    // =====================================
    // AJAX HANDLERS
    // =====================================
    /**
     * Handle login AJAX requests
     */
    public function ajax_login_handler() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'schilcher_login_nonce')) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Sicherheitsfehler. Bitte laden Sie die Seite neu und versuchen Sie es erneut.'
            )));
        }

        // Get and sanitize form data
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'yes';

        // Validate required fields
        if (empty($username) || empty($password)) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Bitte füllen Sie alle Pflichtfelder aus.'
            )));
        }

        // Prepare login credentials
        $credentials = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember
        );

        // Attempt to sign in the user
        $user = wp_signon($credentials, is_ssl());

        // Check for login errors
        if (is_wp_error($user)) {
            $error_message = $this->get_friendly_error_message($user->get_error_code());
            wp_die(json_encode(array(
                'success' => false,
                'message' => $error_message
            )));
        }

        // Login successful
        $redirect_url = $this->get_redirect_url($user);

        wp_die(json_encode(array(
            'success' => true,
            'message' => 'Erfolgreich angemeldet. Sie werden weitergeleitet...',
            'redirect_url' => $redirect_url
        )));
    }

    /**
     * Handle get user info AJAX requests
     */
    public function ajax_get_user_info() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(json_encode(array(
                'logged_in' => false,
                'username' => '',
                'display_name' => '',
                'first_letter' => ''
            )));
        }

        $current_user = wp_get_current_user();

        // Get display name (priority: display_name, first_name last_name, user_login)
        $display_name = '';
        if (!empty($current_user->display_name)) {
            $display_name = $current_user->display_name;
        } elseif (!empty($current_user->first_name) || !empty($current_user->last_name)) {
            $display_name = trim($current_user->first_name . ' ' . $current_user->last_name);
        } else {
            $display_name = $current_user->user_login;
        }

        // Get first letter for icon
        $first_letter = !empty($display_name) ? strtoupper(substr($display_name, 0, 1)) : 'U';

        wp_die(json_encode(array(
            'logged_in' => true,
            'username' => $current_user->user_login,
            'display_name' => $display_name,
            'first_letter' => $first_letter,
            'email' => $current_user->user_email
        )));
    }

    /**
     * Handle logout AJAX requests
     */
    public function ajax_logout_handler() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'schilcher_logout_nonce')) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Sicherheitsfehler.'
            )));
        }

        // Log out the user
        wp_logout();

        // Clear any additional session data
        if (session_id()) {
            session_destroy();
        }

        wp_die(json_encode(array(
            'success' => true,
            'redirect_url' => 'https://www.schilcher-kaese.de'
        )));
    }

    /**
     * Handle registration AJAX requests
     */
    public function ajax_registration_handler() {
        // Verify nonce
        $nonce = $_POST['registration_nonce'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'schilcher_registration_nonce')) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Sicherheitsfehler. Bitte laden Sie die Seite neu und versuchen Sie es erneut.'
            )));
        }

        // Validate form data
        $validator = $this->get_component('validator');
        $validation_result = $validator->validate_registration_data($_POST);

        if (!$validation_result['success']) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => $validation_result['message']
            )));
        }

        $form_data = $validation_result['data'];

        // Check if user already exists
        if ($validator->user_exists($form_data['email_general'], $form_data['company_name'])) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Ein Benutzer mit dieser E-Mail-Adresse oder diesem Firmennamen existiert bereits.'
            )));
        }

        // Create user
        $user_manager = $this->get_component('user_manager');
        $user_result = $user_manager->create_b2b_user($form_data);

        if (!$user_result['success']) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => $user_result['message']
            )));
        }

        // Send emails
        $email_manager = $this->get_component('email_manager');
        $email_manager->send_registration_email($user_result['user_id'], $form_data);
        $email_manager->send_admin_notification($user_result['user_id'], $form_data, $user_result['username']);

        wp_die(json_encode(array(
            'success' => true,
            'message' => 'Registrierung erfolgreich eingereicht! Sie erhalten eine Bestätigung per E-Mail.'
        )));
    }

    /**
     * Handle get registration nonce AJAX requests
     */
    public function ajax_get_registration_nonce() {
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'nonce' => wp_create_nonce('schilcher_registration_nonce')
            )
        )));
    }

    /**
     * Handle password reset AJAX requests
     */
    public function ajax_password_reset_handler() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'schilcher_reset_nonce')) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Sicherheitsfehler. Bitte laden Sie die Seite neu und versuchen Sie es erneut.'
            )));
        }

        // Get and sanitize email
        $user_email = sanitize_email($_POST['user_email']);

        // Validate email
        if (empty($user_email) || !is_email($user_email)) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.'
            )));
        }

        // Check if user exists
        $user = get_user_by('email', $user_email);
        if (!$user) {
            // For security, don't reveal if email exists or not
            wp_die(json_encode(array(
                'success' => true,
                'message' => 'Falls ein Konto mit dieser E-Mail-Adresse existiert, wurde ein Passwort-Reset-Link gesendet.'
            )));
        }

        // Generate reset key
        $reset_key = get_password_reset_key($user);
        if (is_wp_error($reset_key)) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Fehler beim Generieren des Reset-Links. Bitte versuchen Sie es später erneut.'
            )));
        }

        // Send reset email
        $sent = $this->send_password_reset_email($user, $reset_key);

        if ($sent) {
            wp_die(json_encode(array(
                'success' => true,
                'message' => 'Ein Passwort-Reset-Link wurde an Ihre E-Mail-Adresse gesendet. Bitte überprüfen Sie Ihren Posteingang und Spam-Ordner.'
            )));
        } else {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Fehler beim Senden der E-Mail. Bitte versuchen Sie es später erneut.'
            )));
        }
    }

    /**
     * Handle password reset completion AJAX requests
     */
    public function ajax_reset_complete_handler() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'schilcher_reset_complete_nonce')) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Sicherheitsfehler. Bitte laden Sie die Seite neu und versuchen Sie es erneut.'
            )));
        }

        // Get form data
        $reset_key = sanitize_text_field($_POST['reset_key']);
        $user_login = sanitize_text_field($_POST['user_login']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate inputs
        if (empty($reset_key) || empty($user_login) || empty($new_password) || empty($confirm_password)) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Bitte füllen Sie alle Felder aus.'
            )));
        }

        // Check if passwords match
        if ($new_password !== $confirm_password) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Die Passwörter stimmen nicht überein.'
            )));
        }

        // Validate password strength
        $password_check = $this->validate_password_strength($new_password);
        if (!$password_check['valid']) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => $password_check['message']
            )));
        }

        // Check if the reset key is valid
        $user = check_password_reset_key($reset_key, $user_login);
        if (is_wp_error($user)) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Der Reset-Link ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.'
            )));
        }

        // Reset the password
        reset_password($user, $new_password);

        // Success
        wp_die(json_encode(array(
            'success' => true,
            'message' => 'Ihr Passwort wurde erfolgreich geändert.',
            'redirect_url' => '/intern?password_reset_success=1'
        )));
    }

    /**
     * Handle debug user data AJAX requests
     */
    public function ajax_debug_user_data_handler() {
        // Security: Basic protection against unauthorized access
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Only allow in debug mode or with admin token
            $admin_token = $_GET['token'] ?? '';
            if ($admin_token !== 'schilcher_debug_2025') {
                wp_die(json_encode(['error' => 'Unauthorized access. Debug mode required or valid token needed.']), 403);
            }
        }

        // Check user permissions (additional security layer)
        if (!current_user_can('manage_options') && (!defined('WP_DEBUG') || !WP_DEBUG)) {
            wp_die(json_encode(['error' => 'Administrator privileges required.']), 403);
        }

        // Get user ID from URL parameter
        $user_id = $_GET['user_id'] ?? null;

        if (!$user_id) {
            wp_die(json_encode(['error' => 'user_id parameter is required. Usage: admin-ajax.php?action=debug_user_data&user_id=123']), 400);
        }

        // Validate user ID
        $user_id = intval($user_id);
        if ($user_id <= 0) {
            wp_die(json_encode(['error' => 'Invalid user_id. Must be a positive integer.']), 400);
        }

        // Get user data
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            wp_die(json_encode(['error' => 'User not found with ID: ' . $user_id]), 404);
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
        wp_die(json_encode($debug_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    // =====================================
    // HELPER METHODS
    // =====================================

    /**
     * Convert WordPress error codes to user-friendly German messages
     */
    private function get_friendly_error_message($error_code) {
        $error_messages = array(
            'invalid_username'      => 'Unbekannter Benutzername. Überprüfen Sie Ihren Benutzernamen oder Ihre E-Mail-Adresse.',
            'invalid_email'         => 'Unbekannte E-Mail-Adresse. Überprüfen Sie Ihre E-Mail-Adresse.',
            'incorrect_password'    => 'Das eingegebene Passwort ist für diesen Benutzernamen falsch.',
            'empty_username'        => 'Bitte geben Sie einen Benutzername oder eine E-Mail-Adresse ein.',
            'empty_password'        => 'Bitte geben Sie ein Passwort ein.',
            'authentication_failed' => 'Ungültige Anmeldedaten. Bitte überprüfen Sie Ihre Angaben.',
            'too_many_retries'      => 'Zu viele fehlgeschlagene Anmeldeversuche. Bitte versuchen Sie es später erneut.'
        );

        return isset($error_messages[$error_code])
            ? $error_messages[$error_code]
            : 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
    }

    /**
     * Get appropriate redirect URL based on user role and context
     */
    private function get_redirect_url($user) {
        // Check if there's a requested redirect URL
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '';

        // Validate and sanitize redirect URL
        if (!empty($redirect_to)) {
            $redirect_to = wp_validate_redirect($redirect_to, '');
            if (!empty($redirect_to)) {
                return $redirect_to;
            }
        }

        // B2BKing specific redirects
        if (function_exists('b2bking_is_b2b_user') && b2bking_is_b2b_user($user->ID)) {
            // B2B user - redirect to B2B dashboard or account page
            if (class_exists('WooCommerce')) {
                return wc_get_account_endpoint_url('dashboard');
            }
        }

        // Default redirects based on user role
        if (user_can($user, 'manage_options')) {
            // Administrator - redirect to admin dashboard
            return admin_url();
        } elseif (user_can($user, 'edit_posts')) {
            // Editor/Author - redirect to admin dashboard
            return admin_url();
        } elseif (class_exists('WooCommerce')) {
            // Regular user with WooCommerce - redirect to account page
            return wc_get_account_endpoint_url('dashboard');
        } else {
            // Default - redirect to home page
            return home_url();
        }
    }

    /**
     * Send custom branded password reset email
     */
    private function send_password_reset_email($user, $reset_key) {
        $user_login = $user->user_login;
        $user_email = $user->user_email;
        $user_display_name = $user->display_name;

        // Create reset URL that redirects to our custom page
        $reset_url = home_url('/new-password?key=' . urlencode($reset_key) . '&login=' . urlencode($user_login));

        // Email subject
        $subject = 'Passwort zurücksetzen - Schilcher Käse Händlerbereich';

        // Email content
        $message = "
Hallo {$user_display_name},

Sie haben eine Passwort-Zurücksetzung für Ihren Schilcher Käse Händlerbereich angefordert.

Um Ihr Passwort zurückzusetzen, klicken Sie bitte auf den folgenden Link:
{$reset_url}

Dieser Link ist 24 Stunden gültig.

Falls Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren.

Mit freundlichen Grüßen
Ihr Schilcher Käse Team

---
Schilcher - Kompetenz in Biokäse
E-Mail: vertrieb@schilcher-kaese.de
Web: https://www.schilcher-kaese.de
";

        // Email headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: Schilcher Käse <noreply@schilcher-kaese.de>'
        );

        // Send email
        return wp_mail($user_email, $subject, $message, $headers);
    }

    /**
     * Validate password strength
     */
    private function validate_password_strength($password) {
        $errors = array();

        // Check length
        if (strlen($password) < 6) {
            $errors[] = 'Das Passwort muss mindestens 6 Zeichen lang sein.';
        }

        // Check for uppercase
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Das Passwort muss mindestens einen Großbuchstaben enthalten.';
        }

        // Check for lowercase
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Das Passwort muss mindestens einen Kleinbuchstaben enthalten.';
        }

        // Check for number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Das Passwort muss mindestens eine Zahl enthalten.';
        }

        // Check for special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Das Passwort muss mindestens ein Sonderzeichen enthalten.';
        }

        if (empty($errors)) {
            return array('valid' => true, 'message' => '');
        } else {
            return array('valid' => false, 'message' => implode(' ', $errors));
        }
    }

    /**
     * Redirect logged-in users away from login page
     */
    public function redirect_logged_in_users() {
        // Only apply to the login page
        if (is_page('login') || is_page('anmelden') || is_page('intern')) {
            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();
                $redirect_url = $this->get_redirect_url($current_user);
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
    }

    /**
     * Redirect default WordPress lost password page to custom page
     */
    public function redirect_to_custom_reset() {
        // Only redirect if not already on custom page
        if (!isset($_GET['custom_reset'])) {
            wp_redirect('/reset-password');
            exit;
        }
    }

    /**
     * Redirect password reset links to custom page
     */
    public function redirect_to_custom_reset_form() {
        // Get the reset key and login from URL
        $key = isset($_GET['key']) ? $_GET['key'] : '';
        $login = isset($_GET['login']) ? $_GET['login'] : '';

        if (!empty($key) && !empty($login)) {
            // Redirect to custom reset complete page with parameters
            wp_redirect('/new-password?key=' . urlencode($key) . '&login=' . urlencode($login));
            exit;
        }
    }
}

// Initialize the plugin
function schilcher_user_service() {
    return Schilcher_User_Service::instance();
}

// Start the plugin
schilcher_user_service();
