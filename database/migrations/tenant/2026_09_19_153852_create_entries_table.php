<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_type_id')
                  ->constrained('content_types')
                  ->cascadeOnDelete();
            $table->string('slug', 200);
            $table->string('title', 255);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['content_type_id', 'slug']);
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};