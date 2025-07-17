<?php
/**
 * Schilcher Email Manager
 * Handles all email functionality for the user service plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Schilcher_Email_Manager {

    private static $instance = null;
    private $template_cache = array();
    private $template_path;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->template_path = SCHILCHER_USER_SERVICE_PLUGIN_DIR . 'templates/email-template.html';
    }

    public function load_email_template($template_data) {
        $cache_key = 'schilcher_email_template';
        if (isset($this->template_cache[$cache_key])) {
            $template = $this->template_cache[$cache_key];
        } else {
            if (file_exists($this->template_path)) {
                $template = file_get_contents($this->template_path);
                $this->template_cache[$cache_key] = $template;
            } else {
                $template = $this->get_fallback_email_template();
                $this->template_cache[$cache_key] = $template;
            }
        }

        $defaults = array(
            'EMAIL_SUBJECT' => 'Schilcher Käse',
            'EMAIL_PREHEADER' => '',
            'EMAIL_TITLE' => 'Nachricht von Schilcher Käse',
            'GREETING' => 'Sehr geehrte Damen und Herren,',
            'MAIN_CONTENT' => '',
            'INFO_BOX' => '',
            'BUTTON' => '',
            'CLOSING_TEXT' => 'Bei Fragen stehen wir Ihnen gerne zur Verfügung.'
        );

        $data = array_merge($defaults, $template_data);

        foreach ($data as $placeholder => $value) {
            $template = str_replace('{{' . $placeholder . '}}', $value, $template);
        }

        return $template;
    }

    public function create_info_box($title, $content) {
        if (empty($content)) {
            return '';
        }

        $info_box = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f9f7f3; border: 1px solid #e6ddd0; border-radius: 6px; margin: 20px 0;">';
        $info_box .= '<tr><td style="padding: 20px;">';

        if (!empty($title)) {
            $info_box .= '<h3 style="font-size: 18px; color: #49391b; margin: 0 0 15px 0; font-weight: 700; font-family: Arial, sans-serif;">' . esc_html($title) . '</h3>';
        }

        if (is_array($content)) {
            foreach ($content as $item) {
                $info_box .= '<p style="margin: 0 0 10px 0; color: #876c4b; font-size: 14px; font-family: Arial, sans-serif;">' . esc_html($item) . '</p>';
            }
        } else {
            $info_box .= '<p style="margin: 0; color: #876c4b; font-size: 14px; font-family: Arial, sans-serif;">' . esc_html($content) . '</p>';
        }

        $info_box .= '</td></tr></table>';
        return $info_box;
    }

    public function create_email_button($text, $url) {
        if (empty($text) || empty($url)) {
            return '';
        }

        return '<table cellpadding="0" cellspacing="0" border="0" style="margin: 20px auto;">
            <tr>
                <td style="background-color: #49391b; border-radius: 6px; padding: 12px 30px;">
                    <a href="' . esc_url($url) . '" style="color: #ffffff; text-decoration: none; font-weight: 600; font-size: 16px; font-family: Arial, sans-serif; display: block;">' . esc_html($text) . '</a>
                </td>
            </tr>
        </table>';
    }

    public function send_registration_email($user_id, $form_data) {
        $user = get_user_by('ID', $user_id);

        $template_data = array(
            'EMAIL_SUBJECT' => 'Registrierung bei Schilcher Käse - Bestätigung',
            'EMAIL_PREHEADER' => 'Ihre Registrierung wurde erfolgreich eingereicht und wird nun geprüft.',
            'EMAIL_TITLE' => 'Registrierung erfolgreich eingereicht',
            'GREETING' => 'Sehr geehrte Damen und Herren,',
            'MAIN_CONTENT' => 'vielen Dank für Ihre Registrierung bei Schilcher Käse. Wir freuen uns über Ihr Interesse an einer Partnerschaft mit uns.',
            'INFO_BOX' => $this->create_info_box('Ihre Registrierungsdaten', array(
                'Firmenname: ' . $form_data['company_name'],
                'E-Mail: ' . $form_data['email_general'],
                'Benutzername: ' . $user->user_login
            )),
            'CLOSING_TEXT' => 'Ihr Antrag wird nun geprüft. Sie erhalten eine weitere E-Mail, sobald Ihr Konto freigeschaltet wurde.<br><br>Wir melden uns zeitnah bei Ihnen.'
        );

        $email_content = $this->load_email_template($template_data);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Schilcher Käse <vertrieb@schilcher-kaese.de>'
        );

        return wp_mail($form_data['email_general'], $template_data['EMAIL_SUBJECT'], $email_content, $headers);
    }

    public function send_approval_email($user_id) {
        $user = get_user_by('ID', $user_id);
        $company_name = get_user_meta($user_id, 'schilcher_b2b_company_name', true);

        $template_data = array(
            'EMAIL_SUBJECT' => 'Ihr Händlerkonto bei Schilcher Käse wurde freigeschaltet',
            'EMAIL_PREHEADER' => 'Ihr Händlerkonto wurde erfolgreich freigeschaltet - jetzt anmelden!',
            'EMAIL_TITLE' => 'Konto erfolgreich freigeschaltet',
            'GREETING' => 'Sehr geehrte Damen und Herren,',
            'MAIN_CONTENT' => 'Ihr Händlerkonto bei Schilcher Käse wurde erfolgreich freigeschaltet! Wir freuen uns auf die Zusammenarbeit.',
            'INFO_BOX' => $this->create_info_box('Ihre Zugangsdaten', array(
                'Benutzername: ' . $user->user_login,
                'E-Mail: ' . $user->user_email,
                'Firmenname: ' . $company_name
            )),
            'BUTTON' => $this->create_email_button('Jetzt anmelden', 'https://www.schilcher-kaese.de/intern'),
            'CLOSING_TEXT' => 'Bei der ersten Anmeldung werden Sie aufgefordert, ein neues Passwort zu erstellen.<br><br>Sollten Sie Fragen haben, stehen wir Ihnen gerne zur Verfügung.'
        );

        $email_content = $this->load_email_template($template_data);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Schilcher Käse <vertrieb@schilcher-kaese.de>'
        );

        return wp_mail($user->user_email, $template_data['EMAIL_SUBJECT'], $email_content, $headers);
    }

    public function send_rejection_email($user_id) {
        $user = get_user_by('ID', $user_id);

        $template_data = array(
            'EMAIL_SUBJECT' => 'Ihr Händlerantrag bei Schilcher Käse',
            'EMAIL_PREHEADER' => 'Informationen zu Ihrem Händlerantrag bei Schilcher Käse.',
            'EMAIL_TITLE' => 'Händlerantrag',
            'GREETING' => 'Sehr geehrte Damen und Herren,',
            'MAIN_CONTENT' => 'vielen Dank für Ihr Interesse an einer Händlerpartnerschaft mit Schilcher Käse.',
            'CLOSING_TEXT' => 'Nach Prüfung Ihres Antrags müssen wir Ihnen leider mitteilen, dass wir Ihren Antrag nicht genehmigen können.<br><br>Bei Fragen stehen wir Ihnen gerne zur Verfügung.'
        );

        $email_content = $this->load_email_template($template_data);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Schilcher Käse <vertrieb@schilcher-kaese.de>'
        );

        return wp_mail($user->user_email, $template_data['EMAIL_SUBJECT'], $email_content, $headers);
    }

    public function send_admin_notification($user_id, $form_data, $username) {
        // Get notification emails from admin settings
        $admin_manager = Schilcher_Admin_Manager::get_instance();
        $notification_emails = $admin_manager->get_notification_emails();
        
        $admin_subject = 'Neue B2B Registrierung - ' . $form_data['company_name'];

        $admin_message = "Neue B2B-Registrierung eingegangen:\n\n";
        $admin_message .= "=== GESCHÄFTSPARTNER ===\n";
        $admin_message .= "Firmenname: " . $form_data['company_name'] . "\n";
        $admin_message .= "Firmierung lt. Handelsregister: " . ($form_data['company_registration'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "Rechtsform: " . ($form_data['legal_form'] ?? 'Nicht angegeben') . "\n";
        if (!empty($form_data['legal_form_other'])) {
            $admin_message .= "Andere Rechtsform: " . $form_data['legal_form_other'] . "\n";
        }
        $admin_message .= "Geschäftsführung: " . ($form_data['management'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "Adresse: " . $form_data['address'] . "\n";
        $admin_message .= "Telefon: " . $form_data['phone'] . "\n";
        $admin_message .= "Fax: " . ($form_data['fax'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "Website: " . ($form_data['website'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "E-Mail Allgemein: " . $form_data['email_general'] . "\n";
        $admin_message .= "E-Mail Bestellungen: " . ($form_data['email_orders'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "E-Mail Wocheninfo: " . ($form_data['email_weekly'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "E-Mail Rechnungen: " . ($form_data['email_invoices'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "Lieferadresse: " . ($form_data['delivery_address'] ?? 'Wie Hauptadresse') . "\n";
        $admin_message .= "Rechnungsadresse: " . ($form_data['billing_address'] ?? 'Wie Hauptadresse') . "\n";
        $admin_message .= "Käsetheke Kontakt: " . ($form_data['cheese_counter_contact'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "Käsetheke Telefon: " . ($form_data['cheese_counter_phone'] ?? 'Nicht angegeben') . "\n";

        $admin_message .= "\n=== SONSTIGES ===\n";
        $admin_message .= "Spedition: " . ($form_data['shipping'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "IFCO-Kisten: " . ($form_data['ifco_delivery'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "SEPA-Lastschrift: " . ($form_data['sepa_mandate'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "Umsatzsteuer-ID: " . ($form_data['vat_id'] ?? 'Nicht angegeben') . "\n";
        $admin_message .= "Handelsregister-Kopie angefordert: " . ($form_data['trade_register_copy'] === '1' ? 'Ja' : 'Nein') . "\n";
        $admin_message .= "Gewerbeanmeldung-Kopie angefordert: " . ($form_data['business_registration_copy'] === '1' ? 'Ja' : 'Nein') . "\n";

        $admin_message .= "\n=== SYSTEM-INFORMATIONEN ===\n";
        $admin_message .= "Benutzername: " . $username . "\n";
        $admin_message .= "User ID: " . $user_id . "\n";
        $admin_message .= "Registrierungsdatum: " . current_time('mysql') . "\n";
        $admin_message .= "IP-Adresse: " . $this->get_client_ip() . "\n";

        $admin_message .= "\n=== AKTIONEN ===\n";
        $admin_message .= "Benutzerprofil bearbeiten: " . admin_url('user-edit.php?user_id=' . $user_id) . "\n";

        // Send to all configured notification emails
        $success = true;
        foreach ($notification_emails as $email) {
            $result = wp_mail($email, $admin_subject, $admin_message);
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
    }

    private function get_fallback_email_template() {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
            <div style="display: none; font-size: 1px; color: #fefefe; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">{{EMAIL_PREHEADER}}</div>
            <div style="background: linear-gradient(135deg, #fef8e7 0%, #f5f0e6 100%); padding: 30px; text-align: center; border-bottom: 3px solid #fdfdfc;">
                <img src="https://www.schilcher-kaese.de/wp-content/uploads/2025/06/logo_schilcher_kaese.svg" alt="Schilcher Käse" style="max-width: 250px; height: auto; margin-bottom: 20px;">
                <h1 style="font-size: 24px; font-weight: 700; color: #49391b; margin: 0;">{{EMAIL_TITLE}}</h1>
            </div>
            
            <div style="padding: 40px 30px;">
                <div style="font-size: 16px; font-weight: 600; color: #49391b; margin-bottom: 20px;">{{GREETING}}</div>
                
                <div style="font-size: 15px; line-height: 1.7; color: #876c4b; margin-bottom: 30px;">
                    {{MAIN_CONTENT}}
                </div>
                
                {{INFO_BOX}}
                {{BUTTON}}
                
                <div style="font-size: 15px; line-height: 1.7; color: #876c4b;">
                    {{CLOSING_TEXT}}
                </div>
            </div>
            
            <div style="background-color: #f9f7f3; padding: 30px; text-align: center; border-top: 1px solid #e6ddd0;">
                <div style="font-size: 14px; color: #876c4b; line-height: 1.6;">
                    <strong style="color: #49391b;">Mit freundlichen Grüßen</strong><br>
                    Ihr Schilcher Käse Team
                </div>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e6ddd0;">
                    <p style="margin: 5px 0; font-size: 14px; color: #876c4b;"><strong>Schilcher Käse GmbH</strong></p>
                    <p style="margin: 5px 0; font-size: 14px; color: #876c4b;">📧 E-Mail: vertrieb@schilcher-kaese.de</p>
                    <p style="margin: 5px 0; font-size: 14px; color: #876c4b;">📞 Telefon: +49 (0) 8869 911 515</p>
                    <p style="margin: 5px 0; font-size: 14px; color: #876c4b;">🌐 Website: www.schilcher-kaese.de</p>
                </div>
                
                <div style="font-style: italic; color: #bda77f; margin-top: 15px;">
                    Kompetenz in Biokäse
                </div>
            </div>
        </div>';
    }
}
