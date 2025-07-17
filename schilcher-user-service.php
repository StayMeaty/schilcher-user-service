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
