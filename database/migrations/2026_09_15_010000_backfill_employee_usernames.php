<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')->whereNull('username')->orderBy('id')->get()->each(function (object $employee) {
            $base = mb_strtolower(strstr($employee->email, '@', true) ?: 'usuario');
            $base = preg_replace('/[^a-z0-9._-]/', '', $base) ?: 'usuario';
            $username = $base;
            $suffix = 1;

            while (DB::table('employees')->where('username', $username)->exists()) {
                $username = $base.'-'.$suffix++;
            }

            DB::table('employees')->where('id', $employee->id)->update(['username' => $username]);
        });
    }

    public function down(): void
    {
        // Usernames may have been edited after deployment, so they are intentionally preserved.
    }
};
