<?php

declare(strict_types=1);

namespace App\Features\Loans\Controllers;

use App\Features\Loans\Repositories\LoanPaymentRepository;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Loans\Services\PaymentService;
use App\Features\Loans\Services\StatementOfAccountService;
use App\Features\Loans\Services\StatementOfAccountXlsx;
use App\Features\Members\Services\MemberService;
use App\Foundation\Session;
use App\Foundation\View;
use App\Http\Request;
use App\Http\Response;
use InvalidArgumentException;
use RuntimeException;

final class LoanController
{
    public function __construct(
        private readonly View $view,
        private readonly LoanService $loanService,
        private readonly AmortizationService $amortization,
        private readonly PaymentService $paymentService,
        private readonly LoanPaymentRepository $paymentRepository,
        private readonly StatementOfAccountService $soaService,
        private readonly StatementOfAccountXlsx $soaXlsx,
        private readonly MemberService $memberService,
        private readonly Session $session,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'Under Review'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $allowedStatuses = [
            'Pending',
            'Under Review',
            'Approved',
            'Rejected',
            'Overdue',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'Under Review';
        }

        $loans = $this->loanService->all(
            search: $search,
            applicationStatus: $status,
            loanStatus: '',
            memberId: 0,
            limit: $perPage,
            offset: $offset,
        );

        $total = $this->loanService->count(
            search: $search,
            applicationStatus: $status,
            loanStatus: '',
            memberId: 0,
        );

        $lastPage = max(1, (int) ceil($total / $perPage));

        return new Response(
            $this->view->render(
                'loans.index',
                [
                    'title' => 'Loan Applications',
                    'loans' => $loans,
                    'search' => $search,
                    'status' => $status,
                    'page' => $page,
                    'lastPage' => $lastPage,
                    'total' => $total,
                ],
                'layouts.app',
            ),
        );
    }

