<?php
/**
 * Schilcher User Manager
 * Handles user creation, management, and B2B functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Schilcher_User_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function create_b2b_user($form_data) {
        $username = $this->generate_username($form_data['company_name']);
        $password = wp_generate_password(12, false);

        $user_data = array(
            'user_login'    => $username,
            'user_email'    => $form_data['email_general'],
            'user_pass'     => $password,
            'display_name'  => $form_data['company_name'],
            'first_name'    => '',
            'last_name'     => $form_data['company_name'],
            'role'          => 'customer'
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            return array(
                'success' => false,
                'message' => 'Fehler beim Erstellen des Benutzerkontos: ' . $user_id->get_error_message()
            );
        }

        // Set B2B metadata
        if (function_exists('b2bking_get_role_id')) {
            $b2b_role_id = 13860; // B2BKing role ID

            $b2b_metadata = array(
                'b2bking_b2buser' => 'yes',
                'b2bking_customergroup' => $b2b_role_id,
                'b2bking_account_approved' => 'no',
                'b2bking_registration_data_time' => current_time('timestamp'),
                'b2bking_user_registration_date_time' => current_time('mysql')
            );

            $this->batch_update_user_meta($user_id, $b2b_metadata);
        } else {
            $user = new WP_User($user_id);
            $user->set_role('customer');
            update_user_meta($user_id, 'schilcher_b2b_pending', 'yes');
        }

        // Store form data as user metadata
        $this->store_b2b_metadata($user_id, $form_data);

        return array(
            'success' => true,
            'user_id' => $user_id,
            'username' => $username,
            'password' => $password
        );
    }

    private function generate_username($company_name) {
        $base_username = sanitize_user(strtolower($company_name), true);
        $base_username = str_replace(' ', '', $base_username);
        $base_username = substr($base_username, 0, 20);

        $username = $base_username;
        $counter = 1;

        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }

        return $username;
    }

    public function store_b2b_metadata($user_id, $form_data) {
        $prefix = 'schilcher_b2b_';

        $metadata = array(
            $prefix . 'registration_date' => current_time('mysql'),
            $prefix . 'approval_status' => 'pending'
        );

        foreach ($form_data as $key => $value) {
            if (is_array($value)) {
                $metadata[$prefix . $key] = implode(',', $value);
            } else {
                $metadata[$prefix . $key] = $value;
            }
        }

        $this->batch_update_user_meta($user_id, $metadata);
    }

    private function batch_update_user_meta($user_id, $metadata) {
        global $wpdb;

        if (empty($metadata)) {
            return;
        }

        $values = array();
        $placeholders = array();

        foreach ($metadata as $meta_key => $meta_value) {
            $values[] = $user_id;
            $values[] = $meta_key;
            $values[] = maybe_serialize($meta_value);
            $placeholders[] = '(%d, %s, %s)';
        }

        $query = "INSERT INTO {$wpdb->usermeta} (user_id, meta_key, meta_value) VALUES " . implode(', ', $placeholders) . "
                  ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)";

        $wpdb->query($wpdb->prepare($query, $values));
    }

    public function update_approval_status($user_id, $status) {
        $metadata = array(
            'schilcher_b2b_approval_status' => $status
        );

        if ($status === 'approved') {
            $metadata['b2bking_account_approved'] = 'yes';
        }

        $this->batch_update_user_meta($user_id, $metadata);
    }

    public function is_b2b_user($user_id) {
        return get_user_meta($user_id, 'b2bking_b2buser', true) === 'yes';
    }

    public function get_approval_status($user_id) {
        return get_user_meta($user_id, 'schilcher_b2b_approval_status', true);
    }

    public function get_b2b_users() {
        return get_users(array(
            'meta_key' => 'b2bking_b2buser',
            'meta_value' => 'yes'
        ));
    }

    public function export_b2b_users() {
        $users = $this->get_b2b_users();

        $export_data = array();
        $export_data[] = array(
            'ID', 'Benutzername', 'E-Mail', 'Firmenname', 'Status', 'Registrierungsdatum',
            'Telefon', 'Adresse', 'Rechtsform', 'Website', 'Umsatzsteuer-ID'
        );

        foreach ($users as $user) {
            $export_data[] = array(
                $user->ID,
                $user->user_login,
                $user->user_email,
                get_user_meta($user->ID, 'schilcher_b2b_company_name', true),
                get_user_meta($user->ID, 'schilcher_b2b_approval_status', true),
                get_user_meta($user->ID, 'schilcher_b2b_registration_date', true),
                get_user_meta($user->ID, 'schilcher_b2b_phone', true),
                get_user_meta($user->ID, 'schilcher_b2b_address', true),
                get_user_meta($user->ID, 'schilcher_b2b_legal_form', true),
                get_user_meta($user->ID, 'schilcher_b2b_website', true),
                get_user_meta($user->ID, 'schilcher_b2b_vat_id', true)
            );
        }

        return $export_data;
    }
}
