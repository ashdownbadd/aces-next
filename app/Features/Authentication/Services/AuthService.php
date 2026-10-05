<?php

declare(strict_types=1);

namespace App\Features\Authentication\Services;

use App\Domain\Authentication\User;
use App\Features\Authentication\Repositories\UserRepository;
use App\Foundation\Session;
use App\Foundation\Database;
use DateTimeImmutable;

final readonly class AuthService
{
    public function __construct(
        private UserRepository $users,
        private Session $session,
        private Database $database,
    ) {}

    public function login(string $username, string $password): string
    {
        $username = trim($username);
        $ipAddress = substr(
            (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
            0,
            45,
        );

        if ($this->isRateLimited($username, $ipAddress)) {
            return 'rate_limited';
        }

        $user = $this->users->findByUsername($username);

        if (
            $user === null
            || ! $user->isActive()
            || ! password_verify($password, $user->password())
        ) {
            $this->recordAttempt($username, $ipAddress, false);
            return 'invalid';
        }

        $this->recordAttempt($username, $ipAddress, true);

        $this->session->regenerate();
        $this->session->put('user_id', $user->id());
        $this->session->put('user_name', $user->fullName());
        $this->session->save();

        return 'success';
    }

    private function isRateLimited(string $username, string $ipAddress): bool
    {
        $statement = $this->database->connection()->prepare(
            'SELECT COUNT(*)
             FROM login_attempts
             WHERE username = :username
               AND ip_address = :ip_address
               AND was_successful = 0
               AND attempted_at >= :cutoff'
        );

        $statement->execute([
            'username' => $username,
            'ip_address' => $ipAddress,
            'cutoff' => (new DateTimeImmutable('-15 minutes'))->format('Y-m-d H:i:s'),
        ]);

        if ((int) $statement->fetchColumn() >= 5) {
            return true;
        }

        // Also cap failures from one IP across many usernames.
        $statement = $this->database->connection()->prepare(
            'SELECT COUNT(*)
             FROM login_attempts
             WHERE ip_address = :ip_address
               AND was_successful = 0
               AND attempted_at >= :cutoff'
        );

        $statement->execute([
            'ip_address' => $ipAddress,
            'cutoff' => (new DateTimeImmutable('-15 minutes'))->format('Y-m-d H:i:s'),
        ]);

        return (int) $statement->fetchColumn() >= 20;
    }

    private function recordAttempt(
        string $username,
        string $ipAddress,
        bool $successful,
    ): void {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO login_attempts
                (username, ip_address, attempted_at, was_successful)
             VALUES
                (:username, :ip_address, CURRENT_TIMESTAMP, :was_successful)'
        );

        $statement->execute([
            'username' => $username,
            'ip_address' => $ipAddress,
            'was_successful' => $successful ? 1 : 0,
        ]);

        if ($successful) {
            $cleanup = $this->database->connection()->prepare(
                'DELETE FROM login_attempts
                 WHERE username = :username
                   AND ip_address = :ip_address
                   AND was_successful = 0'
            );
            $cleanup->execute([
                'username' => $username,
                'ip_address' => $ipAddress,
            ]);
        }
    }

    public function updateCredentials(
        int $userId,
        string $username,
        string $currentPassword,
        string $newPassword,
    ): void {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new \InvalidArgumentException(
                'Unable to update the account.'
            );
        }

        if ($this->users->usernameExists($username, $userId)) {
            throw new \InvalidArgumentException(
                'That username is already in use.'
            );
        }

        $passwordHash = null;

        if ($newPassword !== '') {
            if (!password_verify($currentPassword, $user->password())) {
                throw new \InvalidArgumentException(
                    'The current password is incorrect.'
                );
            }

            if ($newPassword === $currentPassword) {
                throw new \InvalidArgumentException(
                    'New password must be different from the current password.'
                );
            }

            $passwordHash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT,
            );
        }

        $connection = $this->database->connection();
        $connection->beginTransaction();

        try {
            $this->users->updateUsername($userId, $username);

            if ($passwordHash !== null) {
                $this->users->updatePassword(
                    $userId,
                    $passwordHash,
                );
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    public function check(): bool
    {
        return $this->session->has('user_id');
    }

    public function user(): ?User
    {
        $id = $this->session->get('user_id');

        if ($id === null) {
            return null;
        }

        return $this->users->findById((int) $id);
    }
}
