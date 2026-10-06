<?php

namespace App\Traits;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * For models that carry organization_id. Adds the isolation scope, and stamps new rows with the
 * current organization when one is set so a row created in the console cannot land outside it.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function ($model) {
            if ($model->organization_id !== null) {
                return;
            }

            if (OrganizationContext::id() !== null) {
                $model->organization_id = OrganizationContext::id();

                return;
            }

            // Created outside the console (the new-schedule form, the API, an import): join the
            // creator's own organization if they have one. A creator with none leaves it null,
            // and Organization::provisionPersonalFor() adopts it when their organization is made.
            if ($model->user_id && static::organizationTablesExist()) {
                $model->organization_id = DB::table('organization_user')
                    ->where('user_id', $model->user_id)
                    ->where('level', Organization::LEVEL_OWNER)
                    ->orderBy('id')
                    ->value('organization_id');
            }
        });
    }

    /**
     * False while migrating an install that predates organizations: a schedule created by an
     * older migration or seeder must not fail on a table that does not exist yet.
     */
    protected static function organizationTablesExist(): bool
    {
        static $exists = false;

        // Only a positive answer is remembered: an install still migrating may say no, then yes.
        return $exists = $exists || Schema::hasTable('organization_user');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
