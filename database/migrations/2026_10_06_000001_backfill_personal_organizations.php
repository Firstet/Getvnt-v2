<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gives every existing schedule owner a personal organization and moves their schedules into it.
 *
 * Idempotent: a user who already belongs to an organization is skipped, and a schedule that
 * already has an organization is never moved. Schedules with no owner (auto-created ones) stay
 * unassigned, which the platform owner can fix from /admin/organizations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ownerIds = DB::table('role_user')
            ->where('level', 'owner')
            ->distinct()
            ->pluck('user_id');

        foreach ($ownerIds->chunk(200) as $chunk) {
            foreach ($chunk as $userId) {
                $alreadyMember = DB::table('organization_user')->where('user_id', $userId)->exists();
                if ($alreadyMember) {
                    continue;
                }

                $user = DB::table('users')->where('id', $userId)->first(['id', 'name', 'email']);
                if (! $user) {
                    continue;
                }

                $label = trim((string) $user->name) !== '' ? $user->name : Str::before($user->email, '@');

                DB::transaction(function () use ($user, $label) {
                    $now = now();

                    $organizationId = DB::table('organizations')->insertGetId([
                        'name' => Str::limit($label, 100, '').' Organization',
                        'slug' => Str::slug(Str::limit($label, 40, '')).'-'.$user->id,
                        'owner_id' => $user->id,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('organization_user')->insert([
                        'organization_id' => $organizationId,
                        'user_id' => $user->id,
                        'level' => 'owner',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $roleIds = DB::table('role_user')
                        ->where('user_id', $user->id)
                        ->where('level', 'owner')
                        ->pluck('role_id');

                    DB::table('roles')
                        ->whereIn('id', $roleIds)
                        ->whereNull('organization_id')
                        ->update(['organization_id' => $organizationId]);
                });
            }
        }
    }

    public function down(): void
    {
        // Data only. Rolling back the previous migration drops the tables and the column.
    }
};
