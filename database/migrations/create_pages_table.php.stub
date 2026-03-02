<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('page')->index();
            $table->string('title');
            $table->string('slug');
            $table->string('slug_path');
            $table->string('locale')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->restrictOnDelete();
            $table->integer('order')->default(0);
            $table->json('blocks')->nullable();
            $table->string('layout')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['type', 'locale', 'slug_path']);
            $table->unique(['type', 'locale', 'parent_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
