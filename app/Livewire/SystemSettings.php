<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;


class SystemSettings extends Component
{
    use WithFileUploads, Toast;

    // Tab management
    public $activeTab = 'general';

    // General Settings
    public $appName = '';
    public $appUrl = '';
    public $appTimezone = '';
    public $appLocale = '';
    public $defaultCurrency = '';
    public $currencySymbol = '';
    public $dateFormat = '';
    public $timeFormat = '';

    // Email Settings
    public $emailEnabled = true;
    public $mailDriver = '';
    public $mailHost = '';
    public $mailPort = '';
    public $mailUsername = '';
    public $mailPassword = '';
    public $mailEncryption = '';
    public $mailFromAddress = '';
    public $mailFromName = '';

    // Invoice Settings
    public $invoicePrefix = '';
    public $invoiceDigits = 4;
    public $invoiceTerms = '';
    public $invoiceNotes = '';
    public $defaultTaxRate = 18;
    public $enableGst = true;

    // Security Settings
    public $sessionLifetime = 120;
    public $passwordMinLength = 8;
    public $requireStrongPassword = true;
    public $enableTwoFactor = false;
    public $maxLoginAttempts = 5;
    public $lockoutDuration = 15;

    // Backup Settings
    public $autoBackupEnabled = true;
    public $backupFrequency = 'daily';
    public $backupRetention = 30;

    // Notification Settings
    public $enableNotifications = true;
    public $emailNotifications = true;
    public $lowStockThreshold = 10;

    // System Info
    public $systemInfo = [];


    // Invoice Template Settings
    public $selectedTemplate = 'invoice';
    public $previewTemplate = '';

    // Available templates
    public $availableTemplates = [
        'invoice' => [
            'name' => 'Default Invoice',
            'description' => 'Your current GST invoice template with detailed tax breakdown',
            'preview' => 'pdfs.invoice-preview',
            'pdf_template' => 'pdfs.invoice'
        ],
        'modern-clean' => [
            'name' => 'Modern Clean',
            'description' => 'Clean and professional design with blue accents',
            'preview' => 'pdfs.modern-clean-preview',
            'pdf_template' => 'pdfs.modern-clean'
        ],
        'professional-dark' => [
            'name' => 'Professional Dark',
            'description' => 'Dark theme with yellow highlights for premium look',
            'preview' => 'pdfs.professional-dark-preview',
            'pdf_template' => 'pdfs.professional-dark'
        ],
        'minimalist' => [
            'name' => 'Minimalist',
            'description' => 'Clean and simple design with minimal styling',
            'preview' => 'pdfs.minimalist-preview',
            'pdf_template' => 'pdfs.minimalist'
        ],
        'classic' => [
            'name' => 'Classic Business',
            'description' => 'Traditional business invoice with formal styling',
            'preview' => 'pdfs.classic-preview',
            'pdf_template' => 'pdfs.classic'
        ]
    ];


    public function mount()
    {
        $this->loadSettings();
        $this->loadSystemInfo();

        // Fix: Ensure selectedTemplate has a valid default value
        $this->selectedTemplate = SystemSetting::get('invoice_template', 'invoice', 'invoice');

        // Fix: Ensure selectedTemplate exists in availableTemplates
        if (!isset($this->availableTemplates[$this->selectedTemplate])) {
            $this->selectedTemplate = 'invoice'; // fallback to default
        }

        // Fix: Initialize previewTemplate as empty but handle it properly
        $this->previewTemplate = '';
    }

    public function saveTemplateSettings()
    {
        try {
            SystemSetting::set('invoice_template', $this->selectedTemplate, 'invoice', 'string');

            $this->success('Template Settings Saved!', 'Invoice template has been updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error saving template settings: ' . $e->getMessage());
            $this->error('Save Failed!', 'Error: ' . $e->getMessage());
        }
    }

    // Add method for template preview
    public function previewInvoiceTemplate($templateKey)
    {
        // Fix: Validate template key exists before setting
        if (isset($this->availableTemplates[$templateKey])) {
            $this->previewTemplate = $templateKey;
        } else {
            $this->previewTemplate = '';
        }
    }

