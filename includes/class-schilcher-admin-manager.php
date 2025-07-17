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
}
