<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_field_privacies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('field_key');
            $table->enum('visibility', ['everyone', 'family', 'private'])->default('family');
            $table->timestamps();

            $table->unique(['person_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_field_privacies');
    }
};
