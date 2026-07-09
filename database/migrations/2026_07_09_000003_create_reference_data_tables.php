<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise Migration 3 — Reference Data Tables
 *
 * Creates the following normalised reference tables:
 *
 *   course_categories  — hierarchical course taxonomy (replaces missing categorization)
 *   countries          — ISO 3166-1 country list (replaces varchar country columns)
 *   payment_methods    — configurable payment channels (replaces hardcoded 'bank_qr')
 *   email_templates    — email content stored in DB (replaces hardcoded Mailable views)
 *
 * Relationships after this migration:
 *   courses.category_id      → course_categories.id   (added in migration 6)
 *   user_details.country_id  → countries.id           (added in migration 6)
 *   invoices.payment_method  → payment_methods.key    (soft reference via key column)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Course Categories (hierarchical) ───────────────────────────────
        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();

            // Self-referential parent for nested categories (max 2 levels recommended)
            $table->unsignedBigInteger('parent_id')->nullable()->index()
                ->comment('NULL = top-level category. Points to course_categories.id for subcategories.');

            $table->string('name', 100);
            $table->string('slug', 120)->unique()
                ->comment('URL-safe identifier. Auto-generated from name if blank.');
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable()
                ->comment('Icon class name or emoji for UI display.');
            $table->string('color', 30)->nullable()
                ->comment('Tailwind color token for category badge (e.g., blue, emerald).');
            $table->string('image_path', 255)->nullable()
                ->comment('Banner or thumbnail image for category pages.');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('course_categories')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['parent_id', 'is_active'], 'course_cats_parent_active_index');
            $table->index(['is_active', 'sort_order'], 'course_cats_active_sort_index');
        });

        // Seed initial categories relevant to the platform
        $now = now();
        $cats = [
            ['name' => 'Language Tests',     'slug' => 'language-tests',     'description' => 'PTE, IELTS, TOEFL and other English proficiency tests.', 'color' => 'blue',    'sort_order' => 1],
            ['name' => 'Academic Courses',   'slug' => 'academic-courses',   'description' => 'Degree and diploma programme preparation.',               'color' => 'purple',  'sort_order' => 2],
            ['name' => 'Visa Preparation',   'slug' => 'visa-preparation',   'description' => 'Visa application support and interview coaching.',        'color' => 'amber',   'sort_order' => 3],
            ['name' => 'Professional Skills','slug' => 'professional-skills', 'description' => 'Workplace communication and professional development.',   'color' => 'emerald', 'sort_order' => 4],
            ['name' => 'Mock Tests',         'slug' => 'mock-tests',         'description' => 'Practice test packages.',                                 'color' => 'teal',    'sort_order' => 5],
        ];
        foreach ($cats as $cat) {
            DB::table('course_categories')->insert($cat + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // ── 2. Countries (ISO 3166-1) ─────────────────────────────────────────
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('iso2', 2)->unique()
                ->comment('ISO 3166-1 alpha-2 code (e.g., NP for Nepal).');
            $table->char('iso3', 3)->unique()
                ->comment('ISO 3166-1 alpha-3 code (e.g., NPL for Nepal).');
            $table->string('name', 100)
                ->comment('English name.');
            $table->string('native_name', 100)->nullable()
                ->comment('Name in the native language.');
            $table->string('phone_code', 10)->nullable()
                ->comment('Dialling prefix including + (e.g., +977).');
            $table->char('currency_code', 3)->nullable()
                ->comment('ISO 4217 currency code (e.g., NPR).');
            $table->string('currency_symbol', 10)->nullable()
                ->comment('Currency symbol (e.g., Rs.).');
            $table->string('flag_emoji', 10)->nullable()
                ->comment('Unicode flag emoji (e.g., 🇳🇵).');
            $table->string('region', 50)->nullable()
                ->comment('Continental region (e.g., Asia, Europe).');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0)
                ->comment('0 = alphabetical; higher = pinned to top.');
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'countries_active_sort_index');
            $table->index('name', 'countries_name_index');
        });

        $this->seedCountries();

        // ── 3. Payment Methods ────────────────────────────────────────────────
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique()
                ->comment('Stored in invoices.payment_method column. Must match existing invoice data.');
            $table->string('label', 100)
                ->comment('Display name shown to students and admin.');
            $table->text('description')->nullable()
                ->comment('Explanation of the payment channel.');
            $table->text('instructions')->nullable()
                ->comment('Step-by-step instructions shown to students after invoice generation.');
            $table->string('icon_path', 255)->nullable()
                ->comment('Path to payment channel logo image.');
            $table->json('config')->nullable()
                ->comment('Gateway-specific configuration (API keys stored encrypted in system_settings).');
            $table->boolean('requires_screenshot')->default(true)
                ->comment('Whether students must upload a payment screenshot for this method.');
            $table->boolean('is_online')->default(false)
                ->comment('If true, payment is processed through an online gateway.');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['is_active', 'sort_order'], 'payment_methods_active_sort_index');
        });

        DB::table('payment_methods')->insert([
            [
                'key'                 => 'bank_qr',
                'label'               => 'Bank Transfer / QR Code',
                'description'         => 'Pay via bank transfer or by scanning our QR code.',
                'instructions'        => "1. Scan the QR code or transfer to the bank account shown.\n2. Upload a screenshot or receipt.\n3. Wait for admin verification (usually within 24 hours).",
                'requires_screenshot' => true,
                'is_online'           => false,
                'is_active'           => true,
                'sort_order'          => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'key'                 => 'esewa',
                'label'               => 'eSewa',
                'description'         => 'Pay using your eSewa digital wallet.',
                'instructions'        => "1. Open your eSewa app.\n2. Send payment to our eSewa ID.\n3. Upload the transaction screenshot.",
                'requires_screenshot' => true,
                'is_online'           => false,
                'is_active'           => true,
                'sort_order'          => 2,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'key'                 => 'khalti',
                'label'               => 'Khalti',
                'description'         => 'Pay using your Khalti digital wallet.',
                'instructions'        => "1. Open Khalti and send to our merchant ID.\n2. Upload the payment confirmation screenshot.",
                'requires_screenshot' => true,
                'is_online'           => false,
                'is_active'           => true,
                'sort_order'          => 3,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'key'                 => 'connectips',
                'label'               => 'ConnectIPS',
                'description'         => 'Pay via ConnectIPS interbank transfer.',
                'instructions'        => "1. Log in to ConnectIPS.\n2. Transfer to our account and upload receipt.",
                'requires_screenshot' => true,
                'is_online'           => false,
                'is_active'           => false,
                'sort_order'          => 4,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'key'                 => 'cash',
                'label'               => 'Cash (In-Office)',
                'description'         => 'Pay in cash at our office.',
                'instructions'        => 'Visit our office during working hours and pay in cash. Collect your receipt.',
                'requires_screenshot' => false,
                'is_online'           => false,
                'is_active'           => false,
                'sort_order'          => 5,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ]);

        // ── 4. Email Templates ────────────────────────────────────────────────
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique()
                ->comment('Machine-readable key used in application code to load this template.');
            $table->string('label', 180)
                ->comment('Admin-facing display name.');
            $table->string('subject', 255)
                ->comment('Email subject line. Supports {{ variable }} placeholders.');
            $table->longText('body_html')
                ->comment('HTML body of the email. Supports {{ variable }} placeholders.');
            $table->text('body_text')->nullable()
                ->comment('Plain-text fallback body. Supports {{ variable }} placeholders.');
            $table->json('available_variables')->nullable()
                ->comment('JSON array of {name, description} objects documenting available template variables.');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index('key');
        });

        $this->seedEmailTemplates($now);
    }

    private function seedCountries(): void
    {
        $now = now();
        $countries = [
            // Nepal first (primary market)
            ['iso2'=>'NP','iso3'=>'NPL','name'=>'Nepal',          'native_name'=>'नेपाल',     'phone_code'=>'+977', 'currency_code'=>'NPR','currency_symbol'=>'Rs.','flag_emoji'=>'🇳🇵','region'=>'Asia','sort_order'=>99],
            // Common destination/source countries for students
            ['iso2'=>'AU','iso3'=>'AUS','name'=>'Australia',       'native_name'=>'Australia', 'phone_code'=>'+61',  'currency_code'=>'AUD','currency_symbol'=>'A$','flag_emoji'=>'🇦🇺','region'=>'Oceania','sort_order'=>10],
            ['iso2'=>'CA','iso3'=>'CAN','name'=>'Canada',          'native_name'=>'Canada',    'phone_code'=>'+1',   'currency_code'=>'CAD','currency_symbol'=>'C$','flag_emoji'=>'🇨🇦','region'=>'Americas','sort_order'=>10],
            ['iso2'=>'GB','iso3'=>'GBR','name'=>'United Kingdom',  'native_name'=>'United Kingdom','phone_code'=>'+44','currency_code'=>'GBP','currency_symbol'=>'£','flag_emoji'=>'🇬🇧','region'=>'Europe','sort_order'=>10],
            ['iso2'=>'US','iso3'=>'USA','name'=>'United States',   'native_name'=>'United States','phone_code'=>'+1','currency_code'=>'USD','currency_symbol'=>'$','flag_emoji'=>'🇺🇸','region'=>'Americas','sort_order'=>10],
            ['iso2'=>'IN','iso3'=>'IND','name'=>'India',           'native_name'=>'भारत',       'phone_code'=>'+91',  'currency_code'=>'INR','currency_symbol'=>'₹','flag_emoji'=>'🇮🇳','region'=>'Asia','sort_order'=>8],
            ['iso2'=>'NZ','iso3'=>'NZL','name'=>'New Zealand',     'native_name'=>'New Zealand','phone_code'=>'+64', 'currency_code'=>'NZD','currency_symbol'=>'NZ$','flag_emoji'=>'🇳🇿','region'=>'Oceania','sort_order'=>9],
            ['iso2'=>'DE','iso3'=>'DEU','name'=>'Germany',         'native_name'=>'Deutschland','phone_code'=>'+49', 'currency_code'=>'EUR','currency_symbol'=>'€','flag_emoji'=>'🇩🇪','region'=>'Europe','sort_order'=>5],
            ['iso2'=>'JP','iso3'=>'JPN','name'=>'Japan',           'native_name'=>'日本',        'phone_code'=>'+81',  'currency_code'=>'JPY','currency_symbol'=>'¥','flag_emoji'=>'🇯🇵','region'=>'Asia','sort_order'=>5],
            ['iso2'=>'SG','iso3'=>'SGP','name'=>'Singapore',       'native_name'=>'Singapore', 'phone_code'=>'+65',  'currency_code'=>'SGD','currency_symbol'=>'S$','flag_emoji'=>'🇸🇬','region'=>'Asia','sort_order'=>5],
            ['iso2'=>'AE','iso3'=>'ARE','name'=>'United Arab Emirates','native_name'=>'الإمارات','phone_code'=>'+971','currency_code'=>'AED','currency_symbol'=>'AED','flag_emoji'=>'🇦🇪','region'=>'Asia','sort_order'=>5],
            ['iso2'=>'QA','iso3'=>'QAT','name'=>'Qatar',           'native_name'=>'قطر',        'phone_code'=>'+974', 'currency_code'=>'QAR','currency_symbol'=>'QR','flag_emoji'=>'🇶🇦','region'=>'Asia','sort_order'=>4],
            ['iso2'=>'KW','iso3'=>'KWT','name'=>'Kuwait',          'native_name'=>'الكويت',     'phone_code'=>'+965', 'currency_code'=>'KWD','currency_symbol'=>'KD','flag_emoji'=>'🇰🇼','region'=>'Asia','sort_order'=>4],
            ['iso2'=>'MY','iso3'=>'MYS','name'=>'Malaysia',        'native_name'=>'Malaysia',  'phone_code'=>'+60',  'currency_code'=>'MYR','currency_symbol'=>'RM','flag_emoji'=>'🇲🇾','region'=>'Asia','sort_order'=>4],
            ['iso2'=>'KR','iso3'=>'KOR','name'=>'South Korea',     'native_name'=>'한국',        'phone_code'=>'+82',  'currency_code'=>'KRW','currency_symbol'=>'₩','flag_emoji'=>'🇰🇷','region'=>'Asia','sort_order'=>4],
            ['iso2'=>'CN','iso3'=>'CHN','name'=>'China',           'native_name'=>'中国',        'phone_code'=>'+86',  'currency_code'=>'CNY','currency_symbol'=>'¥','flag_emoji'=>'🇨🇳','region'=>'Asia','sort_order'=>4],
            ['iso2'=>'FR','iso3'=>'FRA','name'=>'France',          'native_name'=>'France',    'phone_code'=>'+33',  'currency_code'=>'EUR','currency_symbol'=>'€','flag_emoji'=>'🇫🇷','region'=>'Europe','sort_order'=>3],
            ['iso2'=>'IT','iso3'=>'ITA','name'=>'Italy',           'native_name'=>'Italia',    'phone_code'=>'+39',  'currency_code'=>'EUR','currency_symbol'=>'€','flag_emoji'=>'🇮🇹','region'=>'Europe','sort_order'=>3],
            ['iso2'=>'IE','iso3'=>'IRL','name'=>'Ireland',         'native_name'=>'Ireland',   'phone_code'=>'+353', 'currency_code'=>'EUR','currency_symbol'=>'€','flag_emoji'=>'🇮🇪','region'=>'Europe','sort_order'=>3],
            ['iso2'=>'Other','iso3'=>'OTH','name'=>'Other',        'native_name'=>'Other',     'phone_code'=>null,   'currency_code'=>null, 'currency_symbol'=>null,'flag_emoji'=>'🌍','region'=>null,'sort_order'=>1],
        ];

        foreach ($countries as $c) {
            DB::table('countries')->insert($c + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    private function seedEmailTemplates(string $now): void
    {
        $templates = [
            [
                'key'   => 'demo_approved',
                'label' => 'Demo Session Approved',
                'subject' => 'Your Demo Session is Confirmed — {{ site_name }}',
                'body_html' => '<h2>Hello {{ student_name }},</h2><p>Great news! Your demo session has been <strong>approved</strong>.</p><p><strong>Zoom Link:</strong> <a href="{{ zoom_url }}">{{ zoom_url }}</a></p><p><strong>Scheduled At:</strong> {{ scheduled_at }}</p>{% if admin_notes %}<p><strong>Note from Admin:</strong> {{ admin_notes }}</p>{% endif %}<p>If you have any questions, reply to this email or contact us at {{ support_email }}.</p><p>Best regards,<br>{{ site_name }} Team</p>',
                'body_text' => "Hello {{ student_name }},\n\nYour demo session has been approved.\n\nZoom Link: {{ zoom_url }}\nScheduled At: {{ scheduled_at }}\n\n{% if admin_notes %}Note: {{ admin_notes }}\n\n{% endif %}Best regards,\n{{ site_name }} Team",
                'available_variables' => json_encode([
                    ['name' => 'student_name', 'description' => 'Full name of the student'],
                    ['name' => 'zoom_url',     'description' => 'Zoom meeting URL'],
                    ['name' => 'scheduled_at', 'description' => 'Formatted date and time of the session'],
                    ['name' => 'admin_notes',  'description' => 'Optional notes from admin'],
                    ['name' => 'site_name',    'description' => 'Site name from system settings'],
                    ['name' => 'support_email','description' => 'Support email from system settings'],
                ]),
            ],
            [
                'key'   => 'invoice_generated',
                'label' => 'Invoice Generated',
                'subject' => 'Invoice #{{ invoice_number }} — {{ site_name }}',
                'body_html' => '<h2>Hello {{ student_name }},</h2><p>Your invoice has been generated.</p><p><strong>Invoice #:</strong> {{ invoice_number }}<br><strong>Amount:</strong> {{ currency_symbol }}{{ total_npr }}<br><strong>Due Date:</strong> {{ due_date }}</p><p><strong>Payment Instructions:</strong><br>{{ payment_instructions }}</p><p>Best regards,<br>{{ site_name }} Team</p>',
                'body_text' => "Hello {{ student_name }},\n\nInvoice #{{ invoice_number }}\nAmount: {{ currency_symbol }}{{ total_npr }}\nDue: {{ due_date }}\n\nPayment Instructions:\n{{ payment_instructions }}\n\nBest regards,\n{{ site_name }} Team",
                'available_variables' => json_encode([
                    ['name' => 'student_name',        'description' => 'Student full name'],
                    ['name' => 'invoice_number',       'description' => 'Invoice number'],
                    ['name' => 'total_npr',            'description' => 'Total amount'],
                    ['name' => 'due_date',             'description' => 'Payment due date'],
                    ['name' => 'payment_instructions', 'description' => 'Payment method instructions'],
                    ['name' => 'currency_symbol',      'description' => 'Currency symbol'],
                ]),
            ],
            [
                'key'   => 'enrollment_confirmed',
                'label' => 'Enrollment Confirmed',
                'subject' => 'Enrollment Confirmed — {{ course_name }} — {{ site_name }}',
                'body_html' => '<h2>Hello {{ student_name }},</h2><p>Your enrollment in <strong>{{ course_name }}</strong> ({{ batch_type }}) has been confirmed.</p><p><strong>Batch Start:</strong> {{ start_date }}<br><strong>Class Time:</strong> {{ class_time }}</p><p>Best regards,<br>{{ site_name }} Team</p>',
                'body_text' => "Hello {{ student_name }},\n\nYour enrollment in {{ course_name }} ({{ batch_type }}) has been confirmed.\n\nStart: {{ start_date }}\nClass Time: {{ class_time }}\n\nBest regards,\n{{ site_name }} Team",
                'available_variables' => json_encode([
                    ['name' => 'student_name', 'description' => 'Student full name'],
                    ['name' => 'course_name',  'description' => 'Course name'],
                    ['name' => 'batch_type',   'description' => 'Batch type label'],
                    ['name' => 'start_date',   'description' => 'Batch start date'],
                    ['name' => 'class_time',   'description' => 'Class time'],
                ]),
            ],
            [
                'key'   => 'payment_verified',
                'label' => 'Payment Verified',
                'subject' => 'Payment Confirmed — Invoice #{{ invoice_number }} — {{ site_name }}',
                'body_html' => '<h2>Hello {{ student_name }},</h2><p>Your payment for Invoice #<strong>{{ invoice_number }}</strong> has been <strong>confirmed</strong>.</p><p>Thank you for your payment of {{ currency_symbol }}{{ total_npr }}.</p><p>Best regards,<br>{{ site_name }} Team</p>',
                'body_text' => "Hello {{ student_name }},\n\nPayment for Invoice #{{ invoice_number }} has been confirmed.\n\nAmount: {{ currency_symbol }}{{ total_npr }}\n\nThank you!\n\n{{ site_name }} Team",
                'available_variables' => json_encode([
                    ['name' => 'student_name',   'description' => 'Student full name'],
                    ['name' => 'invoice_number',  'description' => 'Invoice number'],
                    ['name' => 'total_npr',       'description' => 'Total amount paid'],
                    ['name' => 'currency_symbol', 'description' => 'Currency symbol'],
                ]),
            ],
            [
                'key'   => 'welcome_new_user',
                'label' => 'Welcome Email (New Registration)',
                'subject' => 'Welcome to {{ site_name }}!',
                'body_html' => '<h2>Welcome, {{ first_name }}!</h2><p>Your account has been created successfully.</p><p><strong>Username:</strong> {{ username }}<br><strong>Email:</strong> {{ email }}</p><p>You can now log in and explore our courses.</p><p>Best regards,<br>{{ site_name }} Team</p>',
                'body_text' => "Welcome, {{ first_name }}!\n\nYour account has been created.\n\nUsername: {{ username }}\nEmail: {{ email }}\n\nBest regards,\n{{ site_name }} Team",
                'available_variables' => json_encode([
                    ['name' => 'first_name', 'description' => 'User first name'],
                    ['name' => 'username',   'description' => 'User username'],
                    ['name' => 'email',      'description' => 'User email address'],
                    ['name' => 'site_name',  'description' => 'Site name'],
                ]),
            ],
        ];

        foreach ($templates as $tpl) {
            DB::table('email_templates')->insert($tpl + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('course_categories');
    }
};
