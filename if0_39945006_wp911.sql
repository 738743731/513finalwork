-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- 主机： sql307.infinityfree.com
-- 生成日期： 2026-02-03 22:11:46
-- 服务器版本： 11.4.9-MariaDB
-- PHP 版本： 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `if0_39945006_wp911`
--

-- --------------------------------------------------------

--
-- 表的结构 `churn_models`
--

CREATE TABLE `churn_models` (
  `id` int(11) NOT NULL,
  `model_name` varchar(255) DEFAULT NULL,
  `accuracy` decimal(5,4) DEFAULT NULL,
  `precision` decimal(5,4) DEFAULT NULL,
  `recall` decimal(5,4) DEFAULT NULL,
  `f1_score` decimal(5,4) DEFAULT NULL,
  `roc_auc` decimal(5,4) DEFAULT NULL,
  `feature_importance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- 转存表中的数据 `churn_models`
--

INSERT INTO `churn_models` (`id`, `model_name`, `accuracy`, `precision`, `recall`, `f1_score`, `roc_auc`, `feature_importance`, `is_active`, `created_at`) VALUES
(1, 'XGBoost Churn Model', '0.8720', '0.8450', '0.8120', '0.8280', '0.9230', '{\"days_since_last_order\": 0.87, \"order_count\": 0.54, \"months_as_customer\": 0.32, \"total_spent\": 0.15, \"category_preference\": 0.12, \"avg_order_value\": 0.08}', 1, '2025-12-12 10:06:47'),
(2, 'XGBoost Classifier v2.0', '0.8700', '0.8500', '0.8200', '0.8350', '0.9300', '{\"days_since_last_order\": 0.89, \"order_count\": 0.61, \"months_as_customer\": 0.35, \"total_spent\": 0.22, \"avg_order_value\": 0.18, \"product_variety\": 0.12}', 1, '2024-12-15 18:30:00'),
(3, 'Random Forest Ensemble', '0.8400', '0.8100', '0.7900', '0.8000', '0.9100', '{\"days_since_last_order\": 0.85, \"order_count\": 0.58, \"months_as_customer\": 0.31, \"total_spent\": 0.19, \"discount_usage\": 0.15}', 0, '2024-12-10 22:45:00'),
(4, 'XGBoost Classifier v2.0', '0.8700', '0.8500', '0.8200', '0.8350', '0.9300', '{\"days_since_last_order\": 0.89, \"order_count\": 0.61, \"months_as_customer\": 0.35, \"total_spent\": 0.22, \"avg_order_value\": 0.18, \"product_variety\": 0.12}', 1, '2024-12-15 18:30:00'),
(5, 'Random Forest Ensemble', '0.8400', '0.8100', '0.7900', '0.8000', '0.9100', '{\"days_since_last_order\": 0.85, \"order_count\": 0.58, \"months_as_customer\": 0.31, \"total_spent\": 0.19, \"discount_usage\": 0.15}', 0, '2024-12-10 22:45:00');

-- --------------------------------------------------------

--
-- 表的结构 `customer_behavior`
--

CREATE TABLE `customer_behavior` (
  `id` int(11) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `months_as_customer` int(11) DEFAULT 0,
  `order_count` int(11) DEFAULT 0,
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `avg_order_value` decimal(10,2) DEFAULT 0.00,
  `days_since_last_order` int(11) DEFAULT 0,
  `last_order_date` date DEFAULT NULL,
  `last_purchase_date` date DEFAULT NULL,
  `first_purchase_date` date DEFAULT NULL,
  `favorite_category` varchar(100) DEFAULT NULL,
  `churned` tinyint(1) DEFAULT 0,
  `churn_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `customer_behavior`
--

INSERT INTO `customer_behavior` (`id`, `customer_email`, `customer_id`, `months_as_customer`, `order_count`, `total_spent`, `avg_order_value`, `days_since_last_order`, `last_order_date`, `last_purchase_date`, `first_purchase_date`, `favorite_category`, `churned`, `churn_date`, `created_at`, `updated_at`) VALUES
(1, 'john.danger@email.com', NULL, 3, 2, '89.98', '0.00', 45, '2025-10-28', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(2, 'sarah.risky@email.com', NULL, 6, 5, '224.95', '0.00', 60, '2025-10-13', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(3, 'mike.atrisk@email.com', NULL, 2, 1, '39.99', '0.00', 55, '2025-10-18', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(4, 'lisa.worried@email.com', NULL, 8, 3, '104.97', '0.00', 42, '2025-10-31', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(5, 'tom.concern@email.com', NULL, 12, 8, '359.92', '0.00', 65, '2025-10-08', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(6, 'alice.medium@email.com', NULL, 18, 12, '599.88', '0.00', 25, '2025-11-17', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(7, 'bob.moderate@email.com', NULL, 9, 6, '239.94', '0.00', 22, '2025-11-20', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(8, 'carol.steady@email.com', NULL, 24, 15, '749.85', '0.00', 18, '2025-11-24', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(9, 'dave.regular@email.com', NULL, 15, 9, '404.91', '0.00', 28, '2025-11-14', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(10, 'eva.balanced@email.com', NULL, 6, 4, '159.96', '0.00', 20, '2025-11-22', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(11, 'frank.safe@email.com', NULL, 36, 25, '1249.75', '0.00', 3, '2025-12-09', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(12, 'grace.loyal@email.com', NULL, 48, 32, '1599.68', '0.00', 1, '2025-12-11', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(13, 'henry.active@email.com', NULL, 12, 8, '399.92', '0.00', 5, '2025-12-07', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(14, 'irena.engaged@email.com', NULL, 24, 18, '899.82', '0.00', 2, '2025-12-10', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(15, 'jack.frequent@email.com', NULL, 30, 22, '1099.78', '0.00', 4, '2025-12-08', NULL, NULL, NULL, 0, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(16, 'karen.lost@email.com', NULL, 4, 2, '79.98', '0.00', 120, '2025-08-14', NULL, NULL, NULL, 1, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(17, 'leo.gone@email.com', NULL, 6, 3, '134.97', '0.00', 95, '2025-09-08', NULL, NULL, NULL, 1, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(18, 'mona.left@email.com', NULL, 2, 1, '49.99', '0.00', 110, '2025-08-24', NULL, NULL, NULL, 1, NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(19, '3206246004@qq.com', NULL, 24, 15, '1250.50', '0.00', 5, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(20, 'chen@email.com', NULL, 36, 28, '3200.75', '0.00', 2, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(21, 'olivia.thompson@email.com', NULL, 6, 3, '180.00', '0.00', 120, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(22, '123@163.com', NULL, 48, 42, '5500.25', '0.00', 1, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(23, 'sophia.rdz@email.com', NULL, 3, 1, '59.99', '0.00', 150, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(24, 'kim@email.com', NULL, 18, 12, '980.50', '0.00', 45, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(25, 'f@email.com', NULL, 60, 55, '8500.00', '0.00', 3, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(26, 'wilson@email.com', NULL, 2, 0, '0.00', '0.00', 180, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(27, 'patel@email.com', NULL, 72, 68, '12500.00', '0.00', 0, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(28, 'm@email.com', NULL, 15, 9, '720.25', '0.00', 30, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(29, 's@email.com', NULL, 30, 22, '2850.75', '0.00', 10, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(30, 'ethan.j@email.com', NULL, 4, 1, '69.99', '0.00', 160, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(31, 'mia.w@email.com', NULL, 42, 38, '6200.50', '0.00', 5, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(32, 'brown@email.com', NULL, 8, 4, '320.00', '0.00', 90, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(33, 'charlotte.d@email.com', NULL, 84, 75, '15800.00', '0.00', 2, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(34, 'michael.j@email.com', NULL, 20, 16, '2100.25', '0.00', 15, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(35, 'sarah.w@email.com', NULL, 28, 24, '3800.50', '0.00', 8, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(36, 'david.b@email.com', NULL, 12, 8, '950.75', '0.00', 60, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(37, 'emily.d@email.com', NULL, 5, 2, '139.98', '0.00', 130, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(38, 'robert.m@email.com', NULL, 32, 30, '4800.00', '0.00', 12, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:45:31', '2025-12-15 13:45:31'),
(39, '3206246004@qq.com', NULL, 24, 15, '1250.50', '0.00', 5, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(40, 'chen@email.com', NULL, 36, 28, '3200.75', '0.00', 2, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(41, 'olivia.thompson@email.com', NULL, 6, 3, '180.00', '0.00', 120, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(42, '123@163.com', NULL, 48, 42, '5500.25', '0.00', 1, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(43, 'sophia.rdz@email.com', NULL, 3, 1, '59.99', '0.00', 150, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(44, 'kim@email.com', NULL, 18, 12, '980.50', '0.00', 45, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(45, 'f@email.com', NULL, 60, 55, '8500.00', '0.00', 3, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(46, 'wilson@email.com', NULL, 2, 0, '0.00', '0.00', 180, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(47, 'patel@email.com', NULL, 72, 68, '12500.00', '0.00', 0, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(48, 'm@email.com', NULL, 15, 9, '720.25', '0.00', 30, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(49, 's@email.com', NULL, 30, 22, '2850.75', '0.00', 10, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(50, 'ethan.j@email.com', NULL, 4, 1, '69.99', '0.00', 160, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(51, 'mia.w@email.com', NULL, 42, 38, '6200.50', '0.00', 5, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(52, 'brown@email.com', NULL, 8, 4, '320.00', '0.00', 90, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(53, 'charlotte.d@email.com', NULL, 84, 75, '15800.00', '0.00', 2, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(54, 'michael.j@email.com', NULL, 20, 16, '2100.25', '0.00', 15, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(55, 'sarah.w@email.com', NULL, 28, 24, '3800.50', '0.00', 8, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(56, 'david.b@email.com', NULL, 12, 8, '950.75', '0.00', 60, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(57, 'emily.d@email.com', NULL, 5, 2, '139.98', '0.00', 130, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(58, 'robert.m@email.com', NULL, 32, 30, '4800.00', '0.00', 12, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(59, 'michael.johnson2@email.com', NULL, 20, 16, '2100.25', '0.00', 15, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(60, 'sarah.williams2@email.com', NULL, 28, 24, '3800.50', '0.00', 8, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(61, 'david.brown2@email.com', NULL, 12, 8, '950.75', '0.00', 60, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(62, 'emily.davis2@email.com', NULL, 5, 2, '139.98', '0.00', 130, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(63, 'robert.miller2@email.com', NULL, 32, 30, '4800.00', '0.00', 12, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(64, 'jennifer.wilson2@email.com', NULL, 8, 5, '450.50', '0.00', 85, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(65, 'thomas.moore2@email.com', NULL, 15, 11, '1200.75', '0.00', 42, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(66, 'lisa.taylor2@email.com', NULL, 22, 18, '2800.25', '0.00', 25, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(67, 'daniel.anderson2@email.com', NULL, 40, 35, '6200.00', '0.00', 5, NULL, NULL, NULL, NULL, 0, NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(68, 'karen.thomas2@email.com', NULL, 3, 1, '69.99', '0.00', 155, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30');

-- --------------------------------------------------------

--
-- 表的结构 `customer_churn_predictions`
--

CREATE TABLE `customer_churn_predictions` (
  `id` int(11) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `prediction_date` date NOT NULL,
  `churn_probability` decimal(5,4) NOT NULL,
  `risk_level` enum('Low','Medium','High') DEFAULT 'Low',
  `feature_days_since_last_order` decimal(5,4) DEFAULT NULL,
  `feature_order_count` decimal(5,4) DEFAULT NULL,
  `feature_months_as_customer` decimal(5,4) DEFAULT NULL,
  `feature_total_spent` decimal(5,4) DEFAULT NULL,
  `confidence_score` decimal(5,4) DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `actual_churn` tinyint(1) DEFAULT NULL,
  `actual_churn_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `customer_churn_predictions`
--

INSERT INTO `customer_churn_predictions` (`id`, `customer_email`, `prediction_date`, `churn_probability`, `risk_level`, `feature_days_since_last_order`, `feature_order_count`, `feature_months_as_customer`, `feature_total_spent`, `confidence_score`, `recommendation`, `actual_churn`, `actual_churn_date`, `created_at`) VALUES
(1, 'john.danger@email.com', '2025-12-12', '0.8500', 'High', NULL, NULL, NULL, NULL, NULL, 'Send retention email with 20% discount', NULL, NULL, '2025-12-12 10:06:47'),
(2, 'sarah.risky@email.com', '2025-12-12', '0.7800', 'High', NULL, NULL, NULL, NULL, NULL, 'Personalized outreach and special offer', NULL, NULL, '2025-12-12 10:06:47'),
(3, 'mike.atrisk@email.com', '2025-12-12', '0.8200', 'High', NULL, NULL, NULL, NULL, NULL, 'Reactivate with limited-time promotion', NULL, NULL, '2025-12-12 10:06:47'),
(4, 'lisa.worried@email.com', '2025-12-12', '0.7600', 'High', NULL, NULL, NULL, NULL, NULL, 'Loyalty program invitation', NULL, NULL, '2025-12-12 10:06:47'),
(5, 'tom.concern@email.com', '2025-12-12', '0.8100', 'High', NULL, NULL, NULL, NULL, NULL, 'Exclusive content access offer', NULL, NULL, '2025-12-12 10:06:47'),
(6, 'alice.medium@email.com', '2025-12-12', '0.6200', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Newsletter with personalized recommendations', NULL, NULL, '2025-12-12 10:06:47'),
(7, 'bob.moderate@email.com', '2025-12-12', '0.5800', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Early access to new releases', NULL, NULL, '2025-12-12 10:06:47'),
(8, 'carol.steady@email.com', '2025-12-12', '0.4500', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Regular engagement through email campaigns', NULL, NULL, '2025-12-12 10:06:47'),
(9, 'dave.regular@email.com', '2025-12-12', '0.5300', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Cross-selling suggestions based on history', NULL, NULL, '2025-12-12 10:06:47'),
(10, 'eva.balanced@email.com', '2025-12-12', '0.4900', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Community event invitations', NULL, NULL, '2025-12-12 10:06:47'),
(11, 'frank.safe@email.com', '2025-12-12', '0.1200', 'Low', NULL, NULL, NULL, NULL, NULL, 'Continue regular communication', NULL, NULL, '2025-12-12 10:06:47'),
(12, 'grace.loyal@email.com', '2025-12-12', '0.0800', 'Low', NULL, NULL, NULL, NULL, NULL, 'VIP treatment and exclusive offers', NULL, NULL, '2025-12-12 10:06:47'),
(13, 'henry.active@email.com', '2025-12-12', '0.1500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Upsell premium features', NULL, NULL, '2025-12-12 10:06:47'),
(14, 'irena.engaged@email.com', '2025-12-12', '0.1000', 'Low', NULL, NULL, NULL, NULL, NULL, 'Referral program promotion', NULL, NULL, '2025-12-12 10:06:47'),
(15, 'jack.frequent@email.com', '2025-12-12', '0.1800', 'Low', NULL, NULL, NULL, NULL, NULL, 'Premium membership offer', NULL, NULL, '2025-12-12 10:06:47'),
(16, 'john.danger@email.com', '2025-12-11', '0.8300', 'High', NULL, NULL, NULL, NULL, NULL, 'Previous prediction', NULL, NULL, '2025-12-12 10:06:47'),
(17, 'sarah.risky@email.com', '2025-12-11', '0.7700', 'High', NULL, NULL, NULL, NULL, NULL, 'Previous prediction', NULL, NULL, '2025-12-12 10:06:47'),
(18, 'alice.medium@email.com', '2025-12-10', '0.6000', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Previous prediction', NULL, NULL, '2025-12-12 10:06:47'),
(19, 'frank.safe@email.com', '2025-12-10', '0.1100', 'Low', NULL, NULL, NULL, NULL, NULL, 'Previous prediction', NULL, NULL, '2025-12-12 10:06:47'),
(20, 'olivia.thompson@email.com', '2025-12-15', '0.8500', 'High', NULL, NULL, NULL, NULL, NULL, 'High risk - send retention email and offer discount', NULL, NULL, '2025-12-15 13:45:31'),
(21, 'sophia.rdz@email.com', '2025-12-15', '0.9200', 'High', NULL, NULL, NULL, NULL, NULL, 'Very high risk - immediate intervention needed', NULL, NULL, '2025-12-15 13:45:31'),
(22, 'wilson@email.com', '2025-12-15', '0.8800', 'High', NULL, NULL, NULL, NULL, NULL, 'Inactive for 6 months - re-engagement campaign', NULL, NULL, '2025-12-15 13:45:31'),
(23, 'ethan.j@email.com', '2025-12-15', '0.8100', 'High', NULL, NULL, NULL, NULL, NULL, 'High risk - limited purchase history', NULL, NULL, '2025-12-15 13:45:31'),
(24, 'emily.d@email.com', '2025-12-15', '0.7900', 'High', NULL, NULL, NULL, NULL, NULL, 'High risk - low engagement', NULL, NULL, '2025-12-15 13:45:31'),
(25, 'michael.j@email.com', '2025-12-15', '0.2500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Active customer - continue engagement', NULL, NULL, '2025-12-15 13:45:31'),
(26, 'sarah.w@email.com', '2025-12-15', '0.1800', 'Low', NULL, NULL, NULL, NULL, NULL, 'Loyal customer - consider upselling', NULL, NULL, '2025-12-15 13:45:31'),
(27, 'david.b@email.com', '2025-12-15', '0.4200', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Medium risk - monitor activity', NULL, NULL, '2025-12-15 13:45:31'),
(28, 'robert.m@email.com', '2025-12-15', '0.1500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Very active - premium customer', NULL, NULL, '2025-12-15 13:45:31'),
(29, 'kim@email.com', '2025-12-15', '0.6200', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Medium risk - check in regularly', NULL, NULL, '2025-12-15 13:45:31'),
(30, 'm@email.com', '2025-12-15', '0.5800', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Moderate risk - offer loyalty rewards', NULL, NULL, '2025-12-15 13:45:31'),
(31, 'brown@email.com', '2025-12-15', '0.6700', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Medium-high risk - send personalized offer', NULL, NULL, '2025-12-15 13:45:31'),
(32, 'jennifer.w@email.com', '2025-12-15', '0.5500', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Medium risk - engagement campaign', NULL, NULL, '2025-12-15 13:45:31'),
(33, 'thomas.m@email.com', '2025-12-15', '0.4900', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Borderline risk - monitor closely', NULL, NULL, '2025-12-15 13:45:31'),
(34, '3206246004@qq.com', '2025-12-15', '0.1200', 'Low', NULL, NULL, NULL, NULL, NULL, 'Very active - loyal customer', NULL, NULL, '2025-12-15 13:45:31'),
(35, 'chen@email.com', '2025-12-15', '0.0800', 'Low', NULL, NULL, NULL, NULL, NULL, 'High-value customer - maintain relationship', NULL, NULL, '2025-12-15 13:45:31'),
(36, '123@163.com', '2025-12-15', '0.0500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Premium customer - advocate', NULL, NULL, '2025-12-15 13:45:31'),
(37, 'f@email.com', '2025-12-15', '0.0300', 'Low', NULL, NULL, NULL, NULL, NULL, 'Top customer - special treatment', NULL, NULL, '2025-12-15 13:45:31'),
(38, 'patel@email.com', '2025-12-15', '0.0200', 'Low', NULL, NULL, NULL, NULL, NULL, 'Best customer - VIP status', NULL, NULL, '2025-12-15 13:45:31'),
(39, 's@email.com', '2025-12-15', '0.1500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Regular customer - stable', NULL, NULL, '2025-12-15 13:45:31'),
(40, 'mia.w@email.com', '2025-12-15', '0.1000', 'Low', NULL, NULL, NULL, NULL, NULL, 'High-value - frequent purchases', NULL, NULL, '2025-12-15 13:45:31'),
(41, 'charlotte.d@email.com', '2025-12-15', '0.0100', 'Low', NULL, NULL, NULL, NULL, NULL, 'Ultimate customer - lifetime value', NULL, NULL, '2025-12-15 13:45:31'),
(42, 'lisa.t@email.com', '2025-12-15', '0.2200', 'Low', NULL, NULL, NULL, NULL, NULL, 'Regular buyer - low risk', NULL, NULL, '2025-12-15 13:45:31'),
(43, 'daniel.a@email.com', '2025-12-15', '0.1900', 'Low', NULL, NULL, NULL, NULL, NULL, 'Active member - good engagement', NULL, NULL, '2025-12-15 13:45:31'),
(44, 'karen.t@email.com', '2025-12-15', '0.2800', 'Low', NULL, NULL, NULL, NULL, NULL, 'Stable customer - normal activity', NULL, NULL, '2025-12-15 13:45:31'),
(45, 'chris.j@email.com', '2025-12-15', '0.3200', 'Low', NULL, NULL, NULL, NULL, NULL, 'Regular purchaser - healthy', NULL, NULL, '2025-12-15 13:45:31'),
(46, 'olivia.thompson@email.com', '2025-12-15', '0.8500', 'High', NULL, NULL, NULL, NULL, NULL, 'High risk - send retention email and offer discount', NULL, NULL, '2025-12-15 13:53:54'),
(47, 'sophia.rdz@email.com', '2025-12-15', '0.9200', 'High', NULL, NULL, NULL, NULL, NULL, 'Very high risk - immediate intervention needed', NULL, NULL, '2025-12-15 13:53:54'),
(48, 'wilson@email.com', '2025-12-15', '0.8800', 'High', NULL, NULL, NULL, NULL, NULL, 'Inactive for 6 months - re-engagement campaign', NULL, NULL, '2025-12-15 13:53:54'),
(49, 'ethan.j@email.com', '2025-12-15', '0.8100', 'High', NULL, NULL, NULL, NULL, NULL, 'High risk - limited purchase history', NULL, NULL, '2025-12-15 13:53:54'),
(50, 'emily.d@email.com', '2025-12-15', '0.7900', 'High', NULL, NULL, NULL, NULL, NULL, 'High risk - low engagement', NULL, NULL, '2025-12-15 13:53:54'),
(51, 'michael.j@email.com', '2025-12-15', '0.2500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Active customer - continue engagement', NULL, NULL, '2025-12-15 13:53:54'),
(52, 'sarah.w@email.com', '2025-12-15', '0.1800', 'Low', NULL, NULL, NULL, NULL, NULL, 'Loyal customer - consider upselling', NULL, NULL, '2025-12-15 13:53:54'),
(53, 'david.b@email.com', '2025-12-15', '0.4200', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Medium risk - monitor activity', NULL, NULL, '2025-12-15 13:53:54'),
(54, 'robert.m@email.com', '2025-12-15', '0.1500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Very active - premium customer', NULL, NULL, '2025-12-15 13:53:54'),
(55, 'kim@email.com', '2025-12-15', '0.6200', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Medium risk - check in regularly', NULL, NULL, '2025-12-15 13:53:54'),
(56, 'm@email.com', '2025-12-15', '0.5800', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Moderate risk - offer loyalty rewards', NULL, NULL, '2025-12-15 13:53:54'),
(57, 'brown@email.com', '2025-12-15', '0.6700', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Medium-high risk - send personalized offer', NULL, NULL, '2025-12-15 13:53:54'),
(58, 'jennifer.w@email.com', '2025-12-15', '0.5500', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Medium risk - engagement campaign', NULL, NULL, '2025-12-15 13:53:54'),
(59, 'thomas.m@email.com', '2025-12-15', '0.4900', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Borderline risk - monitor closely', NULL, NULL, '2025-12-15 13:53:54'),
(60, '3206246004@qq.com', '2025-12-15', '0.1200', 'Low', NULL, NULL, NULL, NULL, NULL, 'Very active - loyal customer', NULL, NULL, '2025-12-15 13:53:54'),
(61, 'chen@email.com', '2025-12-15', '0.0800', 'Low', NULL, NULL, NULL, NULL, NULL, 'High-value customer - maintain relationship', NULL, NULL, '2025-12-15 13:53:54'),
(62, '123@163.com', '2025-12-15', '0.0500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Premium customer - advocate', NULL, NULL, '2025-12-15 13:53:54'),
(63, 'f@email.com', '2025-12-15', '0.0300', 'Low', NULL, NULL, NULL, NULL, NULL, 'Top customer - special treatment', NULL, NULL, '2025-12-15 13:53:54'),
(64, 'patel@email.com', '2025-12-15', '0.0200', 'Low', NULL, NULL, NULL, NULL, NULL, 'Best customer - VIP status', NULL, NULL, '2025-12-15 13:53:54'),
(65, 's@email.com', '2025-12-15', '0.1500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Regular customer - stable', NULL, NULL, '2025-12-15 13:53:54'),
(66, 'mia.w@email.com', '2025-12-15', '0.1000', 'Low', NULL, NULL, NULL, NULL, NULL, 'High-value - frequent purchases', NULL, NULL, '2025-12-15 13:53:54'),
(67, 'charlotte.d@email.com', '2025-12-15', '0.0100', 'Low', NULL, NULL, NULL, NULL, NULL, 'Ultimate customer - lifetime value', NULL, NULL, '2025-12-15 13:53:54'),
(68, 'lisa.t@email.com', '2025-12-15', '0.2200', 'Low', NULL, NULL, NULL, NULL, NULL, 'Regular buyer - low risk', NULL, NULL, '2025-12-15 13:53:54'),
(69, 'daniel.a@email.com', '2025-12-15', '0.1900', 'Low', NULL, NULL, NULL, NULL, NULL, 'Active member - good engagement', NULL, NULL, '2025-12-15 13:53:54'),
(70, 'karen.t@email.com', '2025-12-15', '0.2800', 'Low', NULL, NULL, NULL, NULL, NULL, 'Stable customer - normal activity', NULL, NULL, '2025-12-15 13:53:54'),
(71, 'chris.j@email.com', '2025-12-15', '0.3200', 'Low', NULL, NULL, NULL, NULL, NULL, 'Regular purchaser - healthy', NULL, NULL, '2025-12-15 13:53:54'),
(72, 'emily.davis2@email.com', '2025-12-15', '0.7900', 'High', NULL, NULL, NULL, NULL, NULL, 'High risk - low engagement', NULL, NULL, '2025-12-15 13:57:30'),
(73, 'karen.thomas2@email.com', '2025-12-15', '0.8800', 'High', NULL, NULL, NULL, NULL, NULL, 'Very high risk - new customer with low activity', NULL, NULL, '2025-12-15 13:57:30'),
(74, 'david.brown2@email.com', '2025-12-15', '0.6200', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Medium risk - check in regularly', NULL, NULL, '2025-12-15 13:57:30'),
(75, 'jennifer.wilson2@email.com', '2025-12-15', '0.5500', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Medium risk - moderate activity', NULL, NULL, '2025-12-15 13:57:30'),
(76, 'thomas.moore2@email.com', '2025-12-15', '0.4900', 'Medium', NULL, NULL, NULL, NULL, NULL, 'Borderline risk - monitor closely', NULL, NULL, '2025-12-15 13:57:30'),
(77, 'michael.johnson2@email.com', '2025-12-15', '0.2500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Active customer - continue engagement', NULL, NULL, '2025-12-15 13:57:30'),
(78, 'sarah.williams2@email.com', '2025-12-15', '0.1800', 'Low', NULL, NULL, NULL, NULL, NULL, 'Loyal customer - consider upselling', NULL, NULL, '2025-12-15 13:57:30'),
(79, 'robert.miller2@email.com', '2025-12-15', '0.1500', 'Low', NULL, NULL, NULL, NULL, NULL, 'Very active - premium customer', NULL, NULL, '2025-12-15 13:57:30'),
(80, 'lisa.taylor2@email.com', '2025-12-15', '0.2200', 'Low', NULL, NULL, NULL, NULL, NULL, 'Regular buyer - low risk', NULL, NULL, '2025-12-15 13:57:30'),
(81, 'daniel.anderson2@email.com', '2025-12-15', '0.1000', 'Low', NULL, NULL, NULL, NULL, NULL, 'High-value - frequent purchases', NULL, NULL, '2025-12-15 13:57:30');

-- --------------------------------------------------------

--
-- 表的结构 `customer_risk_segments`
--

CREATE TABLE `customer_risk_segments` (
  `id` int(11) NOT NULL,
  `segment_name` varchar(100) NOT NULL,
  `segment_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- 转存表中的数据 `customer_risk_segments`
--

INSERT INTO `customer_risk_segments` (`id`, `segment_name`, `segment_criteria`, `customer_count`, `avg_churn_probability`, `avg_months_as_customer`, `avg_order_count`, `avg_days_since_last_order`, `total_value`, `retention_strategy`, `created_at`, `updated_at`) VALUES
(1, 'Low Risk', NULL, 5, '0.1260', '30.00', '21.00', '3.00', '5399.95', NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(2, 'Medium Risk', NULL, 5, '0.5340', '14.40', '9.20', '23.20', '2154.36', NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(3, 'High Risk', NULL, 5, '0.8040', '6.20', '3.80', '53.40', '819.71', NULL, '2025-12-12 10:06:47', '2025-12-12 10:06:47'),
(4, 'Low Risk', NULL, 12, '0.1500', '35.20', '28.50', '8.30', '48500.75', NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(5, 'Medium Risk', NULL, 5, '0.5800', '16.80', '11.40', '52.60', '7500.25', NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(6, 'High Risk', NULL, 8, '0.8200', '6.50', '2.80', '145.20', '850.99', NULL, '2025-12-15 13:53:54', '2025-12-15 13:53:54'),
(7, 'Low Risk', NULL, 6, '0.1500', '35.20', '28.50', '8.30', '48500.75', NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(8, 'Medium Risk', NULL, 3, '0.5800', '16.80', '11.40', '52.60', '7500.25', NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(9, 'High Risk', NULL, 2, '0.8200', '6.50', '2.80', '145.20', '850.99', NULL, '2025-12-15 13:57:30', '2025-12-15 13:57:30');

-- --------------------------------------------------------

--
-- 表的结构 `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `feedback`
--

INSERT INTO `feedback` (`id`, `name`, `email`, `subject`, `message`, `created_at`, `is_read`) VALUES
(1, 'Jerry', '123@163.com', 'Chinese', 'hello world', '2025-12-02 12:34:35', 0),
(2, 'Jerry', 'brown@email.com', 'game language', 'hello world', '2025-12-21 07:18:45', 0);

-- --------------------------------------------------------

--
-- 表的结构 `forum_posts`
--

CREATE TABLE `forum_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `forum_posts`
--

INSERT INTO `forum_posts` (`id`, `user_id`, `content`, `created_at`) VALUES
(1, 4, 'hello word', '2025-12-02 05:54:52'),
(2, 4, 'hello word', '2025-12-02 05:54:56'),
(3, 4, 'Just finished Cyberpunk Odyssey! The open world is absolutely stunning. Anyone else playing it?', '2025-12-02 12:14:01'),
(4, 4, 'Looking for teammates to play Shadow Strike: Tactical Ops with. I prefer stealth approach. Add me if interested!', '2025-12-02 11:14:01'),
(5, 4, 'What\'s your favorite game from the collection? Mine has to be Kingdom of Eldoria - the fantasy world is so immersive!', '2025-12-02 10:14:01'),
(6, 4, 'Just got 100% completion on Racing Revolution. The car customization options are incredible!', '2025-12-02 09:14:01'),
(7, 4, 'Is anyone else having issues with Space Explorers multiplayer? Can\'t seem to join my friend\'s game.', '2025-12-02 08:14:01'),
(8, 4, 'I just built the most amazing dragon habitat in Mythical Creatures Zoo! What\'s your favorite creature to care for?', '2025-12-02 07:14:01'),
(9, 4, 'Sports Championship 2024 tournament this weekend! Who\'s joining? Need 4 more players for our team.', '2025-12-02 06:14:01'),
(10, 4, 'The parkour mechanics in Neon Streets: Cyber Runner are so smooth! Any tips for advanced movement?', '2025-12-02 05:14:01'),
(11, 4, 'Dragon\'s Legacy MMORPG - which class are you playing? I\'m loving the Mage class so far.', '2025-12-02 04:14:01'),
(12, 4, 'Puzzle Dimensions is melting my brain in the best way possible. Just finished level 45!', '2025-12-02 03:14:01'),
(13, 4, 'Looking for guild members in Dragon\'s Legacy MMORPG. We\'re currently 15 members strong!', '2025-12-02 02:14:01'),
(14, 4, 'Virtual Life Simulator question: What\'s the most profitable career path you\'ve found?', '2025-12-02 01:14:01'),
(15, 4, 'Echoes of Fear - I can\'t get past the third floor of the asylum. Any tips? Too scared to continue!', '2025-12-02 00:14:01'),
(16, 4, 'Galactic Conquest players: What\'s your favorite alien race to play as? I\'m partial to the Zorblaxians.', '2025-12-01 23:14:01'),
(17, 4, 'Pixel Quest Retro Adventure is such a charming game! The soundtrack is amazing.', '2025-12-01 22:14:01'),
(18, 4, 'Empire Builders: Medieval - What\'s the best strategy for early game expansion?', '2025-12-01 21:14:01'),
(19, 4, 'Just organized a 24-hour gaming marathon with friends. We played 8 different games from the collection!', '2025-12-01 20:14:01'),
(20, 4, 'Looking for mod recommendations for Empire Builders: Medieval. Any must-have mods?', '2025-12-01 19:14:01'),
(21, 4, 'Sports Championship 2024: Created a custom team with all my favorite players. What\'s your dream team?', '2025-12-01 18:14:01'),
(22, 4, 'Community challenge: Let\'s see who can get the highest score in Puzzle Dimensions level 50 this week!', '2025-12-01 17:14:01'),
(23, 58, '111111', '2026-01-18 14:21:05'),
(24, 4, 'test 99', '2026-01-27 08:01:43');

-- --------------------------------------------------------

--
-- 替换视图以便查看 `forum_posts_view`
-- （参见下面的实际视图）
--
CREATE TABLE `forum_posts_view` (
`id` int(11)
,`content` text
,`created_at` timestamp
,`username` varchar(50)
,`email` varchar(100)
);

-- --------------------------------------------------------

--
-- 表的结构 `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `experience` int(11) DEFAULT NULL,
  `cover_letter` text NOT NULL,
  `applied_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','reviewed','accepted','rejected') DEFAULT 'pending'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `job_applications`
--

INSERT INTO `job_applications` (`id`, `full_name`, `email`, `phone`, `position`, `experience`, `cover_letter`, `applied_at`, `status`) VALUES
(1, 'Charlotte Davis', 'charlotte.d@email.com', '73124', 'Web Developer', 5, 'world', '2025-12-21 07:17:44', 'pending');

-- --------------------------------------------------------

--
-- 表的结构 `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_address` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `customer_name`, `customer_email`, `customer_address`, `created_at`) VALUES
(1, NULL, '89.98', 'completed', 'LiamChen', 'chen@email.com', '', '2025-10-28 07:00:00'),
(2, NULL, '224.95', 'completed', 'OliviaThompson', 'olivia.thompson@email.com', '', '2025-10-13 07:00:00'),
(3, NULL, '1249.75', 'completed', 'lusimple', '123@163.com', '', '2025-12-09 08:00:00'),
(4, NULL, '1599.68', 'completed', '	\r\nSophiaRodriguez', 'sophia.rdz@email.com', '', '2025-12-11 08:00:00'),
(5, NULL, '599.88', 'completed', 'NoahKim', 'kim@email.com', '', '2025-11-17 08:00:00'),
(6, NULL, '239.94', 'completed', 'EmmaFischer', 'f@email.com', '', '2025-11-20 08:00:00'),
(7, NULL, '749.85', '', 'JamesWilson', 'wilson@email.com', '', '2025-11-24 08:00:00'),
(8, NULL, '404.91', 'completed', 'AvaPatel', 'patel@email.com', '', '2025-11-14 08:00:00'),
(9, NULL, '299.95', 'completed', 'LucasMartin', 'm@email.com', '', '2024-11-20 22:30:00'),
(10, NULL, '139.98', '', 'IsabellaSilva', 's@email.com', '', '2024-12-10 17:15:00'),
(11, NULL, '59.99', 'completed', 'EthanJohnson\r\n', 'ethan.j@email.com', '', '2024-08-15 23:45:00'),
(12, NULL, '209.97', 'completed', 'lusimple', '123@163.com', '', '2024-12-14 19:20:00'),
(13, NULL, '69.99', 'completed', 'MiaWilliams', 'mia.w@email.com', '', '2024-07-22 20:10:00'),
(14, NULL, '119.98', '', 'BenjaminBrown', 'brown@email.com', '', '2024-11-05 18:30:00'),
(15, NULL, '459.95', 'completed', 'CharlotteDavis', 'charlotte.d@email.com', '', '2024-12-12 23:40:00'),
(16, NULL, '179.97', 'completed', 'MichaelJohnson', 'michael.j@email.com', '', '2024-12-13 17:25:00'),
(17, NULL, '89.99', '', '	\r\nSarahWilliams', 'sarah.w@email.com', '', '2024-11-25 22:15:00'),
(18, NULL, '239.96', 'completed', 'David	\r\nBrown', 'david.b@email.com', '', '2024-12-06 00:30:00'),
(19, NULL, '299.95', 'completed', 'EmilyDavis', 'emily.d@email.com', '', '2024-11-20 22:30:00'),
(20, NULL, '139.98', '', 'RobertMiller', 'robert.m@email.com', '', '2024-12-10 17:15:00'),
(21, NULL, '59.99', 'completed', 'JenniferWilson', 'jennifer.w@email.com', '', '2024-08-15 23:45:00'),
(22, NULL, '209.97', 'completed', 'ThomasMoore', 'thomas.m@email.com', '', '2024-12-14 19:20:00'),
(23, NULL, '69.99', 'completed', 'LisaTaylor', 'lisa.t@email.com', '', '2024-07-22 20:10:00'),
(24, NULL, '139.98', '', 'RobertMiller', 'robert.m@email.com', '', '2024-12-10 17:15:00'),
(25, 4, '197.97', 'processing', 'lu simple', '123@163.com', 'no, no, no, US', '2026-01-28 00:49:17'),
(26, 4, '263.96', 'processing', 'lu simple', '123@163.com', 'no, no, no, US', '2026-01-28 01:42:39'),
(27, 4, '138.58', 'pending', 'lu simple', '123@163.com', 'test, no, no, CN', '2026-01-28 01:49:50');

-- --------------------------------------------------------

--
-- 表的结构 `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `game_name` varchar(200) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `game_id`, `game_name`, `quantity`, `price`) VALUES
(1, 1, 1, '', 1, '59.99'),
(2, 1, 3, '', 1, '39.99'),
(3, 2, 1, '', 2, '59.99'),
(4, 2, 4, '', 1, '34.99'),
(5, 2, 6, '', 1, '44.99'),
(6, 3, 2, '', 3, '49.99'),
(7, 3, 7, '', 2, '54.99'),
(8, 3, 11, '', 1, '64.99'),
(9, 4, 1, '', 5, '59.99'),
(10, 4, 2, '', 4, '49.99'),
(11, 4, 3, '', 3, '39.99'),
(12, 5, 10, '', 10, '19.99'),
(13, 5, 15, '', 5, '14.99'),
(14, 6, 8, '', 4, '29.99'),
(15, 6, 12, '', 2, '37.99'),
(16, 7, 5, '', 3, '69.99'),
(17, 7, 9, '', 2, '39.99'),
(18, 8, 1, '', 2, '59.99'),
(19, 8, 13, '', 3, '24.99'),
(20, 8, 14, '', 1, '0.00'),
(21, 1, 1, '', 1, '59.99'),
(22, 1, 2, '', 1, '69.99'),
(23, 1, 3, '', 1, '59.99'),
(24, 1, 4, '', 1, '59.99'),
(25, 1, 5, '', 1, '49.99'),
(26, 2, 6, '', 1, '69.99'),
(27, 2, 7, '', 1, '69.99'),
(28, 3, 8, '', 1, '59.99'),
(29, 4, 9, '', 1, '69.99'),
(30, 4, 10, '', 1, '69.99'),
(31, 4, 11, '', 1, '69.99'),
(32, 5, 12, '', 1, '69.99'),
(33, 6, 13, '', 1, '59.99'),
(34, 6, 14, '', 1, '59.99'),
(35, 7, 1, '', 2, '119.98'),
(36, 7, 2, '', 1, '69.99'),
(37, 7, 3, '', 1, '59.99'),
(38, 7, 4, '', 2, '119.98'),
(39, 7, 5, '', 1, '49.99'),
(40, 8, 6, '', 1, '69.99'),
(41, 8, 7, '', 1, '69.99'),
(42, 8, 8, '', 1, '59.99'),
(43, 9, 9, '', 1, '69.99'),
(44, 10, 10, '', 1, '69.99'),
(45, 10, 11, '', 1, '69.99'),
(46, 10, 12, '', 1, '69.99'),
(47, 10, 13, '', 1, '59.99'),
(48, 19, 1, '', 1, '59.99'),
(49, 19, 2, '', 1, '69.99'),
(50, 18, 3, '', 1, '59.99'),
(51, 17, 4, '', 1, '59.99'),
(52, 16, 5, '', 1, '49.99'),
(53, 25, 25, 'Super Mario Bros. Wonder', 3, '59.99'),
(54, 26, 25, 'Super Mario Bros. Wonder', 4, '59.99'),
(55, 27, 24, 'Starfield', 2, '62.99');

-- --------------------------------------------------------

--
-- 表的结构 `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `old_status`, `new_status`, `changed_by`, `change_reason`, `created_at`) VALUES
(1, 26, 'pending', 'processing', 'lu simple', '', '2026-01-28 01:43:29'),
(2, 25, 'pending', 'processing', 'lu simple', '', '2026-01-28 01:46:01');

-- --------------------------------------------------------

--
-- 表的结构 `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(5,2) DEFAULT 0.00,
  `category` varchar(100) DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `long_description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT 'assets/images/game_default.jpg',
  `developer` varchar(100) DEFAULT 'Unknown',
  `publisher` varchar(100) DEFAULT 'Unknown',
  `release_date` date DEFAULT NULL,
  `platforms` text DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `features` text DEFAULT NULL,
  `stock` int(11) DEFAULT 100,
  `sales` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `discount`, `category`, `short_description`, `long_description`, `image`, `developer`, `publisher`, `release_date`, `platforms`, `rating`, `features`, `stock`, `sales`, `created_at`) VALUES
(1, 'Cyberpunk Odyssey', '59.99', '20.00', 'Action', 'Futuristic open-world RPG', NULL, 'assets/images/game1.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 150, 234, '2025-12-12 10:06:47'),
(2, 'Kingdom of Eldoria', '49.99', '0.00', 'RPG', 'Epic fantasy adventure', NULL, 'assets/images/game2.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 200, 189, '2025-12-12 10:06:47'),
(3, 'Racing Revolution', '39.99', '15.00', 'Racing', 'Ultimate racing simulation', NULL, 'assets/images/game3.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 120, 156, '2025-12-12 10:06:47'),
(4, 'Space Explorers', '34.99', '30.00', 'Adventure', 'Interstellar exploration game', NULL, 'assets/images/game4.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 180, 210, '2025-12-12 10:06:47'),
(5, 'Sports Championship 2024', '69.99', '10.00', 'Sports', 'Complete sports collection', NULL, 'assets/images/game5.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 220, 178, '2025-12-12 10:06:47'),
(6, 'Shadow Strike: Tactical Ops', '44.99', '25.00', 'Shooter', 'Intense tactical first-person shooter', NULL, 'assets/images/game6.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 90, 145, '2025-12-12 10:06:47'),
(7, 'Empire Builders: Medieval', '54.99', '0.00', 'Strategy', 'Historical empire-building strategy', NULL, 'assets/images/game7.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 110, 167, '2025-12-12 10:06:47'),
(8, 'Virtual Life Simulator', '29.99', '40.00', 'Simulation', 'Ultimate life simulation experience', NULL, 'assets/images/game8.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 250, 198, '2025-12-12 10:06:47'),
(9, 'Echoes of Fear', '39.99', '20.00', 'Horror', 'Psychological horror survival', NULL, 'assets/images/game9.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 80, 123, '2025-12-12 10:06:47'),
(10, 'Pixel Quest Retro Adventure', '19.99', '50.00', 'Indie', 'Charming pixel-art adventure', NULL, 'assets/images/game10.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 300, 267, '2025-12-12 10:06:47'),
(11, 'Galactic Conquest', '64.99', '15.00', 'Strategy', '4X space strategy epic', NULL, 'assets/images/game11.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 130, 156, '2025-12-12 10:06:47'),
(12, 'Mythical Creatures Zoo', '37.99', '10.00', 'Simulation', 'Build and manage a zoo for magical creatures', NULL, 'assets/images/game12.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 170, 189, '2025-12-12 10:06:47'),
(13, 'Neon Streets: Cyber Runner', '24.99', '60.00', 'Action', 'Fast-paced cyberpunk parkour', NULL, 'assets/images/game13.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 140, 134, '2025-12-12 10:06:47'),
(14, 'Dragon\'s Legacy MMORPG', '0.00', '0.00', 'RPG', 'Free-to-play fantasy MMORPG', NULL, 'assets/images/game14.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 1000, 890, '2025-12-12 10:06:47'),
(15, 'Puzzle Dimensions', '14.99', '70.00', 'Puzzle', 'Mind-bending dimensional puzzles', NULL, 'assets/images/game15.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 200, 145, '2025-12-12 10:06:47'),
(16, 'Cyberpunk 2077', '59.99', '20.00', 'Action', 'Open-world RPG set in Night City', NULL, 'assets/images/game16.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 150, 1200, '2025-12-15 13:45:31'),
(17, 'FIFA 24', '69.99', '10.00', 'Sports', 'Latest football simulation game', NULL, 'assets/images/game17.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 200, 1800, '2025-12-15 13:45:31'),
(18, 'The Legend of Zelda: Tears of the Kingdom', '59.99', '0.00', 'Adventure', 'Epic adventure in Hyrule', NULL, 'assets/images/game18.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 100, 950, '2025-12-15 13:45:31'),
(19, 'Call of Duty: Modern Warfare III', '69.99', '15.00', 'Shooter', 'First-person military shooter', NULL, 'assets/images/game19.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 180, 1400, '2025-12-15 13:45:31'),
(20, 'Elden Ring', '59.99', '0.00', 'RPG', 'Action RPG from FromSoftware', NULL, 'assets/images/game20.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 120, 1100, '2025-12-15 13:45:31'),
(21, 'Forza Horizon 5', '59.99', '25.00', 'Racing', 'Open-world racing game', NULL, 'assets/images/game21.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 160, 1300, '2025-12-15 13:45:31'),
(22, 'Resident Evil 4 Remake', '59.99', '10.00', 'Horror', 'Survival horror remake', NULL, 'assets/images/game22.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 90, 750, '2025-12-15 13:45:31'),
(23, 'Cities: Skylines II', '49.99', '0.00', 'Simulation', 'City-building simulation', NULL, 'assets/images/game23.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 140, 800, '2025-12-15 13:45:31'),
(24, 'Starfield', '69.99', '10.00', 'RPG', 'Space exploration RPG', NULL, 'assets/images/game24.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 110, 900, '2025-12-15 13:45:31'),
(25, 'Super Mario Bros. Wonder', '59.99', '0.00', 'Adventure', 'Side-scrolling platformer', NULL, 'assets/images/game25.jpg', 'Unknown', 'Unknown', NULL, NULL, '0.00', NULL, 170, 1200, '2025-12-15 13:45:31'),
(96, 'test99', '23.00', '1.00', 'Sports', 'tess', 'ddd', 'assets/images/fdsa', 'dd', '', '2026-01-26', '[\"PC\"]', '0.00', '[]', 100, 0, '2026-01-27 04:14:41');

-- --------------------------------------------------------

--
-- 表的结构 `retention_activities`
--

CREATE TABLE `retention_activities` (
  `id` int(11) NOT NULL,
  `activity_name` varchar(255) NOT NULL,
  `target_segment` varchar(50) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `target_customers` int(11) DEFAULT 0,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `retention_activities`
--

INSERT INTO `retention_activities` (`id`, `activity_name`, `target_segment`, `activity_type`, `start_date`, `target_customers`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Welcome Email Campaign', 'New Customers', 'Email', '2024-12-01', 25, 'Completed', '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(2, 'Loyalty Reward Program', 'Low Risk', 'Loyalty', '2024-12-05', 12, 'Active', '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(3, 'High Risk Retention Campaign', 'High Risk', 'Email', '2024-12-10', 8, 'Active', '2025-12-15 13:57:30', '2025-12-15 13:57:30'),
(4, 'Discount Offer Campaign', 'Medium Risk', 'Promotion', '2024-12-08', 5, 'Completed', '2025-12-15 13:57:30', '2025-12-15 13:57:30');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@gamehub.com', '$2y$10$YourHashedPasswordHere', '2025-12-01 11:42:17', '2025-12-01 11:42:17');

-- --------------------------------------------------------

--
-- 表的结构 `wp9k_fc_subscribers`
--

CREATE TABLE `wp9k_fc_subscribers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hash` varchar(90) DEFAULT NULL,
  `contact_owner` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `prefix` varchar(192) DEFAULT NULL,
  `first_name` varchar(192) DEFAULT NULL,
  `last_name` varchar(192) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `timezone` varchar(192) DEFAULT NULL,
  `address_line_1` varchar(192) DEFAULT NULL,
  `address_line_2` varchar(192) DEFAULT NULL,
  `postal_code` varchar(192) DEFAULT NULL,
  `city` varchar(192) DEFAULT NULL,
  `state` varchar(192) DEFAULT NULL,
  `country` varchar(192) DEFAULT NULL,
  `ip` varchar(40) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  `total_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `life_time_value` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `phone` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'subscribed',
  `contact_type` varchar(50) DEFAULT 'lead',
  `source` varchar(50) DEFAULT NULL,
  `avatar` varchar(192) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `wp9k_fc_subscribers`
--

INSERT INTO `wp9k_fc_subscribers` (`id`, `user_id`, `hash`, `contact_owner`, `company_id`, `prefix`, `first_name`, `last_name`, `email`, `timezone`, `address_line_1`, `address_line_2`, `postal_code`, `city`, `state`, `country`, `ip`, `latitude`, `longitude`, `total_points`, `life_time_value`, `phone`, `status`, `contact_type`, `source`, `avatar`, `date_of_birth`, `created_at`, `last_activity`, `updated_at`) VALUES
(1, NULL, '122001993d562b35289d0c5e2d5afe5c', NULL, NULL, NULL, 'Jay', NULL, '3206246004@qq.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.144.123.224', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-11-25 12:15:26', NULL, '2025-11-25 12:15:26'),
(6, NULL, '1389ab22649d14b49b07152db2da74fe', NULL, NULL, NULL, 'Liam', 'Chen', 'chen@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:43:01', NULL, '2025-12-01 18:43:01'),
(5, NULL, '8262f26a605f8020238e117b7be776ef', NULL, NULL, NULL, 'Olivia', 'Thompson', 'olivia.thompson@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:42:01', NULL, '2025-12-01 18:42:01'),
(4, NULL, '9e2e1fac1f7ca52ec99ef74b12917ffd', NULL, NULL, NULL, 'lu', 'simple', '123@163.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 17:30:41', NULL, '2025-12-01 17:30:41'),
(7, NULL, '6c597fad887b9e74ef27213df55d95cd', NULL, NULL, NULL, 'Sophia', 'Rodriguez', 'sophia.rdz@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:44:04', NULL, '2025-12-01 18:44:04'),
(8, NULL, 'f81f0c3bc9ec494ea4a8c505c51b866b', NULL, NULL, NULL, 'Noah', 'Kim', 'kim@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:45:19', NULL, '2025-12-01 18:45:19'),
(9, NULL, '20f28820e07b2298f7b46d03f4e3ca81', NULL, NULL, NULL, 'Emma', 'Fischer', 'f@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:46:18', NULL, '2025-12-01 18:46:18'),
(10, NULL, 'b65ec5637331bf31f72daddc2d3995db', NULL, NULL, NULL, 'James', 'Wilson', 'wilson@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:47:07', NULL, '2025-12-01 18:47:07'),
(11, NULL, 'a17bcf4b9e2220055d19e186c6f53f64', NULL, NULL, NULL, 'Ava', 'Patel', 'patel@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:48:16', NULL, '2025-12-01 18:48:16'),
(12, NULL, 'cfc98892bc2cbb9d5fa6ff276f014f79', NULL, NULL, NULL, 'Lucas', 'Martin', 'm@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:50:05', NULL, '2025-12-01 18:50:05'),
(13, NULL, 'b4a9b7b2176d16eb268611c8f3585553', NULL, NULL, NULL, 'Isabella', 'Silva', 's@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:51:21', NULL, '2025-12-01 18:51:21'),
(14, NULL, 'e1f54eb477e144483bbb7f59cd08a433', NULL, NULL, NULL, 'Ethan', 'Johnson', 'ethan.j@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:52:09', NULL, '2025-12-01 18:52:09'),
(15, NULL, '2747ffed6a7bff4845f6e563d0713a1a', NULL, NULL, NULL, 'Mia', 'Williams', 'mia.w@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:52:50', NULL, '2025-12-01 18:52:50'),
(16, NULL, '5a68d498e2945e3783b9781d6ef05531', NULL, NULL, NULL, 'Benjamin', 'Brown', 'brown@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:53:49', NULL, '2025-12-01 18:53:49'),
(17, NULL, 'df80d5e2e35a622c28f002413945b007', NULL, NULL, NULL, 'Charlotte', 'Davis', 'charlotte.d@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.82', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2025-12-01 18:54:39', NULL, '2025-12-01 18:54:39'),
(18, NULL, NULL, NULL, NULL, NULL, 'Michael', 'Johnson', 'michael.j@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-01-15 08:00:00', NULL, NULL),
(19, NULL, NULL, NULL, NULL, NULL, 'Sarah', 'Williams', 'sarah.w@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-02-20 08:00:00', NULL, NULL),
(20, NULL, NULL, NULL, NULL, NULL, 'David', 'Brown', 'david.b@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-03-10 08:00:00', NULL, NULL),
(21, NULL, NULL, NULL, NULL, NULL, 'Emily', 'Davis', 'emily.d@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-04-05 07:00:00', NULL, NULL),
(22, NULL, NULL, NULL, NULL, NULL, 'Robert', 'Miller', 'robert.m@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-05-12 07:00:00', NULL, NULL),
(23, NULL, NULL, NULL, NULL, NULL, 'Jennifer', 'Wilson', 'jennifer.w@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-06-18 07:00:00', NULL, NULL),
(24, NULL, NULL, NULL, NULL, NULL, 'Thomas', 'Moore', 'thomas.m@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-07-22 07:00:00', NULL, NULL),
(25, NULL, NULL, NULL, NULL, NULL, 'Lisa', 'Taylor', 'lisa.t@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-08-30 07:00:00', NULL, NULL),
(26, NULL, NULL, NULL, NULL, NULL, 'Daniel', 'Anderson', 'daniel.a@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-09-14 07:00:00', NULL, NULL),
(27, NULL, NULL, NULL, NULL, NULL, 'Karen', 'Thomas', 'karen.t@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-10-25 07:00:00', NULL, NULL),
(28, NULL, NULL, NULL, NULL, NULL, 'Christopher', 'Jackson', 'chris.j@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-11-08 08:00:00', NULL, NULL),
(29, NULL, NULL, NULL, NULL, NULL, 'Amanda', 'White', 'amanda.w@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-12-03 08:00:00', NULL, NULL),
(30, NULL, NULL, NULL, NULL, NULL, 'Matthew', 'Harris', 'matt.h@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-01-17 08:00:00', NULL, NULL),
(31, NULL, NULL, NULL, NULL, NULL, 'Jessica', 'Martin', 'jessica.m@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-02-09 08:00:00', NULL, NULL),
(32, NULL, NULL, NULL, NULL, NULL, 'Kevin', 'Thompson', 'kevin.t@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-03-22 07:00:00', NULL, NULL),
(33, NULL, NULL, NULL, NULL, NULL, 'Ashley', 'Garcia', 'ashley.g@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-04-11 07:00:00', NULL, NULL),
(34, NULL, NULL, NULL, NULL, NULL, 'Richard', 'Martinez', 'richard.m@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-05-19 07:00:00', NULL, NULL),
(35, NULL, NULL, NULL, NULL, NULL, 'Michelle', 'Robinson', 'michelle.r@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-06-28 07:00:00', NULL, NULL),
(36, NULL, NULL, NULL, NULL, NULL, 'Charles', 'Clark', 'charles.c@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-07-15 07:00:00', NULL, NULL),
(37, NULL, NULL, NULL, NULL, NULL, 'Stephanie', 'Rodriguez', 'stephanie.r@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-08-23 07:00:00', NULL, NULL),
(38, NULL, NULL, NULL, NULL, NULL, 'Michael', 'Johnson', 'michael.johnson2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-01-15 08:00:00', NULL, NULL),
(39, NULL, NULL, NULL, NULL, NULL, 'Sarah', 'Williams', 'sarah.williams2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-02-20 08:00:00', NULL, NULL),
(40, NULL, NULL, NULL, NULL, NULL, 'David', 'Brown', 'david.brown2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-03-10 08:00:00', NULL, NULL),
(41, NULL, NULL, NULL, NULL, NULL, 'Emily', 'Davis', 'emily.davis2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-04-05 07:00:00', NULL, NULL),
(42, NULL, NULL, NULL, NULL, NULL, 'Robert', 'Miller', 'robert.miller2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-05-12 07:00:00', NULL, NULL),
(43, NULL, NULL, NULL, NULL, NULL, 'Jennifer', 'Wilson', 'jennifer.wilson2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-06-18 07:00:00', NULL, NULL),
(44, NULL, NULL, NULL, NULL, NULL, 'Thomas', 'Moore', 'thomas.moore2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-07-22 07:00:00', NULL, NULL),
(45, NULL, NULL, NULL, NULL, NULL, 'Lisa', 'Taylor', 'lisa.taylor2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-08-30 07:00:00', NULL, NULL),
(46, NULL, NULL, NULL, NULL, NULL, 'Daniel', 'Anderson', 'daniel.anderson2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-09-14 07:00:00', NULL, NULL),
(47, NULL, NULL, NULL, NULL, NULL, 'Karen', 'Thomas', 'karen.thomas2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-10-25 07:00:00', NULL, NULL),
(48, NULL, NULL, NULL, NULL, NULL, 'Christopher', 'Jackson', 'chris.jackson2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-11-08 08:00:00', NULL, NULL),
(49, NULL, NULL, NULL, NULL, NULL, 'Amanda', 'White', 'amanda.white2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2023-12-03 08:00:00', NULL, NULL),
(50, NULL, NULL, NULL, NULL, NULL, 'Matthew', 'Harris', 'matthew.harris2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-01-17 08:00:00', NULL, NULL),
(51, NULL, NULL, NULL, NULL, NULL, 'Jessica', 'Martin', 'jessica.martin2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-02-09 08:00:00', NULL, NULL),
(52, NULL, NULL, NULL, NULL, NULL, 'Kevin', 'Thompson', 'kevin.thompson2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-03-22 07:00:00', NULL, NULL),
(53, NULL, NULL, NULL, NULL, NULL, 'Ashley', 'Garcia', 'ashley.garcia2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-04-11 07:00:00', NULL, NULL),
(54, NULL, NULL, NULL, NULL, NULL, 'Richard', 'Martinez', 'richard.martinez2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-05-19 07:00:00', NULL, NULL),
(55, NULL, NULL, NULL, NULL, NULL, 'Michelle', 'Robinson', 'michelle.robinson2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-06-28 07:00:00', NULL, NULL),
(56, NULL, NULL, NULL, NULL, NULL, 'Charles', 'Clark', 'charles.clark2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-07-15 07:00:00', NULL, NULL),
(57, NULL, NULL, NULL, NULL, NULL, 'Stephanie', 'Rodriguez', 'stephanie.rodriguez2@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'subscribed', 'lead', NULL, NULL, NULL, '2024-08-23 07:00:00', NULL, NULL),
(58, NULL, '4d9b2f508461e2cef25e9349f7a31756', NULL, NULL, NULL, '佳明', '刘1', '738743731@qq.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '122.247.74.93', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2026-01-14 18:14:53', NULL, '2026-01-16 12:39:32'),
(59, NULL, 'fe0e307a9272d5acd457f32a88823713', NULL, NULL, NULL, NULL, NULL, 'sdfa@asdf.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '49.245.96.35', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2026-01-26 22:01:11', NULL, '2026-01-26 22:01:11'),
(60, NULL, '87d37b5c2fe850cc1bff026f079de408', NULL, NULL, NULL, 'yyz', 'zyy', '222222222222222@qq.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '39.184.255.81', NULL, NULL, 0, 0, NULL, 'pending', 'lead', 'FluentForms', NULL, NULL, '2026-01-28 18:23:55', NULL, '2026-01-28 18:24:48');

-- --------------------------------------------------------

--
-- 视图结构 `forum_posts_view`
--
DROP TABLE IF EXISTS `forum_posts_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`if0_39945006`@`192.168.0.%` SQL SECURITY DEFINER VIEW `forum_posts_view`  AS  select `fp`.`id` AS `id`,`fp`.`content` AS `content`,`fp`.`created_at` AS `created_at`,`u`.`username` AS `username`,`u`.`email` AS `email` from (`forum_posts` `fp` join `users` `u` on(`fp`.`user_id` = `u`.`id`)) ;

--
-- 转储表的索引
--

--
-- 表的索引 `customer_behavior`
--
ALTER TABLE `customer_behavior`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_email` (`customer_email`(250)),
  ADD KEY `idx_churned` (`churned`),
  ADD KEY `idx_last_purchase` (`days_since_last_order`);

--
-- 表的索引 `customer_churn_predictions`
--
ALTER TABLE `customer_churn_predictions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_email` (`customer_email`(250)),
  ADD KEY `idx_prediction_date` (`prediction_date`),
  ADD KEY `idx_risk_level` (`risk_level`);

--
-- 表的索引 `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_feedback_email` (`email`);

--
-- 表的索引 `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forum_user` (`user_id`);

--
-- 表的索引 `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_job_applications_status` (`status`);

--
-- 表的索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- 表的索引 `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 表的索引 `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `retention_activities`
--
ALTER TABLE `retention_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_target_segment` (`target_segment`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_status` (`status`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- 表的索引 `wp9k_fc_subscribers`
--
ALTER TABLE `wp9k_fc_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `wp9k_fc_index__subscriber_user_id_idx` (`user_id`),
  ADD KEY `wp9k_fc_index__subscriber_status_idx` (`status`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `churn_models`
--
ALTER TABLE `churn_models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `customer_behavior`
--
ALTER TABLE `customer_behavior`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- 使用表AUTO_INCREMENT `customer_churn_predictions`
--
ALTER TABLE `customer_churn_predictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- 使用表AUTO_INCREMENT `customer_risk_segments`
--
ALTER TABLE `customer_risk_segments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- 使用表AUTO_INCREMENT `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- 使用表AUTO_INCREMENT `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- 使用表AUTO_INCREMENT `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- 使用表AUTO_INCREMENT `retention_activities`
--
ALTER TABLE `retention_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `wp9k_fc_subscribers`
--
ALTER TABLE `wp9k_fc_subscribers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
