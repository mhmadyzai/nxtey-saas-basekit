<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // "Header Menu"
            $table->string('slug')->unique();          // "header"
            $table->string('location')->nullable();    // "header", "footer", "sidebar"
            $table->json('items')->nullable();         // nested tree
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};