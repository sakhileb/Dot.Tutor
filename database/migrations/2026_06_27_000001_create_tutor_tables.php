<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('level')->default('high_school');
            $table->timestamps();
        });

        Schema::create('tutor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->decimal('hourly_rate', 8, 2);
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('total_sessions')->default(0);
            $table->enum('status', ['pending', 'approved', 'suspended'])->default('pending');
            $table->timestamps();
        });

        Schema::create('tutor_subject', function (Blueprint $table) {
            $table->foreignId('tutor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->primary(['tutor_profile_id', 'subject_id']);
        });

        Schema::create('tutor_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->enum('delivery', ['online', 'in_person'])->default('online');
            $table->timestamp('starts_at');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->decimal('rate', 8, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('lesson_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('tutor_sessions')->cascadeOnDelete();
            $table->foreignId('uploader_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->timestamps();
        });

        Schema::create('session_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->unique()->constrained('tutor_sessions')->cascadeOnDelete();
            $table->foreignId('rated_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('review')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_ratings');
        Schema::dropIfExists('lesson_resources');
        Schema::dropIfExists('tutor_sessions');
        Schema::dropIfExists('tutor_subject');
        Schema::dropIfExists('tutor_profiles');
        Schema::dropIfExists('subjects');
    }
};
