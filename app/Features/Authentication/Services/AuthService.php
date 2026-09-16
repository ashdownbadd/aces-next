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

    public function login(string $username, string $password): bool
    {
        $username = trim($username);
        $ipAddress = substr(
            (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
            0,
            45,
        );

        if ($this->isRateLimited($username, $ipAddress)) {
            return false;
        }

        $user = $this->users->findByUsername($username);

        if (
            $user === null
            || ! $user->isActive()
            || ! password_verify($password, $user->password())
        ) {
            $this->recordAttempt($username, $ipAddress, false);
            return false;
        }

        $this->recordAttempt($username, $ipAddress, true);

        $this->session->put('user_id', $user->id());
        $this->session->regenerate();

        return true;
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
