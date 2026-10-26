<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_meta', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value');
            $table->string('owner');

            $table->unique(['term_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_meta');
    }
};
