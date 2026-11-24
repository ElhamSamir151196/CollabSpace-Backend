<?php

namespace App\Policies;

use App\Models\ReportExport;
use App\Models\User;

/**
 * Exported PDFs are private to the person who generated them.
 */
class ReportExportPolicy
{
    public function view(User $user, ReportExport $export): bool
    {
        return $export->isOwnedBy($user);
    }

    public function delete(User $user, ReportExport $export): bool
    {
        return $export->isOwnedBy($user);
    }
}
