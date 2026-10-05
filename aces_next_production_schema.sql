-- ACES Next production schema
-- Schema only. No development/member/loan/payment/user data is included.
-- Create the database first, then import this file.
-- Configure the application via .env before running it.

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aces_next`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Income','Expense') NOT NULL,
  `normal_balance` enum('Debit','Credit') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

(50, 9, '2030', 'Unapplied Loan Payments', 'Liability', 'Credit', 1, '2026-09-16 00:00:00', '2026-09-16 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `subject_type` varchar(100) DEFAULT NULL,
  `subject_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

-- --------------------------------------------------------

--
-- Table structure for table `journal_lines`
--

CREATE TABLE `journal_lines` (
  `id` int(10) UNSIGNED NOT NULL,
  `journal_voucher_id` int(10) UNSIGNED NOT NULL,
  `account_id` int(10) UNSIGNED NOT NULL,
  `member_id` int(10) UNSIGNED DEFAULT NULL,
  `loan_id` int(10) UNSIGNED DEFAULT NULL,
  `line_description` varchar(255) DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `journal_lines`
--

-- --------------------------------------------------------

--
-- Table structure for table `journal_vouchers`
--

CREATE TABLE `journal_vouchers` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `transaction_date` date NOT NULL,
  `particulars` text NOT NULL,
  `source_type` varchar(100) DEFAULT NULL,
  `source_id` int(10) UNSIGNED DEFAULT NULL,
  `reversal_of_voucher_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Posted') NOT NULL DEFAULT 'Pending',
  `rejection_reason` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `posted_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `journal_vouchers`
--

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(10) UNSIGNED NOT NULL,
  `member_id` int(10) UNSIGNED NOT NULL,
  `loan_type` enum('Bridge Financing','Investment Loan','Pension Loan','Productivity Loan','Personal Loan','Salary Loan','Micro-Finance Loan') NOT NULL,
  `collateral` enum('Post-Dated Check','Real Property','Chattels / Movable Assets') NOT NULL,
  `application_status` enum('Pending','Under Review','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `loan_status` enum('Active','Fully Paid') DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(8,4) NOT NULL,
  `amortization_type` enum('Straight-line','Diminishing balance','Manual') DEFAULT NULL,
  `payment_frequency` enum('Monthly','Bi-Monthly','Weekly') DEFAULT NULL,
  `terms_months` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `release_date` date DEFAULT NULL,
  `manual_payment` decimal(15,2) DEFAULT NULL,
  `processing_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `insurance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notarial_fee` decimal(15,2) NOT NULL DEFAULT 400.00,
  `net_proceeds` decimal(15,2) DEFAULT NULL,
  `tct_no` varchar(100) DEFAULT NULL,
  `tax_declaration_no` varchar(100) DEFAULT NULL,
  `real_property_payment_status` enum('Updated','Not Updated','Pending') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `released_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `fully_paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loans`
--

-- --------------------------------------------------------

--
-- Table structure for table `loan_amortizations`
--

CREATE TABLE `loan_amortizations` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_id` int(10) UNSIGNED NOT NULL,
  `period` int(10) UNSIGNED NOT NULL,
  `due_date` date NOT NULL,
  `principal` decimal(15,2) NOT NULL,
  `interest` decimal(15,2) NOT NULL,
  `rem_principal` decimal(15,2) NOT NULL,
  `rem_interest` decimal(15,2) NOT NULL,
  `rem_penalty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `orig_penalty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Near-Due','Overdue','Paid') NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_amortizations`
--

-- --------------------------------------------------------

--
-- Table structure for table `loan_payments`
--

CREATE TABLE `loan_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_id` int(10) UNSIGNED NOT NULL,
  `idempotency_key` varchar(80) DEFAULT NULL,
  `payment_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `amount_paid` decimal(15,2) NOT NULL,
  `penalty_applied` decimal(15,2) NOT NULL DEFAULT 0.00,
  `interest_applied` decimal(15,2) NOT NULL DEFAULT 0.00,
  `principal_applied` decimal(15,2) NOT NULL DEFAULT 0.00,
  `excess` decimal(15,2) NOT NULL DEFAULT 0.00,
  `type` varchar(50) NOT NULL DEFAULT 'Global',
  `remarks` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversed_by` int(10) UNSIGNED DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_payments`
--

-- --------------------------------------------------------

--
-- Table structure for table `loan_payment_allocations`
--

CREATE TABLE `loan_payment_allocations` (
  `id` int(10) UNSIGNED NOT NULL,
  `payment_id` int(10) UNSIGNED NOT NULL,
  `amortization_id` int(10) UNSIGNED NOT NULL,
  `allocation_type` enum('Penalty','Interest','Principal') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_payment_allocations`
--

-- --------------------------------------------------------

--
-- Table structure for table `member_number_sequences`
DROP TABLE IF EXISTS `member_number_sequences`;
CREATE TABLE `member_number_sequences` (
  `id` tinyint(3) unsigned NOT NULL,
  `next_number` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_member_number_sequence_singleton` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(10) UNSIGNED NOT NULL,
  `member_number` varchar(30) NOT NULL,
  `membership_type` enum('Regular','Associate') NOT NULL DEFAULT 'Regular',
  `membership_date` date NOT NULL,
  `status` enum('Pending','Active','Inactive') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

-- --------------------------------------------------------

--
-- Table structure for table `member_addresses`
--

CREATE TABLE `member_addresses` (
  `member_id` int(10) UNSIGNED NOT NULL,
  `house_number` varchar(100) DEFAULT NULL,
  `street` varchar(150) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `zip_code` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_addresses`
--

-- --------------------------------------------------------

--
-- Table structure for table `member_beneficiaries`
--

CREATE TABLE `member_beneficiaries` (
  `id` int(10) UNSIGNED NOT NULL,
  `member_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `relationship` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_beneficiaries`
--

-- --------------------------------------------------------

--
-- Table structure for table `member_contacts`
--

CREATE TABLE `member_contacts` (
  `member_id` int(10) UNSIGNED NOT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `telephone_number` varchar(30) DEFAULT NULL,
  `email_address` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_contacts`
--

-- --------------------------------------------------------

--
-- Table structure for table `member_educations`
--

CREATE TABLE `member_educations` (
  `member_id` int(10) UNSIGNED NOT NULL,
  `highest_educational_attainment` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_educations`
--

-- --------------------------------------------------------

--
-- Table structure for table `member_livelihoods`
--

CREATE TABLE `member_livelihoods` (
  `member_id` int(10) UNSIGNED NOT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `employer` varchar(150) DEFAULT NULL,
  `monthly_income` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_livelihoods`
--

-- --------------------------------------------------------

--
-- Table structure for table `member_profiles`
--

CREATE TABLE `member_profiles` (
  `member_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(150) DEFAULT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_profiles`
--

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `was_successful` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `role` varchar(30) NOT NULL DEFAULT 'membership',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`),
  ADD KEY `idx_accounts_parent` (`parent_id`),
  ADD KEY `idx_accounts_type` (`account_type`),
  ADD KEY `idx_accounts_active` (`is_active`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_user` (`user_id`),
  ADD KEY `idx_activity_logs_action` (`action`),
  ADD KEY `idx_activity_logs_subject` (`subject_type`,`subject_id`),
  ADD KEY `idx_activity_logs_created_at` (`created_at`);

--
-- Indexes for table `journal_lines`
--
ALTER TABLE `journal_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_journal_lines_voucher` (`journal_voucher_id`),
  ADD KEY `idx_journal_lines_account` (`account_id`),
  ADD KEY `idx_journal_lines_member` (`member_id`),
  ADD KEY `idx_journal_lines_loan` (`loan_id`);

--
-- Indexes for table `journal_vouchers`
--
ALTER TABLE `journal_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `idx_journal_vouchers_transaction_date` (`transaction_date`),
  ADD KEY `idx_journal_vouchers_status` (`status`),
  ADD KEY `idx_journal_vouchers_source` (`source_type`,`source_id`),
  ADD KEY `idx_journal_vouchers_created_by` (`created_by`),
  ADD KEY `idx_journal_vouchers_approved_by` (`approved_by`),
  ADD KEY `idx_journal_vouchers_posted_by` (`posted_by`),
  ADD KEY `idx_journal_vouchers_reversal_of` (`reversal_of_voucher_id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loans_member` (`member_id`),
  ADD KEY `idx_loans_application_status` (`application_status`),
  ADD KEY `idx_loans_loan_status` (`loan_status`),
  ADD KEY `idx_loans_created_by` (`created_by`),
  ADD KEY `idx_loans_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_loans_approved_by` (`approved_by`),
  ADD KEY `idx_loans_released_by` (`released_by`);

--
-- Indexes for table `loan_amortizations`
--
ALTER TABLE `loan_amortizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_loan_amortizations_period` (`loan_id`,`period`),
  ADD KEY `idx_loan_amortizations_loan` (`loan_id`),
  ADD KEY `idx_loan_amortizations_due_date` (`due_date`),
  ADD KEY `idx_loan_amortizations_status` (`status`);

--
-- Indexes for table `loan_payments`
--
ALTER TABLE `loan_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loan_payments_loan` (`loan_id`),
  ADD KEY `idx_loan_payments_datetime` (`payment_datetime`),
  ADD KEY `idx_loan_payments_created_by` (`created_by`),
  ADD KEY `fk_loan_payments_reversed_by` (`reversed_by`);

--
-- Indexes for table `loan_payment_allocations`
--
ALTER TABLE `loan_payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loan_payment_allocations_payment` (`payment_id`),
  ADD KEY `idx_loan_payment_allocations_amortization` (`amortization_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_number` (`member_number`);

--
-- Indexes for table `member_addresses`
--
ALTER TABLE `member_addresses`
  ADD PRIMARY KEY (`member_id`);

--
-- Indexes for table `member_beneficiaries`
--
ALTER TABLE `member_beneficiaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_member_beneficiary_member` (`member_id`);

--
-- Indexes for table `member_contacts`
--
ALTER TABLE `member_contacts`
  ADD PRIMARY KEY (`member_id`);

--
-- Indexes for table `member_educations`
--
ALTER TABLE `member_educations`
  ADD PRIMARY KEY (`member_id`);

--
-- Indexes for table `member_livelihoods`
--
ALTER TABLE `member_livelihoods`
  ADD PRIMARY KEY (`member_id`);

--
-- Indexes for table `member_profiles`
--
ALTER TABLE `member_profiles`
  ADD PRIMARY KEY (`member_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `migration` (`migration`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_attempts_lookup` (`username`,`ip_address`,`attempted_at`),
  ADD KEY `idx_login_attempts_ip` (`ip_address`,`attempted_at`);

-- --------------------------------------------------------

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

--
-- AUTO_INCREMENT for table `journal_lines`
--
ALTER TABLE `journal_lines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_vouchers`
--
ALTER TABLE `journal_vouchers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `loan_amortizations`
--
ALTER TABLE `loan_amortizations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `loan_payments`
--
ALTER TABLE `loan_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `loan_payment_allocations`
--
ALTER TABLE `loan_payment_allocations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `member_beneficiaries`
--
ALTER TABLE `member_beneficiaries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_accounts_parent` FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `journal_lines`
--
ALTER TABLE `journal_lines`
  ADD CONSTRAINT `fk_journal_lines_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_journal_lines_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_journal_lines_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_journal_lines_voucher` FOREIGN KEY (`journal_voucher_id`) REFERENCES `journal_vouchers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_vouchers`
--
ALTER TABLE `journal_vouchers`
  ADD CONSTRAINT `fk_journal_vouchers_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_journal_vouchers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_journal_vouchers_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_journal_vouchers_reversal_of` FOREIGN KEY (`reversal_of_voucher_id`) REFERENCES `journal_vouchers` (`id`);

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loans_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_loans_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_loans_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `fk_loans_released_by` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_loans_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loan_amortizations`
--
ALTER TABLE `loan_amortizations`
  ADD CONSTRAINT `fk_loan_amortizations_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_payments`
--
ALTER TABLE `loan_payments`
  ADD CONSTRAINT `fk_loan_payments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_loan_payments_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  ADD CONSTRAINT `fk_loan_payments_reversed_by` FOREIGN KEY (`reversed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loan_payment_allocations`
--
ALTER TABLE `loan_payment_allocations`
  ADD CONSTRAINT `fk_loan_payment_allocations_amortization` FOREIGN KEY (`amortization_id`) REFERENCES `loan_amortizations` (`id`),
  ADD CONSTRAINT `fk_loan_payment_allocations_payment` FOREIGN KEY (`payment_id`) REFERENCES `loan_payments` (`id`);

--
-- Constraints for table `member_addresses`
--
ALTER TABLE `member_addresses`
  ADD CONSTRAINT `fk_member_addresses_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_beneficiaries`
--
ALTER TABLE `member_beneficiaries`
  ADD CONSTRAINT `fk_member_beneficiaries_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_contacts`
--
ALTER TABLE `member_contacts`
  ADD CONSTRAINT `fk_member_contacts_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_educations`
--
ALTER TABLE `member_educations`
  ADD CONSTRAINT `fk_member_educations_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_livelihoods`
--
ALTER TABLE `member_livelihoods`
  ADD CONSTRAINT `fk_member_livelihoods_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_profiles`
--
ALTER TABLE `member_profiles`
  ADD CONSTRAINT `fk_member_profiles_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
