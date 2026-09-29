<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta', function (Blueprint $table): void {
            $table->id();
            $table->morphs('metable');
            $table->string('key');
            $table->json('value');

            $table->unique(['metable_type', 'metable_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta');
    }
};
