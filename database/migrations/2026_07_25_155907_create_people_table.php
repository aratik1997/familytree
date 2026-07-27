<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();

            $table->string('full_name');
            $table->string('email');
            $table->string('mobile')->nullable();
            $table->string('address')->nullable();
            $table->json('social_links')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->date('date_of_birth');
            $table->string('gender')->nullable();
            $table->text('bio')->nullable();

            $table->boolean('is_deceased')->default(false);
            $table->date('death_date')->nullable();

            $table->enum('claim_status', ['not_applicable_minor', 'pending_invite', 'claimed'])
                ->default('not_applicable_minor');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('claimed_at')->nullable();

            $table->foreignId('created_by_person_id')->nullable()->constrained('people')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
