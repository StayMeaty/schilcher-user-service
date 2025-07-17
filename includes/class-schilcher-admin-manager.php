<?php
/**
 * Schilcher Admin Manager
 * Handles admin interface customizations and B2B user management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Schilcher_Admin_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action('admin_init', array($this, 'add_user_approval_actions'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('user_row_actions', array($this, 'user_row_actions'), 10, 2);
        add_action('admin_action_approve_b2b_user', array($this, 'approve_b2b_user'));
        add_action('admin_action_reject_b2b_user', array($this, 'reject_b2b_user'));
        add_action('admin_action_export_b2b_users', array($this, 'export_b2b_users'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_filter('manage_users_columns', array($this, 'user_columns'));
        add_action('manage_users_custom_column', array($this, 'user_custom_column'), 10, 3);
        add_action('admin_footer-users.php', array($this, 'add_export_link'));
        add_filter('wp_authenticate_user', array($this, 'check_b2b_approval'), 10, 2);
    }

    public function add_user_approval_actions() {
        // Actions are registered in init method
    }

    public function user_row_actions($actions, $user_object) {
        $user_manager = Schilcher_User_Manager::get_instance();
        $approval_status = $user_manager->get_approval_status($user_object->ID);
        $is_b2b_user = $user_manager->is_b2b_user($user_object->ID);

        if ($is_b2b_user && $approval_status === 'pending') {
            $actions['approve_b2b'] = '<a href="' . wp_nonce_url("admin.php?action=approve_b2b_user&user_id=" . $user_object->ID, 'approve_b2b_user') . '">B2B Freischalten</a>';
            $actions['reject_b2b'] = '<a href="' . wp_nonce_url("admin.php?action=reject_b2b_user&user_id=" . $user_object->ID, 'reject_b2b_user') . '">B2B Ablehnen</a>';
        }

        return $actions;
    }

    public function approve_b2b_user() {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'approve_b2b_user')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $user_id = (int) $_GET['user_id'];

        if (!$user_id) {
            wp_die('Invalid user ID');
        }

        $user_manager = Schilcher_User_Manager::get_instance();
        $user_manager->update_approval_status($user_id, 'approved');

        $email_manager = Schilcher_Email_Manager::get_instance();
        $email_manager->send_approval_email($user_id);

        wp_redirect(admin_url('users.php?approved=1'));
        exit;
    }

    public function reject_b2b_user() {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'reject_b2b_user')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $user_id = (int) $_GET['user_id'];

        if (!$user_id) {
            wp_die('Invalid user ID');
        }

        $user_manager = Schilcher_User_Manager::get_instance();
        $user_manager->update_approval_status($user_id, 'rejected');

        $email_manager = Schilcher_Email_Manager::get_instance();
        $email_manager->send_rejection_email($user_id);

        wp_redirect(admin_url('users.php?rejected=1'));
        exit;
    }

    public function export_b2b_users() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $user_manager = Schilcher_User_Manager::get_instance();
        $export_data = $user_manager->export_b2b_users();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="b2b_users_export.csv"');

        $output = fopen('php://output', 'w');

        foreach ($export_data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public function admin_notices() {
        if (isset($_GET['approved']) && $_GET['approved'] == '1') {
            echo '<div class="notice notice-success"><p>B2B-Benutzer wurde erfolgreich freigeschaltet.</p></div>';
        }

        if (isset($_GET['rejected']) && $_GET['rejected'] == '1') {
            echo '<div class="notice notice-warning"><p>B2B-Benutzer wurde abgelehnt.</p></div>';
        }
    }

    public function user_columns($columns) {
        $columns['b2b_status'] = 'B2B Status';
        $columns['company_name'] = 'Firmenname';
        return $columns;
    }

    public function user_custom_column($value, $column_name, $user_id) {
        $user_manager = Schilcher_User_Manager::get_instance();

        if ($column_name === 'b2b_status') {
            $is_b2b = $user_manager->is_b2b_user($user_id);
            $approval_status = $user_manager->get_approval_status($user_id);

            if ($is_b2b) {
                switch ($approval_status) {
                    case 'pending':
                        return '<span style="color: orange;">⏳ Wartend</span>';
                    case 'approved':
                        return '<span style="color: green;">✓ Freigegeben</span>';
                    case 'rejected':
                        return '<span style="color: red;">✗ Abgelehnt</span>';
                    default:
                        return '<span style="color: gray;">B2B Benutzer</span>';
                }
            }
            return '-';
        }

        if ($column_name === 'company_name') {
            $company_name = get_user_meta($user_id, 'schilcher_b2b_company_name', true);
            return $company_name ? $company_name : '-';
        }

        return $value;
    }

    public function add_export_link() {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const bulkActions = document.querySelector(".tablenav.top .bulkactions");
                if (bulkActions) {
                    const exportLink = document.createElement("a");
                    exportLink.href = "admin.php?action=export_b2b_users";
                    exportLink.textContent = "B2B Benutzer exportieren";
                    exportLink.className = "button";
                    exportLink.style.marginLeft = "10px";
                    bulkActions.appendChild(exportLink);
                }
            });
        </script>';
    }

    public function check_b2b_approval($user, $password) {
        if (is_wp_error($user)) {
            return $user;
        }

        $user_manager = Schilcher_User_Manager::get_instance();
        $is_b2b = $user_manager->is_b2b_user($user->ID);
        $approval_status = $user_manager->get_approval_status($user->ID);

        if ($is_b2b && $approval_status === 'pending') {
            return new WP_Error('account_pending', 'Ihr Konto wartet noch auf Freischaltung. Sie erhalten eine E-Mail, sobald Ihr Konto freigeschaltet wurde.');
        }

        if ($is_b2b && $approval_status === 'rejected') {
            return new WP_Error('account_rejected', 'Ihr Konto wurde nicht freigegeben. Bei Fragen wenden Sie sich bitte an uns.');
        }

        return $user;
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            'Schilcher User Service', // Page title
            'Schilcher B2B', // Menu title
            'manage_options', // Capability
            'schilcher-user-service', // Menu slug
            array($this, 'admin_page') // Callback function
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('schilcher_user_service_settings', 'schilcher_notification_email_mode');
        register_setting('schilcher_user_service_settings', 'schilcher_notification_custom_emails');

        add_settings_section(
            'schilcher_email_notifications',
            'E-Mail Benachrichtigungen',
            array($this, 'email_notifications_section_callback'),
            'schilcher_user_service_settings'
        );

        add_settings_field(
            'notification_email_mode',
            'Benachrichtigungs-Modus',
            array($this, 'notification_email_mode_callback'),
            'schilcher_user_service_settings',
            'schilcher_email_notifications'
        );

        add_settings_field(
            'notification_custom_emails',
            'Benutzerdefinierte E-Mail-Adressen',
            array($this, 'notification_custom_emails_callback'),
            'schilcher_user_service_settings',
            'schilcher_email_notifications'
        );
    }

    /**
     * Admin page callback
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Schilcher User Service Einstellungen</h1>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('schilcher_user_service_settings');
                do_settings_sections('schilcher_user_service_settings');
                submit_button();
                ?>
            </form>

            <div class="card" style="margin-top: 20px;">
                <h2>B2B Benutzerverwaltung</h2>
                <p>Hier können Sie die E-Mail-Benachrichtigungen für neue B2B-Registrierungen konfigurieren.</p>
                
                <h3>Aktuelle Einstellungen:</h3>
                <ul>
                    <li><strong>WordPress Admin E-Mail:</strong> <?php echo esc_html(get_option('admin_email')); ?></li>
                    <li><strong>Benachrichtigungs-Modus:</strong> 
                        <?php 
                        $mode = get_option('schilcher_notification_email_mode', 'wordpress_admin');
                        echo $mode === 'custom' ? 'Benutzerdefinierte E-Mail-Adressen' : 'WordPress Admin E-Mail';
                        ?>
                    </li>
                    <?php if ($mode === 'custom'): ?>
                    <li><strong>Benutzerdefinierte E-Mails:</strong> <?php echo esc_html(get_option('schilcher_notification_custom_emails', '')); ?></li>
                    <?php endif; ?>
                </ul>

                <h3>Schnellaktionen:</h3>
                <p>
                    <a href="<?php echo admin_url('users.php'); ?>" class="button">Benutzer verwalten</a>
                    <a href="<?php echo admin_url('admin.php?action=export_b2b_users'); ?>" class="button">B2B Benutzer exportieren</a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Email notifications section callback
     */
    public function email_notifications_section_callback() {
        echo '<p>Konfigurieren Sie, welche E-Mail-Adressen bei neuen B2B-Registrierungen benachrichtigt werden sollen.</p>';
    }

    /**
     * Notification email mode callback
     */
    public function notification_email_mode_callback() {
        $mode = get_option('schilcher_notification_email_mode', 'wordpress_admin');
        ?>
        <fieldset>
            <label>
                <input type="radio" name="schilcher_notification_email_mode" value="wordpress_admin" <?php checked($mode, 'wordpress_admin'); ?>>
                WordPress Admin E-Mail verwenden (<?php echo esc_html(get_option('admin_email')); ?>)
            </label><br>
            <label>
                <input type="radio" name="schilcher_notification_email_mode" value="custom" <?php checked($mode, 'custom'); ?>>
                Benutzerdefinierte E-Mail-Adressen verwenden
            </label>
        </fieldset>
        <p class="description">Wählen Sie aus, welche E-Mail-Adressen bei neuen B2B-Registrierungen benachrichtigt werden sollen.</p>
        <?php
    }

    /**
     * Notification custom emails callback
     */
    public function notification_custom_emails_callback() {
        $emails = get_option('schilcher_notification_custom_emails', '');
        ?>
        <textarea name="schilcher_notification_custom_emails" rows="3" cols="50" class="large-text"><?php echo esc_textarea($emails); ?></textarea>
        <p class="description">
            Geben Sie eine oder mehrere E-Mail-Adressen ein, getrennt durch Kommas.<br>
            Beispiel: admin@schilcher-kaese.de, vertrieb@schilcher-kaese.de, manager@schilcher-kaese.de
        </p>
        <?php
    }

    /**
     * Get notification email addresses based on settings
     */
    public function get_notification_emails() {
        $mode = get_option('schilcher_notification_email_mode', 'wordpress_admin');
        
        if ($mode === 'custom') {
            $custom_emails = get_option('schilcher_notification_custom_emails', '');
            if (!empty($custom_emails)) {
                // Split by comma and clean up each email
                $emails = array_map('trim', explode(',', $custom_emails));
                // Filter out empty emails and validate
                $emails = array_filter($emails, function($email) {
                    return !empty($email) && is_email($email);
                });
                
                if (!empty($emails)) {
                    return $emails;
                }
            }
        }
        
        // Fallback to WordPress admin email
        return array(get_option('admin_email'));
    }
}
