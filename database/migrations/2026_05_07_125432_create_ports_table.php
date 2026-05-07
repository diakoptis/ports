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
        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->string('unlocode', 20)->unique();
            $table->string('name', 255);
            $table->string('country_name', 100);
            $table->string('country_code', 100);
            $table->timestamp('updated_at')->nullable();

            $table->index('name');
            $table->index('country_code');
            $table->index(['country_code', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ports');
    }
};
