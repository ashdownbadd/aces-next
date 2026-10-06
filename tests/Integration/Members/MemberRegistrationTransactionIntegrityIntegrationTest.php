<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Foundation\Config;
use App\Foundation\Database;
use App\Features\Members\DTOs\AddressData;
use App\Features\Members\DTOs\BeneficiaryData;
use App\Features\Members\DTOs\ContactData;
use App\Features\Members\DTOs\EducationData;
use App\Features\Members\DTOs\LivelihoodData;
use App\Features\Members\DTOs\MemberRegistrationData;
use App\Features\Members\DTOs\MembershipData;
use App\Features\Members\DTOs\PersonalData;
use App\Features\Members\Repositories\MemberRepository;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            '%s Expected %s, got %s.',
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function assertThrows(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        return;
    }

    throw new RuntimeException(
        $message . ' Expected an exception.'
    );
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();
$repository = new MemberRepository($database);

$triggerName = 'qa_member_registration_rollback_trigger_' . bin2hex(random_bytes(4));
$marker = 'QA-TXN-' . bin2hex(random_bytes(6));

$registration = new MemberRegistrationData(
    membership: new MembershipData(
        membershipDate: '2026-10-06',
        membershipType: 'regular',
    ),
    personal: new PersonalData(
        firstName: $marker,
        middleName: '',
        lastName: 'Rollback',
        suffix: '',
        birthDate: '1995-01-01',
        birthPlace: 'QA',
        sex: 'Male',
        civilStatus: 'Single',
    ),
    contact: new ContactData(
        mobileNumber: '09170000000',
        telephoneNumber: '',
        emailAddress: $marker . '@example.test',
    ),
    address: new AddressData(
        houseNumber: '1',
        street: 'QA Street',
        barangay: 'QA Barangay',
        city: 'QA City',
        province: 'QA Province',
        zipCode: '6000',
    ),
    livelihood: new LivelihoodData(
        livelihoodType: 'Employed',
        occupation: 'QA Tester',
        employer: 'ACES QA',
        monthlyIncome: '10000',
    ),
    education: new EducationData(
        highestEducationalAttainment: 'College',
        schoolName: 'QA University',
        graduationYear: 2018,
    ),
    beneficiaries: [
        new BeneficiaryData(
            firstName: 'QA',
            middleName: '',
            lastName: 'Beneficiary',
            suffix: '',
            relationship: 'Sibling',
            birthDate: '2000-01-01',
        ),
    ],
);

try {
    echo "================================================\n";
    echo "ACES MEMBER REGISTRATION TRANSACTION INTEGRITY TEST\n";
    echo "================================================\n";

    $sequenceBefore = (int) $pdo->query(
        'SELECT next_number FROM member_number_sequences WHERE id = 1'
    )->fetchColumn();

    $memberCountBefore = (int) $pdo->query(
        'SELECT COUNT(*) FROM members'
    )->fetchColumn();

    $pdo->exec(
        "CREATE TRIGGER `{$triggerName}`
         BEFORE INSERT ON member_addresses
         FOR EACH ROW
         SIGNAL SQLSTATE '45000'
         SET MESSAGE_TEXT = 'Intentional QA registration failure'"
    );

    assertThrows(
        fn() => $repository->create($registration, 'Pending'),
        'Registration failure must abort the complete transaction.',
    );

    $memberCountAfter = (int) $pdo->query(
        'SELECT COUNT(*) FROM members'
    )->fetchColumn();

    $sequenceAfter = (int) $pdo->query(
        'SELECT next_number FROM member_number_sequences WHERE id = 1'
    )->fetchColumn();

    assertSameValue(
        $memberCountBefore,
        $memberCountAfter,
        'Member row must roll back.',
    );

    assertSameValue(
        $sequenceBefore,
        $sequenceAfter,
        'Member number sequence must roll back.',
    );

    $profileCount = (int) $pdo->query(
        "SELECT COUNT(*)
         FROM member_profiles
         WHERE first_name = " . $pdo->quote($marker)
    )->fetchColumn();

    assertSameValue(
        0,
        $profileCount,
        'Personal profile must not survive a failed registration.',
    );

    $contactCount = (int) $pdo->query(
        "SELECT COUNT(*)
         FROM member_contacts
         WHERE email_address = " . $pdo->quote($marker . '@example.test')
    )->fetchColumn();

    assertSameValue(
        0,
        $contactCount,
        'Contact record must not survive a failed registration.',
    );

    echo "Registration failure             → transaction aborted ✓\n";
    echo "Member record                     → rolled back ✓\n";
    echo "Member number sequence            → rolled back ✓\n";
    echo "Personal profile                  → rolled back ✓\n";
    echo "Contact record                    → rolled back ✓\n";
    echo "================================================\n";
    echo "ACES MEMBER REGISTRATION TRANSACTION INTEGRITY TEST: PASS\n";
    echo "================================================\n";
} finally {
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS `{$triggerName}`");
    } catch (Throwable) {
        // Best-effort cleanup.
    }
}
