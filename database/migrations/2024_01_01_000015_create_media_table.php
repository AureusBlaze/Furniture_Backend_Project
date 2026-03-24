<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('disk')->default('public');
            $table->enum('type', ['image', 'video', 'ar_model'])->default('image');
            $table->unsignedBigInteger('size')->nullable()->comment('bytes');
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['product_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('media');
    }
};
