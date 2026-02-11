<?php

namespace App\Policies;

use App\Models\Injury;
use App\Models\InjuryReport;
use App\Models\User;

class InjuryReportPolicy
{
    /**
     * Determine whether the user can view any reports for the injury.
     */
    public function viewAny(User $user, Injury $injury): bool
    {
        return $user->id === $injury->user_id;
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, InjuryReport $report): bool
    {
        return $user->id === $report->injury->user_id;
    }

    /**
     * Determine whether the user can create reports for the injury.
     */
    public function create(User $user, Injury $injury): bool
    {
        return $user->id === $injury->user_id;
    }

    /**
     * Determine whether the user can update the report.
     */
    public function update(User $user, InjuryReport $report): bool
    {
        return $user->id === $report->user_id;
    }

    /**
     * Determine whether the user can delete the report.
     */
    public function delete(User $user, InjuryReport $report): bool
    {
        return $user->id === $report->user_id || $user->id === $report->injury->user_id;
    }
}
