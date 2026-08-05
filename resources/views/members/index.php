<?php

$title = 'Members';

?>

<div class="members">

    <div class="members__header">

        <div>

            <h1 class="members__title">
                Members
            </h1>

            <p class="members__description">
                Manage cooperative members.
            </p>

        </div>

        <a
            href="/members/create"
            class="btn btn--primary">
            Register Member
        </a>

    </div>

    <div class="card">

        <div class="members__toolbar">

            <input
                class="input members__search"
                type="search"
                placeholder="Search member...">

            <span class="members__total">
                Total: 0
            </span>

        </div>

        <table class="table">

            <thead>

                <tr>

                    <th>Member #</th>

                    <th>Name</th>

                    <th>Mobile</th>

                    <th>Status</th>

                    <th>Joined</th>

                    <th>Actions</th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td colspan="6">

                        <div class="members__empty">

                            <div class="members__empty-icon">

                                👥

                            </div>

                            <h3>

                                No members have been registered yet.

                            </h3>

                            <p>

                                Register your first cooperative member to get started.

                            </p>

                            <a
                                href="/members/create"
                                class="btn btn--primary">
                                Register Member
                            </a>

                        </div>

                    </td>

                </tr>

            </tbody>

        </table>

        <div class="members__footer">

            Showing 0 of 0 members

        </div>

    </div>

</div>