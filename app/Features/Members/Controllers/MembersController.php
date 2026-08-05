<?php

declare(strict_types=1);

namespace App\Features\Members\Controllers;

use App\Features\Members\DTOs\AddressData;
use App\Features\Members\DTOs\ContactData;
use App\Features\Members\DTOs\EducationData;
use App\Features\Members\DTOs\LivelihoodData;
use App\Features\Members\DTOs\MembershipData;
use App\Features\Members\DTOs\PersonalData;
use App\Features\Members\Services\MemberService;
use App\Features\Members\Services\RegistrationService;
use App\Features\Members\Support\RegistrationWorkflow;
use App\Foundation\View;
use App\Http\Request;
use App\Http\Response;

final class MembersController
{
    public function __construct(
        private readonly View $view,
        private readonly MemberService $memberService,
        private readonly RegistrationService $registrationService,
    ) {}

    public function index(): Response
    {
        return new Response(
            $this->view->render(
                'members.index',
                [
                    'title' => 'Members',
                    'members' => $this->memberService->all(),
                    'totalMembers' => $this->memberService->count(),
                ],
                'layouts.app',
            ),
        );
    }

    public function create(Request $request): Response
    {
        $step = $this->resolveStep($request);

        $registration = $this->registrationService->all();

        return new Response(
            $this->view->render(
                'members.create',
                [
                    'title' => 'Register Member',

                    'step' => $step,

                    'steps' => RegistrationWorkflow::all(),

                    'previousStep' => RegistrationWorkflow::previous($step),

                    'nextStep' => RegistrationWorkflow::next($step),

                    /*
                |--------------------------------------------------------------------------
                | Registration Data
                |--------------------------------------------------------------------------
                */

                    'membership' => $registration['membership'] ?? [],

                    'personal' => $registration['personal'] ?? [],

                    'contact' => $registration['contact'] ?? [],

                    'address' => $registration['address'] ?? [],

                    'livelihood' => $registration['livelihood'] ?? [],

                    'education' => $registration['education'] ?? [],

                    'beneficiaries' => $registration['beneficiaries'] ?? [],
                ],
                'layouts.app',
            ),
        );
    }

    public function storeStep(Request $request): Response
    {
        $step = $this->resolveStep($request);

        switch ($step) {

            case 'membership':

                $this->registrationService->saveStep(
                    $step,
                    MembershipData::fromRequest($request)->toArray(),
                );

                break;

            case 'personal':

                $this->registrationService->saveStep(
                    $step,
                    PersonalData::fromRequest($request)->toArray(),
                );

                break;

            case 'contact':

                $this->registrationService->saveStep(
                    $step,
                    ContactData::fromRequest($request)->toArray(),
                );

                break;

            case 'address':

                $this->registrationService->saveStep(
                    $step,
                    AddressData::fromRequest($request)->toArray(),
                );

                break;

            case 'livelihood':

                $this->registrationService->saveStep(
                    $step,
                    LivelihoodData::fromRequest($request)->toArray(),
                );

                break;

            case 'education':

                $this->registrationService->saveStep(
                    $step,
                    EducationData::fromRequest($request)->toArray(),
                );

                break;

            /*
             |-------------------------------------------------------------
             | Beneficiaries
             |-------------------------------------------------------------
             |
             | We'll implement this separately because it manages
             | multiple records instead of a single DTO.
             |
             */

            case 'beneficiaries':

                break;

            case 'review':

                break;
        }

        return Response::redirect(
            '/members/create?step=' . urlencode(
                RegistrationWorkflow::next($step) ?? $step,
            ),
        );
    }

    private function resolveStep(Request $request): string
    {
        $step = (string) $request->query(
            'step',
            RegistrationWorkflow::first(),
        );

        if (! RegistrationWorkflow::isValid($step)) {
            return RegistrationWorkflow::first();
        }

        return $step;
    }
}
