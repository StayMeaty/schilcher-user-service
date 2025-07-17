<?php
/**
 * Schilcher Shortcodes
 * Handles all shortcode functionality for the user service plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Schilcher_Shortcodes {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_shortcodes() {
        add_shortcode('schilcher_login_form', array($this, 'login_form_shortcode'));
        add_shortcode('schilcher_registration_form', array($this, 'registration_form_shortcode'));
        add_shortcode('schilcher_password_reset_form', array($this, 'password_reset_form_shortcode'));
        add_shortcode('schilcher_password_reset_complete_form', array($this, 'password_reset_complete_form_shortcode'));
        add_shortcode('schilcher_user_navbar', array($this, 'user_navbar_shortcode'));
        add_shortcode('schilcher_nonce', array($this, 'nonce_shortcode'));
    }

    /**
     * Login form shortcode
     */
    public function login_form_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts, 'schilcher_login_form');
        
        ob_start();
        ?>
        <div class="schilcher-login-page-wrapper">
            <!-- Login Header -->
            <div class="schilcher-login-header">
                <div class="schilcher-logo">
                    <img src="https://www.schilcher-kaese.de/wp-content/uploads/2025/06/logo_schilcher_kaese.svg" alt="Schilcher - Kompetenz in Biokäse">
                </div>
                <h1>Händlerbereich</h1>
                <p>Melden Sie sich an, um auf Ihr Händlerkonto zuzugreifen</p>
            </div>

            <!-- Login Container -->
            <div class="schilcher-login-container">
                <!-- Login Section -->
                <div class="schilcher-login-section">
                    <h2 class="schilcher-section-title">Anmelden</h2>

                    <!-- Display Login Messages -->
                    <div id="schilcher-login-messages"></div>

                    <!-- Password Reset Success Message -->
                    <div id="password-reset-success-message" class="schilcher-login-success" style="display: none;">
                        ✓ Ihr Passwort wurde erfolgreich zurückgesetzt! Sie können sich jetzt mit Ihrem neuen Passwort anmelden.
                    </div>

                    <!-- Login Form -->
                    <form method="post" action="" id="schilcher-login-form">
                        <input type="hidden" name="login_nonce" value="" id="login-nonce">

                        <label for="schilcher-username">Benutzername oder E-Mail-Adresse</label>
                        <input type="text"
                               name="username"
                               id="schilcher-username"
                               required
                               autocomplete="username">

                        <label for="schilcher-password">Passwort</label>
                        <input type="password"
                               name="password"
                               id="schilcher-password"
                               required
                               autocomplete="current-password">

                        <div class="schilcher-checkbox-wrapper">
                            <input type="checkbox"
                                   name="remember"
                                   id="schilcher-remember"
                                   value="yes">
                            <label for="schilcher-remember">Angemeldet bleiben</label>
                        </div>

                        <button type="submit" name="schilcher_login_submit">Anmelden</button>

                        <div class="schilcher-forgot-password-wrapper">
                            <a href="/reset-password" class="schilcher-forgot-password-link">Passwort vergessen?</a>
                        </div>
                    </form>
                </div>

                <!-- Privacy Notice -->
                <div class="schilcher-privacy-notice">
                    <p>Ihre persönlichen Daten werden gemäß unseren <a href="https://www.schilcher-kaese.de/datenschutz/" target="_blank">Datenschutzbestimmungen</a> vertraulich behandelt und ausschließlich zur Verwaltung Ihres Händlerkontos verwendet.</p>
                </div>

                <!-- Trust Badge -->
                <div class="schilcher-trust-badge">
                    <span class="schilcher-trust-badge-icon">🔒</span>
                    <span>Ihre Daten sind sicher und SSL-verschlüsselt</span>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="schilcher-info-box">
                <h3>Noch kein Händlerkonto?</h3>
                <p>Registrieren Sie sich jetzt und erhalten Sie Zugang zu unserem exklusiven Portal für Händler.</p>
                <a href="/register" class="schilcher-btn-secondary">Jetzt registrieren</a>
            </div>
        </div>

        <script>
            // Check for password reset success parameter and show message
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const passwordResetSuccess = urlParams.get('password_reset_success');
                
                if (passwordResetSuccess === '1') {
                    const successMessage = document.getElementById('password-reset-success-message');
                    if (successMessage) {
                        successMessage.style.display = 'block';
                    }
                    
                    // Clean up URL parameter
                    if (window.history && window.history.replaceState) {
                        const url = new URL(window.location);
                        url.searchParams.delete('password_reset_success');
                        window.history.replaceState({}, document.title, url.pathname + url.search);
                    }
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Registration form shortcode
     */
    public function registration_form_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts, 'schilcher_registration_form');
        
        // Check if user is already logged in
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $display_name = !empty($current_user->display_name) ? $current_user->display_name : $current_user->user_login;
            
            ob_start();
            ?>
            <div class="schilcher-dealer-registration-wrapper">
                <!-- Header Section -->
                <div class="schilcher-registration-header">
                    <div class="schilcher-logo">
                        <img src="https://www.schilcher-kaese.de/wp-content/uploads/2025/06/logo_schilcher_kaese.svg" alt="Schilcher - Kompetenz in Biokäse">
                    </div>
                    <h2>Bereits angemeldet</h2>
                </div>

                <!-- Already Logged In Message -->
                <div class="schilcher-form-container">
                    <div class="schilcher-already-logged-in-box">
                        <div class="schilcher-success-icon">✓</div>
                        <h3>Hallo <?php echo esc_html($display_name); ?>!</h3>
                        <p>Sie sind bereits in Ihrem Händlerkonto angemeldet. Eine erneute Registrierung ist nicht erforderlich.</p>
                        
                        <div class="schilcher-logged-in-actions">
                            <a href="<?php echo esc_url(home_url()); ?>" class="schilcher-btn-primary">
                                Zurück zur Hauptseite
                            </a>
                            <a href="#" onclick="schilcherLogoutUser(); return false;" class="schilcher-btn-secondary">
                                Abmelden
                            </a>
                        </div>
                        
                        <div class="schilcher-logout-info">
                            <p><small>Sie können sich abmelden, um ein neues Konto zu registrieren oder sich mit einem anderen Konto anzumelden.</small></p>
                        </div>
                        
                        <div class="schilcher-contact-info">
                            <p><strong>Bei Fragen erreichen Sie uns:</strong></p>
                            <p>📧 E-Mail: vertrieb@schilcher-kaese.de<br>
                            📞 Telefon: +49 (0) 8869 911 515</p>
                        </div>
                    </div>
                </div>

                <!-- Trust Indicators -->
                <div class="schilcher-trust-indicators">
                    <div class="schilcher-trust-item">
                        <span class="schilcher-trust-icon">🔒</span>
                        <span>Ihre Daten sind sicher</span>
                    </div>
                    <div class="schilcher-trust-item">
                        <span class="schilcher-trust-icon">✓</span>
                        <span>DSGVO-konform</span>
                    </div>
                    <div class="schilcher-trust-item">
                        <span class="schilcher-trust-icon">🧀</span>
                        <span>Premium Käsesortiment</span>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
        
        ob_start();
        ?>
        <div class="schilcher-dealer-registration-wrapper">
            <!-- Header Section -->
            <div class="schilcher-registration-header">
                <div class="schilcher-logo">
                    <img src="https://www.schilcher-kaese.de/wp-content/uploads/2025/06/logo_schilcher_kaese.svg" alt="Schilcher - Kompetenz in Biokäse">
                </div>
                <h2>Händlerzugang für Premium Biokäse</h2>
                <p>Werden Sie Teil unseres exklusiven Händlernetzwerks und bieten Sie Ihren Kunden erstklassige Schilcher Käsespezialitäten – denn Käse kauft man am besten beim Käsermeister!</p>
            </div>

            <!-- Benefits Section -->
            <div class="schilcher-benefits-section">
                <div class="schilcher-benefit-card">
                    <div class="schilcher-benefit-icon">🧀</div>
                    <h3>Premium Sortiment</h3>
                    <p>Zugang zu unserem vollständigen Sortiment an handwerklich hergestellten Käsespezialitäten</p>
                </div>

                <div class="schilcher-benefit-card">
                    <div class="schilcher-benefit-icon">🫰🏼</div>
                    <h3>Attraktive Konditionen</h3>
                    <p>Profitieren Sie von speziellen Händlerpreisen und mengenbezogenen Rabatten</p>
                </div>

                <div class="schilcher-benefit-card">
                    <div class="schilcher-benefit-icon">🚚</div>
                    <h3>Zuverlässige Lieferung</h3>
                    <p>Schnelle und sichere Lieferung mit garantierter Kühlkette für optimale Produktqualität</p>
                </div>
            </div>

            <!-- Form Container -->
            <div class="schilcher-form-container">
                <div class="schilcher-form-intro">
                    <h2>Jetzt als Händler registrieren</h2>
                    <p>Füllen Sie das folgende Formular aus, um Zugang zu unserem B2B-Portal zu erhalten. Nach der Registrierung werden Ihre Daten geprüft und Sie erhalten eine Bestätigung per E-Mail.</p>
                </div>

                <!-- Success Message Container -->
                <div id="registration-success-container" style="display: none;">
                    <div class="schilcher-success-message-box">
                        <div class="schilcher-success-icon">✓</div>
                        <h3>Registrierung erfolgreich eingereicht!</h3>
                        <p>Vielen Dank für Ihre Registrierung bei Schilcher Käse. Ihre Anfrage wird nun geprüft.</p>
                        <div class="schilcher-next-steps">
                            <h4>Wie geht es weiter?</h4>
                            <ol>
                                <li>Wir prüfen Ihre Angaben und Berechtigung als Händler</li>
                                <li>Nach der Freischaltung erhalten Sie eine E-Mail mit Ihren Zugangsdaten und können sich damit anmelden.</li>
                            </ol>
                        </div>
                        <div class="schilcher-contact-info">
                            <p><strong>Bei Fragen erreichen Sie uns:</strong></p>
                            <p>📧 E-Mail: vertrieb@schilcher-kaese.de<br>
                            📞 Telefon: +49 (0) 8869 911 515</p>
                        </div>
                    </div>
                </div>

                <!-- Registration Form -->
                <div class="schilcher-custom-registration-form">
                    <!-- Display Registration Messages -->
                    <div id="schilcher-registration-messages"></div>

                    <!-- Registration Form -->
                    <form method="post" action="" id="schilcher-registration-form">
                        <!-- Security nonce -->
                        <input type="hidden" name="registration_nonce" value="" id="registration-nonce">

                        <!-- Section 1: Business Partner Details -->
                        <div class="schilcher-form-section">
                            <h3 class="schilcher-section-title">1. Angaben zum Geschäftspartner</h3>
                            
                            <!-- Legal Form -->
                            <div class="schilcher-form-group">
                                <label class="schilcher-form-label">Rechtsform: *</label>
                                <div class="schilcher-radio-group">
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="legal_form" value="einzelunternehmen" id="legal_form_einzelunternehmen" required>
                                        <label for="legal_form_einzelunternehmen">Einzelunternehmen</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="legal_form" value="gmbh" id="legal_form_gmbh" required>
                                        <label for="legal_form_gmbh">GmbH</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="legal_form" value="ag" id="legal_form_ag" required>
                                        <label for="legal_form_ag">AG</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="legal_form" value="gbr" id="legal_form_gbr" required>
                                        <label for="legal_form_gbr">GbR</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="legal_form" value="ohg" id="legal_form_ohg" required>
                                        <label for="legal_form_ohg">OHG</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="legal_form" value="kg" id="legal_form_kg" required>
                                        <label for="legal_form_kg">KG</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="legal_form" value="gmbh_co_kg" id="legal_form_gmbh_co_kg" required>
                                        <label for="legal_form_gmbh_co_kg">GmbH & Co. KG</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="legal_form" value="sonstige" id="legal_form_sonstige" required>
                                        <label for="legal_form_sonstige">Sonstige:</label>
                                        <input type="text" name="legal_form_other" id="legal_form_other" placeholder="Andere Rechtsform">
                                    </div>
                                </div>
                            </div>

                            <!-- Company Registration -->
                            <div class="schilcher-form-group">
                                <label for="company_registration">Firmierung lt. Handelsregister: *</label>
                                <input type="text" name="company_registration" id="company_registration" required>
                            </div>

                            <!-- Company Name -->
                            <div class="schilcher-form-group">
                                <label for="company_name">Unternehmensname/Firma: *</label>
                                <input type="text" name="company_name" id="company_name" required>
                            </div>

                            <!-- Management -->
                            <div class="schilcher-form-group">
                                <label for="management">Geschäftsführung: *</label>
                                <input type="text" name="management" id="management" required>
                            </div>

                            <!-- Address -->
                            <div class="schilcher-form-group">
                                <label for="address">Adresse: (Straße, Haus-Nr., PLZ, Ort, Land) *</label>
                                <textarea name="address" id="address" required placeholder="Musterstraße 123, 12345 Musterstadt, Deutschland"></textarea>
                            </div>

                            <!-- Phone -->
                            <div class="schilcher-form-group">
                                <label for="phone">Telefon-Nr.: *</label>
                                <input type="tel" name="phone" id="phone" required>
                            </div>

                            <!-- Fax -->
                            <div class="schilcher-form-group">
                                <label for="fax">Fax-Nr.:</label>
                                <input type="tel" name="fax" id="fax">
                            </div>

                            <!-- Website -->
                            <div class="schilcher-form-group">
                                <label for="website">Internetseite:</label>
                                <input type="text" name="website" id="website">
                            </div>

                            <!-- General Email -->
                            <div class="schilcher-form-group">
                                <label for="email_general">E-Mail-Adresse Allgemein: *</label>
                                <input type="email" name="email_general" id="email_general" required>
                            </div>

                            <!-- Order List Email -->
                            <div class="schilcher-form-group">
                                <label for="email_orders">E-Mail-Adresse Bestelllisten: *</label>
                                <input type="email" name="email_orders" id="email_orders" required>
                            </div>

                            <!-- Weekly Info Email -->
                            <div class="schilcher-form-group">
                                <label for="email_weekly">E-Mail-Adresse Wocheninfo: *</label>
                                <input type="email" name="email_weekly" id="email_weekly" required>
                            </div>

                            <!-- Delivery Address -->
                            <div class="schilcher-form-group">
                                <label for="delivery_address">Lieferadresse: *</label>
                                <textarea name="delivery_address" id="delivery_address" required placeholder="Lieferadresse falls abweichend von Hauptadresse"></textarea>
                            </div>

                            <!-- Billing Address -->
                            <div class="schilcher-form-group">
                                <label for="billing_address">Rechnungsadresse: *</label>
                                <textarea name="billing_address" id="billing_address" required placeholder="Rechnungsadresse falls abweichend von Hauptadresse"></textarea>
                            </div>

                            <!-- Invoice Email -->
                            <div class="schilcher-form-group">
                                <label for="email_invoices">E-Mail-Adresse Rechnungen: *</label>
                                <input type="email" name="email_invoices" id="email_invoices" required>
                            </div>

                            <!-- Cheese Counter Contact -->
                            <div class="schilcher-form-group">
                                <label for="cheese_counter_contact">Ansprechpartner/in Käsetheke: *</label>
                                <input type="text" name="cheese_counter_contact" id="cheese_counter_contact" required>
                            </div>

                            <!-- Cheese Counter Phone -->
                            <div class="schilcher-form-group">
                                <label for="cheese_counter_phone">Telefon-Nr. Käsetheke: *</label>
                                <input type="tel" name="cheese_counter_phone" id="cheese_counter_phone" required>
                            </div>
                        </div>

                        <!-- Section 2: Other Information -->
                        <div class="schilcher-form-section">
                            <h3 class="schilcher-section-title">2. Sonstiges</h3>

                            <!-- Shipping -->
                            <div class="schilcher-form-group">
                                <label class="schilcher-form-label">Spedition: *</label>
                                <div class="schilcher-radio-group">
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="shipping" value="ave" id="shipping_ave" required>
                                        <label for="shipping_ave">AVE</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="shipping" value="nagel" id="shipping_nagel" required>
                                        <label for="shipping_nagel">NAGEL</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="shipping" value="dachser" id="shipping_dachser" required>
                                        <label for="shipping_dachser">DACHSER</label>
                                    </div>
                                </div>
                            </div>

                            <!-- IFCO Containers -->
                            <div class="schilcher-form-group">
                                <label class="schilcher-form-label">Lieferung in IFCO-Kisten: *</label>
                                <div class="schilcher-radio-group schilcher-compact">
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="ifco_delivery" value="ja" id="ifco_ja" required>
                                        <label for="ifco_ja">JA</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="ifco_delivery" value="nein" id="ifco_nein" required>
                                        <label for="ifco_nein">NEIN</label>
                                    </div>
                                </div>
                            </div>

                            <!-- SEPA Direct Debit -->
                            <div class="schilcher-form-group">
                                <label class="schilcher-form-label">SEPA-Lastschriftmandat: *</label>
                                <div class="schilcher-radio-group schilcher-compact">
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="sepa_mandate" value="ja" id="sepa_ja" required>
                                        <label for="sepa_ja">JA</label>
                                    </div>
                                    <div class="schilcher-radio-item">
                                        <input type="radio" name="sepa_mandate" value="nein" id="sepa_nein" required>
                                        <label for="sepa_nein">NEIN</label>
                                    </div>
                                </div>
                            </div>

                            <!-- VAT ID -->
                            <div class="schilcher-form-group">
                                <label for="vat_id">Umsatzsteuer-Identifikations-Nr.: *</label>
                                <input type="text" name="vat_id" id="vat_id" required>
                            </div>

                            <!-- Trade Register Copy -->
                            <div class="schilcher-form-group">
                                <div class="schilcher-checkbox-item">
                                    <input type="checkbox" name="trade_register_copy" value="1" id="trade_register_copy">
                                    <label for="trade_register_copy">Kopie Handelsregisterauszug (bitte per Mail schicken!) <br><span class="schilcher-explanatory-text">Sofern Sie bzw. Ihr Unternehmen in einem amtlichen Register eingetragen sind, legen Sie uns bitte eine Kopie des Registerauszuges bei</span></label>
                                </div>
                            </div>

                            <!-- Business Registration Copy -->
                            <div class="schilcher-form-group">
                                <div class="schilcher-checkbox-item">
                                    <input type="checkbox" name="business_registration_copy" value="1" id="business_registration_copy">
                                    <label for="business_registration_copy">Kopie Gewerbeanmeldung (bitte per Mail schicken!)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" name="schilcher_registration_submit" class="schilcher-submit-button">
                            Registrierung absenden
                        </button>
                    </form>
                </div>

                <!-- Privacy Notice -->
                <div class="schilcher-privacy-notice">
                    <p>Mit der Registrierung stimmen Sie unseren <a href="https://www.schilcher-kaese.de/datenschutz/" target="_blank">Datenschutzbestimmungen</a> zu. Ihre Daten werden vertraulich behandelt und ausschließlich zur Abwicklung Ihrer Händleranfragen verwendet.</p>
                </div>

                <!-- Trust Indicators -->
                <div class="schilcher-trust-indicators">
                    <div class="schilcher-trust-item">
                        <span class="schilcher-trust-icon">🔒</span>
                        <span>SSL-verschlüsselte Datenübertragung</span>
                    </div>
                    <div class="schilcher-trust-item">
                        <span class="schilcher-trust-icon">✓</span>
                        <span>DSGVO-konform</span>
                    </div>
                    <div class="schilcher-trust-item">
                        <span class="schilcher-trust-icon">⚡</span>
                        <span>Schnelle Freischaltung</span>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle "Sonstige" legal form radio button
            const legalFormRadios = document.querySelectorAll('input[name="legal_form"]');
            const legalFormOtherInput = document.getElementById('legal_form_other');
            
            if (legalFormRadios.length > 0 && legalFormOtherInput) {
                legalFormRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.value === 'sonstige' && this.checked) {
                            legalFormOtherInput.style.display = 'inline-block';
                            legalFormOtherInput.focus();
                        } else {
                            legalFormOtherInput.style.display = 'none';
                            legalFormOtherInput.value = '';
                        }
                    });
                });
                
                // Initially hide the other input
                legalFormOtherInput.style.display = 'none';
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Password reset form shortcode
     */
    public function password_reset_form_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts, 'schilcher_password_reset_form');
        
        ob_start();
        ?>
        <div class="schilcher-login-page-wrapper">
            <!-- Login Header -->
            <div class="schilcher-login-header">
                <div class="schilcher-logo">
                    <img src="https://www.schilcher-kaese.de/wp-content/uploads/2025/06/logo_schilcher_kaese.svg"
                         alt="Schilcher - Kompetenz in Biokäse">
                </div>
                <h1>Passwort zurücksetzen</h1>
                <p>Geben Sie Ihre E-Mail-Adresse ein, um Ihr Passwort zurückzusetzen</p>
            </div>

            <!-- Login Container -->
            <div class="schilcher-login-container">
                <!-- Password Reset Section -->
                <div class="schilcher-login-section">

                    <div class="schilcher-info-text">
                        Sie erhalten eine E-Mail mit einem Link zum Zurücksetzen Ihres Passworts. Bitte überprüfen Sie auch
                        Ihren Spam-Ordner.
                    </div>

                    <!-- Display Messages -->
                    <div id="schilcher-reset-messages"></div>

                    <!-- Password Reset Form -->
                    <form method="post" action="" id="schilcher-reset-form">
                        <!-- Nonce field will be added by JavaScript -->
                        <input type="hidden" name="reset_nonce" value="" id="reset-nonce">

                        <label for="reset-email">E-Mail-Adresse</label>
                        <input type="email"
                               name="user_email"
                               id="reset-email"
                               required
                               autocomplete="email"
                               placeholder="ihre@email-adresse.de">

                        <button type="submit" name="schilcher_reset_submit">Passwort-Link senden</button>

                        <div class="schilcher-back-to-login-wrapper">
                            <a href="/intern" class="schilcher-back-to-login-link">← Zurück zur Anmeldung</a>
                        </div>
                    </form>
                </div>

                <!-- Trust Badge -->
                <div class="schilcher-trust-badge">
                    <span class="schilcher-trust-badge-icon">🔒</span>
                    <span>Ihre Daten sind sicher und SSL-verschlüsselt</span>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="schilcher-info-box">
                <h3>Probleme beim Zurücksetzen?</h3>
                <p>Falls Sie keine E-Mail erhalten, kontaktieren Sie uns direkt. Wir helfen Ihnen gerne weiter.</p>
                <div class="schilcher-contact-info">
                    <p><strong>Schilcher Käse GmbH</strong><br>
                        Herzogstraße 9 | 86981 Kinsau</p>
                    <p>Telefon: 08869 - 911 515<br>
                        E-Mail: <a href="mailto:vertrieb@schilcher-kaese.de">vertrieb@schilcher-kaese.de</a></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Password reset complete form shortcode
     */
    public function password_reset_complete_form_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts, 'schilcher_password_reset_complete_form');
        
        ob_start();
        ?>
        <div class="schilcher-login-page-wrapper">
            <!-- Login Header -->
            <div class="schilcher-login-header">
                <div class="schilcher-logo">
                    <img src="https://www.schilcher-kaese.de/wp-content/uploads/2025/06/logo_schilcher_kaese.svg" alt="Schilcher - Kompetenz in Biokäse">
                </div>
                <h1>Neues Passwort setzen</h1>
                <p>Geben Sie Ihr neues Passwort ein</p>
            </div>

            <!-- Login Container -->
            <div class="schilcher-login-container">
                <!-- Password Reset Section -->
                <div class="schilcher-login-section">
                    <h2 class="schilcher-section-title">Passwort zurücksetzen</h2>

                    <!-- Password Requirements -->
                    <div class="schilcher-password-requirements">
                        <strong>Passwort-Anforderungen:</strong>
                        <ul>
                            <li>Mindestens 6 Zeichen lang</li>
                            <li>Kombination aus Groß- und Kleinbuchstaben</li>
                            <li>Mindestens eine Zahl</li>
                            <li>Mindestens ein Sonderzeichen (!, @, #, $, %, etc.)</li>
                        </ul>
                    </div>

                    <!-- Display Messages -->
                    <div id="schilcher-reset-complete-messages"></div>

                    <!-- Password Reset Form -->
                    <form method="post" action="" id="schilcher-reset-complete-form">
                        <!-- Hidden fields will be populated by JavaScript -->
                        <input type="hidden" name="reset_key" value="" id="reset-key">
                        <input type="hidden" name="user_login" value="" id="user-login">
                        <input type="hidden" name="reset_complete_nonce" value="" id="reset-complete-nonce">

                        <label for="new-password">Neues Passwort</label>
                        <input type="password"
                               name="new_password"
                               id="new-password"
                               required
                               autocomplete="new-password"
                               placeholder="Neues sicheres Passwort eingeben">

                        <!-- Password Strength Indicator -->
                        <div class="schilcher-password-strength">
                            <div class="schilcher-password-strength-indicator">
                                <div class="schilcher-password-strength-bar" id="password-strength-bar"></div>
                            </div>
                            <div class="schilcher-password-strength-text" id="password-strength-text">Passwortstärke</div>
                        </div>

                        <label for="confirm-password">Passwort bestätigen</label>
                        <input type="password"
                               name="confirm_password"
                               id="confirm-password"
                               required
                               autocomplete="new-password"
                               placeholder="Passwort wiederholen">

                        <button type="submit" name="schilcher_reset_complete_submit">Passwort speichern</button>

                        <div class="schilcher-back-to-login-wrapper">
                            <a href="/intern" class="schilcher-back-to-login-link">← Zurück zur Anmeldung</a>
                        </div>
                    </form>
                </div>

                <!-- Trust Badge -->
                <div class="schilcher-trust-badge">
                    <span class="schilcher-trust-badge-icon">🔒</span>
                    <span>Ihre Daten sind sicher und SSL-verschlüsselt</span>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="schilcher-info-box">
                <h3>Probleme beim Zurücksetzen?</h3>
                <p>Falls Sie keine E-Mail erhalten, kontaktieren Sie uns direkt. Wir helfen Ihnen gerne weiter.</p>
                <div class="schilcher-contact-info">
                    <p><strong>Schilcher Käse GmbH</strong><br>
                        Herzogstraße 9 | 86981 Kinsau</p>
                    <p>Telefon: 08869 - 911 515<br>
                        E-Mail: <a href="mailto:vertrieb@schilcher-kaese.de">vertrieb@schilcher-kaese.de</a></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * User navbar shortcode
     */
    public function user_navbar_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts, 'schilcher_user_navbar');
        
        ob_start();
        ?>
        <div class="schilcher-account-button" id="schilcherAccountButton">
            <!-- Login Button (shown when logged out) -->
            <a href="/intern" class="schilcher-login-button">
                Händler Login
            </a>

            <!-- Account Dropdown (shown when logged in) -->
            <div class="schilcher-account-dropdown">
                <button class="schilcher-account-trigger" onclick="toggleSchilcherDropdown()">
                    <div class="schilcher-user-icon" id="schilcherUserIcon">U</div>
                    <span class="schilcher-username" id="schilcherUsername">Händler</span>
                    <span class="schilcher-dropdown-arrow">▼</span>
                </button>
                <div class="schilcher-dropdown-menu" id="schilcherDropdownMenu">
                    <button class="schilcher-dropdown-item" onclick="schilcherPasswordChange()">Passwort ändern</button>
                    <button class="schilcher-dropdown-item" onclick="schilcherLogoutUser()">Abmelden</button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Nonce shortcode
     */
    public function nonce_shortcode($atts) {
        $atts = shortcode_atts(array(
            'action' => 'schilcher_registration_nonce'
        ), $atts, 'schilcher_nonce');
        
        return wp_create_nonce($atts['action']);
    }
}
