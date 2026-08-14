<?php

declare(strict_types=1);

namespace App\Features\Members\Controllers;

use App\Features\Members\DTOs\BeneficiaryData;
use App\Features\Members\Services\BeneficiaryService;
use App\Features\Members\Support\EditSession;
use App\Http\Request;
use App\Http\Response;

final class BeneficiaryController
{
    public function __construct(
        private readonly BeneficiaryService $beneficiaryService,
        private readonly EditSession $editSession,
    ) {}

    /**
     * Store a new beneficiary.
     */
    public function store(Request $request): Response
    {
        $this->beneficiaryService->add(
            BeneficiaryData::fromRequest(
                $request
            )->toArray(),
        );

        if ($this->editSession->has()) {
            return Response::redirect(
                '/members/create?step=beneficiaries&edit='
                    . $this->editSession->memberId(),
            );
        }

        return Response::redirect(
            '/members/create?step=beneficiaries',
        );
    }

    /**
     * Update an existing beneficiary.
     */
    public function update(
        Request $request,
    ): Response {
        $index = (int) $request->input(
            'index',
            -1,
        );

        if ($index < 0) {
            return Response::redirect(
                '/members/create?step=beneficiaries',
            );
        }

        $this->beneficiaryService->update(
            $index,
            BeneficiaryData::fromRequest(
                $request
            )->toArray(),
        );

        if ($this->editSession->has()) {
            return Response::redirect(
                '/members/create?step=beneficiaries&edit='
                    . $this->editSession->memberId(),
            );
        }

        return Response::redirect(
            '/members/create?step=beneficiaries',
        );
    }

    /**
     * Remove a beneficiary.
     */
    public function destroy(
        Request $request,
    ): Response {
        $index = (int) $request->input(
            'index',
            -1,
        );

        if ($index < 0) {
            return Response::redirect(
                '/members/create?step=beneficiaries',
            );
        }

        $this->beneficiaryService->delete(
            $index,
        );

        if ($this->editSession->has()) {
            return Response::redirect(
                '/members/create?step=beneficiaries&edit='
                    . $this->editSession->memberId(),
            );
        }

        return Response::redirect(
            '/members/create?step=beneficiaries',
        );
    }
}
