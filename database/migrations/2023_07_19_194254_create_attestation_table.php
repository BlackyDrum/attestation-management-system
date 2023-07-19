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
        Schema::create('attestation', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_number');
            $table->string('subject_name',255);
            $table->unsignedBigInteger('current_semester');
            $table->unsignedBigInteger('creator_id');
            $table->timestamps();

            $table->foreign('current_semester')->references('id')->on('semester');
            $table->foreign('creator_id')->references('id')->on('users')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attestation');
    }
};
