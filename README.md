# Schilcher User Service WordPress Plugin

A comprehensive user management system for Schilcher Käse with B2B registration, login, password reset, and account management features.

## Features

- **User Login System**: AJAX-powered login with proper error handling
- **B2B Registration**: Complete B2B dealer registration with admin approval workflow
- **Password Reset**: Secure password reset flow with custom email templates
- **User Account Management**: Dropdown navbar integration with user info
- **Email System**: Professional branded email templates for all communications
- **Admin Management**: Admin interface for approving/rejecting B2B users
- **WordPress Integration**: Proper WordPress plugin structure with hooks and filters

## Installation

1. Upload the `schilcher-user-service` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create necessary database entries and set up required functionality

## Usage

### Shortcodes

The plugin provides several shortcodes to display forms and components:

#### Login Form
```
[schilcher_login_form]
```
Displays the complete login form with AJAX functionality.

#### Registration Form
```
[schilcher_registration_form]
```
Displays the B2B dealer registration form with all required fields.

#### Password Reset Request Form
```
[schilcher_password_reset_form]
```
Displays the password reset request form.

#### Password Reset Complete Form
```
[schilcher_password_reset_complete_form]
```
Displays the new password form (used in reset flow).

#### User Navbar Menu
```
[schilcher_user_navbar]
```
Displays the user account dropdown for navigation areas.

#### Generate Nonce
```
[schilcher_nonce action="action_name"]
```
Generates a WordPress nonce for forms (action parameter is optional).

### Page Setup

Create the following pages and add the respective shortcodes:

1. **Login Page** (`/intern`): `[schilcher_login_form]`
2. **Registration Page** (`/register`): `[schilcher_registration_form]`
3. **Password Reset Request** (`/reset-password`): `[schilcher_password_reset_form]`
4. **Password Reset Complete** (`/new-password`): `[schilcher_password_reset_complete_form]`

Add the navbar shortcode to your theme's navigation area:
```php
<?php echo do_shortcode('[schilcher_user_navbar]'); ?>
```

## File Structure

```
schilcher-user-service/
├── schilcher-user-service.php          # Main plugin file
├── includes/                           # Core classes
│   ├── class-schilcher-email-manager.php
│   ├── class-schilcher-user-manager.php
│   ├── class-schilcher-admin-manager.php
│   ├── class-schilcher-validator.php
│   └── class-schilcher-shortcodes.php
├── assets/                            # CSS and JavaScript
│   ├── css/
│   │   └── schilcher-user-service.css
│   └── js/
│       └── schilcher-user-service.js
├── templates/                         # Email templates
│   └── email-template.html
└── README.md
```

## Components

### Email Manager (`class-schilcher-email-manager.php`)
- Handles all email functionality
- Professional HTML email templates
- Registration confirmation emails
- Approval/rejection notifications
- Admin notifications

### User Manager (`class-schilcher-user-manager.php`)
- B2B user creation and management
- User metadata storage
- Integration with B2BKing plugin (if available)
- User approval status management

### Admin Manager (`class-schilcher-admin-manager.php`)
- Admin interface customizations
- User approval/rejection actions
- B2B user columns in admin user list
- Export functionality for B2B users

### Validator (`class-schilcher-validator.php`)
- Form validation for registration
- Data sanitization
- Email validation
- Required field checking

### Shortcodes (`class-schilcher-shortcodes.php`)
- All shortcode implementations
- HTML generation for forms
- Integration with JavaScript functionality

## Brand Guidelines Compliance

The plugin follows the Schilcher Käse Brand Style Guide:

### Colors
- **Primary Teal**: `#fef8e7`
- **Primary Text Brown**: `#49391b`
- **Accent Yellow**: `#fdfdfc`
- **Warm Brown**: `#876c4b`
- **Light Brown**: `#bda77f`
- **Dark Brown**: `#352507`
- **Cream**: `#f5f0e6`

### Typography
- **Font Family**: "Open Sans", sans-serif
- **Headings**: Bold (700) weight
- **Body Text**: Regular (400) weight

### Design Elements
- **Border Radius**: 8px (standard), 4px (small), 16px (large)
- **Shadows**: Professional drop shadows following brand guidelines
- **Layout**: Clean, organized with generous white space

## AJAX Functionality

All forms use AJAX for submission to provide a seamless user experience:

- **Login**: Real-time login with proper error handling
- **Registration**: Form submission without page reload
- **Password Reset**: Secure password reset flow
- **User Info**: Dynamic user information loading in navbar

## Security Features

- WordPress nonces for all form submissions
- Proper data sanitization and validation
- CSRF protection
- SQL injection prevention through WordPress APIs
- XSS protection

## B2B Integration

The plugin includes full B2B functionality:

- **Registration Flow**: Complete dealer registration with approval workflow
- **Admin Management**: Easy approval/rejection of B2B users
- **Email Notifications**: Automated emails for all registration stages
- **B2BKing Compatibility**: Seamless integration with B2BKing plugin

## Email System

Professional email templates following brand guidelines:

- **HTML Templates**: Responsive design with brand colors
- **Placeholder System**: Dynamic content insertion
- **Multiple Email Types**: Registration, approval, rejection, notifications
- **Email Client Compatibility**: Works with all major email clients

## Admin Features

- **User Management**: Enhanced user list with B2B status
- **Approval Actions**: One-click approval/rejection
- **Export Functionality**: CSV export of B2B users
- **Notifications**: Admin alerts for new registrations

## Hooks and Filters

The plugin integrates properly with WordPress:

- **Action Hooks**: For initialization and functionality
- **Filter Hooks**: For customization and extension
- **AJAX Handlers**: For all form submissions
- **Template Redirects**: For custom login/registration flow

## Customization

### CSS Customization
The main CSS file is organized with clear sections for easy customization:
- Brand variables at the top
- Component-specific styles
- Responsive design rules

### Email Template Customization
Email templates use placeholder system for easy customization:
- `{{EMAIL_SUBJECT}}` - Email subject
- `{{EMAIL_TITLE}}` - Main heading
- `{{GREETING}}` - Greeting text
- `{{MAIN_CONTENT}}` - Email body
- `{{CLOSING_TEXT}}` - Closing text

### PHP Customization
All classes use singleton pattern and proper WordPress hooks for easy extension.

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **JavaScript**: Modern browser support for ES6+

## Optional Integrations

- **B2BKing Plugin**: Enhanced B2B functionality
- **WooCommerce**: E-commerce integration for user redirects

## Support

This plugin was developed specifically for Schilcher Käse following their brand guidelines and business requirements. All functionality has been thoroughly tested and optimized for their use case.

## Version History

- **1.0.0**: Initial release with complete user management system

## License

GPL v2 or later

## Debug Functionality

### User Debug Page

A debug page is available for troubleshooting user data issues:

**File:** `debug-user.php`

**Usage:**
```
/wp-content/plugins/schilcher-user-service/debug-user.php?user_id=123
```

**Security:**
- Requires administrator privileges OR WP_DEBUG mode enabled
- Alternative access with token: `?user_id=123&token=schilcher_debug_2025`

**Output:**
- Complete user data in JSON format
- All user metadata categorized by plugin/source
- B2B specific data analysis
- Plugin status information
- Summary statistics

**Features:**
- All WordPress user data (wp_users table)
- Complete user metadata (wp_usermeta table) 
- Schilcher B2B specific data
- B2BKing integration data
- WooCommerce user data
- Analysis of user roles and capabilities
- Plugin activation status

## Author

Created by Lukas Peschel for Schilcher Käse GmbH
#   s c h i l c h e r - u s e r - s e r v i c e  
 