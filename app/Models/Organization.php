<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A tenant: a company, venue group or promoter that owns schedules and has its own team.
 *
 * Above it sits the platform owner (users.is_admin), who can see and manage every organization.
 * Inside it, members hold a level: owner (one, can do everything), admin (manages the team and
 * schedules) or member (works in the schedules they are given).
 */
class Organization extends Model
{
    public const LEVEL_OWNER = 'owner';

    public const LEVEL_ADMIN = 'admin';

    public const LEVEL_MEMBER = 'member';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    /** status, suspended_at and owner_id are operational state, set through the methods below. */
    protected $fillable = ['name', 'slug', 'settings'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'suspended_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('level')->withTimestamps();
    }

    /**
     * Already keyed by organization_id, so the global scope is redundant here, and while the
     * platform owner has switched into a DIFFERENT organization it would hide every row.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Role::class)->withoutGlobalScope(OrganizationScope::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /** The user's level in this organization, or null if they are not a member. */
    public function levelFor(User $user): ?string
    {
        $level = $this->users()->where('users.id', $user->id)->first()?->pivot->level;

        return $level ?: null;
    }

    public function isManagedBy(User $user): bool
    {
        return in_array($this->levelFor($user), [self::LEVEL_OWNER, self::LEVEL_ADMIN], true);
    }

    public function suspend(): void
    {
        $this->forceFill(['status' => self::STATUS_SUSPENDED, 'suspended_at' => now()])->save();
    }

    public function resume(): void
    {
        $this->forceFill(['status' => self::STATUS_ACTIVE, 'suspended_at' => null])->save();
    }

    /**
     * Create an organization with its owner already a member. The one place that sets owner_id.
     */
    public static function createWithOwner(string $name, User $owner): self
    {
        return DB::transaction(function () use ($name, $owner) {
            $organization = new self(['name' => $name, 'slug' => self::uniqueSlug($name)]);
            $organization->forceFill(['status' => self::STATUS_ACTIVE, 'owner_id' => $owner->id])->save();
            $organization->users()->attach($owner->id, ['level' => self::LEVEL_OWNER]);

            return $organization;
        });
    }

    /** The organization a user gets when they have none, so the console always has somewhere to land. */
    public static function provisionPersonalFor(User $user): self
    {
        $label = trim((string) $user->name) !== '' ? $user->name : Str::before((string) $user->email, '@');

        $organization = self::createWithOwner(Str::limit($label, 100, '').' Organization', $user);

        // Schedules this user already owns but that were made before they had an organization.
        Role::withoutGlobalScopes()
            ->whereNull('organization_id')
            ->whereIn('id', DB::table('role_user')
                ->where('user_id', $user->id)
                ->where('level', 'owner')
                ->select('role_id'))
            ->update(['organization_id' => $organization->id]);

        return $organization;
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug(Str::limit($name, 60, '')) ?: 'organization';
        $slug = $base;
        $i = 2;

        while (self::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
