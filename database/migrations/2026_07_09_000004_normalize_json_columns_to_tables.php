<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise Migration 4 — Fourth Normal Form (4NF) JSON Column Normalization
 *
 * PROBLEM: The following columns store multi-valued data as JSON arrays,
 * violating First Normal Form (1NF) and Fourth Normal Form (4NF):
 *
 *   courses.features       → JSON array of feature strings
 *   courses.support        → JSON array of support items
 *   courses.instruction    → JSON array of instructions
 *   courses.schedule       → JSON array of schedule entries
 *   batches.best_for       → JSON array of target audience descriptors
 *   teachers.available_days → JSON array of weekday integers [0–6]
 *
 * SOLUTION: Create proper relational tables for each multi-valued dependency.
 * The JSON columns are NOT dropped here — they remain as a read fallback
 * during the application migration period. Once application code is fully
 * migrated to use the new tables, the JSON columns should be dropped in a
 * future migration.
 *
 * 4NF COMPLIANCE: Each new table contains only one multi-valued fact per entity,
 * eliminating all non-trivial multi-valued dependencies.
 *
 * DATA MIGRATION: Existing JSON data is read and migrated to the new tables
 * automatically during the migration run.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. course_features ────────────────────────────────────────────────
        // Replaces courses.features JSON column
        Schema::create('course_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();
            $table->string('feature', 500)
                ->comment('A single course feature or selling point.');
            $table->string('icon', 80)->nullable()
                ->comment('Optional icon name or emoji for UI display.');
            $table->boolean('is_highlighted')->default(false)
                ->comment('If true, displayed more prominently in course listing cards.');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'sort_order'], 'course_features_course_sort_index');
        });

        // ── 2. course_support_items ───────────────────────────────────────────
        // Replaces courses.support JSON column
        Schema::create('course_support_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();
            $table->string('item', 500)
                ->comment('A single support channel or resource provided with the course.');
            $table->string('icon', 80)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('course_id');
        });

        // ── 3. course_instruction_items ───────────────────────────────────────
        // Replaces courses.instruction JSON column
        Schema::create('course_instruction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();
            $table->text('item')
                ->comment('A single learning objective or course instruction.');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('course_id');
        });

        // ── 4. course_schedule_items ──────────────────────────────────────────
        // Replaces courses.schedule JSON column
        Schema::create('course_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();
            $table->string('label', 120)->nullable()
                ->comment('Schedule item label (e.g., "Week 1", "Day 3").');
            $table->text('content')
                ->comment('Schedule entry content or description.');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('course_id');
        });

        // ── 5. batch_best_for_items ───────────────────────────────────────────
        // Replaces batches.best_for JSON column
        Schema::create('batch_best_for_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();
            $table->string('item', 300)
                ->comment('A single target audience descriptor (e.g., "University graduates").');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('batch_id');
        });

        // ── 6. teacher_available_days ─────────────────────────────────────────
        // Replaces teachers.available_days JSON column
        Schema::create('teacher_available_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')
                ->comment('0 = Sunday, 1 = Monday, …, 6 = Saturday.');
            $table->time('from_time')->nullable()
                ->comment('Start of availability window on this day.');
            $table->time('to_time')->nullable()
                ->comment('End of availability window on this day.');
            $table->timestamps();

            // Each teacher can have at most one row per weekday
            $table->unique(['teacher_id', 'day_of_week'], 'teacher_days_unique');
            $table->index('teacher_id');
        });

        // ── Migrate existing JSON data ─────────────────────────────────────────
        $this->migrateJsonData();
    }

    /**
     * Read existing JSON column data and insert into the new normalized tables.
     * Safe to run even on empty databases (all cursors yield zero rows).
     */
    private function migrateJsonData(): void
    {
        $now = now();

        // courses: features, support, instruction, schedule
        DB::table('courses')
            ->whereNotNull('features')
            ->orWhereNotNull('support')
            ->orWhereNotNull('instruction')
            ->orWhereNotNull('schedule')
            ->select('id', 'features', 'support', 'instruction', 'schedule')
            ->chunkById(100, function ($courses) use ($now) {
                foreach ($courses as $course) {
                    $this->migrateJsonArray($course->features, 'course_features',          'course_id', $course->id, 'feature', $now);
                    $this->migrateJsonArray($course->support,   'course_support_items',     'course_id', $course->id, 'item',    $now);
                    $this->migrateJsonArray($course->instruction,'course_instruction_items','course_id', $course->id, 'item',    $now);
                    $this->migrateScheduleJson($course->schedule, $course->id, $now);
                }
            });

        // batches: best_for
        if (Schema::hasColumn('batches', 'best_for')) {
            DB::table('batches')
                ->whereNotNull('best_for')
                ->select('id', 'best_for')
                ->chunkById(100, function ($batches) use ($now) {
                    foreach ($batches as $batch) {
                        $this->migrateJsonArray($batch->best_for, 'batch_best_for_items', 'batch_id', $batch->id, 'item', $now);
                    }
                });
        }

        // teachers: available_days
        DB::table('teachers')
            ->whereNotNull('available_days')
            ->select('id', 'available_days')
            ->chunkById(100, function ($teachers) use ($now) {
                foreach ($teachers as $teacher) {
                    $days = $this->decodeJson($teacher->available_days);
                    if (!is_array($days)) continue;

                    foreach ($days as $day) {
                        $dayInt = is_numeric($day) ? (int) $day : $this->dayNameToInt($day);
                        if ($dayInt === null) continue;
                        DB::table('teacher_available_days')->insertOrIgnore([
                            'teacher_id'  => $teacher->id,
                            'day_of_week' => $dayInt,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ]);
                    }
                }
            });
    }

    /** Insert rows from a JSON array column into a target table. */
    private function migrateJsonArray(?string $json, string $table, string $fkColumn, int $ownerId, string $valueColumn, string $now): void
    {
        $items = $this->decodeJson($json);
        if (!is_array($items)) return;

        foreach (array_values(array_filter($items)) as $i => $item) {
            $value = is_string($item) ? $item : (is_array($item) ? json_encode($item) : (string) $item);
            DB::table($table)->insert([
                $fkColumn    => $ownerId,
                $valueColumn => $value,
                'sort_order' => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** Parse schedule JSON which may be array-of-strings or array-of-objects. */
    private function migrateScheduleJson(?string $json, int $courseId, string $now): void
    {
        $items = $this->decodeJson($json);
        if (!is_array($items)) return;

        foreach (array_values($items) as $i => $item) {
            if (is_string($item)) {
                DB::table('course_schedule_items')->insert([
                    'course_id'  => $courseId,
                    'content'    => $item,
                    'sort_order' => $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif (is_array($item)) {
                DB::table('course_schedule_items')->insert([
                    'course_id'  => $courseId,
                    'label'      => $item['label'] ?? ($item['title'] ?? null),
                    'content'    => $item['content'] ?? ($item['description'] ?? json_encode($item)),
                    'sort_order' => $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function decodeJson(?string $json): mixed
    {
        if (!$json) return null;
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }
    }

    private function dayNameToInt(mixed $day): ?int
    {
        $map = ['sunday'=>0,'monday'=>1,'tuesday'=>2,'wednesday'=>3,'thursday'=>4,'friday'=>5,'saturday'=>6,
                'sun'=>0,'mon'=>1,'tue'=>2,'wed'=>3,'thu'=>4,'fri'=>5,'sat'=>6];
        return $map[strtolower((string) $day)] ?? null;
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_available_days');
        Schema::dropIfExists('batch_best_for_items');
        Schema::dropIfExists('course_schedule_items');
        Schema::dropIfExists('course_instruction_items');
        Schema::dropIfExists('course_support_items');
        Schema::dropIfExists('course_features');
    }
};
