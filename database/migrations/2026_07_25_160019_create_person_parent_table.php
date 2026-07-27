<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_parent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('people')->cascadeOnDelete();
            $table->enum('relationship_type', ['biological', 'step', 'adoptive', 'guardian'])->default('biological');
            $table->timestamps();

            $table->unique(['child_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_parent');
    }
};
