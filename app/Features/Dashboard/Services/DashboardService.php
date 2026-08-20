<?php

declare(strict_types=1);

namespace App\Features\Dashboard\Services;

use App\Features\Dashboard\Repositories\DashboardRepository;

final class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $repository,
    ) {}

    /** @return array{cards: array<int, array<string, mixed>>, alerts: array<string, array<int, array<string, mixed>>>} */
    public function snapshot(): array
    {
        $members = $this->repository->memberCounts();
        $loans = $this->repository->loanCounts();

        return [
            'cards' => [
                [
                    'title' => 'Members',
                    'value' => $members['total'],
                    'subtitle' => 'Registered Members',
                    'description' => 'Total cooperative members',
                    'icon' => 'fas fa-users',
                    'color' => 'primary',
                    'url' => '/members',
                ],
                [
                    'title' => 'Regular Members',
                    'value' => $members['regular'],
                    'subtitle' => 'Membership Type',
                    'description' => 'Associate: ' . $members['associate'],
                    'icon' => 'fas fa-id-card',
                    'color' => 'gold',
                    'url' => '/members',
                ],
                [
                    'title' => 'Active Members',
                    'value' => $members['active'],
                    'subtitle' => 'Membership Status',
                    'description' => 'Inactive: ' . $members['inactive'],
                    'icon' => 'fas fa-user-check',
                    'color' => 'success',
                    'url' => '/members?status=Active',
                ],
                [
                    'title' => 'Female Members',
                    'value' => $members['female'],
                    'subtitle' => 'Gender Distribution',
                    'description' => 'Male: ' . $members['male'],
                    'icon' => 'fas fa-venus',
                    'color' => 'warning',
                    'url' => '/members',
                ],
                [
                    'title' => 'Active Loans',
                    'value' => $loans['active'],
                    'subtitle' => 'Loan Status',
                    'description' => 'Currently in payment',
                    'icon' => 'fas fa-money-bill-wave',
                    'color' => 'primary',
                    'url' => '/loans',
                ],
                [
                    'title' => 'Fully Paid Loans',
                    'value' => $loans['fully_paid'],
                    'subtitle' => 'Loan Status',
                    'description' => 'Completed loan accounts',
                    'icon' => 'fas fa-circle-check',
                    'color' => 'success',
                    'url' => '/loans',
                ],
                [
                    'title' => 'Under Review',
                    'value' => $loans['under_review'],
                    'subtitle' => 'Loan Applications',
                    'description' => 'Awaiting review/decision',
                    'icon' => 'fas fa-clipboard-check',
                    'color' => 'warning',
                    'url' => '/loans?status=Under%20Review',
                ],
                [
                    'title' => 'Overdue Loans',
                    'value' => $loans['overdue'],
                    'subtitle' => 'Loan Alerts',
                    'description' => 'Active loans with overdue periods',
                    'icon' => 'fas fa-triangle-exclamation',
                    'color' => 'danger',
                    'url' => '/loans',
                ],
            ],
            'alerts' => [
                'negative_equity' => $this->repository->negativeEquityMembers(),
                'past_due_loans' => $this->repository->pastDueLoans(),
            ],
        ];
    }
}
