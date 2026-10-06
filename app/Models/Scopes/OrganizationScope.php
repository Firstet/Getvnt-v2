<?php

namespace App\Models\Scopes;

use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Narrows a model to the current organization, but only while one is set.
 *
 * No context means no restriction on purpose: see OrganizationContext. A query that must see
 * across organizations while one is set (the platform owner's totals) opts out with
 * withoutGlobalScope(OrganizationScope::class).
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $id = OrganizationContext::id();

        if ($id !== null) {
            $builder->where($model->qualifyColumn('organization_id'), $id);
        }
    }
}
