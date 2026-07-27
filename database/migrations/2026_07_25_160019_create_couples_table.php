<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_a_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('person_b_id')->constrained('people')->cascadeOnDelete();
            $table->enum('status', ['married', 'divorced', 'separated', 'widowed', 'partnered'])->default('married');
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couples');
    }
};
