<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gives every family its own tree.
 *
 * Until now there was one tree and everybody was in it. An Admin gets their own
 * instead — their own people, their own couples, invisible to every other
 * Admin. The Super Admin manages who the Admins are; the family records inside
 * each tree stay that Admin's own business.
 *
 * Everything already on record becomes tree #1 and stays with the Super Admin,
 * so nothing changes for anyone already using the site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            // Which tree this login works in. An Admin whose tree is still
            // empty has no Person record of their own, so the tree cannot be
            // reached through one — it has to be held here.
            $table->foreignId('tree_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_admin')->default(false)->after('is_super_admin');
        });

        foreach (['people', 'couples'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tree_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                $table->index('tree_id');
            });
        }

        $this->seedFirstTree();
    }

    /**
     * Everything that exists today is one family: put it in tree #1 under
     * whichever account is the Super Admin.
     */
    private function seedFirstTree(): void
    {
        $ownerId = DB::table('users')->where('is_super_admin', true)->value('id');

        $treeId = DB::table('trees')->insertGetId([
            'name' => 'The Khandani Legacy',
            'owner_user_id' => $ownerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('people')->whereNull('tree_id')->update(['tree_id' => $treeId]);
        DB::table('couples')->whereNull('tree_id')->update(['tree_id' => $treeId]);
        DB::table('users')->whereNull('tree_id')->update(['tree_id' => $treeId]);
    }

    public function down(): void
    {
        foreach (['people', 'couples'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['tree_id']);
                $table->dropColumn('tree_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tree_id']);
            $table->dropColumn(['tree_id', 'is_admin']);
        });

        Schema::dropIfExists('trees');
    }
};
