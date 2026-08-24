<?php

declare(strict_types=1);

namespace App\Features\Ledger\Controllers;

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Session;
use App\Foundation\View;
use App\Http\Request;
use App\Http\Response;
use InvalidArgumentException;
use RuntimeException;

final class LedgerController
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly View $view,
        private readonly JournalVoucherRepository $repository,
        private readonly LedgerService $ledger,
        private readonly Session $session,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $page = max(1, (int) $request->query('page', '1'));

        $statuses = ['Pending', 'Approved', 'Rejected', 'Posted'];

        if ($status !== '' && !in_array($status, $statuses, true)) {
            $status = '';
        }

        $total = $this->repository->count($search, $status);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);

        return new Response(
            $this->view->render(
                'ledger.index',
                [
                    'title' => 'Journal Vouchers',
                    'vouchers' => $this->repository->all(
                        $search,
                        $status,
                        self::PER_PAGE,
                        ($page - 1) * self::PER_PAGE,
                    ),
                    'search' => $search,
                    'status' => $status,
                    'statuses' => $statuses,
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'total' => $total,
                ],
                'layouts.app',
            ),
        );
    }

    public function show(Request $request, string $id): Response
    {
        $voucherId = (int) $id;

        if ($voucherId <= 0) {
            return Response::redirect('/ledger');
        }

        $voucher = $this->repository->find($voucherId);

        if ($voucher === null) {
            return Response::redirect('/ledger');
        }

        $lines = $this->repository->lines($voucherId);
        $debitTotal = 0.00;
        $creditTotal = 0.00;

        foreach ($lines as $line) {
            $debitTotal += (float) $line['debit'];
            $creditTotal += (float) $line['credit'];
        }

        $error = $this->session->get('ledger_error');
        $this->session->forget('ledger_error');

        $success = $this->session->get('ledger_success');
        $this->session->forget('ledger_success');

        return new Response(
            $this->view->render(
                'ledger.show',
                [
                    'title' => 'Journal Voucher',
                    'voucher' => $voucher,
                    'lines' => $lines,
                    'debitTotal' => round($debitTotal, 2),
                    'creditTotal' => round($creditTotal, 2),
                    'error' => $error,
                    'success' => $success,
                ],
                'layouts.app',
            ),
        );
    }

    public function approve(Request $request, string $id): Response
    {
        return $this->action(
            (int) $id,
            fn(int $voucherId) => $this->ledger->approve(
                $voucherId,
                $this->actorId(),
                $this->now(),
            ),
            'Journal voucher approved.',
        );
    }

    public function reject(Request $request, string $id): Response
    {
        $voucherId = (int) $id;

        try {
            $this->ledger->reject(
                $voucherId,
                trim((string) $request->input('reason', '')),
            );
            $this->session->put('ledger_success', 'Journal voucher rejected.');
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->session->put('ledger_error', $exception->getMessage());
        }

        return Response::redirect('/ledger/' . $voucherId);
    }

    public function post(Request $request, string $id): Response
    {
        return $this->action(
            (int) $id,
            fn(int $voucherId) => $this->ledger->post(
                $voucherId,
                $this->actorId(),
                $this->now(),
            ),
            'Journal voucher posted to the ledger.',
        );
    }

    public function general(Request $request): Response
    {
        $accounts = $this->repository->accounts();

        $accountId = max(
            0,
            (int) $request->query('account', '0'),
        );

        $dateFrom = trim(
            (string) $request->query('date_from', '')
        );

        $dateTo = trim(
            (string) $request->query('date_to', '')
        );

        $ledger = null;

        if ($accountId > 0) {
            try {
                $ledger = $this->ledger->generalLedger(
                    accountId: $accountId,
                    dateFrom: $dateFrom !== '' ? $dateFrom : null,
                    dateTo: $dateTo !== '' ? $dateTo : null,
                );
            } catch (
                InvalidArgumentException |
                RuntimeException $exception
            ) {
                $this->session->put(
                    'ledger_error',
                    $exception->getMessage(),
                );

                return Response::redirect('/ledger/general');
            }
        }

        $error = $this->session->get('ledger_error');
        $this->session->forget('ledger_error');

        return new Response(
            $this->view->render(
                'ledger.general',
                [
                    'title' => 'General Ledger',
                    'accounts' => $accounts,
                    'accountId' => $accountId,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'ledger' => $ledger,
                    'error' => $error,
                ],
                'layouts.app',
            ),
        );
    }

    public function trialBalance(Request $request): Response
    {
        $asOfDate = trim(
            (string) $request->query('as_of', '')
        );

        $result = null;
        $error = $this->session->get('ledger_error');
        $this->session->forget('ledger_error');

        if ($asOfDate !== '') {
            try {
                $result = $this->ledger->trialBalance(
                    asOfDate: $asOfDate,
                );
            } catch (
                InvalidArgumentException |
                RuntimeException $exception
            ) {
                $error = $exception->getMessage();
            }
        }

        return new Response(
            $this->view->render(
                'ledger.trial_balance',
                [
                    'title' => 'Trial Balance',
                    'asOfDate' => $asOfDate,
                    'trialBalance' => $result,
                    'error' => $error,
                ],
                'layouts.app',
            ),
        );
    }

    public function accounts(Request $request): Response
    {
        return new Response(
            $this->view->render(
                'ledger.accounts',
                [
                    'title' => 'Chart of Accounts',
                    'accounts' => $this->repository->accounts(),
                ],
                'layouts.app',
            ),
        );
    }

    private function action(int $voucherId, callable $callback, string $success): Response
    {
        try {
            $callback($voucherId);
            $this->session->put('ledger_success', $success);
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->session->put('ledger_error', $exception->getMessage());
        }

        return Response::redirect('/ledger/' . $voucherId);
    }

    private function actorId(): int
    {
        $userId = $this->session->get('user_id');

        if ($userId === null || (int) $userId <= 0) {
            throw new RuntimeException(
                'An authenticated user is required for Ledger actions.'
            );
        }

        return (int) $userId;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
