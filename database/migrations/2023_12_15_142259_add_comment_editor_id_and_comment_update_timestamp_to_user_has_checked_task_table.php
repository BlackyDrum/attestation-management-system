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
        Schema::table('user_has_checked_task', function (Blueprint $table) {
            $table->unsignedBigInteger('comment_editor_id')->nullable();
            $table->timestamp('comment_updated_at')->nullable();

            $table->foreign('comment_editor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_has_checked_task', function (Blueprint $table) {
            $table->dropColumn('comment_editor_id');
            $table->dropColumn('comment_updated_at');
        });
    }
};
