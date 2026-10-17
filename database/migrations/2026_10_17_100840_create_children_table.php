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
    Schema::create('children', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->unsignedTinyInteger('level')->default(1);
        $table->unsignedInteger('xp')->default(0);
        $table->unsignedInteger('streak')->default(0);
        $table->string('mood')->default('ok');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
