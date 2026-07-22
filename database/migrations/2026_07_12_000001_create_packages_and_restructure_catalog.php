<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog restructure (Bimal, 12 July 2026):
 *   Course Type (IELTS Academic / IELTS General / PTE Academic / PTE Core)
 *     └── Package (Elite Private, Premium Focus, Value Batch, Smart Batch,
 *                  Friends Private Group — priced per course type)
 *           └── Batches (time slots, each may have its own teacher;
 *                        flexible packages use a custom-schedule batch)
 *
 * Existing batch rows carry the package metadata today; each becomes the
 * source of a package row and remains as the first batch under it, so all
 * existing enrollments/invoices (batch_id) stay valid.
 */
return new class extends Migration
{
    private const PACKAGE_ORDER = [
        'Elite Private' => 1,
        'Premium Focus' => 2,
        'Value Batch' => 3,
        'Smart Batch' => 4,
        'Friends Private Group' => 5,
    ];

    private const FLEXIBLE = ['Elite Private', 'Friends Private Group'];

    public function up(): void
    {
        // 1. Packages table
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('size_label', 100)->nullable();
            $table->string('schedule_notes')->nullable();
            $table->unsignedSmallInteger('duration_weeks')->nullable();
            $table->decimal('price_npr', 10, 2)->nullable();
            $table->boolean('is_flexible')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['course_id', 'name']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('course_id')
                ->constrained('packages')->nullOnDelete();
        });

        // 2. Rename existing course types (keeps enrollments intact)
        DB::table('courses')->where('id', 1)->update(['course_name' => 'IELTS Academic']);
        DB::table('courses')->where('id', 2)->update(['course_name' => 'PTE Academic']);

        // 3. Create the two new course types by copying the academic ones
        $newCourseIds = [];
        foreach ([['source' => 1, 'name' => 'IELTS General'], ['source' => 2, 'name' => 'PTE Core']] as $def) {
            $src = (array) DB::table('courses')->find($def['source']);
            if (! $src) {
                continue;
            }
            unset($src['id']);
            $src['course_name'] = $def['name'];
            $src['created_at'] = now();
            $src['updated_at'] = now();
            $newCourseIds[$def['source']][] = DB::table('courses')->insertGetId($src);
        }

        // 4. Packages for the two EXISTING courses — sourced from their batch rows
        foreach ([1, 2] as $courseId) {
            $batches = DB::table('batches')
                ->where('course_id', $courseId)->whereNull('deleted_at')->get();

            foreach ($batches as $batch) {
                $weeks = null;
                if ($batch->schedule_notes && preg_match('/(\d+)\s*(?:weeks?|wks?)/i', $batch->schedule_notes, $m)) {
                    $weeks = (int) $m[1];
                }

                $packageId = DB::table('packages')->insertGetId([
                    'course_id' => $courseId,
                    'name' => $batch->batch_type,
                    'description' => $batch->best_for,
                    'size_label' => $batch->size_label,
                    'schedule_notes' => $batch->schedule_notes,
                    'duration_weeks' => $weeks,
                    'price_npr' => $batch->price_npr,
                    'is_flexible' => in_array($batch->batch_type, self::FLEXIBLE),
                    'is_featured' => (bool) ($batch->is_featured ?? false),
                    'sort_order' => self::PACKAGE_ORDER[$batch->batch_type] ?? 99,
                    'is_active' => (bool) $batch->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('batches')->where('id', $batch->id)->update(['package_id' => $packageId]);
            }
        }

        // 5. Packages + one starter batch for the two NEW courses
        //    (prices copied from the academic variant as a starting point —
        //     Bimal adjusts per course type in the admin panel)
        foreach ($newCourseIds as $sourceCourseId => $ids) {
            foreach ($ids as $newCourseId) {
                $sourcePackages = DB::table('packages')->where('course_id', $sourceCourseId)->get();
                foreach ($sourcePackages as $pkg) {
                    $newPkgId = DB::table('packages')->insertGetId([
                        'course_id' => $newCourseId,
                        'name' => $pkg->name,
                        'description' => $pkg->description,
                        'size_label' => $pkg->size_label,
                        'schedule_notes' => $pkg->schedule_notes,
                        'duration_weeks' => $pkg->duration_weeks,
                        'price_npr' => $pkg->price_npr,
                        'is_flexible' => $pkg->is_flexible,
                        'is_featured' => $pkg->is_featured,
                        'sort_order' => $pkg->sort_order,
                        'is_active' => $pkg->is_active,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Starter batch so enrollment works immediately; admin adds
                    // real time slots later.
                    DB::table('batches')->insert([
                        'course_id' => $newCourseId,
                        'package_id' => $newPkgId,
                        'batch_type' => $pkg->name,
                        'price_npr' => $pkg->price_npr,
                        'schedule_notes' => $pkg->schedule_notes,
                        'size_label' => $pkg->size_label,
                        'best_for' => $pkg->description,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
        });
        Schema::dropIfExists('packages');
        DB::table('courses')->where('id', 1)->update(['course_name' => 'IELTS Preparation Course']);
        DB::table('courses')->where('id', 2)->update(['course_name' => 'PTE Academic Preparation Course']);
        DB::table('courses')->whereIn('course_name', ['IELTS General', 'PTE Core'])->delete();
    }
};
