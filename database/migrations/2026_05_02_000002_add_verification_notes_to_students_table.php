<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'birth_certificate_path')) {
                $table->string('birth_certificate_path')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('students', 'verification_status')) {
                $table->string('verification_status')->default('pending')->after('birth_certificate_path');
            }
            if (!Schema::hasColumn('students', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verification_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('students', 'birth_certificate_path') ? 'birth_certificate_path' : null,
                Schema::hasColumn('students', 'verification_status')    ? 'verification_status'    : null,
                Schema::hasColumn('students', 'verification_notes')     ? 'verification_notes'     : null,
            ]));
        });
    }
};