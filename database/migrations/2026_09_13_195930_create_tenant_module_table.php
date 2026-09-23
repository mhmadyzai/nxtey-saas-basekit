<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_module', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 36);
            $table->string('module');               // e.g. "Blog", "Invoices"
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();   // per-tenant module config
            $table->timestamps();

            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->unique(['tenant_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_module');
    }
};