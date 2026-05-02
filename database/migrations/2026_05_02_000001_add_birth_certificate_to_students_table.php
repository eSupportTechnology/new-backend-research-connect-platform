<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('birth_certificate_path')->nullable()->after('student_id');
            $table->string('verification_status')->default('pending')->after('birth_certificate_path');
            $table->text('verification_notes')->nullable()->after('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['birth_certificate_path', 'verification_status', 'verification_notes']);
        });
    }
};