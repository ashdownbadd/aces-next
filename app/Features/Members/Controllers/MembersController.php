<?php

declare(strict_types=1);

namespace App\Features\Members\Controllers;

use App\Features\Members\Services\MemberService;
use App\Foundation\View;
use App\Http\Response;

final class MembersController
{
    public function __construct(
        private readonly View $view,
        private readonly MemberService $memberService,
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
}
