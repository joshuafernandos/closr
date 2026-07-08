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
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            // The single catalogue source this widget reads from. Nullable so a
            // widget can exist before a source is picked; the source is shared at
            // the business level, so deleting it just detaches the widget.
            $table->foreignId('catalogue_origin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            // Public key the embed script carries to identify this widget.
            $table->string('widget_key')->unique();
            // 'chat' (floating launcher card) or 'component' (inline panel).
            $table->string('template')->default('chat');
            $table->string('accent_color')->default('#171717');
            $table->timestamps();

            $table->index('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};
