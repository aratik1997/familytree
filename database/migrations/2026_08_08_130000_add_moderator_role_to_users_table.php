<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A moderator looks after the family records: adding people, editing anyone's
 * profile, recording who is related to whom. The same run of the tree the Super
 * Admin has, so the work of keeping it up to date can be shared.
 *
 * What stays with the Super Admin is not a longer reach into the records but a
 * different job: saying who the moderators are.
 *
 * The second half of this drops the multi-tree arrangement that was tried and
 * dropped again — there is one family tree here, and one only. Every drop is
 * guarded, so this is a no-op on a database where those tables were never
 * created, and a clean-up on one where they were.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_moderator')->default(false)->after('is_super_admin');
        });

        $this->dropTheAbandonedTreeTables();
    }

    private function dropTheAbandonedTreeTables(): void
    {
        foreach (['people', 'couples'] as $tableName) {
            if (Schema::hasColumn($tableName, 'tree_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['tree_id']);
                    $table->dropColumn('tree_id');
                });
            }
        }

        if (Schema::hasColumn('people', 'public_id')) {
            Schema::table('people', function (Blueprint $table) {
                $table->dropUnique(['public_id']);
                $table->dropColumn('public_id');
            });
        }

        foreach (['tree_id', 'is_admin'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                continue;
            }

            Schema::table('users', function (Blueprint $table) use ($column) {
                if ($column === 'tree_id') {
                    $table->dropForeign(['tree_id']);
                }

                $table->dropColumn($column);
            });
        }

        Schema::dropIfExists('tree_memberships');
        Schema::dropIfExists('trees');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_moderator');
        });
    }
};
