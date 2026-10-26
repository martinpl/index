<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_meta', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value');
            $table->string('owner');

            $table->unique(['entry_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_meta');
    }
};
