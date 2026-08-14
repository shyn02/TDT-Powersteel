<?php

namespace App\Filament\Admin\Concerns;

/**
 * Gates ALL five Filament authorization checkpoints to admin-position
 * users, not just navigation visibility.
 *
 * Security note: hiding a resource from the sidebar via
 * shouldRegisterNavigation() does NOT stop someone from opening its
 * URLs directly (e.g. /admin/user-profiles/3/edit). Filament's default
 * canViewAny()/canCreate()/canEdit()/canDelete()/canDeleteAny() all
 * return true when a resource has no Policy class registered — so any
 * authenticated staff account (not just admins) could reach and submit
 * those pages unless every one of these methods is explicitly closed.
 *
 * This was the root cause of a privilege-escalation hole: a non-admin
 * "sales_rep" account could browse straight to a UserProfile edit page
 * and flip their own `position` field to `admin`, then use that to
 * create further admin accounts via UserResource. Applying this trait
 * to every admin-only resource closes that class of bug in one place
 * instead of copy-pasting (and inevitably forgetting) the same five
 * methods per resource.
 *
 * Usage: `use AdminOnlyResource;` in the Resource class. Resources that
 * need one of these to behave differently (e.g. SystemSettingsResource
 * always blocking canCreate/canDelete, admin or not, to protect its
 * singleton row) can still override that single method afterward —
 * a method defined directly on the class always wins over the trait.
 */
trait AdminOnlyResource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdminPosition() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdminPosition() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isAdminPosition() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isAdminPosition() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdminPosition() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdminPosition() ?? false;
    }
}
