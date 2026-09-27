<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->string('slug');
            $table->string('name');
            $table->longText('content')->nullable();
            $table->timestamp('date')->nullable();
            $table->timestamp('modified')->nullable();

            $table->unique(['type', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
