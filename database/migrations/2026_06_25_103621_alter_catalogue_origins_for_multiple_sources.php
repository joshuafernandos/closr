<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A business may now connect several catalogue sources (one per widget),
     * so the 1:1 unique constraint on business_id is dropped in favour of a
     * plain index, and each source gets a human-readable label.
     */
    public function up(): void
    {
        Schema::table('catalogue_origins', function (Blueprint $table) {
            $table->string('name')->nullable()->after('business_id');
        });

        Schema::table('catalogue_origins', function (Blueprint $table) {
            // Drop the FK before the unique index it relies on (MySQL), then
            // restore the FK against a plain index.
            $table->dropForeign(['business_id']);
            $table->dropUnique(['business_id']);
        });

        Schema::table('catalogue_origins', function (Blueprint $table) {
            $table->index('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalogue_origins', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id']);
        });

        Schema::table('catalogue_origins', function (Blueprint $table) {
            $table->unique('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::table('catalogue_origins', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
