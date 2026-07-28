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
        // Fix community_posts
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('community_posts', function (Blueprint $table) {
            $table->uuid('user_id')->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Fix community_likes.
        // The (user_id, post_id) unique index has to go first: SQLite refuses
        // to drop a column that an index still references.
        Schema::table('community_likes', function (Blueprint $table) {
            $table->dropUnique('community_likes_user_id_post_id_unique');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('community_likes', function (Blueprint $table) {
            $table->uuid('user_id')->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'post_id']);
        });

        // Fix community_comments
        Schema::table('community_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('community_comments', function (Blueprint $table) {
            $table->uuid('user_id')->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback posts
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('community_posts', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
        });

        // Rollback likes
        Schema::table('community_likes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('community_likes', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
        });

        // Rollback comments
        Schema::table('community_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('community_comments', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
        });
    }
};
