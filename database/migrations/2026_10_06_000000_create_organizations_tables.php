<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The tenant layer above schedules.
 *
 * A schedule (roles row) was already the unit of isolation, but nothing sat above it: a company
 * running five venues had five unrelated tenants with separate billing and separate team access.
 * An organization owns schedules (roles.organization_id) and has its own members, each holding a
 * level of owner, admin or member inside it. The platform owner stays users.is_admin.
 *
 * Levels are plain strings, not an ENUM, so adding one later is not an ALTER on a big table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organizations')) {
            Schema::create('organizations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 20)->default('active')->index();
                $table->timestamp('suspended_at')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('organization_user')) {
            Schema::create('organization_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('level', 20)->default('member');
                $table->timestamps();

                $table->unique(['organization_id', 'user_id']);
                $table->index('user_id');
            });
        }

        if (! Schema::hasColumn('roles', 'organization_id')) {
            try {
                \Illuminate\Support\Facades\DB::statement('ALTER TABLE roles MODIFY subdomain_before_delete TEXT NULL, MODIFY notification_email TEXT NULL, MODIFY microsoft_webhook_id TEXT NULL, MODIFY stay22_aid TEXT NULL, MODIFY list_animation TEXT NULL, MODIFY sponsor_background_color TEXT NULL;');
            } catch (\Throwable $e) {
                // Ignore if DB driver does not support or if columns differ
            }

            Schema::table('roles', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->after('id')
                    ->constrained('organizations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
