<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function shouldRun(): bool
    {
        return Schema::getConnection()->getTablePrefix() === '';
    }

    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table): void {
            $table->id();
            $table->string('domain');
            $table->string('path')->default('/');

            $table->unique(['domain', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
