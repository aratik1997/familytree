<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Lets one person stand in more than one family's tree.
 *
 * A man is his father's son in one tree and his father-in-law's son-in-law in
 * another, and he is the same man in both — one profile, one photo, one set of
 * privacy choices. So this does not copy him: his record stays where it was
 * created and is lent to the other tree, which is what `tree_memberships`
 * records.
 *
 * Nothing appears in the other tree until the person themselves agrees. Being
 * written into somebody's family is not a thing that should happen to you
 * without your say-so, and a membership only counts once its status is
 * 'accepted'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // The code somebody quotes to add this person to their own tree.
            // Random rather than the row id: ids are guessable in sequence, and
            // a code that can be worked out invites a stream of requests from
            // people who have never met you.
            $table->string('public_id', 12)->nullable()->unique()->after('id');
        });

        Schema::create('tree_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('tree_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->foreignId('invited_by_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // One standing request or membership per person per tree, so a
            // second invitation cannot quietly reset a refusal.
            $table->unique(['person_id', 'tree_id']);
            $table->index(['tree_id', 'status']);
        });

        $this->backfillPublicIds();
    }

    private function backfillPublicIds(): void
    {
        DB::table('people')->whereNull('public_id')->orderBy('id')->each(function ($person) {
            DB::table('people')->where('id', $person->id)->update([
                'public_id' => static::freshCode(),
            ]);
        });
    }

    /** Upper-case, no vowels and no 0/O/1/I — it gets read aloud and copied by hand. */
    public static function freshCode(): string
    {
        do {
            $code = 'FT-'.Str::upper(Str::random(6));
            $code = preg_replace('/[AEIOU01]/', 'X', $code);
        } while (DB::table('people')->where('public_id', $code)->exists());

        return $code;
    }

    public function down(): void
    {
        Schema::dropIfExists('tree_memberships');

        Schema::table('people', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
