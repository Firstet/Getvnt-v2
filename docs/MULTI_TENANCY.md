# Multi-tenancy: organizations

Getvnt has three levels of authority:

| Level | Who | What they can do |
| --- | --- | --- |
| Platform owner | `users.is_admin` | Everything: every organization, every schedule, platform settings, billing |
| Organization admin | `organization_user.level` = `owner` or `admin` | Manage their organization's team and schedules |
| Organization member | `organization_user.level` = `member` | See the organization, work in the schedules they are given |

A **schedule** (`Role` in code) was already the unit of isolation. An **organization** is the
tenant above it: it owns schedules (`roles.organization_id`) and has its own team.

## How isolation works

- `App\Support\OrganizationContext` holds the current organization id for the request.
- `App\Models\Scopes\OrganizationScope` is a global scope that adds
  `WHERE organization_id = ?` **only while a context is set**.
- `App\Traits\BelongsToOrganization` (used by `Role`) installs the scope and stamps new rows.
- `SetOrganizationContext` (route alias `org`) sets the context for the organization console
  and nothing else. `EnsureOrganizationAdmin` (`org.admin`) gates the mutating routes.

No context means no restriction. That is deliberate: the public guest portal, webhooks, queue
jobs and the platform owner's `/admin` pages all run with no context, exactly as before, so
turning this on cannot change them.

## Routes

- `/organization` (+ members, switch): the organization's own console. Behind `org`.
- `/admin/organizations*`: the platform owner's list, create, suspend, resume, assign a schedule,
  and "open console" (steps into any organization). Behind `admin`.
- `organization` and `organizations` are reserved schedule names.

## Rollout

1. Deploy and run migrations. `2026_10_06_000001` gives every existing schedule owner a personal
   organization and moves their schedules in. It is idempotent.
2. Open `/admin/organizations`. The banner counts schedules that belong to no organization
   (auto-created ones with no owner). Move them in with "Move a schedule here".
3. Create real organizations for companies, set their owner by email, and move their schedules.

## Not done yet (in rough priority order)

1. **Enforce the scope on every tenant-owned model.** Only `Role` has it. `Event`, `Ticket`,
   `Sale`, newsletters, analytics and the rest are reached through a schedule today, so they are
   isolated by that join, not by a column. Add `organization_id` to the ones queried directly
   (`Sale`, `Event`, `Newsletter`, `PromoCode`, `GiftCard`) and add the trait.
2. **Set the context outside the console.** Queue jobs and webhooks should wrap their work in
   `OrganizationContext::run($organizationId, ...)` once the models above carry the scope.
3. **Organization-level billing.** Plans and Stripe subscriptions are per schedule
   (`RoleBillable`). A shared plan and seat limits per organization is the SaaS step.
4. **Invite by email.** Adding a team member needs an existing account today.
5. **Provision at sign-up.** A user gets an organization on first console visit or first
   schedule. Creating one in the registration flow is cleaner.
6. **Organization subdomain or branding**, and ownership transfer.
7. **Deleting an organization.** Intentionally absent: suspend is reversible, delete is not.
