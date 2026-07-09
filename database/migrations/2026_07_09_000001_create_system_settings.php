<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise Migration 1 — Dynamic System Settings
 *
 * Eliminates hardcoded configuration values from source code.
 * All application settings, feature flags, business rules, and
 * operational parameters are stored here and managed via the
 * Admin Settings panel without requiring code deployments.
 *
 * Structure:
 *   group  — logical namespace (general, email, invoice, enrollment, ...)
 *   key    — unique identifier within the group
 *   value  — stored as text; cast at runtime using data_type
 *   is_public — whether the /api/v1/settings public endpoint exposes this
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();

            // Logical grouping for admin UI panels and scoped lookups
            $table->string('group', 60)->index()
                ->comment('Logical namespace: general | email | invoice | enrollment | feature_flags | payment | security');

            // Unique key within the group — used in code as config('setting.key')
            $table->string('key', 100)
                ->comment('Unique key within the group. Queried as: group + key.');

            // All values stored as text; cast using data_type at runtime
            $table->text('value')->nullable()
                ->comment('Stored value. Cast at runtime via data_type field.');

            // Controls how the value is cast when read
            $table->string('data_type', 20)->default('string')
                ->comment('Runtime cast: string | integer | boolean | decimal | json | text | date');

            // Admin UI display metadata
            $table->string('label', 180)->nullable()
                ->comment('Human-readable label shown in admin settings panel.');

            $table->text('description')->nullable()
                ->comment('Help text / explanation shown below the input field in admin panel.');

            // Access control
            $table->boolean('is_public')->default(false)
                ->comment('If true, this setting is exposed to authenticated frontend via /api/settings.');

            $table->boolean('is_encrypted')->default(false)
                ->comment('If true, the value is stored encrypted (API keys, SMTP passwords, etc.).');

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Composite unique — prevents duplicate keys within the same group
            $table->unique(['group', 'key'], 'system_settings_group_key_unique');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // ── Seed all application configuration values ────────────────────────
        $now = now();
        $rows = [];

        $add = function (
            string $group,
            string $key,
            ?string $value,
            string $dataType,
            string $label,
            string $description = '',
            bool $isPublic = false
        ) use (&$rows, $now) {
            $rows[] = compact('group', 'key', 'value', 'dataType', 'label', 'description', 'isPublic')
                + ['data_type' => $dataType, 'is_public' => $isPublic, 'created_at' => $now, 'updated_at' => $now];
        };

        // ── General ──────────────────────────────────────────────────────────
        $add('general', 'site_name',         'KTM Constancy',        'string',  'Site Name',              'Displayed in page titles and emails.',             true);
        $add('general', 'site_tagline',       'Your Learning Partner','string',  'Site Tagline',           'Short description shown on homepage.',             true);
        $add('general', 'site_url',           'https://ktmconstancy.com', 'string', 'Site URL',            'Public-facing URL without trailing slash.',        true);
        $add('general', 'admin_email',        '',                     'string',  'Admin Email',            'Receives system alerts and admin notifications.');
        $add('general', 'support_phone',      '',                     'string',  'Support Phone',          'Displayed on contact pages.',                      true);
        $add('general', 'support_email',      '',                     'string',  'Support Email',          'Displayed on contact pages.',                      true);
        $add('general', 'address',            '',                     'text',    'Office Address',         'Physical address shown in footer and emails.',      true);
        $add('general', 'currency_code',      'NPR',                  'string',  'Currency Code',          'ISO 4217 code used in invoice amounts.',            true);
        $add('general', 'currency_symbol',    'Rs.',                  'string',  'Currency Symbol',        'Symbol shown next to monetary values.',            true);
        $add('general', 'timezone',           'Asia/Kathmandu',       'string',  'Default Timezone',       'IANA timezone for date/time display and jobs.');
        $add('general', 'date_format',        'd M Y',                'string',  'Date Display Format',    'PHP date format for UI display.',                  true);
        $add('general', 'items_per_page',     '20',                   'integer', 'Default Items Per Page', 'Default pagination size for admin lists.');

        // ── Invoice ───────────────────────────────────────────────────────────
        $add('invoice', 'number_prefix',      'INV',                  'string',  'Invoice Number Prefix',  'Prepended to invoice number e.g. INV-1001.',        true);
        $add('invoice', 'starting_number',    '1000',                 'integer', 'Invoice Starting Number', 'First invoice will be prefix + this number.');
        $add('invoice', 'due_days',           '7',                    'integer', 'Payment Due Days',        'Days after invoice date that payment is due.');
        $add('invoice', 'tax_rate',           '0',                    'decimal', 'Default Tax Rate (%)',   '0 = no tax. Applied to invoice subtotal.');
        $add('invoice', 'default_payment_method', 'bank_qr',          'string',  'Default Payment Method', 'Used when creating invoices if none specified.');
        $add('invoice', 'bank_name',          '',                     'string',  'Bank Name',              'Shown on invoice payment instructions.',            true);
        $add('invoice', 'bank_account_number','',                     'string',  'Bank Account Number',    'Shown on invoice payment instructions.',            true);
        $add('invoice', 'bank_account_name',  '',                     'string',  'Bank Account Name',      'Shown on invoice payment instructions.',            true);
        $add('invoice', 'qr_code_image_path', '',                     'string',  'QR Code Image Path',     'Relative path to payment QR code image.',          true);
        $add('invoice', 'footer_notes',       '',                     'text',    'Invoice Footer Notes',   'Printed at the bottom of every invoice PDF.',       true);

        // ── Enrollment ────────────────────────────────────────────────────────
        $add('enrollment', 'max_per_batch',              '',          'integer', 'Max Enrollments Per Batch',    'Leave blank to use batch.max_size. Hard cap override.');
        $add('enrollment', 'allow_waitlist',             'false',     'boolean', 'Allow Waitlist',               'Enable waitlist when batch is full.');
        $add('enrollment', 'demo_limit_per_user',        '1',         'integer', 'Max Pending Demo Requests',    'Max simultaneous pending demo requests per user.');
        $add('enrollment', 'duplicate_course_check',     'true',      'boolean', 'Block Duplicate Course Enrollment', 'Prevent enrolling in two batches of the same course.');

        // ── Email ────────────────────────────────────────────────────────────
        $add('email', 'from_name',            'KTM Constancy',        'string',  'Email From Name',         'Sender name shown in all system emails.');
        $add('email', 'from_address',         'noreply@ktmconstancy.com','string','Email From Address',     'Sender address for all system emails.');
        $add('email', 'smtp_host',            '',                     'string',  'SMTP Host',               'Mail server hostname.');
        $add('email', 'smtp_port',            '587',                  'integer', 'SMTP Port',               '465 (SSL) or 587 (TLS).');
        $add('email', 'smtp_encryption',      'tls',                  'string',  'SMTP Encryption',         'ssl | tls | null');
        $add('email', 'smtp_username',        '',                     'string',  'SMTP Username',           'Authentication username for SMTP.');
        $add('email', 'smtp_password',        '',                     'string',  'SMTP Password',           'Authentication password (stored encrypted).', false);

        // ── Feature Flags ─────────────────────────────────────────────────────
        $add('feature_flags', 'google_login_enabled',       'true',   'boolean', 'Google Login',            'Enable Google OAuth login button.', true);
        $add('feature_flags', 'demo_request_enabled',       'true',   'boolean', 'Demo Requests',           'Show demo request form to students.', true);
        $add('feature_flags', 'mock_test_enabled',          'true',   'boolean', 'Mock Tests',              'Enable mock test subscription module.', true);
        $add('feature_flags', 'exam_booking_enabled',       'true',   'boolean', 'Exam Booking',            'Enable exam booking module.', true);
        $add('feature_flags', 'offers_enabled',             'true',   'boolean', 'Offers / Promotions',     'Show promotional offer banners.', true);
        $add('feature_flags', 'testimonials_enabled',       'true',   'boolean', 'Testimonials',            'Display testimonials section on homepage.', true);
        $add('feature_flags', 'contact_form_enabled',       'true',   'boolean', 'Contact Form',            'Enable the public contact form.', true);
        $add('feature_flags', 'certificate_module_enabled', 'false',  'boolean', 'Certificate Module',      'Enable certificate generation (future module).', true);

        // ── Security ──────────────────────────────────────────────────────────
        $add('security', 'session_lifetime_minutes',   '120',        'integer', 'Session Lifetime (min)',   'How long before an idle session expires.');
        $add('security', 'max_login_attempts',         '5',          'integer', 'Max Login Attempts',      'Attempts before account is temporarily locked.');
        $add('security', 'lockout_duration_minutes',   '15',         'integer', 'Lockout Duration (min)',   'Duration of account lock after max attempts.');
        $add('security', 'password_min_length',        '8',          'integer', 'Minimum Password Length', 'Minimum character count for new passwords.');

        DB::table('system_settings')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
