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
        Schema::table('conversation_messages', function (Blueprint $table) {
            // 'message' for a chat turn, 'event' for a shopper action (added to
            // cart, picked a variation) replayed inline in the transcript.
            $table->string('type')->default('message')->after('role');
            // Structured payload for event rows: the action and chosen variant.
            $table->json('meta')->nullable()->after('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'meta']);
        });
    }
};
