-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 04, 2025 at 02:16 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u863526903_edemand_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(50) NOT NULL,
  `type` varchar(32) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `area` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `alternate_mobile` varchar(20) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `city_id` int(20) NOT NULL DEFAULT 0,
  `city` varchar(252) NOT NULL,
  `landmark` varchar(128) DEFAULT NULL,
  `state` varchar(200) DEFAULT NULL,
  `country` varchar(200) DEFAULT NULL,
  `lattitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_contact_query`
--

CREATE TABLE `admin_contact_query` (
  `id` int(11) NOT NULL,
  `email` text DEFAULT NULL,
  `name` longtext DEFAULT NULL,
  `message` longtext NOT NULL,
  `subject` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users`
--

CREATE TABLE `blocked_users` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `blocked_user_id` int(11) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL,
  `short_description` text DEFAULT NULL,
  `slug` text NOT NULL,
  `category_id` int(11) NOT NULL,
  `image` text NOT NULL,
  `description` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blogs_seo_settings`
--

CREATE TABLE `blogs_seo_settings` (
  `id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'SEO meta title',
  `description` text NOT NULL COMMENT 'SEO meta description',
  `keywords` text NOT NULL COMMENT 'SEO meta keywords',
  `schema_markup` longtext NOT NULL COMMENT 'Schema markup for SEO',
  `image` varchar(255) NOT NULL COMMENT 'SEO image for social media sharing',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores SEO settings for different blogs';

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `slug` text NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_tags`
--

CREATE TABLE `blog_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores unique tags and their slugs for blogs';

-- --------------------------------------------------------

--
-- Table structure for table `blog_tag_map`
--

CREATE TABLE `blog_tag_map` (
  `id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Maps blogs to tags (many-to-many)';

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `partner_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `is_saved_for_later` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_collection`
--

CREATE TABLE `cash_collection` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `commison` int(11) NOT NULL,
  `status` text NOT NULL,
  `partner_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `order_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(50) NOT NULL DEFAULT 0,
  `name` varchar(1024) NOT NULL,
  `image` text NOT NULL,
  `slug` varchar(1024) NOT NULL,
  `admin_commission` double NOT NULL COMMENT 'global admin commission for all partners',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - deactive | 1 - active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL,
  `dark_color` varchar(255) NOT NULL,
  `light_color` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories_seo_settings`
--

CREATE TABLE `categories_seo_settings` (
  `id` int(11) NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'SEO meta title',
  `description` text NOT NULL COMMENT 'SEO meta description',
  `keywords` text NOT NULL COMMENT 'SEO meta keywords',
  `schema_markup` longtext NOT NULL COMMENT 'Schema markup for SEO',
  `image` varchar(255) NOT NULL COMMENT 'SEO image for social media sharing',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores SEO settings for different categories';

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `booking_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `message` longtext NOT NULL,
  `file` longtext DEFAULT NULL,
  `file_type` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `e_id` int(11) NOT NULL,
  `sender_type` int(11) NOT NULL COMMENT '0 : Admin\r\n1: Provider\r\n2: customer',
  `receiver_type` int(11) NOT NULL COMMENT '0 : Admin\r\n1: Provider\r\n2: customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` mediumtext NOT NULL,
  `latitude` varchar(120) DEFAULT NULL,
  `longitude` varchar(120) DEFAULT NULL,
  `delivery_charge_method` varchar(30) DEFAULT NULL,
  `fixed_charge` int(11) NOT NULL DEFAULT 0,
  `per_km_charge` int(11) NOT NULL DEFAULT 0,
  `range_wise_charges` text DEFAULT NULL,
  `time_to_travel` int(11) NOT NULL DEFAULT 0,
  `geolocation_type` varchar(30) DEFAULT NULL COMMENT 'not used in current',
  `radius` varchar(512) DEFAULT '0' COMMENT 'not used in current',
  `boundary_points` text DEFAULT NULL COMMENT 'not used in current',
  `max_deliverable_distance` int(10) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `country_codes`
--

CREATE TABLE `country_codes` (
  `id` int(11) NOT NULL,
  `country_name` text NOT NULL,
  `country_code` text NOT NULL,
  `calling_code` text NOT NULL,
  `is_default` int(11) NOT NULL DEFAULT 0,
  `flag_image` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `country_codes`
--

INSERT INTO `country_codes` (`id`, `country_name`, `country_code`, `calling_code`, `is_default`, `flag_image`, `created_at`, `updated_at`) VALUES
(1, 'India', 'IN', '+91', 1, 'in.png', '2025-08-13 00:00:00', '2025-08-13 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `custom_job_provider`
--

CREATE TABLE `custom_job_provider` (
  `id` int(11) NOT NULL,
  `custom_job_request_id` text NOT NULL,
  `partner_id` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_job_requests`
--

CREATE TABLE `custom_job_requests` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `category_id` varchar(255) NOT NULL,
  `service_title` text NOT NULL,
  `service_short_description` text NOT NULL,
  `min_price` text NOT NULL,
  `max_price` text NOT NULL,
  `requested_start_date` date NOT NULL,
  `requested_start_time` time NOT NULL,
  `requested_end_date` date NOT NULL,
  `requested_end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delete_general_notification`
--

CREATE TABLE `delete_general_notification` (
  `id` int(50) NOT NULL,
  `user_id` int(50) NOT NULL,
  `notification_id` int(50) NOT NULL,
  `is_readed` tinyint(50) NOT NULL,
  `is_deleted` tinytext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emails`
--

CREATE TABLE `emails` (
  `id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `user_id` text DEFAULT NULL,
  `subject` text NOT NULL,
  `type` text NOT NULL,
  `parameters` text NOT NULL,
  `bcc` text DEFAULT NULL,
  `cc` text DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `type` text NOT NULL,
  `subject` text NOT NULL,
  `to` text NOT NULL,
  `template` longtext NOT NULL,
  `bcc` text DEFAULT NULL,
  `cc` text DEFAULT NULL,
  `parameters` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `type`, `subject`, `to`, `template`, `bcc`, `cc`, `parameters`) VALUES
(1, 'provider_approved', 'Approval of Registration Request', 'null', '<p>Dear [[provider_name]],&nbsp;</p>\n<p>We\'re thrilled to inform you that your request has been approved! This is a significant milestone, and we can\'t wait to witness the impact your solutions will have on our operations.</p>\n<p>&nbsp;</p>\n<p>Here are the details you need:<br /><br />Provider ID: [[provider_id]]<br />Company Name: [[provider_name]]</p>\n<p>As we move forward, please feel free to reach out with any questions or additional information you may require. We\'re here to ensure a smooth and successful collaboration.</p>\n<p>&nbsp;</p>\n<p>Thank you once again for your outstanding work and dedication. We\'re looking forward to a fruitful partnership!</p>\n<p>&nbsp;</p>\n<p>Warm regards,</p>\n<p>[[company_logo]]</p>\n<p>[[company_contact_info]]</p>', '', '', '[\"provider_name\",\"provider_id\",\"provider_name\",\"company_logo\",\"company_contact_info\"]'),
(2, 'provider_disapproved', 'Rejection of Registration Request', '', '&lt;p&gt;Dear [[provider_name]] ,&lt;/p&gt;\\r\\n&lt;p&gt;I regret to inform you that your registration request has been declined. After careful review and consideration, we have determined that your offerings do not align with our current needs or standards.&lt;/p&gt;\\r\\n&lt;p&gt;While we appreciate your interest in partnering with us, we believe it&#039;s in both of our best interests to explore other opportunities that better fit our requirements at this time.&lt;/p&gt;\\r\\n&lt;p&gt;Please know that this decision was not made lightly, and we genuinely value the effort you put into your application. We encourage you to continue pursuing opportunities that align more closely with your expertise and offerings.&lt;/p&gt;\\r\\n&lt;p&gt;Thank you for your understanding. Should you have any questions or require further clarification, please don&#039;t hesitate to reach out.&lt;/p&gt;\\r\\n&lt;p&gt;I wish you all the best in your future endeavors.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;Warm regards,&lt;/p&gt;\\r\\n&lt;p&gt;[[company_name]]&lt;/p&gt;\\r\\n&lt;p&gt;[[company_contact_info]]&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;', '', '', '[\\\"provider_name\\\",\\\"company_name\\\",\\\"company_contact_info\\\"]'),
(3, 'withdraw_request_approved', 'Withdrawal Request Approved', '', '&lt;p&gt;Dear [[Provider Name]],&lt;/p&gt;\\r\\n&lt;p&gt;We are pleased to inform you that your withdrawal request has been approved. If you have any questions or concerns regarding this transaction, please do not hesitate to contact us. Thank you for choosing our services. We look forward to providing you with excellent service in the future.&lt;/p&gt;\\r\\n&lt;p&gt;Your Request is for: [[Amount]] [[Currency]].&lt;/p&gt;\\r\\n&lt;p&gt;Best Regards, [[Company Name]]&lt;/p&gt;', '', '', '[\\\"Provider Name\\\",\\\"Amount\\\",\\\"Currency\\\",\\\"Company Name\\\"]'),
(4, 'withdraw_request_disapproved', 'Withdrawal Request Disapproved', '', '&lt;p&gt;Dear [[Provider Name]],&lt;/p&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\\\\\r\\\\\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;We regret to inform you that your withdrawal request has been disapproved. If you have any questions or concerns regarding this decision, please do not hesitate to contact us. Thank you for choosing our services. We look forward to providing you with excellent service in the future.&lt;/p&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\\\\\r\\\\\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;Your Request is for: [[Amount]] [[Currency]].&lt;/p&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\\\\\r\\\\\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;Best Regards, [[Company Name]]&lt;/p&gt;', '', '', '[\\\"Provider Name\\\",\\\"Amount\\\",\\\"Currency\\\",\\\"Company Name\\\"]'),
(5, 'payment_settlement', 'Payment Settlement', '', '&lt;p&gt;Dear [[provider_name]],&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;div&gt;I am writing to confirm that we have credited the agreed upon amount of [[currency]][[amount]] to your account, as per our agreement. This payment settles the outstanding balance for the services provided by your company.&amp;nbsp;&lt;/div&gt;\\r\\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\\r\\n&lt;div&gt;\\r\\n&lt;div&gt;We understand the importance of timely payments for maintaining a healthy business relationship, and we strive to meet our payment obligations promptly. Please check your account and confirm that the payment has been received. If you have any questions or concerns, please do not hesitate to contact us.&lt;/div&gt;\\r\\n&lt;/div&gt;\\r\\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\\r\\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\\r\\n&lt;div&gt;[[company_contact_info]]&lt;/div&gt;\\r\\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\\r\\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\\r\\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\\r\\n&lt;div&gt;\\r\\n&lt;div&gt;Thank you for your prompt attention to this matter. We look forward to continuing our mutually beneficial partnership.&lt;/div&gt;\\r\\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\\r\\n&lt;div&gt;Best Regards ,&amp;nbsp;&lt;/div&gt;\\r\\n&lt;div&gt;[[company_name]].&amp;nbsp;&lt;/div&gt;\\r\\n&lt;/div&gt;', '', '', '[\\\"provider_name\\\",\\\"currency\\\",\\\"amount\\\",\\\"company_contact_info\\\",\\\"company_name\\\"]'),
(6, 'service_disapproved', 'Rejection of Service Request', '', '&lt;p&gt;Dear [[Provider Name]],&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;We regret to inform you that your request for service approval has been disapproved. After thorough evaluation and consideration, our team has determined that your request does not meet the necessary criteria for approval.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;We understand that this decision may be disappointing for you, but please know that we carefully reviewed your request and made the best decision based on our policies and guidelines.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;If you have any questions or concerns regarding the decision, please do not hesitate to reach out to us. We would be happy to discuss any specific concerns that you may have.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;Contact us:&lt;/p&gt;\\r\\n&lt;p&gt;[[Company Contact Info]]&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;Best Regards,&lt;/p&gt;\\r\\n&lt;p&gt;[[Company Name]]&lt;/p&gt;', '', '', '[\\\"Provider Name\\\",\\\"Company Contact Info\\\",\\\"Company Name\\\"]'),
(7, 'service_approved', 'Approval of Service Request', '', '&lt;p&gt;Dear [[Provider Name]],&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;I am pleased to inform you that your request for service approval has been approved. After careful review and consideration, our team has determined that your request meets all the necessary criteria and is eligible for approval.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;Once again, congratulations on your approval status! We look forward to working with you and supporting your goals.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;If you have any questions or concerns, please do not hesitate to contact us.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;Contact Us:&lt;/p&gt;\\r\\n&lt;p&gt;[[Company Contact Info]]&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;Best Regards,&lt;/p&gt;\\r\\n&lt;p&gt;[[Company Name]]&lt;/p&gt;', '', '', '[\\\"Provider Name\\\",\\\"Company Contact Info\\\",\\\"Company Name\\\"]'),
(8, 'user_account_active', 'Account activation confirmation', '', '&lt;p&gt;Dear [[user_name]],&amp;nbsp;&lt;/p&gt;\r\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\r\n&lt;div&gt;We are pleased to inform you that your account has been successfully activated. You can now log in to your account and start using our services.&lt;/div&gt;\r\n&lt;div&gt;\r\n&lt;div&gt;If you have any questions or need any assistance feel free to contact us.&lt;/div&gt;\r\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\r\n&lt;div&gt;\r\n&lt;div&gt;Thank you again for choosing our services. We look forward to doing business with you again.&lt;/div&gt;\r\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\r\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\r\n&lt;div&gt;Best Regards ,&amp;nbsp;&lt;/div&gt;\r\n&lt;div&gt;[[company_name]].&lt;/div&gt;\r\n&lt;/div&gt;\r\n&lt;/div&gt;', '', '', '[\"user_name\",\"company_name\"]'),
(9, 'user_account_deactive', 'Account Deactivation Confirmation', '', '&lt;div&gt;Dear [[user_name]]&lt;/div&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\\\\\r\\\\\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\\\\\r\\\\\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;div&gt;We are sorry to inform you that your account has been deactivated.&lt;/div&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\\\\\r\\\\\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;div&gt;[[user_id]][[user_name]][[company_name]][[site_url]][[company_contact_info]][[company_logo]][[company_contact_info]][[company_logo]]&lt;/div&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\\\\\r\\\\\\\\n&lt;/p&gt;\\r\\n&lt;p&gt;\\\\r\\\\n&lt;/p&gt;\\r\\n&lt;div&gt;\\\\\\\\r\\\\\\\\n\\\\r\\\\n\\r\\n&lt;div&gt;If you have any questions or need any assistance feel free to contact us.&lt;/div&gt;\\r\\n\\\\r\\\\n\\\\\\\\r\\\\\\\\n\\\\r\\\\n\\r\\n&lt;div&gt;\\\\\\\\r\\\\\\\\n\\\\r\\\\n\\r\\n&lt;div&gt;Thank you again for choosing our services. We look forward to doing business with you again.&lt;/div&gt;\\r\\n\\\\r\\\\n\\\\\\\\r\\\\\\\\n\\\\r\\\\n\\r\\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\\r\\n\\\\r\\\\n\\\\\\\\r\\\\\\\\n\\\\r\\\\n\\r\\n&lt;div&gt;Best Regards&amp;nbsp;&lt;/div&gt;\\r\\n\\\\r\\\\n\\\\\\\\r\\\\\\\\n\\\\r\\\\n\\r\\n&lt;div&gt;&amp;nbsp;&lt;/div&gt;\\r\\n\\\\r\\\\n\\\\\\\\r\\\\\\\\n\\\\r\\\\n\\r\\n&lt;div&gt;[[company_name]]&lt;/div&gt;\\r\\n\\\\r\\\\n\\\\\\\\r\\\\\\\\n&lt;/div&gt;\\r\\n\\\\r\\\\n\\\\\\\\r\\\\\\\\n&lt;/div&gt;', '', '', '[\\\"user_name\\\",\\\"user_id\\\",\\\"user_name\\\",\\\"company_name\\\",\\\"site_url\\\",\\\"company_contact_info\\\",\\\"company_logo\\\",\\\"company_contact_info\\\",\\\"company_logo\\\",\\\"company_name\\\"]'),
(10, 'new_booking_confirmation_to_customer', 'Booking Confirmation', '', '&lt;p&gt;Dear [[user_name]],&lt;/p&gt;\n&lt;p&gt;Thank you for choosing [[provider_name]]. We are pleased to confirm your booking.&lt;/p&gt;\n&lt;p&gt;Booking Details:&lt;/p&gt;\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\n&lt;ul&gt;\n&lt;li&gt;Booking Date:[[booking_date]]&lt;/li&gt;\n&lt;li&gt;Time:[[booking_time]]&lt;/li&gt;\n&lt;li&gt;Address:[[booking_address]]&lt;/li&gt;\n&lt;li&gt;Services include:[[booking_service_names]]&lt;/li&gt;\n&lt;/ul&gt;\n&lt;p&gt;We look forward to serving you. If you have any questions or need to make changes to your booking, please don&#039;t hesitate to contact us at [Contact Information].&lt;/p&gt;\n&lt;p&gt;Best regards,&lt;/p&gt;\n&lt;p&gt;[[company_name]]&lt;/p&gt;\n&lt;p&gt;[[company_contact_info]]&lt;/p&gt;', '', '', '[\"user_name\",\"provider_name\",\"booking_date\",\"booking_time\",\"booking_address\",\"company_name\",\"company_contact_info\"]'),
(11, 'new_booking_received_for_provider', 'New Booking Received', '', '&lt;p&gt;Dear [[provider_name]] ,&lt;/p&gt;\n&lt;p&gt;We are delighted to inform you that a new booking has been received through our platform.&lt;/p&gt;\n&lt;p&gt;Booking Details:&lt;/p&gt;\n&lt;ul&gt;\n&lt;li&gt;Service:[[booking_service_names]]&lt;/li&gt;\n&lt;li&gt;Booking Date: [[booking_date]]&lt;/li&gt;\n&lt;li&gt;Time: [[booking_time]]&lt;/li&gt;\n&lt;li&gt;Customer:[[user_name]]&lt;/li&gt;\n&lt;/ul&gt;\n&lt;p&gt;Please ensure that you are prepared for the appointment and ready to provide exceptional service to our valued customer.&lt;/p&gt;\n&lt;p&gt;If you have any questions or require further information regarding this booking, feel free to reach out to us.&lt;/p&gt;\n&lt;p&gt;Thank you for being a part of our service and for your commitment to excellence.&lt;/p&gt;\n&lt;p&gt;Best regards,&lt;/p&gt;\n&lt;p&gt;[[company_name]]&lt;/p&gt;\n&lt;p&gt;[[company_contact_info]]&lt;/p&gt;', '', '', '[\"provider_name\",\"booking_service_names\",\"booking_date\",\"booking_time\",\"user_name\",\"company_name\",\"company_contact_info\"]'),
(12, 'provider_update_information', 'Provider Update Information', '', '&lt;p&gt;Dear [[company_name]]&lt;/p&gt;\\r\\n&lt;p&gt;I hope this message finds you well.&lt;/p&gt;\\r\\n&lt;p&gt;I wanted to inform you that [Provider Name] has recently updated their details. Please find the updated information below:&lt;/p&gt;\\r\\n&lt;p&gt;Provider ID: [[provider_id]]&lt;/p&gt;\\r\\n&lt;p&gt;[[provider_name]] has taken the initiative to ensure that their information is accurate and up-to-date in our records. If there are any further steps required from our end regarding this update, please let us know.&lt;/p&gt;\\r\\n&lt;p&gt;Thank you for your attention to this matter.&lt;/p&gt;\\r\\n&lt;p&gt;Best regards,&lt;/p&gt;\\r\\n&lt;p&gt;[[company_name]]&lt;/p&gt;\\r\\n&lt;p&gt;[[company_contact_info]]&lt;/p&gt;', '', '', '[\\\"company_name\\\",\\\"provider_id\\\",\\\"provider_name\\\",\\\"company_name\\\",\\\"company_contact_info\\\"]'),
(13, 'new_provider_registerd', 'New Provider Registered', '', '&lt;p&gt;Subject: New Provider Registered&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;Dear [[company_name]],&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;I hope this email finds you well.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;I&#039;m pleased to inform you that a new provider has registered with us. Here are the details of the new registration:&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;Provider Name:[[provider_name]]&lt;/p&gt;\\r\\n&lt;p&gt;Provider ID:[[provider_id]]&lt;/p&gt;\\r\\n&lt;p&gt;We welcome [[provider_name]] to our platform and look forward to exploring potential collaborations with them. Kindly review the provided information and proceed with the necessary steps to onboard them into our system.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;If you require any further details or assistance regarding this registration, please don&#039;t hesitate to reach out to me.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;Thank you for your attention to this matter.&lt;/p&gt;\\r\\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\\r\\n&lt;p&gt;Best regards,&lt;/p&gt;\\r\\n&lt;p&gt;[[company_name]]&lt;/p&gt;\\r\\n&lt;p&gt;[[company_contact_info]]&lt;/p&gt;', '', '', '[\\\"company_name\\\",\\\"provider_name\\\",\\\"provider_id\\\",\\\"provider_name\\\",\\\"company_name\\\",\\\"company_contact_info\\\"]'),
(14, 'withdraw_request_received', 'Withdrawal Request Received', 'null', '<p>Dear [[company_name]],</p>\n<p>I hope this email finds you well.</p>\n<p>I wanted to bring to your attention that we have received a withdrawal request from one of our providers. Here are the details of the request:</p>\n<p>Provider Name: [[provider_name]]</p>\n<p>Provider ID:[[provider_id]]</p>\n<p>Amount:[[amount]]</p>\n<p>Currency:[[currency]]</p>\n<p>&nbsp;</p>\n<p>Please review this withdrawal request at your earliest convenience and proceed with the necessary steps to process it accordingly. If you need any additional information or assistance, please don\'t hesitate to reach out to me.</p>\n<p>&nbsp;</p>\n<p>Thank you for your attention to this matter.</p>\n<p>&nbsp;</p>', '', '', '[\"company_name\",\"provider_name\",\"provider_id\",\"amount\",\"currency\"]'),
(15, 'new_rating_given_by_customer', ' New Rating Received from a Customer', 'null', '<p><strong>Subject:</strong> New Rating Received from a Customer !</p>\r\n<p><strong>Dear [[provider_name]],</strong></p>\r\n<p>We wanted to let you know that a customer has recently submitted a rating for your service.</p>\r\n<p>To view the details and feedback, please log in to your provider dashboard at your convenience.</p>\r\n<p>Thank you for your continued commitment to providing excellent service!</p>\r\n<p>Best regards,<br />[[company_name]]</p>\r\n<p>[[company_contact_info]]</p>', '', '', '[\"provider_name\",\"company_name\",\"company_contact_info\"]'),
(16, 'rating_request_to_customer', 'We Value Your Feedback – Please Share Your Rating!', 'null', ' <p><strong>Subject:</strong> We Value Your Feedback – Please Share Your Rating!!</p>\r\n<p><strong>Dear [[user_name]],</strong></p>\r\n<p>We hope you enjoyed your recent experience with us!</p>\r\n<p>Your feedback is incredibly important and helps us to continue improving our services. We would greatly appreciate it if you could take a moment to rate your experience by clicking the link below:</p>\r\n<p> </p>\r\n<p>Thank you for your time and for choosing [[provider_name]]. If you have any additional comments or suggestions, please feel free to reply to this email.</p>\r\n<p>Best regards,<br />[[company_name]]<br /><br /></p>', '', '', '[\"user_name\",\"provider_name\",\"company_name\"]'),
(17, 'cash_collection_by_provider', 'Cash Collection by Provider - Commission Due', '', '<p>Dear [[company_name]],</p>\n    <p>I hope this email finds you well.</p>\n    <p>I wanted to bring to your attention that one of our partners has completed a Cash On Delivery booking. Here are the details of the booking:</p>\n    <p>Provider Name: [[provider_name]]</p>\n    <p>Provider ID: [[provider_id]]</p>\n    <p>Booking ID: [[booking_id]]</p>\n    <p>Commission Amount: [[currency]][[amount]]</p>\n    <p>&nbsp;</p>\n    <p>Please review the cash collection details and process the commission accordingly.</p>\n    <p>&nbsp;</p>\n    <p>Thank you for your attention to this matter.</p>\n    <p>&nbsp;</p>', NULL, NULL, '[\"company_name\",\"provider_name\",\"provider_id\",\"amount\",\"currency\",\"booking_id\",\"company_contact_info\"]'),
(18, 'maintenance_mode', 'System Maintenance - [[company_name]]', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>We would like to inform you that our system is currently under maintenance.</p>\r\n    <p>During this time, some features may be temporarily unavailable. We are working to restore full functionality as soon as possible.</p>\r\n    <p>&nbsp;</p>\r\n    <p>We apologize for any inconvenience this may cause and appreciate your patience.</p>\r\n    <p>&nbsp;</p>\r\n    <p>If you have any urgent concerns, please contact us at [[company_contact_info]].</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for your understanding.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', NULL, NULL, '[\"company_name\",\"site_url\",\"company_contact_info\",\"company_logo\"]'),
(19, 'category_removed', 'Category Removed - [[category_name]]', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>We would like to inform you that the category <strong>[[category_name]]</strong> has been removed from our platform.</p>\r\n    <p>Please note that all services associated with this category have been deactivated.</p>\r\n    <p>&nbsp;</p>\r\n    <p>If you have any questions or concerns, please contact us at [[company_contact_info]].</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for your understanding.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', NULL, NULL, '[\"company_name\",\"category_name\",\"category_id\",\"company_contact_info\",\"site_url\",\"company_logo\"]'),
(20, 'new_blog', 'New Blog Published - [[blog_title]]', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>We are excited to inform you that a new blog has been published on our platform.</p>\r\n    <p>&nbsp;</p>\r\n    <p><strong>Blog Title:</strong> [[blog_title]]</p>\r\n    <p><strong>Category:</strong> [[blog_category_name]]</p>\r\n    <p><strong>Summary:</strong> [[blog_short_description]]</p>\r\n    <p>&nbsp;</p>\r\n    <p>Click <a href=\"[[blog_url]]\">here</a> to read the full blog post.</p>\r\n    <p>&nbsp;</p>\r\n    <p>We hope you enjoy reading it!</p>\r\n    <p>&nbsp;</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', NULL, NULL, '[\"company_name\",\"blog_id\",\"blog_title\",\"blog_slug\",\"blog_url\",\"blog_short_description\",\"blog_category_name\",\"site_url\",\"company_contact_info\",\"company_logo\"]'),
(21, 'new_category_available', 'New Category Available - [[category_name]]', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>We are excited to announce that a new category <strong>[[category_name]]</strong> is now available on our platform!</p>\r\n    <p>You can now explore and book services in this new category.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Visit our website to discover what\'s new: <a href=\"[[site_url]]\">[[site_url]]</a></p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for being a valued member of our community.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', NULL, NULL, '[\"company_name\",\"category_name\",\"category_id\",\"company_contact_info\",\"site_url\",\"company_logo\"]'),
(22, 'new_custom_job_request', 'New Custom Job Request Created', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>A new custom job request has been created by a customer. The details of the request are as follows:</p>\r\n    <p>Customer Name: [[customer_name]]</p>\r\n    <p>Customer ID: [[customer_id]]</p>\r\n    <p>Custom Job Request ID: [[custom_job_request_id]]</p>\r\n    <p>Service Title: [[service_title]]</p>\r\n    <p>Service Description: [[service_short_description]]</p>\r\n    <p>Category: [[category_name]]</p>\r\n    <p>Price Range: [[currency]][[min_price]] - [[currency]][[max_price]]</p>\r\n    <p>Requested Start Date: [[requested_start_date]]</p>\r\n    <p>Requested Start Time: [[requested_start_time]]</p>\r\n    <p>Requested End Date: [[requested_end_date]]</p>\r\n    <p>Requested End Time: [[requested_end_time]]</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for your attention to this matter.</p>\r\n    <p>&nbsp;</p>', NULL, NULL, '[\"company_name\",\"customer_name\",\"customer_id\",\"custom_job_request_id\",\"service_title\",\"service_short_description\",\"category_name\",\"category_id\",\"min_price\",\"max_price\",\"currency\",\"requested_start_date\",\"requested_start_time\",\"requested_end_date\",\"requested_end_time\",\"company_contact_info\"]'),
(23, 'new_user_registered', 'New User Registered', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>A new user has registered on the platform. The details are as follows:</p>\r\n    <p>User Name: [[user_name]]</p>\r\n    <p>User ID: [[user_id]]</p>\r\n    <p>Email: [[user_email]]</p>\r\n    <p>Phone: [[user_phone]]</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for your attention to this matter.</p>\r\n    <p>&nbsp;</p>', NULL, NULL, '[\"company_name\",\"user_name\",\"user_id\",\"user_email\",\"user_phone\",\"company_contact_info\"]'),
(24, 'privacy_policy_changed', 'Privacy Policy Updated - [[company_name]]', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>We would like to inform you that our privacy policy has been updated.</p>\r\n    <p>We encourage you to review the updated privacy policy to stay informed about how we collect, use, and protect your personal information.</p>\r\n    <p>&nbsp;</p>\r\n    <p>You can view the updated privacy policy by visiting our website: <a href=\"[[site_url]]\">[[site_url]]</a></p>\r\n    <p>&nbsp;</p>\r\n    <p>If you have any questions or concerns about the privacy policy, please contact us at [[company_contact_info]].</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for being a valued member of our community.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', NULL, NULL, '[\"company_name\",\"site_url\",\"company_contact_info\",\"company_logo\"]'),
(25, 'promo_code_added', 'New Promo Code Added: [[promo_code]]', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>A new promo code has been added on the platform.</p>\r\n    <p><strong>Promo Code:</strong> [[promo_code]]</p>\r\n    <p><strong>Provider:</strong> [[provider_name]]</p>\r\n    <p><strong>Discount:</strong> [[discount]][[discount_type_symbol]]</p>\r\n    <p><strong>Minimum Order Amount:</strong> [[minimum_order_amount]]</p>\r\n    <p><strong>Maximum Discount Amount:</strong> [[max_discount_amount]]</p>\r\n    <p><strong>Valid From:</strong> [[start_date]]</p>\r\n    <p><strong>Valid Until:</strong> [[end_date]]</p>\r\n    <p><strong>Number of Users:</strong> [[no_of_users]]</p>\r\n    <p>[[company_logo]]</p>\r\n    <p>&nbsp;</p>\r\n    <p>Please review this promo code and take appropriate action if needed.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for your attention to this matter.</p>\r\n    <p>&nbsp;</p>', NULL, NULL, '[\"company_name\",\"provider_name\",\"provider_id\",\"promo_code\",\"promo_code_id\",\"discount\",\"discount_type\",\"discount_type_symbol\",\"minimum_order_amount\",\"max_discount_amount\",\"start_date\",\"end_date\",\"no_of_users\",\"company_contact_info\",\"site_url\"]'),
(26, 'service_updated', 'Service Updated', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>A provider has updated their service information. The details are as follows:</p>\r\n    <p>Provider Name: [[provider_name]]</p>\r\n    <p>Provider ID: [[provider_id]]</p>\r\n    <p>Service ID: [[service_id]]</p>\r\n    <p>Service Title: [[service_title]]</p>\r\n    <p>Category: [[category_name]]</p>\r\n    <p>&nbsp;</p>\r\n    <p>Please review the updated service details in the admin panel.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for your attention to this matter.</p>\r\n    <p>&nbsp;</p>', NULL, NULL, '[\"company_name\",\"provider_name\",\"provider_id\",\"service_id\",\"service_title\",\"service_description\",\"category_name\",\"category_id\",\"service_price\",\"service_discounted_price\",\"currency\",\"company_contact_info\"]'),
(27, 'subscription_changed', 'Subscription Changed - [[subscription_name]]', '', '<p>Hello [[provider_name]]!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>We would like to inform you that your subscription has been changed.</p>\r\n    <p>&nbsp;</p>\r\n    <p><strong>Subscription Details:</strong></p>\r\n    <p><strong>Subscription Name:</strong> [[subscription_name]]</p>\r\n    <p><strong>Price:</strong> [[subscription_price]] [[currency]]</p>\r\n    <p><strong>Duration:</strong> [[subscription_duration]]</p>\r\n    <p><strong>Purchase Date:</strong> [[purchase_date]]</p>\r\n    <p><strong>Expiry Date:</strong> [[expiry_date]]</p>\r\n    <p>&nbsp;</p>\r\n    <p>If you have any questions or concerns, please contact us at [[company_contact_info]].</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for being a valued partner.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', NULL, NULL, '[\"company_name\",\"provider_name\",\"provider_id\",\"subscription_name\",\"subscription_id\",\"subscription_price\",\"subscription_duration\",\"expiry_date\",\"purchase_date\",\"currency\",\"company_contact_info\",\"site_url\",\"company_logo\"]'),
(28, 'subscription_removed', 'Subscription Removed - [[subscription_name]]', '', '<p>Hello [[provider_name]]!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>We would like to inform you that your subscription <strong>[[subscription_name]]</strong> has been removed by the admin.</p>\r\n    <p>Your subscription is now deactivated.</p>\r\n    <p>&nbsp;</p>\r\n    <p>If you have any questions or concerns, please contact us at [[company_contact_info]].</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for your understanding.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', NULL, NULL, '[\"company_name\",\"provider_name\",\"provider_id\",\"subscription_name\",\"subscription_id\",\"company_contact_info\",\"site_url\",\"company_logo\"]'),
(29, 'terms_and_conditions_changed', 'Terms and Conditions Updated - [[company_name]]', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>We would like to inform you that our terms and conditions have been updated.</p>\r\n    <p>We encourage you to review the updated terms and conditions to stay informed about our policies and your rights and responsibilities.</p>\r\n    <p>&nbsp;</p>\r\n    <p>You can view the updated terms and conditions by visiting our website: <a href=\"[[site_url]]\">[[site_url]]</a></p>\r\n    <p>&nbsp;</p>\r\n    <p>If you have any questions or concerns about the terms and conditions, please contact us at [[company_contact_info]].</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for being a valued member of our community.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', NULL, NULL, '[\"company_name\",\"site_url\",\"company_contact_info\",\"company_logo\"]'),
(30, 'user_blocked', 'User Blocked Notification', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>A user has been blocked on the platform.</p>\r\n    <p><strong>User who blocked:</strong> [[blocker_name]] ([[blocker_type]])</p>\r\n    <p><strong>User who was blocked:</strong> [[blocked_user_name]] ([[blocked_user_type]])</p>\r\n    <p><strong>Blocked User ID:</strong> [[blocked_user_id]]</p>\r\n    <p>[[company_logo]]</p>\r\n    <p>&nbsp;</p>\r\n    <p>Please review this blocking action and take appropriate action if needed.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for your attention to this matter.</p>\r\n    <p>&nbsp;</p>', NULL, NULL, '[\"company_name\",\"blocker_name\",\"blocker_type\",\"blocker_id\",\"blocked_user_name\",\"blocked_user_type\",\"blocked_user_id\",\"company_contact_info\",\"site_url\"]'),
(31, 'user_query_submitted', 'New Customer Query Received', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>A new customer query has been submitted via the Contact Us form. The details are as follows:</p>\r\n    <p><strong>Customer Name:</strong> [[customer_name]]</p>\r\n    <p><strong>Customer Email:</strong> [[customer_email]]</p>\r\n    <p><strong>Subject:</strong> [[query_subject]]</p>\r\n    <p><strong>Message:</strong></p>\r\n    <p>[[query_message]]</p>\r\n    <p>&nbsp;</p>\r\n    <p>Please review and respond to this query at your earliest convenience.</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for your attention to this matter.</p>\r\n    <p>&nbsp;</p>', NULL, NULL, '[\"company_name\",\"customer_name\",\"customer_email\",\"query_subject\",\"query_message\",\"company_contact_info\",\"site_url\"]'),
(32, 'user_reported', 'User Report Notification', '', '<p>Hello!</p>\r\n    <p>I hope this email finds you well.</p>\r\n    <p>[[notification_message]]</p>\r\n    <p><strong>Reporter:</strong> [[reporter_name]] ([[reporter_type]])</p>\r\n    <p><strong>Reporter ID:</strong> [[reporter_id]]</p>\r\n    <p><strong>Reported User:</strong> [[reported_user_name]] ([[reported_user_type]])</p>\r\n    <p><strong>Reported User ID:</strong> [[reported_user_id]]</p>\r\n    <p><strong>Reason:</strong> [[report_reason]]</p>\r\n    <p><strong>Additional Information:</strong></p>\r\n    <p>[[additional_info]]</p>\r\n    <p>&nbsp;</p>\r\n    <p>[[action_message]]</p>\r\n    <p>&nbsp;</p>\r\n    <p>Thank you for your attention to this matter.</p>\r\n    <p>&nbsp;</p>', NULL, NULL, '[\"company_name\",\"reporter_name\",\"reporter_type\",\"reporter_id\",\"reported_user_name\",\"reported_user_type\",\"reported_user_id\",\"report_reason\",\"report_reason_id\",\"additional_info\",\"notification_message\",\"action_message\",\"company_contact_info\",\"site_url\"]'),
(33, 'added_additional_charges', 'Additional Charges Added - Booking #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We would like to inform you that additional charges have been added to your booking.</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Booking Details:</strong></p>\r\n<p>Booking ID: #[[booking_id]]</p>\r\n<p>Provider: [[provider_name]]</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Additional Charges:</strong></p>\r\n<p>[[additional_charges_list]]</p>\r\n<p>Total Additional Charges: [[total_additional_charge]] [[currency]]</p>\r\n<p>Final Total: [[final_total]] [[currency]]</p>\r\n<p>&nbsp;</p>\r\n<p>Please review the additional charges and make payment to complete your booking.</p>\r\n<p>&nbsp;</p>\r\n<p>You can view the details and make payment from your account.</p>\r\n<p>&nbsp;</p>\r\n<p>Thank you for your understanding!</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Team</p>', '', '', '[\"booking_id\", \"order_id\", \"total_additional_charge\", \"currency\", \"provider_id\", \"provider_name\", \"customer_id\", \"customer_name\", \"customer_email\", \"additional_charges_list\", \"final_total\", \"company_name\"]'),
(34, 'bid_on_custom_job_request', 'New Bid Received - [[service_title]]', '', '<p>Dear [[customer_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We are pleased to inform you that a provider has placed a bid on your custom job request.</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Job Details:</strong></p>\r\n<p>Service Title: [[service_title]]</p>\r\n<p>Description: [[service_short_description]]</p>\r\n<p>Category: [[category_name]]</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Bid Details:</strong></p>\r\n<p>Provider Name: [[provider_name]]</p>\r\n<p>Bid Amount: [[counter_price]] [[currency]]</p>\r\n<p>Duration: [[duration]] days</p>\r\n<p>Cover Note: [[cover_note]]</p>\r\n<p>&nbsp;</p>\r\n<p>You can view the bid details and accept or reject it from your account.</p>\r\n<p>&nbsp;</p>\r\n<p>Thank you for using our platform!</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Team</p>', '', '', '[\"custom_job_request_id\", \"service_title\", \"service_short_description\", \"provider_id\", \"provider_name\", \"bid_id\", \"counter_price\", \"currency\", \"duration\", \"cover_note\", \"customer_id\", \"customer_name\", \"customer_email\", \"category_name\", \"company_name\"]'),
(35, 'online_payment_success', 'Payment Successful - Booking #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We are pleased to inform you that your payment has been successfully processed.</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Payment Details:</strong></p>\r\n<p>Booking ID: #[[booking_id]]</p>\r\n<p>Amount Paid: [[amount]] [[currency]]</p>\r\n<p>Transaction ID: [[transaction_id]]</p>\r\n<p>Payment Method: [[payment_method]]</p>\r\n<p>Paid At: [[paid_at]]</p>\r\n<p>&nbsp;</p>\r\n<p>Your booking has been confirmed and you can view the details in your account.</p>\r\n<p>&nbsp;</p>\r\n<p>Thank you for your payment!</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Team</p>', '', '', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"transaction_id\", \"customer_id\", \"customer_name\", \"customer_email\", \"payment_method\", \"paid_at\", \"company_name\"]'),
(36, 'online_payment_failed', 'Payment Failed - Booking #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We regret to inform you that your payment could not be processed.</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Payment Details:</strong></p>\r\n<p>Booking ID: #[[booking_id]]</p>\r\n<p>Amount: [[amount]] [[currency]]</p>\r\n<p>Transaction ID: [[transaction_id]]</p>\r\n<p>Payment Method: [[payment_method]]</p>\r\n<p>Failure Reason: [[failure_reason]]</p>\r\n<p>&nbsp;</p>\r\n<p>Please try again or contact our support team if you need assistance.</p>\r\n<p>&nbsp;</p>\r\n<p>If you have any questions, please feel free to reach out to us.</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Support Team</p>', '', '', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"transaction_id\", \"customer_id\", \"customer_name\", \"customer_email\", \"failure_reason\", \"payment_method\", \"company_name\"]'),
(37, 'online_payment_pending', 'Payment Pending - Booking #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We have received your payment request and it is currently being processed.</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Payment Details:</strong></p>\r\n<p>Booking ID: #[[booking_id]]</p>\r\n<p>Amount: [[amount]] [[currency]]</p>\r\n<p>Transaction ID: [[transaction_id]]</p>\r\n<p>Payment Method: [[payment_method]]</p>\r\n<p>&nbsp;</p>\r\n<p>Your payment is pending confirmation. We will notify you once the payment is successfully processed and your booking is confirmed.</p>\r\n<p>&nbsp;</p>\r\n<p>Thank you for your patience.</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Team</p>', '', '', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"transaction_id\", \"customer_id\", \"customer_name\", \"customer_email\", \"payment_method\", \"company_name\"]'),
(38, 'payment_refund_executed', 'Refund Processed - Booking #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We are pleased to inform you that your refund has been successfully processed.</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Refund Details:</strong></p>\r\n<p>Booking ID: #[[booking_id]]</p>\r\n<p>Refund Amount: [[amount]] [[currency]]</p>\r\n<p>Refund ID: [[refund_id]]</p>\r\n<p>Transaction ID: [[transaction_id]]</p>\r\n<p>Processed Date: [[processed_date]]</p>\r\n<p>&nbsp;</p>\r\n<p>The refunded amount will be credited back to your original payment method. Please allow a few business days for the refund to appear in your account.</p>\r\n<p>&nbsp;</p>\r\n<p>If you have any questions or need further assistance, please feel free to contact our support team.</p>\r\n<p>&nbsp;</p>\r\n<p>Thank you for your understanding.</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Support Team</p>', '', '', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"refund_id\", \"transaction_id\", \"customer_id\", \"customer_name\", \"processed_date\", \"company_name\"]'),
(39, 'payment_refund_successful', 'Refund Processed Successfully - Order #[[order_id]]', '', '<p>Dear [[customer_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We are pleased to inform you that your refund request has been processed successfully.</p>\r\n<p>&nbsp;</p>\r\n<p>Refund Details:</p>\r\n<p>Order ID: #[[order_id]]</p>\r\n<p>Refund Amount: [[amount]] [[currency]]</p>\r\n<p>Refund ID: [[refund_id]]</p>\r\n<p>Transaction ID: [[transaction_id]]</p>\r\n<p>Processed Date: [[processed_date]]</p>\r\n<p>&nbsp;</p>\r\n<p>The refunded amount will be credited to your original payment method within 3-5 business days, depending on your bank or payment provider.</p>\r\n<p>&nbsp;</p>\r\n<p>If you have any questions or concerns about this refund, please feel free to contact our support team.</p>\r\n<p>&nbsp;</p>\r\n<p>Thank you for your patience and understanding.</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Support Team</p>', '', '', '[\"order_id\", \"amount\", \"currency\", \"refund_id\", \"transaction_id\", \"customer_name\", \"customer_email\", \"customer_id\", \"processed_date\", \"company_name\"]'),
(40, 'subscription_expired', 'Subscription Expired - [[subscription_name]]', '', '<p>Dear [[provider_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We hope this email finds you well.</p>\r\n<p>&nbsp;</p>\r\n<p>We wanted to inform you that your subscription has expired.</p>\r\n<p>&nbsp;</p>\r\n<p>Subscription Details:</p>\r\n<p>Subscription Name: [[subscription_name]]</p>\r\n<p>Expiry Date: [[expiry_date]]</p>\r\n<p>Purchase Date: [[purchase_date]]</p>\r\n<p>Duration: [[duration]]</p>\r\n<p>&nbsp;</p>\r\n<p>To continue providing services and receiving bookings, please renew your subscription at your earliest convenience.</p>\r\n<p>&nbsp;</p>\r\n<p>If you have any questions or need assistance with renewing your subscription, please feel free to contact our support team.</p>\r\n<p>&nbsp;</p>\r\n<p>Thank you for being a valued provider on our platform.</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Support Team</p>', '', '', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"expiry_date\", \"purchase_date\", \"duration\", \"company_name\"]'),
(41, 'subscription_payment_successful', 'Subscription Payment Successful - [[subscription_name]]', '', '<p>Dear [[provider_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We are pleased to inform you that your subscription payment has been successfully processed.</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Payment Details:</strong></p>\r\n<p>Subscription Name: [[subscription_name]]</p>\r\n<p>Amount Paid: [[amount]] [[currency]]</p>\r\n<p>Transaction ID: [[transaction_id]]</p>\r\n<p>Purchase Date: [[purchase_date]]</p>\r\n<p>Expiry Date: [[expiry_date]]</p>\r\n<p>&nbsp;</p>\r\n<p>Your subscription is now active and you can start receiving bookings.</p>\r\n<p>&nbsp;</p>\r\n<p>Thank you for your subscription!</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Team</p>', '', '', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"amount\", \"currency\", \"transaction_id\", \"purchase_date\", \"expiry_date\", \"company_name\"]'),
(42, 'subscription_payment_failed', 'Subscription Payment Failed - [[subscription_name]]', '', '<p>Dear [[provider_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We regret to inform you that your subscription payment could not be processed.</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Payment Details:</strong></p>\r\n<p>Subscription Name: [[subscription_name]]</p>\r\n<p>Amount: [[amount]] [[currency]]</p>\r\n<p>Transaction ID: [[transaction_id]]</p>\r\n<p>Failure Reason: [[failure_reason]]</p>\r\n<p>&nbsp;</p>\r\n<p>Please try again or contact our support team if you need assistance.</p>\r\n<p>&nbsp;</p>\r\n<p>If you have any questions, please feel free to reach out to us.</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Support Team</p>', '', '', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"amount\", \"currency\", \"transaction_id\", \"failure_reason\", \"company_name\"]'),
(43, 'subscription_payment_pending', 'Subscription Payment Pending - [[subscription_name]]', '', '<p>Dear [[provider_name]],</p>\r\n<p>&nbsp;</p>\r\n<p>We have received your subscription payment request and it is currently being processed.</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Payment Details:</strong></p>\r\n<p>Subscription Name: [[subscription_name]]</p>\r\n<p>Amount: [[amount]] [[currency]]</p>\r\n<p>Transaction ID: [[transaction_id]]</p>\r\n<p>&nbsp;</p>\r\n<p>Your payment is pending confirmation. We will notify you once the payment is successfully processed and your subscription is activated.</p>\r\n<p>&nbsp;</p>\r\n<p>Thank you for your patience.</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] Team</p>', '', '', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"amount\", \"currency\", \"transaction_id\", \"company_name\"]'),
(44, 'subscription_purchased', 'New Subscription Purchase - [[provider_name]]', '', '<p>Dear Admin,</p>\r\n<p>&nbsp;</p>\r\n<p>We wanted to inform you that a provider has purchased a new subscription.</p>\r\n<p>&nbsp;</p>\r\n<p><strong>Subscription Purchase Details:</strong></p>\r\n<p>Provider Name: [[provider_name]]</p>\r\n<p>Provider ID: [[provider_id]]</p>\r\n<p>Subscription Name: [[subscription_name]]</p>\r\n<p>Purchase Date: [[purchase_date]]</p>\r\n<p>Expiry Date: [[expiry_date]]</p>\r\n<p>Duration: [[duration]] days</p>\r\n<p>Amount Paid: [[amount]] [[currency]]</p>\r\n<p>Transaction ID: [[transaction_id]]</p>\r\n<p>&nbsp;</p>\r\n<p>This subscription is now active and the provider can start receiving bookings.</p>\r\n<p>&nbsp;</p>\r\n<p>Best regards,</p>\r\n<p>[[company_name]] System</p>', '', '', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"purchase_date\", \"expiry_date\", \"duration\", \"amount\", \"currency\", \"transaction_id\", \"company_name\"]'),
(45, 'booking_confirmed', 'Booking Confirmed - Order #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n    <p>Your booking has been confirmed!</p>\r\n    <p><strong>Booking Details:</strong></p>\r\n    <ul>\r\n        <li>Booking ID: #[[booking_id]]</li>\r\n        <li>Status: [[booking_status]]</li>\r\n        <li>Service Date: [[date_of_service]]</li>\r\n        <li>Service Time: [[service_time]]</li>\r\n        <li>Provider: [[provider_name]]</li>\r\n        <li>Total Amount: [[currency]] [[final_total]]</li>\r\n    </ul>\r\n    <p>Thank you for choosing our services.</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', '', '', '[\"booking_id\",\"booking_status\",\"customer_name\",\"provider_name\",\"date_of_service\",\"service_time\",\"final_total\",\"currency\",\"company_name\"]'),
(46, 'booking_rescheduled', 'Booking Rescheduled - Order #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n    <p>Your booking has been rescheduled.</p>\r\n    <p><strong>Updated Booking Details:</strong></p>\r\n    <ul>\r\n        <li>Booking ID: #[[booking_id]]</li>\r\n        <li>Status: [[booking_status]]</li>\r\n        <li>New Service Date: [[date_of_service]]</li>\r\n        <li>New Service Time: [[service_time]]</li>\r\n        <li>Provider: [[provider_name]]</li>\r\n        <li>Total Amount: [[currency]] [[final_total]]</li>\r\n    </ul>\r\n    <p>If you have any questions, please contact us.</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', '', '', '[\"booking_id\",\"booking_status\",\"customer_name\",\"provider_name\",\"date_of_service\",\"service_time\",\"final_total\",\"currency\",\"company_name\"]'),
(47, 'booking_cancelled', 'Booking Cancelled - Order #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n    <p>Your booking has been cancelled.</p>\r\n    <p><strong>Booking Details:</strong></p>\r\n    <ul>\r\n        <li>Booking ID: #[[booking_id]]</li>\r\n        <li>Status: [[booking_status]]</li>\r\n        <li>Service Date: [[date_of_service]]</li>\r\n        <li>Provider: [[provider_name]]</li>\r\n    </ul>\r\n    <p>If you have any questions or concerns, please contact us.</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', '', '', '[\"booking_id\",\"booking_status\",\"customer_name\",\"provider_name\",\"date_of_service\",\"company_name\"]'),
(48, 'booking_completed', 'Booking Completed - Order #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n    <p>Your booking has been completed successfully!</p>\r\n    <p><strong>Booking Details:</strong></p>\r\n    <ul>\r\n        <li>Booking ID: #[[booking_id]]</li>\r\n        <li>Status: [[booking_status]]</li>\r\n        <li>Service Date: [[date_of_service]]</li>\r\n        <li>Provider: [[provider_name]]</li>\r\n        <li>Total Amount: [[currency]] [[final_total]]</li>\r\n    </ul>\r\n    <p>Thank you for using our services. We hope you had a great experience!</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', '', '', '[\"booking_id\",\"booking_status\",\"customer_name\",\"provider_name\",\"date_of_service\",\"final_total\",\"currency\",\"company_name\"]'),
(49, 'booking_started', 'Service Started - Order #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n    <p>Your service has started!</p>\r\n    <p><strong>Booking Details:</strong></p>\r\n    <ul>\r\n        <li>Booking ID: #[[booking_id]]</li>\r\n        <li>Status: [[booking_status]]</li>\r\n        <li>Service Date: [[date_of_service]]</li>\r\n        <li>Service Time: [[service_time]]</li>\r\n        <li>Provider: [[provider_name]]</li>\r\n    </ul>\r\n    <p>Your service provider has started working on your booking.</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', '', '', '[\"booking_id\",\"booking_status\",\"customer_name\",\"provider_name\",\"date_of_service\",\"service_time\",\"company_name\"]'),
(50, 'booking_ended', 'Booking Ended - Order #[[booking_id]]', '', '<p>Dear [[customer_name]],</p>\r\n    <p>Your booking has ended.</p>\r\n    <p><strong>Booking Details:</strong></p>\r\n    <ul>\r\n        <li>Booking ID: #[[booking_id]]</li>\r\n        <li>Status: [[booking_status]]</li>\r\n        <li>Service Date: [[date_of_service]]</li>\r\n        <li>Provider: [[provider_name]]</li>\r\n        <li>Total Amount: [[currency]] [[final_total]]</li>\r\n    </ul>\r\n    <p>[[status_message]]</p>\r\n    <p>If you have any questions, please contact us.</p>\r\n    <p>Best regards,<br>[[company_name]]</p>', '', '', '[\"booking_id\",\"booking_status\",\"customer_name\",\"provider_name\",\"date_of_service\",\"final_total\",\"currency\",\"status_message\",\"company_name\"]'),
(51, 'provider_edits_service_details', 'Provider [[provider_name]] Has Edited Service Details', '', '<!DOCTYPE html>\n<html>\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>Service Details Edited</title>\n</head>\n<body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;\">\n    <div style=\"background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;\">\n        <h2 style=\"color: #2c3e50; margin-top: 0;\">Service Details Edited</h2>\n        <p>Hello Admin,</p>\n        <p>The provider <strong>[[provider_name]]</strong> has edited the details of their service.</p>\n    </div>\n    \n    <div style=\"background-color: #ffffff; border: 1px solid #dee2e6; padding: 20px; border-radius: 5px; margin-bottom: 20px;\">\n        <h3 style=\"color: #495057; margin-top: 0;\">Service Information</h3>\n        <table style=\"width: 100%; border-collapse: collapse;\">\n            <tr>\n                <td style=\"padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold; width: 40%;\">Service ID:</td>\n                <td style=\"padding: 8px; border-bottom: 1px solid #dee2e6;\">[[service_id]]</td>\n            </tr>\n            <tr>\n                <td style=\"padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;\">Service Title:</td>\n                <td style=\"padding: 8px; border-bottom: 1px solid #dee2e6;\">[[service_title]]</td>\n            </tr>\n            <tr>\n                <td style=\"padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;\">Category:</td>\n                <td style=\"padding: 8px; border-bottom: 1px solid #dee2e6;\">[[category_name]]</td>\n            </tr>\n            <tr>\n                <td style=\"padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;\">Price:</td>\n                <td style=\"padding: 8px; border-bottom: 1px solid #dee2e6;\">[[currency]] [[service_price]]</td>\n            </tr>\n            <tr>\n                <td style=\"padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;\">Discounted Price:</td>\n                <td style=\"padding: 8px; border-bottom: 1px solid #dee2e6;\">[[currency]] [[service_discounted_price]]</td>\n            </tr>\n            <tr>\n                <td style=\"padding: 8px; font-weight: bold;\">Provider:</td>\n                <td style=\"padding: 8px;\">[[provider_name]] (ID: [[provider_id]])</td>\n            </tr>\n        </table>\n    </div>\n    \n    <div style=\"background-color: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #0066cc;\">\n        <p style=\"margin: 0;\"><strong>Note:</strong> Please review the updated service details in the admin panel to ensure all changes comply with your platform guidelines.</p>\n    </div>\n    \n    <div style=\"margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; text-align: center; color: #6c757d; font-size: 12px;\">\n        <p>This is an automated notification from [[company_name]].</p>\n        <p>Visit <a href=\"[[site_url]]\" style=\"color: #0066cc;\">[[site_url]]</a> for more information.</p>\n        <p>[[company_contact_info]]</p>\n    </div>\n</body>\n</html>', NULL, NULL, '[\"provider_name\",\"provider_id\",\"service_id\",\"service_title\",\"service_description\",\"category_name\",\"category_id\",\"service_price\",\"service_discounted_price\",\"currency\",\"company_name\",\"site_url\",\"company_contact_info\"]');

-- --------------------------------------------------------

--
-- Table structure for table `enquiries`
--

CREATE TABLE `enquiries` (
  `id` int(60) NOT NULL,
  `customer_id` int(250) DEFAULT NULL,
  `title` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0: Open 1:Close',
  `userType` int(11) NOT NULL COMMENT '0 : Admin\r\n1: Provider\r\n2: customer\r\n',
  `date` date NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `provider_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` mediumtext DEFAULT NULL,
  `answer` mediumtext DEFAULT NULL,
  `status` char(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` mediumint(8) UNSIGNED NOT NULL,
  `name` varchar(20) NOT NULL,
  `description` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator'),
(2, 'members', 'Customers'),
(3, 'partners', 'Service Providing Partners');

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `language` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `is_rtl` tinyint(4) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_default` varchar(255) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `language`, `code`, `is_rtl`, `image`, `created_at`, `updated_at`, `is_default`) VALUES
(1, 'English', 'en', 0, 'languages/hi_1756187789.png', '2021-12-25 10:37:11', '2025-12-04 13:46:57', '1');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `login` varchar(100) NOT NULL,
  `time` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(1, '2021-12-02-124048', 'App\\Database\\Migrations\\AddProducts', 'default', 'App', 1669892046, 1),
(2, '2021-12-03-040835', 'App\\Database\\Migrations\\Test', 'default', 'App', 1669892046, 1),
(3, '2022-12-01-114504', 'App\\Database\\Migrations\\users_tokens', 'default', 'App', 1669900113, 2),
(5, '2023-10-12-112040', 'CodeIgniter\\Queue\\Database\\Migrations\\AddQueueTables', 'default', 'App', 1755087313, 3),
(6, '2023-11-05-064053', 'CodeIgniter\\Queue\\Database\\Migrations\\AddPriorityField', 'default', 'App', 1760349080, 4),
(7, '2024-03-21-000000', 'App\\Database\\Migrations\\CreateUserReportsAndBlockedUsersTables', 'default', 'App', 1760349080, 4),
(8, '2024-12-27-110712', 'CodeIgniter\\Queue\\Database\\Migrations\\ChangePayloadFieldTypeInSqlsrv', 'default', 'App', 1760349080, 4),
(9,'2025-12-22-095732', 'App\\Database\\Migrations\\AdoptExistingSchema', 'default', 'App', UNIX_TIMESTAMP(), 5);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `type_id` varchar(512) NOT NULL DEFAULT '0',
  `image` varchar(128) DEFAULT NULL,
  `order_id` int(50) DEFAULT NULL,
  `user_id` varchar(512) DEFAULT NULL,
  `is_readed` tinyint(1) NOT NULL,
  `notification_type` varchar(50) DEFAULT NULL,
  `date_sent` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `target` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `order_status` text DEFAULT NULL,
  `custom_job_request_id` text DEFAULT NULL,
  `bidder_id` text DEFAULT NULL,
  `bid_status` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL,
  `event_key` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `parameters` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores FCM notification templates';

--
-- Dumping data for table `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `event_key`, `title`, `body`, `parameters`, `created_at`, `updated_at`) VALUES
(1, 'withdraw_request_received', 'New Payment Withdraw Request', 'New payment withdraw request of amount [[currency]][[amount]] from provider [[provider_id]]: [[provider_name]]\n', '[\"company_name\",\"provider_name\",\"provider_id\", \"amount\", \"currency\"]', '2025-11-05 18:35:00', '2025-11-05 18:35:00'),
(2, 'cash_collection_by_provider', 'New Cash Collection by Provider', 'Provider [[provider_name]] (ID: [[provider_id]]) has completed COD booking #[[booking_id]]. Admin commission of [[currency]][[amount]] is due.\',\n    \'[\"company_name\",\"provider_name\",\"provider_id\",\"amount\",\"currency\",\"booking_id\"]', '[\"company_name\",\"provider_name\",\"provider_id\",\"amount\",\"currency\",\"booking_id\"]', '2025-11-10 10:27:00', '2025-11-10 10:27:00'),
(3, 'maintenance_mode', 'Maintenance Mode Enabled', 'The system is currently under maintenance. We apologize for any inconvenience. Please check back later.', '[\"company_name\",\"site_url\",\"company_contact_info\"]', '2025-11-12 09:48:28', '2025-11-12 09:48:28'),
(4, 'category_removed', 'Category Removed', 'The category [[category_name]] has been removed from the platform. Services in this category have been deactivated.', '[\"company_name\",\"category_name\",\"category_id\",\"site_url\",\"company_contact_info\"]', '2025-11-12 09:50:08', '2025-11-12 09:50:08'),
(5, 'new_blog', 'New Blog Published', 'A new blog \"[[blog_title]]\" has been published. Tap to read more.', '[\"company_name\",\"blog_id\",\"blog_title\",\"blog_slug\",\"blog_url\",\"blog_short_description\",\"blog_category_name\",\"site_url\",\"company_contact_info\"]', '2025-11-12 09:50:58', '2025-11-12 09:50:58'),
(6, 'new_booking_confirmation_to_customer', 'Booking Confirmed', 'Your booking #[[booking_id]] has been confirmed! Service: [[booking_service_names]] with [[provider_name]]. Date: [[booking_date]] at [[booking_time]].', '[\"company_name\",\"provider_name\",\"provider_id\",\"user_name\",\"user_id\",\"booking_id\",\"booking_date\",\"booking_time\",\"booking_service_names\",\"booking_address\",\"booking_status\",\"amount\",\"currency\",\"site_url\"]', '2025-11-12 09:51:43', '2025-11-12 09:51:43'),
(7, 'new_booking_received_for_provider', 'New Booking Received', 'You have received a new booking #[[booking_id]] from [[user_name]]. Service: [[booking_service_names]]. Date: [[booking_date]] at [[booking_time]].', '[\"company_name\",\"provider_name\",\"provider_id\",\"user_name\",\"user_id\",\"booking_id\",\"booking_date\",\"booking_time\",\"booking_service_names\",\"booking_address\",\"booking_status\",\"amount\",\"currency\",\"site_url\"]', '2025-11-12 09:52:18', '2025-11-12 09:52:18'),
(8, 'new_category_available', 'New Category Available', 'A new category [[category_name]] is now available. Explore services in this category!', '[\"company_name\",\"category_name\",\"category_id\",\"site_url\",\"company_contact_info\"]', '2025-11-12 09:52:34', '2025-11-12 09:52:34'),
(9, 'new_custom_job_request', 'New Custom Job Request', 'Customer [[customer_name]] has created a new custom job request #[[custom_job_request_id]]', '[\"customer_name\",\"customer_id\",\"custom_job_request_id\"]', '2025-11-12 09:53:26', '2025-11-12 09:53:26'),
(10, 'new_message', 'New Message', '[[sender_name]]: [[message_content]]', '[\"company_name\",\"sender_name\",\"sender_type\",\"receiver_name\",\"receiver_type\",\"message_content\",\"booking_id\",\"site_url\"]', '2025-11-12 09:54:11', '2025-11-12 09:54:11'),
(11, 'new_provider_registerd', 'New Provider Registered', 'A new provider [[provider_name]] (ID: [[provider_id]]) has registered on the platform.', '[\"company_name\",\"provider_name\",\"provider_id\"]', '2025-11-12 09:54:32', '2025-11-12 09:54:32'),
(12, 'new_rating_given_by_customer', 'New Rating Received', 'You have received a new rating from [[user_name]] for your service. Rating: [[rating]]/5', '[\"company_name\",\"provider_name\",\"provider_id\",\"user_name\",\"user_id\",\"service_id\",\"service_title\",\"rating\",\"site_url\",\"company_contact_info\"]', '2025-11-12 09:54:54', '2025-11-12 09:54:54'),
(13, 'new_user_registered', 'New User Registered', 'A new user [[user_name]] (ID: [[user_id]]) has registered on the platform.', '[\"user_name\",\"user_id\",\"user_email\",\"user_phone\"]', '2025-11-12 09:55:18', '2025-11-12 09:55:18'),
(14, 'payment_settlement', 'Payment Settlement', 'Your payment settlement of [[currency]] [[amount]] has been completed successfully. The amount has been credited to your account.', '[\"company_name\",\"provider_name\",\"provider_id\",\"amount\",\"currency\",\"site_url\",\"company_contact_info\"]', '2025-11-12 09:56:38', '2025-11-12 09:56:38'),
(15, 'privacy_policy_changed', 'Privacy Policy Updated', 'The privacy policy has been updated by the admin. Please review the updated privacy policy to stay informed about our data practices.', '[\"company_name\",\"site_url\",\"company_contact_info\"]', '2025-11-12 09:56:55', '2025-11-12 09:56:55'),
(16, 'promo_code_added', 'New Promo Code Added', 'A new promo code \"[[promo_code]]\" has been added for [[provider_name]]. Discount: [[discount]][[discount_type_symbol]] (Min order: [[minimum_order_amount]]). Valid from [[start_date]] to [[end_date]].', '[\"company_name\",\"provider_name\",\"provider_id\",\"promo_code\",\"promo_code_id\",\"discount\",\"discount_type\",\"discount_type_symbol\",\"minimum_order_amount\",\"max_discount_amount\",\"start_date\",\"end_date\",\"no_of_users\",\"site_url\"]', '2025-11-12 09:57:40', '2025-11-12 09:57:40'),
(17, 'provider_approved', 'Provider Approved', 'Congratulations! Your provider account [[provider_name]] (ID: [[provider_id]]) has been approved by the admin. You can now start using the platform.', '[\"company_name\",\"provider_name\",\"provider_id\",\"site_url\",\"company_contact_info\"]', '2025-11-12 09:58:17', '2025-11-12 09:58:17'),
(18, 'provider_disapproved', 'Provider Disapproved', 'Your provider account [[provider_name]] (ID: [[provider_id]]) has been disapproved by the admin. Please contact support for more information.', '[\"company_name\",\"provider_name\",\"provider_id\",\"site_url\",\"company_contact_info\"]', '2025-11-12 09:58:40', '2025-11-12 09:58:40'),
(19, 'service_updated', 'Service Updated', 'Provider [[provider_name]] (ID: [[provider_id]]) has updated service #[[service_id]]: [[service_title]].', '[\"company_name\",\"provider_name\",\"provider_id\",\"service_id\",\"service_title\",\"category_name\"]', '2025-11-12 09:58:58', '2025-11-12 09:58:58'),
(20, 'provider_update_information', 'Provider Information Updated', 'Provider [[provider_name]] (ID: [[provider_id]]) has updated their profile information.', '[\"company_name\",\"provider_name\",\"provider_id\"]', '2025-11-12 10:00:05', '2025-11-12 10:00:05'),
(21, 'service_approved', 'Service Approved', 'Your service [[service_title]] has been approved by the admin. It is now live and available for customers.', '[\"company_name\",\"provider_name\",\"provider_id\",\"service_id\",\"service_title\",\"site_url\",\"company_contact_info\"]', '2025-11-12 10:00:43', '2025-11-12 10:00:43'),
(22, 'service_disapproved', 'Service Disapproved', 'Your service [[service_title]] has been disapproved by the admin. Please contact support for more information.', '[\"company_name\",\"provider_name\",\"provider_id\",\"service_id\",\"service_title\",\"site_url\",\"company_contact_info\"]', '2025-11-12 10:01:00', '2025-11-12 10:01:00'),
(23, 'subscription_changed', 'Subscription Changed', 'Your subscription has been changed to [[subscription_name]]. Price: [[subscription_price]] [[currency]]. Expiry Date: [[expiry_date]].', '[\"company_name\",\"provider_name\",\"provider_id\",\"subscription_name\",\"subscription_id\",\"subscription_price\",\"subscription_duration\",\"expiry_date\",\"purchase_date\",\"currency\",\"site_url\",\"company_contact_info\"]', '2025-11-12 10:01:52', '2025-11-12 10:01:52'),
(24, 'subscription_removed', 'Subscription Removed', 'Your subscription [[subscription_name]] has been removed by the admin. Your subscription is now deactivated.', '[\"company_name\",\"provider_name\",\"provider_id\",\"subscription_name\",\"subscription_id\",\"site_url\",\"company_contact_info\"]', '2025-11-12 10:02:31', '2025-11-12 10:02:31'),
(25, 'terms_and_conditions_changed', 'Terms and Conditions Updated', 'The terms and conditions have been updated by the admin. Please review the updated terms and conditions to stay informed about our policies.', '[\"company_name\",\"site_url\",\"company_contact_info\"]', '2025-11-12 10:03:08', '2025-11-12 10:03:08'),
(26, 'user_account_active', 'Account Activated', 'Your account has been activated successfully. You can now access all features of the platform.', '[\"company_name\",\"user_name\",\"user_id\",\"site_url\",\"company_contact_info\"]', '2025-11-12 10:03:47', '2025-11-12 10:03:47'),
(27, 'user_account_deactive', 'Account Deactivated', 'Your account has been deactivated. Please contact support at [[company_contact_info]] if you have any questions.', '[\"company_name\",\"user_name\",\"user_id\",\"site_url\",\"company_contact_info\"]', '2025-11-12 10:04:04', '2025-11-12 10:04:04'),
(28, 'user_blocked', 'User Blocked', '[[blocker_name]] ([[blocker_type]]) has blocked [[blocked_user_name]] ([[blocked_user_type]]).', '[\"company_name\",\"blocker_name\",\"blocker_type\",\"blocker_id\",\"blocked_user_name\",\"blocked_user_type\",\"blocked_user_id\",\"site_url\"]', '2025-11-12 10:04:19', '2025-11-12 10:04:19'),
(29, 'user_query_submitted', 'New Customer Query', 'A new query has been submitted by [[customer_name]] ([[customer_email]]). Subject: [[query_subject]]', '[\"company_name\",\"customer_name\",\"customer_email\",\"query_subject\",\"query_message\",\"site_url\"]', '2025-11-12 10:05:01', '2025-11-12 10:05:01'),
(30, 'user_reported', 'User Reported', '[[notification_message]]', '[\"company_name\",\"reporter_name\",\"reporter_type\",\"reporter_id\",\"reported_user_name\",\"reported_user_type\",\"reported_user_id\",\"report_reason\",\"report_reason_id\",\"additional_info\",\"notification_message\",\"site_url\"]', '2025-11-12 10:05:45', '2025-11-12 10:05:45'),
(31, 'withdraw_request_approved', 'Withdrawal Request Approved', 'Your withdrawal request of [[currency]] [[amount]] has been approved. The amount has been processed successfully.', '[\"company_name\",\"provider_name\",\"provider_id\",\"amount\",\"currency\",\"site_url\",\"company_contact_info\"]', '2025-11-12 10:06:23', '2025-11-12 10:06:23'),
(32, 'withdraw_request_disapproved', 'Withdrawal Request Disapproved', 'Your withdrawal request of [[currency]] [[amount]] has been disapproved. Please contact support for more information.', '[\"company_name\",\"provider_name\",\"provider_id\",\"amount\",\"currency\",\"site_url\",\"company_contact_info\"]', '2025-11-12 10:06:39', '2025-11-12 10:06:39'),
(33, 'added_additional_charges', 'Additional Charges Added', 'Provider [[provider_name]] has added additional charges of [[total_additional_charge]] [[currency]] to your booking #[[booking_id]]. Please review and make payment.', '[\"booking_id\", \"order_id\", \"total_additional_charge\", \"currency\", \"provider_id\", \"provider_name\", \"customer_id\", \"customer_name\", \"additional_charges_list\", \"final_total\"]', '2025-11-14 05:20:24', '2025-11-14 05:20:24'),
(34, 'bid_on_custom_job_request', 'New Bid Received', 'Provider [[provider_name]] has placed a bid of [[counter_price]] [[currency]] on your custom job request: [[service_title]]. Duration: [[duration]] days.', '[\"custom_job_request_id\", \"service_title\", \"service_short_description\", \"provider_id\", \"provider_name\", \"bid_id\", \"counter_price\", \"currency\", \"duration\", \"cover_note\", \"customer_id\", \"customer_name\", \"category_name\"]', '2025-11-14 05:20:58', '2025-11-14 05:20:58'),
(35, 'online_payment_success', 'Payment Successful', 'Your payment of [[amount]] [[currency]] for Booking #[[booking_id]] has been successfully processed. Transaction ID: [[transaction_id]].', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"transaction_id\", \"customer_id\", \"customer_name\", \"payment_method\", \"paid_at\"]', '2025-11-14 05:21:14', '2025-11-14 05:21:14'),
(36, 'online_payment_failed', 'Payment Failed', 'Your payment of [[amount]] [[currency]] for Booking #[[booking_id]] has failed. Transaction ID: [[transaction_id]]. Please try again or contact support.', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"transaction_id\", \"customer_id\", \"customer_name\", \"failure_reason\", \"payment_method\"]', '2025-11-14 05:21:14', '2025-11-14 05:21:14'),
(37, 'online_payment_pending', 'Payment Pending', 'Your payment of [[amount]] [[currency]] for Booking #[[booking_id]] is pending. Transaction ID: [[transaction_id]]. We will notify you once the payment is confirmed.', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"transaction_id\", \"customer_id\", \"customer_name\", \"payment_method\"]', '2025-11-14 05:21:14', '2025-11-14 05:21:14'),
(38, 'payment_refund_executed', 'Refund Processed', 'Your refund of [[amount]] [[currency]] for booking #[[booking_id]] has been processed successfully. Refund ID: [[refund_id]].', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"refund_id\", \"transaction_id\", \"customer_id\", \"customer_name\", \"processed_date\"]', '2025-11-14 05:21:35', '2025-11-14 05:21:35'),
(39, 'payment_refund_successful', 'Refund Processed Successfully', 'A refund of [[amount]] [[currency]] for Order #[[order_id]] has been processed successfully. Refund ID: [[refund_id]]. The amount will be credited within 3-5 business days.', '[\"order_id\", \"amount\", \"currency\", \"refund_id\", \"transaction_id\", \"customer_name\", \"customer_email\", \"customer_id\", \"processed_date\"]', '2025-11-14 05:21:44', '2025-11-14 05:21:44'),
(40, 'subscription_expired', 'Subscription Expired', 'Your subscription [[subscription_name]] has expired on [[expiry_date]]. Please renew your subscription to continue providing services.', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"expiry_date\", \"purchase_date\", \"duration\"]', '2025-11-14 05:23:03', '2025-11-14 05:23:03'),
(41, 'subscription_payment_successful', 'Payment Successful', 'Your subscription payment for [[subscription_name]] has been successfully processed. Amount: [[amount]] [[currency]]. Transaction ID: [[transaction_id]].', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"amount\", \"currency\", \"transaction_id\", \"purchase_date\", \"expiry_date\"]', '2025-11-14 05:23:38', '2025-11-14 05:23:38'),
(42, 'subscription_payment_failed', 'Payment Failed', 'Your subscription payment for [[subscription_name]] has failed. Amount: [[amount]] [[currency]]. Transaction ID: [[transaction_id]]. Please try again or contact support.', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"amount\", \"currency\", \"transaction_id\", \"failure_reason\"]', '2025-11-14 05:23:38', '2025-11-14 05:23:38'),
(43, 'subscription_payment_pending', 'Payment Pending', 'Your subscription payment for [[subscription_name]] is pending. Amount: [[amount]] [[currency]]. Transaction ID: [[transaction_id]]. We will notify you once the payment is confirmed.', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"amount\", \"currency\", \"transaction_id\"]', '2025-11-14 05:23:38', '2025-11-14 05:23:38'),
(44, 'subscription_purchased', 'New Subscription Purchase', 'Provider [[provider_name]] has purchased subscription [[subscription_name]] for [[amount]] [[currency]]. Purchase date: [[purchase_date]].', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"purchase_date\", \"expiry_date\", \"duration\", \"amount\", \"currency\", \"transaction_id\"]', '2025-11-14 05:23:56', '2025-11-14 05:23:56'),
(45, 'rating_request_to_customer', 'Rate Your Experience', 'Your booking #[[booking_id]] has been completed. Please rate your experience and help us improve our services.', '[\"booking_id\", \"provider_id\", \"provider_name\", \"user_id\", \"user_name\"]', '2025-11-14 05:26:10', '2025-11-14 05:26:10'),
(46, 'booking_confirmed', 'Booking Confirmed', 'Your booking #[[booking_id]] has been confirmed. Service Date: [[date_of_service]] at [[service_time]].', '[\"booking_id\",\"date_of_service\",\"service_time\"]', '2025-11-17 06:33:49', '2025-11-17 06:33:49'),
(47, 'booking_rescheduled', 'Booking Rescheduled', 'Your booking #[[booking_id]] has been rescheduled. New Date: [[date_of_service]] at [[service_time]].', '[\"booking_id\",\"date_of_service\",\"service_time\"]', '2025-11-17 06:33:49', '2025-11-17 06:33:49'),
(48, 'booking_cancelled', 'Booking Cancelled', 'Your booking #[[booking_id]] has been cancelled.', '[\"booking_id\"]', '2025-11-17 06:33:49', '2025-11-17 06:33:49'),
(49, 'booking_completed', 'Booking Completed', 'Your booking #[[booking_id]] has been completed successfully!', '[\"booking_id\"]', '2025-11-17 06:33:49', '2025-11-17 06:33:49'),
(50, 'booking_started', 'Service Started', 'Your service for booking #[[booking_id]] has started.', '[\"booking_id\"]', '2025-11-17 06:33:49', '2025-11-17 06:33:49'),
(51, 'booking_ended', 'Booking Ended', 'Your booking #[[booking_id]] has ended. [[status_message]]', '[\"booking_id\",\"status_message\"]', '2025-11-17 06:33:49', '2025-11-17 06:33:49'),
(52, 'provider_edits_service_details', 'Service Details Edited', 'Provider [[provider_name]] has edited service \"[[service_title]]\". Please review the changes.', '[\"provider_name\",\"service_title\"]', '2025-12-18 16:13:16', '2025-12-18 16:13:16');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) UNSIGNED NOT NULL,
  `partner_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL DEFAULT 0,
  `city` varchar(252) NOT NULL,
  `total` double NOT NULL,
  `visiting_charges` double NOT NULL DEFAULT 0,
  `promo_code` varchar(64) NOT NULL,
  `promo_discount` double NOT NULL,
  `final_total` double NOT NULL,
  `payment_method` varchar(64) NOT NULL,
  `admin_earnings` double NOT NULL,
  `partner_earnings` double NOT NULL,
  `is_commission_settled` tinyint(1) NOT NULL COMMENT '0: Not settled\r\n1: Settled\r\n',
  `address_id` int(11) NOT NULL,
  `address` varchar(2048) NOT NULL,
  `date_of_service` date NOT NULL,
  `starting_time` time NOT NULL,
  `ending_time` time NOT NULL,
  `duration` varchar(64) NOT NULL COMMENT 'in minutes',
  `status` varchar(64) NOT NULL COMMENT '0. awaiting\r\n1. confirmed\r\n2. rescheduled\r\n3. cancelled\r\n4. completed',
  `remarks` varchar(2048) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL,
  `payment_status` text DEFAULT NULL,
  `otp` int(11) DEFAULT NULL,
  `work_started_proof` text DEFAULT NULL,
  `work_completed_proof` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `order_latitude` text DEFAULT NULL,
  `order_longitude` text DEFAULT NULL,
  `promocode_id` int(11) DEFAULT NULL,
  `isRefunded` varchar(255) DEFAULT '0',
  `additional_charges` text DEFAULT NULL,
  `payment_status_of_additional_charge` text DEFAULT NULL,
  `total_additional_charge` text DEFAULT NULL,
  `custom_job_request_id` text DEFAULT NULL,
  `payment_method_of_additional_charge` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_services`
--

CREATE TABLE `order_services` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `service_title` text NOT NULL,
  `tax_percentage` double NOT NULL,
  `tax_amount` double NOT NULL,
  `price` double NOT NULL,
  `discount_price` double NOT NULL,
  `quantity` double NOT NULL,
  `sub_total` double NOT NULL COMMENT 'price X quantity',
  `status` varchar(64) NOT NULL COMMENT '0. awaiting \r\n1. confirmed \r\n2. rescheduled \r\n3. cancelled \r\n4. completed	',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL,
  `custom_job_request_id` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` int(11) NOT NULL,
  `mobile` text NOT NULL,
  `otp` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partners_seo_settings`
--

CREATE TABLE `partners_seo_settings` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'SEO meta title',
  `description` text NOT NULL COMMENT 'SEO meta description',
  `keywords` text NOT NULL COMMENT 'SEO meta keywords',
  `schema_markup` longtext NOT NULL COMMENT 'Schema markup for SEO',
  `image` varchar(255) NOT NULL COMMENT 'SEO image for social media sharing',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores SEO settings for different partners';

-- --------------------------------------------------------

--
-- Table structure for table `partner_bids`
--

CREATE TABLE `partner_bids` (
  `id` int(11) NOT NULL,
  `partner_id` text NOT NULL,
  `counter_price` text NOT NULL,
  `note` text NOT NULL,
  `duration` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL,
  `custom_job_request_id` text NOT NULL,
  `status` text NOT NULL,
  `tax_id` text DEFAULT NULL,
  `tax_amount` text DEFAULT NULL,
  `tax_percentage` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_details`
--

CREATE TABLE `partner_details` (
  `id` int(11) UNSIGNED NOT NULL,
  `partner_id` int(11) NOT NULL COMMENT 'user_id',
  `company_name` varchar(1024) DEFAULT NULL,
  `about` varchar(4096) NOT NULL,
  `national_id` varchar(1024) DEFAULT NULL,
  `address` varchar(1024) DEFAULT NULL,
  `banner` longtext NOT NULL,
  `address_id` varchar(1024) DEFAULT NULL,
  `passport` varchar(1024) DEFAULT NULL,
  `tax_name` varchar(100) DEFAULT NULL,
  `tax_number` varchar(64) DEFAULT NULL,
  `bank_name` varchar(256) DEFAULT NULL,
  `account_number` varchar(256) NOT NULL,
  `account_name` varchar(512) DEFAULT NULL,
  `bank_code` varchar(256) DEFAULT NULL,
  `swift_code` varchar(256) DEFAULT NULL,
  `advance_booking_days` int(11) NOT NULL DEFAULT 0,
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 - individual | 1 - organization ',
  `number_of_members` int(11) NOT NULL,
  `admin_commission` text NOT NULL COMMENT '[ {"category_id" : "commission"},{...} ]',
  `visiting_charges` int(20) NOT NULL,
  `is_approved` tinyint(1) NOT NULL COMMENT '0. Not approved\r\n1. Approved\r\n7. Trashed',
  `service_range` double DEFAULT NULL,
  `ratings` double NOT NULL DEFAULT 0,
  `number_of_ratings` double NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL,
  `other_images` text NOT NULL,
  `long_description` longtext NOT NULL,
  `at_store` int(11) DEFAULT NULL,
  `at_doorstep` int(11) DEFAULT NULL,
  `need_approval_for_the_service` text DEFAULT NULL,
  `chat` varchar(255) NOT NULL DEFAULT '0',
  `pre_chat` varchar(255) NOT NULL DEFAULT '1',
  `custom_job_categories` text DEFAULT NULL,
  `is_accepting_custom_jobs` varchar(255) NOT NULL DEFAULT '1',
  `slug` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_subscriptions`
--

CREATE TABLE `partner_subscriptions` (
  `subscription_id` text NOT NULL,
  `status` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_payment` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `purchase_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `duration` text NOT NULL,
  `price` text NOT NULL,
  `discount_price` text NOT NULL,
  `publish` text DEFAULT NULL,
  `order_type` text NOT NULL,
  `max_order_limit` text DEFAULT NULL,
  `service_type` text NOT NULL,
  `max_service_limit` text DEFAULT NULL,
  `tax_type` text NOT NULL,
  `tax_id` text NOT NULL,
  `is_commision` text NOT NULL,
  `commission_threshold` text DEFAULT NULL,
  `commission_percentage` text DEFAULT NULL,
  `transaction_id` text DEFAULT NULL,
  `tax_percentage` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_timings`
--

CREATE TABLE `partner_timings` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `day` varchar(20) DEFAULT NULL,
  `opening_time` time NOT NULL,
  `closing_time` time NOT NULL,
  `is_open` tinyint(2) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_request`
--

CREATE TABLE `payment_request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` varchar(56) NOT NULL COMMENT 'partner | customer',
  `payment_address` varchar(1024) NOT NULL,
  `amount` double NOT NULL,
  `remarks` varchar(512) DEFAULT NULL,
  `status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '0-pending | 1- approved|2-rejected',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `promo_code` varchar(28) NOT NULL,
  `message` varchar(512) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `no_of_users` int(11) DEFAULT NULL,
  `minimum_order_amount` double DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `discount_type` varchar(32) DEFAULT NULL,
  `max_discount_amount` double DEFAULT NULL,
  `repeat_usage` tinyint(4) NOT NULL,
  `no_of_repeat_usage` int(11) DEFAULT NULL,
  `image` varchar(256) DEFAULT NULL,
  `status` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue_jobs`
--

CREATE TABLE `queue_jobs` (
  `id` bigint(11) UNSIGNED NOT NULL,
  `queue` varchar(64) NOT NULL,
  `payload` text NOT NULL,
  `priority` varchar(64) NOT NULL DEFAULT 'default',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue_jobs_failed`
--

CREATE TABLE `queue_jobs_failed` (
  `id` bigint(11) UNSIGNED NOT NULL,
  `connection` varchar(64) NOT NULL,
  `queue` varchar(64) NOT NULL,
  `payload` text NOT NULL,
  `priority` varchar(64) NOT NULL DEFAULT 'default',
  `exception` text NOT NULL,
  `failed_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reasons_for_report_and_block_chat`
--

CREATE TABLE `reasons_for_report_and_block_chat` (
  `id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `needs_additional_info` text NOT NULL,
  `type` enum('admin','provider') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(1024) NOT NULL,
  `section_type` varchar(1024) NOT NULL,
  `category_ids` varchar(255) DEFAULT NULL,
  `partners_ids` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `status` text NOT NULL,
  `limit` text NOT NULL,
  `rank` int(11) DEFAULT NULL,
  `banner_type` text DEFAULT NULL,
  `banner_url` text DEFAULT NULL,
  `app_banner_image` text DEFAULT NULL,
  `web_banner_image` text DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seo_settings`
--

CREATE TABLE `seo_settings` (
  `id` int(11) NOT NULL,
  `page` varchar(100) NOT NULL COMMENT 'Page identifier (home, about-us, contact-us, etc.)',
  `title` varchar(255) NOT NULL COMMENT 'SEO meta title',
  `description` text NOT NULL COMMENT 'SEO meta description',
  `keywords` text NOT NULL COMMENT 'SEO meta keywords',
  `image` varchar(255) NOT NULL COMMENT 'SEO image for social media sharing',
  `schema_markup` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores SEO settings for different pages';

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'partner_id',
  `category_id` int(11) NOT NULL,
  `tax_type` varchar(20) NOT NULL DEFAULT ' included',
  `tax_id` int(11) NOT NULL DEFAULT 0,
  `tax` double DEFAULT NULL,
  `title` varchar(2048) NOT NULL,
  `slug` varchar(2048) NOT NULL,
  `description` text NOT NULL,
  `tags` text NOT NULL,
  `image` varchar(512) DEFAULT NULL,
  `price` double NOT NULL,
  `discounted_price` double NOT NULL DEFAULT 0,
  `number_of_members_required` int(11) NOT NULL DEFAULT 1 COMMENT 'No of members required to perform service',
  `duration` varchar(128) NOT NULL COMMENT 'in minutes',
  `rating` double NOT NULL DEFAULT 0 COMMENT 'Average rating',
  `number_of_ratings` double NOT NULL DEFAULT 0,
  `on_site_allowed` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 - not allowed | 1 - allowed',
  `is_cancelable` tinyint(1) NOT NULL DEFAULT 0,
  `cancelable_till` varchar(200) NOT NULL,
  `max_quantity_allowed` int(11) NOT NULL DEFAULT 0 COMMENT '0 - unlimited | number - limited qty',
  `is_pay_later_allowed` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 - not allowed | 1 - allowed',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 - deactive | 1 - active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `long_description` longtext NOT NULL,
  `other_images` text NOT NULL,
  `files` text NOT NULL,
  `faqs` text NOT NULL,
  `at_store` text DEFAULT NULL,
  `at_doorstep` text DEFAULT NULL,
  `approved_by_admin` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services_ratings`
--

CREATE TABLE `services_ratings` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `rating` double NOT NULL,
  `comment` varchar(4096) DEFAULT NULL,
  `images` text DEFAULT NULL COMMENT 'multiple images( comma separated )',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `custom_job_request_id` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services_seo_settings`
--

CREATE TABLE `services_seo_settings` (
  `id` int(11) NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'SEO meta title',
  `description` text NOT NULL COMMENT 'SEO meta description',
  `keywords` text NOT NULL COMMENT 'SEO meta keywords',
  `schema_markup` longtext NOT NULL COMMENT 'Schema markup for SEO',
  `image` varchar(255) NOT NULL COMMENT 'SEO image for social media sharing',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores SEO settings for different services';

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `variable` varchar(35) NOT NULL,
  `value` mediumtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `variable`, `value`, `created_at`, `updated_at`) VALUES
(1, 'test', '{\"val\" : \"this\"}', '2022-04-21 05:59:17', '0000-00-00 00:00:00'),
(2, 'languages', '{\"ar-XA\":\"Arabic [Switzerland]\",\"bn-IN\":\"Bengali [India]\",\"en-GB\":\"English [United Kingdom]\",\"fr-CA\":\"French [Canada]\",\"en-US\":\"English [United States of America]\",\"es-ES\":\"Spanish \\/ Castilian [Spain]\",\"fi-FI\":\"Finnish [Finland]\",\"gu-IN\":\"Gujarati [India]\",\"ja-JP\":\"Japanese (ja) [Japan]\",\"kn-IN\":\"Kannada [India]\",\"ml-IN\":\"Malayalam [India]\",\"sv-SE\":\"Swedish [Sweden]\",\"ta-IN\":\"Tamil [India]\",\"tr-TR\":\"Turkish [Turkey]\",\"ms-MY\":\"Malay [Malaysia]\",\"pa-IN\":\"Punjabi [India]\",\"cs-CZ\":\"Czech [Czech Republic]\",\"de-DE\":\"German [Germany]\",\"en-AU\":\"English [Australia]\",\"en-IN\":\"English [India]\",\"es-US\":\"Spanish \\/ Castilian [United States of America]\",\"fr-FR\":\"French [France, French Republic]\",\"hi-IN\":\"Hindi [India]\",\"id-ID\":\"Indonesian [Indonesia]\",\"it-IT\":\"Italian [Italy]\",\"ko-KR\":\"Korean [Korea]\",\"ru-RU\":\"Russian [Russian Federation]\",\"uk-UA\":\"Ukrainian [Ukraine]\",\"cmn-CN\":\"Mandarin Chinese [China]\",\"cmn-TW\":\"Mandarin Chinese [Taiwan]\",\"da-DK\":\"Danish [Denmark]\",\"el-GR\":\"Greek \\/ Modern [Greece]\",\"fil-PH\":\"Filipino \\/ Pilipino [Philippines]\",\"hu-HU\":\"Hungarian [Hungary]\",\"nb-NO\":\"Norwegian Bokm\\u00e5l [Norway]\",\"nl-BE\":\"Dutch [Belgium]\",\"nl-NL\":\"Dutch [Netherlands the]\",\"pt-PT\":\"Portuguese [Portugal, Portuguese Republic]\",\"sk-SK\":\"Slovak [Slovakia (Slovak Republic)]\",\"vi-VN\":\"Vietnamese [Vietnam]\",\"pl-PL\":\"Polish [Poland]\",\"pt-BR\":\"Portuguese [Brazil]\",\"ca-ES\":\"Catalan; Valencian [Spain]\",\"yue-HK\":\"Yue Chinese [Hong Kong]\",\"af-ZA\":\"Afrikaans [South Africa]\",\"bg-BG\":\"Bulgarian [Bulgaria]\",\"lv-LV\":\"Latvian [Latvia]\",\"ro-RO\":\"Romanian \\/ Moldavian \\/ Moldovan [Romania]\",\"sr-RS\":\"Serbian [Serbia]\",\"th-TH\":\"Thai [Thailand]\",\"te-IN\":\"Telugu [India]\",\"is-IS\":\"Icelandic [Iceland]\",\"cy-GB\":\"Welsh [United Kingdom]\",\"en-GB-WLS\":\"English [united kingdom]\",\"es-MX\":\"Spanish \\/ Castilian [Mexico]\",\"en-NZ\":\"English [New Zealand]\",\"en-ZA\":\"English [South Africa]\",\"ar-EG\":\"Arabic [Egypt]\",\"ar-SA\":\"Arabic [Saudi Arabia]\",\"de-AT\":\"German [Austria]\",\"de-CH\":\"German [Switzerland, Swiss Confederation]\",\"en-CA\":\"English [Canada]\",\"en-HK\":\"English [Hong Kong]\",\"en-IE\":\"English [Ireland]\",\"en-PH\":\"English [Philippines]\",\"en-SG\":\"English [Singapore]\",\"es-AR\":\"Spanish \\/ Castilian [Argentina]\",\"es-CO\":\"Spanish \\/ Castilian [Colombia]\",\"et-EE\":\"Estonian [Estonia]\",\"fr-BE\":\"French [Belgium]\",\"fr-CH\":\"French [Switzerland, Swiss Confederation]\",\"ga-IE\":\"Irish [Ireland]\",\"he-IL\":\"Hebrew (modern) [Israel]\",\"hr-HR\":\"Croatian [Croatia]\",\"lt-LT\":\"Lithuanian [Lithuania]\",\"mr-IN\":\"Marathi [India]\",\"mt-MT\":\"Maltese [Malta]\",\"sl-SI\":\"Slovene [Slovenia]\",\"sw-KE\":\"Swahili [Kenya]\",\"ur-PK\":\"Urdu [Pakistan]\",\"zh-CN\":\"Chinese [China]\",\"zh-HK\":\"Chinese [Hong Kong]\",\"zh-TW\":\"Chinese [Taiwan]\",\"es-LA\":\"Spanish \\/ Castilian [Lao]\",\"ar-MS\":\"Arabic [Montserrat]\"}', '2022-04-21 05:59:17', '0000-00-00 00:00:00'),
(13, 'payment_gateways_settings', '{\n  \"razorpayApiStatus\": \"disable\",\n  \"razorpay_mode\": \"test\",\n  \"razorpay_currency\": \"INR\",\n  \"razorpay_secret\": \"your_razorpay_secret\",\n  \"razorpay_key\": \"your_razorpay_key\",\n  \"endpoint\": \"your_razorpay_endpoint\",\n  \"paypal_client_key\": \"your_paypal_client_key\",\n  \"paypal_currency_code\": \"USD\",\n  \"paypal_secret_key\": \"1235\",\n  \"paypal_business_email\": \"test@test.com\",\n  \"paypal_mode\": \"sandbox\",\n  \"paystack_status\": \"disable\",\n  \"paystack_mode\": \"test\",\n  \"paystack_currency\": \"NGN\",\n  \"paystack_secret\": \"your_paystack_secret\",\n  \"paystack_key\": \"your_paystack_key\",\n  \"stripe_status\": \"enable\",\n  \"stripe_mode\": \"test\",\n  \"stripe_currency\": \"INR\",\n  \"stripe_publishable_key\": \"your_stripe_publishable_key\",\n  \"stripe_webhook_secret_key\": \"your_stripe_webhook_secret_key\",\n  \"flutterwave_status\": \"enable\",\n  \"flutterwave_currency_code\": \"TZS\",\n  \"flutterwave_public_key\": \"your_flutterwave_public_key\",\n  \"flutterwave_secret_key\": \"your_flutterwave_secret_key\",\n  \"flutterwave_encryption_key\": \"your_flutterwave_encryption_key\",\n  \"flutterwave_webhook_secret_key\": \"your_flutterwave_webhook_secret_key\",\n  \"flutterwave_endpoint\": \"your_flutterwave_endpoint\",\n  \"stripe_secret_key\": \"your_stripe_secret_key\",\n  \"cod_setting\": \"1\",\n  \"payment_gateway_setting\": \"1\",\n  \"flutterwave_website_url\": \"your_website_url\",\n  \"paypal_website_url\": \"your_website_url\",\n  \"xendit_status\": \"enable\",\n  \"xendit_currency\": \"IDR\",\n  \"xendit_api_key\": \"your_xendit_api_key\",\n  \"xendit_endpoint\": \"your_xendit_endpoint\",\n  \"xendit_website_url\": \"your_website_url\",\n  \"xendit_webhook_verification_token\": \"your_xendit_webhook_verification_token\"\n}', '2025-08-13 12:52:23', '0000-00-00 00:00:00'),
(15, 'terms_conditions', '{\"terms_conditions\":\"<p>Partner Terms and Conditions Here<\\/p>\"}', '2022-04-29 06:48:00', '0000-00-00 00:00:00'),
(16, 'privacy_policy', '{\"privacy_policy\":\"<h1>Privacy Policy for WRTeam<\\/h1>\\r\\n<p>At eDemand Provider, accessible from https:\\/\\/edemand.wrteam.me\\/partner\\/login, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by eDemand Provider and how we use it.<\\/p>\\r\\n<p>If you have additional questions or require more information about our Privacy Policy, do not hesitate to contact us.<\\/p>\\r\\n<p>This Privacy Policy applies only to our online activities and is valid for visitors to our website with regards to the information that they shared and\\/or collect in eDemand Provider. This policy is not applicable to any information collected offline or via channels other than this website.<\\/p>\\r\\n<p>Consent<\\/p>\\r\\n<p>By using our website, you hereby consent to our Privacy Policy and agree to its terms.<\\/p>\\r\\n<h2>Information we collect<\\/h2>\\r\\n<p>The personal information that you are asked to provide, and the reasons why you are asked to provide it, will be made clear to you at the point we ask you to provide your personal information.<\\/p>\\r\\n<p>If you contact us directly, we may receive additional information about you such as your name, email address, phone number, the contents of the message and\\/or attachments you may send us, and any other information you may choose to provide.<\\/p>\\r\\n<p>When you register for an Account, we may ask for your contact information, including items such as name, company name, address, email address, and telephone number.<\\/p>\\r\\n<h2>How we use your information<\\/h2>\\r\\n<p>We use the information we collect in various ways, including to:<\\/p>\\r\\n<ul>\\r\\n<li>Provide, operate, and maintain our website<\\/li>\\r\\n<li>Improve, personalize, and expand our website<\\/li>\\r\\n<li>Understand and analyze how you use our website<\\/li>\\r\\n<li>Develop new products, services, features, and functionality<\\/li>\\r\\n<li>Communicate with you, either directly or through one of our partners, including for customer service, to provide you with updates and other information relating to the website, and for marketing and promotional purposes<\\/li>\\r\\n<li>Send you emails<\\/li>\\r\\n<li>Find and prevent fraud<\\/li>\\r\\n<\\/ul>\\r\\n<h2>Log Files<\\/h2>\\r\\n<p>eDemand Provider follows a standard procedure of using log files. These files log visitors when they visit websites. All hosting companies do this and a part of hosting services\' analytics. The information collected by log files include internet protocol (IP) addresses, browser type, Internet Service Provider (ISP), date and time stamp, referring\\/exit pages, and possibly the number of clicks. These are not linked to any information that is personally identifiable. The purpose of the information is for analyzing trends, administering the site, tracking users\' movement on the website, and gathering demographic information.<\\/p>\\r\\n<h2>Advertising Partners Privacy Policies<\\/h2>\\r\\n<p>You may consult this list to find the Privacy Policy for each of the advertising partners of eDemand Provider.<\\/p>\\r\\n<p>Third-party ad servers or ad networks uses technologies like cookies, JavaScript, or Web Beacons that are used in their respective advertisements and links that appear on eDemand Provider, which are sent directly to users\' browser. They automatically receive your IP address when this occurs. These technologies are used to measure the effectiveness of their advertising campaigns and\\/or to personalize the advertising content that you see on websites that you visit.<\\/p>\\r\\n<p>Note that eDemand Provider has no access to or control over these cookies that are used by third-party advertisers.<\\/p>\\r\\n<h2>Third Party Privacy Policies<\\/h2>\\r\\n<p>eDemand Provider\'s Privacy Policy does not apply to other advertisers or websites. Thus, we are advising you to consult the respective Privacy Policies of these third-party ad servers for more detailed information. It may include their practices and instructions about how to opt-out of certain options.<\\/p>\\r\\n<p>You can choose to disable cookies through your individual browser options. To know more detailed information about cookie management with specific web browsers, it can be found at the browsers\' respective websites.<\\/p>\\r\\n<h2>CCPA Privacy Rights (Do Not Sell My Personal Information)<\\/h2>\\r\\n<p>Under the CCPA, among other rights, California consumers have the right to:<\\/p>\\r\\n<p>Request that a business that collects a consumer\'s personal data disclose the categories and specific pieces of personal data that a business has collected about consumers.<\\/p>\\r\\n<p>Request that a business delete any personal data about the consumer that a business has collected.<\\/p>\\r\\n<p>Request that a business that sells a consumer\'s personal data, not sell the consumer\'s personal data.<\\/p>\\r\\n<p>If you make a request, we have one month to respond to you. If you would like to exercise any of these rights, please contact us.<\\/p>\\r\\n<h2>GDPR Data Protection Rights<\\/h2>\\r\\n<p>We would like to make sure you are fully aware of all of your data protection rights. Every user is entitled to the following:<\\/p>\\r\\n<p>The right to access &ndash; You have the right to request copies of your personal data. We may charge you a small fee for this service.<\\/p>\\r\\n<p>The right to rectification &ndash; You have the right to request that we correct any information you believe is inaccurate. You also have the right to request that we complete the information you believe is incomplete.<\\/p>\\r\\n<p>The right to erasure &ndash; You have the right to request that we erase your personal data, under certain conditions.<\\/p>\\r\\n<p>The right to restrict processing &ndash; You have the right to request that we restrict the processing of your personal data, under certain conditions.<\\/p>\\r\\n<p>The right to object to processing &ndash; You have the right to object to our processing of your personal data, under certain conditions.<\\/p>\\r\\n<p>The right to data portability &ndash; You have the right to request that we transfer the data that we have collected to another organization, or directly to you, under certain conditions.<\\/p>\\r\\n<p>If you make a request, we have one month to respond to you. If you would like to exercise any of these rights, please contact us.<\\/p>\\r\\n<h2>Children\'s Information<\\/h2>\\r\\n<p>Another part of our priority is adding protection for children while using the internet. We encourage parents and guardians to observe, participate in, and\\/or monitor and guide their online activity.<\\/p>\\r\\n<p>eDemand Provider does not knowingly collect any Personal Identifiable Information from children under the age of 13. If you think that your child provided this kind of information on our website, we strongly encourage you to contact us immediately and we will do our best efforts to promptly remove such information from our records.<\\/p>\"}', '2022-11-29 09:52:41', '0000-00-00 00:00:00'),
(17, 'about_us', '{\"about_us\":\"this is about us page information\"}', '2022-11-22 06:38:16', '0000-00-00 00:00:00'),
(18, 'general_settings', '{\"system_timezone_gmt\":\"+05:30\",\"system_timezone\":\"Asia\\/Kolkata\",\"max_serviceable_distance\":\"50\",\"distance_unit\":\"km\",\"primary_color\":\"#007bff\",\"secondary_color\":\"#fcfcfc\",\"booking_auto_cancle_duration\":\"30\",\"image_compression_preference\":\"0\",\"image_compression_quality\":\"0\",\"company_title\":\"eDemand - On Demand Services\",\"support_email\":\"info@eDemand.in\",\"phone\":\"9876543210\",\"support_hours\":\"09:00 AM to 06:00PM IST\",\"copyright_details\":\"Copyright@2024 eDemand. All rights reserved.\",\"company_map_location\":\"https:\\/\\/www.google.com\\/maps\\/embed?pb=!1m18!1m12!1m3!1d7333.306105038601!2d69.62475796805171!3d23.219311325645396!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39511e44b301f483%3A0xe8396658a3fed5b!2sMirjapar%2C%20Bhuj%2C%20Gujarat%20370040!5e0!3m2!1sen!2sin!4v1711532768938!5m2!1sen!2sin\",\"address\":\"#123, Time Square, Bhuj - India\",\"short_description\":\"<p>eDemand- On Demand services<\\/p>\",\"maxFilesOrImagesInOneMessage\":\"5\",\"maxFileSizeInMBCanBeSent\":\"80\",\"maxCharactersInATextMessage\":\"250\",\"allow_pre_booking_chat\":\"1\",\"allow_post_booking_chat\":\"1\",\"otp_system\":\"1\",\"authentication_mode\":\"firebase\",\"file_manager\":\"local_server\",\"file_transfer_process\":\"0\",\"aws_access_key_id\":\"your aws access key id\",\"aws_secret_access_key\":\"you aws secret access key\",\"aws_default_region\":\"us-east-1\",\"aws_bucket\":\"your aws bucket\",\"aws_url\":\"your_aws_url\",\"schema_for_deeplink\":\"your schema\",\"passport_verification_status\":\"1\",\"passport_required_status\":\"1\",\"national_id_verification_status\":\"1\",\"national_id_required_status\":\"1\",\"address_id_verification_status\":\"1\",\"address_id_required_status\":\"1\",\"favicon\":\"1657775760_29d5c9510f319bcff33f.svg\",\"half_logo\":\"1657775760_f730e7b07a5cda36133e.svg\",\"logo\":\"1655699574_7fd61254c6132ebfd8ce.svg\",\"partner_favicon\":\"1655699528_c19e479401407f3a416d.svg\",\"partner_half_logo\":\"1657775933_c37b2c2a81820814b648.svg\",\"partner_logo\":\"edemand_provider_logo.svg\",\"login_image\":\"\",\"currency\":\"$\",\"country_currency_code\":\"\",\"decimal_point\":\"0\",\"customer_current_version_ios_app\":\"1.0.0\",\"customer_compulsary_update_force_update\":\"0\",\"provider_current_version_android_app\":\"1.0.0\",\"provider_current_version_ios_app\":\"1.0.0\",\"provider_compulsary_update_force_update\":\"0\",\"customer_app_maintenance_schedule_date\":\"\",\"message_for_customer_application\":\"\",\"customer_app_maintenance_mode\":\"0\",\"provider_app_maintenance_schedule_date\":\"\",\"message_for_provider_application\":\"\",\"provider_app_maintenance_mode\":\"0\",\"provider_location_in_provider_details\":\"0\",\"support_name\":\"eDemand\",\"primary_shadow\":\"#ffffff\",\"customer_playstore_url\":\"\",\"customer_appstore_url\":\"\",\"provider_playstore_url\":\"\",\"provider_appstore_url\":\"\",\"android_google_interstitial_id\":\"your_android_google_interstitial_id\",\"android_google_banner_id\":\"your_android_google_banner_id\",\"ios_google_interstitial_id\":\"your_ios_google_interstitial_id\",\"ios_google_banner_id\":\"your_ios_google_banner_id\",\"android_google_ads_status\":\"0\",\"ios_google_ads_status\":\"0\",\"customer_current_version_android_app\":\"1.0.0\"}', '2025-08-13 13:55:47', '0000-00-00 00:00:00'),
(19, 'email_settings', '{\"mailProtocol\":\"SMTP\",\"smtpHost\":\"smtp.googlemail.com\",\"smtpUsername\":\"yourmail@gmail.com\",\"smtpPassword\":\"yourpassword\",\"smtpPort\":\"465\",\"smtpEncryption\":\"ssl\",\"mailType\":\"html\",\"update\":\"Update\"}', '2022-11-23 09:44:35', '0000-00-00 00:00:00'),
(21, 'refund_policy', '{\"refund_policy\":\"\"}', '2022-04-21 05:59:17', '0000-00-00 00:00:00'),
(22, 'app_settings', '{\"maintenance_date\":\"2022-11-15\",\"start_time\":\"11:01\",\"end_time\":\"15:03\",\"maintenance_mode\":\"on\"}', '2022-11-01 06:29:54', '0000-00-00 00:00:00'),
(23, 'customer_terms_conditions', '{\"customer_terms_conditions\":\"<p>Customer Terms and Condtions here<\\/p>\"}', '2022-04-29 06:41:44', NULL),
(24, 'customer_privacy_policy', '{\"customer_privacy_policy\":\"<h1>Privacy Policy for eDemand<\\/h1>\\r\\n<p>At https:\\/\\/edemand.wrteam.me\\/admin, accessible from https:\\/\\/edemand.wrteam.me\\/admin, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by https:\\/\\/edemand.wrteam.me\\/admin and how we use it.<\\/p>\\r\\n<p>If you have additional questions or require more information about our Privacy Policy, do not hesitate to contact us.<\\/p>\\r\\n<p>This Privacy Policy applies only to our online activities and is valid for visitors to our website with regards to the information that they shared and\\/or collect in https:\\/\\/edemand.wrteam.me\\/admin. This policy is not applicable to any information collected offline or via channels other than this website. Our Privacy Policy was created with the help of the <a href=\\\"https:\\/\\/www.privacypolicygenerator.info\\/\\\">Free Privacy Policy Generator<\\/a>.<\\/p>\\r\\n<h2>Consent<\\/h2>\\r\\n<p>By using our website, you hereby consent to our Privacy Policy and agree to its terms.<\\/p>\\r\\n<h2>Information we collect<\\/h2>\\r\\n<p>The personal information that you are asked to provide, and the reasons why you are asked to provide it, will be made clear to you at the point we ask you to provide your personal information.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>If you contact us directly, we may receive additional information about you such as your name, email address, phone number, the contents of the message and\\/or attachments you may send us, and any other information you may choose to provide.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>When you register for an Account, we may ask for your contact information, including items such as name, company name, address, email address, and telephone number.<\\/p>\\r\\n<h2>How we use your information<\\/h2>\\r\\n<p>We use the information we collect in various ways, including to:<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<ul>\\r\\n<ul>\\r\\n<li>Provide, operate, and maintain our website<\\/li>\\r\\n<\\/ul>\\r\\n<\\/ul>\\r\\n<p>&nbsp;<\\/p>\\r\\n<ul>\\r\\n<ul>\\r\\n<li>Improve, personalize, and expand our website<\\/li>\\r\\n<\\/ul>\\r\\n<\\/ul>\\r\\n<p>&nbsp;<\\/p>\\r\\n<ul>\\r\\n<ul>\\r\\n<li>Understand and analyze how you use our website<\\/li>\\r\\n<\\/ul>\\r\\n<\\/ul>\\r\\n<p>&nbsp;<\\/p>\\r\\n<ul>\\r\\n<ul>\\r\\n<li>Develop new products, services, features, and functionality<\\/li>\\r\\n<\\/ul>\\r\\n<\\/ul>\\r\\n<p>&nbsp;<\\/p>\\r\\n<ul>\\r\\n<ul>\\r\\n<li>Communicate with you, either directly or through one of our partners, including for customer service, to provide you with updates and other information relating to the website, and for marketing and promotional purposes<\\/li>\\r\\n<\\/ul>\\r\\n<\\/ul>\\r\\n<p>&nbsp;<\\/p>\\r\\n<ul>\\r\\n<ul>\\r\\n<li>Send you emails<\\/li>\\r\\n<\\/ul>\\r\\n<\\/ul>\\r\\n<p>&nbsp;<\\/p>\\r\\n<ul>\\r\\n<ul>\\r\\n<li>Find and prevent fraud<\\/li>\\r\\n<\\/ul>\\r\\n<\\/ul>\\r\\n<p>&nbsp;<\\/p>\\r\\n<h2>Log Files<\\/h2>\\r\\n<p>https:\\/\\/edemand.wrteam.me\\/admin follows a standard procedure of using log files. These files log visitors when they visit websites. All hosting companies do this and a part of hosting services\' analytics. The information collected by log files include internet protocol (IP) addresses, browser type, Internet Service Provider (ISP), date and time stamp, referring\\/exit pages, and possibly the number of clicks. These are not linked to any information that is personally identifiable. The purpose of the information is for analyzing trends, administering the site, tracking users\' movement on the website, and gathering demographic information.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<h2>Advertising Partners Privacy Policies<\\/h2>\\r\\n<p>You may consult this list to find the Privacy Policy for each of the advertising partners of https:\\/\\/edemand.wrteam.me\\/admin.<\\/p>\\r\\n<p>Third-party ad servers or ad networks uses technologies like cookies, JavaScript, or Web Beacons that are used in their respective advertisements and links that appear on https:\\/\\/edemand.wrteam.me\\/admin, which are sent directly to users\' browser. They automatically receive your IP address when this occurs. These technologies are used to measure the effectiveness of their advertising campaigns and\\/or to personalize the advertising content that you see on websites that you visit.<\\/p>\\r\\n<p>Note that https:\\/\\/edemand.wrteam.me\\/admin has no access to or control over these cookies that are used by third-party advertisers.<\\/p>\\r\\n<h2>Third Party Privacy Policies<\\/h2>\\r\\n<p>https:\\/\\/edemand.wrteam.me\\/admin\'s Privacy Policy does not apply to other advertisers or websites. Thus, we are advising you to consult the respective Privacy Policies of these third-party ad servers for more detailed information. It may include their practices and instructions about how to opt-out of certain options.<\\/p>\\r\\n<p>You can choose to disable cookies through your individual browser options. To know more detailed information about cookie management with specific web browsers, it can be found at the browsers\' respective websites.<\\/p>\\r\\n<h2>CCPA Privacy Rights (Do Not Sell My Personal Information)<\\/h2>\\r\\n<p>Under the CCPA, among other rights, California consumers have the right to:<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>Request that a business that collects a consumer\'s personal data disclose the categories and specific pieces of personal data that a business has collected about consumers.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>Request that a business delete any personal data about the consumer that a business has collected.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>Request that a business that sells a consumer\'s personal data, not sell the consumer\'s personal data.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>If you make a request, we have one month to respond to you. If you would like to exercise any of these rights, please contact us.<\\/p>\\r\\n<h2>GDPR Data Protection Rights<\\/h2>\\r\\n<p>We would like to make sure you are fully aware of all of your data protection rights. Every user is entitled to the following:<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>The right to access &ndash; You have the right to request copies of your personal data. We may charge you a small fee for this service.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>The right to rectification &ndash; You have the right to request that we correct any information you believe is inaccurate. You also have the right to request that we complete the information you believe is incomplete.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>The right to erasure &ndash; You have the right to request that we erase your personal data, under certain conditions.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>The right to restrict processing &ndash; You have the right to request that we restrict the processing of your personal data, under certain conditions.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>The right to object to processing &ndash; You have the right to object to our processing of your personal data, under certain conditions.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>The right to data portability &ndash; You have the right to request that we transfer the data that we have collected to another organization, or directly to you, under certain conditions.<\\/p>\\r\\n<p>&nbsp;<\\/p>\\r\\n<p>If you make a request, we have one month to respond to you. If you would like to exercise any of these rights, please contact us.<\\/p>\\r\\n<h2>Children\'s Information<\\/h2>\\r\\n<p>Another part of our priority is adding protection for children while using the internet. We encourage parents and guardians to observe, participate in, and\\/or monitor and guide their online activity.<\\/p>\\r\\n<p>https:\\/\\/edemand.wrteam.me\\/admin does not knowingly collect any Personal Identifiable Information from children under the age of 13. If you think that your child provided this kind of information on our website, we strongly encourage you to contact us immediately and we will do our best efforts to promptly remove such information from our records.<\\/p>\"}', '2022-11-16 11:52:29', NULL),
(25, 'country_codes_old', '{\r\n  \n  \"countries\": [\n   \r\n  {\n      \"code\": \"+7 840\",\n      \"name\": \"Abkhazia\"\n    },\n \r\n  {\n      \"code\": \"+93\",\n      \"name\": \"Afghanistan\"\n  },\n\r\n  {\n      \"code\": \"+355\",\n      \"name\": \"Albania\"\n    },\n \r\n      {\n      \"code\": \"+213\",\n      \"name\": \"Algeria\"\n    },\n    {\n      \"code\": \"+1 684\",\n      \"name\": \"American Samoa\"\n    },\n    {\n      \"code\": \"+376\",\n      \"name\": \"Andorra\"\n    },\n    {\n      \"code\": \"+244\",\n      \"name\": \"Angola\"\n    },\n    {\n      \"code\": \"+1 264\",\n      \"name\": \"Anguilla\"\n    },\n    {\n      \"code\": \"+1 268\",\n      \"name\": \"Antigua and Barbuda\"\n    },\n    {\n      \"code\": \"+54\",\n      \"name\": \"Argentina\"\n    },\n    {\n      \"code\": \"+374\",\n      \"name\": \"Armenia\"\n    },\n    {\n      \"code\": \"+297\",\n      \"name\": \"Aruba\"\n    },\n    {\n      \"code\": \"+247\",\n      \"name\": \"Ascension\"\n    },\n    {\n      \"code\": \"+61\",\n      \"name\": \"Australia\"\n    },\n    {\n      \"code\": \"+672\",\n      \"name\": \"Australian External Territories\"\n    },\n    {\n      \"code\": \"+43\",\n      \"name\": \"Austria\"\n    },\n    {\n      \"code\": \"+994\",\n      \"name\": \"Azerbaijan\"\n    },\n    {\n      \"code\": \"+1 242\",\n      \"name\": \"Bahamas\"\n    },\n    {\n      \"code\": \"+973\",\n      \"name\": \"Bahrain\"\n    },\n    {\n      \"code\": \"+880\",\n      \"name\": \"Bangladesh\"\n    },\n    {\n      \"code\": \"+1 246\",\n      \"name\": \"Barbados\"\n    },\n    {\n      \"code\": \"+1 268\",\n      \"name\": \"Barbuda\"\n    },\n    {\n      \"code\": \"+375\",\n      \"name\": \"Belarus\"\n    },\n    {\n      \"code\": \"+32\",\n      \"name\": \"Belgium\"\n    },\n    {\n      \"code\": \"+501\",\n      \"name\": \"Belize\"\n    },\n    {\n      \"code\": \"+229\",\n      \"name\": \"Benin\"\n    },\n    {\n      \"code\": \"+1 441\",\n      \"name\": \"Bermuda\"\n    },\n    {\n      \"code\": \"+975\",\n      \"name\": \"Bhutan\"\n    },\n    {\n      \"code\": \"+591\",\n      \"name\": \"Bolivia\"\n    },\n    {\n      \"code\": \"+387\",\n      \"name\": \"Bosnia and Herzegovina\"\n    },\n    {\n      \"code\": \"+267\",\n      \"name\": \"Botswana\"\n    },\n    {\n      \"code\": \"+55\",\n      \"name\": \"Brazil\"\n    },\n    {\n      \"code\": \"+246\",\n      \"name\": \"British Indian Ocean Territory\"\n    },\n    {\n      \"code\": \"+1 284\",\n      \"name\": \"British Virgin Islands\"\n    },\n    {\n      \"code\": \"+673\",\n      \"name\": \"Brunei\"\n    },\n    {\n      \"code\": \"+359\",\n      \"name\": \"Bulgaria\"\n    },\n    {\n      \"code\": \"+226\",\n      \"name\": \"Burkina Faso\"\n    },\n    {\n      \"code\": \"+257\",\n      \"name\": \"Burundi\"\n    },\n    {\n      \"code\": \"+855\",\n      \"name\": \"Cambodia\"\n    },\n    {\n      \"code\": \"+237\",\n      \"name\": \"Cameroon\"\n    },\n    {\n      \"code\": \"+1\",\n      \"name\": \"Canada\"\n    },\n    {\n      \"code\": \"+238\",\n      \"name\": \"Cape Verde\"\n    },\n    {\n      \"code\": \"+ 345\",\n      \"name\": \"Cayman Islands\"\n    },\n    {\n      \"code\": \"+236\",\n      \"name\": \"Central African Republic\"\n    },\n    {\n      \"code\": \"+235\",\n      \"name\": \"Chad\"\n    },\n    {\n      \"code\": \"+56\",\n      \"name\": \"Chile\"\n    },\n    {\n      \"code\": \"+86\",\n      \"name\": \"China\"\n    },\n    {\n      \"code\": \"+61\",\n      \"name\": \"Christmas Island\"\n    },\n    {\n      \"code\": \"+61\",\n      \"name\": \"Cocos-Keeling Islands\"\n    },\n    {\n      \"code\": \"+57\",\n      \"name\": \"Colombia\"\n    },\n    {\n      \"code\": \"+269\",\n      \"name\": \"Comoros\"\n    },\n    {\n      \"code\": \"+242\",\n      \"name\": \"Congo\"\n    },\n    {\n      \"code\": \"+243\",\n      \"name\": \"Congo, Dem. Rep. of (Zaire)\"\n    },\n    {\n      \"code\": \"+682\",\n      \"name\": \"Cook Islands\"\n    },\n    {\n      \"code\": \"+506\",\n      \"name\": \"Costa Rica\"\n    },\n    {\n      \"code\": \"+385\",\n      \"name\": \"Croatia\"\n    },\n    {\n      \"code\": \"+53\",\n      \"name\": \"Cuba\"\n    },\n    {\n      \"code\": \"+599\",\n      \"name\": \"Curacao\"\n    },\n    {\n      \"code\": \"+537\",\n      \"name\": \"Cyprus\"\n    },\n    {\n      \"code\": \"+420\",\n      \"name\": \"Czech Republic\"\n    },\n    {\n      \"code\": \"+45\",\n      \"name\": \"Denmark\"\n    },\n    {\n      \"code\": \"+246\",\n      \"name\": \"Diego Garcia\"\n    },\n    {\n      \"code\": \"+253\",\n      \"name\": \"Djibouti\"\n    },\n    {\n      \"code\": \"+1 767\",\n      \"name\": \"Dominica\"\n    },\n    {\n      \"code\": \"+1 809\",\n      \"name\": \"Dominican Republic\"\n    },\n    {\n      \"code\": \"+670\",\n      \"name\": \"East Timor\"\n    },\n    {\n      \"code\": \"+56\",\n      \"name\": \"Easter Island\"\n    },\n    {\n      \"code\": \"+593\",\n      \"name\": \"Ecuador\"\n    },\n    {\n      \"code\": \"+20\",\n      \"name\": \"Egypt\"\n    },\n    {\n      \"code\": \"+503\",\n      \"name\": \"El Salvador\"\n    },\n    {\n      \"code\": \"+240\",\n      \"name\": \"Equatorial Guinea\"\n    },\n    {\n      \"code\": \"+291\",\n      \"name\": \"Eritrea\"\n    },\n    {\n      \"code\": \"+372\",\n      \"name\": \"Estonia\"\n    },\n    {\n      \"code\": \"+251\",\n      \"name\": \"Ethiopia\"\n    },\n    {\n      \"code\": \"+500\",\n      \"name\": \"Falkland Islands\"\n    },\n    {\n      \"code\": \"+298\",\n      \"name\": \"Faroe Islands\"\n    },\n    {\n      \"code\": \"+679\",\n      \"name\": \"Fiji\"\n    },\n    {\n      \"code\": \"+358\",\n      \"name\": \"Finland\"\n    },\n    {\n      \"code\": \"+33\",\n      \"name\": \"France\"\n    },\n    {\n      \"code\": \"+596\",\n      \"name\": \"French Antilles\"\n    },\n    {\n      \"code\": \"+594\",\n      \"name\": \"French Guiana\"\n    },\n    {\n      \"code\": \"+689\",\n      \"name\": \"French Polynesia\"\n    },\n    {\n      \"code\": \"+241\",\n      \"name\": \"Gabon\"\n    },\n    {\n      \"code\": \"+220\",\n      \"name\": \"Gambia\"\n    },\n    {\n      \"code\": \"+995\",\n      \"name\": \"Georgia\"\n    },\n    {\n      \"code\": \"+49\",\n      \"name\": \"Germany\"\n    },\n    {\n      \"code\": \"+233\",\n      \"name\": \"Ghana\"\n    },\n    {\n      \"code\": \"+350\",\n      \"name\": \"Gibraltar\"\n    },\n    {\n      \"code\": \"+30\",\n      \"name\": \"Greece\"\n    },\n    {\n      \"code\": \"+299\",\n      \"name\": \"Greenland\"\n    },\n    {\n      \"code\": \"+1 473\",\n      \"name\": \"Grenada\"\n    },\n    {\n      \"code\": \"+590\",\n      \"name\": \"Guadeloupe\"\n    },\n    {\n      \"code\": \"+1 671\",\n      \"name\": \"Guam\"\n    },\n    {\n      \"code\": \"+502\",\n      \"name\": \"Guatemala\"\n    },\n    {\n      \"code\": \"+224\",\n      \"name\": \"Guinea\"\n    },\n    {\n      \"code\": \"+245\",\n      \"name\": \"Guinea-Bissau\"\n    },\n    {\n      \"code\": \"+595\",\n      \"name\": \"Guyana\"\n    },\n    {\n      \"code\": \"+509\",\n      \"name\": \"Haiti\"\n    },\n    {\n      \"code\": \"+504\",\n      \"name\": \"Honduras\"\n    },\n    {\n      \"code\": \"+852\",\n      \"name\": \"Hong Kong SAR China\"\n    },\n    {\n      \"code\": \"+36\",\n      \"name\": \"Hungary\"\n    },\n    {\n      \"code\": \"+354\",\n      \"name\": \"Iceland\"\n    },\n    {\n      \"code\": \"+91\",\n      \"name\": \"India\"\n    },\n    {\n      \"code\": \"+62\",\n      \"name\": \"Indonesia\"\n    },\n    {\n      \"code\": \"+98\",\n      \"name\": \"Iran\"\n    },\n    {\n      \"code\": \"+964\",\n      \"name\": \"Iraq\"\n    },\n    {\n      \"code\": \"+353\",\n      \"name\": \"Ireland\"\n    },\n    {\n      \"code\": \"+972\",\n      \"name\": \"Israel\"\n    },\n    {\n      \"code\": \"+39\",\n      \"name\": \"Italy\"\n    },\n    {\n      \"code\": \"+225\",\n      \"name\": \"Ivory Coast\"\n    },\n    {\n      \"code\": \"+1 876\",\n      \"name\": \"Jamaica\"\n    },\n    {\n      \"code\": \"+81\",\n      \"name\": \"Japan\"\n    },\n    {\n      \"code\": \"+962\",\n      \"name\": \"Jordan\"\n    },\n    {\n      \"code\": \"+7 7\",\n      \"name\": \"Kazakhstan\"\n    },\n    {\n      \"code\": \"+254\",\n      \"name\": \"Kenya\"\n    },\n    {\n      \"code\": \"+686\",\n      \"name\": \"Kiribati\"\n    },\n    {\n      \"code\": \"+965\",\n      \"name\": \"Kuwait\"\n    },\n    {\n      \"code\": \"+996\",\n      \"name\": \"Kyrgyzstan\"\n    },\n    {\n      \"code\": \"+856\",\n      \"name\": \"Laos\"\n    },\n    {\n      \"code\": \"+371\",\n      \"name\": \"Latvia\"\n    },\n    {\n      \"code\": \"+961\",\n      \"name\": \"Lebanon\"\n    },\n    {\n      \"code\": \"+266\",\n      \"name\": \"Lesotho\"\n    },\n    {\n      \"code\": \"+231\",\n      \"name\": \"Liberia\"\n    },\n    {\n      \"code\": \"+218\",\n      \"name\": \"Libya\"\n    },\n    {\n      \"code\": \"+423\",\n      \"name\": \"Liechtenstein\"\n    },\n    {\n      \"code\": \"+370\",\n      \"name\": \"Lithuania\"\n    },\n    {\n      \"code\": \"+352\",\n      \"name\": \"Luxembourg\"\n    },\n    {\n      \"code\": \"+853\",\n      \"name\": \"Macau SAR China\"\n    },\n    {\n      \"code\": \"+389\",\n      \"name\": \"Macedonia\"\n    },\n    {\n      \"code\": \"+261\",\n      \"name\": \"Madagascar\"\n    },\n    {\n      \"code\": \"+265\",\n      \"name\": \"Malawi\"\n    },\n    {\n      \"code\": \"+60\",\n      \"name\": \"Malaysia\"\n    },\n    {\n      \"code\": \"+960\",\n      \"name\": \"Maldives\"\n    },\n    {\n      \"code\": \"+223\",\n      \"name\": \"Mali\"\n    },\n    {\n      \"code\": \"+356\",\n      \"name\": \"Malta\"\n    },\n    {\n      \"code\": \"+692\",\n      \"name\": \"Marshall Islands\"\n    },\n    {\n      \"code\": \"+596\",\n      \"name\": \"Martinique\"\n    },\n    {\n      \"code\": \"+222\",\n      \"name\": \"Mauritania\"\n    },\n    {\n      \"code\": \"+230\",\n      \"name\": \"Mauritius\"\n    },\n    {\n      \"code\": \"+262\",\n      \"name\": \"Mayotte\"\n    },\n    {\n      \"code\": \"+52\",\n      \"name\": \"Mexico\"\n    },\n    {\n      \"code\": \"+691\",\n      \"name\": \"Micronesia\"\n    },\n    {\n      \"code\": \"+1 808\",\n      \"name\": \"Midway Island\"\n    },\n    {\n      \"code\": \"+373\",\n      \"name\": \"Moldova\"\n    },\n    {\n      \"code\": \"+377\",\n      \"name\": \"Monaco\"\n    },\n    {\n      \"code\": \"+976\",\n      \"name\": \"Mongolia\"\n    },\n    {\n      \"code\": \"+382\",\n      \"name\": \"Montenegro\"\n    },\n    {\n      \"code\": \"+1664\",\n      \"name\": \"Montserrat\"\n    },\n    {\n      \"code\": \"+212\",\n      \"name\": \"Morocco\"\n    },\n    {\n      \"code\": \"+95\",\n      \"name\": \"Myanmar\"\n    },\n    {\n      \"code\": \"+264\",\n      \"name\": \"Namibia\"\n    },\n    {\n      \"code\": \"+674\",\n      \"name\": \"Nauru\"\n    },\n    {\n      \"code\": \"+977\",\n      \"name\": \"Nepal\"\n    },\n    {\n      \"code\": \"+31\",\n      \"name\": \"Netherlands\"\n    },\n    {\n      \"code\": \"+599\",\n      \"name\": \"Netherlands Antilles\"\n    },\n    {\n      \"code\": \"+1 869\",\n      \"name\": \"Nevis\"\n    },\n    {\n      \"code\": \"+687\",\n      \"name\": \"New Caledonia\"\n    },\n    {\n      \"code\": \"+64\",\n      \"name\": \"New Zealand\"\n    },\n    {\n      \"code\": \"+505\",\n      \"name\": \"Nicaragua\"\n    },\n    {\n      \"code\": \"+227\",\n      \"name\": \"Niger\"\n    },\n    {\n      \"code\": \"+234\",\n      \"name\": \"Nigeria\"\n    },\n    {\n      \"code\": \"+683\",\n      \"name\": \"Niue\"\n    },\n    {\n      \"code\": \"+672\",\n      \"name\": \"Norfolk Island\"\n    },\n    {\n      \"code\": \"+850\",\n      \"name\": \"North Korea\"\n    },\n    {\n      \"code\": \"+1 670\",\n      \"name\": \"Northern Mariana Islands\"\n    },\n    {\n      \"code\": \"+47\",\n      \"name\": \"Norway\"\n    },\n    {\n      \"code\": \"+968\",\n      \"name\": \"Oman\"\n    },\n    {\n      \"code\": \"+92\",\n      \"name\": \"Pakistan\"\n    },\n    {\n      \"code\": \"+680\",\n      \"name\": \"Palau\"\n    },\n    {\n      \"code\": \"+970\",\n      \"name\": \"Palestinian Territory\"\n    },\n    {\n      \"code\": \"+507\",\n      \"name\": \"Panama\"\n    },\n    {\n      \"code\": \"+675\",\n      \"name\": \"Papua New Guinea\"\n    },\n    {\n      \"code\": \"+595\",\n      \"name\": \"Paraguay\"\n    },\n    {\n      \"code\": \"+51\",\n      \"name\": \"Peru\"\n    },\n    {\n      \"code\": \"+63\",\n      \"name\": \"Philippines\"\n    },\n    {\n      \"code\": \"+48\",\n      \"name\": \"Poland\"\n    },\n    {\n      \"code\": \"+351\",\n      \"name\": \"Portugal\"\n    },\n    {\n      \"code\": \"+1 787\",\n      \"name\": \"Puerto Rico\"\n    },\n    {\n      \"code\": \"+974\",\n      \"name\": \"Qatar\"\n    },\n    {\n      \"code\": \"+262\",\n      \"name\": \"Reunion\"\n    },\n    {\n      \"code\": \"+40\",\n      \"name\": \"Romania\"\n    },\n    {\n      \"code\": \"+7\",\n      \"name\": \"Russia\"\n    },\n    {\n      \"code\": \"+250\",\n      \"name\": \"Rwanda\"\n    },\n    {\n      \"code\": \"+685\",\n      \"name\": \"Samoa\"\n    },\n    {\n      \"code\": \"+378\",\n      \"name\": \"San Marino\"\n    },\n    {\n      \"code\": \"+966\",\n      \"name\": \"Saudi Arabia\"\n    },\n    {\n      \"code\": \"+221\",\n      \"name\": \"Senegal\"\n    },\n    {\n      \"code\": \"+381\",\n      \"name\": \"Serbia\"\n    },\n    {\n      \"code\": \"+248\",\n      \"name\": \"Seychelles\"\n    },\n    {\n      \"code\": \"+232\",\n      \"name\": \"Sierra Leone\"\n    },\n    {\n      \"code\": \"+65\",\n      \"name\": \"Singapore\"\n    },\n    {\n      \"code\": \"+421\",\n      \"name\": \"Slovakia\"\n    },\n    {\n      \"code\": \"+386\",\n      \"name\": \"Slovenia\"\n    },\n    {\n      \"code\": \"+677\",\n      \"name\": \"Solomon Islands\"\n    },\n    {\n      \"code\": \"+27\",\n      \"name\": \"South Africa\"\n    },\n    {\n      \"code\": \"+500\",\n      \"name\": \"South Georgia and the South Sandwich Islands\"\n    },\n    {\n      \"code\": \"+82\",\n      \"name\": \"South Korea\"\n    },\n    {\n      \"code\": \"+34\",\n      \"name\": \"Spain\"\n    },\n    {\n      \"code\": \"+94\",\n      \"name\": \"Sri Lanka\"\n    },\n    {\n      \"code\": \"+249\",\n      \"name\": \"Sudan\"\n    },\n    {\n      \"code\": \"+597\",\n      \"name\": \"Suriname\"\n    },\n    {\n      \"code\": \"+268\",\n      \"name\": \"Swaziland\"\n    },\n    {\n      \"code\": \"+46\",\n      \"name\": \"Sweden\"\n    },\n    {\n      \"code\": \"+41\",\n      \"name\": \"Switzerland\"\n    },\n    {\n      \"code\": \"+963\",\n      \"name\": \"Syria\"\n    },\n    {\n      \"code\": \"+886\",\n      \"name\": \"Taiwan\"\n    },\n    {\n      \"code\": \"+992\",\n      \"name\": \"Tajikistan\"\n    },\n    {\n      \"code\": \"+255\",\n      \"name\": \"Tanzania\"\n    },\n    {\n      \"code\": \"+66\",\n      \"name\": \"Thailand\"\n    },\n    {\n      \"code\": \"+670\",\n      \"name\": \"Timor Leste\"\n    },\n    {\n      \"code\": \"+228\",\n      \"name\": \"Togo\"\n    },\n    {\n      \"code\": \"+690\",\n      \"name\": \"Tokelau\"\n    },\n    {\n      \"code\": \"+676\",\n      \"name\": \"Tonga\"\n    },\n    {\n      \"code\": \"+1 868\",\n      \"name\": \"Trinidad and Tobago\"\n    },\n    {\n      \"code\": \"+216\",\n      \"name\": \"Tunisia\"\n    },\n    {\n      \"code\": \"+90\",\n      \"name\": \"Turkey\"\n    },\n    {\n      \"code\": \"+993\",\n      \"name\": \"Turkmenistan\"\n    },\n    {\n      \"code\": \"+1 649\",\n      \"name\": \"Turks and Caicos Islands\"\n    },\n    {\n      \"code\": \"+688\",\n      \"name\": \"Tuvalu\"\n    },\n    {\n      \"code\": \"+1 340\",\n      \"name\": \"U.S. Virgin Islands\"\n    },\n    {\n      \"code\": \"+256\",\n      \"name\": \"Uganda\"\n    },\n    {\n      \"code\": \"+380\",\n      \"name\": \"Ukraine\"\n    },\n    {\n      \"code\": \"+971\",\n      \"name\": \"United Arab Emirates\"\n    },\n    {\n      \"code\": \"+44\",\n      \"name\": \"United Kingdom\"\n    },\n    {\n      \"code\": \"+1\",\n      \"name\": \"United States\"\n    },\n    {\n      \"code\": \"+598\",\n      \"name\": \"Uruguay\"\n    },\n    {\n      \"code\": \"+998\",\n      \"name\": \"Uzbekistan\"\n    },\n    {\n      \"code\": \"+678\",\n      \"name\": \"Vanuatu\"\n    },\n    {\n      \"code\": \"+58\",\n      \"name\": \"Venezuela\"\n    },\n    {\n      \"code\": \"+84\",\n      \"name\": \"Vietnam\"\n    },\n    {\n      \"code\": \"+1 808\",\n      \"name\": \"Wake Island\"\n    },\n    {\n      \"code\": \"+681\",\n      \"name\": \"Wallis and Futuna\"\n    },\n    {\n      \"code\": \"+967\",\n      \"name\": \"Yemen\"\n    },\n    {\n      \"code\": \"+260\",\n      \"name\": \"Zambia\"\n    },\n    {\n      \"code\": \"+255\",\n      \"name\": \"Zanzibar\"\n    },\n    {\n      \"code\": \"+263\",\n      \"name\": \"Zimbabwe\"\n    }\n  ]\n}', '2022-06-06 06:54:27', '2022-06-06 06:48:21'),
(26, 'country_code', '+91', '2022-06-06 07:52:41', '2022-06-06 07:52:26'),
(27, 'api_key_settings', '{\"google_map_api\":\"YOUR_MAP_API\",\"firebase_server_key\":\"SERVER_KEY\", \"google_places_api\":\"YOUR_PLACE_API_KEY\"}', '2025-08-13 12:48:28', NULL),
(29, 'range_units', 'kilometers', '2022-08-10 10:37:37', NULL),
(30, 'contact_us', '{\"contact_us\":\"<p>Enter Contact Us.<\\/p>\"}', '2022-11-05 07:53:48', NULL),
(31, 'system_tax_settings', '{\"tax_status\":\"on\",\"tax_name\":\"GST\",\"tax\":\"10\"}', '2022-11-26 06:31:11', NULL);
INSERT INTO `settings` (`id`, `variable`, `value`, `created_at`, `updated_at`) VALUES
(32, 'country_codes', '{\n  \n \"countries\":[\n \n  {\n \"code\": \"+93\",   \n  \"name\": \"Afghanistan\" \n  },\n  {\n \"code\": \"+358\",  \n  \"name\": \"Åland Islands\"\n  },\n  {\n \"code\": \"+355\",  \n  \"name\": \"Albania\"\n  },\n  {\n \"code\": \"+213\",  \n  \"name\": \"Algeria\"\n  },\n  {\n \"code\": \"+1 684\",\n  \"name\": \"American Samoa\"\n  },\n  {\n \"code\": \"+376\",  \n  \"name\": \"Andorra\"\n  },\n  {\n \"code\": \"+244\",  \n  \"name\": \"Angola\"\n  },\n  {\n \"code\": \"+1 264\",\n  \"name\": \"Anguilla\"\n  },\n  {\n \"code\": \"+672\",  \n  \"name\": \"Antarctica\"\n  },\n  {\n \"code\": \"+1 268\",\n  \"name\": \"Antigua and Barbuda\"\n  },\n  {\n \"code\": \"+54\",   \n  \"name\": \"Argentina\"\n  },\n  {\n \"code\": \"+374\",  \n  \"name\": \"Armenia\"\n  },\n  {\n \"code\": \"+297\",  \n  \"name\": \"Aruba\"\n  },\n  {\n \"code\": \"+61\",   \n  \"name\": \"Australia\"\n  },\n  {\n \"code\": \"+43\",   \n  \"name\": \"Austria\"\n  },\n  {\n \"code\": \"+994\",  \n  \"name\": \"Azerbaijan\"\n  },\n  {\n \"code\": \"+1 242\",\n  \"name\": \"Bahamas\"\n  },\n  {\n \"code\": \"+973\",  \n  \"name\": \"Bahrain\"\n  },\n  {\n \"code\": \"+880\",  \n  \"name\": \"Bangladesh\"\n  },\n  {\n \"code\": \"+1 246\",\n  \"name\": \"Barbados\"\n  },\n  {\n \"code\": \"+375\",  \n  \"name\": \"Belarus\"\n  },\n  {\n \"code\": \"+32\",   \n  \"name\": \"Belgium\"\n  },\n  {\n \"code\": \"+501\",  \n  \"name\": \"Belize\"\n  },\n  {\n \"code\": \"+229\",  \n  \"name\": \"Benin\"\n  },\n  {\n \"code\": \"+1 441\",\n  \"name\": \"Bermuda\"\n  },\n  {\n \"code\": \"+975\",  \n  \"name\": \"Bhutan\"\n  },\n  {\n \"code\": \"+591\",  \n  \"name\": \"Bolivia (Plurinational State of)\"\n  },\n  {\n \"code\": \"+599\",  \n  \"name\": \"Bonaire, Sint Eustatius and Saba\"\n  },\n  {\n \"code\": \"+387\",  \n  \"name\": \"Bosnia and Herzegovina\"\n  },\n  {\n \"code\": \"+267\",  \n  \"name\": \"Botswana\"\n  },\n  {\n \"code\": \"+47\",   \n  \"name\": \"Bouvet Island\"\n  },\n  {\n \"code\": \"+55\",   \n  \"name\": \"Brazil\"\n  },\n  {\n \"code\": \"+246\",  \n  \"name\": \"British Indian Ocean Territory\"\n  },\n  {\n \"code\": \"+673\",  \n  \"name\": \"Brunei Darussalam\"\n  },\n  {\n \"code\": \"+359\",  \n  \"name\": \"Bulgaria\"\n  },\n  {\n \"code\": \"+226\",  \n  \"name\": \"Burkina Faso\"\n  },\n  {\n \"code\": \"+257\",  \n  \"name\": \"Burundi\"\n  },\n  {\n \"code\": \"+238\",  \n  \"name\": \"Cabo Verde\"\n  },\n  {\n \"code\": \"+855\",  \n  \"name\": \"Cambodia\"\n  },\n  {\n \"code\": \"+237\",  \n  \"name\": \"Cameroon\"\n  },\n  {\n \"code\": \"+1\",    \n  \"name\": \"Canada\"\n  },\n  {\n \"code\": \"+1 345\",\n  \"name\": \"Cayman Islands\"\n  },\n  {\n \"code\": \"+236\",  \n  \"name\": \"Central African Republic\"\n  },\n  {\n \"code\": \"+235\",  \n  \"name\": \"Chad\"\n  },\n  {\n \"code\": \"+56\",   \n  \"name\": \"Chile\"\n  },\n  {\n \"code\": \"+86\",   \n  \"name\": \"China\"\n  },\n  {\n \"code\": \"+61\",   \n  \"name\": \"Christmas Island\"\n  },\n  {\n \"code\": \"+61\",   \n  \"name\": \"Cocos (Keeling) Islands\"\n  },\n  {\n \"code\": \"+57\",   \n  \"name\": \"Colombia\"\n  },\n  {\n \"code\": \"+269\",  \n  \"name\": \"Comoros\"\n  },\n  {\n \"code\": \"+242\",  \n  \"name\": \"Congo\"\n  },\n  {\n \"code\": \"+243\",  \n  \"name\": \"Congo, Democratic Republic of the\"\n  },\n  {\n \"code\": \"+682\",  \n  \"name\": \"Cook Islands\"\n  },\n  {\n \"code\": \"+506\",  \n  \"name\": \"Costa Rica\"\n  },\n  {\n \"code\": \"+225\",  \n  \"name\": \"Côte d\'Ivoire\"\n  },\n  {\n \"code\": \"+385\",  \n  \"name\": \"Croatia\"\n  },\n  {\n \"code\": \"+53\",   \n  \"name\": \"Cuba\"\n  },\n  {\n \"code\": \"+599\",  \n  \"name\": \"Curaçao\"\n  },\n  {\n \"code\": \"+357\",  \n  \"name\": \"Cyprus\"\n  },\n  {\n \"code\": \"+420\",  \n  \"name\": \"Czechia\"\n  },\n  {\n \"code\": \"+45\",   \n  \"name\": \"Denmark\"\n  },\n  {\n \"code\": \"+253\",  \n  \"name\": \"Djibouti\"\n  },\n  {\n \"code\": \"+1 767\",\n  \"name\": \"Dominica\"\n  },\n  {\n \"code\": \"+1 809\",\n  \"name\": \"Dominican Republic\"\n  },\n  {\n \"code\": \"+593\",  \n  \"name\": \"Ecuador\"\n  },\n  {\n \"code\": \"+20\",   \n  \"name\": \"Egypt\"\n  },\n  {\n \"code\": \"+503\",  \n  \"name\": \"El Salvador\"\n  },\n  {\n \"code\": \"+240\",  \n  \"name\": \"Equatorial Guinea\"\n  },\n  {\n \"code\": \"+291\",  \n  \"name\": \"Eritrea\"\n  },\n  {\n \"code\": \"+372\",  \n  \"name\": \"Estonia\"\n  },\n  {\n \"code\": \"+268\",  \n  \"name\": \"Eswatini\"\n  },\n  {\n \"code\": \"+251\",  \n  \"name\": \"Ethiopia\"\n  },\n  {\n \"code\": \"+500\",  \n  \"name\": \"Falkland Islands (Malvinas)\"\n  },\n  {\n \"code\": \"+298\",  \n  \"name\": \"Faroe Islands\"\n  },\n  {\n \"code\": \"+679\",  \n  \"name\": \"Fiji\"\n  },\n  {\n \"code\": \"+358\",  \n  \"name\": \"Finland\"\n  },\n  {\n \"code\": \"+33\",   \n  \"name\": \"France\"\n  },\n  {\n \"code\": \"+594\",  \n  \"name\": \"French Guiana\"\n  },\n  {\n \"code\": \"+689\",  \n  \"name\": \"French Polynesia\"\n  },\n  {\n \"code\": \"+262\",  \n  \"name\": \"French Southern Territories\"\n  },\n  {\n \"code\": \"+241\",  \n  \"name\": \"Gabon\"\n  },\n  {\n \"code\": \"+220\",  \n  \"name\": \"Gambia\"\n  },\n  {\n \"code\": \"+995\",  \n  \"name\": \"Georgia\"\n  },\n  {\n \"code\": \"+49\",   \n  \"name\": \"Germany\"\n  },\n  {\n \"code\": \"+233\",  \n  \"name\": \"Ghana\"\n  },\n  {\n \"code\": \"+350\",  \n  \"name\": \"Gibraltar\"\n  },\n  {\n \"code\": \"+30\",   \n  \"name\": \"Greece\"\n  },\n  {\n \"code\": \"+299\",  \n  \"name\": \"Greenland\"\n  },\n  {\n \"code\": \"+1 473\",\n  \"name\": \"Grenada\"\n  },\n  {\n \"code\": \"+590\",  \n  \"name\": \"Guadeloupe\"\n  },\n  {\n \"code\": \"+1 671\",\n  \"name\": \"Guam\"\n  },\n  {\n \"code\": \"+502\",  \n  \"name\": \"Guatemala\"\n  },\n  {\n \"code\": \"+44\",   \n  \"name\": \"Guernsey\"\n  },\n  {\n \"code\": \"+224\",  \n  \"name\": \"Guinea\"\n  },\n  {\n \"code\": \"+245\",  \n  \"name\": \"Guinea-Bissau\"\n  },\n  {\n \"code\": \"+592\",  \n  \"name\": \"Guyana\"\n  },\n  {\n \"code\": \"+509\",  \n  \"name\": \"Haiti\"\n  },\n  {\n \"code\": \"+672\",  \n  \"name\": \"Heard Island and McDonald Islands\"\n  },\n  {\n \"code\": \"+379\",  \n  \"name\": \"Holy See\"\n  },\n  {\n \"code\": \"+504\",  \n  \"name\": \"Honduras\"\n  },\n  {\n \"code\": \"+852\",  \n  \"name\": \"Hong Kong\"\n  },\n  {\n \"code\": \"+36\",   \n  \"name\": \"Hungary\"\n  },\n  {\n \"code\": \"+354\",  \n  \"name\": \"Iceland\"\n  },\n  {\n \"code\": \"+91\",   \n  \"name\": \"India\"\n  },\n  {\n \"code\": \"+62\",   \n  \"name\": \"Indonesia\"\n  },\n  {\n \"code\": \"+98\",   \n  \"name\": \"Iran (Islamic Republic of)\"\n  },\n  {\n \"code\": \"+964\",  \n  \"name\": \"Iraq\"\n  },\n  {\n \"code\": \"+353\",  \n  \"name\": \"Ireland\"\n  },\n  {\n \"code\": \"+44\",   \n  \"name\": \"Isle of Man\"\n  },\n  {\n \"code\": \"+972\",  \n  \"name\": \"Israel\"\n  },\n  {\n \"code\": \"+39\",   \n  \"name\": \"Italy\"\n  },\n  {\n \"code\": \"+1\",    \n  \"name\": \"Jamaica\"\n  },\n  {\n \"code\": \"+81\",   \n  \"name\": \"Japan\"\n  },\n  {\n \"code\": \"+44\",   \n  \"name\": \"Jersey\"\n  },\n  {\n \"code\": \"+962\",  \n  \"name\": \"Jordan\"\n  },\n  {\n \"code\": \"+7 840\",\n  \"name\": \"Abkhazia\" }\n  \n  ]\n}', '2025-08-13 12:46:34', '2022-06-06 06:48:21'),
(33, 'web_settings', '{\"social_media\": [{\"url\": \"https://www.instagram.com/wrteam.in/\", \"file\": \"instagram.png\"}, {\"url\": \"https://www.linkedin.com/company/wrteam\", \"file\": \"5.png\"}, {\"url\": \"https://www.facebook.com/wrteam.in\", \"file\": \"3.png\"}], \"web_title\": \"Download eDemand Mobile App Free\", \"web_tagline\": \"Get eDemand App Now!\", \"short_description\": \"Get the latest resources for downloading, installing, and updating eDemand app. Select your device platform and Use Our app and Enjoy Your Life.\", \"playstore_url\": \"https://play.google.com/store/apps/details?id=wrteam.edemand.customer.e_demand\", \"app_section_status\": \"1\", \"applestore_url\": \"https://testflight.apple.com/join/KdqqsTnH\", \"landing_page_title\": \"One Stop Solution For Your All Services\", \"step_1_title\": \"Request Service\", \"step_2_title\": \"Match with Providers\", \"step_3_title\": \"Monitor Progress\", \"step_4_title\": \"Receive Quality Results\", \"step_1_description\": \"Simply choose the service you need and request it through our user-friendly platform.\", \"step_2_description\": \"Simply choose the service you need and request it through our user-friendly platform.\", \"step_3_description\": \"Simply choose the service you need and request it through our user-friendly platform.\", \"step_4_description\": \"Simply choose the service you need and request it through our user-friendly platform.\", \"process_flow_description\": \"Learn how eDemand streamlines the service booking process for you. From selecting your desired service to tracking its progress, our user-friendly platform ensures a seamless experience.\", \"footer_description\": \"eDemand: Your premier destination for efficient and reliable on-demand services.\", \"process_flow_title\": \"How eDemand Work\", \"web_logo\": \"1712291207_ea8387289fbe73fb9692.svg\", \"web_favicon\": \"1712290334_2aea411f2bf5ddf3e429.svg\", \"web_half_logo\": \"1712290334_44294b1cf3becbbf12c8.svg\", \"footer_logo\": \"1712291207_465ead38b08223e85f9e.png\", \"landing_page_logo\": \"1712290334_c232acc0fea1307e8e97.png\", \"landing_page_backgroud_image\": \"1712290334_dc7aec05d060321c3202.jpeg\", \"step_1_image\": \"1712289102_e92d26a3ad71ef5ff95b.png\", \"step_2_image\": \"1712289102_93c2e95f5a7595a43558.png\", \"step_3_image\": \"1712289102_5f4cd2d127ceb36b69a0.png\", \"step_4_image\": \"1712289102_089f079a3cb8fd99f6c2.png\", \"service_section_title\": \"Essential Repair Services\", \"category_section_title\": \"Essential Repair Services\", \"category_section_description\": \"Discover top-notch services tailored to meet your every need. Our professionals are dedicated to providing reliable and efficient solutions for your home and beyond.\", \"service_section_description\": \"Discover top-notch services tailored to meet your every need. Our professionals are dedicated to providing reliable and efficient solutions for your home and beyond.\", \"rating_section_title\": \"What people say about our services\", \"rating_section_description\": \"Read feedback from our valued customers to see how we’ve made a difference for them. Your opinion matters to us—share your experience and help others make informed decisions.\", \"faq_section_title\": \"Frequently Asked Questions\", \"faq_section_description\": \"Find answers to common questions about our services, how to use the app, and more. If you have any additional questions, feel free to reach out to our support team for further assistance.\", \"web_maintenance_mode\": \"0\"}', '2025-08-13 12:13:46', NULL),
(34, 'sms_gateway_setting', '\r\n {\"twilio_endpoint\":\"YOUR_URL\",\r\n \"sms_gateway_method\":\"POST\",\r\n \"country_code_include\":\"0\",\r\n \"header_key\":[\"Authorization\"],\r\n \"header_value\":[\"Basic YOUR_TOKEN\"],\r\n \"params_key\":[\"Test param key\"],\r\n \"params_value\":[\"test param value\"],\r\n \"body_key\":[\"To\",\"From\",\"Body\"],\"body_value\":[\"{only_mobile_number}\",\"123245\",\"{message}\"]}', '2022-11-26 06:31:11', NULL),
(35, 'notification_settings', '{\r\n  \"provider_approved_sms\": \"true\",\r\n  \"provider_approved_notification\": \"true\",\r\n  \"provider_disapproved_sms\": \"true\",\r\n  \"withdraw_request_approved_email\": \"true\",\r\n  \"withdraw_request_disapproved_sms\": \"true\",\r\n  \"withdraw_request_disapproved_notification\": \"true\",\r\n  \"withdraw_request_received_sms\": \"true\",\r\n  \"withdraw_request_send_email\": \"true\",\r\n  \"new_rating_given_by_customer_email\": \"true\",\r\n  \"new_rating_given_by_customer_sms\": \"true\",\r\n  \"new_rating_given_by_customer_notification\": \"true\",\r\n  \"rating_request_to_customer_email\": \"true\",\r\n  \"rating_request_to_customer_sms\": \"true\",\r\n  \"rating_request_to_customer_notification\": \"true\"\r\n}', '2022-11-26 06:31:11', NULL),
(36, 'storage_disk', 'local_server', '2025-08-13 12:13:46', NULL),
(37, 'become_provider_page_settings', '{\"hero_section\":\"{\\\"status\\\":1,\\\"short_headline\\\":\\\"OPPORTUNITY KNOCKS\\\",\\\"title\\\":\\\"We Provide High Quality Professional Services\\\",\\\"description\\\":\\\"Become an eDemand provider and start earning extra money today. Enjoy flexibility, choose your hours, and take control of your financial future.\\\",\\\"images\\\":[{\\\"image\\\":\\\"1742990017_b7543a5a607df5412e6b.png\\\"},{\\\"image\\\":\\\"1742990529_dc811298a4aa05b7cb85.png\\\"},{\\\"image\\\":\\\"1742990529_5f56f34f54c25c2abf25.png\\\"},{\\\"image\\\":\\\"1742990529_70d695d2bb7e36041338.png\\\"}]}\",\"how_it_work_section\":\"{\\\"status\\\":1,\\\"short_headline\\\":\\\"HOW IT WORKS\\\",\\\"title\\\":\\\"Become a Successful Service Provider\\\",\\\"description\\\":\\\"Easily transform your skills into a thriving business. Our platform provides the tools you need to attract customers, manage bookings, and grow your service empire.\\\",\\\"steps\\\":\\\"[{\\\\\\\"title\\\\\\\":\\\\\\\"Create an Account as a Provider\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"Register as a service provider in the system\\\\\\\"},{\\\\\\\"title\\\\\\\":\\\\\\\"Approval from Admin\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"The admin will review and approve the providers profile\\\\\\\"},{\\\\\\\"title\\\\\\\":\\\\\\\"Add Services\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"The provider can list the services they offer\\\\\\\"},{\\\\\\\"title\\\\\\\":\\\\\\\"Subscription Purchase\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"Providers need to buy a subscription to be listed for customers.\\\\\\\"},{\\\\\\\"title\\\\\\\":\\\\\\\"Receive Bookings\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"Customers can book services based on the listed offerings.\\\\\\\"},{\\\\\\\"title\\\\\\\":\\\\\\\"Custom Bookings\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"Providers can receive custom booking requests.\\\\\\\"},{\\\\\\\"title\\\\\\\":\\\\\\\"Bidding System\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"Providers can bid for custom jobs, increasing their earning opportunities.\\\\\\\"},{\\\\\\\"title\\\\\\\":\\\\\\\"Service Mode Selection\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"Providers can choose to offer services at the customer\\\\\\\\u2019s location or at their shop.\\\\\\\"},{\\\\\\\"title\\\\\\\":\\\\\\\"Earnings Based on Work\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"Payment is received based on the completed services.\\\\\\\"},{\\\\\\\"title\\\\\\\":\\\\\\\"Track Earnings\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"Providers can view their earnings and transaction details.\\\\\\\"},{\\\\\\\"title\\\\\\\":\\\\\\\"Promocode Offers\\\\\\\",\\\\\\\"description\\\\\\\":\\\\\\\"Providers can offer promo codes to attract more bookings.\\\\\\\"}]\\\"}\",\"category_section\":\"{\\\"status\\\":0,\\\"short_headline\\\":\\\"YOUR NEEDS, OUR SERVICES \\\",\\\"title\\\":\\\"Discover a World of Services at Your Fingertips\\\",\\\"description\\\":\\\"Need a cleaner, a plumber, or a tech expert? We have got you covered. Discover a wide range of services, all in one place.\\\",\\\"category_ids\\\":[]}\",\"subscription_section\":\"{\\\"status\\\":1,\\\"short_headline\\\":\\\"UNLOCK UNLIMITED ACCESS\\\",\\\"title\\\":\\\"Elevate Your Business with Our Subscription\\\",\\\"description\\\":\\\"                                             Get more out of eDemand with our subscription plan. Enjoy increased visibility, access to premium features, and the ability to expand your service offerings.\\\"}\",\"top_providers_section\":\"{\\\"status\\\":1,\\\"short_headline\\\":\\\"TOP RATED PROVIDERS\\\",\\\"title\\\":\\\"Trusted by Thousands: Our Top-Rated Providers\\\",\\\"description\\\":\\\"Our top-rated providers are customer favorites. With a proven track record of excellence, they consistently deliver outstanding service.\\\"}\",\"review_section\":\"{\\\"status\\\":1,\\\"short_headline\\\":\\\"YOUR REVIEW MATTERS\\\",\\\"title\\\":\\\"What our Customers Says About Providers\\\",\\\"description\\\":\\\"Discover how eDemand has transformed businesses. Hear directly from our satisfied providers about their success stories and how our platform has helped them reach new heights.\\\",\\\"rating_ids\\\":[]}\",\"faq_section\":\"{\\\"status\\\":1,\\\"short_headline\\\":\\\" TRANSPARENCY MATTERS \\\",\\\"title\\\":\\\"Need Help? We have Got Answers\\\",\\\"description\\\":\\\"Have questions about joining eDemand or providing services? Our FAQ section offers clear and concise answers to the most common inquiries.\\\",\\\"faqs\\\":\\\"[{\\\\\\\"question\\\\\\\":\\\\\\\" What is eDemand, and how does it help service providers?\\\\\\\",\\\\\\\"answer\\\\\\\":\\\\\\\"eDemand is an on-demand service platform that connects skilled service providers with customers looking for various services, including home repairs, beauty, cleaning, and more. It helps providers grow their business by getting more job requests without marketing hassle.\\\\\\\"}]\\\"}\",\"feature_section\":\"{\\\"status\\\":1,\\\"features\\\":[{\\\"short_headline\\\":\\\"SET YOUR OWN HOURS, SERVE YOUR WAY\\\",\\\"title\\\":\\\"Take Control of Your Time and Business\\\",\\\"description\\\":\\\"Enjoy unparalleled flexibility as you build your service empire. Our platform empowers you to set your own schedule, choose your clients, and balance your work life seamlessly.\\\",\\\"position\\\":\\\"left\\\",\\\"image\\\":\\\"1750246562_10e3a0ec3144c18d693a.png\\\"},{\\\"short_headline\\\":\\\"CONNECT, CHAT, CARE\\\",\\\"title\\\":\\\"Instant Messaging for Better Service\\\",\\\"description\\\":\\\"Enjoy unparalleled flexibility as you build your service empire. Our platform empowers you to set your own schedule, choose your clients, and balance your work life seamlessly.\\\",\\\"position\\\":\\\"right\\\",\\\"image\\\":\\\"1750246562_9684dcb2daad93eaefda.png\\\"},{\\\"short_headline\\\":\\\"YOUR SERVICE, YOUR RULES\\\",\\\"title\\\":\\\"Take Charge of Your Service Business\\\",\\\"description\\\":\\\"Create detailed service listings including a unique name, categorize your service for easy discovery, and outline the specific tasks involved. Enhance your listing with relevant files like images or documents. Provide a clear and informative description of your service, including pricing details and frequently asked questions.\\\\r\\\\n\\\\r\\\\nManage your service status, cancellation policy, and payment options. Choose whether your service is offered at your location or at the customers doorstep.\\\",\\\"position\\\":\\\"left\\\",\\\"image\\\":\\\"1750246562_bfb4175a4fd811b11119.png\\\"}]}\"}', '2025-06-17 05:55:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settlement_cashcollection_history`
--

CREATE TABLE `settlement_cashcollection_history` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `payment_request_id` int(11) DEFAULT NULL,
  `commission_percentage` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` text NOT NULL COMMENT ' -cash_collection_by_admin  - cod\r\n    -cash_collection_by_provider - code\r\n    -received_by_admin - online_payment\r\n    -settled_by_settlement - manual settlement by admin\r\n    -settled_by_payment_request - withrequest approved by admin',
  `date` date NOT NULL,
  `time` time NOT NULL,
  `amount` int(11) NOT NULL,
  `total_amount` int(11) NOT NULL,
  `commission_amount` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settlement_history`
--

CREATE TABLE `settlement_history` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `date` date NOT NULL,
  `amount` text NOT NULL,
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` int(11) NOT NULL,
  `type` varchar(128) NOT NULL,
  `type_id` int(11) NOT NULL,
  `app_image` varchar(128) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 - deactive \r\n1 - active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `url` text DEFAULT NULL,
  `web_image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` int(11) NOT NULL,
  `type` text NOT NULL,
  `template` longtext NOT NULL,
  `parameters` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `title` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_templates`
--

INSERT INTO `sms_templates` (`id`, `type`, `template`, `parameters`, `created_at`, `updated_at`, `title`) VALUES
(1, 'provider_approved', 'Dear [[provider_name]], your request as a provider has been approved. You can now start providing services through our platform. Visit [[site_url]] for more details.', '[\"provider_name\",\"site_url\"]', '2024-07-16 12:19:21', '2025-11-03 18:00:06', 'Approval'),
(2, 'provider_disapproved', 'Dear [[provider_name]], we regret to inform you that your provider request with [[company_name]] has been disapproved. For further information, please contact us at [[company_contact_info]].\r\n', '[\"provider_name\",\"company_name\",\"company_contact_info\"]', '2024-07-16 12:23:29', '2024-07-16 12:23:29', 'Rejection'),
(3, 'withdraw_request_approved', 'Hello [[provider_name]], your withdrawal request has been approved. The amount of [[amount]] [[currency]] will be processed shortly.', '[\"provider_name\",\"amount\",\"currency\"]', '2024-07-16 12:24:04', '2024-07-16 12:24:04', 'Withdrawal'),
(4, 'withdraw_request_disapproved', 'Hello [[provider_name]], we regret to inform you that your withdrawal request has been disapproved.Please contact [[company_name]] at [[company_contact_info]] for more details.\r\n', '[\"provider_name\",\"company_name\",\"company_contact_info\"]', '2024-07-16 12:34:19', '2024-07-16 12:34:19', 'Withdrawal'),
(5, 'payment_settlement', 'Dear [[provider_name]], your payment of [[amount]] [[currency]] has been successfully settled. Thank you for choosing [[company_name]].\r\n', '[\"provider_name\",\"amount\",\"currency\",\"company_name\"]', '2024-07-16 12:35:19', '2024-07-16 12:35:19', 'Payment'),
(6, 'service_disapproved', 'Dear [[provider_name]], we regret to inform you that your service request ([[service_name]]) with [[company_name]] has been disapproved. Please contact us for further assistance.\r\n', '[\"provider_name\",\"service_name\",\"company_name\"]', '2024-07-16 12:36:14', '2024-07-16 12:36:14', 'Rejection'),
(7, 'service_approved', 'Hello [[provider_name]], your service request ([[service_name]]) has been approved by [[company_name]]. You can proceed with the service as planned.\r\n', '[\"provider_name\",\"service_name\",\"company_name\"]', '2024-07-16 12:37:22', '2024-07-16 12:37:22', 'Approval'),
(8, 'user_account_active', 'Hello [[user_name]], your account with [[company_name]] is now active. \r\n', '[\"user_name\",\"company_name\"]', '2024-07-16 12:37:56', '2024-07-16 12:37:56', 'Account'),
(9, 'user_account_deactive', 'Hello [[user_name]], your account with [[company_name]] has been deactivated. If you have any questions, please contact us at [[company_contact_info]].\n', '[\"user_name\",\"company_name\",\"company_contact_info\"]', '2024-07-16 12:38:47', '2024-07-16 12:38:47', 'Account'),
(11, 'new_booking_confirmation_to_customer', 'Hello [[user_name]], your booking ([[booking_id]]) with [[company_name]] for [[booking_service_names]] on [[booking_date]] at [[booking_time]] has been confirmed. Thank you for choosing us!\n', '[\"user_name\",\"booking_id\",\"company_name\",\"booking_service_names\",\"booking_date\",\"booking_time\"]', '2024-07-16 12:46:26', '2024-07-16 12:46:26', 'Booking'),
(12, 'new_booking_received_for_provider', 'Hello [[provider_name]], a new booking ([[booking_id]]) has been received for you from [[user_name]]. Please review and confirm at [[site_url]].\r\n', '[\"provider_name\",\"booking_id\",\"user_name\",\"site_url\"]', '2024-07-16 12:47:31', '2024-07-16 12:47:31', 'New'),
(13, 'provider_update_information', 'Hello [[company_logo]], [[provider_name]] updated their details. Check once.\r\n', '[\"company_logo\",\"provider_name\"]', '2024-07-16 12:48:38', '2024-07-16 12:48:38', 'Provider Update Information'),
(14, 'new_provider_registerd', 'A new provider [[provider_name]] has registered with [[company_name]]. Visit [[site_url]] for more details.\r\n', '[\"provider_name\",\"company_name\",\"site_url\"]', '2024-07-16 12:51:15', '2024-07-16 12:51:15', 'New Provider Registered'),
(15, 'withdraw_request_received', 'Hello [[provider_name]], we have received your withdrawal request. It is currently being processed. You will be notified once it\'s approved or disapproved.\r\n', '[\"provider_name\"]', '2024-07-16 12:54:38', '2024-07-16 12:54:38', 'Withdrawal Request Received'),
(16, 'new_rating_given_by_customer', 'Dear [[provider_name]] A [[user_name]] has just rated your service. Check your dashboard for details and feedback. Thank you!', '[\"provider_name\",\"user_name\"]', '2024-08-02 17:54:27', '2024-08-02 17:54:27', 'New Rating Alert'),
(17, 'rating_request_to_customer', 'Dear [[user_name]] We value your feedback! Please take a moment to rate your recent experience with us. Your input helps us improve. \r\n\r\n', '[\"user_name\"]', '2024-08-02 17:56:08', '2024-08-02 17:56:08', 'Rating Request to customer'),
(18, 'new_booking_received_for_provider', '[[user_id]]okokkokokok', '[\"user_id\"]', '2024-11-29 22:50:10', '2025-09-30 16:02:56', 'ok'),
(19, 'cash_collection_by_provider', 'Provider [[provider_name]] (ID: [[provider_id]]) completed COD booking #[[booking_id]]. Admin commission of [[currency]][[amount]] is due. - [[company_name]]', '[\"company_name\",\"provider_name\",\"provider_id\",\"amount\",\"currency\",\"booking_id\"]', '2025-11-10 10:35:46', '2025-11-10 10:35:46', 'Cash Collection by Provider'),
(20, 'maintenance_mode', 'System is under maintenance. We apologize for any inconvenience. Please check back later.', '[\"company_name\",\"company_contact_info\"]', '2025-11-12 09:48:55', '2025-11-12 09:48:55', 'Maintenance Mode Enabled'),
(21, 'category_removed', 'Category [[category_name]] has been removed. Services in this category have been deactivated.', '[\"company_name\",\"category_name\",\"category_id\"]', '2025-11-12 09:50:34', '2025-11-12 09:50:34', 'Category Removed'),
(22, 'new_blog', 'New blog \"[[blog_title]]\" has been published. Read it here: [[blog_url]]', '[\"company_name\",\"blog_title\",\"blog_url\"]', '2025-11-12 09:51:19', '2025-11-12 09:51:19', 'New Blog Published'),
(23, 'new_category_available', 'New category [[category_name]] is now available! Explore services in this category at [[site_url]]', '[\"company_name\",\"category_name\",\"category_id\",\"site_url\"]', '2025-11-12 09:53:08', '2025-11-12 09:53:08', 'New Category Available'),
(24, 'new_custom_job_request', 'Customer [[customer_name]] created custom job request #[[custom_job_request_id]]', '[\"customer_name\",\"customer_id\",\"custom_job_request_id\"]', '2025-11-12 09:53:50', '2025-11-12 09:53:50', 'New Custom Job Request'),
(25, 'new_user_registered', 'A new user [[user_name]] (ID: [[user_id]]) has registered on the platform. Email: [[user_email]] Phone: [[user_phone]]', '[\"user_name\",\"user_id\",\"user_email\",\"user_phone\"]', '2025-11-12 09:56:16', '2025-11-12 09:56:16', 'New User Registered'),
(26, 'privacy_policy_changed', 'The privacy policy has been updated. Please review the updated privacy policy at [[site_url]]', '[\"company_name\",\"site_url\"]', '2025-11-12 09:57:15', '2025-11-12 09:57:15', 'Privacy Policy Updated'),
(27, 'promo_code_added', 'New promo code \"[[promo_code]]\" added for [[provider_name]]. Discount: [[discount]][[discount_type_symbol]]. Valid: [[start_date]] to [[end_date]].', '[\"provider_name\",\"promo_code\",\"discount\",\"discount_type_symbol\",\"start_date\",\"end_date\"]', '2025-11-12 09:58:00', '2025-11-12 09:58:00', 'New Promo Code Added'),
(28, 'service_updated', 'Provider [[provider_name]] (ID: [[provider_id]]) updated service #[[service_id]]: [[service_title]]. - [[company_name]]', '[\"company_name\",\"provider_name\",\"provider_id\",\"service_id\",\"service_title\",\"category_name\"]', '2025-11-12 09:59:38', '2025-11-12 09:59:38', 'Service Updated'),
(29, 'subscription_changed', 'Your subscription has been changed to [[subscription_name]]. Price: [[subscription_price]] [[currency]]. Expiry: [[expiry_date]]', '[\"company_name\",\"provider_name\",\"subscription_name\",\"subscription_price\",\"subscription_duration\",\"expiry_date\",\"currency\"]', '2025-11-12 10:02:14', '2025-11-12 10:02:14', 'Subscription Changed'),
(30, 'subscription_removed', 'Your subscription [[subscription_name]] has been removed by the admin. Your subscription is now deactivated.', '[\"company_name\",\"provider_name\",\"subscription_name\",\"subscription_id\"]', '2025-11-12 10:02:51', '2025-11-12 10:02:51', 'Subscription Removed'),
(31, 'terms_and_conditions_changed', 'The terms and conditions have been updated. Please review the updated terms and conditions at [[site_url]]', '[\"company_name\",\"site_url\"]', '2025-11-12 10:03:29', '2025-11-12 10:03:29', 'Terms and Conditions Updated'),
(32, 'user_blocked', '[[blocker_name]] ([[blocker_type]]) has blocked [[blocked_user_name]] ([[blocked_user_type]]).', '[\"blocker_name\",\"blocker_type\",\"blocked_user_name\",\"blocked_user_type\"]', '2025-11-12 10:04:43', '2025-11-12 10:04:43', 'User Blocked'),
(33, 'user_query_submitted', 'New customer query from [[customer_name]] ([[customer_email]]). Subject: [[query_subject]]', '[\"customer_name\",\"customer_email\",\"query_subject\"]', '2025-11-12 10:05:21', '2025-11-12 10:05:21', 'New Customer Query'),
(34, 'user_reported', '[[notification_message]] Reason: [[report_reason]]', '[\"reporter_name\",\"reporter_type\",\"reported_user_name\",\"reported_user_type\",\"report_reason\",\"notification_message\"]', '2025-11-12 10:06:03', '2025-11-12 10:06:03', 'User Reported'),
(35, 'added_additional_charges', 'Dear [[customer_name]], provider [[provider_name]] has added additional charges of [[total_additional_charge]] [[currency]] to your booking #[[booking_id]]. Final total: [[final_total]] [[currency]]. Please review and make payment.', '[\"booking_id\", \"order_id\", \"total_additional_charge\", \"currency\", \"provider_id\", \"provider_name\", \"customer_id\", \"customer_name\", \"final_total\"]', '2025-11-14 05:20:44', '2025-11-14 05:20:44', 'Additional Charges Added'),
(36, 'bid_on_custom_job_request', 'Dear [[customer_name]], provider [[provider_name]] has placed a bid of [[counter_price]] [[currency]] on your custom job request: [[service_title]]. Duration: [[duration]] days. View details in your account.', '[\"custom_job_request_id\", \"service_title\", \"service_short_description\", \"provider_id\", \"provider_name\", \"bid_id\", \"counter_price\", \"currency\", \"duration\", \"cover_note\", \"customer_id\", \"customer_name\", \"category_name\"]', '2025-11-14 05:20:58', '2025-11-14 05:20:58', 'New Bid Received'),
(37, 'online_payment_success', 'Dear [[customer_name]], your payment of [[amount]] [[currency]] for Booking #[[booking_id]] has been successfully processed. Transaction ID: [[transaction_id]]. Your booking is now confirmed.', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"transaction_id\", \"customer_id\", \"customer_name\", \"payment_method\", \"paid_at\"]', '2025-11-14 05:21:14', '2025-11-14 05:21:14', 'Payment Successful'),
(38, 'online_payment_failed', 'Dear [[customer_name]], your payment of [[amount]] [[currency]] for Booking #[[booking_id]] has failed. Transaction ID: [[transaction_id]]. Please try again or contact support.', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"transaction_id\", \"customer_id\", \"customer_name\", \"failure_reason\", \"payment_method\"]', '2025-11-14 05:21:14', '2025-11-14 05:21:14', 'Payment Failed'),
(39, 'online_payment_pending', 'Dear [[customer_name]], your payment of [[amount]] [[currency]] for Booking #[[booking_id]] is pending. Transaction ID: [[transaction_id]]. We will notify you once confirmed.', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"transaction_id\", \"customer_id\", \"customer_name\", \"payment_method\"]', '2025-11-14 05:21:14', '2025-11-14 05:21:14', 'Payment Pending'),
(40, 'payment_refund_executed', 'Dear [[customer_name]], your refund of [[amount]] [[currency]] for booking #[[booking_id]] has been processed. Refund ID: [[refund_id]]. The amount will be credited to your account within a few business days.', '[\"booking_id\", \"order_id\", \"amount\", \"currency\", \"refund_id\", \"transaction_id\", \"customer_id\", \"customer_name\", \"processed_date\"]', '2025-11-14 05:21:35', '2025-11-14 05:21:35', 'Refund Processed'),
(41, 'payment_refund_successful', 'Dear [[customer_name]], your refund of [[amount]] [[currency]] for Order #[[order_id]] has been processed successfully. Refund ID: [[refund_id]]. The amount will be credited within 3-5 business days.', '[\"order_id\", \"amount\", \"currency\", \"refund_id\", \"transaction_id\", \"customer_name\", \"customer_email\", \"customer_id\", \"processed_date\"]', '2025-11-14 05:21:44', '2025-11-14 05:21:44', 'Refund Processed Successfully'),
(42, 'subscription_expired', 'Dear [[provider_name]], your subscription [[subscription_name]] has expired on [[expiry_date]]. Please renew your subscription to continue providing services.', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"expiry_date\", \"purchase_date\", \"duration\"]', '2025-11-14 05:23:19', '2025-11-14 05:23:19', 'Subscription Expired'),
(43, 'subscription_payment_successful', 'Dear [[provider_name]], your subscription payment for [[subscription_name]] has been successfully processed. Amount: [[amount]] [[currency]]. Transaction ID: [[transaction_id]]. Your subscription is now active.', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"amount\", \"currency\", \"transaction_id\", \"purchase_date\", \"expiry_date\"]', '2025-11-14 05:23:38', '2025-11-14 05:23:38', 'Payment Successful'),
(44, 'subscription_payment_failed', 'Dear [[provider_name]], your subscription payment for [[subscription_name]] has failed. Amount: [[amount]] [[currency]]. Transaction ID: [[transaction_id]]. Please try again or contact support.', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"amount\", \"currency\", \"transaction_id\", \"failure_reason\"]', '2025-11-14 05:23:38', '2025-11-14 05:23:38', 'Payment Failed'),
(45, 'subscription_payment_pending', 'Dear [[provider_name]], your subscription payment for [[subscription_name]] is pending. Amount: [[amount]] [[currency]]. Transaction ID: [[transaction_id]]. We will notify you once confirmed.', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"amount\", \"currency\", \"transaction_id\"]', '2025-11-14 05:23:38', '2025-11-14 05:23:38', 'Payment Pending'),
(46, 'subscription_purchased', 'Admin: Provider [[provider_name]] has purchased subscription [[subscription_name]] for [[amount]] [[currency]]. Purchase date: [[purchase_date]]. Transaction ID: [[transaction_id]].', '[\"subscription_id\", \"subscription_name\", \"provider_id\", \"provider_name\", \"purchase_date\", \"expiry_date\", \"duration\", \"amount\", \"currency\", \"transaction_id\"]', '2025-11-14 05:23:56', '2025-11-14 05:23:56', 'Subscription Purchased'),
(47, 'booking_confirmed', 'Dear [[customer_name]], your booking #[[booking_id]] has been confirmed. Service Date: [[date_of_service]] at [[service_time]]. Provider: [[provider_name]]. Total: [[currency]] [[final_total]]. Thank you! - [[company_name]]', '[\"booking_id\",\"customer_name\",\"provider_name\",\"date_of_service\",\"service_time\",\"final_total\",\"currency\",\"company_name\"]', '2025-11-17 06:35:42', '2025-11-17 06:35:42', 'Booking Confirmed'),
(48, 'booking_rescheduled', 'Dear [[customer_name]], your booking #[[booking_id]] has been rescheduled. New Date: [[date_of_service]] at [[service_time]]. Provider: [[provider_name]]. - [[company_name]]', '[\"booking_id\",\"customer_name\",\"provider_name\",\"date_of_service\",\"service_time\",\"company_name\"]', '2025-11-17 06:35:42', '2025-11-17 06:35:42', 'Booking Rescheduled'),
(49, 'booking_cancelled', 'Dear [[customer_name]], your booking #[[booking_id]] has been cancelled. If you have questions, please contact us. - [[company_name]]', '[\"booking_id\",\"customer_name\",\"company_name\"]', '2025-11-17 06:35:42', '2025-11-17 06:35:42', 'Booking Cancelled'),
(50, 'booking_completed', 'Dear [[customer_name]], your booking #[[booking_id]] has been completed successfully! Total: [[currency]] [[final_total]]. Thank you for using our services! - [[company_name]]', '[\"booking_id\",\"customer_name\",\"final_total\",\"currency\",\"company_name\"]', '2025-11-17 06:35:42', '2025-11-17 06:35:42', 'Booking Completed'),
(51, 'booking_started', 'Dear [[customer_name]], your service for booking #[[booking_id]] has started. Provider: [[provider_name]]. - [[company_name]]', '[\"booking_id\",\"customer_name\",\"provider_name\",\"company_name\"]', '2025-11-17 06:35:42', '2025-11-17 06:35:42', 'Service Started'),
(52, 'booking_ended', 'Dear [[customer_name]], your booking #[[booking_id]] has ended. [[status_message]] Total: [[currency]] [[final_total]]. - [[company_name]]', '[\"booking_id\",\"customer_name\",\"status_message\",\"final_total\",\"currency\",\"company_name\"]', '2025-11-17 06:35:42', '2025-11-17 06:35:42', 'Booking Ended'),
(53, 'provider_edits_service_details', 'Provider [[provider_name]] has edited service details. Service: [[service_title]] (ID: [[service_id]]). Category: [[category_name]]. Price: [[currency]] [[service_price]]. Please review in admin panel.', '[\"provider_name\",\"service_id\",\"service_title\",\"category_name\",\"service_price\",\"currency\"]', '2025-12-18 16:13:01', '2025-12-18 16:13:01', 'Provider Edits Service Details');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `duration` text NOT NULL,
  `price` double NOT NULL,
  `discount_price` double NOT NULL,
  `publish` text NOT NULL,
  `order_type` text NOT NULL,
  `max_order_limit` text DEFAULT NULL,
  `service_type` text NOT NULL,
  `max_service_limit` text DEFAULT NULL,
  `tax_type` text NOT NULL,
  `tax_id` text DEFAULT NULL,
  `is_commision` text NOT NULL,
  `commission_threshold` text DEFAULT NULL,
  `commission_percentage` text DEFAULT NULL,
  `status` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taxes`
--

CREATE TABLE `taxes` (
  `id` int(11) NOT NULL,
  `title` varchar(1024) NOT NULL,
  `percentage` double NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0- deactive | 1 - active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `themes`
--

CREATE TABLE `themes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `is_default` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `themes`
--

INSERT INTO `themes` (`id`, `name`, `slug`, `image`, `is_default`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Retro', 'retro', 'retro.png', 1, 1, '2021-12-03 13:33:03', '2022-08-09 10:20:22');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_type` varchar(16) NOT NULL,
  `user_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `order_id` varchar(128) DEFAULT NULL,
  `type` varchar(64) DEFAULT NULL,
  `txn_id` varchar(256) DEFAULT NULL,
  `amount` double NOT NULL,
  `status` varchar(12) DEFAULT NULL,
  `currency_code` varchar(5) DEFAULT NULL,
  `message` varchar(128) NOT NULL,
  `transaction_date` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reference` text DEFAULT NULL,
  `subscription_id` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `translated_blog_category_details`
--

CREATE TABLE `translated_blog_category_details` (
  `id` int(11) NOT NULL,
  `blog_category_id` int(11) NOT NULL COMMENT 'Reference to blog_categories.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, hi, ar)',
  `name` varchar(255) DEFAULT NULL COMMENT 'Blog category name in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated blog category details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_blog_details`
--

CREATE TABLE `translated_blog_details` (
  `id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL COMMENT 'Reference to blogs.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, hi, ar)',
  `title` text DEFAULT NULL COMMENT 'Blog title in specific language',
  `short_description` text DEFAULT NULL COMMENT 'Blog short description in specific language',
  `description` longtext DEFAULT NULL COMMENT 'Blog description in specific language',
  `tags` text DEFAULT NULL COMMENT 'Blog tags in specific language (JSON format)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated blog details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_blog_seo_settings`
--

CREATE TABLE `translated_blog_seo_settings` (
  `id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL COMMENT 'Reference to blogs.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g., en, ar, tr)',
  `seo_title` varchar(255) DEFAULT NULL COMMENT 'SEO title in specific language',
  `seo_description` text DEFAULT NULL COMMENT 'SEO description in specific language',
  `seo_keywords` text DEFAULT NULL COMMENT 'SEO keywords in specific language',
  `seo_schema_markup` longtext DEFAULT NULL COMMENT 'Schema markup in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated SEO settings for blogs';

-- --------------------------------------------------------

--
-- Table structure for table `translated_blog_tag_details`
--

CREATE TABLE `translated_blog_tag_details` (
  `id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL COMMENT 'Reference to blog_tags.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, hi, ar)',
  `name` varchar(255) DEFAULT NULL COMMENT 'Blog tag name in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated blog tag details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_category_details`
--

CREATE TABLE `translated_category_details` (
  `id` int(11) NOT NULL,
  `category_id` int(11) UNSIGNED NOT NULL COMMENT 'Reference to categories.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, ar, tr)',
  `name` varchar(255) DEFAULT NULL COMMENT 'Category name in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated category details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_category_seo_settings`
--

CREATE TABLE `translated_category_seo_settings` (
  `id` int(11) NOT NULL,
  `category_id` int(11) UNSIGNED NOT NULL COMMENT 'Reference to categories.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g., en, ar, tr)',
  `seo_title` varchar(255) DEFAULT NULL COMMENT 'SEO title in specific language',
  `seo_description` text DEFAULT NULL COMMENT 'SEO description in specific language',
  `seo_keywords` text DEFAULT NULL COMMENT 'SEO keywords in specific language',
  `seo_schema_markup` longtext DEFAULT NULL COMMENT 'Schema markup in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated SEO settings for categories';

-- --------------------------------------------------------

--
-- Table structure for table `translated_email_templates`
--

CREATE TABLE `translated_email_templates` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL COMMENT 'Reference to email_templates.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, hi, ar)',
  `subject` varchar(255) DEFAULT NULL COMMENT 'Email subject in specific language',
  `template` longtext DEFAULT NULL COMMENT 'Email template content in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated email template details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_faq_details`
--

CREATE TABLE `translated_faq_details` (
  `id` int(11) NOT NULL,
  `faq_id` int(11) NOT NULL COMMENT 'Reference to faqs.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, hi, ar)',
  `question` text DEFAULT NULL COMMENT 'FAQ question in specific language',
  `answer` longtext DEFAULT NULL COMMENT 'FAQ answer in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated FAQ details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_featured_sections`
--

CREATE TABLE `translated_featured_sections` (
  `id` int(11) NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL COMMENT 'Reference to sections.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, ar, tr)',
  `title` varchar(255) DEFAULT NULL COMMENT 'Section title in specific language',
  `description` text DEFAULT NULL COMMENT 'Section description in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated featured section details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_notification_templates`
--

CREATE TABLE `translated_notification_templates` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL COMMENT 'Reference to notification_templates.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g., en, ar, tr)',
  `title` varchar(255) DEFAULT NULL COMMENT 'Notification title in specific language',
  `body` text DEFAULT NULL COMMENT 'Notification body in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated notification templates for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_partner_details`
--

CREATE TABLE `translated_partner_details` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL COMMENT 'Reference to partner_details.partner_id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, ar, tr)',
  `company_name` varchar(255) DEFAULT NULL COMMENT 'Company name in specific language',
  `about` text DEFAULT NULL COMMENT 'About provider description in specific language',
  `long_description` longtext DEFAULT NULL COMMENT 'Detailed description in specific language',
  `username` varchar(255) DEFAULT NULL COMMENT 'Partner username in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated partner details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_partner_seo_settings`
--

CREATE TABLE `translated_partner_seo_settings` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL COMMENT 'Reference to partner_details.partner_id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g., en, ar, tr)',
  `seo_title` varchar(255) DEFAULT NULL COMMENT 'SEO title in specific language',
  `seo_description` text DEFAULT NULL COMMENT 'SEO description in specific language',
  `seo_keywords` text DEFAULT NULL COMMENT 'SEO keywords in specific language',
  `seo_schema_markup` longtext DEFAULT NULL COMMENT 'Schema markup in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated SEO settings for partners';

-- --------------------------------------------------------

--
-- Table structure for table `translated_promocode_details`
--

CREATE TABLE `translated_promocode_details` (
  `id` int(11) NOT NULL,
  `promocode_id` int(11) NOT NULL COMMENT 'Reference to promocodes.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, ar, tr)',
  `message` text DEFAULT NULL COMMENT 'Promocode message in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated promocode details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_reasons_for_report_and_block_chat`
--

CREATE TABLE `translated_reasons_for_report_and_block_chat` (
  `id` int(11) NOT NULL,
  `reason_id` int(11) NOT NULL COMMENT 'Reference to reasons_for_report_and_block_chat.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, ar, tr)',
  `reason` text DEFAULT NULL COMMENT 'Reason text in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated reason details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_seo_settings`
--

CREATE TABLE `translated_seo_settings` (
  `id` int(11) NOT NULL,
  `seo_id` int(11) NOT NULL COMMENT 'Reference to seo_settings.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g., en, ar, tr)',
  `seo_title` varchar(255) DEFAULT NULL COMMENT 'SEO title in specific language',
  `seo_description` text DEFAULT NULL COMMENT 'SEO description in specific language',
  `seo_keywords` text DEFAULT NULL COMMENT 'SEO keywords in specific language',
  `seo_schema_markup` longtext DEFAULT NULL COMMENT 'Schema markup in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated SEO settings for general pages';

-- --------------------------------------------------------

--
-- Table structure for table `translated_service_details`
--

CREATE TABLE `translated_service_details` (
  `id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL COMMENT 'Reference to services.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, ar, tr)',
  `title` varchar(255) DEFAULT NULL COMMENT 'Service title in specific language',
  `description` text DEFAULT NULL COMMENT 'Short description in specific language',
  `long_description` longtext DEFAULT NULL COMMENT 'Detailed description in specific language',
  `tags` text DEFAULT NULL COMMENT 'Service tags in specific language',
  `faqs` longtext DEFAULT NULL COMMENT 'FAQs in specific language (JSON format)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated service details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_service_seo_settings`
--

CREATE TABLE `translated_service_seo_settings` (
  `id` int(11) NOT NULL,
  `service_id` int(11) UNSIGNED NOT NULL COMMENT 'Reference to services.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g., en, ar, tr)',
  `seo_title` varchar(255) DEFAULT NULL COMMENT 'SEO title in specific language',
  `seo_description` text DEFAULT NULL COMMENT 'SEO description in specific language',
  `seo_keywords` text DEFAULT NULL COMMENT 'SEO keywords in specific language',
  `seo_schema_markup` longtext DEFAULT NULL COMMENT 'Schema markup in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated SEO settings for services';

-- --------------------------------------------------------

--
-- Table structure for table `translated_sms_templates`
--

CREATE TABLE `translated_sms_templates` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL COMMENT 'Reference to sms_templates.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, hi, ar)',
  `title` varchar(255) DEFAULT NULL COMMENT 'SMS template title in specific language',
  `template` longtext DEFAULT NULL COMMENT 'SMS template content in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated SMS template details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_subscription_details`
--

CREATE TABLE `translated_subscription_details` (
  `id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL COMMENT 'Reference to subscriptions.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, hi, ar)',
  `name` varchar(255) DEFAULT NULL COMMENT 'Subscription name in specific language',
  `description` text DEFAULT NULL COMMENT 'Subscription description in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated subscription details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `translated_tax_details`
--

CREATE TABLE `translated_tax_details` (
  `id` int(11) NOT NULL,
  `tax_id` int(11) NOT NULL COMMENT 'Reference to taxes.id',
  `language_code` varchar(10) NOT NULL COMMENT 'Language code (e.g. en, hi, ar)',
  `title` varchar(255) DEFAULT NULL COMMENT 'Tax title in specific language',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores translated tax details for multi-language support';

-- --------------------------------------------------------

--
-- Table structure for table `updates`
--

CREATE TABLE `updates` (
  `id` int(20) NOT NULL,
  `version` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `updates`
--

INSERT INTO `updates` (`id`, `version`, `created_at`, `updated_at`) VALUES
(1, '1.0', '2022-11-14 04:55:25', '2022-11-14 04:55:25'),
(2, '1.1.0', '2022-12-01 13:08:33', '2022-12-01 13:08:33'),
(3, '1.2.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(4, '1.3.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(5, '1.4.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(6, '1.5.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(7, '1.6.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(8, '1.7.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(9, '1.8.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(10, '1.9.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(11, '2.0.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(12, '2.1.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(13, '2.2.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(14, '2.2.1', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(15, '2.3.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(16, '2.4.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(17, '2.5.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(18, '2.6.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(19, '2.7.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(20, '2.8.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(21, '2.9.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(22, '3.0.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(23, '3.1.0', '2022-12-06 13:08:33', '2022-12-06 13:08:33'),
(24, '4.0.0', '2025-03-26 11:00:00', '2025-03-26 11:00:00'),
(25, '4.1.0', '2025-03-26 11:00:00', '2025-03-26 11:00:00'),
(26, '4.2.0', '2025-08-13 12:15:12', '2025-08-13 12:15:12'),
(27, '4.3.0', '2025-10-13 09:51:19', '2025-10-13 09:51:19'),
(28, '4.4.0', '2025-12-04 13:37:36', '2025-12-04 13:37:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(250) DEFAULT NULL,
  `balance` double NOT NULL DEFAULT 0,
  `activation_selector` varchar(255) DEFAULT NULL,
  `activation_code` varchar(255) DEFAULT NULL,
  `forgotten_password_selector` varchar(255) DEFAULT NULL,
  `forgotten_password_code` varchar(255) DEFAULT NULL,
  `forgotten_password_time` int(11) UNSIGNED DEFAULT NULL,
  `remember_selector` varchar(255) DEFAULT NULL,
  `remember_code` varchar(255) DEFAULT NULL,
  `created_on` int(11) UNSIGNED NOT NULL,
  `last_login` int(11) UNSIGNED DEFAULT NULL,
  `active` tinyint(1) UNSIGNED DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `country_code` text NOT NULL,
  `fcm_id` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `api_key` text NOT NULL,
  `friends_code` varchar(255) DEFAULT NULL,
  `referral_code` varchar(255) DEFAULT NULL,
  `city_id` int(50) DEFAULT 0,
  `city` varchar(252) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payable_commision` text DEFAULT NULL,
  `strip_id` text DEFAULT NULL,
  `web_fcm_id` text DEFAULT NULL,
  `platform` text DEFAULT NULL,
  `panel_fcm_id` text DEFAULT NULL,
  `unsubscribe_email` varchar(255) NOT NULL DEFAULT '1',
  `uid` varchar(255) DEFAULT NULL,
  `loginType` varchar(255) DEFAULT NULL,
  `countryCodeName` varchar(255) DEFAULT NULL,
  `preferred_language` varchar(125) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `ip_address`, `username`, `password`, `email`, `balance`, `activation_selector`, `activation_code`, `forgotten_password_selector`, `forgotten_password_code`, `forgotten_password_time`, `remember_selector`, `remember_code`, `created_on`, `last_login`, `active`, `first_name`, `last_name`, `company`, `phone`, `country_code`, `fcm_id`, `image`, `api_key`, `friends_code`, `referral_code`, `city_id`, `city`, `latitude`, `longitude`, `created_at`, `updated_at`, `payable_commision`, `strip_id`, `web_fcm_id`, `platform`, `panel_fcm_id`, `unsubscribe_email`, `uid`, `loginType`, `countryCodeName`, `preferred_language`) VALUES
(1, '127.0.0.1', 'Super Admin', '$2y$12$vUP2SOA0Ng1ziEeR9gHgMek/Qjke8TEOhLYqa4icuocv6AYfxxSZq', 'superadmin@gmail.com', 2000200, NULL, '', NULL, NULL, NULL, NULL, NULL, 1268889823, 1764856460, 1, 'Admin', 'istrator', 'ADMIN', '9876543210', '', 'eQHx3ANrRLmbdIO7kK8nek:APA91bHuI19SM6qptCWJ3plidwFOhVg2Rg77k4oTuMQ0Xmd521vDBBZKzFX9yLKhe5yEI1SFVvT53Dt8XeIP0j3vxjUtJBj3D7OkgpoSJTHSdznekuew8CL_Ye-MBAjU3ke4lZtgVyI9', '1669274676_d851d48dfbaf52438615.png', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2NjkwMDIwMjEsImlzcyI6ImVkZW1hbmQiLCJleHAiOjE3MDA1MzgwMjEsInN1YiI6ImVkZW1hbmRfYXV0aGVudGljYXRpb24iLCJ1c2VyX2lkIjoiMSJ9.bxPMyvDEFrkA1yq2lHhUhACwidQTsoR86te8gofHspM', '45dsrwr', 'MY_CODE', 10, '', '23.2330718', '69.6442306', '2022-05-24 04:44:29', '2022-05-24 04:44:29', NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_fcm_ids`
--

CREATE TABLE `users_fcm_ids` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fcm_id` text NOT NULL,
  `platform` enum('android','ios','web','admin_panel','provider_panel') NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `language_code` varchar(125) NOT NULL DEFAULT 'en',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_groups`
--

CREATE TABLE `users_groups` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `group_id` mediumint(8) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_groups`
--

INSERT INTO `users_groups` (`id`, `user_id`, `group_id`) VALUES
(1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users_tokens`
--

CREATE TABLE `users_tokens` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(512) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` varchar(512) NOT NULL COMMENT '1. super admin\r\n2. admin\r\n3. client',
  `permissions` mediumtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `role`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 1, '1', NULL, '2022-07-21 04:18:12', '2022-08-11 07:36:06');

-- --------------------------------------------------------

--
-- Table structure for table `user_reports`
--

CREATE TABLE `user_reports` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reported_user_id` int(11) NOT NULL,
  `reason_id` int(11) NOT NULL,
  `additional_info` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_contact_query`
--
ALTER TABLE `admin_contact_query`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `blocked_users_user_id_foreign` (`user_id`),
  ADD KEY `blocked_users_blocked_user_id_foreign` (`blocked_user_id`);

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`(255)),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `blogs_seo_settings`
--
ALTER TABLE `blogs_seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `blog_id` (`blog_id`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blog_tags`
--
ALTER TABLE `blog_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `blog_tag_map`
--
ALTER TABLE `blog_tag_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `blog_tag_unique` (`blog_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partner_id` (`partner_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cash_collection`
--
ALTER TABLE `cash_collection`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories_seo_settings`
--
ALTER TABLE `categories_seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_id` (`category_id`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `country_codes`
--
ALTER TABLE `country_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_job_provider`
--
ALTER TABLE `custom_job_provider`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_job_requests`
--
ALTER TABLE `custom_job_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delete_general_notification`
--
ALTER TABLE `delete_general_notification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_id` (`address_id`);

--
-- Indexes for table `order_services`
--
ALTER TABLE `order_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`,`service_id`);

--
-- Indexes for table `otps`
--
ALTER TABLE `otps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partners_seo_settings`
--
ALTER TABLE `partners_seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `partner_id` (`partner_id`);

--
-- Indexes for table `partner_bids`
--
ALTER TABLE `partner_bids`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partner_details`
--
ALTER TABLE `partner_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`partner_id`),
  ADD KEY `address_id` (`address_id`(768));

--
-- Indexes for table `partner_subscriptions`
--
ALTER TABLE `partner_subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partner_timings`
--
ALTER TABLE `partner_timings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `payment_request`
--
ALTER TABLE `payment_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`partner_id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `queue_jobs`
--
ALTER TABLE `queue_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `queue_priority_status_available_at` (`queue`,`priority`,`status`,`available_at`);

--
-- Indexes for table `queue_jobs_failed`
--
ALTER TABLE `queue_jobs_failed`
  ADD PRIMARY KEY (`id`),
  ADD KEY `queue` (`queue`);

--
-- Indexes for table `reasons_for_report_and_block_chat`
--
ALTER TABLE `reasons_for_report_and_block_chat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seo_settings`
--
ALTER TABLE `seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_page` (`page`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`category_id`,`tax_id`),
  ADD KEY `tax_id` (`tax_id`),
  ADD KEY `id` (`id`),
  ADD KEY `id_2` (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `tax_id_2` (`tax_id`);

--
-- Indexes for table `services_ratings`
--
ALTER TABLE `services_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`service_id`);

--
-- Indexes for table `services_seo_settings`
--
ALTER TABLE `services_seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_id` (`service_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settlement_cashcollection_history`
--
ALTER TABLE `settlement_cashcollection_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settlement_history`
--
ALTER TABLE `settlement_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `taxes`
--
ALTER TABLE `taxes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `translated_blog_category_details`
--
ALTER TABLE `translated_blog_category_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_blog_category_language` (`blog_category_id`,`language_code`),
  ADD KEY `idx_blog_category_id` (`blog_category_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_blog_details`
--
ALTER TABLE `translated_blog_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_blog_language` (`blog_id`,`language_code`),
  ADD KEY `idx_blog_id` (`blog_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_blog_seo_settings`
--
ALTER TABLE `translated_blog_seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_blog_language_seo` (`blog_id`,`language_code`),
  ADD KEY `idx_blog_id` (`blog_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_blog_tag_details`
--
ALTER TABLE `translated_blog_tag_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_blog_tag_language` (`tag_id`,`language_code`),
  ADD KEY `idx_blog_tag_id` (`tag_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_category_details`
--
ALTER TABLE `translated_category_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_category_language` (`category_id`,`language_code`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_category_seo_settings`
--
ALTER TABLE `translated_category_seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_category_language_seo` (`category_id`,`language_code`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_email_templates`
--
ALTER TABLE `translated_email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_template_language` (`template_id`,`language_code`),
  ADD KEY `idx_template_id` (`template_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_faq_details`
--
ALTER TABLE `translated_faq_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_faq_language` (`faq_id`,`language_code`),
  ADD KEY `idx_faq_id` (`faq_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_featured_sections`
--
ALTER TABLE `translated_featured_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_section_language` (`section_id`,`language_code`),
  ADD KEY `idx_section_id` (`section_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_notification_templates`
--
ALTER TABLE `translated_notification_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_notification_template_language` (`template_id`,`language_code`),
  ADD KEY `idx_notification_template_id` (`template_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_partner_details`
--
ALTER TABLE `translated_partner_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_partner_language` (`partner_id`,`language_code`),
  ADD KEY `idx_partner_id` (`partner_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_partner_seo_settings`
--
ALTER TABLE `translated_partner_seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_partner_language_seo` (`partner_id`,`language_code`),
  ADD KEY `idx_partner_id` (`partner_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_promocode_details`
--
ALTER TABLE `translated_promocode_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_promocode_language` (`promocode_id`,`language_code`),
  ADD KEY `idx_promocode_id` (`promocode_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_reasons_for_report_and_block_chat`
--
ALTER TABLE `translated_reasons_for_report_and_block_chat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reason_language` (`reason_id`,`language_code`),
  ADD KEY `idx_reason_id` (`reason_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_seo_settings`
--
ALTER TABLE `translated_seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_seo_language` (`seo_id`,`language_code`),
  ADD KEY `idx_seo_id` (`seo_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_service_details`
--
ALTER TABLE `translated_service_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_service_language` (`service_id`,`language_code`),
  ADD KEY `idx_service_id` (`service_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_service_seo_settings`
--
ALTER TABLE `translated_service_seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_service_language_seo` (`service_id`,`language_code`),
  ADD KEY `idx_service_id` (`service_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_sms_templates`
--
ALTER TABLE `translated_sms_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_template_language` (`template_id`,`language_code`),
  ADD KEY `idx_template_id` (`template_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_subscription_details`
--
ALTER TABLE `translated_subscription_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_subscription_language` (`subscription_id`,`language_code`),
  ADD KEY `idx_subscription_id` (`subscription_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `translated_tax_details`
--
ALTER TABLE `translated_tax_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tax_language` (`tax_id`,`language_code`),
  ADD KEY `idx_tax_id` (`tax_id`),
  ADD KEY `idx_language_code` (`language_code`);

--
-- Indexes for table `updates`
--
ALTER TABLE `updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uc_activation_selector` (`activation_selector`),
  ADD UNIQUE KEY `uc_forgotten_password_selector` (`forgotten_password_selector`);

--
-- Indexes for table `users_fcm_ids`
--
ALTER TABLE `users_fcm_ids`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uc_users_groups` (`user_id`,`group_id`),
  ADD KEY `fk_users_groups_users1_idx` (`user_id`),
  ADD KEY `fk_users_groups_groups1_idx` (`group_id`);

--
-- Indexes for table `users_tokens`
--
ALTER TABLE `users_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_reports`
--
ALTER TABLE `user_reports`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_contact_query`
--
ALTER TABLE `admin_contact_query`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `blocked_users`
--
ALTER TABLE `blocked_users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blogs_seo_settings`
--
ALTER TABLE `blogs_seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blog_tags`
--
ALTER TABLE `blog_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blog_tag_map`
--
ALTER TABLE `blog_tag_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_collection`
--
ALTER TABLE `cash_collection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories_seo_settings`
--
ALTER TABLE `categories_seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `country_codes`
--
ALTER TABLE `country_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `custom_job_provider`
--
ALTER TABLE `custom_job_provider`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1447;

--
-- AUTO_INCREMENT for table `custom_job_requests`
--
ALTER TABLE `custom_job_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=312;

--
-- AUTO_INCREMENT for table `delete_general_notification`
--
ALTER TABLE `delete_general_notification`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emails`
--
ALTER TABLE `emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `enquiries`
--
ALTER TABLE `enquiries`
  MODIFY `id` int(60) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_services`
--
ALTER TABLE `order_services`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partners_seo_settings`
--
ALTER TABLE `partners_seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_bids`
--
ALTER TABLE `partner_bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=236;

--
-- AUTO_INCREMENT for table `partner_details`
--
ALTER TABLE `partner_details`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_subscriptions`
--
ALTER TABLE `partner_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `partner_timings`
--
ALTER TABLE `partner_timings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_request`
--
ALTER TABLE `payment_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_jobs`
--
ALTER TABLE `queue_jobs`
  MODIFY `id` bigint(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_jobs_failed`
--
ALTER TABLE `queue_jobs_failed`
  MODIFY `id` bigint(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reasons_for_report_and_block_chat`
--
ALTER TABLE `reasons_for_report_and_block_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seo_settings`
--
ALTER TABLE `seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services_ratings`
--
ALTER TABLE `services_ratings`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services_seo_settings`
--
ALTER TABLE `services_seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `settlement_cashcollection_history`
--
ALTER TABLE `settlement_cashcollection_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settlement_history`
--
ALTER TABLE `settlement_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `taxes`
--
ALTER TABLE `taxes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `themes`
--
ALTER TABLE `themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_blog_category_details`
--
ALTER TABLE `translated_blog_category_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_blog_details`
--
ALTER TABLE `translated_blog_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_blog_seo_settings`
--
ALTER TABLE `translated_blog_seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_blog_tag_details`
--
ALTER TABLE `translated_blog_tag_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_category_details`
--
ALTER TABLE `translated_category_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_category_seo_settings`
--
ALTER TABLE `translated_category_seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_email_templates`
--
ALTER TABLE `translated_email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_faq_details`
--
ALTER TABLE `translated_faq_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_featured_sections`
--
ALTER TABLE `translated_featured_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_notification_templates`
--
ALTER TABLE `translated_notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_partner_details`
--
ALTER TABLE `translated_partner_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_partner_seo_settings`
--
ALTER TABLE `translated_partner_seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_promocode_details`
--
ALTER TABLE `translated_promocode_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_reasons_for_report_and_block_chat`
--
ALTER TABLE `translated_reasons_for_report_and_block_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_seo_settings`
--
ALTER TABLE `translated_seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_service_details`
--
ALTER TABLE `translated_service_details`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_service_seo_settings`
--
ALTER TABLE `translated_service_seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_sms_templates`
--
ALTER TABLE `translated_sms_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_subscription_details`
--
ALTER TABLE `translated_subscription_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translated_tax_details`
--
ALTER TABLE `translated_tax_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users_fcm_ids`
--
ALTER TABLE `users_fcm_ids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_groups`
--
ALTER TABLE `users_groups`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=346;

--
-- AUTO_INCREMENT for table `users_tokens`
--
ALTER TABLE `users_tokens`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_reports`
--
ALTER TABLE `user_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD CONSTRAINT `blocked_users_blocked_user_id_foreign` FOREIGN KEY (`blocked_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `blocked_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `blogs`
--
ALTER TABLE `blogs`
  ADD CONSTRAINT `blog_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blogs_seo_settings`
--
ALTER TABLE `blogs_seo_settings`
  ADD CONSTRAINT `fk_seo_settings_blog` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_tag_map`
--
ALTER TABLE `blog_tag_map`
  ADD CONSTRAINT `blog_tag_map_ibfk_1` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_tag_map_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories_seo_settings`
--
ALTER TABLE `categories_seo_settings`
  ADD CONSTRAINT `fk_seo_settings_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partners_seo_settings`
--
ALTER TABLE `partners_seo_settings`
  ADD CONSTRAINT `fk_seo_settings_provider` FOREIGN KEY (`partner_id`) REFERENCES `partner_details` (`partner_id`) ON DELETE CASCADE;

--
-- Constraints for table `services_seo_settings`
--
ALTER TABLE `services_seo_settings`
  ADD CONSTRAINT `fk_seo_settings_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_blog_category_details`
--
ALTER TABLE `translated_blog_category_details`
  ADD CONSTRAINT `fk_translated_blog_category_details_blog_category_id` FOREIGN KEY (`blog_category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_blog_details`
--
ALTER TABLE `translated_blog_details`
  ADD CONSTRAINT `fk_translated_blog_details_blog_id` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_blog_seo_settings`
--
ALTER TABLE `translated_blog_seo_settings`
  ADD CONSTRAINT `fk_translated_blog_seo_blog_id` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_blog_tag_details`
--
ALTER TABLE `translated_blog_tag_details`
  ADD CONSTRAINT `fk_translated_blog_tag_details_tag_id` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_category_details`
--
ALTER TABLE `translated_category_details`
  ADD CONSTRAINT `fk_translated_category_details_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_category_seo_settings`
--
ALTER TABLE `translated_category_seo_settings`
  ADD CONSTRAINT `fk_translated_category_seo_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_email_templates`
--
ALTER TABLE `translated_email_templates`
  ADD CONSTRAINT `fk_translated_email_templates_template_id` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_faq_details`
--
ALTER TABLE `translated_faq_details`
  ADD CONSTRAINT `fk_translated_faq_details_faq_id` FOREIGN KEY (`faq_id`) REFERENCES `faqs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_featured_sections`
--
ALTER TABLE `translated_featured_sections`
  ADD CONSTRAINT `fk_translated_featured_sections_section_id` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_notification_templates`
--
ALTER TABLE `translated_notification_templates`
  ADD CONSTRAINT `fk_translated_notification_templates_template` FOREIGN KEY (`template_id`) REFERENCES `notification_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_partner_details`
--
ALTER TABLE `translated_partner_details`
  ADD CONSTRAINT `fk_translated_partner_details_partner_id` FOREIGN KEY (`partner_id`) REFERENCES `partner_details` (`partner_id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_partner_seo_settings`
--
ALTER TABLE `translated_partner_seo_settings`
  ADD CONSTRAINT `fk_translated_partner_seo_partner_id` FOREIGN KEY (`partner_id`) REFERENCES `partner_details` (`partner_id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_promocode_details`
--
ALTER TABLE `translated_promocode_details`
  ADD CONSTRAINT `fk_translated_promocode_details_promocode_id` FOREIGN KEY (`promocode_id`) REFERENCES `promo_codes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_reasons_for_report_and_block_chat`
--
ALTER TABLE `translated_reasons_for_report_and_block_chat`
  ADD CONSTRAINT `fk_translated_reasons_reason_id` FOREIGN KEY (`reason_id`) REFERENCES `reasons_for_report_and_block_chat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_seo_settings`
--
ALTER TABLE `translated_seo_settings`
  ADD CONSTRAINT `fk_translated_seo_seo_id` FOREIGN KEY (`seo_id`) REFERENCES `seo_settings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_service_details`
--
ALTER TABLE `translated_service_details`
  ADD CONSTRAINT `fk_translated_service_details_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_service_seo_settings`
--
ALTER TABLE `translated_service_seo_settings`
  ADD CONSTRAINT `fk_translated_service_seo_service_id` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_sms_templates`
--
ALTER TABLE `translated_sms_templates`
  ADD CONSTRAINT `fk_translated_sms_templates_template_id` FOREIGN KEY (`template_id`) REFERENCES `sms_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_subscription_details`
--
ALTER TABLE `translated_subscription_details`
  ADD CONSTRAINT `fk_translated_subscription_details_subscription_id` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translated_tax_details`
--
ALTER TABLE `translated_tax_details`
  ADD CONSTRAINT `fk_translated_tax_details_tax_id` FOREIGN KEY (`tax_id`) REFERENCES `taxes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD CONSTRAINT `fk_users_groups_groups1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_users_groups_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
