<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['academic', 'photo', 'moment', 'career', 'other']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('occurred_on')->nullable();
            $table->json('meta')->nullable();
            $table->enum('visibility', ['everyone', 'family', 'private'])->default('family');
            $table->foreignId('created_by_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['person_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('records');
    }
};
