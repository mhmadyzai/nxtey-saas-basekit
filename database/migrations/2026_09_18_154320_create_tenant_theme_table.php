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
        Schema::create('tenant_theme', function (Blueprint $table) {
			$table->id();
			$table->string('tenant_id', 36);
			$table->string('theme');
			$table->boolean('enabled')->default(true);
			$table->timestamps();

			$table->foreign('tenant_id')
				  ->references('id')->on('tenants')
				  ->cascadeOnDelete();

			$table->unique(['tenant_id', 'theme']);
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_theme');
    }
};
