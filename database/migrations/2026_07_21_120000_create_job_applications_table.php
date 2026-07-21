<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applications used to be emailed to the poster and nothing more, so
     * nothing showed up in the platform. This stores them.
     */
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('career_id')->constrained('careers')->onDelete('cascade');

            // Keep the application readable even if the applicant's account goes
            // away, hence the name/email snapshot alongside the nullable FK.
            $table->uuid('applicant_id')->nullable();
            $table->foreign('applicant_id')->references('id')->on('users')->onDelete('set null');
            $table->string('applicant_name')->nullable();
            $table->string('applicant_email')->nullable();

            $table->string('profile_url')->nullable();
            $table->text('message')->nullable();
            $table->string('cv_path')->nullable();

            $table->enum('status', ['new', 'reviewed', 'shortlisted', 'rejected'])->default('new');
            $table->timestamp('viewed_at')->nullable();

            $table->timestamps();

            $table->index(['career_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
