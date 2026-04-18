<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('tags');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('event_time')->nullable()->after('end_date');
            $table->string('registration_url')->nullable()->after('event_time');
            
            // Allow 'event' as a type if needed, or we just use category.
            // Let's modify the enum 'type' to include 'event' for better specialized rendering
            $table->string('type')->change(); // Temporary change to string to modify enum
        });

        // Update the enum type
        DB::statement("ALTER TABLE community_posts MODIFY COLUMN type ENUM('research', 'discussion', 'event') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'event_time', 'registration_url']);
        });
        
        DB::statement("ALTER TABLE community_posts MODIFY COLUMN type ENUM('research', 'discussion') NOT NULL");
    }
};
