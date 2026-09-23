<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->string('seo_title', 255)->nullable()->after('title');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->unsignedBigInteger('og_image_id')->nullable()->after('seo_description');
            $table->string('canonical_url', 500)->nullable()->after('og_image_id');
            $table->boolean('no_index')->default(false)->after('canonical_url');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description', 'og_image_id', 'canonical_url', 'no_index']);
        });
    }
};