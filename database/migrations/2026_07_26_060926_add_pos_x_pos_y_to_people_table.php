<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // A manually dragged position on the family tree view, overriding
            // the auto-computed layout for that person. Null means "use the
            // computed position" — the normal, default state.
            $table->integer('tree_pos_x')->nullable()->after('death_date');
            $table->integer('tree_pos_y')->nullable()->after('tree_pos_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['tree_pos_x', 'tree_pos_y']);
        });
    }
};
