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
        Schema::create('themes', function (Blueprint $table) {
			$table->id();
			$table->string('name')->unique();        // matches theme directory name
			$table->string('display_name')->nullable();
			$table->string('version')->nullable();
			$table->string('parent')->nullable();
			$table->boolean('removable')->default(true);
			$table->boolean('disableable')->default(true);
			$table->json('metadata')->nullable();    // author, description, tags
			$table->timestamps();
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
