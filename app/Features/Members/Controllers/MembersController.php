<?php

declare(strict_types=1);

namespace App\Features\Members\Controllers;

use App\Features\Members\DTOs\AddressData;
use App\Features\Members\DTOs\ContactData;
use App\Features\Members\DTOs\EducationData;
use App\Features\Members\DTOs\LivelihoodData;
use App\Features\Members\DTOs\MembershipData;
use App\Features\Members\DTOs\PersonalData;
use App\Features\Members\Services\EditService;
use App\Features\Members\Services\MemberService;
use App\Features\Members\Services\RegistrationService;
use App\Features\Members\Support\RegistrationWorkflow;
use App\Foundation\Session;
use App\Foundation\View;
use App\Http\Request;
use App\Http\Response;

final class MembersController
{
    public function __construct(
        private readonly View $view,
        private readonly MemberService $memberService,
        private readonly RegistrationService $registrationService,
        private readonly Session $session,
        private readonly EditService $editService,
    ) {}

    /**
     * Display the members list.
     */
    public function index(
        Request $request,
    ): Response {
        $successMessage = $this->session->get(
            'members_success',
        );

        $this->session->forget(
            'members_success',
        );

        $search = trim(
            (string) $request->query(
                'search',
                '',
            )
        );

        $status = trim(
            (string) $request->query(
                'status',
                '',
            )
        );

        if (! in_array(
            $status,
            [
                '',
                'Pending',
                'Active',
                'Inactive',
            ],
            true,
        )) {
            $status = '';
        }

        $page = max(
            1,
            (int) $request->query(
                'page',
                '1',
            ),
        );

        $perPage = 25;

        $totalMembers =
            $this->memberService->count();

        $resultCount =
            $this->memberService->count(
                $search,
                $status,
            );

        $totalPages = max(
            1,
            (int) ceil(
                $resultCount / $perPage
            ),
        );

        /*
    |--------------------------------------------------------------------------
    | Prevent invalid pages
    |--------------------------------------------------------------------------
    */

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset =
            ($page - 1) * $perPage;

        $members =
            $this->memberService->all(
                $search,
                $status,
                $perPage,
                $offset,
            );

        $from = $resultCount > 0
            ? $offset + 1
            : 0;

        $to = $resultCount > 0
            ? min(
                $offset + count($members),
                $resultCount,
            )
            : 0;

        return new Response(
            $this->view->render(
                'members.index',
                [
                    'title' =>
                    'Members',

                    'members' =>
                    $members,

                    'totalMembers' =>
                    $totalMembers,

                    'resultCount' =>
                    $resultCount,

                    'search' =>
                    $search,

                    'status' =>
                    $status,

                    'currentPage' =>
                    $page,

                    'perPage' =>
                    $perPage,

                    'totalPages' =>
                    $totalPages,

                    'from' =>
                    $from,

                    'to' =>
                    $to,

                    'successMessage' =>
                    $successMessage,
                ],
                'layouts.app',
            ),
        );
    }

    /**
     * Display a single member profile.
     */
    public function show(
        Request $request,
        string $id,
    ): Response {
        $memberId = (int) $id;

        if ($memberId <= 0) {
            return Response::redirect('/members');
        }

        $member = $this->memberService->find(
            $memberId,
        );

        if ($member === null) {
            return Response::redirect('/members');
        }

        return new Response(
            $this->view->render(
                'members.show',
                [
                    'title' => 'Member Profile',
                    'member' => $member,
                ],
                'layouts.app',
            ),
        );
    }

    /**
     * Change the status of an existing member.
     */
    public function changeStatus(
        Request $request,
        string $id,
    ): Response {
        $memberId = (int) $id;

        if ($memberId <= 0) {
            return Response::redirect('/members');
        }

        $status = trim(
            (string) $request->input(
                'status',
                '',
            ),
        );

        try {
            $this->memberService->changeStatus(
                $memberId,
                $status,
            );

            $this->session->put(
                'members_success',
                'Member status updated successfully.',
            );
        } catch (\InvalidArgumentException $exception) {
            $this->session->put(
                'members_error',
                $exception->getMessage(),
            );
        } catch (\RuntimeException $exception) {
            $this->session->put(
                'members_error',
                $exception->getMessage(),
            );
        }

        return Response::redirect(
            '/members/' . $memberId,
        );
    }

    /**
     * Start editing an existing member.
     */
    public function edit(
        Request $request,
        string $id,
    ): Response {
        $memberId = (int) $id;

        if ($memberId <= 0) {
            return Response::redirect('/members');
        }

        if (! $this->editService->start($memberId)) {
            return Response::redirect('/members');
        }

        return Response::redirect(
            '/members/create?step=membership&edit=' . $memberId,
        );
    }

