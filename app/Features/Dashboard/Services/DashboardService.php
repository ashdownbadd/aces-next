<?php

declare(strict_types=1);

namespace App\Features\Dashboard\Services;

use App\Features\Dashboard\Repositories\DashboardRepository;

final class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $repository,
    ) {}

    /**
     * @return array{
     *   cards: array<int, array<string, mixed>>,
     *   alerts: array<string, array<int, array<string, mixed>>>
     * }
     */
    public function snapshot(): array
    {
        $members = $this->repository->memberCounts();
        $loans = $this->repository->loanCounts();

        return [
            'cards' => [
                [
                    'key' => 'total_members',
                    'title' => 'Total Members',
                    'value' => $members['total'],
                    'description' => 'All registered cooperative members',
                    'icon' => 'fas fa-users',
                    'color' => 'primary',
                    'url' => '/members',
                ],
                [
                    'key' => 'active_members',
                    'title' => 'Active Members',
                    'value' => $members['active'],
                    'description' => 'Members currently in active status',
                    'icon' => 'fas fa-user-check',
                    'color' => 'success',
                    'url' => '/members?status=Active',
                ],
                [
                    'key' => 'regular_members',
                    'title' => 'Regular Members',
                    'value' => $members['regular'],
                    'description' => 'Members with regular membership',
                    'icon' => 'fas fa-id-card',
                    'color' => 'gold',
                    'url' => '/members',
                ],
                [
                    'key' => 'associate_members',
                    'title' => 'Associate Members',
                    'value' => $members['associate'],
                    'description' => 'Members with associate membership',
                    'icon' => 'fas fa-user-group',
                    'color' => 'primary',
                    'url' => '/members',
                ],
                [
                    'key' => 'inactive_members',
                    'title' => 'Inactive Members',
                    'value' => $members['inactive'],
                    'description' => 'Members currently in inactive status',
                    'icon' => 'fas fa-user-slash',
                    'color' => 'warning',
                    'url' => '/members?status=Inactive',
                ],
                [
                    'key' => 'male_members',
                    'title' => 'Male Members',
                    'value' => $members['male'],
                    'description' => 'Male members recorded in profiles',
                    'icon' => 'fas fa-mars',
                    'color' => 'primary',
                    'url' => '/members',
                ],
                [
                    'key' => 'female_members',
                    'title' => 'Female Members',
                    'value' => $members['female'],
                    'description' => 'Female members recorded in profiles',
                    'icon' => 'fas fa-venus',
                    'color' => 'warning',
                    'url' => '/members',
                ],
                [
                    'key' => 'active_loans',
                    'title' => 'Active Loans',
                    'value' => $loans['active'],
                    'description' => 'Loan accounts currently in payment',
                    'icon' => 'fas fa-money-bill-wave',
                    'color' => 'primary',
                    'url' => '/loans',
                ],
                [
                    'key' => 'fully_paid_loans',
                    'title' => 'Fully Paid Loans',
                    'value' => $loans['fully_paid'],
                    'description' => 'Loan accounts completed in full',
                    'icon' => 'fas fa-circle-check',
                    'color' => 'success',
                    'url' => '/loans',
                ],
                [
                    'key' => 'overdue_loans',
                    'title' => 'Overdue Loans',
                    'value' => $loans['overdue'],
                    'description' => 'Active loans with overdue periods',
                    'icon' => 'fas fa-triangle-exclamation',
                    'color' => 'danger',
                    'url' => '/loans',
                ],
            ],
            'action_required' => [
                'under_review_loans' => $loans['under_review'],
                'overdue_loans' => $loans['overdue'],
            ],
            'alerts' => [
                'negative_equity' =>
                    $this->repository->negativeEquityMembers(),
                'past_due_loans' =>
                    $this->repository->pastDueLoans(),
            ],
        ];
    }
}
