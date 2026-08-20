<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->after('id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->index('role_id');
        });

        $administratorRoleId = DB::table('roles')
            ->where('name', 'Administrator')
            ->value('id');

        if ($administratorRoleId !== null) {
            DB::table('users')
                ->whereNull('role_id')
                ->update(['role_id' => $administratorRoleId]);
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE users MODIFY role_id BIGINT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
