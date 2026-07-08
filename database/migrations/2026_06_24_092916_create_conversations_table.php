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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // Null for the keyless demo widget, which has no owning merchant.
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            // Stable per-shopper id the widget keeps in localStorage so turns
            // from the same browser group into one conversation.
            $table->string('session_id');
            $table->string('title')->nullable();
            // Conversations start as "Left" and move to a better action (added
            // to cart, visited a product page) as the shopper interacts.
            $table->string('last_action')->default('left');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'session_id']);
            $table->index(['business_id', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