    /**
     * Search active members for the loan application picker.
     */
    public function memberSearch(Request $request): Response
    {
        $query = trim(
            (string) $request->query(
                'q',
                '',
            ),
        );

        if ($query === '' || mb_strlen($query) < 2) {
            return new Response(
                json_encode(
                    ['members' => []],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
                200,
                [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Cache-Control' => 'no-store',
                ],
            );
        }

        $members = $this->memberService->all(
            search: $query,
            status: 'Active',
            limit: 20,
            offset: 0,
        );

        $result = array_map(
            static function (array $member): array {
                return [
                    'id' => (int) ($member['id'] ?? 0),
                    'member_number' =>
                        (string) ($member['member_number'] ?? ''),
                    'name' =>
                        (string) ($member['full_name'] ?? ''),
                ];
            },
            $members,
        );

        return new Response(
            json_encode(
                ['members' => $result],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ),
            200,
            [
                'Content-Type' =>
                    'application/json; charset=utf-8',
                'Cache-Control' =>
                    'no-store',
            ],
        );
    }

    public function create(Request $request): Response
    {
        // Members are loaded on demand by the searchable picker.
        return new Response(
            $this->view->render(
                'loans.create',
                [
                    'title' => 'Create Loan',
                    'members' => [],
                ],
                'layouts.app',
            ),
        );
    }

    public function store(Request $request): Response
    {
        try {
            $loan = LoanData::fromRequest($request);
            $loanId = $this->loanService->create($loan);

            return Response::redirect(
                '/loans/' . $loanId . '/review'
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->session->put(
                'loan_error',
                $exception->getMessage(),
            );

            return Response::redirect('/loans/create');
        }
    }

    public function review(Request $request, string $id): Response
    {
        $loanId = (int) $id;

        if ($loanId <= 0) {
            return Response::redirect('/loans/create');
        }

        $loan = $this->loanService->find($loanId);

        if ($loan === null) {
            return Response::redirect('/loans/create');
        }

        $memberId = (int) ($loan['member_id'] ?? 0);
        $member = $memberId > 0
            ? $this->memberService->find($memberId)
            : null;

        $schedule = $this->previewSchedule($loan);

        return new Response(
            $this->view->render(
                'loans.review',
                [
                    'title' => 'Review Loan Application',
                    'loan' => $loan,
                    'member' => $member,
                    'schedule' => $schedule,
                    'submitted' => (string) $request->query(
                        'submitted',
                        '',
                    ) === '1',
                ],
                'layouts.app',
            ),
        );
    }

    public function submit(Request $request, string $id): Response
    {
        $loanId = (int) $id;

        if ($loanId <= 0) {
            return Response::redirect('/loans');
        }

        try {
            $this->loanService->submit($loanId);

            return Response::redirect(
                '/loans/' . $loanId . '/show'
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->session->put(
                'loan_error',
                $exception->getMessage(),
            );

            return Response::redirect(
                '/loans/' . $loanId . '/review'
            );
        }
    }

    public function show(Request $request, string $id): Response
    {
        $loanId = (int) $id;

        if ($loanId <= 0) {
            return Response::redirect('/loans');
        }

        $loan = $this->loanService->find($loanId);

        if ($loan === null) {
            return Response::redirect('/loans');
        }

        $member = $this->memberService->find(
            (int) ($loan['member_id'] ?? 0)
        );

        $loanStatus = (string) ($loan['loan_status'] ?? '');

        $schedule = in_array(
            $loanStatus,
            ['Active', 'Fully Paid'],
            true,
        )
            ? $this->paymentRepository->amortizations($loanId)
            : $this->previewSchedule($loan);

        $error = $this->session->get('loan_error');
        $this->session->forget('loan_error');

        return new Response(
            $this->view->render(
                'loans.show',
                [
                    'title' => 'Loan Application',
                    'loan' => $loan,
                    'member' => $member,
                    'schedule' => $schedule,
                    'payments' => $this->paymentService->payments($loanId),
                    'error' => $error,
                    'success' => $request->query('success', ''),
                ],
                'layouts.app',
            ),
        );
    }

    public function reversePayment(Request $request, string $id): Response
    {
        $paymentId = (int) $id;
        $reason = trim((string) $request->input('reason', ''));

        try {
            $result = $this->paymentService->reverse(
                paymentId: $paymentId,
                reason: $reason,
            );

            return Response::redirect(
                '/loans/' . (int) $result['loan_id']
                . '/show?success=payment-reversed'
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->session->put('loan_error', $exception->getMessage());

            $payment = $this->paymentService->payment($paymentId);
            $loanId = (int) ($payment['loan_id'] ?? 0);

            return Response::redirect(
                $loanId > 0 ? '/loans/' . $loanId . '/show' : '/loans'
            );
        }
    }

    public function payment(Request $request, string $id): Response
    {
        $loanId = (int) $id;
        $amount = (float) str_replace(
            ',',
            '',
            (string) $request->input('amount_paid', '0'),
        );
        $remarks = trim((string) $request->input('remarks', ''));

        try {
            $result = $this->paymentService->apply(
                loanId: $loanId,
                amountPaid: $amount,
                remarks: $remarks !== '' ? $remarks : null,
            );

            $message = ($result['loan_fully_paid'] ?? false)
                ? 'Payment applied successfully. The loan is now Fully Paid.'
                : 'Payment applied successfully.';

            return Response::redirect(
                '/loans/' . $loanId
                . '/show?success=' . urlencode($message)
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->session->put('loan_error', $exception->getMessage());

            return Response::redirect('/loans/' . $loanId . '/show');
        }
    }

    public function release(Request $request, string $id): Response
    {
        $loanId = (int) $id;
        $releaseDate = trim((string) $request->input('release_date', ''));

        try {
            $this->loanService->release(
                $loanId,
                $releaseDate !== '' ? $releaseDate : null,
            );

            return Response::redirect(
                '/loans/' . $loanId . '/show?success=released'
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->session->put('loan_error', $exception->getMessage());

            return Response::redirect('/loans/' . $loanId . '/show');
        }
    }

    public function approve(Request $request, string $id): Response
    {
        $loanId = (int) $id;

        try {
            $this->loanService->approve($loanId);

            return Response::redirect(
                '/loans/' . $loanId . '/show?success=approved'
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->session->put(
                'loan_error',
                $exception->getMessage(),
            );

            return Response::redirect('/loans/' . $loanId . '/show');
        }
    }

    public function reject(Request $request, string $id): Response
    {
        $loanId = (int) $id;
        $reason = trim((string) $request->input('reason', ''));

        try {
            $this->loanService->reject($loanId, $reason);

            return Response::redirect(
                '/loans/' . $loanId . '/show?success=rejected'
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->session->put(
                'loan_error',
                $exception->getMessage(),
            );

            return Response::redirect('/loans/' . $loanId . '/show');
        }
    }

    public function statementOfAccount(
        Request $request,
        string $id,
    ): Response {
        $loanId = (int) $id;

        try {
            $soa = $this->soaService->build($loanId);
            $binary = $this->soaXlsx->build($soa);

            return new Response(
                content: $binary,
                status: 200,
                headers: [
                    'Content-Type' =>
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' =>
                        'attachment; filename="SOA_Loan_' . $loanId . '.xlsx"',
                    'Content-Length' => (string) strlen($binary),
                ],
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->session->put('loan_error', $exception->getMessage());

            return Response::redirect('/loans/' . $loanId . '/show');
        }
    }

    /**
     * @param array<string, mixed> $loan
     * @return array<int, array<string, mixed>>
     */
    private function previewSchedule(array $loan): array
    {
        $memberId = (int) ($loan['member_id'] ?? 0);

        try {
            $loanData = LoanData::fromArray([
                'member_id' => $memberId,
                'loan_type' => (string) ($loan['loan_type'] ?? ''),
                'collateral' => (string) ($loan['collateral'] ?? ''),
                'principal_amount' => (float) ($loan['principal_amount'] ?? 0),
                'interest_rate' => (float) ($loan['interest_rate'] ?? 0),
                'amortization_type' => $loan['amortization_type'] !== null
                    ? (string) $loan['amortization_type']
                    : null,
                'payment_frequency' => $loan['payment_frequency'] !== null
                    ? (string) $loan['payment_frequency']
                    : null,
                'terms_months' => (int) ($loan['terms_months'] ?? 0),
                'start_date' => (string) ($loan['start_date'] ?? ''),
                'manual_payment' => $loan['manual_payment'] !== null
                    ? (float) $loan['manual_payment']
                    : null,
                'tct_no' => $loan['tct_no'] ?? null,
                'tax_declaration_no' => $loan['tax_declaration_no'] ?? null,
                'real_property_payment_status' => $loan['real_property_payment_status'] ?? null,
                'notes' => $loan['notes'] ?? null,
            ]);

            return $this->amortization->generate($loanData);
        } catch (\Throwable) {
            return [];
        }
    }
}
