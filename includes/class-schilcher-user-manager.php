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

        // Set B2B metadata - Always create B2BKing fields for compatibility
        $b2b_role_id = 13860; // B2BKing role ID

        $b2b_metadata = array(
            'b2bking_b2buser' => 'yes',
            'b2bking_customergroup' => $b2b_role_id,
            'b2bking_account_approved' => 'no',
            'b2bking_registration_data_time' => current_time('timestamp'),
            'b2bking_user_registration_date_time' => current_time('mysql'),
            'b2bking_registration_data_saved' => 'yes',
            'b2bking_registration_role' => 'role_' . $b2b_role_id,
            'b2bking_custom_fields_string' => '13861,13862,13863,13864,13865,13869,13866,13867,13868,'
        );

        // Add B2BKing custom fields mapping
        $b2bking_custom_fields = $this->map_to_b2bking_fields($form_data);
        $b2b_metadata = array_merge($b2b_metadata, $b2bking_custom_fields);

        // Set user role and fallback pending status
        $user = new WP_User($user_id);
        $user->set_role('customer');
        update_user_meta($user_id, 'schilcher_b2b_pending', 'yes');

        // Create B2BKing metadata
        $this->batch_update_user_meta($user_id, $b2b_metadata);

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

    /**
     * Map Schilcher form data to B2BKing custom fields
     */
    private function map_to_b2bking_fields($form_data) {
        $b2bking_fields = array();

        // Parse management field for first and last name
        $management = $form_data['management'] ?? '';
        $name_parts = $this->parse_management_name($management);
        
        // B2BKing custom field mapping based on analysis of existing user data
        $b2bking_fields['b2bking_custom_field_13861'] = $name_parts['first_name']; // First name
        $b2bking_fields['b2bking_custom_field_13862'] = $name_parts['last_name'];  // Last name
        $b2bking_fields['b2bking_custom_field_13863'] = $form_data['company_name'] ?? '';  // Company name
        
        // Parse address field for street, city, postcode
        $address_parts = $this->parse_address($form_data['address'] ?? '');
        $b2bking_fields['b2bking_custom_field_13864'] = $address_parts['street'];     // Address line 1
        $b2bking_fields['b2bking_custom_field_13865'] = '';                          // Address line 2 (optional)
        $b2bking_fields['b2bking_custom_field_13866'] = $address_parts['city'];      // City
        $b2bking_fields['b2bking_custom_field_13867'] = $address_parts['postcode'];  // Postcode
        $b2bking_fields['b2bking_custom_field_13868'] = $form_data['phone'] ?? '';   // Phone
        $b2bking_fields['b2bking_custom_field_13869'] = 'DE';                        // Country (default to Germany)

        return $b2bking_fields;
    }

    /**
     * Parse management field to extract first and last name
     */
    private function parse_management_name($management) {
        $management = trim($management);
        if (empty($management)) {
            return array('first_name' => '', 'last_name' => '');
        }

        // Split by space and assume first word is first name, rest is last name
        $parts = explode(' ', $management);
        $first_name = array_shift($parts);
        $last_name = implode(' ', $parts);

        return array(
            'first_name' => $first_name,
            'last_name' => $last_name
        );
    }

    /**
     * Parse address field to extract street, city, and postcode
     */
    private function parse_address($address) {
        $address = trim($address);
        if (empty($address)) {
            return array('street' => '', 'city' => '', 'postcode' => '');
        }

        // Default values
        $street = '';
        $city = '';
        $postcode = '';

        // Try to parse format: "Street, Postcode City, Country"
        // Remove country if present (last part after comma)
        $parts = explode(',', $address);
        if (count($parts) >= 3) {
            // Remove last part (country)
            array_pop($parts);
            $address = implode(',', $parts);
        }

        // Split by comma
        $parts = array_map('trim', explode(',', $address));
        
        if (count($parts) >= 2) {
            $street = $parts[0];
            
            // Second part should contain postcode and city
            $postcode_city = $parts[1];
            
            // Extract postcode (first continuous digits) and city (rest)
            if (preg_match('/^(\d+)\s+(.+)$/', $postcode_city, $matches)) {
                $postcode = $matches[1];
                $city = $matches[2];
            } else {
                // Fallback: use whole second part as city
                $city = $postcode_city;
            }
        } else {
            // Single part, try to extract postcode and city
            if (preg_match('/^(.+?)\s+(\d+)\s+(.+)$/', $address, $matches)) {
                $street = $matches[1];
                $postcode = $matches[2];
                $city = $matches[3];
            } else {
                // Fallback: use whole address as street
                $street = $address;
            }
        }

        return array(
            'street' => $street,
            'city' => $city,
            'postcode' => $postcode
        );
    }
}
