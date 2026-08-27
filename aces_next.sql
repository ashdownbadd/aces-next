-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2026 at 08:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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

INSERT INTO `accounts` (`id`, `parent_id`, `account_code`, `account_name`, `account_type`, `normal_balance`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, '1000', 'Cash and Cash Equivalents', 'Asset', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(2, 1, '1010', 'Cash on Hand', 'Asset', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(3, 1, '1020', 'Cash in Bank', 'Asset', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(4, NULL, '1100', 'Loans Receivable', 'Asset', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(5, 4, '1110', 'Principal Loans Receivable', 'Asset', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(6, 4, '1120', 'Interest Receivable', 'Asset', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(7, 4, '1130', 'Penalty Receivable', 'Asset', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(8, NULL, '1200', 'Other Receivables', 'Asset', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(9, NULL, '2000', 'Total Liabilities', 'Liability', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(10, 9, '2010', 'Accounts Payable', 'Liability', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(11, 9, '2020', 'Other Payables', 'Liability', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(12, 9, '2100', 'Member Payables', 'Liability', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(13, NULL, '3000', 'Cooperative Equity', 'Equity', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(14, 13, '3010', 'Share Capital', 'Equity', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(15, 13, '3020', 'Members\' Equity', 'Equity', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(16, 13, '3030', 'Retained Surplus', 'Equity', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(17, NULL, '4000', 'Operating Income', 'Income', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(18, 17, '4010', 'Interest Income', 'Income', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(19, 17, '4020', 'Penalty Income', 'Income', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(20, 17, '4030', 'Other Finance Income', 'Income', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(21, NULL, '4100', 'Other Operating Income', 'Income', 'Credit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(22, NULL, '5000', 'Operating Expenses', 'Expense', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(23, 22, '5010', 'Administrative Expenses', 'Expense', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(24, 22, '5020', 'Office Expenses', 'Expense', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(25, 22, '5030', 'Bank Charges', 'Expense', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(26, 22, '5040', 'Other Operating Expenses', 'Expense', 'Debit', 1, '2026-08-20 07:27:26', '2026-08-20 07:27:26'),
(47, 17, '4040', 'Processing Fee Income', 'Income', 'Credit', 1, '2026-08-24 00:58:34', '2026-08-24 00:58:34'),
(48, 17, '4050', 'Insurance Recovery Income', 'Income', 'Credit', 1, '2026-08-24 00:58:34', '2026-08-24 00:58:34'),
(49, 17, '4060', 'Notarial Fee Recovery Income', 'Income', 'Credit', 1, '2026-08-24 00:58:34', '2026-08-24 00:58:34');

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

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `subject_type`, `subject_id`, `ip_address`, `created_at`) VALUES
(1, 1, 'MEMBER_CREATED', 'Member #0001 was registered with status Pending.', 'Member', 1, '127.0.0.1', '2026-08-20 05:50:35'),
(2, 1, 'MEMBER_UPDATED', 'Member #1 was updated.', 'Member', 1, '127.0.0.1', '2026-08-20 05:55:21'),
(3, 1, 'MEMBER_UPDATED', 'Member #1 was updated.', 'Member', 1, '127.0.0.1', '2026-08-20 05:58:03'),
(4, 1, 'MEMBER_BENEFICIARY_ADDED', 'Beneficiary \"Maria Clara Santos\" was added to Member #1.', 'Member', 1, '127.0.0.1', '2026-08-20 05:58:03'),
(5, 1, 'MEMBER_UPDATED', 'Member #1 was updated.', 'Member', 1, '127.0.0.1', '2026-08-20 05:59:40'),
(6, 1, 'MEMBER_BENEFICIARY_UPDATED', 'Beneficiary \"Maria Clara Santos\" was updated for Member #1.', 'Member', 1, '127.0.0.1', '2026-08-20 06:12:09'),
(7, 1, 'MEMBER_BENEFICIARY_REMOVED', 'Beneficiary \"Maria Clara Santos\" was removed from Member #1.', 'Member', 1, '127.0.0.1', '2026-08-20 06:12:57'),
(8, 1, 'MEMBER_UPDATED', 'Member #1 was updated.', 'Member', 1, '127.0.0.1', '2026-08-20 06:13:07'),
(84, 1, 'MEMBER_UPDATED', 'Member #1 was updated.', 'Member', 1, '127.0.0.1', '2026-08-20 06:53:29'),
(101, 1, 'LOAN_CREATED', 'Loan #4 was created for Member #1.', 'Loan', 4, NULL, '2026-08-24 00:40:36'),
(102, 1, 'LOAN_SUBMITTED', 'Loan #4 was submitted for review.', 'Loan', 4, NULL, '2026-08-24 00:40:36'),
(103, 1, 'LOAN_APPROVED', 'Loan #4 was approved.', 'Loan', 4, NULL, '2026-08-24 00:40:36'),
(104, 1, 'LOAN_AMORTIZATION_GENERATED', 'Amortization schedule with 3 periods was generated for Loan #4.', 'Loan', 4, NULL, '2026-08-24 00:40:36'),
(105, 1, 'LOAN_RELEASED', 'Loan #4 was released and became Active on 2026-08-20.', 'Loan', 4, NULL, '2026-08-24 00:40:36'),
(152, 1, 'LOAN_CREATED', 'Loan #14 was created for Member #3.', 'Loan', 14, '127.0.0.1', '2026-08-24 08:02:39'),
(153, 1, 'MEMBER_STATUS_CHANGED', 'Member #0001 status changed from Pending to Active.', 'Member', 1, '127.0.0.1', '2026-08-26 01:18:42'),
(154, 1, 'LOAN_SUBMITTED', 'Loan #14 was submitted for review.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:17:50'),
(155, 1, 'LOAN_APPROVED', 'Loan #14 was approved.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:18:02'),
(156, 1, 'LOAN_AMORTIZATION_GENERATED', 'Amortization schedule with 12 periods was generated for Loan #14.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:18:07'),
(157, 1, 'LOAN_RELEASED', 'Loan #14 was released and became Active on 2026-08-27.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:18:07'),
(158, 1, 'LOAN_PAYMENT_APPLIED', 'Payment #7 of ₱12,400.00 was applied to Loan #14 using the installment-first payment rule.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:18:26'),
(159, 1, 'LOAN_FULLY_PAID', 'Loan #14 was fully paid.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:19:30'),
(160, 1, 'LOAN_PAYMENT_APPLIED', 'Payment #8 of ₱123,200.00 was applied to Loan #14 using the installment-first payment rule.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:19:30'),
(161, 1, 'LOAN_REACTIVATED', 'Loan #14 returned to Active after Payment #8 was reversed.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:26:49'),
(162, 1, 'LOAN_PAYMENT_REVERSED', 'Payment #8 on Loan #14 was reversed. Reason: Entered under wrong transaction.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:26:49'),
(163, 1, 'LOAN_PAYMENT_APPLIED', 'Payment #9 of ₱100.00 was applied to Loan #14 using the installment-first payment rule.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:28:11'),
(164, 1, 'LOAN_PAYMENT_REVERSED', 'Payment #9 on Loan #14 was reversed. Reason: Entered under wrong transaction.', 'Loan', 14, '127.0.0.1', '2026-08-27 01:28:48'),
(179, 1, 'LOAN_PAYMENT_REVERSED', 'Payment #7 on Loan #14 was reversed. Reason: test', 'Loan', 14, '127.0.0.1', '2026-08-27 01:40:49');

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

INSERT INTO `journal_lines` (`id`, `journal_voucher_id`, `account_id`, `member_id`, `loan_id`, `line_description`, `debit`, `credit`, `created_at`) VALUES
(20, 8, 5, 1, NULL, 'Principal released to member', 6000.00, 0.00, '2026-08-24 01:23:57'),
(21, 8, 2, 1, NULL, 'Net loan proceeds paid to member', 0.00, 5458.40, '2026-08-24 01:23:57'),
(22, 8, 47, 1, NULL, 'Processing fee withheld from release', 0.00, 120.00, '2026-08-24 01:23:57'),
(23, 8, 48, 1, NULL, 'Insurance deduction withheld from release', 0.00, 21.60, '2026-08-24 01:23:57'),
(24, 8, 49, 1, NULL, 'Notarial fee withheld from release', 0.00, 400.00, '2026-08-24 01:23:57'),
(25, 9, 5, 1, NULL, 'Principal released to member', 6000.00, 0.00, '2026-08-24 01:27:57'),
(26, 9, 2, 1, NULL, 'Net loan proceeds paid to member', 0.00, 5458.40, '2026-08-24 01:27:57'),
(27, 9, 47, 1, NULL, 'Processing fee withheld from release', 0.00, 120.00, '2026-08-24 01:27:57'),
(28, 9, 48, 1, NULL, 'Insurance deduction withheld from release', 0.00, 21.60, '2026-08-24 01:27:57'),
(29, 9, 49, 1, NULL, 'Notarial fee withheld from release', 0.00, 400.00, '2026-08-24 01:27:57'),
(33, 11, 5, 1, NULL, 'Principal released to member', 6000.00, 0.00, '2026-08-24 01:30:03'),
(34, 11, 2, 1, NULL, 'Net loan proceeds paid to member', 0.00, 5458.40, '2026-08-24 01:30:03'),
(35, 11, 47, 1, NULL, 'Processing fee withheld from release', 0.00, 120.00, '2026-08-24 01:30:03'),
(36, 11, 48, 1, NULL, 'Insurance deduction withheld from release', 0.00, 21.60, '2026-08-24 01:30:03'),
(37, 11, 49, 1, NULL, 'Notarial fee withheld from release', 0.00, 400.00, '2026-08-24 01:30:03'),
(44, 14, 5, 1, NULL, 'Principal released to member', 6000.00, 0.00, '2026-08-24 01:31:37'),
(45, 14, 2, 1, NULL, 'Net loan proceeds paid to member', 0.00, 5458.40, '2026-08-24 01:31:37'),
(46, 14, 47, 1, NULL, 'Processing fee withheld from release', 0.00, 120.00, '2026-08-24 01:31:37'),
(47, 14, 48, 1, NULL, 'Insurance deduction withheld from release', 0.00, 21.60, '2026-08-24 01:31:37'),
(48, 14, 49, 1, NULL, 'Notarial fee withheld from release', 0.00, 400.00, '2026-08-24 01:31:37'),
(55, 17, 5, 1, NULL, 'Principal released to member', 6000.00, 0.00, '2026-08-24 01:36:59'),
(56, 17, 2, 1, NULL, 'Net loan proceeds paid to member', 0.00, 5458.40, '2026-08-24 01:36:59'),
(57, 17, 47, 1, NULL, 'Processing fee withheld from release', 0.00, 120.00, '2026-08-24 01:36:59'),
(58, 17, 48, 1, NULL, 'Insurance deduction withheld from release', 0.00, 21.60, '2026-08-24 01:36:59'),
(59, 17, 49, 1, NULL, 'Notarial fee withheld from release', 0.00, 400.00, '2026-08-24 01:36:59'),
(70, 22, 5, 3, 14, 'Principal released to member', 120000.00, 0.00, '2026-08-27 01:18:07'),
(71, 22, 2, 3, 14, 'Net loan proceeds paid to member', 0.00, 115472.00, '2026-08-27 01:18:07'),
(72, 22, 47, 3, 14, 'Processing fee withheld from release', 0.00, 2400.00, '2026-08-27 01:18:07'),
(73, 22, 48, 3, 14, 'Insurance deduction withheld from release', 0.00, 1728.00, '2026-08-27 01:18:07'),
(74, 22, 49, 3, 14, 'Notarial fee withheld from release', 0.00, 400.00, '2026-08-27 01:18:07'),
(75, 23, 2, 3, 14, 'Loan payment cash receipt', 12400.00, 0.00, '2026-08-27 01:18:26'),
(76, 23, 5, 3, 14, 'Principal applied to loan', 0.00, 10000.00, '2026-08-27 01:18:26'),
(77, 23, 18, 3, 14, 'Interest income from loan payment', 0.00, 2400.00, '2026-08-27 01:18:26'),
(78, 24, 2, 3, 14, 'Loan payment cash receipt', 123200.00, 0.00, '2026-08-27 01:19:30'),
(79, 24, 5, 3, 14, 'Principal applied to loan', 0.00, 110000.00, '2026-08-27 01:19:30'),
(80, 24, 18, 3, 14, 'Interest income from loan payment', 0.00, 13200.00, '2026-08-27 01:19:30'),
(81, 25, 2, 3, 14, 'Reversal of Journal Voucher #24', 0.00, 123200.00, '2026-08-27 01:26:49'),
(82, 25, 5, 3, 14, 'Reversal of Journal Voucher #24', 110000.00, 0.00, '2026-08-27 01:26:49'),
(83, 25, 18, 3, 14, 'Reversal of Journal Voucher #24', 13200.00, 0.00, '2026-08-27 01:26:49'),
(84, 26, 2, 3, 14, 'Loan payment cash receipt', 100.00, 0.00, '2026-08-27 01:28:11'),
(85, 26, 18, 3, 14, 'Interest income from loan payment', 0.00, 100.00, '2026-08-27 01:28:11'),
(86, 27, 2, 3, 14, 'Reversal of Journal Voucher #26', 0.00, 100.00, '2026-08-27 01:28:48'),
(87, 27, 18, 3, 14, 'Reversal of Journal Voucher #26', 100.00, 0.00, '2026-08-27 01:28:48'),
(88, 28, 5, 1, NULL, 'Principal released to member', 6000.00, 0.00, '2026-08-27 01:30:41'),
(89, 28, 2, 1, NULL, 'Net loan proceeds paid to member', 0.00, 5458.40, '2026-08-27 01:30:41'),
(90, 28, 47, 1, NULL, 'Processing fee withheld from release', 0.00, 120.00, '2026-08-27 01:30:41'),
(91, 28, 48, 1, NULL, 'Insurance deduction withheld from release', 0.00, 21.60, '2026-08-27 01:30:41'),
(92, 28, 49, 1, NULL, 'Notarial fee withheld from release', 0.00, 400.00, '2026-08-27 01:30:41'),
(101, 32, 5, 1, NULL, 'Principal released to member', 6000.00, 0.00, '2026-08-27 01:36:05'),
(102, 32, 2, 1, NULL, 'Net loan proceeds paid to member', 0.00, 5458.40, '2026-08-27 01:36:05'),
(103, 32, 47, 1, NULL, 'Processing fee withheld from release', 0.00, 120.00, '2026-08-27 01:36:05'),
(104, 32, 48, 1, NULL, 'Insurance deduction withheld from release', 0.00, 21.60, '2026-08-27 01:36:05'),
(105, 32, 49, 1, NULL, 'Notarial fee withheld from release', 0.00, 400.00, '2026-08-27 01:36:05'),
(116, 37, 2, 3, 14, 'Reversal of Journal Voucher #23', 0.00, 12400.00, '2026-08-27 01:40:49'),
(117, 37, 5, 3, 14, 'Reversal of Journal Voucher #23', 10000.00, 0.00, '2026-08-27 01:40:49'),
(118, 37, 18, 3, 14, 'Reversal of Journal Voucher #23', 2400.00, 0.00, '2026-08-27 01:40:49');

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

INSERT INTO `journal_vouchers` (`id`, `reference_number`, `transaction_date`, `particulars`, `source_type`, `source_id`, `reversal_of_voucher_id`, `status`, `rejection_reason`, `created_by`, `approved_by`, `posted_by`, `approved_at`, `posted_at`, `created_at`, `updated_at`) VALUES
(8, 'LR-9-463479', '2026-08-20', 'Loan #9 release accounting.', 'LoanRelease', 9, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-24 01:23:57', '2026-08-24 01:23:57'),
(9, 'LR-10-0502A0', '2026-08-20', 'Loan #10 release accounting.', 'LoanRelease', 10, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-24 01:27:57', '2026-08-24 01:27:57'),
(11, 'LR-11-2DDF0C', '2026-08-20', 'Loan #11 release accounting.', 'LoanRelease', 11, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-24 01:30:03', '2026-08-24 01:30:03'),
(14, 'LR-12-9717DC', '2026-08-20', 'Loan #12 release accounting.', 'LoanRelease', 12, NULL, 'Rejected', 'Reject Test', 1, NULL, NULL, NULL, NULL, '2026-08-24 01:31:37', '2026-08-24 01:58:27'),
(17, 'LR-13-63AFB0', '2026-08-20', 'Loan #13 release accounting.', 'LoanRelease', 13, NULL, 'Posted', NULL, 1, 1, 1, '2026-08-23 19:57:52', '2026-08-23 19:57:59', '2026-08-24 01:36:59', '2026-08-24 01:57:59'),
(22, 'LR-14-7069D1', '2026-08-27', 'Loan #14 release accounting.', 'LoanRelease', 14, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-27 01:18:07', '2026-08-27 01:18:07'),
(23, 'LP-7-09A603', '2026-08-27', 'Loan payment #7 for Loan #14.', 'LoanPayment', 7, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-27 01:18:26', '2026-08-27 01:18:26'),
(24, 'LP-8-2FA161', '2026-08-27', 'Loan payment #8 for Loan #14.', 'LoanPayment', 8, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-27 01:19:30', '2026-08-27 01:19:30'),
(25, 'LPR-8-EB75AA', '2026-08-27', 'Reversal of Loan payment #8 for Loan #14.', 'LoanPaymentReversal', 8, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-27 01:26:49', '2026-08-27 01:26:49'),
(26, 'LP-9-A1C7B9', '2026-08-27', 'Loan payment #9 for Loan #14.', 'LoanPayment', 9, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-27 01:28:11', '2026-08-27 01:28:11'),
(27, 'LPR-9-24290A', '2026-08-27', 'Reversal of Loan payment #9 for Loan #14.', 'LoanPaymentReversal', 9, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-27 01:28:48', '2026-08-27 01:28:48'),
(28, 'LR-15-705068', '2026-08-20', 'Loan #15 release accounting.', 'LoanRelease', 15, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-27 01:30:41', '2026-08-27 01:30:41'),
(32, 'LR-16-FBEE16', '2026-08-20', 'Loan #16 release accounting.', 'LoanRelease', 16, NULL, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-27 01:36:05', '2026-08-27 01:36:05'),
(37, 'LPR-7-38B4E6', '2026-08-27', 'Reversal of Loan payment #7 for Loan #14.', 'LoanPaymentReversal', 7, 23, 'Pending', NULL, 1, NULL, NULL, NULL, NULL, '2026-08-27 01:40:49', '2026-08-27 01:40:49');

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

INSERT INTO `loans` (`id`, `member_id`, `loan_type`, `collateral`, `application_status`, `loan_status`, `rejection_reason`, `principal_amount`, `interest_rate`, `amortization_type`, `payment_frequency`, `terms_months`, `start_date`, `release_date`, `manual_payment`, `processing_fee`, `insurance`, `notarial_fee`, `net_proceeds`, `tct_no`, `tax_declaration_no`, `real_property_payment_status`, `notes`, `created_by`, `reviewed_by`, `approved_by`, `released_by`, `reviewed_at`, `approved_at`, `released_at`, `fully_paid_at`, `created_at`, `updated_at`) VALUES
(14, 3, 'Bridge Financing', 'Post-Dated Check', 'Approved', 'Active', NULL, 120000.00, 2.0000, 'Diminishing balance', NULL, 12, '2026-08-06', '2026-08-27', NULL, 2400.00, 1728.00, 400.00, 115472.00, NULL, NULL, NULL, NULL, 1, 1, 1, 1, '2026-08-26 19:17:50', '2026-08-26 19:18:02', '2026-08-26 19:18:07', NULL, '2026-08-24 08:02:39', '2026-08-27 01:26:49');

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

INSERT INTO `loan_amortizations` (`id`, `loan_id`, `period`, `due_date`, `principal`, `interest`, `rem_principal`, `rem_interest`, `rem_penalty`, `orig_penalty`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(52, 14, 1, '2026-09-27', 10000.00, 2400.00, 10000.00, 2400.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:40:49'),
(53, 14, 2, '2026-10-27', 10000.00, 2200.00, 10000.00, 2200.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:28:48'),
(54, 14, 3, '2026-11-27', 10000.00, 2000.00, 10000.00, 2000.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:26:49'),
(55, 14, 4, '2026-12-27', 10000.00, 1800.00, 10000.00, 1800.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:26:49'),
(56, 14, 5, '2027-01-27', 10000.00, 1600.00, 10000.00, 1600.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:26:49'),
(57, 14, 6, '2027-02-27', 10000.00, 1400.00, 10000.00, 1400.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:26:49'),
(58, 14, 7, '2027-03-27', 10000.00, 1200.00, 10000.00, 1200.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:26:49'),
(59, 14, 8, '2027-04-27', 10000.00, 1000.00, 10000.00, 1000.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:26:49'),
(60, 14, 9, '2027-05-27', 10000.00, 800.00, 10000.00, 800.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:26:49'),
(61, 14, 10, '2027-06-27', 10000.00, 600.00, 10000.00, 600.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:26:49'),
(62, 14, 11, '2027-07-27', 10000.00, 400.00, 10000.00, 400.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:26:49'),
(63, 14, 12, '2027-08-27', 10000.00, 200.00, 10000.00, 200.00, 0.00, 0.00, 'Pending', NULL, '2026-08-27 01:18:07', '2026-08-27 01:26:49');

-- --------------------------------------------------------

--
-- Table structure for table `loan_payments`
--

CREATE TABLE `loan_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_id` int(10) UNSIGNED NOT NULL,
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

INSERT INTO `loan_payments` (`id`, `loan_id`, `payment_datetime`, `amount_paid`, `penalty_applied`, `interest_applied`, `principal_applied`, `excess`, `type`, `remarks`, `created_by`, `created_at`, `updated_at`, `reversed_at`, `reversed_by`, `reversal_reason`) VALUES
(7, 14, '2026-08-26 19:18:26', 12400.00, 0.00, 2400.00, 10000.00, 0.00, 'Global', 'QA 4 - First Payment', 1, '2026-08-27 01:18:26', '2026-08-27 01:40:49', '2026-08-26 19:40:49', 1, 'test'),
(8, 14, '2026-08-26 19:19:30', 123200.00, 0.00, 13200.00, 110000.00, 0.00, 'Global', 'test', 1, '2026-08-27 01:19:30', '2026-08-27 01:26:49', '2026-08-26 19:26:49', 1, 'Entered under wrong transaction.'),
(9, 14, '2026-08-26 19:28:11', 100.00, 0.00, 100.00, 0.00, 0.00, 'Global', NULL, 1, '2026-08-27 01:28:11', '2026-08-27 01:28:48', '2026-08-26 19:28:48', 1, 'Entered under wrong transaction.');

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

INSERT INTO `loan_payment_allocations` (`id`, `payment_id`, `amortization_id`, `allocation_type`, `amount`, `created_at`) VALUES
(12, 7, 52, 'Interest', 2400.00, '2026-08-27 01:18:26'),
(13, 7, 52, 'Principal', 10000.00, '2026-08-27 01:18:26'),
(14, 8, 53, 'Interest', 2200.00, '2026-08-27 01:19:30'),
(15, 8, 53, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(16, 8, 54, 'Interest', 2000.00, '2026-08-27 01:19:30'),
(17, 8, 54, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(18, 8, 55, 'Interest', 1800.00, '2026-08-27 01:19:30'),
(19, 8, 55, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(20, 8, 56, 'Interest', 1600.00, '2026-08-27 01:19:30'),
(21, 8, 56, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(22, 8, 57, 'Interest', 1400.00, '2026-08-27 01:19:30'),
(23, 8, 57, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(24, 8, 58, 'Interest', 1200.00, '2026-08-27 01:19:30'),
(25, 8, 58, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(26, 8, 59, 'Interest', 1000.00, '2026-08-27 01:19:30'),
(27, 8, 59, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(28, 8, 60, 'Interest', 800.00, '2026-08-27 01:19:30'),
(29, 8, 60, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(30, 8, 61, 'Interest', 600.00, '2026-08-27 01:19:30'),
(31, 8, 61, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(32, 8, 62, 'Interest', 400.00, '2026-08-27 01:19:30'),
(33, 8, 62, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(34, 8, 63, 'Interest', 200.00, '2026-08-27 01:19:30'),
(35, 8, 63, 'Principal', 10000.00, '2026-08-27 01:19:30'),
(36, 9, 53, 'Interest', 100.00, '2026-08-27 01:28:11');

-- --------------------------------------------------------

--
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

INSERT INTO `members` (`id`, `member_number`, `membership_type`, `membership_date`, `status`, `created_at`, `updated_at`) VALUES
(1, '0001', 'Regular', '2026-08-20', 'Active', '2026-08-20 05:50:35', '2026-08-26 01:18:42'),
(2, '0002', 'Regular', '2026-08-20', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(3, '0003', 'Associate', '2026-08-19', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(4, '0004', 'Regular', '2026-08-18', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(5, '0005', 'Associate', '2026-08-17', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(6, '0006', 'Regular', '2026-08-16', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(7, '0007', 'Associate', '2026-08-15', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(8, '0008', 'Regular', '2026-08-14', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(9, '0009', 'Associate', '2026-08-13', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(10, '0010', 'Regular', '2026-08-12', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(11, '0011', 'Associate', '2026-08-11', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(12, '0012', 'Regular', '2026-08-10', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(13, '0013', 'Associate', '2026-08-09', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(14, '0014', 'Regular', '2026-08-08', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(15, '0015', 'Associate', '2026-08-07', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(16, '0016', 'Regular', '2026-08-06', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(17, '0017', 'Associate', '2026-08-05', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(18, '0018', 'Regular', '2026-08-04', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(19, '0019', 'Associate', '2026-08-03', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(20, '0020', 'Regular', '2026-08-02', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(21, '0021', 'Associate', '2026-08-01', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(22, '0022', 'Regular', '2026-07-31', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(23, '0023', 'Associate', '2026-07-30', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(24, '0024', 'Regular', '2026-07-29', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(25, '0025', 'Associate', '2026-07-28', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(26, '0026', 'Regular', '2026-07-27', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(27, '0027', 'Associate', '2026-07-26', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(28, '0028', 'Regular', '2026-07-25', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(29, '0029', 'Associate', '2026-07-24', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(30, '0030', 'Regular', '2026-07-23', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(31, '0031', 'Associate', '2026-07-22', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(32, '0032', 'Regular', '2026-07-21', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(33, '0033', 'Associate', '2026-07-20', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(34, '0034', 'Regular', '2026-07-19', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(35, '0035', 'Associate', '2026-07-18', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(36, '0036', 'Regular', '2026-07-17', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(37, '0037', 'Associate', '2026-07-16', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(38, '0038', 'Regular', '2026-07-15', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(39, '0039', 'Associate', '2026-07-14', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(40, '0040', 'Regular', '2026-07-13', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(41, '0041', 'Associate', '2026-07-12', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(42, '0042', 'Regular', '2026-07-11', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(43, '0043', 'Associate', '2026-07-10', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(44, '0044', 'Regular', '2026-07-09', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(45, '0045', 'Associate', '2026-07-08', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(46, '0046', 'Regular', '2026-07-07', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(47, '0047', 'Associate', '2026-07-06', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(48, '0048', 'Regular', '2026-07-05', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(49, '0049', 'Associate', '2026-07-04', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(50, '0050', 'Regular', '2026-07-03', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(51, '0051', 'Associate', '2026-07-02', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(52, '0052', 'Regular', '2026-07-01', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(53, '0053', 'Associate', '2026-06-30', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(54, '0054', 'Regular', '2026-06-29', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(55, '0055', 'Associate', '2026-06-28', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(56, '0056', 'Regular', '2026-06-27', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(57, '0057', 'Associate', '2026-06-26', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(58, '0058', 'Regular', '2026-06-25', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(59, '0059', 'Associate', '2026-06-24', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(60, '0060', 'Regular', '2026-06-23', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(61, '0061', 'Associate', '2026-06-22', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(62, '0062', 'Regular', '2026-06-21', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(63, '0063', 'Associate', '2026-06-20', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(64, '0064', 'Regular', '2026-06-19', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(65, '0065', 'Associate', '2026-06-18', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(66, '0066', 'Regular', '2026-06-17', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(67, '0067', 'Associate', '2026-06-16', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(68, '0068', 'Regular', '2026-06-15', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(69, '0069', 'Associate', '2026-06-14', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(70, '0070', 'Regular', '2026-06-13', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(71, '0071', 'Associate', '2026-06-12', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(72, '0072', 'Regular', '2026-06-11', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(73, '0073', 'Associate', '2026-06-10', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(74, '0074', 'Regular', '2026-06-09', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(75, '0075', 'Associate', '2026-06-08', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(76, '0076', 'Regular', '2026-06-07', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(77, '0077', 'Associate', '2026-06-06', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(78, '0078', 'Regular', '2026-06-05', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(79, '0079', 'Associate', '2026-06-04', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(80, '0080', 'Regular', '2026-06-03', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(81, '0081', 'Associate', '2026-06-02', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(82, '0082', 'Regular', '2026-06-01', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(83, '0083', 'Associate', '2026-05-31', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(84, '0084', 'Regular', '2026-05-30', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(85, '0085', 'Associate', '2026-05-29', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(86, '0086', 'Regular', '2026-05-28', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(87, '0087', 'Associate', '2026-05-27', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(88, '0088', 'Regular', '2026-05-26', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(89, '0089', 'Associate', '2026-05-25', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(90, '0090', 'Regular', '2026-05-24', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(91, '0091', 'Associate', '2026-05-23', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(92, '0092', 'Regular', '2026-05-22', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(93, '0093', 'Associate', '2026-05-21', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(94, '0094', 'Regular', '2026-05-20', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(95, '0095', 'Associate', '2026-05-19', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(96, '0096', 'Regular', '2026-05-18', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(97, '0097', 'Associate', '2026-05-17', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(98, '0098', 'Regular', '2026-05-16', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(99, '0099', 'Associate', '2026-05-15', 'Active', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(100, '0100', 'Regular', '2026-05-14', 'Pending', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(101, '0101', 'Associate', '2026-05-13', 'Inactive', '2026-08-20 06:38:33', '2026-08-20 06:38:33');

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

INSERT INTO `member_addresses` (`member_id`, `house_number`, `street`, `barangay`, `city`, `province`, `zip_code`, `created_at`, `updated_at`) VALUES
(1, 'Block 90 Lot 32', 'Saguingan Street', 'Upper Bicutan', 'Taguig City', 'Metro Manila', '1633', '2026-08-20 05:50:35', '2026-08-20 05:50:35'),
(2, '100', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(3, '101', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(4, '102', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(5, '103', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(6, '104', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(7, '105', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(8, '106', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(9, '107', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(10, '108', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(11, '109', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(12, '110', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(13, '111', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(14, '112', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(15, '113', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(16, '114', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(17, '115', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(18, '116', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(19, '117', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(20, '118', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(21, '119', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(22, '120', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(23, '121', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(24, '122', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(25, '123', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(26, '124', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(27, '125', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(28, '126', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(29, '127', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(30, '128', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(31, '129', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(32, '130', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(33, '131', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(34, '132', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(35, '133', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(36, '134', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(37, '135', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(38, '136', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(39, '137', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(40, '138', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(41, '139', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(42, '140', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(43, '141', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(44, '142', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(45, '143', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(46, '144', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(47, '145', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(48, '146', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(49, '147', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(50, '148', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(51, '149', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(52, '150', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(53, '151', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(54, '152', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(55, '153', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(56, '154', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(57, '155', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(58, '156', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(59, '157', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(60, '158', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(61, '159', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(62, '160', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(63, '161', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(64, '162', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(65, '163', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(66, '164', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(67, '165', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(68, '166', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(69, '167', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(70, '168', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(71, '169', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(72, '170', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(73, '171', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(74, '172', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(75, '173', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(76, '174', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(77, '175', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(78, '176', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(79, '177', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(80, '178', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(81, '179', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(82, '180', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(83, '181', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(84, '182', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(85, '183', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(86, '184', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(87, '185', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(88, '186', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(89, '187', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(90, '188', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(91, '189', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(92, '190', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(93, '191', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(94, '192', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(95, '193', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(96, '194', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(97, '195', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(98, '196', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(99, '197', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(100, '198', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(101, '199', 'Sample Street', 'Barangay Central', 'Quezon City', 'Metro Manila', '1100', '2026-08-20 06:38:33', '2026-08-20 06:38:33');

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

INSERT INTO `member_beneficiaries` (`id`, `member_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `relationship`, `birth_date`, `remarks`, `created_at`, `updated_at`) VALUES
(3, 2, 'John', 'Test', 'Mendoza', NULL, 'Spouse', '2016-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(4, 3, 'Mary', 'Test', 'Santos', NULL, 'Child', '2015-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(5, 4, 'James', 'Test', 'Reyes', NULL, 'Parent', '2014-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(6, 6, 'Robert', 'Test', 'Mendoza', NULL, 'Spouse', '2012-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(7, 7, 'Jennifer', 'Test', 'Bautista', NULL, 'Child', '2011-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(8, 7, 'Jennifer', 'Test', 'Garcia', NULL, 'Child', '2011-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(9, 9, 'Linda', 'Test', 'Santos', NULL, 'Sibling', '2009-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(10, 10, 'John', 'Test', 'Reyes', NULL, 'Spouse', '2008-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(11, 12, 'James', 'Test', 'Mendoza', NULL, 'Parent', '2006-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(12, 12, 'James', 'Test', 'Reyes', NULL, 'Parent', '2006-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(13, 13, 'Elizabeth', 'Test', 'Bautista', NULL, 'Sibling', '2005-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(14, 15, 'Jennifer', 'Test', 'Santos', NULL, 'Child', '2003-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(15, 16, 'William', 'Test', 'Reyes', NULL, 'Parent', '2002-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(16, 17, 'Linda', 'Test', 'Santos', NULL, 'Sibling', '2001-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(17, 18, 'John', 'Test', 'Mendoza', NULL, 'Spouse', '2000-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(18, 19, 'Mary', 'Test', 'Bautista', NULL, 'Child', '1999-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(19, 21, 'Elizabeth', 'Test', 'Santos', NULL, 'Sibling', '1997-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(20, 22, 'Robert', 'Test', 'Reyes', NULL, 'Spouse', '1996-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(21, 22, 'Robert', 'Test', 'Dela Cruz', NULL, 'Spouse', '1996-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(22, 24, 'William', 'Test', 'Mendoza', NULL, 'Parent', '1994-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(23, 25, 'Linda', 'Test', 'Bautista', NULL, 'Sibling', '1993-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(24, 27, 'Mary', 'Test', 'Santos', NULL, 'Child', '1991-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(25, 27, 'Mary', 'Test', 'Bautista', NULL, 'Child', '1991-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(26, 28, 'James', 'Test', 'Reyes', NULL, 'Parent', '1990-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(27, 30, 'Robert', 'Test', 'Mendoza', NULL, 'Spouse', '1988-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(28, 31, 'Jennifer', 'Test', 'Bautista', NULL, 'Child', '1987-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(29, 32, 'William', 'Test', 'Mendoza', NULL, 'Parent', '1986-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(30, 33, 'Linda', 'Test', 'Santos', NULL, 'Sibling', '1985-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(31, 34, 'John', 'Test', 'Reyes', NULL, 'Spouse', '1984-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(32, 36, 'James', 'Test', 'Mendoza', NULL, 'Parent', '1982-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(33, 37, 'Elizabeth', 'Test', 'Bautista', NULL, 'Sibling', '1981-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(34, 37, 'Elizabeth', 'Test', 'Garcia', NULL, 'Sibling', '1981-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(35, 39, 'Jennifer', 'Test', 'Santos', NULL, 'Child', '1979-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(36, 40, 'William', 'Test', 'Reyes', NULL, 'Parent', '1978-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(37, 42, 'John', 'Test', 'Mendoza', NULL, 'Spouse', '1976-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(38, 42, 'John', 'Test', 'Reyes', NULL, 'Spouse', '1976-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(39, 43, 'Mary', 'Test', 'Bautista', NULL, 'Child', '1975-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(40, 45, 'Elizabeth', 'Test', 'Santos', NULL, 'Sibling', '1973-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(41, 46, 'Robert', 'Test', 'Reyes', NULL, 'Spouse', '1972-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(42, 47, 'Jennifer', 'Test', 'Santos', NULL, 'Child', '1971-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(43, 48, 'William', 'Test', 'Mendoza', NULL, 'Parent', '1970-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(44, 49, 'Linda', 'Test', 'Bautista', NULL, 'Sibling', '1969-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(45, 51, 'Mary', 'Test', 'Santos', NULL, 'Child', '1967-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(46, 52, 'James', 'Test', 'Reyes', NULL, 'Parent', '2016-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(47, 52, 'James', 'Test', 'Dela Cruz', NULL, 'Parent', '2016-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(48, 54, 'Robert', 'Test', 'Mendoza', NULL, 'Spouse', '2014-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(49, 55, 'Jennifer', 'Test', 'Bautista', NULL, 'Child', '2013-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(50, 57, 'Linda', 'Test', 'Santos', NULL, 'Sibling', '2011-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(51, 57, 'Linda', 'Test', 'Bautista', NULL, 'Sibling', '2011-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(52, 58, 'John', 'Test', 'Reyes', NULL, 'Spouse', '2010-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(53, 60, 'James', 'Test', 'Mendoza', NULL, 'Parent', '2008-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(54, 61, 'Elizabeth', 'Test', 'Bautista', NULL, 'Sibling', '2007-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(55, 62, 'Robert', 'Test', 'Mendoza', NULL, 'Spouse', '2006-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(56, 63, 'Jennifer', 'Test', 'Santos', NULL, 'Child', '2005-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(57, 64, 'William', 'Test', 'Reyes', NULL, 'Parent', '2004-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(58, 66, 'John', 'Test', 'Mendoza', NULL, 'Spouse', '2002-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(59, 67, 'Mary', 'Test', 'Bautista', NULL, 'Child', '2001-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(60, 67, 'Mary', 'Test', 'Garcia', NULL, 'Child', '2001-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(61, 69, 'Elizabeth', 'Test', 'Santos', NULL, 'Sibling', '1999-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(62, 70, 'Robert', 'Test', 'Reyes', NULL, 'Spouse', '1998-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(63, 72, 'William', 'Test', 'Mendoza', NULL, 'Parent', '1996-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(64, 72, 'William', 'Test', 'Reyes', NULL, 'Parent', '1996-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(65, 73, 'Linda', 'Test', 'Bautista', NULL, 'Sibling', '1995-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(66, 75, 'Mary', 'Test', 'Santos', NULL, 'Child', '1993-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(67, 76, 'James', 'Test', 'Reyes', NULL, 'Parent', '1992-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(68, 77, 'Elizabeth', 'Test', 'Santos', NULL, 'Sibling', '1991-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(69, 78, 'Robert', 'Test', 'Mendoza', NULL, 'Spouse', '1990-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(70, 79, 'Jennifer', 'Test', 'Bautista', NULL, 'Child', '1989-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(71, 81, 'Linda', 'Test', 'Santos', NULL, 'Sibling', '1987-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(72, 82, 'John', 'Test', 'Reyes', NULL, 'Spouse', '1986-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(73, 82, 'John', 'Test', 'Dela Cruz', NULL, 'Spouse', '1986-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(74, 84, 'James', 'Test', 'Mendoza', NULL, 'Parent', '1984-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(75, 85, 'Elizabeth', 'Test', 'Bautista', NULL, 'Sibling', '1983-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(76, 87, 'Jennifer', 'Test', 'Santos', NULL, 'Child', '1981-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(77, 87, 'Jennifer', 'Test', 'Bautista', NULL, 'Child', '1981-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(78, 88, 'William', 'Test', 'Reyes', NULL, 'Parent', '1980-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(79, 90, 'John', 'Test', 'Mendoza', NULL, 'Spouse', '1978-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(80, 91, 'Mary', 'Test', 'Bautista', NULL, 'Child', '1977-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(81, 92, 'James', 'Test', 'Mendoza', NULL, 'Parent', '1976-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(82, 93, 'Elizabeth', 'Test', 'Santos', NULL, 'Sibling', '1975-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(83, 94, 'Robert', 'Test', 'Reyes', NULL, 'Spouse', '1974-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(84, 96, 'William', 'Test', 'Mendoza', NULL, 'Parent', '1972-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(85, 97, 'Linda', 'Test', 'Bautista', NULL, 'Sibling', '1971-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(86, 97, 'Linda', 'Test', 'Garcia', NULL, 'Sibling', '1971-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(87, 99, 'Mary', 'Test', 'Santos', NULL, 'Child', '1969-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(88, 100, 'James', 'Test', 'Reyes', NULL, 'Parent', '1968-08-20', 'Test beneficiary', '2026-08-20 06:38:33', '2026-08-20 06:38:33');

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

INSERT INTO `member_contacts` (`member_id`, `mobile_number`, `telephone_number`, `email_address`, `created_at`, `updated_at`) VALUES
(1, '09955081740', NULL, 'u.randalljayb@gmail.com', '2026-08-20 05:50:35', '2026-08-20 05:55:21'),
(2, '09170000000', NULL, 'juan.delacruz0002@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(3, '09170000001', NULL, 'maria.santos0003@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(4, '09170000002', NULL, 'pedro.reyes0004@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(5, '09170000003', NULL, 'ana.garcia0005@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(6, '09170000004', NULL, 'carlos.mendoza0006@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(7, '09170000005', NULL, 'sofia.bautista0007@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(8, '09170000006', NULL, 'miguel.cruz0008@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(9, '09170000007', NULL, 'angela.navarro0009@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(10, '09170000008', NULL, 'jose.ramos0010@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(11, '09170000009', NULL, 'gabriel.castillo0011@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(12, '09170000010', NULL, 'patricia.flores0012@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(13, '09170000011', NULL, 'daniel.aquino0013@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(14, '09170000012', NULL, 'andrea.torres0014@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(15, '09170000013', NULL, 'mark.rivera0015@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(16, '09170000014', NULL, 'christine.fernandez0016@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(17, '09170000015', NULL, 'michael.delacruz0017@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(18, '09170000016', NULL, 'nicole.santos0018@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(19, '09170000017', NULL, 'joshua.reyes0019@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(20, '09170000018', NULL, 'camille.garcia0020@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(21, '09170000019', NULL, 'nathan.mendoza0021@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(22, '09170000020', NULL, 'juan.bautista0022@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(23, '09170000021', NULL, 'maria.cruz0023@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(24, '09170000022', NULL, 'pedro.navarro0024@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(25, '09170000023', NULL, 'ana.ramos0025@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(26, '09170000024', NULL, 'carlos.castillo0026@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(27, '09170000025', NULL, 'sofia.flores0027@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(28, '09170000026', NULL, 'miguel.aquino0028@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(29, '09170000027', NULL, 'angela.torres0029@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(30, '09170000028', NULL, 'jose.rivera0030@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(31, '09170000029', NULL, 'gabriel.fernandez0031@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(32, '09170000030', NULL, 'patricia.delacruz0032@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(33, '09170000031', NULL, 'daniel.santos0033@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(34, '09170000032', NULL, 'andrea.reyes0034@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(35, '09170000033', NULL, 'mark.garcia0035@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(36, '09170000034', NULL, 'christine.mendoza0036@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(37, '09170000035', NULL, 'michael.bautista0037@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(38, '09170000036', NULL, 'nicole.cruz0038@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(39, '09170000037', NULL, 'joshua.navarro0039@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(40, '09170000038', NULL, 'camille.ramos0040@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(41, '09170000039', NULL, 'nathan.castillo0041@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(42, '09170000040', NULL, 'juan.flores0042@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(43, '09170000041', NULL, 'maria.aquino0043@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(44, '09170000042', NULL, 'pedro.torres0044@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(45, '09170000043', NULL, 'ana.rivera0045@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(46, '09170000044', NULL, 'carlos.fernandez0046@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(47, '09170000045', NULL, 'sofia.delacruz0047@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(48, '09170000046', NULL, 'miguel.santos0048@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(49, '09170000047', NULL, 'angela.reyes0049@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(50, '09170000048', NULL, 'jose.garcia0050@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(51, '09170000049', NULL, 'gabriel.mendoza0051@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(52, '09170000050', NULL, 'patricia.bautista0052@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(53, '09170000051', NULL, 'daniel.cruz0053@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(54, '09170000052', NULL, 'andrea.navarro0054@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(55, '09170000053', NULL, 'mark.ramos0055@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(56, '09170000054', NULL, 'christine.castillo0056@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(57, '09170000055', NULL, 'michael.flores0057@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(58, '09170000056', NULL, 'nicole.aquino0058@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(59, '09170000057', NULL, 'joshua.torres0059@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(60, '09170000058', NULL, 'camille.rivera0060@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(61, '09170000059', NULL, 'nathan.fernandez0061@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(62, '09170000060', NULL, 'juan.delacruz0062@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(63, '09170000061', NULL, 'maria.santos0063@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(64, '09170000062', NULL, 'pedro.reyes0064@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(65, '09170000063', NULL, 'ana.garcia0065@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(66, '09170000064', NULL, 'carlos.mendoza0066@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(67, '09170000065', NULL, 'sofia.bautista0067@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(68, '09170000066', NULL, 'miguel.cruz0068@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(69, '09170000067', NULL, 'angela.navarro0069@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(70, '09170000068', NULL, 'jose.ramos0070@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(71, '09170000069', NULL, 'gabriel.castillo0071@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(72, '09170000070', NULL, 'patricia.flores0072@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(73, '09170000071', NULL, 'daniel.aquino0073@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(74, '09170000072', NULL, 'andrea.torres0074@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(75, '09170000073', NULL, 'mark.rivera0075@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(76, '09170000074', NULL, 'christine.fernandez0076@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(77, '09170000075', NULL, 'michael.delacruz0077@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(78, '09170000076', NULL, 'nicole.santos0078@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(79, '09170000077', NULL, 'joshua.reyes0079@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(80, '09170000078', NULL, 'camille.garcia0080@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(81, '09170000079', NULL, 'nathan.mendoza0081@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(82, '09170000080', NULL, 'juan.bautista0082@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(83, '09170000081', NULL, 'maria.cruz0083@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(84, '09170000082', NULL, 'pedro.navarro0084@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(85, '09170000083', NULL, 'ana.ramos0085@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(86, '09170000084', NULL, 'carlos.castillo0086@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(87, '09170000085', NULL, 'sofia.flores0087@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(88, '09170000086', NULL, 'miguel.aquino0088@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(89, '09170000087', NULL, 'angela.torres0089@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(90, '09170000088', NULL, 'jose.rivera0090@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(91, '09170000089', NULL, 'gabriel.fernandez0091@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(92, '09170000090', NULL, 'patricia.delacruz0092@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(93, '09170000091', NULL, 'daniel.santos0093@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(94, '09170000092', NULL, 'andrea.reyes0094@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(95, '09170000093', NULL, 'mark.garcia0095@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(96, '09170000094', NULL, 'christine.mendoza0096@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(97, '09170000095', NULL, 'michael.bautista0097@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(98, '09170000096', NULL, 'nicole.cruz0098@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(99, '09170000097', NULL, 'joshua.navarro0099@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(100, '09170000098', NULL, 'camille.ramos0100@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(101, '09170000099', NULL, 'nathan.castillo0101@example.test', '2026-08-20 06:38:33', '2026-08-20 06:38:33');

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

INSERT INTO `member_educations` (`member_id`, `highest_educational_attainment`, `created_at`, `updated_at`) VALUES
(1, 'college', '2026-08-20 05:50:35', '2026-08-20 05:50:35'),
(2, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(3, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(4, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(5, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(6, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(7, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(8, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(9, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(10, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(11, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(12, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(13, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(14, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(15, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(16, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(17, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(18, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(19, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(20, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(21, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(22, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(23, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(24, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(25, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(26, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(27, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(28, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(29, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(30, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(31, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(32, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(33, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(34, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(35, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(36, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(37, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(38, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(39, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(40, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(41, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(42, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(43, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(44, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(45, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(46, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(47, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(48, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(49, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(50, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(51, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(52, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(53, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(54, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(55, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(56, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(57, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(58, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(59, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(60, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(61, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(62, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(63, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(64, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(65, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(66, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(67, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(68, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(69, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(70, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(71, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(72, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(73, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(74, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(75, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(76, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(77, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(78, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(79, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(80, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(81, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(82, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(83, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(84, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(85, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(86, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(87, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(88, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(89, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(90, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(91, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(92, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(93, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(94, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(95, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(96, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(97, 'Elementary', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(98, 'High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(99, 'Senior High School', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(100, 'College', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(101, 'Postgraduate', '2026-08-20 06:38:33', '2026-08-20 06:38:33');

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

INSERT INTO `member_livelihoods` (`member_id`, `employment_status`, `occupation`, `employer`, `monthly_income`, `created_at`, `updated_at`) VALUES
(1, 'employed', 'Acting Human Resources', 'Aviation Cooperative For Enhanced Services', 16000.00, '2026-08-20 05:50:35', '2026-08-20 05:50:35'),
(2, 'employed', 'Employee', 'Sample Company', 15000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(3, 'self_employed', 'Employee', NULL, 17500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(4, 'business_owner', 'Employee', NULL, 20000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(5, 'ofw', 'Employee', NULL, 22500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(6, 'retired', 'Employee', NULL, 25000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(7, 'student', 'Student', NULL, 27500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(8, 'unemployed', 'Employee', NULL, 30000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(9, 'employed', 'Employee', 'Sample Company', 32500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(10, 'self_employed', 'Employee', NULL, 35000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(11, 'business_owner', 'Employee', NULL, 37500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(12, 'ofw', 'Employee', NULL, 15000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(13, 'retired', 'Employee', NULL, 17500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(14, 'student', 'Student', NULL, 20000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(15, 'unemployed', 'Employee', NULL, 22500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(16, 'employed', 'Employee', 'Sample Company', 25000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(17, 'self_employed', 'Employee', NULL, 27500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(18, 'business_owner', 'Employee', NULL, 30000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(19, 'ofw', 'Employee', NULL, 32500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(20, 'retired', 'Employee', NULL, 35000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(21, 'student', 'Student', NULL, 37500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(22, 'unemployed', 'Employee', NULL, 15000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(23, 'employed', 'Employee', 'Sample Company', 17500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(24, 'self_employed', 'Employee', NULL, 20000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(25, 'business_owner', 'Employee', NULL, 22500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(26, 'ofw', 'Employee', NULL, 25000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(27, 'retired', 'Employee', NULL, 27500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(28, 'student', 'Student', NULL, 30000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(29, 'unemployed', 'Employee', NULL, 32500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(30, 'employed', 'Employee', 'Sample Company', 35000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(31, 'self_employed', 'Employee', NULL, 37500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(32, 'business_owner', 'Employee', NULL, 15000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(33, 'ofw', 'Employee', NULL, 17500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(34, 'retired', 'Employee', NULL, 20000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(35, 'student', 'Student', NULL, 22500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(36, 'unemployed', 'Employee', NULL, 25000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(37, 'employed', 'Employee', 'Sample Company', 27500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(38, 'self_employed', 'Employee', NULL, 30000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(39, 'business_owner', 'Employee', NULL, 32500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(40, 'ofw', 'Employee', NULL, 35000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(41, 'retired', 'Employee', NULL, 37500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(42, 'student', 'Student', NULL, 15000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(43, 'unemployed', 'Employee', NULL, 17500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(44, 'employed', 'Employee', 'Sample Company', 20000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(45, 'self_employed', 'Employee', NULL, 22500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(46, 'business_owner', 'Employee', NULL, 25000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(47, 'ofw', 'Employee', NULL, 27500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(48, 'retired', 'Employee', NULL, 30000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(49, 'student', 'Student', NULL, 32500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(50, 'unemployed', 'Employee', NULL, 35000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(51, 'employed', 'Employee', 'Sample Company', 37500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(52, 'self_employed', 'Employee', NULL, 15000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(53, 'business_owner', 'Employee', NULL, 17500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(54, 'ofw', 'Employee', NULL, 20000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(55, 'retired', 'Employee', NULL, 22500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(56, 'student', 'Student', NULL, 25000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(57, 'unemployed', 'Employee', NULL, 27500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(58, 'employed', 'Employee', 'Sample Company', 30000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(59, 'self_employed', 'Employee', NULL, 32500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(60, 'business_owner', 'Employee', NULL, 35000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(61, 'ofw', 'Employee', NULL, 37500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(62, 'retired', 'Employee', NULL, 15000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(63, 'student', 'Student', NULL, 17500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(64, 'unemployed', 'Employee', NULL, 20000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(65, 'employed', 'Employee', 'Sample Company', 22500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(66, 'self_employed', 'Employee', NULL, 25000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(67, 'business_owner', 'Employee', NULL, 27500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(68, 'ofw', 'Employee', NULL, 30000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(69, 'retired', 'Employee', NULL, 32500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(70, 'student', 'Student', NULL, 35000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(71, 'unemployed', 'Employee', NULL, 37500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(72, 'employed', 'Employee', 'Sample Company', 15000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(73, 'self_employed', 'Employee', NULL, 17500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(74, 'business_owner', 'Employee', NULL, 20000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(75, 'ofw', 'Employee', NULL, 22500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(76, 'retired', 'Employee', NULL, 25000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(77, 'student', 'Student', NULL, 27500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(78, 'unemployed', 'Employee', NULL, 30000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(79, 'employed', 'Employee', 'Sample Company', 32500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(80, 'self_employed', 'Employee', NULL, 35000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(81, 'business_owner', 'Employee', NULL, 37500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(82, 'ofw', 'Employee', NULL, 15000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(83, 'retired', 'Employee', NULL, 17500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(84, 'student', 'Student', NULL, 20000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(85, 'unemployed', 'Employee', NULL, 22500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(86, 'employed', 'Employee', 'Sample Company', 25000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(87, 'self_employed', 'Employee', NULL, 27500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(88, 'business_owner', 'Employee', NULL, 30000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(89, 'ofw', 'Employee', NULL, 32500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(90, 'retired', 'Employee', NULL, 35000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(91, 'student', 'Student', NULL, 37500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(92, 'unemployed', 'Employee', NULL, 15000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(93, 'employed', 'Employee', 'Sample Company', 17500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(94, 'self_employed', 'Employee', NULL, 20000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(95, 'business_owner', 'Employee', NULL, 22500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(96, 'ofw', 'Employee', NULL, 25000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(97, 'retired', 'Employee', NULL, 27500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(98, 'student', 'Student', NULL, 30000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(99, 'unemployed', 'Employee', NULL, 32500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(100, 'employed', 'Employee', 'Sample Company', 35000.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(101, 'self_employed', 'Employee', NULL, 37500.00, '2026-08-20 06:38:33', '2026-08-20 06:38:33');

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
  `nationality` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_profiles`
--

INSERT INTO `member_profiles` (`member_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birth_date`, `birth_place`, `sex`, `civil_status`, `nationality`, `created_at`, `updated_at`) VALUES
(1, 'Randall Jay', 'Veloria', 'Unarce', NULL, '2003-07-21', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 05:50:35', '2026-08-20 06:53:29'),
(2, 'Juan', 'Santos', 'Dela Cruz', NULL, '2001-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(3, 'Maria', 'Reyes', 'Santos', NULL, '2000-08-20', 'Quezon City', 'Female', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(4, 'Pedro', 'Garcia', 'Reyes', NULL, '1999-08-20', 'Quezon City', 'Male', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(5, 'Ana', 'Mendoza', 'Garcia', NULL, '1998-08-20', 'Quezon City', 'Female', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(6, 'Carlos', 'Bautista', 'Mendoza', NULL, '1997-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(7, 'Sofia', 'Cruz', 'Bautista', NULL, '1996-08-20', 'Quezon City', 'Female', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(8, 'Miguel', 'Navarro', 'Cruz', NULL, '1995-08-20', 'Quezon City', 'Male', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(9, 'Angela', 'Ramos', 'Navarro', NULL, '1994-08-20', 'Quezon City', 'Female', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(10, 'Jose', 'Castillo', 'Ramos', NULL, '1993-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(11, 'Gabriel', 'Flores', 'Castillo', NULL, '1992-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(12, 'Patricia', 'Santos', 'Flores', NULL, '1991-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(13, 'Daniel', 'Reyes', 'Aquino', NULL, '1990-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(14, 'Andrea', 'Garcia', 'Torres', NULL, '1989-08-20', 'Quezon City', 'Female', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(15, 'Mark', 'Mendoza', 'Rivera', NULL, '1988-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(16, 'Christine', 'Bautista', 'Fernandez', NULL, '1987-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(17, 'Michael', 'Cruz', 'Dela Cruz', NULL, '1986-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(18, 'Nicole', 'Navarro', 'Santos', NULL, '1985-08-20', 'Quezon City', 'Female', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(19, 'Joshua', 'Ramos', 'Reyes', NULL, '1984-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(20, 'Camille', 'Castillo', 'Garcia', NULL, '1983-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(21, 'Nathan', 'Flores', 'Mendoza', NULL, '1982-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(22, 'Juan', 'Santos', 'Bautista', NULL, '1981-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(23, 'Maria', 'Reyes', 'Cruz', NULL, '1980-08-20', 'Quezon City', 'Female', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(24, 'Pedro', 'Garcia', 'Navarro', NULL, '1979-08-20', 'Quezon City', 'Male', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(25, 'Ana', 'Mendoza', 'Ramos', NULL, '1978-08-20', 'Quezon City', 'Female', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(26, 'Carlos', 'Bautista', 'Castillo', NULL, '1977-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(27, 'Sofia', 'Cruz', 'Flores', NULL, '1976-08-20', 'Quezon City', 'Female', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(28, 'Miguel', 'Navarro', 'Aquino', NULL, '1975-08-20', 'Quezon City', 'Male', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(29, 'Angela', 'Ramos', 'Torres', NULL, '1974-08-20', 'Quezon City', 'Female', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(30, 'Jose', 'Castillo', 'Rivera', NULL, '1973-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(31, 'Gabriel', 'Flores', 'Fernandez', NULL, '1972-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(32, 'Patricia', 'Santos', 'Dela Cruz', NULL, '1971-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(33, 'Daniel', 'Reyes', 'Santos', NULL, '1970-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(34, 'Andrea', 'Garcia', 'Reyes', NULL, '1969-08-20', 'Quezon City', 'Female', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(35, 'Mark', 'Mendoza', 'Garcia', NULL, '1968-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(36, 'Christine', 'Bautista', 'Mendoza', NULL, '1967-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(37, 'Michael', 'Cruz', 'Bautista', NULL, '1966-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(38, 'Nicole', 'Navarro', 'Cruz', NULL, '1965-08-20', 'Quezon City', 'Female', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(39, 'Joshua', 'Ramos', 'Navarro', NULL, '1964-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(40, 'Camille', 'Castillo', 'Ramos', NULL, '1963-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(41, 'Nathan', 'Flores', 'Castillo', NULL, '1962-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(42, 'Juan', 'Santos', 'Flores', NULL, '1961-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(43, 'Maria', 'Reyes', 'Aquino', NULL, '1960-08-20', 'Quezon City', 'Female', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(44, 'Pedro', 'Garcia', 'Torres', NULL, '1959-08-20', 'Quezon City', 'Male', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(45, 'Ana', 'Mendoza', 'Rivera', NULL, '1958-08-20', 'Quezon City', 'Female', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(46, 'Carlos', 'Bautista', 'Fernandez', NULL, '1957-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(47, 'Sofia', 'Cruz', 'Dela Cruz', NULL, '2001-08-20', 'Quezon City', 'Female', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(48, 'Miguel', 'Navarro', 'Santos', NULL, '2000-08-20', 'Quezon City', 'Male', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(49, 'Angela', 'Ramos', 'Reyes', NULL, '1999-08-20', 'Quezon City', 'Female', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(50, 'Jose', 'Castillo', 'Garcia', NULL, '1998-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(51, 'Gabriel', 'Flores', 'Mendoza', NULL, '1997-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(52, 'Patricia', 'Santos', 'Bautista', NULL, '1996-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(53, 'Daniel', 'Reyes', 'Cruz', NULL, '1995-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(54, 'Andrea', 'Garcia', 'Navarro', NULL, '1994-08-20', 'Quezon City', 'Female', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(55, 'Mark', 'Mendoza', 'Ramos', NULL, '1993-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(56, 'Christine', 'Bautista', 'Castillo', NULL, '1992-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(57, 'Michael', 'Cruz', 'Flores', NULL, '1991-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(58, 'Nicole', 'Navarro', 'Aquino', NULL, '1990-08-20', 'Quezon City', 'Female', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(59, 'Joshua', 'Ramos', 'Torres', NULL, '1989-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(60, 'Camille', 'Castillo', 'Rivera', NULL, '1988-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(61, 'Nathan', 'Flores', 'Fernandez', NULL, '1987-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(62, 'Juan', 'Santos', 'Dela Cruz', NULL, '1986-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(63, 'Maria', 'Reyes', 'Santos', NULL, '1985-08-20', 'Quezon City', 'Female', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(64, 'Pedro', 'Garcia', 'Reyes', NULL, '1984-08-20', 'Quezon City', 'Male', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(65, 'Ana', 'Mendoza', 'Garcia', NULL, '1983-08-20', 'Quezon City', 'Female', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(66, 'Carlos', 'Bautista', 'Mendoza', NULL, '1982-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(67, 'Sofia', 'Cruz', 'Bautista', NULL, '1981-08-20', 'Quezon City', 'Female', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(68, 'Miguel', 'Navarro', 'Cruz', NULL, '1980-08-20', 'Quezon City', 'Male', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(69, 'Angela', 'Ramos', 'Navarro', NULL, '1979-08-20', 'Quezon City', 'Female', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(70, 'Jose', 'Castillo', 'Ramos', NULL, '1978-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(71, 'Gabriel', 'Flores', 'Castillo', NULL, '1977-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(72, 'Patricia', 'Santos', 'Flores', NULL, '1976-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(73, 'Daniel', 'Reyes', 'Aquino', NULL, '1975-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(74, 'Andrea', 'Garcia', 'Torres', NULL, '1974-08-20', 'Quezon City', 'Female', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(75, 'Mark', 'Mendoza', 'Rivera', NULL, '1973-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(76, 'Christine', 'Bautista', 'Fernandez', NULL, '1972-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(77, 'Michael', 'Cruz', 'Dela Cruz', NULL, '1971-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(78, 'Nicole', 'Navarro', 'Santos', NULL, '1970-08-20', 'Quezon City', 'Female', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(79, 'Joshua', 'Ramos', 'Reyes', NULL, '1969-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(80, 'Camille', 'Castillo', 'Garcia', NULL, '1968-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(81, 'Nathan', 'Flores', 'Mendoza', NULL, '1967-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(82, 'Juan', 'Santos', 'Bautista', NULL, '1966-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(83, 'Maria', 'Reyes', 'Cruz', NULL, '1965-08-20', 'Quezon City', 'Female', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(84, 'Pedro', 'Garcia', 'Navarro', NULL, '1964-08-20', 'Quezon City', 'Male', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(85, 'Ana', 'Mendoza', 'Ramos', NULL, '1963-08-20', 'Quezon City', 'Female', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(86, 'Carlos', 'Bautista', 'Castillo', NULL, '1962-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(87, 'Sofia', 'Cruz', 'Flores', NULL, '1961-08-20', 'Quezon City', 'Female', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(88, 'Miguel', 'Navarro', 'Aquino', NULL, '1960-08-20', 'Quezon City', 'Male', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(89, 'Angela', 'Ramos', 'Torres', NULL, '1959-08-20', 'Quezon City', 'Female', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(90, 'Jose', 'Castillo', 'Rivera', NULL, '1958-08-20', 'Quezon City', 'Male', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(91, 'Gabriel', 'Flores', 'Fernandez', NULL, '1957-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(92, 'Patricia', 'Santos', 'Dela Cruz', NULL, '2001-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(93, 'Daniel', 'Reyes', 'Santos', NULL, '2000-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(94, 'Andrea', 'Garcia', 'Reyes', NULL, '1999-08-20', 'Quezon City', 'Female', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(95, 'Mark', 'Mendoza', 'Garcia', NULL, '1998-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(96, 'Christine', 'Bautista', 'Mendoza', NULL, '1997-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(97, 'Michael', 'Cruz', 'Bautista', NULL, '1996-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(98, 'Nicole', 'Navarro', 'Cruz', NULL, '1995-08-20', 'Quezon City', 'Female', 'Single', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(99, 'Joshua', 'Ramos', 'Navarro', NULL, '1994-08-20', 'Quezon City', 'Male', 'Married', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(100, 'Camille', 'Castillo', 'Ramos', NULL, '1993-08-20', 'Quezon City', 'Female', 'Widowed', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33'),
(101, 'Nathan', 'Flores', 'Castillo', NULL, '1992-08-20', 'Quezon City', 'Male', 'Separated', 'Filipino', '2026-08-20 06:38:33', '2026-08-20 06:38:33');

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

INSERT INTO `migrations` (`id`, `migration`, `batch`, `created_at`) VALUES
(1, 'App\\Console\\Migrations\\CreateUsersTable', 1, '2026-08-11 07:22:11'),
(2, 'App\\Console\\Migrations\\CreateMembersTable', 2, '2026-08-11 07:22:21'),
(3, 'App\\Console\\Migrations\\CreateMemberProfilesTable', 3, '2026-08-11 07:22:21'),
(4, 'App\\Console\\Migrations\\CreateMemberContactsTable', 4, '2026-08-11 07:22:21'),
(5, 'App\\Console\\Migrations\\CreateMemberAddressesTable', 5, '2026-08-11 07:22:21'),
(6, 'App\\Console\\Migrations\\CreateMemberEducationsTable', 6, '2026-08-11 07:22:21'),
(7, 'App\\Console\\Migrations\\CreateMemberLivelihoodsTable', 7, '2026-08-11 07:22:21'),
(8, 'App\\Console\\Migrations\\CreateMemberBeneficiariesTable', 8, '2026-08-11 07:22:21'),
(9, 'App\\Console\\Migrations\\RemoveSharePercentageFromMemberBeneficiariesTable', 9, '2026-08-13 02:53:22'),
(10, 'App\\Console\\Migrations\\RemoveArchivedStatusFromMembersTable', 10, '2026-08-14 01:48:07'),
(11, 'App\\Console\\Migrations\\CreateActivityLogsTable', 11, '2026-08-14 03:17:45'),
(12, 'App\\Console\\Migrations\\CreateLoansTable', 12, '2026-08-19 03:43:20'),
(13, 'App\\Console\\Migrations\\CreateLoanAmortizationsTable', 13, '2026-08-19 03:43:20'),
(14, 'App\\Console\\Migrations\\CreateLoanPaymentsTable', 14, '2026-08-19 03:43:20'),
(15, 'App\\Console\\Migrations\\CreateLoanPaymentAllocationsTable', 15, '2026-08-19 03:43:20'),
(16, 'App\\Console\\Migrations\\AddPaymentReversalFields', 16, '2026-08-19 08:00:33'),
(17, 'App\\Console\\Migrations\\CreateAccountsTable', 17, '2026-08-20 07:23:49'),
(18, 'App\\Console\\Migrations\\CreateJournalVouchersTable', 18, '2026-08-20 07:23:49'),
(19, 'App\\Console\\Migrations\\CreateJournalLinesTable', 19, '2026-08-20 07:23:49'),
(20, 'App\\Console\\Migrations\\AddPostedStatusToJournalVouchersTable', 20, '2026-08-24 00:48:18'),
(21, 'App\\Console\\Migrations\\AddReversalLinkToJournalVouchersTable', 21, '2026-08-24 01:23:53');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `middle_name`, `last_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$Y6oHM7R4tkYHqpkUb.bkxuYRSEb9cLiYnkt1tLSpW86.pTHhK.3SC', 'System', NULL, 'Administrator', 1, '2026-08-04 01:58:35', '2026-08-04 01:58:35');

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