    // Add method to get preview data
    public function getPreviewData()
    {
        $templateToUse = $this->previewTemplate ?: $this->selectedTemplate;

        if (empty($templateToUse) || !isset($this->availableTemplates[$templateToUse])) {
            return [];
        }

        $company = \App\Models\CompanyProfile::current();

        // Create dummy invoice object that matches your template structure
        $invoice = (object) [
            'invoice_number' => $this->invoicePrefix . '-' . str_pad(1234, $this->invoiceDigits, '0', STR_PAD_LEFT),
            'is_gst_invoice' => $this->enableGst,
            'invoice_date' => now(),
            'due_date' => now()->addDays(15),
            'place_of_supply' => $company->state ?? 'Maharashtra',
            'display_client_name' => 'Sample Client Pvt Ltd',
            'client_name' => 'Sample Client Pvt Ltd',
            'client_address' => '456 Client Building, Sample City, Sample State - 400001',
            'client_phone' => '+91-9876543210',
            'client_gstin' => '27BBBBB0000B1Z3',
            'gst_type' => 'cgst_sgst',
            'subtotal' => 2005.00,
            'discount_amount' => 0.00,
            'cgst_amount' => 180.45,
            'sgst_amount' => 180.45,
            'igst_amount' => 0.00,
            'total_amount' => 2365.90,
            'created_at' => now(),
            'notes' => $this->invoiceNotes ?: 'Thank you for your business!',
            'terms_conditions' => $this->invoiceTerms ?: 'Payment due within 15 days',
            'items' => collect([
                (object) [
                    'product_name' => 'Sample Product 1',
                    'display_unit' => 'PCS',
                    'quantity' => 10.00,
                    'unit_price' => 100.50,
                    'taxable_amount' => 1005.00,
                    'cgst_rate' => 9,
                    'cgst_amount' => 90.45,
                    'sgst_rate' => 9,
                    'sgst_amount' => 90.45,
                    'igst_rate' => 0,
                    'igst_amount' => 0.00,
                    'total_amount' => 1185.90
                ],
                (object) [
                    'product_name' => 'Sample Service A',
                    'display_unit' => 'HRS',
                    'quantity' => 5.00,
                    'unit_price' => 200.00,
                    'taxable_amount' => 1000.00,
                    'cgst_rate' => 9,
                    'cgst_amount' => 90.00,
                    'sgst_rate' => 9,
                    'sgst_amount' => 90.00,
                    'igst_rate' => 0,
                    'igst_amount' => 0.00,
                    'total_amount' => 1180.00
                ]
            ])
        ];

        // Create company array that matches your template
        $companyData = [
            'name' => $company->name ?? 'Your Company Name',
            'address' => $company->address ?? '123 Business Street',
            'city' => $company->city ?? 'Your City',
            'state' => $company->state ?? 'Your State',
            'pincode' => $company->postal_code ?? '400001',
            'phone' => $company->phone ?? '+91-9876543210',
            'gstin' => $company->gstin ?? '27AAAAA0000A1Z2'
        ];

        return [
            'invoice' => $invoice,
            'company' => $companyData,
            'invoicePrefix' => $this->invoicePrefix,
            'invoiceDigits' => $this->invoiceDigits,
            'currencySymbol' => $this->currencySymbol,
            'dateFormat' => $this->dateFormat,
            'defaultTaxRate' => $this->defaultTaxRate,
            'enableGst' => $this->enableGst,
            'invoiceTerms' => $this->invoiceTerms,
            'invoiceNotes' => $this->invoiceNotes,
        ];
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    private function loadSettings()
    {
        // General Settings
        $this->appName = SystemSetting::get('app_name', config('app.name'), 'general');
        $this->appUrl = SystemSetting::get('app_url', config('app.url'), 'general');
        $this->appTimezone = SystemSetting::get('app_timezone', config('app.timezone'), 'general');
        $this->appLocale = SystemSetting::get('app_locale', config('app.locale'), 'general');
        $this->defaultCurrency = SystemSetting::get('default_currency', 'INR', 'general');
        $this->currencySymbol = SystemSetting::get('currency_symbol', '₹', 'general');
        $this->dateFormat = SystemSetting::get('date_format', 'd/m/Y', 'general');
        $this->timeFormat = SystemSetting::get('time_format', 'H:i', 'general');

        // Email Settings
        $this->emailEnabled = SystemSetting::get('email_enabled', true, 'email');
        $this->mailDriver = SystemSetting::get('mail_driver', 'smtp', 'email');
        $this->mailHost = SystemSetting::get('mail_host', '', 'email');
        $this->mailPort = SystemSetting::get('mail_port', '587', 'email');
        $this->mailUsername = SystemSetting::get('mail_username', '', 'email');
        $this->mailPassword = SystemSetting::get('mail_password', '', 'email');
        $this->mailEncryption = SystemSetting::get('mail_encryption', 'tls', 'email');
        $this->mailFromAddress = SystemSetting::get('mail_from_address', '', 'email');
        $this->mailFromName = SystemSetting::get('mail_from_name', '', 'email');

        // Invoice Settings
        $this->invoicePrefix = SystemSetting::get('invoice_prefix', 'INV', 'invoice');
        $this->invoiceDigits = SystemSetting::get('invoice_digits', 4, 'invoice');
        $this->invoiceTerms = SystemSetting::get('invoice_terms', '', 'invoice');
        $this->invoiceNotes = SystemSetting::get('invoice_notes', '', 'invoice');
        $this->defaultTaxRate = SystemSetting::get('default_tax_rate', 18, 'invoice');
        $this->enableGst = SystemSetting::get('enable_gst', true, 'invoice');

        // Security Settings
        $this->sessionLifetime = SystemSetting::get('session_lifetime', 120, 'security');
        $this->passwordMinLength = SystemSetting::get('password_min_length', 8, 'security');
        $this->requireStrongPassword = SystemSetting::get('require_strong_password', true, 'security');
        $this->enableTwoFactor = SystemSetting::get('enable_two_factor', false, 'security');
        $this->maxLoginAttempts = SystemSetting::get('max_login_attempts', 5, 'security');
        $this->lockoutDuration = SystemSetting::get('lockout_duration', 15, 'security');

        // Backup Settings
        $this->autoBackupEnabled = SystemSetting::get('auto_backup_enabled', true, 'backup');
        $this->backupFrequency = SystemSetting::get('backup_frequency', 'daily', 'backup');
        $this->backupRetention = SystemSetting::get('backup_retention', 30, 'backup');

        // Notification Settings
        $this->enableNotifications = SystemSetting::get('enable_notifications', true, 'notification');
        $this->emailNotifications = SystemSetting::get('email_notifications', true, 'notification');
        $this->lowStockThreshold = SystemSetting::get('low_stock_threshold', 10, 'notification');
    }

    public function saveGeneralSettings()
    {
        try {
            SystemSetting::set('app_name', $this->appName, 'general', 'string');
            SystemSetting::set('app_url', $this->appUrl, 'general', 'string');
            SystemSetting::set('app_timezone', $this->appTimezone, 'general', 'string');
            SystemSetting::set('app_locale', $this->appLocale, 'general', 'string');
            SystemSetting::set('default_currency', $this->defaultCurrency, 'general', 'string');
            SystemSetting::set('currency_symbol', $this->currencySymbol, 'general', 'string');
            SystemSetting::set('date_format', $this->dateFormat, 'general', 'string');
            SystemSetting::set('time_format', $this->timeFormat, 'general', 'string');

            $this->success('General Settings Saved!', 'Settings have been updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error saving general settings: ' . $e->getMessage());
            $this->error('Save Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function saveEmailSettings()
    {
        try {
            SystemSetting::set('email_enabled', $this->emailEnabled, 'email', 'boolean');
            SystemSetting::set('mail_driver', $this->mailDriver, 'email', 'string');
            SystemSetting::set('mail_host', $this->mailHost, 'email', 'string');
            SystemSetting::set('mail_port', $this->mailPort, 'email', 'string');
            SystemSetting::set('mail_username', $this->mailUsername, 'email', 'string');
            SystemSetting::set('mail_password', $this->mailPassword, 'email', 'string');
            SystemSetting::set('mail_encryption', $this->mailEncryption, 'email', 'string');
            SystemSetting::set('mail_from_address', $this->mailFromAddress, 'email', 'string');
            SystemSetting::set('mail_from_name', $this->mailFromName, 'email', 'string');

            $this->success('Email Settings Saved!', 'Email configuration has been updated.');
        } catch (\Exception $e) {
            Log::error('Error saving email settings: ' . $e->getMessage());
            $this->error('Save Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function testEmailConnection()
    {
        try {
            // Update mail config temporarily
            Config::set('mail.driver', $this->mailDriver);
            Config::set('mail.host', $this->mailHost);
            Config::set('mail.port', $this->mailPort);
            Config::set('mail.username', $this->mailUsername);
            Config::set('mail.password', $this->mailPassword);
            Config::set('mail.encryption', $this->mailEncryption);

            // Send test email
            Mail::raw('This is a test email from your ERP system.', function ($message) {
                $message->to($this->mailFromAddress)
                    ->subject('Test Email - ERP System')
                    ->from($this->mailFromAddress, $this->mailFromName);
            });

            $this->success('Test Email Sent!', 'Email configuration is working correctly.');
        } catch (\Exception $e) {
            Log::error('Email test failed: ' . $e->getMessage());
            $this->error('Email Test Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function saveInvoiceSettings()
    {
        try {
            SystemSetting::set('invoice_prefix', $this->invoicePrefix, 'invoice', 'string');
            SystemSetting::set('invoice_digits', $this->invoiceDigits, 'invoice', 'integer');
            SystemSetting::set('invoice_terms', $this->invoiceTerms, 'invoice', 'string');
            SystemSetting::set('invoice_notes', $this->invoiceNotes, 'invoice', 'string');
            SystemSetting::set('default_tax_rate', $this->defaultTaxRate, 'invoice', 'float');
            SystemSetting::set('enable_gst', $this->enableGst, 'invoice', 'boolean');

            // Save the selected template
            SystemSetting::set('invoice_template', $this->selectedTemplate, 'invoice', 'string');

            $this->success('Invoice Settings Saved!', 'Invoice configuration has been updated.');
        } catch (\Exception $e) {
            Log::error('Error saving invoice settings: ' . $e->getMessage());
            $this->error('Save Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function saveSecuritySettings()
    {
        try {
            SystemSetting::set('session_lifetime', $this->sessionLifetime, 'security', 'integer');
            SystemSetting::set('password_min_length', $this->passwordMinLength, 'security', 'integer');
            SystemSetting::set('require_strong_password', $this->requireStrongPassword, 'security', 'boolean');
            SystemSetting::set('enable_two_factor', $this->enableTwoFactor, 'security', 'boolean');
            SystemSetting::set('max_login_attempts', $this->maxLoginAttempts, 'security', 'integer');
            SystemSetting::set('lockout_duration', $this->lockoutDuration, 'security', 'integer');

            $this->success('Security Settings Saved!', 'Security configuration has been updated.');
        } catch (\Exception $e) {
            Log::error('Error saving security settings: ' . $e->getMessage());
            $this->error('Save Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function saveBackupSettings()
    {
        try {
            SystemSetting::set('auto_backup_enabled', $this->autoBackupEnabled, 'backup', 'boolean');
            SystemSetting::set('backup_frequency', $this->backupFrequency, 'backup', 'string');
            SystemSetting::set('backup_retention', $this->backupRetention, 'backup', 'integer');

            $this->success('Backup Settings Saved!', 'Backup configuration has been updated.');
        } catch (\Exception $e) {
            Log::error('Error saving backup settings: ' . $e->getMessage());
            $this->error('Save Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function saveNotificationSettings()
    {
        try {
            SystemSetting::set('enable_notifications', $this->enableNotifications, 'notification', 'boolean');
            SystemSetting::set('email_notifications', $this->emailNotifications, 'notification', 'boolean');
            SystemSetting::set('low_stock_threshold', $this->lowStockThreshold, 'notification', 'integer');

            $this->success('Notification Settings Saved!', 'Notification configuration has been updated.');
        } catch (\Exception $e) {
            Log::error('Error saving notification settings: ' . $e->getMessage());
            $this->error('Save Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            $this->success('Cache Cleared!', 'All application caches have been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Error clearing cache: ' . $e->getMessage());
            $this->error('Cache Clear Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function optimizeSystem()
    {
        try {
            Artisan::call('optimize');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            $this->success('System Optimized!', 'Application has been optimized for better performance.');
        } catch (\Exception $e) {
            Log::error('Error optimizing system: ' . $e->getMessage());
            $this->error('Optimization Failed!', 'Error: ' . $e->getMessage());
        }
    }

    private function loadSystemInfo()
    {
        $this->systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_version' => DB::select('SELECT VERSION() as version')[0]->version ?? 'Unknown',
            'timezone' => date_default_timezone_get(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'disk_free_space' => $this->formatBytes(disk_free_space('/')),
            'disk_total_space' => $this->formatBytes(disk_total_space('/')),
        ];
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }

    public function render()
    {
        Log::error('Full backup creation failed: ');
        return view('livewire.system-settings');
    }
}
