<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Conversations now belong to a specific widget. business_id is kept (and
     * populated from the widget) so the Messages dashboard can keep scoping by
     * business, while the grouping key for a shopper's session moves to the
     * widget.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('widget_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'session_id']);
            $table->unique(['widget_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['widget_id', 'session_id']);
            $table->unique(['business_id', 'session_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('widget_id');
        });
    }
};