    /**
     * Display the member registration/edit wizard.
     */
    public function create(Request $request): Response
    {
        /*
    |--------------------------------------------------------------------------
    | Start a new registration
    |--------------------------------------------------------------------------
    |
    | The "new=1" parameter is only sent when the user explicitly
    | clicks "Register Member".
    |
    | This prevents an unfinished previous registration from
    | appearing in a brand-new registration.
    |
    */

        $isNewRegistration =
            (string) $request->query('new', '') === '1';

        if ($isNewRegistration) {
            $this->registrationService->clear();

            /*
        | If an edit session exists, clear that too.
        | This prevents an old edit session from being mistaken
        | for a new registration.
        */
            if (isset($this->editService)) {
                $this->editService->clear();
            }
        }

        $step = $this->resolveStep($request);

        $isEditing = $this->editService->has();

        $registration = $isEditing
            ? $this->editService->all()
            : $this->registrationService->all();

        return new Response(
            $this->view->render(
                'members.create',
                [
                    'title' => $isEditing
                        ? 'Edit Member'
                        : 'Register Member',

                    'step' => $step,

                    'steps' =>
                    RegistrationWorkflow::all(),

                    'previousStep' =>
                    RegistrationWorkflow::previous($step),

                    'nextStep' =>
                    RegistrationWorkflow::next($step),

                    'isEditing' =>
                    $isEditing,

                    'editMemberId' =>
                    $isEditing
                        ? $this->editService->memberId()
                        : null,

                    'registration' =>
                    $registration,

                    'membership' =>
                    $registration['membership'] ?? [],

                    'personal' =>
                    $registration['personal'] ?? [],

                    'contact' =>
                    $registration['contact'] ?? [],

                    'address' =>
                    $registration['address'] ?? [],

                    'livelihood' =>
                    $registration['livelihood'] ?? [],

                    'education' =>
                    $registration['education'] ?? [],

                    'beneficiaries' =>
                    $registration['beneficiaries'] ?? [],
                ],
                'layouts.app',
            ),
        );
    }

    /**
     * Store the current wizard step.
     */
    public function storeStep(
        Request $request,
    ): Response {
        $step = $this->resolveStep($request);

        $isEditing = $this->editService->has();

        switch ($step) {

            case 'membership':

                $data = MembershipData::fromRequest(
                    $request
                )->toArray();

                break;

            case 'personal':

                $data = PersonalData::fromRequest(
                    $request
                )->toArray();

                break;

            case 'contact':

                $data = ContactData::fromRequest(
                    $request
                )->toArray();

                break;

            case 'address':

                $data = AddressData::fromRequest(
                    $request
                )->toArray();

                break;

            case 'livelihood':

                $data = LivelihoodData::fromRequest(
                    $request
                )->toArray();

                break;

            case 'education':

                $data = EducationData::fromRequest(
                    $request
                )->toArray();

                break;

            case 'beneficiaries':
            case 'review':

                $data = [];

                break;

            default:

                $data = [];

                break;
        }

        if ($data !== []) {

            if ($isEditing) {
                $this->editService->saveStep(
                    $step,
                    $data,
                );
            } else {
                $this->registrationService->saveStep(
                    $step,
                    $data,
                );
            }
        }

        $nextStep =
            RegistrationWorkflow::next($step)
            ?? $step;

        $query = http_build_query([
            'step' => $nextStep,
        ]);

        /*
    |--------------------------------------------------------------------------
    | Preserve edit mode
    |--------------------------------------------------------------------------
    */

        if ($isEditing) {
            $query = http_build_query([
                'step' => $nextStep,
                'edit' => $this->editService->memberId(),
            ]);
        }

        return Response::redirect(
            '/members/create?' . $query,
        );
    }

    /**
     * Complete the member registration.
     */
    public function register(): Response
    {
        /*
    |--------------------------------------------------------------------------
    | Edit existing member
    |--------------------------------------------------------------------------
    */

        if ($this->editService->has()) {

            $memberId =
                $this->editService->memberId();

            $this->editService->update();

            $this->session->put(
                'members_success',
                "Member #{$memberId} updated successfully.",
            );

            return Response::redirect(
                '/members/' . $memberId,
            );
        }

        /*
    |--------------------------------------------------------------------------
    | New member registration
    |--------------------------------------------------------------------------
    */

        $memberNumber =
            $this->memberService->nextMemberNumber();

        $this->registrationService->register(
            $memberNumber,
            'Pending',
        );

        $this->session->put(
            'members_success',
            "Member {$memberNumber} registered successfully.",
        );

        return Response::redirect('/members');
    }

    /**
     * Resolve and validate the current wizard step.
     */
    private function resolveStep(
        Request $request,
    ): string {
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
