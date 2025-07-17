<?php
/**
 * Schilcher Registration Validator
 * Handles form validation for registration and other user inputs
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Schilcher_Registration_Validator {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function validate_registration_data($post_data) {
        $errors = array();
        $data = array();

        $required_fields = array(
            'company_registration' => 'Firmierung lt. Handelsregister',
            'company_name' => 'Firmenname',
            'management' => 'Geschäftsführung',
            'address' => 'Adresse',
            'phone' => 'Telefonnummer',
            'delivery_address' => 'Lieferadresse',
            'billing_address' => 'Rechnungsadresse',
            'cheese_counter_contact' => 'Ansprechpartner/in Käsetheke',
            'cheese_counter_phone' => 'Telefon-Nr. Käsetheke',
            'vat_id' => 'Umsatzsteuer-Identifikations-Nr.'
        );

        foreach ($required_fields as $field => $label) {
            if (empty($post_data[$field])) {
                $errors[] = $label . ' ist ein Pflichtfeld.';
            } else {
                $data[$field] = sanitize_text_field($post_data[$field]);
            }
        }

        $required_email_fields = array('email_general', 'email_orders', 'email_weekly', 'email_invoices');
        $optional_email_fields = array();
        $all_email_fields = array_merge($required_email_fields, $optional_email_fields);

        foreach ($all_email_fields as $field) {
            $field_labels = array(
                'email_general' => 'E-Mail-Adresse Allgemein',
                'email_orders' => 'E-Mail-Adresse Bestelllisten',
                'email_weekly' => 'E-Mail-Adresse Wocheninfo',
                'email_invoices' => 'E-Mail-Adresse Rechnungen'
            );
            $field_label = $field_labels[$field] ?? $field;

            if (in_array($field, $required_email_fields)) {
                if (empty($post_data[$field])) {
                    $errors[] = $field_label . ' ist ein Pflichtfeld.';
                } elseif (!is_email($post_data[$field])) {
                    $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse für ' . $field_label . ' ein.';
                } else {
                    $data[$field] = sanitize_email($post_data[$field]);
                }
            } else {
                if (!empty($post_data[$field])) {
                    if (!is_email($post_data[$field])) {
                        $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse für ' . $field_label . ' ein.';
                    } else {
                        $data[$field] = sanitize_email($post_data[$field]);
                    }
                }
            }
        }

        $required_radio_fields = array(
            'legal_form' => 'Rechtsform',
            'shipping' => 'Spedition',
            'ifco_delivery' => 'IFCO-Kisten Lieferung',
            'sepa_mandate' => 'SEPA-Lastschriftmandat'
        );

        foreach ($required_radio_fields as $field => $label) {
            if (empty($post_data[$field])) {
                $errors[] = $label . ' ist ein Pflichtfeld.';
            } else {
                $data[$field] = sanitize_text_field($post_data[$field]);
            }
        }

        $optional_text_fields = array(
            'fax', 'website', 'legal_form_other'
        );

        foreach ($optional_text_fields as $field) {
            if (!empty($post_data[$field])) {
                $data[$field] = sanitize_text_field($post_data[$field]);
            }
        }

        if (empty($data['legal_form'])) {
            $errors[] = 'Bitte wählen Sie eine Rechtsform aus.';
        } else {
            if ($data['legal_form'] === 'sonstige' && empty($data['legal_form_other'])) {
                $errors[] = 'Bitte geben Sie die andere Rechtsform an.';
            }
        }

        $checkbox_fields = array('trade_register_copy', 'business_registration_copy');
        foreach ($checkbox_fields as $field) {
            $data[$field] = !empty($post_data[$field]) ? '1' : '0';
        }

        if (!empty($errors)) {
            return array(
                'success' => false,
                'message' => implode('<br>', $errors)
            );
        }

        return array(
            'success' => true,
            'data' => $data
        );
    }

    public function verify_nonce($nonce) {
        return wp_verify_nonce($nonce, 'schilcher_registration_nonce');
    }

    public function get_nonce_from_post($post_data) {
        return $post_data['nonce'] ?? $post_data['registration_nonce'] ?? '';
    }

    public function user_exists($email, $company_name) {
        return email_exists($email) || username_exists($company_name);
    }
}
