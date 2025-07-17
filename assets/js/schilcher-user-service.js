/**
 * Schilcher User Service Plugin JavaScript
 * Handles all client-side functionality for the user management system
 */

(function($) {
    'use strict';

    // Global variables
    let schilcherUserEmail = '';

    /**
     * Initialize the plugin when DOM is ready
     */
    $(document).ready(function() {
        console.log('Schilcher User Service: JavaScript loaded');
        
        // Initialize all components
        initLoginForm();
        initRegistrationForm();
        initPasswordResetForm();
        initPasswordResetCompleteForm();
        initUserNavbar();
        initPasswordResetSuccess();
    });

    /**
     * Initialize login form functionality
     */
    function initLoginForm() {
        const loginForm = $('#schilcher-login-form');
        if (!loginForm.length) return;

        console.log('Schilcher User Service: Login form found, setting up AJAX...');

        // Handle form submission via AJAX
        loginForm.on('submit', function(e) {
            e.preventDefault();
            console.log('Login form submitted via AJAX...');

            const submitButton = $(this).find('button[type="submit"]');
            const messagesContainer = $('#schilcher-login-messages');

            // Show loading state
            submitButton.text('Anmelden...').prop('disabled', true);
            messagesContainer.html('');

            // Get form data
            const formData = {
                action: 'schilcher_login',
                username: $('#schilcher-username').val(),
                password: $('#schilcher-password').val(),
                remember: $('#schilcher-remember').is(':checked') ? 'yes' : 'no',
                nonce: schilcherAjax.login_nonce
            };

            // Send AJAX request
            $.post(schilcherAjax.ajaxurl, formData)
                .done(function(response) {
                    console.log('Login AJAX response:', response);
                    
                    if (response.success) {
                        messagesContainer.html('<div class="schilcher-login-success">' + response.message + '</div>');
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 1000);
                    } else {
                        messagesContainer.html('<div class="schilcher-login-error">' + response.message + '</div>');
                        submitButton.text('Anmelden').prop('disabled', false);
                    }
                })
                .fail(function() {
                    messagesContainer.html('<div class="schilcher-login-error">Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.</div>');
                    submitButton.text('Anmelden').prop('disabled', false);
                });
        });
    }

    /**
     * Initialize registration form functionality
     */
    function initRegistrationForm() {
        const registrationForm = $('#schilcher-registration-form');
        if (!registrationForm.length) return;

        console.log('Schilcher User Service: Registration form found');

        // Ensure nonce is properly set
        const nonceField = $('#registration-nonce');
        if (nonceField.length && (!nonceField.val() || nonceField.val() === '[schilcher_nonce]')) {
            $.post(schilcherAjax.ajaxurl, { action: 'get_registration_nonce' })
                .done(function(response) {
                    if (response.success && response.data && response.data.nonce) {
                        nonceField.val(response.data.nonce);
                        console.log('Registration nonce set via AJAX');
                    }
                });
        }

        // Handle "Sonstige" legal form radio button
        const legalFormRadios = $('input[name="legal_form"]');
        const legalFormOtherInput = $('#legal_form_other');
        
        legalFormRadios.on('change', function() {
            if ($(this).val() === 'sonstige' && $(this).is(':checked')) {
                legalFormOtherInput.show().focus();
            } else {
                legalFormOtherInput.hide().val('');
            }
        });
        
        // Initially hide the other input
        legalFormOtherInput.hide();

        // Handle form submission
        registrationForm.on('submit', function(e) {
            e.preventDefault();
            console.log('Registration form submission started');

            const submitButton = $(this).find('button[type="submit"]');
            const messagesContainer = $('#schilcher-registration-messages');

            // Show loading state
            submitButton.text('Registrierung wird gesendet...').prop('disabled', true);
            messagesContainer.html('');

            // Collect form data
            const formData = new FormData(this[0]);
            formData.append('action', 'schilcher_registration');

            // Send AJAX request
            $.ajax({
                url: schilcherAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done(function(response) {
                console.log('Registration AJAX response:', response);
                
                if (response.success) {
                    // Hide the form and show success container
                    registrationForm.parent().hide();
                    $('#registration-success-container').show();
                    messagesContainer.html('<div class="schilcher-registration-success">' + response.message + '</div>');
                    
                    // Scroll to top
                    $('html, body').animate({ scrollTop: 0 }, 'smooth');
                } else {
                    messagesContainer.html('<div class="schilcher-registration-error">' + response.message + '</div>');
                    messagesContainer[0].scrollIntoView({ behavior: 'smooth' });
                }

                submitButton.text('Registrierung absenden').prop('disabled', false);
            })
            .fail(function() {
                messagesContainer.html('<div class="schilcher-registration-error">Ein technischer Fehler ist aufgetreten. Bitte versuchen Sie es erneut oder kontaktieren Sie uns direkt.</div>');
                submitButton.text('Registrierung absenden').prop('disabled', false);
            });
        });
    }

    /**
     * Initialize password reset form functionality
     */
    function initPasswordResetForm() {
        const resetForm = $('#schilcher-reset-form');
        if (!resetForm.length) return;

        console.log('Schilcher User Service: Password reset form found');

        // Check for email URL parameter and pre-fill if present
        const urlParams = new URLSearchParams(window.location.search);
        const emailParam = urlParams.get('email');
        const emailField = $('#reset-email');
        
        if (emailParam && emailField.length) {
            emailField.val(decodeURIComponent(emailParam));
            console.log('Email field pre-filled from URL parameter:', emailParam);
        }

        // Handle form submission
        resetForm.on('submit', function(e) {
            e.preventDefault();
            console.log('Password reset form submitted');

            const submitButton = $(this).find('button[type="submit"]');
            const messagesContainer = $('#schilcher-reset-messages');

            // Show loading state
            submitButton.text('Sende...').prop('disabled', true);
            messagesContainer.html('');

            // Get form data
            const formData = {
                action: 'schilcher_password_reset',
                user_email: $('#reset-email').val(),
                nonce: schilcherAjax.reset_nonce
            };

            // Send AJAX request
            $.post(schilcherAjax.ajaxurl, formData)
                .done(function(response) {
                    console.log('Password reset AJAX response:', response);
                    
                    if (response.success) {
                        messagesContainer.html('<div class="schilcher-login-success">' + response.message + '</div>');
                        $('#reset-email').val('');
                    } else {
                        messagesContainer.html('<div class="schilcher-login-error">' + response.message + '</div>');
                    }

                    submitButton.text('Passwort-Link senden').prop('disabled', false);
                })
                .fail(function() {
                    messagesContainer.html('<div class="schilcher-login-error">Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.</div>');
                    submitButton.text('Passwort-Link senden').prop('disabled', false);
                });
        });
    }

    /**
     * Initialize password reset complete form functionality
     */
    function initPasswordResetCompleteForm() {
        const resetCompleteForm = $('#schilcher-reset-complete-form');
        if (!resetCompleteForm.length) return;

        console.log('Schilcher User Service: Password reset complete form found');

        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const resetKey = urlParams.get('key');
        const userLogin = urlParams.get('login');

        console.log('Reset key:', resetKey);
        console.log('User login:', userLogin);

        // Set hidden fields
        $('#reset-key').val(resetKey || '');
        $('#user-login').val(userLogin || '');

        // Check if we have required parameters
        if (!resetKey || !userLogin) {
            const messagesContainer = $('#schilcher-reset-complete-messages');
            messagesContainer.html('<div class="schilcher-login-error">Ungültiger Reset-Link. Bitte fordern Sie einen neuen an.</div>');
            return;
        }

        // Password strength checker
        const passwordField = $('#new-password');
        const strengthBar = $('#password-strength-bar');
        const strengthText = $('#password-strength-text');

        if (passwordField.length && strengthBar.length && strengthText.length) {
            passwordField.on('input', function() {
                const password = $(this).val();
                const strength = calculatePasswordStrength(password);
                
                strengthBar.removeClass('weak fair good strong').addClass(strength.class);
                strengthText.text(strength.text);
            });
        }

        // Handle form submission
        resetCompleteForm.on('submit', function(e) {
            e.preventDefault();
            console.log('Password reset complete form submitted');

            const submitButton = $(this).find('button[type="submit"]');
            const messagesContainer = $('#schilcher-reset-complete-messages');

            // Show loading state
            submitButton.text('Speichern...').prop('disabled', true);
            messagesContainer.html('');

            // Get form data
            const formData = {
                action: 'schilcher_reset_complete',
                reset_key: resetKey,
                user_login: userLogin,
                new_password: $('#new-password').val(),
                confirm_password: $('#confirm-password').val(),
                nonce: schilcherAjax.reset_complete_nonce
            };

            // Send AJAX request
            $.post(schilcherAjax.ajaxurl, formData)
                .done(function(response) {
                    console.log('Password reset complete AJAX response:', response);
                    
                    if (response.success) {
                        messagesContainer.html('<div class="schilcher-login-success">' + response.message + '</div>');
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 1000);
                    } else {
                        messagesContainer.html('<div class="schilcher-login-error">' + response.message + '</div>');
                        submitButton.text('Passwort speichern').prop('disabled', false);
                    }
                })
                .fail(function() {
                    messagesContainer.html('<div class="schilcher-login-error">Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.</div>');
                    submitButton.text('Passwort speichern').prop('disabled', false);
                });
        });
    }

    /**
     * Calculate password strength
     */
    function calculatePasswordStrength(password) {
        let score = 0;

        // Length check
        if (password.length >= 6) score += 25;
        else if (password.length >= 4) score += 10;

        // Character variety
        if (/[a-z]/.test(password)) score += 15;
        if (/[A-Z]/.test(password)) score += 15;
        if (/[0-9]/.test(password)) score += 15;
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score += 15;

        // Bonus for length
        if (password.length >= 10) score += 15;

        if (score < 30) {
            return { class: 'weak', text: 'Schwach' };
        } else if (score < 60) {
            return { class: 'fair', text: 'Ausreichend' };
        } else if (score < 90) {
            return { class: 'good', text: 'Gut' };
        } else {
            return { class: 'strong', text: 'Stark' };
        }
    }

    /**
     * Initialize user navbar functionality
     */
    function initUserNavbar() {
        initUserInfo();
        setupNavbarEvents();
    }

    /**
     * Initialize user info in navbar
     */
    function initUserInfo() {
        const accountButton = $('#schilcherAccountButton');
        if (!accountButton.length) return;

        // Check if user is logged in using WordPress body class
        const isLoggedIn = $('body').hasClass('logged-in');
        
        if (isLoggedIn) {
            accountButton.addClass('schilcher-logged-in');
            
            // Get user details via AJAX
            $.post(schilcherAjax.ajaxurl, { action: 'schilcher_get_user_info' })
                .done(function(response) {
                    if (response.logged_in) {
                        updateUserDisplay(response);
                        schilcherUserEmail = response.email || '';
                    }
                })
                .fail(function() {
                    console.log('User info AJAX failed, keeping fallback values');
                });
        } else {
            accountButton.removeClass('schilcher-logged-in');
        }
    }

    /**
     * Update user display in navbar
     */
    function updateUserDisplay(userInfo) {
        const usernameEl = $('#schilcherUsername');
        const iconEl = $('#schilcherUserIcon');

        if (usernameEl.length && userInfo.display_name) {
            usernameEl.text(userInfo.display_name);
        }
        if (iconEl.length && userInfo.first_letter) {
            iconEl.text(userInfo.first_letter);
        }
    }

    /**
     * Setup navbar event handlers
     */
    function setupNavbarEvents() {
        // Close dropdown when clicking outside
        $(document).on('click', function(event) {
            const container = $('.schilcher-account-dropdown');
            if (container.length && !container[0].contains(event.target)) {
                closeSchilcherDropdown();
            }
        });
    }

    /**
     * Check for password reset success parameter and show message
     */
    function initPasswordResetSuccess() {
        const urlParams = new URLSearchParams(window.location.search);
        const passwordResetSuccess = urlParams.get('password_reset_success');
        
        if (passwordResetSuccess === '1') {
            const successMessage = $('#password-reset-success-message');
            if (successMessage.length) {
                successMessage.show();
            }
            
            // Clean up URL parameter
            if (window.history && window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('password_reset_success');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        }
    }

    // =====================================
    // GLOBAL FUNCTIONS FOR NAVBAR
    // =====================================

    /**
     * Toggle dropdown
     */
    window.toggleSchilcherDropdown = function() {
        const dropdown = $('#schilcherDropdownMenu');
        const trigger = $('.schilcher-account-trigger');

        if (dropdown.hasClass('show')) {
            closeSchilcherDropdown();
        } else {
            dropdown.addClass('show');
            trigger.addClass('active');
        }
    };

    /**
     * Close dropdown
     */
    window.closeSchilcherDropdown = function() {
        const dropdown = $('#schilcherDropdownMenu');
        const trigger = $('.schilcher-account-trigger');
        dropdown.removeClass('show');
        trigger.removeClass('active');
    };

    /**
     * Password change function
     */
    window.schilcherPasswordChange = function() {
        window.closeSchilcherDropdown();
        
        // Construct URL with email parameter if available
        let url = '/reset-password';
        if (schilcherUserEmail) {
            url += '?email=' + encodeURIComponent(schilcherUserEmail);
        }
        
        window.location.href = url;
    };

    /**
     * Logout function
     */
    window.schilcherLogoutUser = function() {
        window.closeSchilcherDropdown();

        // Show loading state
        const accountTrigger = $('.schilcher-account-trigger');
        const usernameEl = $('#schilcherUsername');
        const originalText = usernameEl.text();

        usernameEl.text('Abmelden...');
        accountTrigger.css({ opacity: '0.7', pointerEvents: 'none' });

        // Send logout request
        $.post(schilcherAjax.ajaxurl, {
            action: 'schilcher_logout',
            nonce: schilcherAjax.logout_nonce
        })
        .done(function(response) {
            if (response.success) {
                showSchilcherMessage('Erfolgreich abgemeldet. Sie werden weitergeleitet...', 'success');
                setTimeout(function() {
                    window.location.href = response.redirect_url;
                }, 1500);
            } else {
                showSchilcherMessage('Fehler beim Abmelden.', 'error');
                console.error('Logout failed:', response.message);
            }
        })
        .fail(function() {
            showSchilcherMessage('Fehler beim Abmelden.', 'error');
            setTimeout(function() {
                window.location.href = 'https://www.schilcher-kaese.de';
            }, 2000);
        })
        .always(function() {
            // Reset button state
            usernameEl.text(originalText);
            accountTrigger.css({ opacity: '1', pointerEvents: 'auto' });
        });
    };

    /**
     * Show messages
     */
    window.showSchilcherMessage = function(message, type) {
        type = type || 'info';
        
        // Remove existing message
        $('#schilcher-message').remove();

        // Create message element
        const messageEl = $('<div id="schilcher-message">' + message + '</div>');
        
        // Base styles
        messageEl.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '12px 20px',
            borderRadius: '8px',
            fontFamily: '"Open Sans", Arial, sans-serif',
            fontSize: '14px',
            fontWeight: '600',
            color: 'white',
            zIndex: '99999',
            maxWidth: '300px',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease, opacity 0.3s ease',
            opacity: '0'
        });

        // Type-specific styles
        switch(type) {
            case 'success':
                messageEl.css('backgroundColor', '#4CAF50');
                break;
            case 'error':
                messageEl.css('backgroundColor', '#f44336');
                break;
            case 'loading':
                messageEl.css('backgroundColor', '#49391b');
                messageEl.html('<span style="display: inline-block; width: 12px; height: 12px; border: 2px solid #fef8e7; border-top: 2px solid transparent; border-radius: 50%; animation: schilcher-spin 1s linear infinite; margin-right: 8px;"></span>' + message);
                break;
            default:
                messageEl.css('backgroundColor', '#2196F3');
        }

        // Add to page
        $('body').append(messageEl);

        // Add CSS for spinner animation if not exists
        if (type === 'loading' && !$('#schilcher-spinner-style').length) {
            $('<style id="schilcher-spinner-style">@keyframes schilcher-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
        }

        // Animate in
        setTimeout(function() {
            messageEl.css({ transform: 'translateX(0)', opacity: '1' });
        }, 100);

        // Auto-remove after delay (except for loading messages)
        if (type !== 'loading') {
            setTimeout(function() {
                messageEl.css({ transform: 'translateX(100%)', opacity: '0' });
                setTimeout(function() {
                    messageEl.remove();
                }, 300);
            }, 3000);
        }

        return messageEl;
    };

})(jQuery);
