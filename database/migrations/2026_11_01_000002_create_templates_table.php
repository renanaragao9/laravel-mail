<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->string('color', 7)->default('#2563eb');
            $table->string('background_color', 7)->default('#f4f4f5');
            $table->string('font', 20)->default('arial');
            $table->json('blocks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
