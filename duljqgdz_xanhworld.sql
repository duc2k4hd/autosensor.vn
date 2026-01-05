-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost:3306
-- Thời gian đã tạo: Th12 10, 2025 lúc 04:29 PM
-- Phiên bản máy phục vụ: 10.11.14-MariaDB-cll-lve
-- Phiên bản PHP: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `duljqgdz_xanhworld`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `accounts`
--

CREATE TABLE `accounts` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính tự tăng của tài khoản',
  `name` varchar(50) DEFAULT NULL COMMENT 'Tên hiển thị của người dùng',
  `email` varchar(80) NOT NULL COMMENT 'Email đăng nhập duy nhất',
  `phone` varchar(20) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm xác thực email',
  `password` varchar(191) NOT NULL COMMENT 'Mật khẩu đã băm',
  `role` varchar(191) NOT NULL DEFAULT 'user' COMMENT 'Vai trò tài khoản',
  `remember_token` varchar(100) DEFAULT NULL COMMENT 'Token ghi nhớ đăng nhập',
  `last_password_changed_at` timestamp NULL DEFAULT NULL COMMENT 'Lần thay đổi mật khẩu gần nhất',
  `login_attempts` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số lần đăng nhập thất bại',
  `status` enum('active','inactive','suspended','locked','banned') NOT NULL DEFAULT 'active' COMMENT 'Trạng thái tài khoản',
  `admin_note` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `security_flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Các cờ bảo mật bổ sung' CHECK (json_valid(`security_flags`)),
  `login_history` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm đăng nhập gần nhất',
  `logs` text DEFAULT NULL COMMENT 'Ghi chú nội bộ liên quan tới tài khoản',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `accounts`
--

INSERT INTO `accounts` (`id`, `name`, `email`, `phone`, `email_verified_at`, `password`, `role`, `remember_token`, `last_password_changed_at`, `login_attempts`, `status`, `admin_note`, `tags`, `security_flags`, `login_history`, `logs`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Nguyễn Minh Đức ❤️', 'admin@gmail.com', '0827786198', '2025-11-29 00:28:00', '$2y$12$0XX2wqlzmxrueCPko1oIr.WQ/we9pp2cm7RQIN/jsUm1Yhoxg9aDC', 'admin', 'tFRXJfZ6dKOB6zdFowQYmxzIoFF3AiRncgJ1acGmdopwvWMDDwGQjqHlpKzE', '2025-11-29 18:15:20', 0, 'active', NULL, NULL, NULL, NULL, NULL, '2025-11-29 00:28:00', '2025-12-05 02:40:14', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `account_email_verifications`
--

CREATE TABLE `account_email_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính của phiên xác thực email',
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(80) NOT NULL COMMENT 'Mã token gửi qua email',
  `expires_at` timestamp NOT NULL COMMENT 'Thời điểm token hết hạn',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Thời điểm tạo token'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `account_logs`
--

CREATE TABLE `account_logs` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính log',
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(191) NOT NULL COMMENT 'Loại hành động',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dữ liệu chi tiết' CHECK (json_valid(`payload`)),
  `ip` varchar(191) DEFAULT NULL COMMENT 'IP thực hiện',
  `user_agent` varchar(191) DEFAULT NULL COMMENT 'User-agent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(191) NOT NULL COMMENT 'create, update, delete, etc.',
  `model_type` varchar(191) NOT NULL COMMENT 'Product, Order, Account, etc.',
  `model_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dữ liệu cũ (trước khi thay đổi)' CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dữ liệu mới (sau khi thay đổi)' CHECK (json_valid(`new_data`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `action`, `model_type`, `model_id`, `account_id`, `description`, `old_data`, `new_data`, `ip`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'create', 'App\\Models\\Product', 1, 1, 'Tạo sản phẩm mới: Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', NULL, '{\"id\":1,\"sku\":\"XWCPT120525\",\"name\":\"C\\u00e2y Ph\\u00e1t T\\u00e0i \\u2013 C\\u00e2y C\\u1ea3nh Phong Th\\u1ee7y Thu H\\u00fat T\\u00e0i L\\u1ed9c\",\"slug\":\"cay-phat-tai-cay-canh-phong-thuy-thu-hut-tai-loc\",\"description\":\"<h2 data-start=\\\"275\\\" data-end=\\\"335\\\"><strong data-start=\\\"277\\\" data-end=\\\"335\\\">C&Acirc;Y PH&Aacute;T T&Agrave;I &ndash; BI\\u1ec2U T\\u01af\\u1ee2NG T&Agrave;I L\\u1ed8C MAY M\\u1eaeN CHO GIA \\u0110&Igrave;NH<\\/strong><\\/h2>\\r\\n<h3 data-start=\\\"337\\\" data-end=\\\"358\\\"><strong data-start=\\\"340\\\" data-end=\\\"358\\\">M&ocirc; t\\u1ea3 s\\u1ea3n ph\\u1ea9m<\\/strong><\\/h3>\\r\\n<p data-start=\\\"359\\\" data-end=\\\"648\\\">C&acirc;y Ph&aacute;t T&agrave;i l&agrave; d&ograve;ng c&acirc;y c\\u1ea3nh phong th\\u1ee7y \\u0111\\u01b0\\u1ee3c \\u01b0a chu\\u1ed9ng h&agrave;ng \\u0111\\u1ea7u, mang &yacute; ngh\\u0129a thu h&uacute;t v\\u1eadn may, t&agrave;i l\\u1ed9c v&agrave; th\\u1ecbnh v\\u01b0\\u1ee3ng cho gia ch\\u1ee7. Nh\\u1edd \\u0111a d\\u1ea1ng ch\\u1ee7ng lo\\u1ea1i v&agrave; d\\u1ec5 ch\\u0103m s&oacute;c, c&acirc;y ph&ugrave; h\\u1ee3p v\\u1edbi nhi\\u1ec1u kh&ocirc;ng gian nh\\u01b0 nh&agrave; \\u1edf, v\\u0103n ph&ograve;ng, c\\u1eeda h&agrave;ng kinh doanh v&agrave; h\\u1ee3p v\\u1edbi nhi\\u1ec1u m\\u1ec7nh phong th\\u1ee7y kh&aacute;c nhau.<\\/p>\\r\\n<hr data-start=\\\"650\\\" data-end=\\\"653\\\">\\r\\n<h3 data-start=\\\"655\\\" data-end=\\\"678\\\"><strong data-start=\\\"658\\\" data-end=\\\"678\\\">\\u0110\\u1eb6C \\u0110I\\u1ec2M N\\u1ed4I B\\u1eacT<\\/strong><\\/h3>\\r\\n<p data-start=\\\"679\\\" data-end=\\\"926\\\">&bull; D\\u1ec5 tr\\u1ed3ng &ndash; d\\u1ec5 ch\\u0103m s&oacute;c &ndash; ph&aacute;t tri\\u1ec3n t\\u1ed1t trong nh&agrave; l\\u1eabn v\\u0103n ph&ograve;ng<br data-start=\\\"744\\\" data-end=\\\"747\\\">&bull; Kh\\u1ea3 n\\u0103ng thanh l\\u1ecdc kh&ocirc;ng kh&iacute; hi\\u1ec7u qu\\u1ea3<br data-start=\\\"786\\\" data-end=\\\"789\\\">&bull; S\\u1ed1ng kh\\u1ecfe trong m&ocirc;i tr\\u01b0\\u1eddng thi\\u1ebfu s&aacute;ng<br data-start=\\\"828\\\" data-end=\\\"831\\\">&bull; Ph&ugrave; h\\u1ee3p h\\u1ea7u h\\u1ebft c&aacute;c m\\u1ec7nh trong ng\\u0169 h&agrave;nh<br data-start=\\\"872\\\" data-end=\\\"875\\\">&bull; T\\u1ea1o \\u0111i\\u1ec3m nh\\u1ea5n phong th\\u1ee7y &ndash; th\\u1ea9m m\\u1ef9 cho kh&ocirc;ng gian<\\/p>\\r\\n<hr data-start=\\\"928\\\" data-end=\\\"931\\\">\\r\\n<h2 data-start=\\\"933\\\" data-end=\\\"969\\\"><strong data-start=\\\"935\\\" data-end=\\\"969\\\">C&Aacute;C LO\\u1ea0I C&Acirc;Y PH&Aacute;T T&Agrave;I B&Aacute;N CH\\u1ea0Y<\\/strong><\\/h2>\\r\\n<h3 data-start=\\\"971\\\" data-end=\\\"1007\\\"><strong data-start=\\\"974\\\" data-end=\\\"1007\\\">1. C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i (\\u0110\\u1ea1i L\\u1ed9c)<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1008\\\" data-end=\\\"1256\\\"><strong data-start=\\\"1008\\\" data-end=\\\"1021\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> Th&acirc;n to, kh\\u1ecfe, l&aacute; thu&ocirc;n d&agrave;i nh\\u1ecdn nh\\u01b0 c\\u1ecd; hoa v&agrave;ng m\\u1ecdc th&agrave;nh c\\u1ee5m \\u0111\\u1eb9p m\\u1eaft<br data-start=\\\"1093\\\" data-end=\\\"1096\\\"><strong data-start=\\\"1096\\\" data-end=\\\"1111\\\">Phong th\\u1ee7y:<\\/strong> Thu\\u1ed9c h&agrave;nh M\\u1ed9c &ndash; h\\u1ee3p m\\u1ec7nh Th\\u1ee7y, M\\u1ed9c, H\\u1ecfa<br data-start=\\\"1152\\\" data-end=\\\"1155\\\"><strong data-start=\\\"1155\\\" data-end=\\\"1170\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> S&acirc;n v\\u01b0\\u1eddn, ph&ograve;ng kh&aacute;ch, v\\u0103n ph&ograve;ng, khu&ocirc;n vi&ecirc;n nh&agrave;<br data-start=\\\"1219\\\" data-end=\\\"1222\\\"><strong data-start=\\\"1222\\\" data-end=\\\"1234\\\">Gi&aacute; b&aacute;n:<\\/strong> 300.000\\u0111 &ndash; 3.000.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"1258\\\" data-end=\\\"1261\\\">\\r\\n<h3 data-start=\\\"1263\\\" data-end=\\\"1294\\\"><strong data-start=\\\"1266\\\" data-end=\\\"1294\\\">2. C&acirc;y Ph&aacute;t T&agrave;i Ph&aacute;t L\\u1ed9c<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1295\\\" data-end=\\\"1516\\\"><strong data-start=\\\"1295\\\" data-end=\\\"1308\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> Th&acirc;n chia \\u0111\\u1ed1t nh\\u01b0 tre, nh\\u1ecf g\\u1ecdn, d\\u1ec5 u\\u1ed1n d&aacute;ng phong th\\u1ee7y<br data-start=\\\"1363\\\" data-end=\\\"1366\\\"><strong data-start=\\\"1366\\\" data-end=\\\"1381\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh Kim &ndash; mang l\\u1ea1i s\\u1ef1 c&acirc;n b\\u1eb1ng, may m\\u1eafn<br data-start=\\\"1426\\\" data-end=\\\"1429\\\"><strong data-start=\\\"1429\\\" data-end=\\\"1444\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> B&agrave;n l&agrave;m vi\\u1ec7c, ph&ograve;ng h\\u1ecdp, qu\\u1ea7y l\\u1ec5 t&acirc;n<br data-start=\\\"1481\\\" data-end=\\\"1484\\\"><strong data-start=\\\"1484\\\" data-end=\\\"1496\\\">Gi&aacute; b&aacute;n:<\\/strong> 200.000\\u0111 &ndash; 500.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"1518\\\" data-end=\\\"1521\\\">\\r\\n<h3 data-start=\\\"1523\\\" data-end=\\\"1548\\\"><strong data-start=\\\"1526\\\" data-end=\\\"1548\\\">3. C&acirc;y Ph&aacute;t T&agrave;i \\u0110\\u1ecf<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1549\\\" data-end=\\\"1781\\\"><strong data-start=\\\"1549\\\" data-end=\\\"1562\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> L&aacute; xanh khi non, chuy\\u1ec3n \\u0111\\u1ecf n\\u1ed5i b\\u1eadt khi tr\\u01b0\\u1edfng th&agrave;nh<br data-start=\\\"1614\\\" data-end=\\\"1617\\\"><strong data-start=\\\"1617\\\" data-end=\\\"1632\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh H\\u1ecfa &ndash; thu h&uacute;t n\\u0103ng l\\u01b0\\u1ee3ng t&iacute;ch c\\u1ef1c<br data-start=\\\"1675\\\" data-end=\\\"1678\\\"><strong data-start=\\\"1678\\\" data-end=\\\"1693\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> Ph&ograve;ng kh&aacute;ch, ph&ograve;ng th\\u1edd, c&aacute;c v\\u1ecb tr&iacute; trang tr&iacute; n\\u1ed5i b\\u1eadt<br data-start=\\\"1746\\\" data-end=\\\"1749\\\"><strong data-start=\\\"1749\\\" data-end=\\\"1761\\\">Gi&aacute; b&aacute;n:<\\/strong> 100.000\\u0111 &ndash; 150.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"1783\\\" data-end=\\\"1786\\\">\\r\\n<h3 data-start=\\\"1788\\\" data-end=\\\"1818\\\"><strong data-start=\\\"1791\\\" data-end=\\\"1818\\\">4. C&acirc;y Ph&aacute;t T&agrave;i B&uacute;p Sen<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1819\\\" data-end=\\\"2019\\\"><strong data-start=\\\"1819\\\" data-end=\\\"1832\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> L&aacute; xanh \\u0111\\u1eadm, m\\u1ecdc x\\u1ebfp l\\u1edbp nh\\u01b0 b&ocirc;ng sen<br data-start=\\\"1870\\\" data-end=\\\"1873\\\"><strong data-start=\\\"1873\\\" data-end=\\\"1888\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh Kim &ndash; M\\u1ed9c &ndash; Th\\u1ee7y (khi tr\\u1ed3ng th\\u1ee7y sinh)<br data-start=\\\"1936\\\" data-end=\\\"1939\\\"><strong data-start=\\\"1939\\\" data-end=\\\"1954\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> B&agrave;n th\\u1edd Th\\u1ea7n T&agrave;i, b&agrave;n l&agrave;m vi\\u1ec7c<br data-start=\\\"1985\\\" data-end=\\\"1988\\\"><strong data-start=\\\"1988\\\" data-end=\\\"2000\\\">Gi&aacute; b&aacute;n:<\\/strong> 70.000\\u0111 &ndash; 200.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"2021\\\" data-end=\\\"2024\\\">\\r\\n<h3 data-start=\\\"2026\\\" data-end=\\\"2053\\\"><strong data-start=\\\"2029\\\" data-end=\\\"2053\\\">5. C&acirc;y Thi\\u1ebft M\\u1ed9c Lan<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2054\\\" data-end=\\\"2247\\\"><strong data-start=\\\"2054\\\" data-end=\\\"2067\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> T&aacute;n l&aacute; r\\u1eadm, th&acirc;n c\\u1ed9t cao, c&oacute; th\\u1ec3 ra hoa th\\u01a1m nh\\u1eb9<br data-start=\\\"2116\\\" data-end=\\\"2119\\\"><strong data-start=\\\"2119\\\" data-end=\\\"2134\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh M\\u1ed9c v&agrave; H\\u1ecfa<br data-start=\\\"2154\\\" data-end=\\\"2157\\\"><strong data-start=\\\"2157\\\" data-end=\\\"2172\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> Nh&agrave; \\u1edf, v\\u0103n ph&ograve;ng, c\\u1eeda h&agrave;ng kinh doanh<br data-start=\\\"2210\\\" data-end=\\\"2213\\\"><strong data-start=\\\"2213\\\" data-end=\\\"2225\\\">Gi&aacute; b&aacute;n:<\\/strong> 500.000\\u0111 &ndash; 2.000.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"2249\\\" data-end=\\\"2252\\\">\\r\\n<h3 data-start=\\\"2254\\\" data-end=\\\"2276\\\"><strong data-start=\\\"2257\\\" data-end=\\\"2276\\\">6. C&acirc;y Kim Ti\\u1ec1n<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2277\\\" data-end=\\\"2495\\\"><strong data-start=\\\"2277\\\" data-end=\\\"2290\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> L&aacute; \\u0111\\u1ed1i x\\u1ee9ng, b&oacute;ng \\u0111\\u1eb9p, c&agrave;nh v\\u01b0\\u01a1n cao gi&agrave;u sinh kh&iacute;<br data-start=\\\"2341\\\" data-end=\\\"2344\\\"><strong data-start=\\\"2344\\\" data-end=\\\"2359\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh M\\u1ed9c &ndash; H\\u1ecfa, t\\u01b0\\u1ee3ng tr\\u01b0ng cho ti\\u1ec1n t&agrave;i v&agrave; th\\u1ecbnh v\\u01b0\\u1ee3ng<br data-start=\\\"2419\\\" data-end=\\\"2422\\\"><strong data-start=\\\"2422\\\" data-end=\\\"2437\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> H\\u1ea7u h\\u1ebft m\\u1ecdi kh&ocirc;ng gian<br data-start=\\\"2460\\\" data-end=\\\"2463\\\"><strong data-start=\\\"2463\\\" data-end=\\\"2475\\\">Gi&aacute; b&aacute;n:<\\/strong> 150.000\\u0111 &ndash; 500.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"2497\\\" data-end=\\\"2500\\\">\\r\\n<h2 data-start=\\\"2502\\\" data-end=\\\"2539\\\"><strong data-start=\\\"2504\\\" data-end=\\\"2539\\\">H\\u01af\\u1edaNG D\\u1eaaN CH\\u0102M S&Oacute;C C&Acirc;Y PH&Aacute;T T&Agrave;I<\\/strong><\\/h2>\\r\\n<h3 data-start=\\\"2541\\\" data-end=\\\"2563\\\"><strong data-start=\\\"2545\\\" data-end=\\\"2563\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t c&acirc;y<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2564\\\" data-end=\\\"2613\\\">N&ecirc;n \\u0111\\u1eb7t h\\u01b0\\u1edbng \\u0110&ocirc;ng ho\\u1eb7c \\u0110&ocirc;ng Nam theo phong th\\u1ee7y.<\\/p>\\r\\n<h3 data-start=\\\"2615\\\" data-end=\\\"2631\\\"><strong data-start=\\\"2619\\\" data-end=\\\"2631\\\">&Aacute;nh s&aacute;ng<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2632\\\" data-end=\\\"2725\\\">&bull; S\\u1ed1ng t\\u1ed1t trong m&ocirc;i tr\\u01b0\\u1eddng &iacute;t s&aacute;ng<br data-start=\\\"2667\\\" data-end=\\\"2670\\\">&bull; Th\\u1ec9nh tho\\u1ea3ng ph\\u01a1i n\\u1eafng nh\\u1eb9 \\u0111\\u1ec3 c&acirc;y quang h\\u1ee3p t\\u1ed1t h\\u01a1n<\\/p>\\r\\n<h3 data-start=\\\"2727\\\" data-end=\\\"2744\\\"><strong data-start=\\\"2731\\\" data-end=\\\"2744\\\">T\\u01b0\\u1edbi n\\u01b0\\u1edbc<\\/strong><\\/h3>\\r\\n<ul data-start=\\\"2745\\\" data-end=\\\"2878\\\">\\r\\n<li data-start=\\\"2745\\\" data-end=\\\"2790\\\">\\r\\n<p data-start=\\\"2747\\\" data-end=\\\"2790\\\">C&acirc;y trong nh&agrave;: phun s\\u01b0\\u01a1ng, gi\\u1eef \\u0111\\u1ea5t \\u1ea9m nh\\u1eb9<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"2791\\\" data-end=\\\"2828\\\">\\r\\n<p data-start=\\\"2793\\\" data-end=\\\"2828\\\">C&acirc;y ngo&agrave;i tr\\u1eddi: t\\u01b0\\u1edbi \\u0111\\u1ec1u m\\u1ed7i ng&agrave;y<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"2829\\\" data-end=\\\"2878\\\">\\r\\n<p data-start=\\\"2831\\\" data-end=\\\"2878\\\">Th\\u1ee7y sinh: thay n\\u01b0\\u1edbc 3&ndash;4 ng&agrave;y\\/l\\u1ea7n b\\u1eb1ng n\\u01b0\\u1edbc l\\u1ecdc<\\/p>\\r\\n<\\/li>\\r\\n<\\/ul>\\r\\n<h3 data-start=\\\"2880\\\" data-end=\\\"2897\\\"><strong data-start=\\\"2884\\\" data-end=\\\"2897\\\">\\u0110\\u1ea5t tr\\u1ed3ng<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2898\\\" data-end=\\\"2984\\\">\\u0110\\u1ea5t t\\u01a1i x\\u1ed1p, gi&agrave;u dinh d\\u01b0\\u1ee1ng, tho&aacute;ng n\\u01b0\\u1edbc, pH 6&ndash;7. C&oacute; th\\u1ec3 tr\\u1ed9n m&ugrave;n, tr\\u1ea5u, ph&acirc;n h\\u1eefu c\\u01a1.<\\/p>\\r\\n<h3 data-start=\\\"2986\\\" data-end=\\\"3002\\\"><strong data-start=\\\"2990\\\" data-end=\\\"3002\\\">B&oacute;n ph&acirc;n<\\/strong><\\/h3>\\r\\n<p data-start=\\\"3003\\\" data-end=\\\"3056\\\">B&oacute;n h\\u1eefu c\\u01a1 ho\\u1eb7c NPK \\u0111\\u1ecbnh k\\u1ef3 v&agrave;o giai \\u0111o\\u1ea1n ph&aacute;t tri\\u1ec3n.<\\/p>\\r\\n<h3 data-start=\\\"3058\\\" data-end=\\\"3084\\\"><strong data-start=\\\"3062\\\" data-end=\\\"3084\\\">C\\u1eaft t\\u1ec9a &ndash; t\\u1ea1o d&aacute;ng<\\/strong><\\/h3>\\r\\n<p data-start=\\\"3085\\\" data-end=\\\"3136\\\">Lo\\u1ea1i b\\u1ecf l&aacute; h&eacute;o, ch\\u1ec9nh d&aacute;ng \\u0111\\u1ec3 c&acirc;y lu&ocirc;n \\u0111\\u1eb9p v&agrave; kh\\u1ecfe.<\\/p>\\r\\n<h3 data-start=\\\"3138\\\" data-end=\\\"3155\\\"><strong data-start=\\\"3142\\\" data-end=\\\"3155\\\">Thay ch\\u1eadu<\\/strong><\\/h3>\\r\\n<p data-start=\\\"3156\\\" data-end=\\\"3196\\\">Kho\\u1ea3ng 1 n\\u0103m\\/l\\u1ea7n \\u0111\\u1ec3 c&acirc;y ph&aacute;t tri\\u1ec3n m\\u1ea1nh.<\\/p>\\r\\n<hr data-start=\\\"3198\\\" data-end=\\\"3201\\\">\\r\\n<h2 data-start=\\\"3203\\\" data-end=\\\"3227\\\"><strong data-start=\\\"3205\\\" data-end=\\\"3227\\\">&Yacute; NGH\\u0128A PHONG TH\\u1ee6Y<\\/strong><\\/h2>\\r\\n<p data-start=\\\"3228\\\" data-end=\\\"3451\\\">\\u2713 Thu h&uacute;t t&agrave;i l\\u1ed9c &ndash; may m\\u1eafn &ndash; th\\u1ecbnh v\\u01b0\\u1ee3ng<br data-start=\\\"3269\\\" data-end=\\\"3272\\\">\\u2713 Mang l\\u1ea1i b&igrave;nh an cho gia \\u0111&igrave;nh<br data-start=\\\"3303\\\" data-end=\\\"3306\\\">\\u2713 Gi\\u1ea3m c\\u0103ng th\\u1eb3ng, t\\u0103ng n\\u0103ng l\\u01b0\\u1ee3ng t&iacute;ch c\\u1ef1c<br data-start=\\\"3349\\\" data-end=\\\"3352\\\">\\u2713 H\\u1ed7 tr\\u1ee3 c&acirc;n b\\u1eb1ng phong th\\u1ee7y trong kh&ocirc;ng gian<br data-start=\\\"3397\\\" data-end=\\\"3400\\\">\\u2713 C&acirc;y ra hoa \\u0111\\u01b0\\u1ee3c xem l&agrave; d\\u1ea5u hi\\u1ec7u v\\u1eadn may s\\u1eafp \\u0111\\u1ebfn<\\/p>\\r\\n<hr data-start=\\\"3453\\\" data-end=\\\"3456\\\">\\r\\n<h2 data-start=\\\"3458\\\" data-end=\\\"3480\\\"><strong data-start=\\\"3460\\\" data-end=\\\"3480\\\">CAM K\\u1ebeT S\\u1ea2N PH\\u1ea8M<\\/strong><\\/h2>\\r\\n<p data-start=\\\"3481\\\" data-end=\\\"3638\\\">&bull; C&acirc;y kh\\u1ecfe m\\u1ea1nh &ndash; \\u0111&uacute;ng lo\\u1ea1i &ndash; \\u0111&uacute;ng k&iacute;ch th\\u01b0\\u1edbc<br data-start=\\\"3526\\\" data-end=\\\"3529\\\">&bull; Giao h&agrave;ng to&agrave;n qu\\u1ed1c &ndash; \\u0111&oacute;ng g&oacute;i an to&agrave;n<br data-start=\\\"3569\\\" data-end=\\\"3572\\\">&bull; T\\u01b0 v\\u1ea5n ch\\u1ecdn c&acirc;y h\\u1ee3p m\\u1ec7nh mi\\u1ec5n ph&iacute;<br data-start=\\\"3607\\\" data-end=\\\"3610\\\">&bull; H\\u1ed7 tr\\u1ee3 ch\\u0103m s&oacute;c tr\\u1ecdn \\u0111\\u1eddi<\\/p>\\r\\n<hr data-start=\\\"3640\\\" data-end=\\\"3643\\\">\\r\\n<h2 data-start=\\\"3645\\\" data-end=\\\"3664\\\"><strong data-start=\\\"3647\\\" data-end=\\\"3664\\\">L\\u01afU &Yacute; AN TO&Agrave;N<\\/strong><\\/h2>\\r\\n<p data-start=\\\"3665\\\" data-end=\\\"3787\\\">H\\u1ea7u h\\u1ebft c&acirc;y Ph&aacute;t T&agrave;i \\u0111\\u1ec1u an to&agrave;n. M\\u1ed9t s\\u1ed1 lo\\u1ea1i c&oacute; th\\u1ec3 g&acirc;y k&iacute;ch \\u1ee9ng nh\\u1eb9 khi nh\\u1ef1a c&acirc;y d&iacute;nh tay &mdash; n&ecirc;n r\\u1eeda tay sau khi c\\u1eaft t\\u1ec9a.<\\/p>\\r\\n<hr data-start=\\\"3789\\\" data-end=\\\"3792\\\">\\r\\n<h2 data-start=\\\"3794\\\" data-end=\\\"3817\\\"><strong data-start=\\\"3796\\\" data-end=\\\"3817\\\">B\\u1ea2NG GI&Aacute; CHI TI\\u1ebeT<\\/strong><\\/h2>\\r\\n<div class=\\\"TyagGW_tableContainer\\\">\\r\\n<div class=\\\"group TyagGW_tableWrapper flex w-fit flex-col-reverse\\\" tabindex=\\\"-1\\\">\\r\\n<table class=\\\"w-fit min-w-(--thread-content-width)\\\" data-start=\\\"3819\\\" data-end=\\\"4149\\\">\\r\\n<thead data-start=\\\"3819\\\" data-end=\\\"3850\\\">\\r\\n<tr data-start=\\\"3819\\\" data-end=\\\"3850\\\">\\r\\n<th data-start=\\\"3819\\\" data-end=\\\"3830\\\" data-col-size=\\\"sm\\\">Lo\\u1ea1i c&acirc;y<\\/th>\\r\\n<th data-start=\\\"3830\\\" data-end=\\\"3839\\\" data-col-size=\\\"sm\\\">Gi&aacute; t\\u1eeb<\\/th>\\r\\n<th data-start=\\\"3839\\\" data-end=\\\"3850\\\" data-col-size=\\\"sm\\\">Gi&aacute; \\u0111\\u1ebfn<\\/th>\\r\\n<\\/tr>\\r\\n<\\/thead>\\r\\n<tbody data-start=\\\"3884\\\" data-end=\\\"4149\\\">\\r\\n<tr data-start=\\\"3884\\\" data-end=\\\"3928\\\">\\r\\n<td data-start=\\\"3884\\\" data-end=\\\"3903\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3903\\\" data-end=\\\"3914\\\">300.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3914\\\" data-end=\\\"3928\\\">3.000.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"3929\\\" data-end=\\\"3976\\\">\\r\\n<td data-start=\\\"3929\\\" data-end=\\\"3953\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i Ph&aacute;t L\\u1ed9c<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3953\\\" data-end=\\\"3964\\\">200.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3964\\\" data-end=\\\"3976\\\">500.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"3977\\\" data-end=\\\"4018\\\">\\r\\n<td data-start=\\\"3977\\\" data-end=\\\"3995\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i \\u0110\\u1ecf<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3995\\\" data-end=\\\"4006\\\">100.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4006\\\" data-end=\\\"4018\\\">150.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"4019\\\" data-end=\\\"4064\\\">\\r\\n<td data-start=\\\"4019\\\" data-end=\\\"4042\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i B&uacute;p Sen<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4042\\\" data-end=\\\"4052\\\">70.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4052\\\" data-end=\\\"4064\\\">200.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"4065\\\" data-end=\\\"4110\\\">\\r\\n<td data-start=\\\"4065\\\" data-end=\\\"4085\\\" data-col-size=\\\"sm\\\">C&acirc;y Thi\\u1ebft M\\u1ed9c Lan<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4085\\\" data-end=\\\"4096\\\">500.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4096\\\" data-end=\\\"4110\\\">2.000.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"4111\\\" data-end=\\\"4149\\\">\\r\\n<td data-start=\\\"4111\\\" data-end=\\\"4126\\\" data-col-size=\\\"sm\\\">C&acirc;y Kim Ti\\u1ec1n<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4126\\\" data-end=\\\"4137\\\">150.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4137\\\" data-end=\\\"4149\\\">500.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<\\/tbody>\\r\\n<\\/table>\\r\\n<\\/div>\\r\\n<\\/div>\\r\\n<p data-start=\\\"4151\\\" data-end=\\\"4202\\\"><em data-start=\\\"4151\\\" data-end=\\\"4202\\\">(Gi&aacute; c&oacute; th\\u1ec3 thay \\u0111\\u1ed5i t&ugrave;y k&iacute;ch th\\u01b0\\u1edbc v&agrave; tu\\u1ed5i c&acirc;y.)<\\/em><\\/p>\\r\\n<hr data-start=\\\"4204\\\" data-end=\\\"4207\\\">\\r\\n<h2 data-start=\\\"4209\\\" data-end=\\\"4240\\\"><strong data-start=\\\"4211\\\" data-end=\\\"4240\\\">T\\u01af V\\u1ea4N CH\\u1eccN C&Acirc;Y THEO M\\u1ec6NH<\\/strong><\\/h2>\\r\\n<ul data-start=\\\"4242\\\" data-end=\\\"4419\\\">\\r\\n<li data-start=\\\"4242\\\" data-end=\\\"4277\\\">\\r\\n<p data-start=\\\"4244\\\" data-end=\\\"4277\\\"><strong data-start=\\\"4244\\\" data-end=\\\"4258\\\">M\\u1ec7nh Th\\u1ee7y:<\\/strong> C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"4278\\\" data-end=\\\"4328\\\">\\r\\n<p data-start=\\\"4280\\\" data-end=\\\"4328\\\"><strong data-start=\\\"4280\\\" data-end=\\\"4293\\\">M\\u1ec7nh M\\u1ed9c:<\\/strong> B&uacute;p Sen, Kim Ti\\u1ec1n, Thi\\u1ebft M\\u1ed9c Lan<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"4329\\\" data-end=\\\"4383\\\">\\r\\n<p data-start=\\\"4331\\\" data-end=\\\"4383\\\"><strong data-start=\\\"4331\\\" data-end=\\\"4344\\\">M\\u1ec7nh H\\u1ecfa:<\\/strong> Ph&aacute;t T&agrave;i \\u0110\\u1ecf, Kim Ti\\u1ec1n, Thi\\u1ebft M\\u1ed9c Lan<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"4384\\\" data-end=\\\"4419\\\">\\r\\n<p data-start=\\\"4386\\\" data-end=\\\"4419\\\"><strong data-start=\\\"4386\\\" data-end=\\\"4399\\\">M\\u1ec7nh Kim:<\\/strong> Ph&aacute;t L\\u1ed9c, B&uacute;p Sen<\\/p>\\r\\n<\\/li>\\r\\n<\\/ul>\",\"short_description\":null,\"price\":\"970000.00\",\"sale_price\":\"899000.00\",\"cost_price\":\"500000.00\",\"stock_quantity\":100,\"meta_title\":\"C\\u00e2y ph\\u00e1t t\\u00e0i c\\u1ea3nh phong th\\u1ee7y \\u2013 thu h\\u00fat t\\u00e0i l\\u1ed9c, may m\\u1eafn, ph\\u00f9 h\\u1ee3p m\\u1ecdi kh\\u00f4ng gian\",\"meta_description\":\"C\\u00e2y Ph\\u00e1t T\\u00e0i mang \\u00fd ngh\\u0129a thu h\\u00fat t\\u00e0i l\\u1ed9c, may m\\u1eafn v\\u00e0 th\\u1ecbnh v\\u01b0\\u1ee3ng. C\\u00e2y d\\u1ec5 ch\\u0103m s\\u00f3c, ph\\u00f9 h\\u1ee3p trang tr\\u00ed nh\\u00e0 \\u2013 v\\u0103n ph\\u00f2ng. Gi\\u00e1 t\\u1ed1t, giao h\\u00e0ng to\\u00e0n qu\\u1ed1c.\",\"meta_keywords\":[\"c\\u00e2y ph\\u00e1t t\\u00e0i\",\"c\\u00e2y phong th\\u1ee7y ph\\u00e1t t\\u00e0i\",\"c\\u00e2y ph\\u00e1t t\\u00e0i n\\u00fai\",\"c\\u00e2y ph\\u00e1t t\\u00e0i ph\\u00e1t l\\u1ed9c\",\"c\\u00e2y ph\\u00e1t t\\u00e0i \\u0111\\u1ecf\",\"c\\u00e2y ph\\u00e1t t\\u00e0i b\\u00fap sen\",\"thi\\u1ebft m\\u1ed9c lan\",\"c\\u00e2y kim ti\\u1ec1n\",\"c\\u00e2y c\\u1ea3nh phong th\\u1ee7y\",\"mua c\\u00e2y ph\\u00e1t t\\u00e0i\"],\"meta_canonical\":\"http:\\/\\/127.0.0.1:8000\\/san-pham\\/cay-phat-tai-cay-canh-phong-thuy-thu-hut-tai-loc\",\"primary_category_id\":\"2\",\"category_included_ids\":null,\"category_ids\":[\"1\",\"9\",\"6\",\"14\",\"15\",\"7\",\"8\",\"2\",\"13\",\"12\",\"16\",\"17\"],\"tag_ids\":[1,2,3,4,5,6,7,8,9,10],\"image_ids\":[1,2,3,4],\"is_featured\":true,\"locked_by\":null,\"locked_at\":null,\"created_by\":\"1\",\"is_active\":true,\"category_ids_backup\":null,\"created_at\":\"2025-12-05T07:43:56.000000Z\",\"updated_at\":\"2025-12-05T07:43:56.000000Z\"}', '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 07:43:56', '2025-12-05 07:43:56');
INSERT INTO `activity_logs` (`id`, `action`, `model_type`, `model_id`, `account_id`, `description`, `old_data`, `new_data`, `ip`, `user_agent`, `created_at`, `updated_at`) VALUES
(2, 'update', 'App\\Models\\Product', 1, 1, 'Cập nhật sản phẩm: Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', '{\"id\":1,\"sku\":\"XWCPT120525\",\"name\":\"C\\u00e2y Ph\\u00e1t T\\u00e0i \\u2013 C\\u00e2y C\\u1ea3nh Phong Th\\u1ee7y Thu H\\u00fat T\\u00e0i L\\u1ed9c\",\"slug\":\"cay-phat-tai-cay-canh-phong-thuy-thu-hut-tai-loc\",\"description\":\"<h2 data-start=\\\"275\\\" data-end=\\\"335\\\"><strong data-start=\\\"277\\\" data-end=\\\"335\\\">C&Acirc;Y PH&Aacute;T T&Agrave;I &ndash; BI\\u1ec2U T\\u01af\\u1ee2NG T&Agrave;I L\\u1ed8C MAY M\\u1eaeN CHO GIA \\u0110&Igrave;NH<\\/strong><\\/h2>\\r\\n<h3 data-start=\\\"337\\\" data-end=\\\"358\\\"><strong data-start=\\\"340\\\" data-end=\\\"358\\\">M&ocirc; t\\u1ea3 s\\u1ea3n ph\\u1ea9m<\\/strong><\\/h3>\\r\\n<p data-start=\\\"359\\\" data-end=\\\"648\\\">C&acirc;y Ph&aacute;t T&agrave;i l&agrave; d&ograve;ng c&acirc;y c\\u1ea3nh phong th\\u1ee7y \\u0111\\u01b0\\u1ee3c \\u01b0a chu\\u1ed9ng h&agrave;ng \\u0111\\u1ea7u, mang &yacute; ngh\\u0129a thu h&uacute;t v\\u1eadn may, t&agrave;i l\\u1ed9c v&agrave; th\\u1ecbnh v\\u01b0\\u1ee3ng cho gia ch\\u1ee7. Nh\\u1edd \\u0111a d\\u1ea1ng ch\\u1ee7ng lo\\u1ea1i v&agrave; d\\u1ec5 ch\\u0103m s&oacute;c, c&acirc;y ph&ugrave; h\\u1ee3p v\\u1edbi nhi\\u1ec1u kh&ocirc;ng gian nh\\u01b0 nh&agrave; \\u1edf, v\\u0103n ph&ograve;ng, c\\u1eeda h&agrave;ng kinh doanh v&agrave; h\\u1ee3p v\\u1edbi nhi\\u1ec1u m\\u1ec7nh phong th\\u1ee7y kh&aacute;c nhau.<\\/p>\\r\\n<hr data-start=\\\"650\\\" data-end=\\\"653\\\">\\r\\n<h3 data-start=\\\"655\\\" data-end=\\\"678\\\"><strong data-start=\\\"658\\\" data-end=\\\"678\\\">\\u0110\\u1eb6C \\u0110I\\u1ec2M N\\u1ed4I B\\u1eacT<\\/strong><\\/h3>\\r\\n<p data-start=\\\"679\\\" data-end=\\\"926\\\">&bull; D\\u1ec5 tr\\u1ed3ng &ndash; d\\u1ec5 ch\\u0103m s&oacute;c &ndash; ph&aacute;t tri\\u1ec3n t\\u1ed1t trong nh&agrave; l\\u1eabn v\\u0103n ph&ograve;ng<br data-start=\\\"744\\\" data-end=\\\"747\\\">&bull; Kh\\u1ea3 n\\u0103ng thanh l\\u1ecdc kh&ocirc;ng kh&iacute; hi\\u1ec7u qu\\u1ea3<br data-start=\\\"786\\\" data-end=\\\"789\\\">&bull; S\\u1ed1ng kh\\u1ecfe trong m&ocirc;i tr\\u01b0\\u1eddng thi\\u1ebfu s&aacute;ng<br data-start=\\\"828\\\" data-end=\\\"831\\\">&bull; Ph&ugrave; h\\u1ee3p h\\u1ea7u h\\u1ebft c&aacute;c m\\u1ec7nh trong ng\\u0169 h&agrave;nh<br data-start=\\\"872\\\" data-end=\\\"875\\\">&bull; T\\u1ea1o \\u0111i\\u1ec3m nh\\u1ea5n phong th\\u1ee7y &ndash; th\\u1ea9m m\\u1ef9 cho kh&ocirc;ng gian<\\/p>\\r\\n<hr data-start=\\\"928\\\" data-end=\\\"931\\\">\\r\\n<h2 data-start=\\\"933\\\" data-end=\\\"969\\\"><strong data-start=\\\"935\\\" data-end=\\\"969\\\">C&Aacute;C LO\\u1ea0I C&Acirc;Y PH&Aacute;T T&Agrave;I B&Aacute;N CH\\u1ea0Y<\\/strong><\\/h2>\\r\\n<h3 data-start=\\\"971\\\" data-end=\\\"1007\\\"><strong data-start=\\\"974\\\" data-end=\\\"1007\\\">1. C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i (\\u0110\\u1ea1i L\\u1ed9c)<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1008\\\" data-end=\\\"1256\\\"><strong data-start=\\\"1008\\\" data-end=\\\"1021\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> Th&acirc;n to, kh\\u1ecfe, l&aacute; thu&ocirc;n d&agrave;i nh\\u1ecdn nh\\u01b0 c\\u1ecd; hoa v&agrave;ng m\\u1ecdc th&agrave;nh c\\u1ee5m \\u0111\\u1eb9p m\\u1eaft<br data-start=\\\"1093\\\" data-end=\\\"1096\\\"><strong data-start=\\\"1096\\\" data-end=\\\"1111\\\">Phong th\\u1ee7y:<\\/strong> Thu\\u1ed9c h&agrave;nh M\\u1ed9c &ndash; h\\u1ee3p m\\u1ec7nh Th\\u1ee7y, M\\u1ed9c, H\\u1ecfa<br data-start=\\\"1152\\\" data-end=\\\"1155\\\"><strong data-start=\\\"1155\\\" data-end=\\\"1170\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> S&acirc;n v\\u01b0\\u1eddn, ph&ograve;ng kh&aacute;ch, v\\u0103n ph&ograve;ng, khu&ocirc;n vi&ecirc;n nh&agrave;<br data-start=\\\"1219\\\" data-end=\\\"1222\\\"><strong data-start=\\\"1222\\\" data-end=\\\"1234\\\">Gi&aacute; b&aacute;n:<\\/strong> 300.000\\u0111 &ndash; 3.000.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"1258\\\" data-end=\\\"1261\\\">\\r\\n<h3 data-start=\\\"1263\\\" data-end=\\\"1294\\\"><strong data-start=\\\"1266\\\" data-end=\\\"1294\\\">2. C&acirc;y Ph&aacute;t T&agrave;i Ph&aacute;t L\\u1ed9c<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1295\\\" data-end=\\\"1516\\\"><strong data-start=\\\"1295\\\" data-end=\\\"1308\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> Th&acirc;n chia \\u0111\\u1ed1t nh\\u01b0 tre, nh\\u1ecf g\\u1ecdn, d\\u1ec5 u\\u1ed1n d&aacute;ng phong th\\u1ee7y<br data-start=\\\"1363\\\" data-end=\\\"1366\\\"><strong data-start=\\\"1366\\\" data-end=\\\"1381\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh Kim &ndash; mang l\\u1ea1i s\\u1ef1 c&acirc;n b\\u1eb1ng, may m\\u1eafn<br data-start=\\\"1426\\\" data-end=\\\"1429\\\"><strong data-start=\\\"1429\\\" data-end=\\\"1444\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> B&agrave;n l&agrave;m vi\\u1ec7c, ph&ograve;ng h\\u1ecdp, qu\\u1ea7y l\\u1ec5 t&acirc;n<br data-start=\\\"1481\\\" data-end=\\\"1484\\\"><strong data-start=\\\"1484\\\" data-end=\\\"1496\\\">Gi&aacute; b&aacute;n:<\\/strong> 200.000\\u0111 &ndash; 500.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"1518\\\" data-end=\\\"1521\\\">\\r\\n<h3 data-start=\\\"1523\\\" data-end=\\\"1548\\\"><strong data-start=\\\"1526\\\" data-end=\\\"1548\\\">3. C&acirc;y Ph&aacute;t T&agrave;i \\u0110\\u1ecf<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1549\\\" data-end=\\\"1781\\\"><strong data-start=\\\"1549\\\" data-end=\\\"1562\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> L&aacute; xanh khi non, chuy\\u1ec3n \\u0111\\u1ecf n\\u1ed5i b\\u1eadt khi tr\\u01b0\\u1edfng th&agrave;nh<br data-start=\\\"1614\\\" data-end=\\\"1617\\\"><strong data-start=\\\"1617\\\" data-end=\\\"1632\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh H\\u1ecfa &ndash; thu h&uacute;t n\\u0103ng l\\u01b0\\u1ee3ng t&iacute;ch c\\u1ef1c<br data-start=\\\"1675\\\" data-end=\\\"1678\\\"><strong data-start=\\\"1678\\\" data-end=\\\"1693\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> Ph&ograve;ng kh&aacute;ch, ph&ograve;ng th\\u1edd, c&aacute;c v\\u1ecb tr&iacute; trang tr&iacute; n\\u1ed5i b\\u1eadt<br data-start=\\\"1746\\\" data-end=\\\"1749\\\"><strong data-start=\\\"1749\\\" data-end=\\\"1761\\\">Gi&aacute; b&aacute;n:<\\/strong> 100.000\\u0111 &ndash; 150.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"1783\\\" data-end=\\\"1786\\\">\\r\\n<h3 data-start=\\\"1788\\\" data-end=\\\"1818\\\"><strong data-start=\\\"1791\\\" data-end=\\\"1818\\\">4. C&acirc;y Ph&aacute;t T&agrave;i B&uacute;p Sen<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1819\\\" data-end=\\\"2019\\\"><strong data-start=\\\"1819\\\" data-end=\\\"1832\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> L&aacute; xanh \\u0111\\u1eadm, m\\u1ecdc x\\u1ebfp l\\u1edbp nh\\u01b0 b&ocirc;ng sen<br data-start=\\\"1870\\\" data-end=\\\"1873\\\"><strong data-start=\\\"1873\\\" data-end=\\\"1888\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh Kim &ndash; M\\u1ed9c &ndash; Th\\u1ee7y (khi tr\\u1ed3ng th\\u1ee7y sinh)<br data-start=\\\"1936\\\" data-end=\\\"1939\\\"><strong data-start=\\\"1939\\\" data-end=\\\"1954\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> B&agrave;n th\\u1edd Th\\u1ea7n T&agrave;i, b&agrave;n l&agrave;m vi\\u1ec7c<br data-start=\\\"1985\\\" data-end=\\\"1988\\\"><strong data-start=\\\"1988\\\" data-end=\\\"2000\\\">Gi&aacute; b&aacute;n:<\\/strong> 70.000\\u0111 &ndash; 200.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"2021\\\" data-end=\\\"2024\\\">\\r\\n<h3 data-start=\\\"2026\\\" data-end=\\\"2053\\\"><strong data-start=\\\"2029\\\" data-end=\\\"2053\\\">5. C&acirc;y Thi\\u1ebft M\\u1ed9c Lan<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2054\\\" data-end=\\\"2247\\\"><strong data-start=\\\"2054\\\" data-end=\\\"2067\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> T&aacute;n l&aacute; r\\u1eadm, th&acirc;n c\\u1ed9t cao, c&oacute; th\\u1ec3 ra hoa th\\u01a1m nh\\u1eb9<br data-start=\\\"2116\\\" data-end=\\\"2119\\\"><strong data-start=\\\"2119\\\" data-end=\\\"2134\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh M\\u1ed9c v&agrave; H\\u1ecfa<br data-start=\\\"2154\\\" data-end=\\\"2157\\\"><strong data-start=\\\"2157\\\" data-end=\\\"2172\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> Nh&agrave; \\u1edf, v\\u0103n ph&ograve;ng, c\\u1eeda h&agrave;ng kinh doanh<br data-start=\\\"2210\\\" data-end=\\\"2213\\\"><strong data-start=\\\"2213\\\" data-end=\\\"2225\\\">Gi&aacute; b&aacute;n:<\\/strong> 500.000\\u0111 &ndash; 2.000.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"2249\\\" data-end=\\\"2252\\\">\\r\\n<h3 data-start=\\\"2254\\\" data-end=\\\"2276\\\"><strong data-start=\\\"2257\\\" data-end=\\\"2276\\\">6. C&acirc;y Kim Ti\\u1ec1n<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2277\\\" data-end=\\\"2495\\\"><strong data-start=\\\"2277\\\" data-end=\\\"2290\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> L&aacute; \\u0111\\u1ed1i x\\u1ee9ng, b&oacute;ng \\u0111\\u1eb9p, c&agrave;nh v\\u01b0\\u01a1n cao gi&agrave;u sinh kh&iacute;<br data-start=\\\"2341\\\" data-end=\\\"2344\\\"><strong data-start=\\\"2344\\\" data-end=\\\"2359\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh M\\u1ed9c &ndash; H\\u1ecfa, t\\u01b0\\u1ee3ng tr\\u01b0ng cho ti\\u1ec1n t&agrave;i v&agrave; th\\u1ecbnh v\\u01b0\\u1ee3ng<br data-start=\\\"2419\\\" data-end=\\\"2422\\\"><strong data-start=\\\"2422\\\" data-end=\\\"2437\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> H\\u1ea7u h\\u1ebft m\\u1ecdi kh&ocirc;ng gian<br data-start=\\\"2460\\\" data-end=\\\"2463\\\"><strong data-start=\\\"2463\\\" data-end=\\\"2475\\\">Gi&aacute; b&aacute;n:<\\/strong> 150.000\\u0111 &ndash; 500.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"2497\\\" data-end=\\\"2500\\\">\\r\\n<h2 data-start=\\\"2502\\\" data-end=\\\"2539\\\"><strong data-start=\\\"2504\\\" data-end=\\\"2539\\\">H\\u01af\\u1edaNG D\\u1eaaN CH\\u0102M S&Oacute;C C&Acirc;Y PH&Aacute;T T&Agrave;I<\\/strong><\\/h2>\\r\\n<h3 data-start=\\\"2541\\\" data-end=\\\"2563\\\"><strong data-start=\\\"2545\\\" data-end=\\\"2563\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t c&acirc;y<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2564\\\" data-end=\\\"2613\\\">N&ecirc;n \\u0111\\u1eb7t h\\u01b0\\u1edbng \\u0110&ocirc;ng ho\\u1eb7c \\u0110&ocirc;ng Nam theo phong th\\u1ee7y.<\\/p>\\r\\n<h3 data-start=\\\"2615\\\" data-end=\\\"2631\\\"><strong data-start=\\\"2619\\\" data-end=\\\"2631\\\">&Aacute;nh s&aacute;ng<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2632\\\" data-end=\\\"2725\\\">&bull; S\\u1ed1ng t\\u1ed1t trong m&ocirc;i tr\\u01b0\\u1eddng &iacute;t s&aacute;ng<br data-start=\\\"2667\\\" data-end=\\\"2670\\\">&bull; Th\\u1ec9nh tho\\u1ea3ng ph\\u01a1i n\\u1eafng nh\\u1eb9 \\u0111\\u1ec3 c&acirc;y quang h\\u1ee3p t\\u1ed1t h\\u01a1n<\\/p>\\r\\n<h3 data-start=\\\"2727\\\" data-end=\\\"2744\\\"><strong data-start=\\\"2731\\\" data-end=\\\"2744\\\">T\\u01b0\\u1edbi n\\u01b0\\u1edbc<\\/strong><\\/h3>\\r\\n<ul data-start=\\\"2745\\\" data-end=\\\"2878\\\">\\r\\n<li data-start=\\\"2745\\\" data-end=\\\"2790\\\">\\r\\n<p data-start=\\\"2747\\\" data-end=\\\"2790\\\">C&acirc;y trong nh&agrave;: phun s\\u01b0\\u01a1ng, gi\\u1eef \\u0111\\u1ea5t \\u1ea9m nh\\u1eb9<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"2791\\\" data-end=\\\"2828\\\">\\r\\n<p data-start=\\\"2793\\\" data-end=\\\"2828\\\">C&acirc;y ngo&agrave;i tr\\u1eddi: t\\u01b0\\u1edbi \\u0111\\u1ec1u m\\u1ed7i ng&agrave;y<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"2829\\\" data-end=\\\"2878\\\">\\r\\n<p data-start=\\\"2831\\\" data-end=\\\"2878\\\">Th\\u1ee7y sinh: thay n\\u01b0\\u1edbc 3&ndash;4 ng&agrave;y\\/l\\u1ea7n b\\u1eb1ng n\\u01b0\\u1edbc l\\u1ecdc<\\/p>\\r\\n<\\/li>\\r\\n<\\/ul>\\r\\n<h3 data-start=\\\"2880\\\" data-end=\\\"2897\\\"><strong data-start=\\\"2884\\\" data-end=\\\"2897\\\">\\u0110\\u1ea5t tr\\u1ed3ng<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2898\\\" data-end=\\\"2984\\\">\\u0110\\u1ea5t t\\u01a1i x\\u1ed1p, gi&agrave;u dinh d\\u01b0\\u1ee1ng, tho&aacute;ng n\\u01b0\\u1edbc, pH 6&ndash;7. C&oacute; th\\u1ec3 tr\\u1ed9n m&ugrave;n, tr\\u1ea5u, ph&acirc;n h\\u1eefu c\\u01a1.<\\/p>\\r\\n<h3 data-start=\\\"2986\\\" data-end=\\\"3002\\\"><strong data-start=\\\"2990\\\" data-end=\\\"3002\\\">B&oacute;n ph&acirc;n<\\/strong><\\/h3>\\r\\n<p data-start=\\\"3003\\\" data-end=\\\"3056\\\">B&oacute;n h\\u1eefu c\\u01a1 ho\\u1eb7c NPK \\u0111\\u1ecbnh k\\u1ef3 v&agrave;o giai \\u0111o\\u1ea1n ph&aacute;t tri\\u1ec3n.<\\/p>\\r\\n<h3 data-start=\\\"3058\\\" data-end=\\\"3084\\\"><strong data-start=\\\"3062\\\" data-end=\\\"3084\\\">C\\u1eaft t\\u1ec9a &ndash; t\\u1ea1o d&aacute;ng<\\/strong><\\/h3>\\r\\n<p data-start=\\\"3085\\\" data-end=\\\"3136\\\">Lo\\u1ea1i b\\u1ecf l&aacute; h&eacute;o, ch\\u1ec9nh d&aacute;ng \\u0111\\u1ec3 c&acirc;y lu&ocirc;n \\u0111\\u1eb9p v&agrave; kh\\u1ecfe.<\\/p>\\r\\n<h3 data-start=\\\"3138\\\" data-end=\\\"3155\\\"><strong data-start=\\\"3142\\\" data-end=\\\"3155\\\">Thay ch\\u1eadu<\\/strong><\\/h3>\\r\\n<p data-start=\\\"3156\\\" data-end=\\\"3196\\\">Kho\\u1ea3ng 1 n\\u0103m\\/l\\u1ea7n \\u0111\\u1ec3 c&acirc;y ph&aacute;t tri\\u1ec3n m\\u1ea1nh.<\\/p>\\r\\n<hr data-start=\\\"3198\\\" data-end=\\\"3201\\\">\\r\\n<h2 data-start=\\\"3203\\\" data-end=\\\"3227\\\"><strong data-start=\\\"3205\\\" data-end=\\\"3227\\\">&Yacute; NGH\\u0128A PHONG TH\\u1ee6Y<\\/strong><\\/h2>\\r\\n<p data-start=\\\"3228\\\" data-end=\\\"3451\\\">\\u2713 Thu h&uacute;t t&agrave;i l\\u1ed9c &ndash; may m\\u1eafn &ndash; th\\u1ecbnh v\\u01b0\\u1ee3ng<br data-start=\\\"3269\\\" data-end=\\\"3272\\\">\\u2713 Mang l\\u1ea1i b&igrave;nh an cho gia \\u0111&igrave;nh<br data-start=\\\"3303\\\" data-end=\\\"3306\\\">\\u2713 Gi\\u1ea3m c\\u0103ng th\\u1eb3ng, t\\u0103ng n\\u0103ng l\\u01b0\\u1ee3ng t&iacute;ch c\\u1ef1c<br data-start=\\\"3349\\\" data-end=\\\"3352\\\">\\u2713 H\\u1ed7 tr\\u1ee3 c&acirc;n b\\u1eb1ng phong th\\u1ee7y trong kh&ocirc;ng gian<br data-start=\\\"3397\\\" data-end=\\\"3400\\\">\\u2713 C&acirc;y ra hoa \\u0111\\u01b0\\u1ee3c xem l&agrave; d\\u1ea5u hi\\u1ec7u v\\u1eadn may s\\u1eafp \\u0111\\u1ebfn<\\/p>\\r\\n<hr data-start=\\\"3453\\\" data-end=\\\"3456\\\">\\r\\n<h2 data-start=\\\"3458\\\" data-end=\\\"3480\\\"><strong data-start=\\\"3460\\\" data-end=\\\"3480\\\">CAM K\\u1ebeT S\\u1ea2N PH\\u1ea8M<\\/strong><\\/h2>\\r\\n<p data-start=\\\"3481\\\" data-end=\\\"3638\\\">&bull; C&acirc;y kh\\u1ecfe m\\u1ea1nh &ndash; \\u0111&uacute;ng lo\\u1ea1i &ndash; \\u0111&uacute;ng k&iacute;ch th\\u01b0\\u1edbc<br data-start=\\\"3526\\\" data-end=\\\"3529\\\">&bull; Giao h&agrave;ng to&agrave;n qu\\u1ed1c &ndash; \\u0111&oacute;ng g&oacute;i an to&agrave;n<br data-start=\\\"3569\\\" data-end=\\\"3572\\\">&bull; T\\u01b0 v\\u1ea5n ch\\u1ecdn c&acirc;y h\\u1ee3p m\\u1ec7nh mi\\u1ec5n ph&iacute;<br data-start=\\\"3607\\\" data-end=\\\"3610\\\">&bull; H\\u1ed7 tr\\u1ee3 ch\\u0103m s&oacute;c tr\\u1ecdn \\u0111\\u1eddi<\\/p>\\r\\n<hr data-start=\\\"3640\\\" data-end=\\\"3643\\\">\\r\\n<h2 data-start=\\\"3645\\\" data-end=\\\"3664\\\"><strong data-start=\\\"3647\\\" data-end=\\\"3664\\\">L\\u01afU &Yacute; AN TO&Agrave;N<\\/strong><\\/h2>\\r\\n<p data-start=\\\"3665\\\" data-end=\\\"3787\\\">H\\u1ea7u h\\u1ebft c&acirc;y Ph&aacute;t T&agrave;i \\u0111\\u1ec1u an to&agrave;n. M\\u1ed9t s\\u1ed1 lo\\u1ea1i c&oacute; th\\u1ec3 g&acirc;y k&iacute;ch \\u1ee9ng nh\\u1eb9 khi nh\\u1ef1a c&acirc;y d&iacute;nh tay &mdash; n&ecirc;n r\\u1eeda tay sau khi c\\u1eaft t\\u1ec9a.<\\/p>\\r\\n<hr data-start=\\\"3789\\\" data-end=\\\"3792\\\">\\r\\n<h2 data-start=\\\"3794\\\" data-end=\\\"3817\\\"><strong data-start=\\\"3796\\\" data-end=\\\"3817\\\">B\\u1ea2NG GI&Aacute; CHI TI\\u1ebeT<\\/strong><\\/h2>\\r\\n<div class=\\\"TyagGW_tableContainer\\\">\\r\\n<div class=\\\"group TyagGW_tableWrapper flex w-fit flex-col-reverse\\\" tabindex=\\\"-1\\\">\\r\\n<table class=\\\"w-fit min-w-(--thread-content-width)\\\" data-start=\\\"3819\\\" data-end=\\\"4149\\\">\\r\\n<thead data-start=\\\"3819\\\" data-end=\\\"3850\\\">\\r\\n<tr data-start=\\\"3819\\\" data-end=\\\"3850\\\">\\r\\n<th data-start=\\\"3819\\\" data-end=\\\"3830\\\" data-col-size=\\\"sm\\\">Lo\\u1ea1i c&acirc;y<\\/th>\\r\\n<th data-start=\\\"3830\\\" data-end=\\\"3839\\\" data-col-size=\\\"sm\\\">Gi&aacute; t\\u1eeb<\\/th>\\r\\n<th data-start=\\\"3839\\\" data-end=\\\"3850\\\" data-col-size=\\\"sm\\\">Gi&aacute; \\u0111\\u1ebfn<\\/th>\\r\\n<\\/tr>\\r\\n<\\/thead>\\r\\n<tbody data-start=\\\"3884\\\" data-end=\\\"4149\\\">\\r\\n<tr data-start=\\\"3884\\\" data-end=\\\"3928\\\">\\r\\n<td data-start=\\\"3884\\\" data-end=\\\"3903\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3903\\\" data-end=\\\"3914\\\">300.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3914\\\" data-end=\\\"3928\\\">3.000.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"3929\\\" data-end=\\\"3976\\\">\\r\\n<td data-start=\\\"3929\\\" data-end=\\\"3953\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i Ph&aacute;t L\\u1ed9c<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3953\\\" data-end=\\\"3964\\\">200.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3964\\\" data-end=\\\"3976\\\">500.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"3977\\\" data-end=\\\"4018\\\">\\r\\n<td data-start=\\\"3977\\\" data-end=\\\"3995\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i \\u0110\\u1ecf<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3995\\\" data-end=\\\"4006\\\">100.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4006\\\" data-end=\\\"4018\\\">150.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"4019\\\" data-end=\\\"4064\\\">\\r\\n<td data-start=\\\"4019\\\" data-end=\\\"4042\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i B&uacute;p Sen<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4042\\\" data-end=\\\"4052\\\">70.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4052\\\" data-end=\\\"4064\\\">200.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"4065\\\" data-end=\\\"4110\\\">\\r\\n<td data-start=\\\"4065\\\" data-end=\\\"4085\\\" data-col-size=\\\"sm\\\">C&acirc;y Thi\\u1ebft M\\u1ed9c Lan<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4085\\\" data-end=\\\"4096\\\">500.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4096\\\" data-end=\\\"4110\\\">2.000.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"4111\\\" data-end=\\\"4149\\\">\\r\\n<td data-start=\\\"4111\\\" data-end=\\\"4126\\\" data-col-size=\\\"sm\\\">C&acirc;y Kim Ti\\u1ec1n<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4126\\\" data-end=\\\"4137\\\">150.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4137\\\" data-end=\\\"4149\\\">500.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<\\/tbody>\\r\\n<\\/table>\\r\\n<\\/div>\\r\\n<\\/div>\\r\\n<p data-start=\\\"4151\\\" data-end=\\\"4202\\\"><em data-start=\\\"4151\\\" data-end=\\\"4202\\\">(Gi&aacute; c&oacute; th\\u1ec3 thay \\u0111\\u1ed5i t&ugrave;y k&iacute;ch th\\u01b0\\u1edbc v&agrave; tu\\u1ed5i c&acirc;y.)<\\/em><\\/p>\\r\\n<hr data-start=\\\"4204\\\" data-end=\\\"4207\\\">\\r\\n<h2 data-start=\\\"4209\\\" data-end=\\\"4240\\\"><strong data-start=\\\"4211\\\" data-end=\\\"4240\\\">T\\u01af V\\u1ea4N CH\\u1eccN C&Acirc;Y THEO M\\u1ec6NH<\\/strong><\\/h2>\\r\\n<ul data-start=\\\"4242\\\" data-end=\\\"4419\\\">\\r\\n<li data-start=\\\"4242\\\" data-end=\\\"4277\\\">\\r\\n<p data-start=\\\"4244\\\" data-end=\\\"4277\\\"><strong data-start=\\\"4244\\\" data-end=\\\"4258\\\">M\\u1ec7nh Th\\u1ee7y:<\\/strong> C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"4278\\\" data-end=\\\"4328\\\">\\r\\n<p data-start=\\\"4280\\\" data-end=\\\"4328\\\"><strong data-start=\\\"4280\\\" data-end=\\\"4293\\\">M\\u1ec7nh M\\u1ed9c:<\\/strong> B&uacute;p Sen, Kim Ti\\u1ec1n, Thi\\u1ebft M\\u1ed9c Lan<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"4329\\\" data-end=\\\"4383\\\">\\r\\n<p data-start=\\\"4331\\\" data-end=\\\"4383\\\"><strong data-start=\\\"4331\\\" data-end=\\\"4344\\\">M\\u1ec7nh H\\u1ecfa:<\\/strong> Ph&aacute;t T&agrave;i \\u0110\\u1ecf, Kim Ti\\u1ec1n, Thi\\u1ebft M\\u1ed9c Lan<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"4384\\\" data-end=\\\"4419\\\">\\r\\n<p data-start=\\\"4386\\\" data-end=\\\"4419\\\"><strong data-start=\\\"4386\\\" data-end=\\\"4399\\\">M\\u1ec7nh Kim:<\\/strong> Ph&aacute;t L\\u1ed9c, B&uacute;p Sen<\\/p>\\r\\n<\\/li>\\r\\n<\\/ul>\",\"short_description\":null,\"price\":\"970000.00\",\"sale_price\":\"899000.00\",\"cost_price\":\"500000.00\",\"stock_quantity\":100,\"meta_title\":\"C\\u00e2y ph\\u00e1t t\\u00e0i c\\u1ea3nh phong th\\u1ee7y \\u2013 thu h\\u00fat t\\u00e0i l\\u1ed9c, may m\\u1eafn, ph\\u00f9 h\\u1ee3p m\\u1ecdi kh\\u00f4ng gian\",\"meta_description\":\"C\\u00e2y Ph\\u00e1t T\\u00e0i mang \\u00fd ngh\\u0129a thu h\\u00fat t\\u00e0i l\\u1ed9c, may m\\u1eafn v\\u00e0 th\\u1ecbnh v\\u01b0\\u1ee3ng. C\\u00e2y d\\u1ec5 ch\\u0103m s\\u00f3c, ph\\u00f9 h\\u1ee3p trang tr\\u00ed nh\\u00e0 \\u2013 v\\u0103n ph\\u00f2ng. Gi\\u00e1 t\\u1ed1t, giao h\\u00e0ng to\\u00e0n qu\\u1ed1c.\",\"meta_keywords\":[\"c\\u00e2y ph\\u00e1t t\\u00e0i\",\"c\\u00e2y phong th\\u1ee7y ph\\u00e1t t\\u00e0i\",\"c\\u00e2y ph\\u00e1t t\\u00e0i n\\u00fai\",\"c\\u00e2y ph\\u00e1t t\\u00e0i ph\\u00e1t l\\u1ed9c\",\"c\\u00e2y ph\\u00e1t t\\u00e0i \\u0111\\u1ecf\",\"c\\u00e2y ph\\u00e1t t\\u00e0i b\\u00fap sen\",\"thi\\u1ebft m\\u1ed9c lan\",\"c\\u00e2y kim ti\\u1ec1n\",\"c\\u00e2y c\\u1ea3nh phong th\\u1ee7y\",\"mua c\\u00e2y ph\\u00e1t t\\u00e0i\"],\"meta_canonical\":\"http:\\/\\/127.0.0.1:8000\\/san-pham\\/cay-phat-tai-cay-canh-phong-thuy-thu-hut-tai-loc\",\"primary_category_id\":\"2\",\"category_included_ids\":null,\"category_ids\":[\"1\",\"9\",\"6\",\"14\",\"15\",\"7\",\"8\",\"2\",\"13\",\"12\",\"16\",\"17\"],\"tag_ids\":[1,2,3,4,5,6,7,8,9,10],\"image_ids\":[1,2,3,4],\"is_featured\":true,\"locked_by\":null,\"locked_at\":null,\"created_by\":\"1\",\"is_active\":true,\"category_ids_backup\":null,\"created_at\":\"2025-12-05T07:43:56.000000Z\",\"updated_at\":\"2025-12-05T08:19:18.000000Z\",\"locked_by_user\":null}', '{\"id\":1,\"sku\":\"XWCPT120525\",\"name\":\"C\\u00e2y Ph\\u00e1t T\\u00e0i \\u2013 C\\u00e2y C\\u1ea3nh Phong Th\\u1ee7y Thu H\\u00fat T\\u00e0i L\\u1ed9c\",\"slug\":\"cay-phat-tai-cay-canh-phong-thuy-thu-hut-tai-loc\",\"description\":\"<h2 data-start=\\\"275\\\" data-end=\\\"335\\\"><strong data-start=\\\"277\\\" data-end=\\\"335\\\">C&Acirc;Y PH&Aacute;T T&Agrave;I &ndash; BI\\u1ec2U T\\u01af\\u1ee2NG T&Agrave;I L\\u1ed8C MAY M\\u1eaeN CHO GIA \\u0110&Igrave;NH<\\/strong><\\/h2>\\r\\n<h3 data-start=\\\"337\\\" data-end=\\\"358\\\"><strong data-start=\\\"340\\\" data-end=\\\"358\\\">Gi\\u1edbi thi\\u1ec7u chung<\\/strong><\\/h3>\\r\\n<p data-start=\\\"359\\\" data-end=\\\"648\\\">C&acirc;y Ph&aacute;t T&agrave;i l&agrave; d&ograve;ng c&acirc;y c\\u1ea3nh phong th\\u1ee7y \\u0111\\u01b0\\u1ee3c \\u01b0a chu\\u1ed9ng h&agrave;ng \\u0111\\u1ea7u, mang &yacute; ngh\\u0129a thu h&uacute;t v\\u1eadn may, t&agrave;i l\\u1ed9c v&agrave; th\\u1ecbnh v\\u01b0\\u1ee3ng cho gia ch\\u1ee7. Nh\\u1edd \\u0111a d\\u1ea1ng ch\\u1ee7ng lo\\u1ea1i v&agrave; d\\u1ec5 ch\\u0103m s&oacute;c, c&acirc;y ph&ugrave; h\\u1ee3p v\\u1edbi nhi\\u1ec1u kh&ocirc;ng gian nh\\u01b0 nh&agrave; \\u1edf, v\\u0103n ph&ograve;ng, c\\u1eeda h&agrave;ng kinh doanh v&agrave; h\\u1ee3p v\\u1edbi nhi\\u1ec1u m\\u1ec7nh phong th\\u1ee7y kh&aacute;c nhau.<\\/p>\\r\\n<hr data-start=\\\"650\\\" data-end=\\\"653\\\">\\r\\n<h3 data-start=\\\"655\\\" data-end=\\\"678\\\"><strong data-start=\\\"658\\\" data-end=\\\"678\\\">\\u0110\\u1eb6C \\u0110I\\u1ec2M N\\u1ed4I B\\u1eacT<\\/strong><\\/h3>\\r\\n<p data-start=\\\"679\\\" data-end=\\\"926\\\">&bull; D\\u1ec5 tr\\u1ed3ng &ndash; d\\u1ec5 ch\\u0103m s&oacute;c &ndash; ph&aacute;t tri\\u1ec3n t\\u1ed1t trong nh&agrave; l\\u1eabn v\\u0103n ph&ograve;ng<br data-start=\\\"744\\\" data-end=\\\"747\\\">&bull; Kh\\u1ea3 n\\u0103ng thanh l\\u1ecdc kh&ocirc;ng kh&iacute; hi\\u1ec7u qu\\u1ea3<br data-start=\\\"786\\\" data-end=\\\"789\\\">&bull; S\\u1ed1ng kh\\u1ecfe trong m&ocirc;i tr\\u01b0\\u1eddng thi\\u1ebfu s&aacute;ng<br data-start=\\\"828\\\" data-end=\\\"831\\\">&bull; Ph&ugrave; h\\u1ee3p h\\u1ea7u h\\u1ebft c&aacute;c m\\u1ec7nh trong ng\\u0169 h&agrave;nh<br data-start=\\\"872\\\" data-end=\\\"875\\\">&bull; T\\u1ea1o \\u0111i\\u1ec3m nh\\u1ea5n phong th\\u1ee7y &ndash; th\\u1ea9m m\\u1ef9 cho kh&ocirc;ng gian<\\/p>\\r\\n<hr data-start=\\\"928\\\" data-end=\\\"931\\\">\\r\\n<h2 data-start=\\\"933\\\" data-end=\\\"969\\\"><strong data-start=\\\"935\\\" data-end=\\\"969\\\">C&Aacute;C LO\\u1ea0I C&Acirc;Y PH&Aacute;T T&Agrave;I B&Aacute;N CH\\u1ea0Y<\\/strong><\\/h2>\\r\\n<h3 data-start=\\\"971\\\" data-end=\\\"1007\\\"><strong data-start=\\\"974\\\" data-end=\\\"1007\\\">1. C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i (\\u0110\\u1ea1i L\\u1ed9c)<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1008\\\" data-end=\\\"1256\\\"><strong data-start=\\\"1008\\\" data-end=\\\"1021\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> Th&acirc;n to, kh\\u1ecfe, l&aacute; thu&ocirc;n d&agrave;i nh\\u1ecdn nh\\u01b0 c\\u1ecd; hoa v&agrave;ng m\\u1ecdc th&agrave;nh c\\u1ee5m \\u0111\\u1eb9p m\\u1eaft<br data-start=\\\"1093\\\" data-end=\\\"1096\\\"><strong data-start=\\\"1096\\\" data-end=\\\"1111\\\">Phong th\\u1ee7y:<\\/strong> Thu\\u1ed9c h&agrave;nh M\\u1ed9c &ndash; h\\u1ee3p m\\u1ec7nh Th\\u1ee7y, M\\u1ed9c, H\\u1ecfa<br data-start=\\\"1152\\\" data-end=\\\"1155\\\"><strong data-start=\\\"1155\\\" data-end=\\\"1170\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> S&acirc;n v\\u01b0\\u1eddn, ph&ograve;ng kh&aacute;ch, v\\u0103n ph&ograve;ng, khu&ocirc;n vi&ecirc;n nh&agrave;<br data-start=\\\"1219\\\" data-end=\\\"1222\\\"><strong data-start=\\\"1222\\\" data-end=\\\"1234\\\">Gi&aacute; b&aacute;n:<\\/strong> 300.000\\u0111 &ndash; 3.000.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"1258\\\" data-end=\\\"1261\\\">\\r\\n<h3 data-start=\\\"1263\\\" data-end=\\\"1294\\\"><strong data-start=\\\"1266\\\" data-end=\\\"1294\\\">2. C&acirc;y Ph&aacute;t T&agrave;i Ph&aacute;t L\\u1ed9c<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1295\\\" data-end=\\\"1516\\\"><strong data-start=\\\"1295\\\" data-end=\\\"1308\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> Th&acirc;n chia \\u0111\\u1ed1t nh\\u01b0 tre, nh\\u1ecf g\\u1ecdn, d\\u1ec5 u\\u1ed1n d&aacute;ng phong th\\u1ee7y<br data-start=\\\"1363\\\" data-end=\\\"1366\\\"><strong data-start=\\\"1366\\\" data-end=\\\"1381\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh Kim &ndash; mang l\\u1ea1i s\\u1ef1 c&acirc;n b\\u1eb1ng, may m\\u1eafn<br data-start=\\\"1426\\\" data-end=\\\"1429\\\"><strong data-start=\\\"1429\\\" data-end=\\\"1444\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> B&agrave;n l&agrave;m vi\\u1ec7c, ph&ograve;ng h\\u1ecdp, qu\\u1ea7y l\\u1ec5 t&acirc;n<br data-start=\\\"1481\\\" data-end=\\\"1484\\\"><strong data-start=\\\"1484\\\" data-end=\\\"1496\\\">Gi&aacute; b&aacute;n:<\\/strong> 200.000\\u0111 &ndash; 500.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"1518\\\" data-end=\\\"1521\\\">\\r\\n<h3 data-start=\\\"1523\\\" data-end=\\\"1548\\\"><strong data-start=\\\"1526\\\" data-end=\\\"1548\\\">3. C&acirc;y Ph&aacute;t T&agrave;i \\u0110\\u1ecf<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1549\\\" data-end=\\\"1781\\\"><strong data-start=\\\"1549\\\" data-end=\\\"1562\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> L&aacute; xanh khi non, chuy\\u1ec3n \\u0111\\u1ecf n\\u1ed5i b\\u1eadt khi tr\\u01b0\\u1edfng th&agrave;nh<br data-start=\\\"1614\\\" data-end=\\\"1617\\\"><strong data-start=\\\"1617\\\" data-end=\\\"1632\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh H\\u1ecfa &ndash; thu h&uacute;t n\\u0103ng l\\u01b0\\u1ee3ng t&iacute;ch c\\u1ef1c<br data-start=\\\"1675\\\" data-end=\\\"1678\\\"><strong data-start=\\\"1678\\\" data-end=\\\"1693\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> Ph&ograve;ng kh&aacute;ch, ph&ograve;ng th\\u1edd, c&aacute;c v\\u1ecb tr&iacute; trang tr&iacute; n\\u1ed5i b\\u1eadt<br data-start=\\\"1746\\\" data-end=\\\"1749\\\"><strong data-start=\\\"1749\\\" data-end=\\\"1761\\\">Gi&aacute; b&aacute;n:<\\/strong> 100.000\\u0111 &ndash; 150.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"1783\\\" data-end=\\\"1786\\\">\\r\\n<h3 data-start=\\\"1788\\\" data-end=\\\"1818\\\"><strong data-start=\\\"1791\\\" data-end=\\\"1818\\\">4. C&acirc;y Ph&aacute;t T&agrave;i B&uacute;p Sen<\\/strong><\\/h3>\\r\\n<p data-start=\\\"1819\\\" data-end=\\\"2019\\\"><strong data-start=\\\"1819\\\" data-end=\\\"1832\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> L&aacute; xanh \\u0111\\u1eadm, m\\u1ecdc x\\u1ebfp l\\u1edbp nh\\u01b0 b&ocirc;ng sen<br data-start=\\\"1870\\\" data-end=\\\"1873\\\"><strong data-start=\\\"1873\\\" data-end=\\\"1888\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh Kim &ndash; M\\u1ed9c &ndash; Th\\u1ee7y (khi tr\\u1ed3ng th\\u1ee7y sinh)<br data-start=\\\"1936\\\" data-end=\\\"1939\\\"><strong data-start=\\\"1939\\\" data-end=\\\"1954\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> B&agrave;n th\\u1edd Th\\u1ea7n T&agrave;i, b&agrave;n l&agrave;m vi\\u1ec7c<br data-start=\\\"1985\\\" data-end=\\\"1988\\\"><strong data-start=\\\"1988\\\" data-end=\\\"2000\\\">Gi&aacute; b&aacute;n:<\\/strong> 70.000\\u0111 &ndash; 200.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"2021\\\" data-end=\\\"2024\\\">\\r\\n<h3 data-start=\\\"2026\\\" data-end=\\\"2053\\\"><strong data-start=\\\"2029\\\" data-end=\\\"2053\\\">5. C&acirc;y Thi\\u1ebft M\\u1ed9c Lan<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2054\\\" data-end=\\\"2247\\\"><strong data-start=\\\"2054\\\" data-end=\\\"2067\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> T&aacute;n l&aacute; r\\u1eadm, th&acirc;n c\\u1ed9t cao, c&oacute; th\\u1ec3 ra hoa th\\u01a1m nh\\u1eb9<br data-start=\\\"2116\\\" data-end=\\\"2119\\\"><strong data-start=\\\"2119\\\" data-end=\\\"2134\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh M\\u1ed9c v&agrave; H\\u1ecfa<br data-start=\\\"2154\\\" data-end=\\\"2157\\\"><strong data-start=\\\"2157\\\" data-end=\\\"2172\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> Nh&agrave; \\u1edf, v\\u0103n ph&ograve;ng, c\\u1eeda h&agrave;ng kinh doanh<br data-start=\\\"2210\\\" data-end=\\\"2213\\\"><strong data-start=\\\"2213\\\" data-end=\\\"2225\\\">Gi&aacute; b&aacute;n:<\\/strong> 500.000\\u0111 &ndash; 2.000.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"2249\\\" data-end=\\\"2252\\\">\\r\\n<h3 data-start=\\\"2254\\\" data-end=\\\"2276\\\"><strong data-start=\\\"2257\\\" data-end=\\\"2276\\\">6. C&acirc;y Kim Ti\\u1ec1n<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2277\\\" data-end=\\\"2495\\\"><strong data-start=\\\"2277\\\" data-end=\\\"2290\\\">\\u0110\\u1eb7c \\u0111i\\u1ec3m:<\\/strong> L&aacute; \\u0111\\u1ed1i x\\u1ee9ng, b&oacute;ng \\u0111\\u1eb9p, c&agrave;nh v\\u01b0\\u01a1n cao gi&agrave;u sinh kh&iacute;<br data-start=\\\"2341\\\" data-end=\\\"2344\\\"><strong data-start=\\\"2344\\\" data-end=\\\"2359\\\">Phong th\\u1ee7y:<\\/strong> H\\u1ee3p m\\u1ec7nh M\\u1ed9c &ndash; H\\u1ecfa, t\\u01b0\\u1ee3ng tr\\u01b0ng cho ti\\u1ec1n t&agrave;i v&agrave; th\\u1ecbnh v\\u01b0\\u1ee3ng<br data-start=\\\"2419\\\" data-end=\\\"2422\\\"><strong data-start=\\\"2422\\\" data-end=\\\"2437\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t:<\\/strong> H\\u1ea7u h\\u1ebft m\\u1ecdi kh&ocirc;ng gian<br data-start=\\\"2460\\\" data-end=\\\"2463\\\"><strong data-start=\\\"2463\\\" data-end=\\\"2475\\\">Gi&aacute; b&aacute;n:<\\/strong> 150.000\\u0111 &ndash; 500.000\\u0111<\\/p>\\r\\n<hr data-start=\\\"2497\\\" data-end=\\\"2500\\\">\\r\\n<h2 data-start=\\\"2502\\\" data-end=\\\"2539\\\"><strong data-start=\\\"2504\\\" data-end=\\\"2539\\\">H\\u01af\\u1edaNG D\\u1eaaN CH\\u0102M S&Oacute;C C&Acirc;Y PH&Aacute;T T&Agrave;I<\\/strong><\\/h2>\\r\\n<h3 data-start=\\\"2541\\\" data-end=\\\"2563\\\"><strong data-start=\\\"2545\\\" data-end=\\\"2563\\\">V\\u1ecb tr&iacute; \\u0111\\u1eb7t c&acirc;y<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2564\\\" data-end=\\\"2613\\\">N&ecirc;n \\u0111\\u1eb7t h\\u01b0\\u1edbng \\u0110&ocirc;ng ho\\u1eb7c \\u0110&ocirc;ng Nam theo phong th\\u1ee7y.<\\/p>\\r\\n<h3 data-start=\\\"2615\\\" data-end=\\\"2631\\\"><strong data-start=\\\"2619\\\" data-end=\\\"2631\\\">&Aacute;nh s&aacute;ng<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2632\\\" data-end=\\\"2725\\\">&bull; S\\u1ed1ng t\\u1ed1t trong m&ocirc;i tr\\u01b0\\u1eddng &iacute;t s&aacute;ng<br data-start=\\\"2667\\\" data-end=\\\"2670\\\">&bull; Th\\u1ec9nh tho\\u1ea3ng ph\\u01a1i n\\u1eafng nh\\u1eb9 \\u0111\\u1ec3 c&acirc;y quang h\\u1ee3p t\\u1ed1t h\\u01a1n<\\/p>\\r\\n<h3 data-start=\\\"2727\\\" data-end=\\\"2744\\\"><strong data-start=\\\"2731\\\" data-end=\\\"2744\\\">T\\u01b0\\u1edbi n\\u01b0\\u1edbc<\\/strong><\\/h3>\\r\\n<ul data-start=\\\"2745\\\" data-end=\\\"2878\\\">\\r\\n<li data-start=\\\"2745\\\" data-end=\\\"2790\\\">\\r\\n<p data-start=\\\"2747\\\" data-end=\\\"2790\\\">C&acirc;y trong nh&agrave;: phun s\\u01b0\\u01a1ng, gi\\u1eef \\u0111\\u1ea5t \\u1ea9m nh\\u1eb9<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"2791\\\" data-end=\\\"2828\\\">\\r\\n<p data-start=\\\"2793\\\" data-end=\\\"2828\\\">C&acirc;y ngo&agrave;i tr\\u1eddi: t\\u01b0\\u1edbi \\u0111\\u1ec1u m\\u1ed7i ng&agrave;y<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"2829\\\" data-end=\\\"2878\\\">\\r\\n<p data-start=\\\"2831\\\" data-end=\\\"2878\\\">Th\\u1ee7y sinh: thay n\\u01b0\\u1edbc 3&ndash;4 ng&agrave;y\\/l\\u1ea7n b\\u1eb1ng n\\u01b0\\u1edbc l\\u1ecdc<\\/p>\\r\\n<\\/li>\\r\\n<\\/ul>\\r\\n<h3 data-start=\\\"2880\\\" data-end=\\\"2897\\\"><strong data-start=\\\"2884\\\" data-end=\\\"2897\\\">\\u0110\\u1ea5t tr\\u1ed3ng<\\/strong><\\/h3>\\r\\n<p data-start=\\\"2898\\\" data-end=\\\"2984\\\">\\u0110\\u1ea5t t\\u01a1i x\\u1ed1p, gi&agrave;u dinh d\\u01b0\\u1ee1ng, tho&aacute;ng n\\u01b0\\u1edbc, pH 6&ndash;7. C&oacute; th\\u1ec3 tr\\u1ed9n m&ugrave;n, tr\\u1ea5u, ph&acirc;n h\\u1eefu c\\u01a1.<\\/p>\\r\\n<h3 data-start=\\\"2986\\\" data-end=\\\"3002\\\"><strong data-start=\\\"2990\\\" data-end=\\\"3002\\\">B&oacute;n ph&acirc;n<\\/strong><\\/h3>\\r\\n<p data-start=\\\"3003\\\" data-end=\\\"3056\\\">B&oacute;n h\\u1eefu c\\u01a1 ho\\u1eb7c NPK \\u0111\\u1ecbnh k\\u1ef3 v&agrave;o giai \\u0111o\\u1ea1n ph&aacute;t tri\\u1ec3n.<\\/p>\\r\\n<h3 data-start=\\\"3058\\\" data-end=\\\"3084\\\"><strong data-start=\\\"3062\\\" data-end=\\\"3084\\\">C\\u1eaft t\\u1ec9a &ndash; t\\u1ea1o d&aacute;ng<\\/strong><\\/h3>\\r\\n<p data-start=\\\"3085\\\" data-end=\\\"3136\\\">Lo\\u1ea1i b\\u1ecf l&aacute; h&eacute;o, ch\\u1ec9nh d&aacute;ng \\u0111\\u1ec3 c&acirc;y lu&ocirc;n \\u0111\\u1eb9p v&agrave; kh\\u1ecfe.<\\/p>\\r\\n<h3 data-start=\\\"3138\\\" data-end=\\\"3155\\\"><strong data-start=\\\"3142\\\" data-end=\\\"3155\\\">Thay ch\\u1eadu<\\/strong><\\/h3>\\r\\n<p data-start=\\\"3156\\\" data-end=\\\"3196\\\">Kho\\u1ea3ng 1 n\\u0103m\\/l\\u1ea7n \\u0111\\u1ec3 c&acirc;y ph&aacute;t tri\\u1ec3n m\\u1ea1nh.<\\/p>\\r\\n<hr data-start=\\\"3198\\\" data-end=\\\"3201\\\">\\r\\n<h2 data-start=\\\"3203\\\" data-end=\\\"3227\\\"><strong data-start=\\\"3205\\\" data-end=\\\"3227\\\">&Yacute; NGH\\u0128A PHONG TH\\u1ee6Y<\\/strong><\\/h2>\\r\\n<p data-start=\\\"3228\\\" data-end=\\\"3451\\\">\\u2713 Thu h&uacute;t t&agrave;i l\\u1ed9c &ndash; may m\\u1eafn &ndash; th\\u1ecbnh v\\u01b0\\u1ee3ng<br data-start=\\\"3269\\\" data-end=\\\"3272\\\">\\u2713 Mang l\\u1ea1i b&igrave;nh an cho gia \\u0111&igrave;nh<br data-start=\\\"3303\\\" data-end=\\\"3306\\\">\\u2713 Gi\\u1ea3m c\\u0103ng th\\u1eb3ng, t\\u0103ng n\\u0103ng l\\u01b0\\u1ee3ng t&iacute;ch c\\u1ef1c<br data-start=\\\"3349\\\" data-end=\\\"3352\\\">\\u2713 H\\u1ed7 tr\\u1ee3 c&acirc;n b\\u1eb1ng phong th\\u1ee7y trong kh&ocirc;ng gian<br data-start=\\\"3397\\\" data-end=\\\"3400\\\">\\u2713 C&acirc;y ra hoa \\u0111\\u01b0\\u1ee3c xem l&agrave; d\\u1ea5u hi\\u1ec7u v\\u1eadn may s\\u1eafp \\u0111\\u1ebfn<\\/p>\\r\\n<hr data-start=\\\"3453\\\" data-end=\\\"3456\\\">\\r\\n<h2 data-start=\\\"3458\\\" data-end=\\\"3480\\\"><strong data-start=\\\"3460\\\" data-end=\\\"3480\\\">CAM K\\u1ebeT S\\u1ea2N PH\\u1ea8M<\\/strong><\\/h2>\\r\\n<p data-start=\\\"3481\\\" data-end=\\\"3638\\\">&bull; C&acirc;y kh\\u1ecfe m\\u1ea1nh &ndash; \\u0111&uacute;ng lo\\u1ea1i &ndash; \\u0111&uacute;ng k&iacute;ch th\\u01b0\\u1edbc<br data-start=\\\"3526\\\" data-end=\\\"3529\\\">&bull; Giao h&agrave;ng to&agrave;n qu\\u1ed1c &ndash; \\u0111&oacute;ng g&oacute;i an to&agrave;n<br data-start=\\\"3569\\\" data-end=\\\"3572\\\">&bull; T\\u01b0 v\\u1ea5n ch\\u1ecdn c&acirc;y h\\u1ee3p m\\u1ec7nh mi\\u1ec5n ph&iacute;<br data-start=\\\"3607\\\" data-end=\\\"3610\\\">&bull; H\\u1ed7 tr\\u1ee3 ch\\u0103m s&oacute;c tr\\u1ecdn \\u0111\\u1eddi<\\/p>\\r\\n<hr data-start=\\\"3640\\\" data-end=\\\"3643\\\">\\r\\n<h2 data-start=\\\"3645\\\" data-end=\\\"3664\\\"><strong data-start=\\\"3647\\\" data-end=\\\"3664\\\">L\\u01afU &Yacute; AN TO&Agrave;N<\\/strong><\\/h2>\\r\\n<p data-start=\\\"3665\\\" data-end=\\\"3787\\\">H\\u1ea7u h\\u1ebft c&acirc;y Ph&aacute;t T&agrave;i \\u0111\\u1ec1u an to&agrave;n. M\\u1ed9t s\\u1ed1 lo\\u1ea1i c&oacute; th\\u1ec3 g&acirc;y k&iacute;ch \\u1ee9ng nh\\u1eb9 khi nh\\u1ef1a c&acirc;y d&iacute;nh tay &mdash; n&ecirc;n r\\u1eeda tay sau khi c\\u1eaft t\\u1ec9a.<\\/p>\\r\\n<hr data-start=\\\"3789\\\" data-end=\\\"3792\\\">\\r\\n<h2 data-start=\\\"3794\\\" data-end=\\\"3817\\\"><strong data-start=\\\"3796\\\" data-end=\\\"3817\\\">B\\u1ea2NG GI&Aacute; CHI TI\\u1ebeT<\\/strong><\\/h2>\\r\\n<div class=\\\"TyagGW_tableContainer\\\">\\r\\n<div class=\\\"group TyagGW_tableWrapper flex w-fit flex-col-reverse\\\" tabindex=\\\"-1\\\">\\r\\n<table class=\\\"w-fit min-w-(--thread-content-width)\\\" data-start=\\\"3819\\\" data-end=\\\"4149\\\">\\r\\n<thead data-start=\\\"3819\\\" data-end=\\\"3850\\\">\\r\\n<tr data-start=\\\"3819\\\" data-end=\\\"3850\\\">\\r\\n<th data-start=\\\"3819\\\" data-end=\\\"3830\\\" data-col-size=\\\"sm\\\">Lo\\u1ea1i c&acirc;y<\\/th>\\r\\n<th data-start=\\\"3830\\\" data-end=\\\"3839\\\" data-col-size=\\\"sm\\\">Gi&aacute; t\\u1eeb<\\/th>\\r\\n<th data-start=\\\"3839\\\" data-end=\\\"3850\\\" data-col-size=\\\"sm\\\">Gi&aacute; \\u0111\\u1ebfn<\\/th>\\r\\n<\\/tr>\\r\\n<\\/thead>\\r\\n<tbody data-start=\\\"3884\\\" data-end=\\\"4149\\\">\\r\\n<tr data-start=\\\"3884\\\" data-end=\\\"3928\\\">\\r\\n<td data-start=\\\"3884\\\" data-end=\\\"3903\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3903\\\" data-end=\\\"3914\\\">300.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3914\\\" data-end=\\\"3928\\\">3.000.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"3929\\\" data-end=\\\"3976\\\">\\r\\n<td data-start=\\\"3929\\\" data-end=\\\"3953\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i Ph&aacute;t L\\u1ed9c<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3953\\\" data-end=\\\"3964\\\">200.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3964\\\" data-end=\\\"3976\\\">500.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"3977\\\" data-end=\\\"4018\\\">\\r\\n<td data-start=\\\"3977\\\" data-end=\\\"3995\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i \\u0110\\u1ecf<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"3995\\\" data-end=\\\"4006\\\">100.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4006\\\" data-end=\\\"4018\\\">150.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"4019\\\" data-end=\\\"4064\\\">\\r\\n<td data-start=\\\"4019\\\" data-end=\\\"4042\\\" data-col-size=\\\"sm\\\">C&acirc;y Ph&aacute;t T&agrave;i B&uacute;p Sen<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4042\\\" data-end=\\\"4052\\\">70.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4052\\\" data-end=\\\"4064\\\">200.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"4065\\\" data-end=\\\"4110\\\">\\r\\n<td data-start=\\\"4065\\\" data-end=\\\"4085\\\" data-col-size=\\\"sm\\\">C&acirc;y Thi\\u1ebft M\\u1ed9c Lan<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4085\\\" data-end=\\\"4096\\\">500.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4096\\\" data-end=\\\"4110\\\">2.000.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<tr data-start=\\\"4111\\\" data-end=\\\"4149\\\">\\r\\n<td data-start=\\\"4111\\\" data-end=\\\"4126\\\" data-col-size=\\\"sm\\\">C&acirc;y Kim Ti\\u1ec1n<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4126\\\" data-end=\\\"4137\\\">150.000\\u0111<\\/td>\\r\\n<td data-col-size=\\\"sm\\\" data-start=\\\"4137\\\" data-end=\\\"4149\\\">500.000\\u0111<\\/td>\\r\\n<\\/tr>\\r\\n<\\/tbody>\\r\\n<\\/table>\\r\\n<\\/div>\\r\\n<\\/div>\\r\\n<p data-start=\\\"4151\\\" data-end=\\\"4202\\\"><em data-start=\\\"4151\\\" data-end=\\\"4202\\\">(Gi&aacute; c&oacute; th\\u1ec3 thay \\u0111\\u1ed5i t&ugrave;y k&iacute;ch th\\u01b0\\u1edbc v&agrave; tu\\u1ed5i c&acirc;y.)<\\/em><\\/p>\\r\\n<hr data-start=\\\"4204\\\" data-end=\\\"4207\\\">\\r\\n<h2 data-start=\\\"4209\\\" data-end=\\\"4240\\\"><strong data-start=\\\"4211\\\" data-end=\\\"4240\\\">T\\u01af V\\u1ea4N CH\\u1eccN C&Acirc;Y THEO M\\u1ec6NH<\\/strong><\\/h2>\\r\\n<ul data-start=\\\"4242\\\" data-end=\\\"4419\\\">\\r\\n<li data-start=\\\"4242\\\" data-end=\\\"4277\\\">\\r\\n<p data-start=\\\"4244\\\" data-end=\\\"4277\\\"><strong data-start=\\\"4244\\\" data-end=\\\"4258\\\">M\\u1ec7nh Th\\u1ee7y:<\\/strong> C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"4278\\\" data-end=\\\"4328\\\">\\r\\n<p data-start=\\\"4280\\\" data-end=\\\"4328\\\"><strong data-start=\\\"4280\\\" data-end=\\\"4293\\\">M\\u1ec7nh M\\u1ed9c:<\\/strong> B&uacute;p Sen, Kim Ti\\u1ec1n, Thi\\u1ebft M\\u1ed9c Lan<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"4329\\\" data-end=\\\"4383\\\">\\r\\n<p data-start=\\\"4331\\\" data-end=\\\"4383\\\"><strong data-start=\\\"4331\\\" data-end=\\\"4344\\\">M\\u1ec7nh H\\u1ecfa:<\\/strong> Ph&aacute;t T&agrave;i \\u0110\\u1ecf, Kim Ti\\u1ec1n, Thi\\u1ebft M\\u1ed9c Lan<\\/p>\\r\\n<\\/li>\\r\\n<li data-start=\\\"4384\\\" data-end=\\\"4419\\\">\\r\\n<p data-start=\\\"4386\\\" data-end=\\\"4419\\\"><strong data-start=\\\"4386\\\" data-end=\\\"4399\\\">M\\u1ec7nh Kim:<\\/strong> Ph&aacute;t L\\u1ed9c, B&uacute;p Sen<\\/p>\\r\\n<\\/li>\\r\\n<\\/ul>\",\"short_description\":null,\"price\":\"970000.00\",\"sale_price\":\"899000.00\",\"cost_price\":\"500000.00\",\"stock_quantity\":100,\"meta_title\":\"C\\u00e2y ph\\u00e1t t\\u00e0i c\\u1ea3nh phong th\\u1ee7y \\u2013 thu h\\u00fat t\\u00e0i l\\u1ed9c, may m\\u1eafn, ph\\u00f9 h\\u1ee3p m\\u1ecdi kh\\u00f4ng gian\",\"meta_description\":\"C\\u00e2y Ph\\u00e1t T\\u00e0i mang \\u00fd ngh\\u0129a thu h\\u00fat t\\u00e0i l\\u1ed9c, may m\\u1eafn v\\u00e0 th\\u1ecbnh v\\u01b0\\u1ee3ng. C\\u00e2y d\\u1ec5 ch\\u0103m s\\u00f3c, ph\\u00f9 h\\u1ee3p trang tr\\u00ed nh\\u00e0 \\u2013 v\\u0103n ph\\u00f2ng. Gi\\u00e1 t\\u1ed1t, giao h\\u00e0ng to\\u00e0n qu\\u1ed1c.\",\"meta_keywords\":[\"c\\u00e2y ph\\u00e1t t\\u00e0i\",\"c\\u00e2y phong th\\u1ee7y ph\\u00e1t t\\u00e0i\",\"c\\u00e2y ph\\u00e1t t\\u00e0i n\\u00fai\",\"c\\u00e2y ph\\u00e1t t\\u00e0i ph\\u00e1t l\\u1ed9c\",\"c\\u00e2y ph\\u00e1t t\\u00e0i \\u0111\\u1ecf\",\"c\\u00e2y ph\\u00e1t t\\u00e0i b\\u00fap sen\",\"thi\\u1ebft m\\u1ed9c lan\",\"c\\u00e2y kim ti\\u1ec1n\",\"c\\u00e2y c\\u1ea3nh phong th\\u1ee7y\",\"mua c\\u00e2y ph\\u00e1t t\\u00e0i\"],\"meta_canonical\":\"http:\\/\\/127.0.0.1:8000\\/san-pham\\/cay-phat-tai-cay-canh-phong-thuy-thu-hut-tai-loc\",\"primary_category_id\":\"2\",\"category_included_ids\":null,\"category_ids\":[\"1\",\"6\",\"16\",\"14\",\"15\",\"13\",\"12\",\"9\",\"8\",\"17\",\"2\",\"7\"],\"tag_ids\":[1,2,3,4,5,6,7,8,9,10],\"image_ids\":[1,2,3,4],\"is_featured\":true,\"locked_by\":null,\"locked_at\":null,\"created_by\":\"1\",\"is_active\":true,\"category_ids_backup\":null,\"created_at\":\"2025-12-05T07:43:56.000000Z\",\"updated_at\":\"2025-12-05T08:19:18.000000Z\"}', '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 08:19:18', '2025-12-05 08:19:18');
INSERT INTO `activity_logs` (`id`, `action`, `model_type`, `model_id`, `account_id`, `description`, `old_data`, `new_data`, `ip`, `user_agent`, `created_at`, `updated_at`) VALUES
(3, 'create', 'App\\Models\\Product', 4, 1, 'Tạo sản phẩm mới: Cây Lộc Vừng: Đặc Điểm, Ý Nghĩa và Bảng Giá Cập Nhật', NULL, '{\"id\":4,\"sku\":\"XWCLV071225\",\"name\":\"C\\u00e2y L\\u1ed9c V\\u1eebng: \\u0110\\u1eb7c \\u0110i\\u1ec3m, \\u00dd Ngh\\u0129a v\\u00e0 B\\u1ea3ng Gi\\u00e1 C\\u1eadp Nh\\u1eadt\",\"slug\":\"cay-loc-vung-dac-diem-y-nghia-va-bang-gia-cap-nhat\",\"description\":\"<h2 style=\\\"color: #3bb77e; font-weight: bold; margin-top: 20px;\\\">C&Acirc;Y L\\u1ed8C V\\u1eeaNG &ndash; C&Acirc;Y C&Ocirc;NG TR&Igrave;NH B&Oacute;NG M&Aacute;T CAO C\\u1ea4P V\\u1edaI GI&Aacute; TR\\u1eca C\\u1ea2NH QUAN V&Agrave; PHONG TH\\u1ee6Y \\u0110\\u1eb6C BI\\u1ec6T<\\/h2>\\r\\n<div style=\\\"line-height: 1.7; font-size: 16px;\\\">\\r\\n<p><strong style=\\\"color: #3bb77e;\\\">C&acirc;y L\\u1ed9c V\\u1eebng<\\/strong> (Barringtonia acutangula) l&agrave; m\\u1ed9t trong nh\\u1eefng lo\\u1ea1i c&acirc;y c&ocirc;ng tr&igrave;nh b&oacute;ng m&aacute;t \\u0111\\u01b0\\u1ee3c y&ecirc;u th&iacute;ch nh\\u1edd v\\u1ebb \\u0111\\u1eb9p sang tr\\u1ecdng, hoa \\u0111\\u1ecf r\\u1ef1c r\\u1ee1 v&agrave; &yacute; ngh\\u0129a phong th\\u1ee7y g\\u1eafn li\\u1ec1n v\\u1edbi t&agrave;i l\\u1ed9c &ndash; may m\\u1eafn. C&acirc;y xu\\u1ea5t hi\\u1ec7n nhi\\u1ec1u trong c&aacute;c c&ocirc;ng tr&igrave;nh l\\u1edbn nh\\u01b0 c&ocirc;ng vi&ecirc;n, khu \\u0111&ocirc; th\\u1ecb, khu sinh th&aacute;i, resort, bi\\u1ec7t th\\u1ef1 v&agrave; c&aacute;c d\\u1ef1 &aacute;n c\\u1ea3nh quan quy m&ocirc;. V\\u1edbi kh\\u1ea3 n\\u0103ng sinh tr\\u01b0\\u1edfng m\\u1ea1nh, ch\\u1ecbu h\\u1ea1n, ch\\u1ecbu nhi\\u1ec7t v&agrave; ch\\u1ed1ng x&oacute;i m&ograve;n t\\u1ed1t, c&acirc;y L\\u1ed9c V\\u1eebng c&ograve;n l&agrave; l\\u1ef1a ch\\u1ecdn h&agrave;ng \\u0111\\u1ea7u cho c&aacute;c khu v\\u1ef1c ven bi\\u1ec3n, ven s&ocirc;ng.<\\/p>\\r\\n<p>Kh&ocirc;ng ch\\u1ec9 mang gi&aacute; tr\\u1ecb th\\u1ea9m m\\u1ef9 v&agrave; phong th\\u1ee7y, c&acirc;y L\\u1ed9c V\\u1eebng c&ograve;n l&agrave; lo\\u1ea1i c&acirc;y c&oacute; tu\\u1ed5i th\\u1ecd cao, gi&aacute; tr\\u1ecb kinh t\\u1ebf l\\u1edbn. C&acirc;y c&agrave;ng gi&agrave;, c&agrave;ng \\u0111\\u1eb9p, d&aacute;ng c&agrave;ng \\u0111\\u1ed9c &ndash; gi&aacute; tr\\u1ecb c&agrave;ng t\\u0103ng theo th\\u1eddi gian.<\\/p>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">\\u0110\\u1eb6C \\u0110I\\u1ec2M CHI TI\\u1ebeT C\\u1ee6A C&Acirc;Y L\\u1ed8C V\\u1eeaNG<\\/h3>\\r\\n<ul>\\r\\n<li><strong>T&ecirc;n khoa h\\u1ecdc:<\\/strong> Barringtonia acutangula<\\/li>\\r\\n<li><strong>T&ecirc;n g\\u1ecdi kh&aacute;c:<\\/strong> C&acirc;y M\\u01b0ng, C&acirc;y Chi\\u1ebfc<\\/li>\\r\\n<li><strong>H\\u1ecd:<\\/strong> Lecythidaceae<\\/li>\\r\\n<li><strong>Ngu\\u1ed3n g\\u1ed1c:<\\/strong> Nam &Aacute;, B\\u1eafc &Uacute;c &ndash; ph&acirc;n b\\u1ed1 t\\u1eeb Afghanistan \\u0111\\u1ebfn Philippines, Queensland<\\/li>\\r\\n<li><strong>Ph&acirc;n b\\u1ed1 t\\u1ea1i Vi\\u1ec7t Nam:<\\/strong> C&oacute; m\\u1eb7t tr&ecirc;n kh\\u1eafp l&atilde;nh th\\u1ed5, t\\u1eeb B\\u1eafc v&agrave;o Nam \\u0111\\u1ebfn t\\u1eadn C&ocirc;n \\u0110\\u1ea3o<\\/li>\\r\\n<li><strong>Th&acirc;n c&acirc;y:<\\/strong> G\\u1ed7 ch\\u1eafc, sinh tr\\u01b0\\u1edfng m\\u1ea1nh, ph&acirc;n t&aacute;n c&agrave;nh t\\u1ed1t<\\/li>\\r\\n<li><strong>T&aacute;n l&aacute;:<\\/strong> R\\u1ed9ng, xanh quanh n\\u0103m, t\\u1ea1o b&oacute;ng m&aacute;t t\\u1ef1 nhi&ecirc;n<\\/li>\\r\\n<li><strong>L&aacute;:<\\/strong> H&igrave;nh m&aacute;c, m\\u1ec1m, b&oacute;ng, nhi\\u1ec1u n\\u01b0\\u1edbc<\\/li>\\r\\n<li><strong>Hoa:<\\/strong> N\\u1edf 2 \\u0111\\u1ee3t: th&aacute;ng 4&ndash;6 v&agrave; 9&ndash;11, m&agrave;u \\u0111\\u1ecf r\\u1ef1c ho\\u1eb7c tr\\u1eafng tinh khi\\u1ebft<\\/li>\\r\\n<li><strong>Ch&ugrave;m hoa:<\\/strong> D&agrave;i, r\\u1ee7 xu\\u1ed1ng t\\u1ea1o th&agrave;nh d\\u1ea3i hoa \\u0111\\u1eb9p m\\u1eaft<\\/li>\\r\\n<li><strong>Qu\\u1ea3:<\\/strong> H&igrave;nh h\\u1ed9p\\/tr&ograve;n d&agrave;i 9&ndash;11cm, chuy\\u1ec3n v&agrave;ng n&acirc;u khi ch&iacute;n<\\/li>\\r\\n<li><strong>Tu\\u1ed5i th\\u1ecd:<\\/strong> R\\u1ea5t l&acirc;u n\\u0103m, c&agrave;ng gi&agrave; c&agrave;ng c&oacute; gi&aacute; tr\\u1ecb ngh\\u1ec7 thu\\u1eadt<\\/li>\\r\\n<\\/ul>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">\\u01afU \\u0110I\\u1ec2M N\\u1ed4I B\\u1eacT<\\/h3>\\r\\n<ul>\\r\\n<li>Hoa \\u0111\\u1ecf r\\u1ef1c r\\u1ee1, n\\u1edf \\u0111\\u1ec1u \\u0111\\u1eb7n 2 l\\u1ea7n\\/n\\u0103m<\\/li>\\r\\n<li>T&aacute;n l&aacute; r\\u1ed9ng &ndash; t\\u1ea1o b&oacute;ng m&aacute;t xu\\u1ea5t s\\u1eafc<\\/li>\\r\\n<li>Th&iacute;ch nghi t\\u1ed1t m&ocirc;i tr\\u01b0\\u1eddng ven bi\\u1ec3n, ng\\u1eadp m\\u1eb7n<\\/li>\\r\\n<li>Ch\\u1ecbu h\\u1ea1n, ch\\u1ecbu nhi\\u1ec7t, d\\u1ec5 ch\\u0103m s&oacute;c<\\/li>\\r\\n<li>&Iacute;t s&acirc;u b\\u1ec7nh, ph&aacute;t tri\\u1ec3n b\\u1ec1n v\\u1eefng<\\/li>\\r\\n<li>Gi\\u1eef \\u0111\\u1ea5t, ch\\u1ed1ng x&oacute;i m&ograve;n hi\\u1ec7u qu\\u1ea3<\\/li>\\r\\n<li>L\\u1ecdc b\\u1ee5i, h\\u1ea5p th\\u1ee5 kh&iacute; \\u0111\\u1ed9c, c\\u1ea3i thi\\u1ec7n ch\\u1ea5t l\\u01b0\\u1ee3ng kh&ocirc;ng kh&iacute;<\\/li>\\r\\n<\\/ul>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">PH&Acirc;N LO\\u1ea0I C&Acirc;Y L\\u1ed8C V\\u1eeaNG<\\/h3>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">1. C&acirc;y L\\u1ed9c V\\u1eebng Hoa \\u0110\\u1ecf &ndash; Ph\\u1ed5 bi\\u1ebfn nh\\u1ea5t<\\/h4>\\r\\n<ul>\\r\\n<li>Hoa \\u0111\\u1ecf r\\u1ef1c r\\u1ee1, mang s\\u1eafc may m\\u1eafn<\\/li>\\r\\n<li>Ngu\\u1ed3n g\\u1ed1c Nam &Aacute;, Philippines<\\/li>\\r\\n<li>D&ugrave;ng trang tr&iacute; s&acirc;n v\\u01b0\\u1eddn, tr\\u01b0\\u1edbc c\\u1eeda nh&agrave;, khu \\u0111&ocirc; th\\u1ecb<\\/li>\\r\\n<\\/ul>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">2. C&acirc;y L\\u1ed9c V\\u1eebng Hoa Tr\\u1eafng<\\/h4>\\r\\n<ul>\\r\\n<li>T&ecirc;n khoa h\\u1ecdc: Barringtonia racemosa<\\/li>\\r\\n<li>Hoa tr\\u1eafng pha h\\u1ed3ng nh\\u1eb9 &ndash; thanh tao, trang nh&atilde;<\\/li>\\r\\n<li>Th&iacute;ch h\\u1ee3p l\\u1ed1i \\u0111i, c\\u1ea3nh quan c\\u1ea7n s\\u1ef1 tinh t\\u1ebf<\\/li>\\r\\n<\\/ul>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">3. C&acirc;y Rau V\\u1eebng (L\\u1ed9c V\\u1eebng Ven Bi\\u1ec3n)<\\/h4>\\r\\n<ul>\\r\\n<li>Sinh s\\u1ed1ng v&ugrave;ng ng\\u1eadp m\\u1eb7n, ven bi\\u1ec3n mi\\u1ec1n Nam<\\/li>\\r\\n<li>Qu\\u1ea3 m\\u1ecdc tr\\u1ef1c ti\\u1ebfp t\\u1eeb th&acirc;n &ndash; kh&aacute;c v\\u1edbi hai lo\\u1ea1i tr&ecirc;n<\\/li>\\r\\n<li>B&oacute;ng m&aacute;t t\\u1ed1t, gi\\u1eef \\u0111\\u1ea5t hi\\u1ec7u qu\\u1ea3<\\/li>\\r\\n<\\/ul>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">&Yacute; NGH\\u0128A PHONG TH\\u1ee6Y<\\/h3>\\r\\n<ul>\\r\\n<li><strong>Thu\\u1ed9c b\\u1ed9 t\\u1ee9:<\\/strong> Sanh &ndash; Sung &ndash; T&ugrave;ng &ndash; L\\u1ed9c (c&acirc;y \\u0111em l\\u1ea1i may m\\u1eafn)<\\/li>\\r\\n<li>\\u0110\\u1ea1i di\\u1ec7n cho nh&oacute;m Ph&uacute;c &ndash; L\\u1ed9c &ndash; Th\\u1ecd<\\/li>\\r\\n<li>L\\u1ed9c = T&agrave;i l\\u1ed9c, ti\\u1ec1n b\\u1ea1c<\\/li>\\r\\n<li>&ldquo;V\\u1eebng\\\" g\\u1ee3i nh\\u1eafc c&acirc;u &ldquo;V\\u1eebng \\u01a1i m\\u1edf c\\u1eeda ra&rdquo; &ndash; \\u0111&oacute;n may m\\u1eafn v&agrave;o nh&agrave;<\\/li>\\r\\n<li>Hoa \\u0111\\u1ecf: H\\u1ef7 s\\u1ef1, t&agrave;i l\\u1ed9c, may m\\u1eafn<\\/li>\\r\\n<li>Th&acirc;n ch\\u1eafc kh\\u1ecfe: S\\u1ef1 b\\u1ec1n v\\u1eefng, tr\\u01b0\\u1eddng t\\u1ed3n<\\/li>\\r\\n<li>T&aacute;n r\\u1ed9ng: G\\u1eafn k\\u1ebft, h&ograve;a thu\\u1eadn, sung t&uacute;c<\\/li>\\r\\n<li>Xua \\u0111u\\u1ed5i t&agrave; kh&iacute;, thu h&uacute;t n\\u0103ng l\\u01b0\\u1ee3ng t&iacute;ch c\\u1ef1c<\\/li>\\r\\n<\\/ul>\\r\\n<p><strong>M&agrave;u s\\u1eafc h\\u1ee3p m\\u1ec7nh:<\\/strong> Xanh (M\\u1ed9c), \\u0110\\u1ecf (H\\u1ecfa)<\\/p>\\r\\n<p><strong>H\\u1ee3p m\\u1ec7nh:<\\/strong> M\\u1ed9c, H\\u1ecfa<\\/p>\\r\\n<p><strong>H\\u1ee3p tu\\u1ed5i:<\\/strong> M\\u1eadu Ng\\u1ecd 1978, T&acirc;n D\\u1eadu 1981, \\u0110inh M&atilde;o 1987, K\\u1ef7 T\\u1ef5 1989&hellip;<\\/p>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">GI&Aacute; TR\\u1eca TRONG \\u0110\\u1edcI S\\u1ed0NG<\\/h3>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">1. T&ocirc; \\u0111i\\u1ec3m c\\u1ea3nh quan<\\/h4>\\r\\n<ul>\\r\\n<li>Ch&ugrave;m hoa \\u0111\\u1ecf r\\u1ef1c \\u0111\\u1eb9p nh\\u01b0 r&egrave;m thi&ecirc;n nhi&ecirc;n<\\/li>\\r\\n<li>T\\u1ea1o kh&ocirc;ng gian sang tr\\u1ecdng, th\\u01a1 m\\u1ed9ng<\\/li>\\r\\n<li>Gi\\u1ea3m n\\u1eafng n&oacute;ng, l&agrave;m m&aacute;t s&acirc;n v\\u01b0\\u1eddn<\\/li>\\r\\n<li>Ph&ugrave; h\\u1ee3p: bi\\u1ec7t th\\u1ef1, khu \\u0111&ocirc; th\\u1ecb, c&ocirc;ng vi&ecirc;n, resort<\\/li>\\r\\n<\\/ul>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">2. B\\u1ea3o v\\u1ec7 m&ocirc;i tr\\u01b0\\u1eddng<\\/h4>\\r\\n<ul>\\r\\n<li>L\\u1ed9c V\\u1eebng l\\u1ecdc b\\u1ee5i t\\u1ed1t, h\\u1ea5p th\\u1ee5 kh&iacute; \\u0111\\u1ed9c<\\/li>\\r\\n<li>T&aacute;n l&aacute; d&agrave;y gi&uacute;p gi\\u1ea3m nhi\\u1ec7t \\u0111\\u1ed9 m&ocirc;i tr\\u01b0\\u1eddng<\\/li>\\r\\n<li>H\\u1ec7 r\\u1ec5 gi\\u1eef \\u0111\\u1ea5t, ch\\u1ed1ng s\\u1ea1t l\\u1edf ven s&ocirc;ng, ven bi\\u1ec3n<\\/li>\\r\\n<\\/ul>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">3. Gi&aacute; tr\\u1ecb kinh t\\u1ebf<\\/h4>\\r\\n<ul>\\r\\n<li>C&acirc;y l&acirc;u n\\u0103m, d&aacute;ng \\u0111\\u1eb9p c&oacute; gi&aacute; tr\\u1ecb t\\u1eeb v&agrave;i tri\\u1ec7u \\u0111\\u1ebfn h&agrave;ng tr\\u0103m tri\\u1ec7u<\\/li>\\r\\n<li>\\u0110\\u01b0\\u1ee3c \\u01b0a chu\\u1ed9ng trong gi\\u1edbi s\\u01b0u t\\u1ea7m c&acirc;y c\\u1ea3nh<\\/li>\\r\\n<li>D&ugrave;ng t\\u1ea1o bonsai &ndash; c&agrave;ng l&acirc;u n\\u0103m c&agrave;ng qu&yacute;<\\/li>\\r\\n<\\/ul>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">4. C&ocirc;ng d\\u1ee5ng y h\\u1ecdc<\\/h4>\\r\\n<ul>\\r\\n<li>L&aacute; v&agrave; v\\u1ecf: gi\\u1ea3m vi&ecirc;m, tr\\u1ecb nhi\\u1ec5m khu\\u1ea9n<\\/li>\\r\\n<li>R\\u1ec5: h\\u1ed7 tr\\u1ee3 \\u0111i\\u1ec1u tr\\u1ecb cao huy\\u1ebft &aacute;p, \\u0111au nh\\u1ee9c x\\u01b0\\u01a1ng kh\\u1edbp<\\/li>\\r\\n<li>D\\u01b0\\u1ee3c li\\u1ec7u trong y h\\u1ecdc c\\u1ed5 truy\\u1ec1n d&acirc;n gian<\\/li>\\r\\n<\\/ul>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">B\\u1ea2NG GI&Aacute; S\\u1ea2N PH\\u1ea8M 2025<\\/h3>\\r\\n<table style=\\\"border-collapse: collapse; width: 100%; border-color: #3bb77e;\\\" border=\\\"1\\\" cellspacing=\\\"0\\\" cellpadding=\\\"10\\\">\\r\\n<tbody>\\r\\n<tr style=\\\"background: #3bb77e; color: white;\\\">\\r\\n<th>Chi\\u1ec1u cao c&acirc;y<\\/th>\\r\\n<th>Size b\\u1ea7u<\\/th>\\r\\n<th>C&ocirc;ng d\\u1ee5ng<\\/th>\\r\\n<th>Gi&aacute; (VN\\u0110)<\\/th>\\r\\n<\\/tr>\\r\\n<tr>\\r\\n<td>50&ndash;80cm<\\/td>\\r\\n<td>15&ndash;20cm<\\/td>\\r\\n<td>C&acirc;y gi\\u1ed1ng, bonsai nh\\u1ecf<\\/td>\\r\\n<td>80.000 &ndash; 120.000<\\/td>\\r\\n<\\/tr>\\r\\n<tr>\\r\\n<td>80cm&ndash;1,2m<\\/td>\\r\\n<td>20&ndash;25cm<\\/td>\\r\\n<td>S&acirc;n v\\u01b0\\u1eddn nh\\u1ecf, ch\\u1eadu c\\u1ea3nh<\\/td>\\r\\n<td>150.000 &ndash; 200.000<\\/td>\\r\\n<\\/tr>\\r\\n<tr>\\r\\n<td>1,2&ndash;1,5m<\\/td>\\r\\n<td>25&ndash;30cm<\\/td>\\r\\n<td>Bi\\u1ec7t th\\u1ef1, c\\u1ea3nh quan s&acirc;n v\\u01b0\\u1eddn<\\/td>\\r\\n<td>250.000 &ndash; 350.000<\\/td>\\r\\n<\\/tr>\\r\\n<tr>\\r\\n<td>1,5&ndash;2m<\\/td>\\r\\n<td>30&ndash;40cm<\\/td>\\r\\n<td>C&ocirc;ng vi&ecirc;n, khu \\u0111&ocirc; th\\u1ecb<\\/td>\\r\\n<td>450.000 &ndash; 600.000<\\/td>\\r\\n<\\/tr>\\r\\n<tr>\\r\\n<td>Tr&ecirc;n 2m<\\/td>\\r\\n<td>40&ndash;50cm<\\/td>\\r\\n<td>D\\u1ef1 &aacute;n l\\u1edbn, \\u0111\\u01b0\\u1eddng ph\\u1ed1<\\/td>\\r\\n<td><strong>Li&ecirc;n h\\u1ec7<\\/strong><\\/td>\\r\\n<\\/tr>\\r\\n<tr>\\r\\n<td>C&acirc;y \\u0111\\u1ea1i th\\u1ee5<\\/td>\\r\\n<td>Theo y&ecirc;u c\\u1ea7u<\\/td>\\r\\n<td>D\\u1ef1 &aacute;n cao c\\u1ea5p<\\/td>\\r\\n<td><strong>Li&ecirc;n h\\u1ec7<\\/strong><\\/td>\\r\\n<\\/tr>\\r\\n<\\/tbody>\\r\\n<\\/table>\\r\\n<p style=\\\"font-style: italic; margin-top: 10px;\\\">Gi&aacute; tham kh\\u1ea3o &ndash; thay \\u0111\\u1ed5i t&ugrave;y th\\u1eddi \\u0111i\\u1ec3m, d&aacute;ng c&acirc;y v&agrave; s\\u1ed1 l\\u01b0\\u1ee3ng.<\\/p>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">V\\u1eca TR&Iacute; TR\\u1ed2NG PH&Ugrave; H\\u1ee2P<\\/h3>\\r\\n<ul>\\r\\n<li>S&acirc;n v\\u01b0\\u1eddn bi\\u1ec7t th\\u1ef1, villa, resort<\\/li>\\r\\n<li>C&ocirc;ng vi&ecirc;n \\u0111&ocirc; th\\u1ecb, khu sinh ho\\u1ea1t c\\u1ed9ng \\u0111\\u1ed3ng<\\/li>\\r\\n<li>C&acirc;y tuy\\u1ebfn \\u0111\\u01b0\\u1eddng, d\\u1ea3i ph&acirc;n c&aacute;ch<\\/li>\\r\\n<li>Tr\\u01b0\\u1edbc nh&agrave; &ndash; t\\u0103ng t&agrave;i l\\u1ed9c<\\/li>\\r\\n<li>Qu&aacute;n x&aacute;, c\\u1eeda h&agrave;ng, khu kinh doanh<\\/li>\\r\\n<li>Khu sinh th&aacute;i, d\\u1ef1 &aacute;n c\\u1ea3nh quan<\\/li>\\r\\n<li>V&ugrave;ng ven bi\\u1ec3n &ndash; ch\\u1ed1ng x&oacute;i m&ograve;n<\\/li>\\r\\n<\\/ul>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">H\\u01af\\u1edaNG D\\u1eaaN TR\\u1ed2NG C&Acirc;Y<\\/h3>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">Ph\\u01b0\\u01a1ng ph&aacute;p chi\\u1ebft c&agrave;nh (Khuy\\u1ebfn ngh\\u1ecb)<\\/h4>\\r\\n<ol>\\r\\n<li>Chu\\u1ea9n b\\u1ecb dao s\\u1eafc, \\u0111\\u1ea5t t\\u01a1i x\\u1ed1p, t&uacute;i nilon, tr\\u1ea5u, r\\u1ec5 b&egrave;o<\\/li>\\r\\n<li>Khoanh v\\u1ecf c&agrave;nh kh\\u1ecfe, b&oacute; h\\u1ed7n h\\u1ee3p \\u0111\\u1ea5t &ndash; tr\\u1ea5u &ndash; r\\u1ec5 b&egrave;o<\\/li>\\r\\n<li>B\\u1ecdc nilon gi\\u1eef \\u1ea9m \\u0111\\u1ec3 r\\u1ec5 ph&aacute;t tri\\u1ec3n<\\/li>\\r\\n<li>C\\u1eaft c&agrave;nh chi\\u1ebft v&agrave; tr\\u1ed3ng v&agrave;o ch\\u1eadu\\/h\\u1ed1 \\u0111\\u1ea5t<\\/li>\\r\\n<li>Th\\u1eddi \\u0111i\\u1ec3m t\\u1ed1t nh\\u1ea5t: Th&aacute;ng 6&ndash;7<\\/li>\\r\\n<\\/ol>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">Gieo h\\u1ea1t (&iacute;t d&ugrave;ng h\\u01a1n)<\\/h4>\\r\\n<ul>\\r\\n<li>Ch\\u1ecdn h\\u1ea1t t\\u1eeb qu\\u1ea3 ch&iacute;n<\\/li>\\r\\n<li>Gieo v&agrave;o \\u0111\\u1ea5t t\\u01a1i, gi\\u1eef \\u1ea9m<\\/li>\\r\\n<li>\\u01af\\u01a1m c&acirc;y non cho \\u0111\\u1ebfn khi \\u0111\\u1ea1t \\u0111\\u1ee7 chi\\u1ec1u cao<\\/li>\\r\\n<\\/ul>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">Tr\\u1ed3ng ngo&agrave;i v\\u01b0\\u1eddn<\\/h4>\\r\\n<ul>\\r\\n<li>\\u0110&agrave;o h\\u1ed1 s&acirc;u v\\u1eeba ph\\u1ea3i<\\/li>\\r\\n<li>\\u0110\\u1eb7t c&acirc;y, l\\u1ea5p \\u0111\\u1ea5t, c\\u1ed1 \\u0111\\u1ecbnh th&acirc;n<\\/li>\\r\\n<li>T\\u01b0\\u1edbi \\u0111\\u1ec1u v&agrave; che n\\u1eafng nh\\u1eb9 ban \\u0111\\u1ea7u<\\/li>\\r\\n<\\/ul>\\r\\n<h4 style=\\\"color: #3bb77e;\\\">Tr\\u1ed3ng ch\\u1eadu<\\/h4>\\r\\n<ul>\\r\\n<li>D&ugrave;ng ch\\u1eadu s&acirc;u, r\\u1ed9ng<\\/li>\\r\\n<li>\\u0110\\u1ea5t t\\u01a1i, tho&aacute;t n\\u01b0\\u1edbc t\\u1ed1t<\\/li>\\r\\n<li>Th&iacute;ch h\\u1ee3p tr\\u1ed3ng bonsai ho\\u1eb7c c&acirc;y c\\u1ea3nh nh\\u1ecf<\\/li>\\r\\n<\\/ul>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">H\\u01af\\u1edaNG D\\u1eaaN CH\\u0102M S&Oacute;C<\\/h3>\\r\\n<p><strong style=\\\"color: #3bb77e;\\\">\\u0110\\u1ea5t tr\\u1ed3ng:<\\/strong> \\u0110\\u1ea5t t\\u01a1i x\\u1ed1p, pha tr\\u1ea5u, x\\u01a1 d\\u1eeba, ph&acirc;n chu\\u1ed3ng hoai.<\\/p>\\r\\n<p><strong style=\\\"color: #3bb77e;\\\">T\\u01b0\\u1edbi n\\u01b0\\u1edbc:<\\/strong><\\/p>\\r\\n<ul>\\r\\n<li>C&acirc;y non: t\\u01b0\\u1edbi 2 l\\u1ea7n\\/ng&agrave;y<\\/li>\\r\\n<li>C&acirc;y l\\u1edbn: t\\u01b0\\u1edbi v\\u1eeba \\u0111\\u1ee7, tr&aacute;nh ng\\u1eadp &uacute;ng<\\/li>\\r\\n<\\/ul>\\r\\n<p><strong style=\\\"color: #3bb77e;\\\">&Aacute;nh s&aacute;ng:<\\/strong> \\u01afa s&aacute;ng m\\u1ea1nh, c\\u1ea7n n\\u01a1i tho&aacute;ng.<\\/p>\\r\\n<p><strong style=\\\"color: #3bb77e;\\\">B&oacute;n ph&acirc;n:<\\/strong><\\/p>\\r\\n<ul>\\r\\n<li>C&acirc;y nh\\u1ecf: B&oacute;n ph&acirc;n \\u0111\\u1ecbnh k\\u1ef3 m\\u1ed7i th&aacute;ng<\\/li>\\r\\n<li>C&acirc;y l\\u1edbn: B&oacute;n nh\\u1eb9<\\/li>\\r\\n<li>C&acirc;y s\\u1eafp ra hoa: B\\u1ed5 sung ph&acirc;n k&iacute;ch th&iacute;ch n\\u1ee5<\\/li>\\r\\n<\\/ul>\\r\\n<p><strong style=\\\"color: #3bb77e;\\\">Nhi\\u1ec7t \\u0111\\u1ed9:<\\/strong> Th&iacute;ch nghi t\\u1ed1t, ch\\u1ecbu n\\u1eafng n&oacute;ng l\\u1eabn r&eacute;t nh\\u1eb9.<\\/p>\\r\\n<p><strong style=\\\"color: #3bb77e;\\\">Ph&ograve;ng s&acirc;u b\\u1ec7nh:<\\/strong> Ki\\u1ec3m tra l&aacute;, t\\u1ec9a c&agrave;nh s&acirc;u, d&ugrave;ng bi\\u1ec7n ph&aacute;p sinh h\\u1ecdc.<\\/p>\\r\\n<p><strong style=\\\"color: #3bb77e;\\\">Ch\\u0103m s&oacute;c c&acirc;y m\\u1edbi tr\\u1ed3ng:<\\/strong> Che n\\u1eafng 5&ndash;7 ng&agrave;y \\u0111\\u1ea7u, t\\u01b0\\u1edbi gi\\u1eef \\u1ea9m.<\\/p>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">L\\u01afU &Yacute; KHI CH\\u1eccN MUA C&Acirc;Y<\\/h3>\\r\\n<ul>\\r\\n<li>L&aacute; xanh, kh&ocirc;ng v&agrave;ng &uacute;a<\\/li>\\r\\n<li>Th&acirc;n th\\u1eb3ng, kh\\u1ecfe, kh&ocirc;ng s\\u1eb9o b\\u1ec7nh<\\/li>\\r\\n<li>R\\u1ec5 tr\\u1eafng, ch\\u1eafc, kh&ocirc;ng b\\u1ecb th\\u1ed1i<\\/li>\\r\\n<li>K&iacute;ch th\\u01b0\\u1edbc ph&ugrave; h\\u1ee3p kh&ocirc;ng gian d\\u1ef1 &aacute;n<\\/li>\\r\\n<li>Y&ecirc;u c\\u1ea7u gi\\u1ea5y ch\\u1ee9ng nh\\u1eadn ngu\\u1ed3n g\\u1ed1c<\\/li>\\r\\n<\\/ul>\\r\\n<h3 style=\\\"color: #3bb77e; margin-top: 30px;\\\">\\u01afU TH\\u1ebe KHI CH\\u1eccN C&Acirc;Y L\\u1ed8C V\\u1eeaNG TRONG C\\u1ea2NH QUAN<\\/h3>\\r\\n<p>C&acirc;y L\\u1ed9c V\\u1eebng kh&ocirc;ng ch\\u1ec9 t\\u1ea1o b&oacute;ng m&aacute;t m&agrave; c&ograve;n mang l\\u1ea1i s\\u1ef1 th\\u1ecbnh v\\u01b0\\u1ee3ng, may m\\u1eafn v&agrave; v\\u1ebb \\u0111\\u1eb9p sinh th&aacute;i b\\u1ec1n v\\u1eefng. T&aacute;n l&aacute; r\\u1ed9ng, r\\u1ec5 kh\\u1ecfe v&agrave; hoa r\\u0169 xu\\u1ed1ng th\\u01a1 m\\u1ed9ng khi\\u1ebfn c&acirc;y tr\\u1edf th&agrave;nh \\u0111i\\u1ec3m nh\\u1ea5n tuy\\u1ec7t \\u0111\\u1eb9p trong m\\u1ecdi c&ocirc;ng tr&igrave;nh.<\\/p>\\r\\n<\\/div>\",\"short_description\":null,\"price\":\"50000.00\",\"sale_price\":\"9900.00\",\"cost_price\":\"2000.00\",\"stock_quantity\":100,\"meta_title\":\"C\\u00e2y L\\u1ed9c V\\u1eebng: \\u0110\\u1eb7c \\u0110i\\u1ec3m, \\u00dd Ngh\\u0129a v\\u00e0 B\\u1ea3ng Gi\\u00e1 C\\u1eadp Nh\\u1eadt\",\"meta_description\":\"C\\u00e2y L\\u1ed9c V\\u1eebng l\\u00e0 l\\u1ef1a ch\\u1ecdn l\\u00fd t\\u01b0\\u1edfng cho s\\u00e2n v\\u01b0\\u1eddn, c\\u00f4ng tr\\u00ecnh v\\u00e0 khu \\u0111\\u00f4 th\\u1ecb nh\\u1edd t\\u00e1n r\\u1ed9ng, hoa \\u0111\\u1eb9p v\\u00e0 s\\u1ee9c s\\u1ed1ng m\\u1ea1nh. Nh\\u1eadn b\\u00e1o gi\\u00e1 s\\u1ec9 theo s\\u1ed1 l\\u01b0\\u1ee3ng, h\\u1ed7 tr\\u1ee3 v\\u1eadn chuy\\u1ec3n to\\u00e0n qu\\u1ed1c.\",\"meta_keywords\":[\"c\\u00e2y l\\u1ed9c v\\u1eebng\",\"c\\u00e2y c\\u00f4ng tr\\u00ecnh b\\u00f3ng m\\u00e1t\",\"c\\u00e2y l\\u1ed9c v\\u1eebng hoa \\u0111\\u1ecf\",\"l\\u1ed9c v\\u1eebng c\\u00f4ng tr\\u00ecnh\",\"gi\\u00e1 c\\u00e2y l\\u1ed9c v\\u1eebng\",\"c\\u00e2y b\\u00f3ng m\\u00e1t \\u0111\\u00f4 th\\u1ecb\",\"c\\u00e2y c\\u1ea3nh quan\",\"c\\u00e2y tr\\u1ed3ng bi\\u1ec7t th\\u1ef1\",\"c\\u00e2y l\\u1ed9c v\\u1eebng gi\\u00e1 s\\u1ec9\"],\"meta_canonical\":\"https:\\/\\/xanhworld.vn\\/\\/san-pham\\/cay-loc-vung-dac-diem-y-nghia-va-bang-gia-cap-nhat\",\"primary_category_id\":\"1\",\"category_included_ids\":null,\"category_ids\":[\"1\",\"8\"],\"tag_ids\":[30,31,32,33,34,35,36],\"image_ids\":[23],\"is_featured\":true,\"locked_by\":null,\"locked_at\":null,\"created_by\":\"1\",\"is_active\":true,\"category_ids_backup\":null,\"created_at\":\"2025-12-07T14:34:47.000000Z\",\"updated_at\":\"2025-12-07T14:34:47.000000Z\"}', '113.23.88.250', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 14:34:47', '2025-12-07 14:34:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `addresses`
--

CREATE TABLE `addresses` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính địa chỉ',
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(191) NOT NULL COMMENT 'Họ tên người nhận',
  `phone_number` varchar(191) NOT NULL COMMENT 'SĐT',
  `detail_address` varchar(191) NOT NULL COMMENT 'Địa chỉ chi tiết',
  `ward` varchar(191) DEFAULT NULL COMMENT 'Phường',
  `district` varchar(191) NOT NULL COMMENT 'Quận',
  `province` varchar(191) NOT NULL COMMENT 'Tỉnh',
  `province_code` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Mã tỉnh',
  `district_code` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Mã huyện',
  `ward_code` varchar(191) DEFAULT NULL COMMENT 'Mã phường',
  `postal_code` varchar(191) NOT NULL COMMENT 'Mã bưu chính',
  `country` varchar(191) NOT NULL COMMENT 'Quốc gia',
  `latitude` varchar(191) DEFAULT NULL COMMENT 'Vĩ độ',
  `longitude` varchar(191) DEFAULT NULL COMMENT 'Kinh độ',
  `address_type` varchar(191) NOT NULL DEFAULT 'home' COMMENT 'Loại địa chỉ',
  `notes` text DEFAULT NULL COMMENT 'Ghi chú',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Địa chỉ mặc định',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `address_audits`
--

CREATE TABLE `address_audits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `address_id` bigint(20) UNSIGNED NOT NULL,
  `performed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(191) NOT NULL COMMENT 'Hành động thực hiện',
  `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết',
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Các thay đổi' CHECK (json_valid(`changes`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `affiliates`
--

CREATE TABLE `affiliates` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính affiliate',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(191) NOT NULL COMMENT 'Mã giới thiệu duy nhất',
  `clicks` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số lượt click',
  `conversions` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số lượt chuyển đổi',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Phần trăm hoa hồng (0-100)',
  `total_commission` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Hoa hồng tích lũy',
  `referral_url` varchar(191) DEFAULT NULL COMMENT 'URL giới thiệu',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT 'Trạng thái',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banners`
--

CREATE TABLE `banners` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính banner',
  `title` varchar(191) DEFAULT NULL COMMENT 'Tiêu đề banner',
  `description` text DEFAULT NULL COMMENT 'Mô tả banner',
  `image_desktop` varchar(191) NOT NULL COMMENT 'Đường dẫn hình ảnh',
  `image_mobile` varchar(191) DEFAULT NULL COMMENT 'Đường dẫn hình ảnh mobile',
  `link` varchar(191) DEFAULT NULL COMMENT 'Liên kết khi click vào banner',
  `target` varchar(10) NOT NULL DEFAULT '_blank' COMMENT 'Target của link (_blank, _self)',
  `position` varchar(191) DEFAULT NULL COMMENT 'Vị trí hiển thị trên trang',
  `order` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `start_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian bắt đầu hiển thị',
  `end_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian kết thúc hiển thị',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái hoạt động',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `banners`
--

INSERT INTO `banners` (`id`, `title`, `description`, `image_desktop`, `image_mobile`, `link`, `target`, `position`, `order`, `start_at`, `end_at`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Banner 1', NULL, 'banner-1764904920-69324fd8b396e.jpg', 'banner-1764904920-69324fd8b4f44.jpg', NULL, '_blank', 'homepage_banner_parent', 0, NULL, NULL, 1, '2025-11-25 00:55:45', '2025-12-05 03:22:00', NULL),
(2, 'Banner 2', NULL, 'Banner-trang-chu-nobifashion.png', NULL, NULL, '_blank', 'homepage_banner_parent', 1, NULL, NULL, 0, '2025-11-25 00:55:45', '2025-12-05 03:47:05', NULL),
(3, 'Banner 3', NULL, 'banner-trang-home.png', NULL, NULL, '_blank', 'homepage_banner_parent', 2, NULL, NULL, 0, '2025-11-25 00:57:27', '2025-12-05 03:47:09', NULL),
(4, 'Banner con 1', NULL, 'banner-1764906383-6932558f9fa50.jpg', 'banner-1764906383-6932558fa068e.jpg', NULL, '_blank', 'homepage_banner_children', 0, NULL, NULL, 1, '2025-11-25 02:22:00', '2025-12-05 03:46:23', NULL),
(5, 'THẾ GIỚI CÂY XANH XWORLD - chúc mừng giáng sinh 2025 | Miễn phí ship toàn bộ sản phẩm', NULL, 'banner-1764906704-693256d0816ab.png', 'banner-1764906704-693256d08224e.png', NULL, '_blank', 'homepage_banner_children', 1, NULL, NULL, 1, '2025-11-25 02:22:00', '2025-12-05 03:51:44', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache`
--

CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(191) NOT NULL,
  `owner` varchar(191) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `carts`
--

CREATE TABLE `carts` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính giỏ hàng',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` varchar(191) DEFAULT NULL COMMENT 'ID session khách vãng lai',
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Tùy chọn sản phẩm (màu, size...)' CHECK (json_valid(`options`)),
  `status` enum('active','ordered','abandoned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `carts`
--

INSERT INTO `carts` (`id`, `account_id`, `session_id`, `product_id`, `options`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', 1, NULL, 'active', '2025-12-05 11:07:20', '2025-12-05 11:07:20'),
(2, NULL, 'u4lRTGD6VTv8Zb5MUhPcKemiecMU5Kj9QLIMjykd', 1, NULL, 'active', '2025-12-06 01:26:24', '2025-12-06 01:26:24'),
(3, 1, NULL, 1, NULL, 'active', '2025-12-06 03:46:43', '2025-12-06 03:46:43'),
(4, NULL, '1SewiPAV4Zq4ekL3VXDmVoy5WU4Lcav9DMrjNZ6X', 1, NULL, 'active', '2025-12-08 12:38:59', '2025-12-08 12:38:59'),
(5, NULL, 'DIPaUpyYre4Z5Knk9tA0RzezeXMvYVdId7FtAh0C', 2, NULL, 'active', '2025-12-09 09:01:21', '2025-12-09 09:01:21'),
(6, NULL, 'cM0kAGBBIbJ730m2xiIeyQA3LpwalFf7gLvEEobZ', 1, NULL, 'active', '2025-12-09 11:14:05', '2025-12-09 11:14:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính mục giỏ hàng',
  `uuid` char(36) DEFAULT NULL COMMENT 'Mã định danh duy nhất của item',
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `product_variant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID biến thể sản phẩm (nếu có)',
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Số lượng',
  `price` decimal(15,2) NOT NULL COMMENT 'Giá tại thời điểm thêm vào giỏ',
  `total_price` decimal(10,0) NOT NULL DEFAULT 0 COMMENT 'Thành tiền = price * quantity',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Thuộc tính bổ sung (size, màu...)' CHECK (json_valid(`options`)),
  `status` enum('active','removed') NOT NULL DEFAULT 'active',
  `is_flash_sale` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đánh dấu sản phẩm flash sale',
  `flash_sale_item_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID flash sale item tương ứng (nếu có)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cart_items`
--

INSERT INTO `cart_items` (`id`, `uuid`, `cart_id`, `product_id`, `product_variant_id`, `quantity`, `price`, `total_price`, `options`, `status`, `is_flash_sale`, `flash_sale_item_id`, `created_at`, `updated_at`) VALUES
(1, '6d1137ee-014d-4e15-9291-cad2d468fabe', 1, 1, NULL, 2, 899000.00, 0, NULL, 'active', 0, NULL, '2025-12-05 11:07:20', '2025-12-09 02:34:56'),
(2, '9f39b6f5-1473-4af5-ad98-6dbc8701c3b8', 2, 1, NULL, 4, 899000.00, 0, NULL, 'active', 0, NULL, '2025-12-06 01:26:24', '2025-12-06 01:26:28'),
(3, 'c325c455-a7dd-4386-b19e-88275808db79', 3, 1, NULL, 1, 899000.00, 0, NULL, 'active', 0, NULL, '2025-12-06 03:46:43', '2025-12-06 03:46:43'),
(4, '5fe9f5ff-f75c-4cdf-b008-5ceef193449d', 4, 1, NULL, 1, 899000.00, 0, NULL, 'active', 0, NULL, '2025-12-08 12:38:59', '2025-12-08 12:38:59'),
(5, '550b1872-45c4-4bad-962f-4d7e67f99cd9', 3, 4, NULL, 2, 9900.00, 0, NULL, 'active', 0, NULL, '2025-12-09 01:31:13', '2025-12-09 01:31:13'),
(6, '6c7dfe4c-f4af-4633-abba-84041dc70c1a', 3, 2, NULL, 4, 299000.00, 0, NULL, 'active', 0, NULL, '2025-12-09 06:45:00', '2025-12-09 06:57:25'),
(7, 'a9459881-250e-40d4-8be2-95dc975232cc', 3, 3, NULL, 7, 199000.00, 0, NULL, 'active', 0, NULL, '2025-12-09 07:11:14', '2025-12-09 08:50:27'),
(8, 'c39b3ff0-9774-46dc-a959-f592a9b8ec00', 1, 2, NULL, 1, 299000.00, 0, NULL, 'active', 0, NULL, '2025-12-09 07:19:56', '2025-12-09 07:19:56'),
(9, 'df861cdd-16e9-4ca7-8091-fd68935e7d87', 5, 2, NULL, 2, 299000.00, 0, NULL, 'active', 0, NULL, '2025-12-09 09:01:21', '2025-12-09 09:01:24'),
(10, 'aa2b8a50-6c55-4256-aa85-395aa62b1e32', 6, 1, NULL, 1, 899000.00, 0, NULL, 'active', 0, NULL, '2025-12-09 11:14:05', '2025-12-09 11:14:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính danh mục',
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL COMMENT 'Tên danh mục',
  `slug` varchar(191) NOT NULL COMMENT 'Slug duy nhất',
  `description` text DEFAULT NULL COMMENT 'Mô tả danh mục',
  `image` varchar(191) DEFAULT NULL COMMENT 'Ảnh đại diện danh mục',
  `order` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái hoạt động',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Meta SEO JSON' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `image`, `order`, `is_active`, `metadata`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Cây Cảnh', 'cay-canh', NULL, NULL, 1, 1, NULL, '2025-11-24 21:02:52', '2025-11-24 21:02:52'),
(2, NULL, 'Cây Phong Thủy', 'cay-phong-thuy', NULL, NULL, 0, 1, NULL, '2025-11-24 21:02:52', '2025-11-24 21:02:52'),
(3, NULL, 'Cây Hoa Đẹp', 'cay-hoa-dep', NULL, NULL, 2, 1, NULL, '2025-11-24 21:02:52', '2025-11-24 21:02:52'),
(4, NULL, 'Sen Đá – Xương Rồng', 'sen-da-xuong-rong', NULL, NULL, 3, 1, NULL, '2025-11-24 21:02:52', '2025-11-24 21:02:52'),
(5, NULL, 'Chậu Cây – Phụ Kiện', 'chau-cay-phu-kien', NULL, NULL, 4, 1, NULL, '2025-11-24 21:02:52', '2025-11-24 21:02:52'),
(6, 1, 'Cây để bàn', 'cay-de-ban', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(7, 1, 'Cây trong nhà', 'cay-trong-nha', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(8, 1, 'Cây ngoài trời', 'cay-ngoai-troi', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(9, 1, 'Cây mini', 'cay-mini', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(10, 1, 'Cây bonsai', 'cay-bonsai', NULL, 'cay-bonsai-C99gR4bQ.webp', 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-30 08:24:36'),
(11, 1, 'Cây thủy sinh', 'cay-thuy-sinh', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(12, 2, 'Cây mang ý nghĩa tài lộc', 'cay-y-nghia-tai-loc', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(13, 2, 'Cây hút tài – chiêu lộc', 'cay-hut-tai-chieu-loc', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(14, 2, 'Cây hợp mệnh', 'cay-hop-menh', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(15, 2, 'Cây hợp tuổi', 'cay-hop-tuoi', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(16, 2, 'Cây để bàn phong thủy', 'cay-de-ban-phong-thuy', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(17, 2, 'Cây nội thất phong thủy', 'cay-noi-that-phong-thuy', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:08', '2025-11-24 21:03:08'),
(18, 14, 'Cây hợp mệnh Kim', 'cay-hop-menh-kim', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:45', '2025-11-24 21:03:45'),
(19, 14, 'Cây hợp mệnh Mộc', 'cay-hop-menh-moc', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:45', '2025-11-24 21:03:45'),
(20, 14, 'Cây hợp mệnh Thủy', 'cay-hop-menh-thuy', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:45', '2025-11-24 21:03:45'),
(21, 14, 'Cây hợp mệnh Hỏa', 'cay-hop-menh-hoa', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:45', '2025-11-24 21:03:45'),
(22, 14, 'Cây hợp mệnh Thổ', 'cay-hop-menh-tho', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:45', '2025-11-24 21:03:45'),
(23, 3, 'Hoa để bàn', 'hoa-de-ban', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(24, 3, 'Hoa trong nhà', 'hoa-trong-nha', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(25, 3, 'Hoa ngoài trời', 'hoa-ngoai-troi', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(26, 3, 'Hoa phong thủy', 'hoa-phong-thuy', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(27, 3, 'Hoa leo – hoa dây', 'hoa-leo', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(28, 4, 'Sen đá các loại', 'sen-da-cac-loai', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(29, 4, 'Xương rồng mini', 'xuong-rong-mini', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(30, 4, 'Combo sen đá', 'combo-sen-da', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(31, 5, 'Chậu cây', 'chau-cay', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(32, 5, 'Đất – giá thể', 'dat-gia-the', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(33, 5, 'Dụng cụ làm vườn', 'dung-cu-lam-vuon', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55'),
(34, 5, 'Bình tưới – phụ kiện', 'binh-tuoi-phu-kien', NULL, NULL, 0, 1, NULL, '2025-11-24 21:03:55', '2025-11-24 21:03:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `comments`
--

CREATE TABLE `comments` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính bình luận',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL COMMENT 'Session của khách vãng lai',
  `commentable_id` bigint(20) UNSIGNED NOT NULL COMMENT 'ID đối tượng được bình luận',
  `commentable_type` varchar(191) NOT NULL COMMENT 'Kiểu model (Post/Product/...)',
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `content` text NOT NULL COMMENT 'Nội dung bình luận',
  `name` varchar(191) DEFAULT NULL COMMENT 'Tên người bình luận (khách)',
  `email` varchar(191) DEFAULT NULL COMMENT 'Email khách',
  `is_approved` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Duyệt bình luận',
  `rating` int(11) DEFAULT NULL COMMENT 'Đánh giá (1-5, chỉ áp dụng cho sản phẩm)',
  `ip` varchar(191) DEFAULT NULL COMMENT 'IP người bình luận',
  `user_agent` text DEFAULT NULL COMMENT 'User agent của người bình luận',
  `is_reported` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đã báo cáo',
  `reports_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lần báo cáo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `comments`
--

INSERT INTO `comments` (`id`, `account_id`, `session_id`, `commentable_id`, `commentable_type`, `parent_id`, `content`, `name`, `email`, `is_approved`, `rating`, `ip`, `user_agent`, `is_reported`, `reports_count`, `created_at`, `updated_at`) VALUES
(1, NULL, 'u4lRTGD6VTv8Zb5MUhPcKemiecMU5Kj9QLIMjykd', 1, 'product', NULL, 'Loại cây phong thủy này rất hợp để trong nhà, dễ mang lại tài lộc cho gia đình, giá cả thì hợp lý, cho shop 5 sao', 'Văn Mạnh', 'nguyenvanmanh@gmail.com', 1, 5, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 0, 0, '2025-12-06 01:27:37', '2025-12-06 02:40:35'),
(2, 1, NULL, 1, 'product', 1, 'Cảm ơn bạn đã quan tâm và đánh giá 5 sao cho shop, chúc bạn ngày mới vui vẻ, XWORLD tặng bạn voucher 10% giảm giá tất cả sản phẩm, inbox shop để nhận nhé! Cảm ơn Văn Mạnh!', NULL, NULL, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 0, 0, '2025-12-06 02:42:10', '2025-12-06 02:42:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính liên hệ',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL COMMENT 'Tên người gửi',
  `email` varchar(191) DEFAULT NULL COMMENT 'Email người gửi',
  `phone` varchar(191) DEFAULT NULL COMMENT 'Số điện thoại',
  `subject` varchar(191) DEFAULT NULL COMMENT 'Tiêu đề',
  `message` text DEFAULT NULL COMMENT 'Nội dung liên hệ',
  `attachment_path` text DEFAULT NULL COMMENT 'Đường dẫn tệp đính kèm của khách hàng',
  `ip` varchar(191) DEFAULT NULL COMMENT 'Địa chỉ IP',
  `status` varchar(30) NOT NULL DEFAULT 'new' COMMENT 'Trạng thái xử lý: new, processing, done, spam',
  `source` varchar(100) DEFAULT NULL COMMENT 'Nguồn liên hệ: contact_form, landing_page, popup, ...',
  `admin_note` text DEFAULT NULL COMMENT 'Ghi chú nội bộ của admin',
  `last_replied_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian trả lời cuối cùng',
  `reply_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số lần đã trả lời khách',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đã đọc hay chưa',
  `created_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm gửi',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm cập nhật',
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `contact_replies`
--

CREATE TABLE `contact_replies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message` longtext NOT NULL COMMENT 'Nội dung trả lời (HTML)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `emails`
--

CREATE TABLE `emails` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính tài khoản email gửi đi',
  `email` varchar(191) NOT NULL COMMENT 'Địa chỉ email gửi',
  `name` varchar(191) NOT NULL COMMENT 'Tên hiển thị',
  `description` text DEFAULT NULL COMMENT 'Mô tả thêm',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đánh dấu làm tài khoản mặc định',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái hoạt động',
  `order` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự ưu tiên',
  `mail_host` varchar(191) DEFAULT NULL COMMENT 'SMTP Host',
  `mail_port` int(11) DEFAULT NULL COMMENT 'SMTP Port',
  `mail_username` varchar(191) DEFAULT NULL COMMENT 'SMTP Username',
  `mail_password` text DEFAULT NULL COMMENT 'SMTP Password',
  `mail_encryption` varchar(191) DEFAULT NULL COMMENT 'Kiểu mã hóa (ssl/tls)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `email_templates`
--

CREATE TABLE `email_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(191) NOT NULL COMMENT 'Unique key: order_confirmation, password_reset, etc.',
  `name` varchar(191) NOT NULL COMMENT 'Tên template',
  `subject` varchar(191) NOT NULL COMMENT 'Subject email',
  `body` text NOT NULL COMMENT 'Nội dung email (HTML)',
  `variables` text DEFAULT NULL COMMENT 'JSON: Danh sách biến có thể dùng',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `favorites`
--

CREATE TABLE `favorites` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính yêu thích',
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL COMMENT 'Session khách vãng lai',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `favorites`
--

INSERT INTO `favorites` (`id`, `product_id`, `account_id`, `session_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, '2025-12-05 08:02:36', '2025-12-05 08:02:36'),
(7, 1, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', '2025-12-09 02:43:24', '2025-12-09 02:43:24'),
(3, 1, NULL, 'u4lRTGD6VTv8Zb5MUhPcKemiecMU5Kj9QLIMjykd', '2025-12-06 01:26:22', '2025-12-06 01:26:22'),
(22, 3, 1, NULL, '2025-12-10 04:49:02', '2025-12-10 04:49:02'),
(5, 1, NULL, '1SewiPAV4Zq4ekL3VXDmVoy5WU4Lcav9DMrjNZ6X', '2025-12-08 12:38:56', '2025-12-08 12:38:56'),
(9, 2, 1, NULL, '2025-12-09 06:56:57', '2025-12-09 06:56:57'),
(23, 4, 1, NULL, '2025-12-10 07:52:03', '2025-12-10 07:52:03'),
(19, 2, NULL, 'nDzM0vLEgozZe4COQzhDNVZMwCEqBWyz460trDTH', '2025-12-09 09:00:52', '2025-12-09 09:00:52');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `flash_sales`
--

CREATE TABLE `flash_sales` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính chương trình flash sale',
  `title` varchar(191) NOT NULL COMMENT 'Tên chương trình',
  `description` text DEFAULT NULL COMMENT 'Mô tả',
  `banner` varchar(191) DEFAULT NULL COMMENT 'Ảnh banner',
  `tag` varchar(50) DEFAULT NULL COMMENT 'Tag hiển thị',
  `start_time` timestamp NOT NULL COMMENT 'Bắt đầu',
  `end_time` timestamp NOT NULL COMMENT 'Kết thúc',
  `status` enum('draft','active','expired') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Kích hoạt?',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `max_per_user` int(10) UNSIGNED DEFAULT NULL COMMENT 'Giới hạn mỗi người',
  `display_limit` int(10) UNSIGNED NOT NULL DEFAULT 20 COMMENT 'Giới hạn hiển thị',
  `product_add_mode` enum('auto_by_category','manual') DEFAULT NULL COMMENT 'Cách thêm sản phẩm',
  `views` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Lượt xem',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `flash_sale_items`
--

CREATE TABLE `flash_sale_items` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính mục flash sale',
  `flash_sale_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `original_price` decimal(15,2) DEFAULT NULL COMMENT 'Giá gốc',
  `sale_price` decimal(15,2) NOT NULL COMMENT 'Giá sale',
  `unified_price` decimal(15,2) DEFAULT NULL COMMENT 'Giá đồng nhất',
  `original_variant_price` decimal(15,2) DEFAULT NULL COMMENT 'Giá gốc biến thể',
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Kho',
  `sold` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Đã bán',
  `max_per_user` int(10) UNSIGNED DEFAULT NULL COMMENT 'Giới hạn mỗi người',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Kích hoạt',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Thứ tự',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `flash_sale_price_logs`
--

CREATE TABLE `flash_sale_price_logs` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính',
  `flash_sale_item_id` bigint(20) UNSIGNED NOT NULL,
  `old_price` decimal(15,2) NOT NULL COMMENT 'Giá cũ',
  `new_price` decimal(15,2) NOT NULL COMMENT 'Giá mới',
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian thay đổi',
  `reason` text DEFAULT NULL COMMENT 'Lý do thay đổi',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `images`
--

CREATE TABLE `images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `url` text DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL COMMENT 'Tiêu đề ảnh',
  `notes` text DEFAULT NULL COMMENT 'Ghi chú thêm cho ảnh',
  `alt` varchar(191) DEFAULT NULL COMMENT 'Alt text cho SEO',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Ảnh chính của sản phẩm',
  `order` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `images`
--

INSERT INTO `images` (`id`, `url`, `title`, `notes`, `alt`, `is_primary`, `order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'cay-phat-tai-de-ban-gia-tot.webp', 'Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', NULL, 'Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', 1, 0, '2025-12-05 07:43:56', '2025-12-05 10:02:03', NULL),
(2, 'cay-phat-tai-1.webp', 'Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', NULL, 'Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', 0, 1, '2025-12-05 07:43:56', '2025-12-05 10:02:03', NULL),
(3, 'cay-phat-tai.webp', 'Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', NULL, 'Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', 0, 2, '2025-12-05 07:43:56', '2025-12-05 10:02:03', NULL),
(4, 'cay-phat-tai-khuc-bo-3-nho.webp', 'Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', NULL, 'Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', 0, 3, '2025-12-05 07:43:56', '2025-12-05 10:02:03', NULL),
(5, 'cay-kim-tien-y-nghia-phong-thuy-cach-cham-soc-gia-tham-khao.webp', 'Cây Kim Tiền: Ý Nghĩa Phong Thủy, Cách Chăm Sóc, Giá Tham Khảo Chi Tiết', NULL, 'Cây Kim Tiền: Ý Nghĩa Phong Thủy, Cách Chăm Sóc, Giá Tham Khảo Chi Tiết', 0, 0, '2025-12-06 03:22:57', '2025-12-06 03:27:29', NULL),
(6, 'cay-kim-tien-y-nghia-phong-thuy-cach-cham-soc-gia-tham-khao-1.webp', 'Cây Kim Tiền: Ý Nghĩa Phong Thủy, Cách Chăm Sóc, Giá Tham Khảo Chi Tiết', NULL, 'Cây Kim Tiền: Ý Nghĩa Phong Thủy, Cách Chăm Sóc, Giá Tham Khảo Chi Tiết', 0, 1, '2025-12-06 03:22:57', '2025-12-06 03:27:29', NULL),
(7, 'cay-kim-tien-y-nghia-phong-thuy-cach-cham-soc-gia-tham-khao-2.webp', 'Cây Kim Tiền: Ý Nghĩa Phong Thủy, Cách Chăm Sóc, Giá Tham Khảo Chi Tiết', NULL, 'Cây Kim Tiền: Ý Nghĩa Phong Thủy, Cách Chăm Sóc, Giá Tham Khảo Chi Tiết', 0, 2, '2025-12-06 03:22:57', '2025-12-06 03:27:29', NULL),
(8, 'cay-kim-tien-y-nghia-phong-thuy-cach-cham-soc-gia-tham-khao-3.webp', 'Cây Kim Tiền: Ý Nghĩa Phong Thủy, Cách Chăm Sóc, Giá Tham Khảo Chi Tiết', NULL, 'Cây Kim Tiền: Ý Nghĩa Phong Thủy, Cách Chăm Sóc, Giá Tham Khảo Chi Tiết', 1, 3, '2025-12-06 03:22:57', '2025-12-06 03:27:29', NULL),
(16, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-1.webp', 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-1.webp', NULL, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-1.webp', 0, 0, '2025-12-06 04:18:56', '2025-12-06 04:18:56', NULL),
(15, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung.webp', 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung.webp', NULL, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung.webp', 0, 0, '2025-12-06 04:18:56', '2025-12-06 04:18:56', NULL),
(14, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-5.webp', 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', NULL, 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', 0, 5, '2025-12-06 04:18:56', '2025-12-06 04:26:56', NULL),
(13, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-4.webp', 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', NULL, 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', 0, 4, '2025-12-06 04:18:56', '2025-12-06 04:26:56', NULL),
(17, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-2.webp', 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-2.webp', NULL, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-2.webp', 0, 0, '2025-12-06 04:18:56', '2025-12-06 04:18:56', NULL),
(18, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-3.webp', 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-3.webp', NULL, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-3.webp', 0, 0, '2025-12-06 04:18:56', '2025-12-06 04:18:56', NULL),
(19, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung.webp', 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', NULL, 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', 1, 0, '2025-12-06 04:26:56', '2025-12-06 04:26:56', NULL),
(20, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-1.webp', 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', NULL, 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', 0, 1, '2025-12-06 04:26:56', '2025-12-06 04:26:56', NULL),
(21, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-2.webp', 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', NULL, 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', 0, 2, '2025-12-06 04:26:56', '2025-12-06 04:26:56', NULL),
(22, 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung-3.webp', 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', NULL, 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', 0, 3, '2025-12-06 04:26:56', '2025-12-06 04:26:56', NULL),
(23, 'cay-loc-vung-dac-diem-y-nghia-va-bang-gia-cap-nhat.png', NULL, NULL, NULL, 1, 0, '2025-12-07 14:34:47', '2025-12-07 14:34:47', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity_change` int(11) NOT NULL COMMENT 'Số lượng +/- thay đổi tồn kho',
  `stock_before` int(11) DEFAULT NULL COMMENT 'Tồn kho trước khi cập nhật',
  `stock_after` int(11) DEFAULT NULL COMMENT 'Tồn kho sau khi cập nhật',
  `type` varchar(50) NOT NULL COMMENT 'order, order_cancel, import, export, adjust, system',
  `reference_type` varchar(191) DEFAULT NULL COMMENT 'Model liên quan: Order, ImportReceipt...',
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID bản ghi liên quan',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note` varchar(191) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính job queue',
  `queue` varchar(191) NOT NULL COMMENT 'Tên queue',
  `payload` longtext NOT NULL COMMENT 'Payload job',
  `attempts` tinyint(3) UNSIGNED NOT NULL COMMENT 'Số lần thử',
  `reserved_at` int(10) UNSIGNED DEFAULT NULL COMMENT 'Thời điểm reserve',
  `available_at` int(10) UNSIGNED NOT NULL COMMENT 'Thời điểm có thể chạy',
  `created_at` int(10) UNSIGNED NOT NULL COMMENT 'Thời điểm tạo job'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(191) NOT NULL COMMENT 'ID batch',
  `name` varchar(191) NOT NULL COMMENT 'Tên batch',
  `total_jobs` int(11) NOT NULL COMMENT 'Tổng số job',
  `pending_jobs` int(11) NOT NULL COMMENT 'Số job đang chờ',
  `failed_jobs` int(11) NOT NULL COMMENT 'Số job lỗi',
  `failed_job_ids` longtext NOT NULL COMMENT 'Danh sách job lỗi',
  `options` mediumtext DEFAULT NULL COMMENT 'Tùy chọn',
  `cancelled_at` int(11) DEFAULT NULL COMMENT 'Thời điểm hủy',
  `created_at` int(11) NOT NULL COMMENT 'Thời điểm tạo',
  `finished_at` int(11) DEFAULT NULL COMMENT 'Thời điểm hoàn tất'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_12_04_200001_create_accounts_table', 1),
(2, '2025_12_04_200002_create_account_email_verifications_table', 1),
(3, '2025_12_04_200003_create_account_logs_table', 1),
(4, '2025_12_04_200004_create_activity_logs_table', 1),
(5, '2025_12_04_200005_create_addresses_and_address_audits_tables', 1),
(6, '2025_12_04_200006_create_affiliates_table', 1),
(7, '2025_12_04_200007_create_banners_table', 1),
(8, '2025_12_04_200008_create_cache_tables', 1),
(9, '2025_12_04_200009_create_carts_table', 1),
(10, '2025_12_04_200010_create_cart_items_table', 1),
(11, '2025_12_04_200011_create_categories_table', 1),
(12, '2025_12_04_200012_create_images_table', 1),
(13, '2025_12_04_200013_create_products_table', 1),
(14, '2025_12_04_200014_create_product_faqs_table', 1),
(15, '2025_12_04_200015_create_product_how_tos_table', 1),
(16, '2025_12_04_200016_create_product_views_table', 1),
(17, '2025_12_04_200017_create_profiles_table', 1),
(18, '2025_12_04_200018_create_sessions_table', 1),
(19, '2025_12_04_200019_create_settings_table', 1),
(20, '2025_12_04_200020_create_sitemap_configs_table', 1),
(21, '2025_12_04_200021_create_sitemap_excludes_table', 1),
(22, '2025_12_04_200022_create_comments_table', 1),
(23, '2025_12_04_200023_create_contacts_and_contact_replies_tables', 1),
(24, '2025_12_04_200024_create_emails_and_email_templates_tables', 1),
(25, '2025_12_04_200025_create_voucher_tables', 1),
(26, '2025_12_04_200026_create_orders_table', 1),
(27, '2025_12_04_200027_create_order_items_table', 1),
(28, '2025_12_04_200028_create_payments_table', 1),
(29, '2025_12_04_200029_create_newsletters_and_campaigns_tables', 1),
(30, '2025_12_04_200030_create_posts_table', 1),
(31, '2025_12_04_200031_create_post_revisions_table', 1),
(32, '2025_12_04_200032_create_tags_table', 1),
(33, '2025_12_04_200033_create_favorites_table', 1),
(34, '2025_12_04_200034_create_notifications_table', 1),
(35, '2025_12_04_200035_create_flash_sales_table', 1),
(36, '2025_12_04_200036_create_flash_sale_items_table', 1),
(37, '2025_12_04_200037_create_flash_sale_price_logs_table', 1),
(38, '2025_12_04_200038_create_inventory_movements_table', 1),
(39, '2025_12_04_200039_create_jobs_and_job_batches_tables', 1),
(40, '2025_12_04_200040_create_failed_jobs_table', 1),
(41, '2025_12_04_200041_create_password_reset_tokens_table', 1),
(42, '2025_12_04_200042_create_personal_access_tokens_table', 1),
(43, '2025_12_05_092103_create_websites_table', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `newsletters`
--

CREATE TABLE `newsletters` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính đăng ký nhận tin',
  `email` varchar(191) NOT NULL COMMENT 'Email đăng ký',
  `ip` varchar(191) DEFAULT NULL COMMENT 'IP đăng ký',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'Địa chỉ IP đăng ký (chuẩn hóa)',
  `user_agent` text DEFAULT NULL COMMENT 'User agent khi đăng ký',
  `note` text DEFAULT NULL COMMENT 'Ghi chú nội bộ admin',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đã xác thực?',
  `status` varchar(30) NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái: pending, subscribed, unsubscribed',
  `source` varchar(100) DEFAULT NULL COMMENT 'Nguồn đăng ký: homepage_form, popup, checkout, ...',
  `verify_token` varchar(100) DEFAULT NULL COMMENT 'Token xác nhận / hủy đăng ký',
  `verified_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian xác nhận đăng ký',
  `unsubscribed_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian hủy đăng ký',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `newsletters`
--

INSERT INTO `newsletters` (`id`, `email`, `ip`, `ip_address`, `user_agent`, `note`, `is_verified`, `status`, `source`, `verify_token`, `verified_at`, `unsubscribed_at`, `created_at`, `updated_at`) VALUES
(1, 'nguyenminhduc552004@gmail.com', '14.224.155.244', '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, 0, 'pending', 'homepage_form', 'a5889d1cd60b20b57b77d04cb5ab9e11cbebf7ca396fba0185aa6a02e4e7c673', NULL, NULL, '2025-12-05 06:10:22', '2025-12-05 08:05:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `newsletter_campaigns`
--

CREATE TABLE `newsletter_campaigns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `subject` varchar(191) NOT NULL,
  `content` longtext DEFAULT NULL,
  `cta_url` varchar(191) DEFAULT NULL,
  `cta_text` varchar(191) DEFAULT NULL,
  `footer` longtext DEFAULT NULL,
  `filter_status` varchar(191) DEFAULT NULL,
  `filter_source` varchar(191) DEFAULT NULL,
  `filter_date_from` date DEFAULT NULL,
  `filter_date_to` date DEFAULT NULL,
  `total_target` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sent_success` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sent_failed` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'completed',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính thông báo',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(50) NOT NULL COMMENT 'Loại thông báo: order, comment, contact, voucher, flash_sale, etc.',
  `title` varchar(255) NOT NULL COMMENT 'Tiêu đề thông báo',
  `message` text NOT NULL COMMENT 'Nội dung thông báo',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dữ liệu bổ sung (JSON)' CHECK (json_valid(`data`)),
  `link` varchar(191) DEFAULT NULL COMMENT 'URL liên quan đến thông báo',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Icon hiển thị (fa-bell, fa-shopping-cart, etc.)',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal' COMMENT 'Mức độ ưu tiên',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đã đọc chưa',
  `read_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm đọc',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` (`id`, `account_id`, `type`, `title`, `message`, `data`, `link`, `icon`, `priority`, `is_read`, `read_at`, `created_at`, `updated_at`) VALUES
(1, NULL, 'comment_new', 'Bình luận mới cần duyệt', 'Văn Mạnh đã bình luận trên sản phẩm #1', '{\"comment_id\":1,\"type\":\"product\",\"object_id\":1}', 'https://xanhworld.vn/admin/comments/1', 'fa-comment', 'normal', 0, NULL, '2025-12-06 01:27:37', '2025-12-06 01:27:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính đơn hàng',
  `code` varchar(191) NOT NULL COMMENT 'Mã đơn hàng',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL COMMENT 'Session khách vãng lai',
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng tiền hàng',
  `shipping_address_id` bigint(20) UNSIGNED DEFAULT NULL,
  `billing_address_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng tiền hàng',
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Giảm giá',
  `voucher_discount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Giảm giá từ voucher',
  `voucher_code` varchar(191) DEFAULT NULL COMMENT 'Mã voucher áp dụng',
  `final_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng thanh toán cuối cùng',
  `receiver_name` varchar(191) DEFAULT NULL COMMENT 'Tên người nhận',
  `receiver_phone` varchar(191) DEFAULT NULL COMMENT 'Số điện thoại người nhận',
  `receiver_email` varchar(191) DEFAULT NULL COMMENT 'Email người nhận',
  `shipping_address` varchar(191) DEFAULT NULL COMMENT 'Địa chỉ giao hàng',
  `shipping_province_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Mã tỉnh (shipping)',
  `shipping_district_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Mã quận (shipping)',
  `shipping_ward_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Mã phường (shipping)',
  `shipping_fee` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Phí vận chuyển',
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Thuế áp dụng',
  `total` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng thanh toán',
  `payment_method` varchar(191) DEFAULT NULL COMMENT 'Phương thức thanh toán',
  `payment_status` varchar(191) NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái thanh toán',
  `transaction_code` varchar(191) DEFAULT NULL COMMENT 'Mã giao dịch từ cổng thanh toán',
  `shipping_partner` varchar(191) DEFAULT NULL COMMENT 'Đối tác vận chuyển',
  `shipping_tracking_code` text DEFAULT NULL COMMENT 'Mã vận đơn',
  `shipping_raw_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Phản hồi gốc từ đối tác vận chuyển' CHECK (json_valid(`shipping_raw_response`)),
  `delivery_status` varchar(191) NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái giao hàng',
  `is_flash_sale` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đơn có sản phẩm flash sale hay không',
  `customer_note` text DEFAULT NULL COMMENT 'Ghi chú từ khách hàng',
  `admin_note` text DEFAULT NULL COMMENT 'Ghi chú nội bộ',
  `voucher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shipping_method` varchar(191) DEFAULT NULL COMMENT 'Phương thức giao hàng',
  `status` varchar(191) NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái đơn hàng',
  `note` varchar(191) DEFAULT NULL COMMENT 'Ghi chú',
  `ip` varchar(191) DEFAULT NULL COMMENT 'Địa chỉ IP đặt hàng',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính sản phẩm trong đơn',
  `uuid` char(36) DEFAULT NULL COMMENT 'Mã định danh item',
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `is_flash_sale` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đánh dấu sản phẩm flash sale',
  `flash_sale_item_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID flash sale item (nếu có)',
  `product_variant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID biến thể sản phẩm (nếu có)',
  `quantity` int(10) UNSIGNED NOT NULL COMMENT 'Số lượng',
  `price` decimal(15,2) NOT NULL COMMENT 'Giá tại thời điểm mua',
  `total` decimal(15,2) NOT NULL COMMENT 'Thành tiền',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Thuộc tính sản phẩm' CHECK (json_valid(`options`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `method` enum('cod','bank_transfer','qr','momo','zalopay','vnpay','credit_card','payos') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gateway` varchar(191) DEFAULT NULL,
  `transaction_code` varchar(191) DEFAULT NULL,
  `raw_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_response`)),
  `card_brand` varchar(191) DEFAULT NULL,
  `last_four` varchar(191) DEFAULT NULL,
  `receipt_url` varchar(191) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính token cá nhân',
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL COMMENT 'Tên token',
  `token` varchar(64) NOT NULL COMMENT 'Giá trị token',
  `abilities` text DEFAULT NULL COMMENT 'Quyền hạn',
  `last_used_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm cuối cùng dùng token',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm hết hạn token',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `posts`
--

CREATE TABLE `posts` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính bài viết',
  `title` varchar(191) NOT NULL COMMENT 'Tiêu đề bài viết',
  `slug` varchar(191) NOT NULL COMMENT 'Slug thân thiện URL',
  `meta_title` varchar(191) DEFAULT NULL COMMENT 'Meta title SEO',
  `meta_description` text DEFAULT NULL COMMENT 'Meta description SEO',
  `meta_keywords` text DEFAULT NULL COMMENT 'Meta keywords SEO',
  `meta_canonical` varchar(191) DEFAULT NULL COMMENT 'Canonical URL',
  `tag_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Danh sách tag (JSON)' CHECK (json_valid(`tag_ids`)),
  `excerpt` text DEFAULT NULL COMMENT 'Tóm tắt bài viết',
  `content` longtext DEFAULT NULL COMMENT 'Nội dung chi tiết',
  `image_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Danh sách ảnh (JSON)' CHECK (json_valid(`image_ids`)),
  `status` enum('draft','pending','published','archived') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái xuất bản',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đánh dấu bài viết nổi bật',
  `views` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Lượt xem',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm xuất bản',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `post_revisions`
--

CREATE TABLE `post_revisions` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính bản nháp bài viết',
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `edited_by` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL COMMENT 'Tiêu đề phiên bản',
  `content` longtext DEFAULT NULL COMMENT 'Nội dung phiên bản',
  `excerpt` text DEFAULT NULL COMMENT 'Tóm tắt phiên bản',
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dữ liệu meta (JSON)' CHECK (json_valid(`meta`)),
  `is_autosave` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đánh dấu autosave',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính sản phẩm',
  `sku` varchar(191) DEFAULT NULL COMMENT 'Mã SKU (không biến thể)',
  `name` varchar(191) NOT NULL COMMENT 'Tên sản phẩm',
  `slug` varchar(191) NOT NULL COMMENT 'Slug sản phẩm',
  `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết',
  `short_description` text DEFAULT NULL COMMENT 'Mô tả ngắn',
  `price` decimal(10,2) NOT NULL COMMENT 'Giá bán niêm yết',
  `sale_price` decimal(10,2) DEFAULT NULL COMMENT 'Giá khuyến mãi',
  `cost_price` decimal(10,2) DEFAULT NULL COMMENT 'Giá vốn',
  `stock_quantity` int(11) NOT NULL DEFAULT 0 COMMENT 'Tồn kho hiện tại',
  `meta_title` text DEFAULT NULL COMMENT 'Meta title SEO',
  `meta_description` text DEFAULT NULL COMMENT 'Meta description SEO',
  `meta_keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Meta keywords (JSON)' CHECK (json_valid(`meta_keywords`)),
  `meta_canonical` text DEFAULT NULL COMMENT 'Canonical URL',
  `primary_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `category_included_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Danh sách danh mục dùng để gợi ý sản phẩm đi kèm' CHECK (json_valid(`category_included_ids`)),
  `category_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Danh sách danh mục (JSON)' CHECK (json_valid(`category_ids`)),
  `tag_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Danh sách tag (JSON)' CHECK (json_valid(`tag_ids`)),
  `image_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Danh sách ảnh (JSON)' CHECK (json_valid(`image_ids`)),
  `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Sản phẩm nổi bật',
  `locked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm khóa',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái hiển thị',
  `category_ids_backup` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Backup danh mục (nếu cần)' CHECK (json_valid(`category_ids_backup`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `description`, `short_description`, `price`, `sale_price`, `cost_price`, `stock_quantity`, `meta_title`, `meta_description`, `meta_keywords`, `meta_canonical`, `primary_category_id`, `category_included_ids`, `category_ids`, `tag_ids`, `image_ids`, `is_featured`, `locked_by`, `locked_at`, `created_by`, `is_active`, `category_ids_backup`, `created_at`, `updated_at`) VALUES
(1, 'XWCPT120525', 'Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', 'cay-phat-tai-cay-canh-phong-thuy-thu-hut-tai-loc', '<h2 data-start=\"275\" data-end=\"335\"><strong data-start=\"277\" data-end=\"335\">C&Acirc;Y PH&Aacute;T T&Agrave;I &ndash; BIỂU TƯỢNG T&Agrave;I LỘC MAY MẮN CHO GIA Đ&Igrave;NH</strong></h2>\n<h3 data-start=\"337\" data-end=\"358\"><strong data-start=\"340\" data-end=\"358\">Giới thiệu chung</strong></h3>\n<p data-start=\"359\" data-end=\"648\">C&acirc;y Ph&aacute;t T&agrave;i l&agrave; d&ograve;ng c&acirc;y cảnh phong thủy được ưa chuộng h&agrave;ng đầu, mang &yacute; nghĩa thu h&uacute;t vận may, t&agrave;i lộc v&agrave; thịnh vượng cho gia chủ. Nhờ đa dạng chủng loại v&agrave; dễ chăm s&oacute;c, c&acirc;y ph&ugrave; hợp với nhiều kh&ocirc;ng gian như nh&agrave; ở, văn ph&ograve;ng, cửa h&agrave;ng kinh doanh v&agrave; hợp với nhiều mệnh phong thủy kh&aacute;c nhau.</p>\n<hr data-start=\"650\" data-end=\"653\">\n<h3 data-start=\"655\" data-end=\"678\"><strong data-start=\"658\" data-end=\"678\">ĐẶC ĐIỂM NỔI BẬT</strong></h3>\n<p data-start=\"679\" data-end=\"926\">&bull; Dễ trồng &ndash; dễ chăm s&oacute;c &ndash; ph&aacute;t triển tốt trong nh&agrave; lẫn văn ph&ograve;ng<br data-start=\"744\" data-end=\"747\">&bull; Khả năng thanh lọc kh&ocirc;ng kh&iacute; hiệu quả<br data-start=\"786\" data-end=\"789\">&bull; Sống khỏe trong m&ocirc;i trường thiếu s&aacute;ng<br data-start=\"828\" data-end=\"831\">&bull; Ph&ugrave; hợp hầu hết c&aacute;c mệnh trong ngũ h&agrave;nh<br data-start=\"872\" data-end=\"875\">&bull; Tạo điểm nhấn phong thủy &ndash; thẩm mỹ cho kh&ocirc;ng gian</p>\n<hr data-start=\"928\" data-end=\"931\">\n<h2 data-start=\"933\" data-end=\"969\"><strong data-start=\"935\" data-end=\"969\">C&Aacute;C LOẠI C&Acirc;Y PH&Aacute;T T&Agrave;I B&Aacute;N CHẠY</strong></h2>\n<h3 data-start=\"971\" data-end=\"1007\"><strong data-start=\"974\" data-end=\"1007\">1. C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i (Đại Lộc)</strong></h3>\n<p data-start=\"1008\" data-end=\"1256\"><strong data-start=\"1008\" data-end=\"1021\">Đặc điểm:</strong> Th&acirc;n to, khỏe, l&aacute; thu&ocirc;n d&agrave;i nhọn như cọ; hoa v&agrave;ng mọc th&agrave;nh cụm đẹp mắt<br data-start=\"1093\" data-end=\"1096\"><strong data-start=\"1096\" data-end=\"1111\">Phong thủy:</strong> Thuộc h&agrave;nh Mộc &ndash; hợp mệnh Thủy, Mộc, Hỏa<br data-start=\"1152\" data-end=\"1155\"><strong data-start=\"1155\" data-end=\"1170\">Vị tr&iacute; đặt:</strong> S&acirc;n vườn, ph&ograve;ng kh&aacute;ch, văn ph&ograve;ng, khu&ocirc;n vi&ecirc;n nh&agrave;<br data-start=\"1219\" data-end=\"1222\"><strong data-start=\"1222\" data-end=\"1234\">Gi&aacute; b&aacute;n:</strong> 300.000đ &ndash; 3.000.000đ</p>\n<hr data-start=\"1258\" data-end=\"1261\">\n<h3 data-start=\"1263\" data-end=\"1294\"><strong data-start=\"1266\" data-end=\"1294\">2. C&acirc;y Ph&aacute;t T&agrave;i Ph&aacute;t Lộc</strong></h3>\n<p data-start=\"1295\" data-end=\"1516\"><strong data-start=\"1295\" data-end=\"1308\">Đặc điểm:</strong> Th&acirc;n chia đốt như tre, nhỏ gọn, dễ uốn d&aacute;ng phong thủy<br data-start=\"1363\" data-end=\"1366\"><strong data-start=\"1366\" data-end=\"1381\">Phong thủy:</strong> Hợp mệnh Kim &ndash; mang lại sự c&acirc;n bằng, may mắn<br data-start=\"1426\" data-end=\"1429\"><strong data-start=\"1429\" data-end=\"1444\">Vị tr&iacute; đặt:</strong> B&agrave;n l&agrave;m việc, ph&ograve;ng họp, quầy lễ t&acirc;n<br data-start=\"1481\" data-end=\"1484\"><strong data-start=\"1484\" data-end=\"1496\">Gi&aacute; b&aacute;n:</strong> 200.000đ &ndash; 500.000đ</p>\n<hr data-start=\"1518\" data-end=\"1521\">\n<h3 data-start=\"1523\" data-end=\"1548\"><strong data-start=\"1526\" data-end=\"1548\">3. C&acirc;y Ph&aacute;t T&agrave;i Đỏ</strong></h3>\n<p data-start=\"1549\" data-end=\"1781\"><strong data-start=\"1549\" data-end=\"1562\">Đặc điểm:</strong> L&aacute; xanh khi non, chuyển đỏ nổi bật khi trưởng th&agrave;nh<br data-start=\"1614\" data-end=\"1617\"><strong data-start=\"1617\" data-end=\"1632\">Phong thủy:</strong> Hợp mệnh Hỏa &ndash; thu h&uacute;t năng lượng t&iacute;ch cực<br data-start=\"1675\" data-end=\"1678\"><strong data-start=\"1678\" data-end=\"1693\">Vị tr&iacute; đặt:</strong> Ph&ograve;ng kh&aacute;ch, ph&ograve;ng thờ, c&aacute;c vị tr&iacute; trang tr&iacute; nổi bật<br data-start=\"1746\" data-end=\"1749\"><strong data-start=\"1749\" data-end=\"1761\">Gi&aacute; b&aacute;n:</strong> 100.000đ &ndash; 150.000đ</p>\n<hr data-start=\"1783\" data-end=\"1786\">\n<h3 data-start=\"1788\" data-end=\"1818\"><strong data-start=\"1791\" data-end=\"1818\">4. C&acirc;y Ph&aacute;t T&agrave;i B&uacute;p Sen</strong></h3>\n<p data-start=\"1819\" data-end=\"2019\"><strong data-start=\"1819\" data-end=\"1832\">Đặc điểm:</strong> L&aacute; xanh đậm, mọc xếp lớp như b&ocirc;ng sen<br data-start=\"1870\" data-end=\"1873\"><strong data-start=\"1873\" data-end=\"1888\">Phong thủy:</strong> Hợp mệnh Kim &ndash; Mộc &ndash; Thủy (khi trồng thủy sinh)<br data-start=\"1936\" data-end=\"1939\"><strong data-start=\"1939\" data-end=\"1954\">Vị tr&iacute; đặt:</strong> B&agrave;n thờ Thần T&agrave;i, b&agrave;n l&agrave;m việc<br data-start=\"1985\" data-end=\"1988\"><strong data-start=\"1988\" data-end=\"2000\">Gi&aacute; b&aacute;n:</strong> 70.000đ &ndash; 200.000đ</p>\n<hr data-start=\"2021\" data-end=\"2024\">\n<h3 data-start=\"2026\" data-end=\"2053\"><strong data-start=\"2029\" data-end=\"2053\">5. C&acirc;y Thiết Mộc Lan</strong></h3>\n<p data-start=\"2054\" data-end=\"2247\"><strong data-start=\"2054\" data-end=\"2067\">Đặc điểm:</strong> T&aacute;n l&aacute; rậm, th&acirc;n cột cao, c&oacute; thể ra hoa thơm nhẹ<br data-start=\"2116\" data-end=\"2119\"><strong data-start=\"2119\" data-end=\"2134\">Phong thủy:</strong> Hợp mệnh Mộc v&agrave; Hỏa<br data-start=\"2154\" data-end=\"2157\"><strong data-start=\"2157\" data-end=\"2172\">Vị tr&iacute; đặt:</strong> Nh&agrave; ở, văn ph&ograve;ng, cửa h&agrave;ng kinh doanh<br data-start=\"2210\" data-end=\"2213\"><strong data-start=\"2213\" data-end=\"2225\">Gi&aacute; b&aacute;n:</strong> 500.000đ &ndash; 2.000.000đ</p>\n<hr data-start=\"2249\" data-end=\"2252\">\n<h3 data-start=\"2254\" data-end=\"2276\"><strong data-start=\"2257\" data-end=\"2276\">6. C&acirc;y Kim Tiền</strong></h3>\n<p data-start=\"2277\" data-end=\"2495\"><strong data-start=\"2277\" data-end=\"2290\">Đặc điểm:</strong> L&aacute; đối xứng, b&oacute;ng đẹp, c&agrave;nh vươn cao gi&agrave;u sinh kh&iacute;<br data-start=\"2341\" data-end=\"2344\"><strong data-start=\"2344\" data-end=\"2359\">Phong thủy:</strong> Hợp mệnh Mộc &ndash; Hỏa, tượng trưng cho tiền t&agrave;i v&agrave; thịnh vượng<br data-start=\"2419\" data-end=\"2422\"><strong data-start=\"2422\" data-end=\"2437\">Vị tr&iacute; đặt:</strong> Hầu hết mọi kh&ocirc;ng gian<br data-start=\"2460\" data-end=\"2463\"><strong data-start=\"2463\" data-end=\"2475\">Gi&aacute; b&aacute;n:</strong> 150.000đ &ndash; 500.000đ</p>\n<hr data-start=\"2497\" data-end=\"2500\">\n<h2 data-start=\"2502\" data-end=\"2539\"><strong data-start=\"2504\" data-end=\"2539\">HƯỚNG DẪN CHĂM S&Oacute;C C&Acirc;Y PH&Aacute;T T&Agrave;I</strong></h2>\n<h3 data-start=\"2541\" data-end=\"2563\"><strong data-start=\"2545\" data-end=\"2563\">Vị tr&iacute; đặt c&acirc;y</strong></h3>\n<p data-start=\"2564\" data-end=\"2613\">N&ecirc;n đặt hướng Đ&ocirc;ng hoặc Đ&ocirc;ng Nam theo phong thủy.</p>\n<h3 data-start=\"2615\" data-end=\"2631\"><strong data-start=\"2619\" data-end=\"2631\">&Aacute;nh s&aacute;ng</strong></h3>\n<p data-start=\"2632\" data-end=\"2725\">&bull; Sống tốt trong m&ocirc;i trường &iacute;t s&aacute;ng<br data-start=\"2667\" data-end=\"2670\">&bull; Thỉnh thoảng phơi nắng nhẹ để c&acirc;y quang hợp tốt hơn</p>\n<h3 data-start=\"2727\" data-end=\"2744\"><strong data-start=\"2731\" data-end=\"2744\">Tưới nước</strong></h3>\n<ul data-start=\"2745\" data-end=\"2878\">\n<li data-start=\"2745\" data-end=\"2790\">\n<p data-start=\"2747\" data-end=\"2790\">C&acirc;y trong nh&agrave;: phun sương, giữ đất ẩm nhẹ</p>\n</li>\n<li data-start=\"2791\" data-end=\"2828\">\n<p data-start=\"2793\" data-end=\"2828\">C&acirc;y ngo&agrave;i trời: tưới đều mỗi ng&agrave;y</p>\n</li>\n<li data-start=\"2829\" data-end=\"2878\">\n<p data-start=\"2831\" data-end=\"2878\">Thủy sinh: thay nước 3&ndash;4 ng&agrave;y/lần bằng nước lọc</p>\n</li>\n</ul>\n<h3 data-start=\"2880\" data-end=\"2897\"><strong data-start=\"2884\" data-end=\"2897\">Đất trồng</strong></h3>\n<p data-start=\"2898\" data-end=\"2984\">Đất tơi xốp, gi&agrave;u dinh dưỡng, tho&aacute;ng nước, pH 6&ndash;7. C&oacute; thể trộn m&ugrave;n, trấu, ph&acirc;n hữu cơ.</p>\n<h3 data-start=\"2986\" data-end=\"3002\"><strong data-start=\"2990\" data-end=\"3002\">B&oacute;n ph&acirc;n</strong></h3>\n<p data-start=\"3003\" data-end=\"3056\">B&oacute;n hữu cơ hoặc NPK định kỳ v&agrave;o giai đoạn ph&aacute;t triển.</p>\n<h3 data-start=\"3058\" data-end=\"3084\"><strong data-start=\"3062\" data-end=\"3084\">Cắt tỉa &ndash; tạo d&aacute;ng</strong></h3>\n<p data-start=\"3085\" data-end=\"3136\">Loại bỏ l&aacute; h&eacute;o, chỉnh d&aacute;ng để c&acirc;y lu&ocirc;n đẹp v&agrave; khỏe.</p>\n<h3 data-start=\"3138\" data-end=\"3155\"><strong data-start=\"3142\" data-end=\"3155\">Thay chậu</strong></h3>\n<p data-start=\"3156\" data-end=\"3196\">Khoảng 1 năm/lần để c&acirc;y ph&aacute;t triển mạnh.</p>\n<hr data-start=\"3198\" data-end=\"3201\">\n<h2 data-start=\"3203\" data-end=\"3227\"><strong data-start=\"3205\" data-end=\"3227\">&Yacute; NGHĨA PHONG THỦY</strong></h2>\n<p data-start=\"3228\" data-end=\"3451\">✓ Thu h&uacute;t t&agrave;i lộc &ndash; may mắn &ndash; thịnh vượng<br data-start=\"3269\" data-end=\"3272\">✓ Mang lại b&igrave;nh an cho gia đ&igrave;nh<br data-start=\"3303\" data-end=\"3306\">✓ Giảm căng thẳng, tăng năng lượng t&iacute;ch cực<br data-start=\"3349\" data-end=\"3352\">✓ Hỗ trợ c&acirc;n bằng phong thủy trong kh&ocirc;ng gian<br data-start=\"3397\" data-end=\"3400\">✓ C&acirc;y ra hoa được xem l&agrave; dấu hiệu vận may sắp đến</p>\n<hr data-start=\"3453\" data-end=\"3456\">\n<h2 data-start=\"3458\" data-end=\"3480\"><strong data-start=\"3460\" data-end=\"3480\">CAM KẾT SẢN PHẨM</strong></h2>\n<p data-start=\"3481\" data-end=\"3638\">&bull; C&acirc;y khỏe mạnh &ndash; đ&uacute;ng loại &ndash; đ&uacute;ng k&iacute;ch thước<br data-start=\"3526\" data-end=\"3529\">&bull; Giao h&agrave;ng to&agrave;n quốc &ndash; đ&oacute;ng g&oacute;i an to&agrave;n<br data-start=\"3569\" data-end=\"3572\">&bull; Tư vấn chọn c&acirc;y hợp mệnh miễn ph&iacute;<br data-start=\"3607\" data-end=\"3610\">&bull; Hỗ trợ chăm s&oacute;c trọn đời</p>\n<hr data-start=\"3640\" data-end=\"3643\">\n<h2 data-start=\"3645\" data-end=\"3664\"><strong data-start=\"3647\" data-end=\"3664\">LƯU &Yacute; AN TO&Agrave;N</strong></h2>\n<p data-start=\"3665\" data-end=\"3787\">Hầu hết c&acirc;y Ph&aacute;t T&agrave;i đều an to&agrave;n. Một số loại c&oacute; thể g&acirc;y k&iacute;ch ứng nhẹ khi nhựa c&acirc;y d&iacute;nh tay &mdash; n&ecirc;n rửa tay sau khi cắt tỉa.</p>\n<hr data-start=\"3789\" data-end=\"3792\">\n<h2 data-start=\"3794\" data-end=\"3817\"><strong data-start=\"3796\" data-end=\"3817\">BẢNG GI&Aacute; CHI TIẾT</strong></h2>\n<div class=\"TyagGW_tableContainer\">\n<div class=\"group TyagGW_tableWrapper flex w-fit flex-col-reverse\" tabindex=\"-1\">\n<table class=\"w-fit min-w-(--thread-content-width)\" data-start=\"3819\" data-end=\"4149\">\n<thead data-start=\"3819\" data-end=\"3850\">\n<tr data-start=\"3819\" data-end=\"3850\">\n<th data-start=\"3819\" data-end=\"3830\" data-col-size=\"sm\">Loại c&acirc;y</th>\n<th data-start=\"3830\" data-end=\"3839\" data-col-size=\"sm\">Gi&aacute; từ</th>\n<th data-start=\"3839\" data-end=\"3850\" data-col-size=\"sm\">Gi&aacute; đến</th>\n</tr>\n</thead>\n<tbody data-start=\"3884\" data-end=\"4149\">\n<tr data-start=\"3884\" data-end=\"3928\">\n<td data-start=\"3884\" data-end=\"3903\" data-col-size=\"sm\">C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i</td>\n<td data-col-size=\"sm\" data-start=\"3903\" data-end=\"3914\">300.000đ</td>\n<td data-col-size=\"sm\" data-start=\"3914\" data-end=\"3928\">3.000.000đ</td>\n</tr>\n<tr data-start=\"3929\" data-end=\"3976\">\n<td data-start=\"3929\" data-end=\"3953\" data-col-size=\"sm\">C&acirc;y Ph&aacute;t T&agrave;i Ph&aacute;t Lộc</td>\n<td data-col-size=\"sm\" data-start=\"3953\" data-end=\"3964\">200.000đ</td>\n<td data-col-size=\"sm\" data-start=\"3964\" data-end=\"3976\">500.000đ</td>\n</tr>\n<tr data-start=\"3977\" data-end=\"4018\">\n<td data-start=\"3977\" data-end=\"3995\" data-col-size=\"sm\">C&acirc;y Ph&aacute;t T&agrave;i Đỏ</td>\n<td data-col-size=\"sm\" data-start=\"3995\" data-end=\"4006\">100.000đ</td>\n<td data-col-size=\"sm\" data-start=\"4006\" data-end=\"4018\">150.000đ</td>\n</tr>\n<tr data-start=\"4019\" data-end=\"4064\">\n<td data-start=\"4019\" data-end=\"4042\" data-col-size=\"sm\">C&acirc;y Ph&aacute;t T&agrave;i B&uacute;p Sen</td>\n<td data-col-size=\"sm\" data-start=\"4042\" data-end=\"4052\">70.000đ</td>\n<td data-col-size=\"sm\" data-start=\"4052\" data-end=\"4064\">200.000đ</td>\n</tr>\n<tr data-start=\"4065\" data-end=\"4110\">\n<td data-start=\"4065\" data-end=\"4085\" data-col-size=\"sm\">C&acirc;y Thiết Mộc Lan</td>\n<td data-col-size=\"sm\" data-start=\"4085\" data-end=\"4096\">500.000đ</td>\n<td data-col-size=\"sm\" data-start=\"4096\" data-end=\"4110\">2.000.000đ</td>\n</tr>\n<tr data-start=\"4111\" data-end=\"4149\">\n<td data-start=\"4111\" data-end=\"4126\" data-col-size=\"sm\">C&acirc;y Kim Tiền</td>\n<td data-col-size=\"sm\" data-start=\"4126\" data-end=\"4137\">150.000đ</td>\n<td data-col-size=\"sm\" data-start=\"4137\" data-end=\"4149\">500.000đ</td>\n</tr>\n</tbody>\n</table>\n</div>\n</div>\n<p data-start=\"4151\" data-end=\"4202\"><em data-start=\"4151\" data-end=\"4202\">(Gi&aacute; c&oacute; thể thay đổi t&ugrave;y k&iacute;ch thước v&agrave; tuổi c&acirc;y.)</em></p>\n<hr data-start=\"4204\" data-end=\"4207\">\n<h2 data-start=\"4209\" data-end=\"4240\"><strong data-start=\"4211\" data-end=\"4240\">TƯ VẤN CHỌN C&Acirc;Y THEO MỆNH</strong></h2>\n<ul data-start=\"4242\" data-end=\"4419\">\n<li data-start=\"4242\" data-end=\"4277\">\n<p data-start=\"4244\" data-end=\"4277\"><strong data-start=\"4244\" data-end=\"4258\">Mệnh Thủy:</strong> C&acirc;y Ph&aacute;t T&agrave;i N&uacute;i</p>\n</li>\n<li data-start=\"4278\" data-end=\"4328\">\n<p data-start=\"4280\" data-end=\"4328\"><strong data-start=\"4280\" data-end=\"4293\">Mệnh Mộc:</strong> B&uacute;p Sen, Kim Tiền, Thiết Mộc Lan</p>\n</li>\n<li data-start=\"4329\" data-end=\"4383\">\n<p data-start=\"4331\" data-end=\"4383\"><strong data-start=\"4331\" data-end=\"4344\">Mệnh Hỏa:</strong> Ph&aacute;t T&agrave;i Đỏ, Kim Tiền, Thiết Mộc Lan</p>\n</li>\n<li data-start=\"4384\" data-end=\"4419\">\n<p data-start=\"4386\" data-end=\"4419\"><strong data-start=\"4386\" data-end=\"4399\">Mệnh Kim:</strong> Ph&aacute;t Lộc, B&uacute;p Sen</p>\n</li>\n</ul>', NULL, 970000.00, 899000.00, 500000.00, 100, 'Cây Phát Tài – Cây Cảnh Phong Thủy Thu Hút Tài Lộc', 'Cây Phát Tài mang ý nghĩa thu hút tài lộc, may mắn và thịnh vượng. Cây dễ chăm sóc, phù hợp trang trí nhà – văn phòng. Giá tốt, giao hàng toàn quốc.', '[\"c\\u00e2y ph\\u00e1t t\\u00e0i\",\"c\\u00e2y phong th\\u1ee7y ph\\u00e1t t\\u00e0i\",\"c\\u00e2y ph\\u00e1t t\\u00e0i n\\u00fai\",\"c\\u00e2y ph\\u00e1t t\\u00e0i ph\\u00e1t l\\u1ed9c\",\"c\\u00e2y ph\\u00e1t t\\u00e0i \\u0111\\u1ecf\",\"c\\u00e2y ph\\u00e1t t\\u00e0i b\\u00fap sen\",\"thi\\u1ebft m\\u1ed9c lan\",\"c\\u00e2y kim ti\\u1ec1n\",\"c\\u00e2y c\\u1ea3nh phong th\\u1ee7y\",\"mua c\\u00e2y ph\\u00e1t t\\u00e0i\"]', 'https://xanhworld.vn/san-pham/cay-phat-tai-cay-canh-phong-thuy-thu-hut-tai-loc', 2, NULL, '[1,6,16,14,15,13,12,9,8,17,2,7]', '[11,12,13,14,15,16,17,18,19,20]', '[1,2,3,4]', 1, 1, '2025-12-05 08:19:19', 1, 1, NULL, '2025-12-05 07:43:56', '2025-12-05 10:02:03'),
(2, 'XWCKT120625', 'Cây Kim Tiền – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Giá Tham Khảo', 'cay-kim-tien-y-nghia-phong-thuy-cach-cham-soc-gia-tham-khao', '<h2 style=\"color:#3bb77e; font-weight:bold; margin-top:20px;\">\n  CÂY KIM TIỀN – BIỂU TƯỢNG PHONG THỦY MAY MẮN, TÀI LỘC VÀ THỊNH VƯỢNG\n</h2>\n\n<div style=\"line-height:1.7; font-size:16px;\">\n\n  <p>\n    <strong style=\"color:#3bb77e;\">Cây Kim Tiền</strong> (hay còn gọi là \n    <em>Kim Phát Tài, Kim Tiền Phát Lộc</em>) là một trong những loại cây cảnh phong thủy \n    được ưa chuộng nhờ vẻ đẹp xanh mát, dễ chăm sóc và mang ý nghĩa thu hút tài lộc. \n    Với khả năng sinh trưởng mạnh mẽ ngay cả trong điều kiện thiếu sáng, cây trở thành \n    lựa chọn lý tưởng để trang trí văn phòng, nhà ở, cửa hàng kinh doanh hay không gian làm việc.\n  </p>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Đặc điểm nổi bật của cây</h3>\n  <ul>\n    <li>Nguồn gốc: Châu Phi</li>\n    <li>Lá xanh lục mọc đối xứng, hướng thẳng lên cao tượng trưng cho sự phát triển</li>\n    <li>Thân to khỏe, ít sâu bệnh, dễ sống trong điều kiện phòng lạnh</li>\n    <li>Khả năng lọc không khí, cải thiện không gian sống</li>\n    <li>Đặc biệt phù hợp môi trường trong nhà, văn phòng hoặc nơi ánh sáng yếu</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Ý nghĩa phong thủy</h3>\n  <p>\n    Cây Kim Tiền mang năng lượng tích cực, tượng trưng cho sự giàu sang – phú quý – thịnh vượng.\n  </p>\n  <ul>\n    <li>Thu hút tài lộc và may mắn</li>\n    <li>Hỗ trợ thăng tiến công danh, phát triển sự nghiệp</li>\n    <li>Hội tụ đủ ngũ hành: Mộc – Thổ – Thủy – Hỏa – Kim</li>\n    <li>Cây nở hoa báo hiệu vận may đang đến</li>\n  </ul>\n\n  <p>\n    <strong>Ngũ hành phù hợp:</strong> Thuộc hành Mộc, hợp mệnh Mộc và Hỏa, phù hợp với mọi đối tuổi, đặc biệt tốt cho người kinh doanh hoặc khởi nghiệp.\n  </p>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Bảng thông tin kích thước – công dụng – mức giá tham khảo</h3>\n  <table border=\"1\" cellpadding=\"10\" cellspacing=\"0\" \n         style=\"border-collapse:collapse; width:100%; text-align:left; border-color:#3bb77e;\">\n    <tr style=\"background:#3bb77e; color:white;\">\n      <th>Kích thước cây</th>\n      <th>Công dụng</th>\n      <th>Giá tham khảo</th>\n    </tr>\n    <tr>\n      <td>15–30cm</td>\n      <td>Cây để bàn văn phòng</td>\n      <td>70.000đ – 100.000đ</td>\n    </tr>\n    <tr>\n      <td>30–50cm</td>\n      <td>Trang trí phòng khách, bàn làm việc</td>\n      <td>200.000đ – 350.000đ</td>\n    </tr>\n    <tr>\n      <td>Trên 50cm</td>\n      <td>Trang trí sảnh – văn phòng lớn</td>\n      <td>350.000đ – 500.000đ</td>\n    </tr>\n  </table>\n\n  <p style=\"font-style:italic; margin-top:10px;\">\n    Giá trên đã bao gồm cây và chậu. Liên hệ để được tư vấn chi tiết từng kích thước.\n  </p>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Vị trí đặt cây phù hợp</h3>\n  <ul>\n    <li>Bàn làm việc, phòng giám đốc</li>\n    <li>Quầy lễ tân, sảnh công ty</li>\n    <li>Phòng khách, phòng đọc sách</li>\n    <li>Cửa hàng kinh doanh – quầy thu ngân</li>\n    <li>Góc cửa sổ hoặc ban công có ánh sáng nhẹ</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Hướng dẫn chăm sóc đúng cách</h3>\n\n  <p><strong style=\"color:#3bb77e;\">Chậu trồng:</strong></p>\n  <ul>\n    <li>Ưu tiên chậu lớn vì rễ phát triển mạnh</li>\n    <li>Chậu có nhiều lỗ thoát nước giúp hạn chế úng rễ</li>\n  </ul>\n\n  <p><strong style=\"color:#3bb77e;\">Đất trồng:</strong></p>\n  <ul>\n    <li>Đất tơi xốp, thoát nước tốt</li>\n    <li>Có thể trộn mùn trấu, xỉ than tổ ong hoặc đất vi sinh</li>\n  </ul>\n\n  <p><strong style=\"color:#3bb77e;\">Tưới nước:</strong></p>\n  <ul>\n    <li>Phun sương 1–2 lần/tuần khi đất đủ ẩm</li>\n    <li>Không tưới quá nhiều để tránh thối rễ</li>\n    <li>Tưới đẫm 1 lần/tháng và cho cây phơi nắng nhẹ</li>\n  </ul>\n\n  <p><strong style=\"color:#3bb77e;\">Ánh sáng:</strong></p>\n  <ul>\n    <li>Ưa sáng nhưng vẫn sống tốt nếu thiếu sáng</li>\n    <li>Tránh nắng gắt trực tiếp</li>\n    <li>Đặt gần cửa sổ, cách kính một khoảng vừa phải</li>\n  </ul>\n\n  <p><strong style=\"color:#3bb77e;\">Bón phân:</strong></p>\n  <ul>\n    <li>Bón phân định kỳ 2–3 tháng/lần</li>\n    <li>Rải quanh gốc, cách gốc 10–15cm</li>\n    <li>Dùng các loại phân chuyên cho cây cảnh</li>\n  </ul>\n\n  <p><strong style=\"color:#3bb77e;\">Vệ sinh cây:</strong></p>\n  <ul>\n    <li>Lau lá bằng khăn ẩm mỗi tuần</li>\n    <li>Cắt tỉa lá vàng ngay khi phát hiện</li>\n    <li>Giúp cây xanh mướt và quang hợp tốt hơn</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Nhân giống cây Kim Tiền (dành cho người thích tự trồng)</h3>\n\n  <p><strong style=\"color:#3bb77e;\">Nhân giống bằng cành – cây lớn nhanh:</strong></p>\n  <ul>\n    <li>Chọn thân con khỏe, lá xanh đậm</li>\n    <li>Cắt sát gốc, để khô 3 giờ</li>\n    <li>Cắm xuống đất và tưới kích rễ</li>\n    <li>Phun sương mỗi 2–3 ngày</li>\n    <li>Sau 1 tháng rễ phát triển mạnh</li>\n  </ul>\n\n  <p><strong style=\"color:#3bb77e;\">Nhân giống bằng lá – được nhiều cây:</strong></p>\n  <ul>\n    <li>Chọn lá to khỏe ở gốc</li>\n    <li>Ngâm nước kích rễ trong 2 giờ</li>\n    <li>Cắm sâu 1cm vào đất</li>\n    <li>Tưới đẫm lần đầu, sau đó phun sương định kỳ</li>\n    <li>Sau 1 tháng hình thành cây non</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Cam kết chất lượng</h3>\n  <ul>\n    <li>Cây khỏe – xanh – đẹp</li>\n    <li>Đặt và giao tận nơi</li>\n    <li>Đóng gói cẩn thận bảo vệ cây</li>\n    <li>Tư vấn phong thủy phù hợp</li>\n    <li>Hướng dẫn chăm sóc miễn phí</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Lưu ý khi chọn mua cây</h3>\n  <ul>\n    <li>Lá xanh đậm, tươi khỏe</li>\n    <li>Không có dấu hiệu sâu bệnh</li>\n    <li>Rễ chắc, không bị thối</li>\n    <li>Kích thước phù hợp vị trí trưng bày</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Tại sao nên chọn cây Kim Tiền?</h3>\n  <ol>\n    <li>Dễ chăm nhất trong các loại cây phong thủy</li>\n    <li>Thích nghi tốt với môi trường máy lạnh</li>\n    <li>Ý nghĩa thu hút tài lộc – may mắn – thịnh vượng</li>\n    <li>Tạo không gian sống xanh mát và tinh tế</li>\n    <li>Chi phí hợp lý, phù hợp nhiều nhu cầu</li>\n  </ol>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Chính sách hỗ trợ</h3>\n  <ul>\n    <li>Giao hàng toàn quốc</li>\n    <li>Thanh toán linh hoạt: COD hoặc chuyển khoản</li>\n    <li>Hỗ trợ đổi cây trong 3 ngày nếu gặp vấn đề</li>\n    <li>Tư vấn chăm sóc trọn đời</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:25px;\">Liên hệ</h3>\n  <p>\n    Vui lòng liên hệ qua <strong>Điện thoại / Zalo / Facebook</strong> để được tư vấn và hỗ trợ chi tiết.<br>\n    <strong style=\"color:#3bb77e;\">Giao hàng nhanh – Cây đẹp – Giá hợp lý – Tận tâm hỗ trợ</strong>\n  </p>\n\n</div>', NULL, 350000.00, 299000.00, 150000.00, 100, 'Cây Kim Tiền: Ý Nghĩa Phong Thủy, Cách Chăm Sóc, Giá Tham Khảo Chi Tiết', 'Cây Kim Tiền (Kim Phát Tài) – biểu tượng phong thủy may mắn, tài lộc và thịnh vượng. Tìm hiểu đặc điểm, ý nghĩa, cách chăm sóc, vị trí đặt cây và bảng giá tham khảo chi tiết.', '[\"c\\u00e2y kim ti\\u1ec1n\",\"kim ph\\u00e1t t\\u00e0i\",\"c\\u00e2y kim ti\\u1ec1n phong th\\u1ee7y\",\"\\u00fd ngh\\u0129a c\\u00e2y kim ti\\u1ec1n\",\"ch\\u0103m s\\u00f3c c\\u00e2y kim ti\\u1ec1n\",\"gi\\u00e1 c\\u00e2y kim ti\\u1ec1n\",\"c\\u00e2y kim ph\\u00e1t t\\u00e0i\",\"c\\u00e2y phong th\\u1ee7y v\\u0103n ph\\u00f2ng\",\"c\\u00e2y xanh trong nh\\u00e0\"]', 'https://xanhworld.vn/san-pham/cay-kim-tien-y-nghia-phong-thuy-cach-cham-soc-gia-tham-khao', 2, NULL, '[1,6,16,14,15,13,12,9,8,17,2,7]', '[18,21,22,19,23,24,25]', '[5,6,7,8]', 1, NULL, NULL, 1, 1, NULL, '2025-12-06 03:27:29', '2025-12-06 03:29:17'),
(3, 'XWCLH061225', 'Cây Lưỡi Hổ – Ý Nghĩa Phong Thủy, Cách Chăm Sóc và Công Dụng Thanh Lọc Không Khí', 'cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung', '<h2 style=\"color:#3bb77e; font-weight:700; margin-top:20px;\">\n  CÂY LƯỠI HỔ – CÂY PHONG THỦY TRỪ TÀ, THANH LỌC KHÔNG KHÍ TỐT NHẤT HIỆN NAY\n</h2>\n\n<div style=\"line-height:1.7; font-size:16px;\">\n\n  <p>\n    <strong style=\"color:#3bb77e;\">Cây Lưỡi Hổ</strong> (còn gọi là Vĩ Hổ, Lưỡi Cọp – \n    <em>Sansevieria trifasciata</em>) là một trong những loại cây cảnh phong thủy được yêu thích \n    nhất hiện nay nhờ vẻ đẹp mạnh mẽ, khả năng thanh lọc không khí vượt trội và đặc biệt là ý nghĩa \n    trừ tà, bảo vệ gia chủ. Theo nghiên cứu của NASA, Lưỡi Hổ có thể loại bỏ hơn \n    <strong>107 loại độc tố khác nhau</strong> trong không khí, giúp không gian sống trong lành và an toàn hơn.\n  </p>\n\n  <p>\n    Cây phù hợp trang trí nhà ở, văn phòng, spa, bệnh viện, tòa nhà cao tầng… Nhờ khả năng \n    chịu hạn và chịu bóng tốt, cây gần như phù hợp với mọi không gian và không cần nhiều công chăm sóc.\n  </p>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">ĐẶC ĐIỂM CỦA CÂY LƯỠI HỔ</h3>\n\n  <ul>\n    <li>Thuộc họ Măng tây, chiều cao trung bình 50–60cm</li>\n    <li>Lá dẹt mọng nước, có hai màu xanh – vàng kéo dài từ gốc lên ngọn</li>\n    <li>Hoa mọc thành cụm từ gốc, sau hoa sẽ có quả tròn nhỏ</li>\n    <li>Nguồn gốc: vùng nhiệt đới châu Phi</li>\n    <li>Trên 70 loài khác nhau, phổ biến nhất: Lưỡi Hổ Thái, Lưỡi Hổ Cọp, Lưỡi Hổ Xanh</li>\n    <li>Là một trong số ít cây nhả oxy vào ban đêm – cực kỳ tốt cho phòng ngủ</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">ƯU ĐIỂM NỔI BẬT</h3>\n\n  <ul>\n    <li>Thanh lọc không khí, loại bỏ 107 loại khí độc như formaldehyde, nitrogen oxide</li>\n    <li>Hấp thụ CO<sub>2</sub>, nhả O<sub>2</sub> vào buổi tối – giúp ngủ sâu hơn</li>\n    <li>Sống tốt trong bóng râm, điều hòa, thiếu ánh sáng</li>\n    <li>Không kén đất, chịu hạn cực tốt</li>\n    <li>Dễ nhân giống, ít sâu bệnh</li>\n    <li>Hình dáng hiện đại, phù hợp nhiều phong cách nội thất</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">Ý NGHĨA PHONG THỦY CỦA CÂY LƯỠI HỔ</h3>\n\n  <p>\n    Trong phong thủy, Lưỡi Hổ được xem là cây mang năng lượng mạnh, có khả năng \n    bảo vệ, trừ tà và hóa giải những nguồn khí xấu.\n  </p>\n\n  <ul>\n    <li><strong>Trừ tà – xua đuổi năng lượng xấu</strong>, tránh thị phi</li>\n    <li>Thu hút may mắn, tài lộc, sự thuận lợi</li>\n    <li>Tăng uy quyền, tạo sự tự tin cho gia chủ</li>\n    <li>Lá mọc thẳng tượng trưng cho ý chí tiến lên</li>\n    <li>Cây ra hoa là dấu hiệu báo vận may lớn sắp đến</li>\n  </ul>\n\n  <p>\n    <strong>Màu sắc:</strong> Xanh (Thổ) kết hợp viền vàng (Kim)<br>\n    <strong>Hợp mệnh:</strong> Thổ, Kim<br>\n    <strong>Hợp tuổi:</strong> Tuổi Ngọ (1942, 1954, 1966, 1978, 1990, 2002, 2014)<br>\n    <strong>Hướng đặt tốt nhất:</strong> Hướng Nam\n  </p>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">CÔNG DỤNG SỨC KHỎE</h3>\n\n  <p>\n    Không chỉ là cây phong thủy, Lưỡi Hổ còn có nhiều công dụng y học tự nhiên:\n  </p>\n\n  <ul>\n    <li>Giảm hen suyễn: dùng gel lá pha nước nóng giúp dễ thở</li>\n    <li>Hỗ trợ tiêu hóa: chứa aloin và barbaloin giúp giảm trào ngược, đầy hơi</li>\n    <li>Giảm stress khi làm việc, tạo không gian thư giãn</li>\n    <li>Nhả oxy ban đêm, cải thiện chất lượng giấc ngủ</li>\n    <li>Kháng viêm, sát khuẩn nhẹ – có thể dùng như nha đam</li>\n    <li>Giảm dị ứng da và các vấn đề hô hấp do không khí kém chất lượng</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">VỊ TRÍ ĐẶT CÂY LƯỠI HỔ PHÙ HỢP</h3>\n\n  <ul>\n    <li><strong>Phòng khách:</strong> hai bên cửa, cạnh sofa, bên kệ tivi</li>\n    <li><strong>Phòng ngủ:</strong> thanh lọc không khí, cung cấp oxy ban đêm</li>\n    <li><strong>Phòng tắm:</strong> hút ẩm, chống mùi</li>\n    <li><strong>Văn phòng:</strong> giảm căng thẳng, tăng sự tập trung</li>\n    <li><strong>Bệnh viện, tòa nhà:</strong> khử khuẩn và cải thiện chất lượng không khí</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">BẢNG GIÁ THAM KHẢO</h3>\n\n  <table border=\"1\" cellpadding=\"10\" cellspacing=\"0\" \n         style=\"border-collapse:collapse; width:100%; border-color:#3bb77e;\">\n    <tr style=\"background:#3bb77e; color:white;\">\n      <th>Loại cây</th>\n      <th>Chiều cao</th>\n      <th>Công dụng</th>\n      <th>Giá tham khảo</th>\n    </tr>\n    <tr>\n      <td>Lưỡi Hổ Thái</td>\n      <td>30–40cm</td>\n      <td>Để bàn, trang trí phòng nhỏ</td>\n      <td>50.000đ – 150.000đ</td>\n    </tr>\n    <tr>\n      <td>Lưỡi Hổ Cọp</td>\n      <td>40–60cm</td>\n      <td>Phòng khách, văn phòng</td>\n      <td>150.000đ – 300.000đ</td>\n    </tr>\n    <tr>\n      <td>Lưỡi Hổ Xanh</td>\n      <td>50–70cm</td>\n      <td>Sảnh, không gian lớn</td>\n      <td>200.000đ – 400.000đ</td>\n    </tr>\n    <tr>\n      <td>Lưỡi Hổ trồng chậu composite</td>\n      <td>40–60cm</td>\n      <td>Không gian trang trí cao cấp</td>\n      <td>300.000đ – 600.000đ</td>\n    </tr>\n  </table>\n\n  <p style=\"font-style:italic;\">Giá đã bao gồm cây + chậu. Có thể thay đổi tùy kích thước và loại chậu.</p>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">HƯỚNG DẪN TRỒNG VÀ CHĂM SÓC</h3>\n\n  <p><strong style=\"color:#3bb77e;\">Ánh sáng:</strong></p>\n  <ul>\n    <li>Ưa bóng râm, chịu thiếu sáng tốt</li>\n    <li>Có thể đặt ở nơi ánh sáng nhẹ, không để nắng gắt chiếu vào lá</li>\n    <li>Nhiệt độ lý tưởng: 20–30°C</li>\n  </ul>\n\n  <p><strong style=\"color:#3bb77e;\">Đất trồng:</strong></p>\n  <ul>\n    <li>Cây không kén đất (đất khô cằn, pha cát, đất thịt đều được)</li>\n    <li>Đất có độ kiềm nhẹ là tốt nhất</li>\n    <li>Cần đảm bảo thoát nước tốt</li>\n  </ul>\n\n  <p><strong style=\"color:#3bb77e;\">Tưới nước:</strong></p>\n  <ul>\n    <li>KHÔNG tưới nhiều để tránh úng</li>\n    <li>Chỉ tưới khi đất khô hoàn toàn</li>\n    <li>Mùa đông giảm lượng nước tối đa</li>\n  </ul>\n\n  <p><strong style=\"color:#3bb77e;\">Bón phân:</strong></p>\n  <ul>\n    <li>Bón 1 tháng/lần vào mùa sinh trưởng</li>\n    <li>Dùng phân đạm, lân, kali hoặc phân tan chậm</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">PHƯƠNG PHÁP NHÂN GIỐNG</h3>\n\n  <p><strong style=\"color:#3bb77e;\">1. Nhân giống bằng cây con:</strong></p>\n  <ul>\n    <li>Tách cây con khi thay chậu</li>\n    <li>Loại bỏ rễ khô, đất cũ</li>\n    <li>Trồng sang chậu mới và tưới nước nhẹ</li>\n  </ul>\n\n  <p><strong style=\"color:#3bb77e;\">2. Nhân giống bằng hom lá:</strong></p>\n  <ul>\n    <li>Chọn lá đẹp, xanh, cắt sát gốc</li>\n    <li>Chia lá thành các khúc 5cm</li>\n    <li>Để khô 1 ngày cho vết cắt se lại</li>\n    <li>Chôn nửa lá vào đất và phun sương giữ ẩm</li>\n    <li>Đặt nơi nắng nhẹ để rễ phát triển</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">CÂY LƯỠI HỔ TRONG DỊP TẾT</h3>\n\n  <ul>\n    <li>Tạo điểm nhấn sang trọng, hiện đại</li>\n    <li>Ý nghĩa may mắn cho năm mới</li>\n    <li>Là món quà phong thủy được nhiều người ưa chuộng</li>\n    <li>Dễ chăm – phù hợp thời điểm bận rộn dịp lễ</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">LƯU Ý KHI CHỌN CÂY</h3>\n  <ul>\n    <li>Lá xanh, không héo, không bị đốm lạ</li>\n    <li>Thân chắc khỏe, không thối gốc</li>\n    <li>Rễ tươi, bám chắc vào đất</li>\n    <li>Kích thước phù hợp không gian trưng bày</li>\n  </ul>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">TẠI SAO NÊN CHỌN CÂY LƯỠI HỔ?</h3>\n\n  <ol>\n    <li>Thanh lọc không khí hiệu quả – được NASA chứng nhận</li>\n    <li>Dễ trồng, cực kỳ dễ chăm</li>\n    <li>Nhả oxy ban đêm – tốt cho giấc ngủ</li>\n    <li>Phong thủy mạnh – trừ tà, hút may mắn</li>\n    <li>Linh hoạt – đặt được mọi nơi từ phòng khách đến phòng tắm</li>\n    <li>Giá phải chăng, tiết kiệm chi phí</li>\n    <li>Tốt cho sức khỏe – giảm dị ứng, hỗ trợ hô hấp</li>\n  </ol>\n\n  <h3 style=\"color:#3bb77e; margin-top:30px;\">CẢM NHẬN TỪ NGƯỜI DÙNG</h3>\n\n  <blockquote style=\"border-left:4px solid #3bb77e; padding-left:10px; margin:10px 0;\">\n    “Cây dễ chăm nhất từng mua, để văn phòng máy lạnh vẫn xanh tốt!” – Anh Minh, Hà Nội\n  </blockquote>\n\n  <blockquote style=\"border-left:4px solid #3bb77e; padding-left:10px; margin:10px 0;\">\n    “Con tôi bị hen, từ khi đặt cây trong phòng ngủ đỡ hơn nhiều.” – Chị Hoa, TP.HCM\n  </blockquote>\n\n  <blockquote style=\"border-left:4px solid #3bb77e; padding-left:10px; margin:10px 0;\">\n    “Đặt cây trong phòng tắm giúp hút ẩm cực tốt, không còn mùi.” – Anh Tuấn, Đà Nẵng\n  </blockquote>\n\n</div>', NULL, 400000.00, 199000.00, 50000.00, 100, 'Cây Lưỡi Hổ: Ý Nghĩa Phong Thủy, Công Dụng, Cách Chăm Sóc Chi Tiết', 'Cây Lưỡi Hổ – loại cây phong thủy nổi bật với khả năng thanh lọc không khí, hút tài lộc và mang lại năng lượng tích cực. Tìm hiểu ý nghĩa, cách chăm sóc và công dụng chi tiết của cây Lưỡi Hổ.', '[\"c\\u00e2y l\\u01b0\\u1ee1i h\\u1ed5\",\"\\u00fd ngh\\u0129a c\\u00e2y l\\u01b0\\u1ee1i h\\u1ed5\",\"c\\u00f4ng d\\u1ee5ng c\\u00e2y l\\u01b0\\u1ee1i h\\u1ed5\",\"ch\\u0103m s\\u00f3c c\\u00e2y l\\u01b0\\u1ee1i h\\u1ed5\",\"c\\u00e2y phong th\\u1ee7y trong nh\\u00e0\",\"c\\u00e2y h\\u00fat t\\u00e0i l\\u1ed9c\",\"c\\u00e2y l\\u1ecdc kh\\u00f4ng kh\\u00ed\",\"l\\u01b0\\u1ee1i h\\u1ed5 phong th\\u1ee7y\",\"c\\u00e2y \\u0111\\u1ec3 b\\u00e0n d\\u1ec5 ch\\u0103m\"]', 'https://xanhworld.vn/san-pham/cay-luoi-ho-y-nghia-phong-thuy-cach-cham-soc-cong-dung', 2, NULL, '[1,6,16,14,15,13,12,9,8,17,2,7]', '[26,12,27,28,23,24,29]', '[19,20,21,22,13,14]', 1, NULL, NULL, 1, 1, NULL, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(4, 'XWCLV071225', 'Cây Lộc Vừng: Đặc Điểm, Ý Nghĩa và Bảng Giá Cập Nhật', 'cay-loc-vung-dac-diem-y-nghia-va-bang-gia-cap-nhat', '<h2 style=\"color: #3bb77e; font-weight: bold; margin-top: 20px;\">C&Acirc;Y LỘC VỪNG &ndash; C&Acirc;Y C&Ocirc;NG TR&Igrave;NH B&Oacute;NG M&Aacute;T CAO CẤP VỚI GI&Aacute; TRỊ CẢNH QUAN V&Agrave; PHONG THỦY ĐẶC BIỆT</h2>\r\n<div style=\"line-height: 1.7; font-size: 16px;\">\r\n<p><strong style=\"color: #3bb77e;\">C&acirc;y Lộc Vừng</strong> (Barringtonia acutangula) l&agrave; một trong những loại c&acirc;y c&ocirc;ng tr&igrave;nh b&oacute;ng m&aacute;t được y&ecirc;u th&iacute;ch nhờ vẻ đẹp sang trọng, hoa đỏ rực rỡ v&agrave; &yacute; nghĩa phong thủy gắn liền với t&agrave;i lộc &ndash; may mắn. C&acirc;y xuất hiện nhiều trong c&aacute;c c&ocirc;ng tr&igrave;nh lớn như c&ocirc;ng vi&ecirc;n, khu đ&ocirc; thị, khu sinh th&aacute;i, resort, biệt thự v&agrave; c&aacute;c dự &aacute;n cảnh quan quy m&ocirc;. Với khả năng sinh trưởng mạnh, chịu hạn, chịu nhiệt v&agrave; chống x&oacute;i m&ograve;n tốt, c&acirc;y Lộc Vừng c&ograve;n l&agrave; lựa chọn h&agrave;ng đầu cho c&aacute;c khu vực ven biển, ven s&ocirc;ng.</p>\r\n<p>Kh&ocirc;ng chỉ mang gi&aacute; trị thẩm mỹ v&agrave; phong thủy, c&acirc;y Lộc Vừng c&ograve;n l&agrave; loại c&acirc;y c&oacute; tuổi thọ cao, gi&aacute; trị kinh tế lớn. C&acirc;y c&agrave;ng gi&agrave;, c&agrave;ng đẹp, d&aacute;ng c&agrave;ng độc &ndash; gi&aacute; trị c&agrave;ng tăng theo thời gian.</p>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">ĐẶC ĐIỂM CHI TIẾT CỦA C&Acirc;Y LỘC VỪNG</h3>\r\n<ul>\r\n<li><strong>T&ecirc;n khoa học:</strong> Barringtonia acutangula</li>\r\n<li><strong>T&ecirc;n gọi kh&aacute;c:</strong> C&acirc;y Mưng, C&acirc;y Chiếc</li>\r\n<li><strong>Họ:</strong> Lecythidaceae</li>\r\n<li><strong>Nguồn gốc:</strong> Nam &Aacute;, Bắc &Uacute;c &ndash; ph&acirc;n bố từ Afghanistan đến Philippines, Queensland</li>\r\n<li><strong>Ph&acirc;n bố tại Việt Nam:</strong> C&oacute; mặt tr&ecirc;n khắp l&atilde;nh thổ, từ Bắc v&agrave;o Nam đến tận C&ocirc;n Đảo</li>\r\n<li><strong>Th&acirc;n c&acirc;y:</strong> Gỗ chắc, sinh trưởng mạnh, ph&acirc;n t&aacute;n c&agrave;nh tốt</li>\r\n<li><strong>T&aacute;n l&aacute;:</strong> Rộng, xanh quanh năm, tạo b&oacute;ng m&aacute;t tự nhi&ecirc;n</li>\r\n<li><strong>L&aacute;:</strong> H&igrave;nh m&aacute;c, mềm, b&oacute;ng, nhiều nước</li>\r\n<li><strong>Hoa:</strong> Nở 2 đợt: th&aacute;ng 4&ndash;6 v&agrave; 9&ndash;11, m&agrave;u đỏ rực hoặc trắng tinh khiết</li>\r\n<li><strong>Ch&ugrave;m hoa:</strong> D&agrave;i, rủ xuống tạo th&agrave;nh dải hoa đẹp mắt</li>\r\n<li><strong>Quả:</strong> H&igrave;nh hộp/tr&ograve;n d&agrave;i 9&ndash;11cm, chuyển v&agrave;ng n&acirc;u khi ch&iacute;n</li>\r\n<li><strong>Tuổi thọ:</strong> Rất l&acirc;u năm, c&agrave;ng gi&agrave; c&agrave;ng c&oacute; gi&aacute; trị nghệ thuật</li>\r\n</ul>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">ƯU ĐIỂM NỔI BẬT</h3>\r\n<ul>\r\n<li>Hoa đỏ rực rỡ, nở đều đặn 2 lần/năm</li>\r\n<li>T&aacute;n l&aacute; rộng &ndash; tạo b&oacute;ng m&aacute;t xuất sắc</li>\r\n<li>Th&iacute;ch nghi tốt m&ocirc;i trường ven biển, ngập mặn</li>\r\n<li>Chịu hạn, chịu nhiệt, dễ chăm s&oacute;c</li>\r\n<li>&Iacute;t s&acirc;u bệnh, ph&aacute;t triển bền vững</li>\r\n<li>Giữ đất, chống x&oacute;i m&ograve;n hiệu quả</li>\r\n<li>Lọc bụi, hấp thụ kh&iacute; độc, cải thiện chất lượng kh&ocirc;ng kh&iacute;</li>\r\n</ul>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">PH&Acirc;N LOẠI C&Acirc;Y LỘC VỪNG</h3>\r\n<h4 style=\"color: #3bb77e;\">1. C&acirc;y Lộc Vừng Hoa Đỏ &ndash; Phổ biến nhất</h4>\r\n<ul>\r\n<li>Hoa đỏ rực rỡ, mang sắc may mắn</li>\r\n<li>Nguồn gốc Nam &Aacute;, Philippines</li>\r\n<li>D&ugrave;ng trang tr&iacute; s&acirc;n vườn, trước cửa nh&agrave;, khu đ&ocirc; thị</li>\r\n</ul>\r\n<h4 style=\"color: #3bb77e;\">2. C&acirc;y Lộc Vừng Hoa Trắng</h4>\r\n<ul>\r\n<li>T&ecirc;n khoa học: Barringtonia racemosa</li>\r\n<li>Hoa trắng pha hồng nhẹ &ndash; thanh tao, trang nh&atilde;</li>\r\n<li>Th&iacute;ch hợp lối đi, cảnh quan cần sự tinh tế</li>\r\n</ul>\r\n<h4 style=\"color: #3bb77e;\">3. C&acirc;y Rau Vừng (Lộc Vừng Ven Biển)</h4>\r\n<ul>\r\n<li>Sinh sống v&ugrave;ng ngập mặn, ven biển miền Nam</li>\r\n<li>Quả mọc trực tiếp từ th&acirc;n &ndash; kh&aacute;c với hai loại tr&ecirc;n</li>\r\n<li>B&oacute;ng m&aacute;t tốt, giữ đất hiệu quả</li>\r\n</ul>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">&Yacute; NGHĨA PHONG THỦY</h3>\r\n<ul>\r\n<li><strong>Thuộc bộ tứ:</strong> Sanh &ndash; Sung &ndash; T&ugrave;ng &ndash; Lộc (c&acirc;y đem lại may mắn)</li>\r\n<li>Đại diện cho nh&oacute;m Ph&uacute;c &ndash; Lộc &ndash; Thọ</li>\r\n<li>Lộc = T&agrave;i lộc, tiền bạc</li>\r\n<li>&ldquo;Vừng\" gợi nhắc c&acirc;u &ldquo;Vừng ơi mở cửa ra&rdquo; &ndash; đ&oacute;n may mắn v&agrave;o nh&agrave;</li>\r\n<li>Hoa đỏ: Hỷ sự, t&agrave;i lộc, may mắn</li>\r\n<li>Th&acirc;n chắc khỏe: Sự bền vững, trường tồn</li>\r\n<li>T&aacute;n rộng: Gắn kết, h&ograve;a thuận, sung t&uacute;c</li>\r\n<li>Xua đuổi t&agrave; kh&iacute;, thu h&uacute;t năng lượng t&iacute;ch cực</li>\r\n</ul>\r\n<p><strong>M&agrave;u sắc hợp mệnh:</strong> Xanh (Mộc), Đỏ (Hỏa)</p>\r\n<p><strong>Hợp mệnh:</strong> Mộc, Hỏa</p>\r\n<p><strong>Hợp tuổi:</strong> Mậu Ngọ 1978, T&acirc;n Dậu 1981, Đinh M&atilde;o 1987, Kỷ Tỵ 1989&hellip;</p>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">GI&Aacute; TRỊ TRONG ĐỜI SỐNG</h3>\r\n<h4 style=\"color: #3bb77e;\">1. T&ocirc; điểm cảnh quan</h4>\r\n<ul>\r\n<li>Ch&ugrave;m hoa đỏ rực đẹp như r&egrave;m thi&ecirc;n nhi&ecirc;n</li>\r\n<li>Tạo kh&ocirc;ng gian sang trọng, thơ mộng</li>\r\n<li>Giảm nắng n&oacute;ng, l&agrave;m m&aacute;t s&acirc;n vườn</li>\r\n<li>Ph&ugrave; hợp: biệt thự, khu đ&ocirc; thị, c&ocirc;ng vi&ecirc;n, resort</li>\r\n</ul>\r\n<h4 style=\"color: #3bb77e;\">2. Bảo vệ m&ocirc;i trường</h4>\r\n<ul>\r\n<li>Lộc Vừng lọc bụi tốt, hấp thụ kh&iacute; độc</li>\r\n<li>T&aacute;n l&aacute; d&agrave;y gi&uacute;p giảm nhiệt độ m&ocirc;i trường</li>\r\n<li>Hệ rễ giữ đất, chống sạt lở ven s&ocirc;ng, ven biển</li>\r\n</ul>\r\n<h4 style=\"color: #3bb77e;\">3. Gi&aacute; trị kinh tế</h4>\r\n<ul>\r\n<li>C&acirc;y l&acirc;u năm, d&aacute;ng đẹp c&oacute; gi&aacute; trị từ v&agrave;i triệu đến h&agrave;ng trăm triệu</li>\r\n<li>Được ưa chuộng trong giới sưu tầm c&acirc;y cảnh</li>\r\n<li>D&ugrave;ng tạo bonsai &ndash; c&agrave;ng l&acirc;u năm c&agrave;ng qu&yacute;</li>\r\n</ul>\r\n<h4 style=\"color: #3bb77e;\">4. C&ocirc;ng dụng y học</h4>\r\n<ul>\r\n<li>L&aacute; v&agrave; vỏ: giảm vi&ecirc;m, trị nhiễm khuẩn</li>\r\n<li>Rễ: hỗ trợ điều trị cao huyết &aacute;p, đau nhức xương khớp</li>\r\n<li>Dược liệu trong y học cổ truyền d&acirc;n gian</li>\r\n</ul>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">BẢNG GI&Aacute; SẢN PHẨM 2025</h3>\r\n<table style=\"border-collapse: collapse; width: 100%; border-color: #3bb77e;\" border=\"1\" cellspacing=\"0\" cellpadding=\"10\">\r\n<tbody>\r\n<tr style=\"background: #3bb77e; color: white;\">\r\n<th>Chiều cao c&acirc;y</th>\r\n<th>Size bầu</th>\r\n<th>C&ocirc;ng dụng</th>\r\n<th>Gi&aacute; (VNĐ)</th>\r\n</tr>\r\n<tr>\r\n<td>50&ndash;80cm</td>\r\n<td>15&ndash;20cm</td>\r\n<td>C&acirc;y giống, bonsai nhỏ</td>\r\n<td>80.000 &ndash; 120.000</td>\r\n</tr>\r\n<tr>\r\n<td>80cm&ndash;1,2m</td>\r\n<td>20&ndash;25cm</td>\r\n<td>S&acirc;n vườn nhỏ, chậu cảnh</td>\r\n<td>150.000 &ndash; 200.000</td>\r\n</tr>\r\n<tr>\r\n<td>1,2&ndash;1,5m</td>\r\n<td>25&ndash;30cm</td>\r\n<td>Biệt thự, cảnh quan s&acirc;n vườn</td>\r\n<td>250.000 &ndash; 350.000</td>\r\n</tr>\r\n<tr>\r\n<td>1,5&ndash;2m</td>\r\n<td>30&ndash;40cm</td>\r\n<td>C&ocirc;ng vi&ecirc;n, khu đ&ocirc; thị</td>\r\n<td>450.000 &ndash; 600.000</td>\r\n</tr>\r\n<tr>\r\n<td>Tr&ecirc;n 2m</td>\r\n<td>40&ndash;50cm</td>\r\n<td>Dự &aacute;n lớn, đường phố</td>\r\n<td><strong>Li&ecirc;n hệ</strong></td>\r\n</tr>\r\n<tr>\r\n<td>C&acirc;y đại thụ</td>\r\n<td>Theo y&ecirc;u cầu</td>\r\n<td>Dự &aacute;n cao cấp</td>\r\n<td><strong>Li&ecirc;n hệ</strong></td>\r\n</tr>\r\n</tbody>\r\n</table>\r\n<p style=\"font-style: italic; margin-top: 10px;\">Gi&aacute; tham khảo &ndash; thay đổi t&ugrave;y thời điểm, d&aacute;ng c&acirc;y v&agrave; số lượng.</p>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">VỊ TR&Iacute; TRỒNG PH&Ugrave; HỢP</h3>\r\n<ul>\r\n<li>S&acirc;n vườn biệt thự, villa, resort</li>\r\n<li>C&ocirc;ng vi&ecirc;n đ&ocirc; thị, khu sinh hoạt cộng đồng</li>\r\n<li>C&acirc;y tuyến đường, dải ph&acirc;n c&aacute;ch</li>\r\n<li>Trước nh&agrave; &ndash; tăng t&agrave;i lộc</li>\r\n<li>Qu&aacute;n x&aacute;, cửa h&agrave;ng, khu kinh doanh</li>\r\n<li>Khu sinh th&aacute;i, dự &aacute;n cảnh quan</li>\r\n<li>V&ugrave;ng ven biển &ndash; chống x&oacute;i m&ograve;n</li>\r\n</ul>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">HƯỚNG DẪN TRỒNG C&Acirc;Y</h3>\r\n<h4 style=\"color: #3bb77e;\">Phương ph&aacute;p chiết c&agrave;nh (Khuyến nghị)</h4>\r\n<ol>\r\n<li>Chuẩn bị dao sắc, đất tơi xốp, t&uacute;i nilon, trấu, rễ b&egrave;o</li>\r\n<li>Khoanh vỏ c&agrave;nh khỏe, b&oacute; hỗn hợp đất &ndash; trấu &ndash; rễ b&egrave;o</li>\r\n<li>Bọc nilon giữ ẩm để rễ ph&aacute;t triển</li>\r\n<li>Cắt c&agrave;nh chiết v&agrave; trồng v&agrave;o chậu/hố đất</li>\r\n<li>Thời điểm tốt nhất: Th&aacute;ng 6&ndash;7</li>\r\n</ol>\r\n<h4 style=\"color: #3bb77e;\">Gieo hạt (&iacute;t d&ugrave;ng hơn)</h4>\r\n<ul>\r\n<li>Chọn hạt từ quả ch&iacute;n</li>\r\n<li>Gieo v&agrave;o đất tơi, giữ ẩm</li>\r\n<li>Ươm c&acirc;y non cho đến khi đạt đủ chiều cao</li>\r\n</ul>\r\n<h4 style=\"color: #3bb77e;\">Trồng ngo&agrave;i vườn</h4>\r\n<ul>\r\n<li>Đ&agrave;o hố s&acirc;u vừa phải</li>\r\n<li>Đặt c&acirc;y, lấp đất, cố định th&acirc;n</li>\r\n<li>Tưới đều v&agrave; che nắng nhẹ ban đầu</li>\r\n</ul>\r\n<h4 style=\"color: #3bb77e;\">Trồng chậu</h4>\r\n<ul>\r\n<li>D&ugrave;ng chậu s&acirc;u, rộng</li>\r\n<li>Đất tơi, tho&aacute;t nước tốt</li>\r\n<li>Th&iacute;ch hợp trồng bonsai hoặc c&acirc;y cảnh nhỏ</li>\r\n</ul>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">HƯỚNG DẪN CHĂM S&Oacute;C</h3>\r\n<p><strong style=\"color: #3bb77e;\">Đất trồng:</strong> Đất tơi xốp, pha trấu, xơ dừa, ph&acirc;n chuồng hoai.</p>\r\n<p><strong style=\"color: #3bb77e;\">Tưới nước:</strong></p>\r\n<ul>\r\n<li>C&acirc;y non: tưới 2 lần/ng&agrave;y</li>\r\n<li>C&acirc;y lớn: tưới vừa đủ, tr&aacute;nh ngập &uacute;ng</li>\r\n</ul>\r\n<p><strong style=\"color: #3bb77e;\">&Aacute;nh s&aacute;ng:</strong> Ưa s&aacute;ng mạnh, cần nơi tho&aacute;ng.</p>\r\n<p><strong style=\"color: #3bb77e;\">B&oacute;n ph&acirc;n:</strong></p>\r\n<ul>\r\n<li>C&acirc;y nhỏ: B&oacute;n ph&acirc;n định kỳ mỗi th&aacute;ng</li>\r\n<li>C&acirc;y lớn: B&oacute;n nhẹ</li>\r\n<li>C&acirc;y sắp ra hoa: Bổ sung ph&acirc;n k&iacute;ch th&iacute;ch nụ</li>\r\n</ul>\r\n<p><strong style=\"color: #3bb77e;\">Nhiệt độ:</strong> Th&iacute;ch nghi tốt, chịu nắng n&oacute;ng lẫn r&eacute;t nhẹ.</p>\r\n<p><strong style=\"color: #3bb77e;\">Ph&ograve;ng s&acirc;u bệnh:</strong> Kiểm tra l&aacute;, tỉa c&agrave;nh s&acirc;u, d&ugrave;ng biện ph&aacute;p sinh học.</p>\r\n<p><strong style=\"color: #3bb77e;\">Chăm s&oacute;c c&acirc;y mới trồng:</strong> Che nắng 5&ndash;7 ng&agrave;y đầu, tưới giữ ẩm.</p>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">LƯU &Yacute; KHI CHỌN MUA C&Acirc;Y</h3>\r\n<ul>\r\n<li>L&aacute; xanh, kh&ocirc;ng v&agrave;ng &uacute;a</li>\r\n<li>Th&acirc;n thẳng, khỏe, kh&ocirc;ng sẹo bệnh</li>\r\n<li>Rễ trắng, chắc, kh&ocirc;ng bị thối</li>\r\n<li>K&iacute;ch thước ph&ugrave; hợp kh&ocirc;ng gian dự &aacute;n</li>\r\n<li>Y&ecirc;u cầu giấy chứng nhận nguồn gốc</li>\r\n</ul>\r\n<h3 style=\"color: #3bb77e; margin-top: 30px;\">ƯU THẾ KHI CHỌN C&Acirc;Y LỘC VỪNG TRONG CẢNH QUAN</h3>\r\n<p>C&acirc;y Lộc Vừng kh&ocirc;ng chỉ tạo b&oacute;ng m&aacute;t m&agrave; c&ograve;n mang lại sự thịnh vượng, may mắn v&agrave; vẻ đẹp sinh th&aacute;i bền vững. T&aacute;n l&aacute; rộng, rễ khỏe v&agrave; hoa rũ xuống thơ mộng khiến c&acirc;y trở th&agrave;nh điểm nhấn tuyệt đẹp trong mọi c&ocirc;ng tr&igrave;nh.</p>\r\n</div>', NULL, 50000.00, 9900.00, 2000.00, 100, 'Cây Lộc Vừng: Đặc Điểm, Ý Nghĩa và Bảng Giá Cập Nhật', 'Cây Lộc Vừng là lựa chọn lý tưởng cho sân vườn, công trình và khu đô thị nhờ tán rộng, hoa đẹp và sức sống mạnh. Nhận báo giá sỉ theo số lượng, hỗ trợ vận chuyển toàn quốc.', '[\"c\\u00e2y l\\u1ed9c v\\u1eebng\",\"c\\u00e2y c\\u00f4ng tr\\u00ecnh b\\u00f3ng m\\u00e1t\",\"c\\u00e2y l\\u1ed9c v\\u1eebng hoa \\u0111\\u1ecf\",\"l\\u1ed9c v\\u1eebng c\\u00f4ng tr\\u00ecnh\",\"gi\\u00e1 c\\u00e2y l\\u1ed9c v\\u1eebng\",\"c\\u00e2y b\\u00f3ng m\\u00e1t \\u0111\\u00f4 th\\u1ecb\",\"c\\u00e2y c\\u1ea3nh quan\",\"c\\u00e2y tr\\u1ed3ng bi\\u1ec7t th\\u1ef1\",\"c\\u00e2y l\\u1ed9c v\\u1eebng gi\\u00e1 s\\u1ec9\"]', 'https://xanhworld.vn//san-pham/cay-loc-vung-dac-diem-y-nghia-va-bang-gia-cap-nhat', 1, NULL, '[\"1\",\"8\"]', '[30,31,32,33,34,35,36]', '[23]', 1, 1, '2025-12-07 14:34:47', 1, 1, NULL, '2025-12-07 14:34:47', '2025-12-07 14:34:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_faqs`
--

CREATE TABLE `product_faqs` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính FAQ sản phẩm',
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `question` varchar(191) NOT NULL COMMENT 'Câu hỏi của khách hàng',
  `answer` text DEFAULT NULL COMMENT 'Câu trả lời',
  `order` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_faqs`
--

INSERT INTO `product_faqs` (`id`, `product_id`, `question`, `answer`, `order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cây phát tài có ý nghĩa gì trong phong thủy?', 'Cây phát tài tượng trưng cho tài lộc, may mắn, vượng khí và sự thịnh vượng. Cây phù hợp đặt tại nhà ở, văn phòng, cửa hàng kinh doanh để thu hút năng lượng tích cực.', 0, '2025-12-05 07:43:56', '2025-12-05 08:19:18'),
(2, 1, 'Cây phát tài hợp mệnh gì?', 'Tùy từng loại:\n\nPhát tài núi hợp mệnh Thủy – Mộc – Hỏa\n\nPhát lộc hợp mệnh Kim\n\nPhát tài đỏ hợp mệnh Hỏa\n\nBúp sen hợp mệnh Kim – Mộc – Thủy\nHầu hết các loại đều phù hợp nhiều mệnh trong ngũ hành.', 1, '2025-12-05 07:43:56', '2025-12-05 09:56:13'),
(3, 1, 'Cây phát tài chăm sóc có khó không?', 'Không. Cây rất dễ chăm: sống tốt trong môi trường thiếu sáng, chỉ cần tưới nước định kỳ và thỉnh thoảng tắm nắng nhẹ để cây xanh tốt.', 2, '2025-12-05 07:43:56', '2025-12-05 08:19:18'),
(4, 1, 'Cây phát tài nên đặt ở vị trí nào?', 'Theo phong thủy, nên đặt ở hướng Đông hoặc Đông Nam để thu hút tài lộc. Trong thực tế có thể đặt ở bàn làm việc, phòng khách, quầy lễ tân, lối vào cửa hàng.', 3, '2025-12-05 07:43:56', '2025-12-05 08:19:18'),
(5, 1, 'Bao lâu nên thay chậu cho cây phát tài?', 'Thông thường 1 năm thay chậu 1 lần để giúp bộ rễ phát triển khỏe, đất tơi xốp, thoát nước tốt hơn.', 4, '2025-12-05 07:43:56', '2025-12-05 08:19:18'),
(6, 1, 'Cây phát tài có độc không?', 'Đa số các loại không độc. Một số ít có thể gây kích ứng nhẹ nếu nhựa cây dính vào da, chỉ cần rửa sạch sau khi cắt tỉa.', 5, '2025-12-05 07:43:56', '2025-12-05 08:19:18'),
(7, 1, 'Cây phát tài giá bao nhiêu?', 'Tùy loại và kích thước:\n\nTừ 70.000đ đến khoảng 3.000.000đ\nGiá cao hơn đối với cây lớn, lâu năm hoặc tạo dáng phong thủy.', 6, '2025-12-05 07:43:56', '2025-12-05 09:56:13'),
(8, 1, 'Cây phát tài trồng thủy sinh được không?', 'Có. Một số loại như phát tài búp sen hoặc phát lộc rất phù hợp trồng thủy sinh, dễ chăm, sạch đẹp.', 7, '2025-12-05 07:43:56', '2025-12-05 08:19:18'),
(9, 1, 'Cây phát tài ra hoa có ý nghĩa gì?', 'Cây phát tài ra hoa được xem là dấu hiệu tài lộc – vận may đang đến. Đây là hiện tượng hiếm gặp và được xem là điềm tốt.', 8, '2025-12-05 07:43:56', '2025-12-05 08:19:18'),
(10, 1, 'Tôi mới bắt đầu chơi cây, có nên mua cây phát tài không?', 'Rất phù hợp. Cây dễ trồng, ít sâu bệnh, chăm sóc đơn giản và mang ý nghĩa phong thủy tốt, phù hợp cả người mới chơi.', 9, '2025-12-05 07:43:56', '2025-12-05 08:19:18'),
(11, 2, 'Cây Kim Tiền có ý nghĩa gì trong phong thủy?', 'Cây Kim Tiền tượng trưng cho tài lộc, may mắn, sự thịnh vượng và phát triển. Đây là loại cây được tin rằng mang đến nguồn năng lượng tích cực, giúp thu hút tiền bạc và vận khí tốt.', 0, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(12, 2, 'Cây Kim Tiền hợp mệnh gì?', 'Cây Kim Tiền thuộc hành Mộc, rất hợp với người mệnh Mộc và Hỏa. Tuy nhiên hầu hết các mệnh khác vẫn có thể trồng vì cây mang tính trung hòa, dễ dung hòa với không gian sống.', 1, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(13, 2, 'Cây Kim Tiền có dễ chăm sóc không?', 'Rất dễ. Cây chịu bóng tốt, sống khỏe trong môi trường máy lạnh, chỉ cần tưới nước vừa phải và giữ đất thoát nước tốt.', 2, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(14, 2, 'Cây Kim Tiền nên đặt ở đâu để hút tài lộc?', 'Phong thủy khuyên đặt ở hướng Đông hoặc Đông Nam để kích hoạt tài lộc. Trong thực tế có thể đặt tại bàn làm việc, phòng khách, quầy lễ tân hoặc cửa hàng kinh doanh.', 3, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(15, 2, 'Cây Kim Tiền bao lâu thì nên thay chậu?', 'Thường 1–2 năm thay chậu một lần vì bộ rễ của cây phát triển mạnh. Việc thay chậu giúp đất tơi xốp hơn và cây sinh trưởng tốt.', 4, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(16, 2, 'Cây Kim Tiền có độc không?', 'Cây không độc nhưng nhựa cây có thể gây kích ứng nhẹ nếu dính vào da. Khi cắt tỉa chỉ cần rửa tay sạch là được.', 5, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(17, 2, 'Cây Kim Tiền giá bao nhiêu?', 'Tùy kích thước và dáng cây:_x000D_\n_x000D_\n15–30cm: 70.000đ – 100.000đ_x000D_\n30–50cm: 200.000đ – 350.000đ_x000D_\nTrên 50cm: 350.000đ – 500.000đ_x000D_\n_x000D_\nGiá thay đổi theo chậu, chất lượng rễ và dáng cây.', 6, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(18, 2, 'Cây Kim Tiền có trồng thủy sinh được không?', 'Có. Cây Kim Tiền trồng thủy sinh rất đẹp, sạch và dễ chăm, chỉ cần thay nước định kỳ và đặt nơi có ánh sáng nhẹ.', 7, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(19, 2, 'Cây Kim Tiền ra hoa có ý nghĩa gì?', 'Kim Tiền ra hoa được xem là điềm cực kỳ may mắn, báo hiệu tài lộc và cơ hội mới đang đến. Đây là hiện tượng hiếm gặp và mang ý nghĩa tốt lành.', 8, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(20, 2, 'Tại sao cây Kim Tiền bị vàng lá?', 'Nguyên nhân thường gặp: tưới quá nhiều nước, đất bí, thiếu ánh sáng, hoặc cây bị nấm rễ. Cần kiểm tra độ ẩm đất và điều chỉnh ánh sáng, đồng thời cắt bỏ lá vàng.', 9, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(21, 2, 'Cách tưới nước cho cây Kim Tiền thế nào là đúng?', 'Tưới 1–2 lần/tuần bằng cách phun sương khi đất còn ẩm. Không tưới quá nhiều vì cây chịu úng rất kém. Mỗi tháng có thể tưới đẫm một lần.', 10, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(22, 2, 'Cây Kim Tiền có sống được trong phòng lạnh không?', 'Có. Cây sống rất tốt trong môi trường máy lạnh, văn phòng hoặc nơi ít ánh sáng.', 11, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(23, 2, 'Nên dùng đất gì để trồng cây Kim Tiền?', 'Dùng đất tơi xốp, thoát nước tốt như đất mùn, trộn thêm trấu hun, xơ dừa hoặc xỉ than giúp hạn chế úng rễ.', 12, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(24, 2, 'Bao lâu nên bón phân cho cây Kim Tiền?', '2–3 tháng bón phân một lần. Dùng phân hữu cơ hoặc phân NPK dành cho cây cảnh, rải cách gốc khoảng 10–15cm.', 13, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(25, 2, 'Cây Kim Tiền có cần ánh sáng không?', 'Có, nhưng chỉ cần ánh sáng gián tiếp. Tránh để dưới nắng gắt vì có thể khiến lá cháy mép.', 14, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(26, 2, 'Cây Kim Tiền có hợp đặt ở quầy thu ngân không?', 'Rất hợp. Đây là vị trí giúp thu hút tài lộc theo quan niệm phong thủy, thường thấy ở cửa hàng, spa và doanh nghiệp.', 15, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(27, 2, 'Cây Kim Tiền có thể để trên bàn làm việc không?', 'Được. Kích thước nhỏ từ 15–30cm rất phù hợp đặt bàn, giúp mang đến cảm giác xanh mát và may mắn trong công việc.', 16, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(28, 2, 'Cách nhân giống cây Kim Tiền như thế nào?', 'Có thể nhân giống bằng cành hoặc bằng lá. Cắt lá hoặc cành khỏe, để khô vài giờ rồi cắm vào đất ẩm. Sau 1 tháng cây sẽ ra rễ mới.', 17, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(29, 2, 'Vì sao đất trồng cây Kim Tiền có mùi hoặc bị nấm?', 'Do tưới quá nhiều hoặc chậu không thoát nước tốt. Cần thay đất mới và giảm lượng nước tưới.', 18, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(30, 2, 'Người mới chơi cây có nên chọn cây Kim Tiền không?', 'Rất nên. Đây là loại cây dễ trồng nhất trong nhóm cây phong thủy, ít sâu bệnh, sống khỏe và mang nhiều ý nghĩa tốt lành.', 19, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(31, 3, 'Cây Lưỡi Hổ có ý nghĩa gì trong phong thủy?', 'Cây Lưỡi Hổ tượng trưng cho sức mạnh, sự bảo vệ, xua đuổi năng lượng xấu và thu hút tài lộc. Là cây mang lại nguồn năng lượng dương mạnh mẽ cho không gian sống.', 0, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(32, 3, 'Cây Lưỡi Hổ hợp mệnh gì?', 'Cây Lưỡi Hổ thuộc hành Mộc nhưng lại mang nét Kim do dáng lá sắc nhọn, vì vậy hợp với mệnh Kim, Mộc, Thổ.', 1, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(33, 3, 'Cây Lưỡi Hổ có lọc không khí không?', 'Có. Đây là một trong những loại cây được NASA công nhận có khả năng lọc khí độc như formaldehyde, benzene, toluene và biến CO₂ thành O₂ vào ban đêm.', 2, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(34, 3, 'Cây Lưỡi Hổ có dễ chăm không?', 'Rất dễ. Cây ưa khô, chịu hạn tốt, chỉ cần tưới 1 lần/tuần và đặt nơi có ánh sáng nhẹ.', 3, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(35, 3, 'Cây Lưỡi Hổ nên đặt ở đâu?', 'Có thể đặt ở phòng khách, phòng ngủ, văn phòng, cửa hàng hoặc nơi có nguồn khí không tốt để cân bằng năng lượng.', 4, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(36, 3, 'Cây Lưỡi Hổ có sống được trong phòng máy lạnh không?', 'Có. Cây sống tốt trong môi trường khô và ít sáng, rất phù hợp văn phòng.', 5, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(37, 3, 'Tưới nước cho cây Lưỡi Hổ thế nào là đúng?', 'Tưới 1 lần/7–10 ngày. Không tưới quá nhiều vì dễ gây úng rễ.', 6, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(38, 3, 'Tại sao lá Lưỡi Hổ bị nhăn hoặc mềm?', 'Do tưới quá nhiều hoặc đất bị nén chặt khiến rễ không thoáng khí. Cần thay đất hoặc giảm tưới.', 7, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(39, 3, 'Cây Lưỡi Hổ để phòng ngủ được không?', 'Được. Cây nhả oxy vào ban đêm, rất tốt cho đường hô hấp.', 8, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(40, 3, 'Cây Lưỡi Hổ có độc không?', 'Cây không độc nhưng nhựa của lá có thể gây kích ứng nhẹ khi nuốt hoặc tiếp xúc trực tiếp. Nên đặt xa trẻ nhỏ và thú cưng.', 9, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(41, 3, 'Cây Lưỡi Hổ giá bao nhiêu?', 'Giá dao động từ 50.000đ đến 500.000đ tùy kích thước và số lượng bụi.', 10, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(42, 3, 'Bao lâu nên thay chậu cho cây Lưỡi Hổ?', '1–2 năm thay chậu một lần để bộ rễ phát triển mạnh và đất tơi xốp hơn.', 11, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(43, 3, 'Cây Lưỡi Hổ có nhân giống được không?', 'Được. Có thể tách bụi hoặc cắt lá để nhân giống.', 12, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(44, 3, 'Vì sao lá Lưỡi Hổ bị cháy mép?', 'Do ánh nắng gắt chiếu trực tiếp hoặc thiếu nước trong thời gian dài.', 13, '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(45, 3, 'Cây Lưỡi Hổ có đặt ở bàn làm việc được không?', 'Hoàn toàn được. Đây là cây phong thủy giúp tăng sự tập trung và xua đuổi khí xấu.', 14, '2025-12-06 04:26:56', '2025-12-06 04:26:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_how_tos`
--

CREATE TABLE `product_how_tos` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính hướng dẫn sử dụng',
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(191) NOT NULL COMMENT 'Tiêu đề hướng dẫn',
  `description` text DEFAULT NULL COMMENT 'Mô tả tổng quan',
  `steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Danh sách bước (JSON)' CHECK (json_valid(`steps`)),
  `supplies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dụng cụ cần thiết (JSON)' CHECK (json_valid(`supplies`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái hiển thị',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_how_tos`
--

INSERT INTO `product_how_tos` (`id`, `product_id`, `title`, `description`, `steps`, `supplies`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cách chăm sóc cây Phát Tài để cây luôn xanh tốt và thu hút tài lộc', 'Hướng dẫn các bước chăm sóc cây Phát Tài đúng cách để cây phát triển khỏe, lá xanh đẹp và mang lại năng lượng phong thủy tích cực cho ngôi nhà bạn.', '[\"B\\u01b0\\u1edbc 1: \\u0110\\u1eb7t c\\u00e2y \\u1edf v\\u1ecb tr\\u00ed c\\u00f3 \\u00e1nh s\\u00e1ng nh\\u1eb9, tr\\u00e1nh n\\u1eafng g\\u1eaft tr\\u1ef1c ti\\u1ebfp.\",\"B\\u01b0\\u1edbc 2: T\\u01b0\\u1edbi n\\u01b0\\u1edbc gi\\u1eef \\u1ea9m v\\u1eeba ph\\u1ea3i, kh\\u00f4ng \\u0111\\u1ec3 \\u0111\\u1ea5t b\\u1ecb \\u00fang.\",\"B\\u01b0\\u1edbc 3: Phun s\\u01b0\\u01a1ng l\\u00ean l\\u00e1 2\\u20133 l\\u1ea7n m\\u1ed7i tu\\u1ea7n \\u0111\\u1ec3 c\\u00e2y quang h\\u1ee3p t\\u1ed1t.\",\"B\\u01b0\\u1edbc 4: B\\u00f3n ph\\u00e2n h\\u1eefu c\\u01a1 ho\\u1eb7c NPK lo\\u00e3ng m\\u1ed7i 1\\u20132 th\\u00e1ng.\",\"B\\u01b0\\u1edbc 5: T\\u1ec9a b\\u1ecf l\\u00e1 v\\u00e0ng, thay ch\\u1eadu \\u0111\\u1ecbnh k\\u1ef3 1 n\\u0103m\\/l\\u1ea7n \\u0111\\u1ec3 c\\u00e2y ph\\u00e1t tri\\u1ec3n kh\\u1ecfe.\"]', '[\"D\\u1ee5ng c\\u1ee5 1: B\\u00ecnh t\\u01b0\\u1edbi n\\u01b0\\u1edbc ho\\u1eb7c b\\u00ecnh phun s\\u01b0\\u01a1ng\",\"D\\u1ee5ng c\\u1ee5 2: Ph\\u00e2n b\\u00f3n h\\u1eefu c\\u01a1 ho\\u1eb7c NPK\",\"D\\u1ee5ng c\\u1ee5 3: K\\u00e9o c\\u1eaft t\\u1ec9a l\\u00e1\",\"D\\u1ee5ng c\\u1ee5 4: \\u0110\\u1ea5t tr\\u1ed3ng t\\u01a1i x\\u1ed1p, tho\\u00e1t n\\u01b0\\u1edbc t\\u1ed1t (n\\u1ebfu thay ch\\u1eadu)\"]', 1, '2025-12-05 07:43:56', '2025-12-05 08:19:18'),
(2, 2, 'Cách chăm sóc cây Kim Tiền để cây luôn xanh mướt và hút tài lộc', 'Hướng dẫn các bước chăm sóc cây Kim Tiền đúng cách để cây phát triển khỏe mạnh, lá bóng đẹp và mang lại tài lộc – may mắn cho không gian sống.', '[\"B\\u01b0\\u1edbc 1: \\u0110\\u1eb7t c\\u00e2y \\u1edf n\\u01a1i c\\u00f3 \\u00e1nh s\\u00e1ng gi\\u00e1n ti\\u1ebfp, tr\\u00e1nh n\\u1eafng g\\u1eaft chi\\u1ebfu tr\\u1ef1c ti\\u1ebfp v\\u00e0o l\\u00e1.\",\"B\\u01b0\\u1edbc 2: T\\u01b0\\u1edbi n\\u01b0\\u1edbc 1\\u20132 l\\u1ea7n\\/tu\\u1ea7n b\\u1eb1ng c\\u00e1ch phun s\\u01b0\\u01a1ng, ch\\u1ec9 t\\u01b0\\u1edbi th\\u00eam khi \\u0111\\u1ea5t kh\\u00f4 b\\u1ec1 m\\u1eb7t.\",\"B\\u01b0\\u1edbc 3: Duy tr\\u00ec \\u0111\\u1ed9 tho\\u00e1ng cho \\u0111\\u1ea5t, tr\\u00e1nh \\u0111\\u1ec3 c\\u00e2y b\\u1ecb \\u00fang r\\u1ec5 b\\u1eb1ng c\\u00e1ch d\\u00f9ng ch\\u1eadu tho\\u00e1t n\\u01b0\\u1edbc t\\u1ed1t.\",\"B\\u01b0\\u1edbc 4: B\\u00f3n ph\\u00e2n h\\u1eefu c\\u01a1 ho\\u1eb7c ph\\u00e2n ch\\u1eadm tan 2\\u20133 th\\u00e1ng\\/l\\u1ea7n \\u0111\\u1ec3 c\\u00e2y ph\\u00e1t tri\\u1ec3n \\u1ed5n \\u0111\\u1ecbnh.\",\"B\\u01b0\\u1edbc 5: Lau s\\u1ea1ch l\\u00e1 m\\u1ed7i tu\\u1ea7n v\\u00e0 c\\u1eaft b\\u1ecf l\\u00e1 v\\u00e0ng \\u0111\\u1ec3 h\\u1ea1n ch\\u1ebf s\\u00e2u b\\u1ec7nh v\\u00e0 gi\\u1eef c\\u00e2y xanh \\u0111\\u1eb9p.\",\"B\\u01b0\\u1edbc 6: Thay ch\\u1eadu \\u0111\\u1ecbnh k\\u1ef3 1\\u20132 n\\u0103m\\/l\\u1ea7n gi\\u00fap b\\u1ed9 r\\u1ec5 ph\\u00e1t tri\\u1ec3n m\\u1ea1nh h\\u01a1n.\"]', '[\"D\\u1ee5ng c\\u1ee5 1: B\\u00ecnh phun s\\u01b0\\u01a1ng ho\\u1eb7c b\\u00ecnh t\\u01b0\\u1edbi\",\"D\\u1ee5ng c\\u1ee5 2: \\u0110\\u1ea5t tr\\u1ed3ng t\\u01a1i x\\u1ed1p, tho\\u00e1t n\\u01b0\\u1edbc t\\u1ed1t\",\"D\\u1ee5ng c\\u1ee5 3: Ph\\u00e2n h\\u1eefu c\\u01a1 ho\\u1eb7c ph\\u00e2n NPK ch\\u1eadm tan\",\"D\\u1ee5ng c\\u1ee5 4: Kh\\u0103n m\\u1ec1m \\u0111\\u1ec3 lau l\\u00e1\",\"D\\u1ee5ng c\\u1ee5 5: K\\u00e9o c\\u1eaft t\\u1ec9a\"]', 1, '2025-12-06 03:29:17', '2025-12-06 03:29:17'),
(3, 3, 'Cách chăm sóc cây Lưỡi Hổ để cây luôn khỏe mạnh và lọc không khí tốt', 'Hướng dẫn các bước chăm sóc cây Lưỡi Hổ đúng kỹ thuật để cây sinh trưởng mạnh, lá cứng đẹp và phát huy tối đa khả năng thanh lọc không khí.', '[\"B\\u01b0\\u1edbc 1: \\u0110\\u1eb7t c\\u00e2y \\u1edf n\\u01a1i c\\u00f3 \\u00e1nh s\\u00e1ng nh\\u1eb9 ho\\u1eb7c \\u00e1nh s\\u00e1ng gi\\u00e1n ti\\u1ebfp.\",\"B\\u01b0\\u1edbc 2: T\\u01b0\\u1edbi n\\u01b0\\u1edbc 7\\u201310 ng\\u00e0y\\/l\\u1ea7n, ch\\u1ec9 t\\u01b0\\u1edbi khi \\u0111\\u1ea5t kh\\u00f4 ho\\u00e0n to\\u00e0n.\",\"B\\u01b0\\u1edbc 3: S\\u1eed d\\u1ee5ng \\u0111\\u1ea5t t\\u01a1i x\\u1ed1p, tho\\u00e1t n\\u01b0\\u1edbc t\\u1ed1t \\u0111\\u1ec3 tr\\u00e1nh \\u00fang r\\u1ec5.\",\"B\\u01b0\\u1edbc 4: Lau l\\u00e1 1 l\\u1ea7n\\/tu\\u1ea7n gi\\u00fap c\\u00e2y quang h\\u1ee3p hi\\u1ec7u qu\\u1ea3.\",\"B\\u01b0\\u1edbc 5: B\\u00f3n ph\\u00e2n h\\u1eefu c\\u01a1 ho\\u1eb7c ph\\u00e2n tan ch\\u1eadm m\\u1ed7i 2\\u20133 th\\u00e1ng.\",\"B\\u01b0\\u1edbc 6: Thay ch\\u1eadu \\u0111\\u1ecbnh k\\u1ef3 1\\u20132 n\\u0103m\\/l\\u1ea7n khi c\\u00e2y m\\u1ecdc nhi\\u1ec1u b\\u1ee5i.\"]', '[\"D\\u1ee5ng c\\u1ee5 1: B\\u00ecnh t\\u01b0\\u1edbi ho\\u1eb7c b\\u00ecnh phun s\\u01b0\\u01a1ng\",\"D\\u1ee5ng c\\u1ee5 2: \\u0110\\u1ea5t t\\u01a1i x\\u1ed1p \\u2013 tho\\u00e1t n\\u01b0\\u1edbc t\\u1ed1t\",\"D\\u1ee5ng c\\u1ee5 3: Ph\\u00e2n h\\u1eefu c\\u01a1 ho\\u1eb7c ph\\u00e2n tan ch\\u1eadm\",\"D\\u1ee5ng c\\u1ee5 4: Kh\\u0103n m\\u1ec1m \\u0111\\u1ec3 lau l\\u00e1\",\"D\\u1ee5ng c\\u1ee5 5: K\\u00e9o c\\u1eaft t\\u1ec9a ho\\u1eb7c d\\u1ee5ng c\\u1ee5 t\\u00e1ch b\\u1ee5i\"]', 1, '2025-12-06 04:26:56', '2025-12-06 04:26:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_views`
--

CREATE TABLE `product_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` varchar(191) DEFAULT NULL COMMENT 'Session ID cho user chưa đăng nhập',
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(191) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_views`
--

INSERT INTO `product_views` (`id`, `product_id`, `account_id`, `session_id`, `ip`, `user_agent`, `viewed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 07:52:23', '2025-12-05 07:52:23', '2025-12-05 07:52:23'),
(2, 1, NULL, 'p27AhQDkgbQ87E03vMgr0WPYQGjIwKxRZKK54x7T', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-05 07:57:26', '2025-12-05 07:57:26', '2025-12-05 07:57:26'),
(3, 1, NULL, 'MrdXWJJPVK2ZSCqrGSL1ueqRLRdpMnVnO43jaXbA', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-05 07:57:27', '2025-12-05 07:57:27', '2025-12-05 07:57:27'),
(4, 1, NULL, 'VOrJmu2S7iU3kekCpQcwgwZT5DZJEJksbJwx0hCR', '66.249.82.165', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-05 07:57:30', '2025-12-05 07:57:30', '2025-12-05 07:57:30'),
(5, 1, NULL, 'UIwbU4eZrKyh8BhTE29wr8GVYRiXxFSrx4YlXS5M', '66.249.82.164', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-05 07:57:30', '2025-12-05 07:57:30', '2025-12-05 07:57:30'),
(6, 1, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 08:31:41', '2025-12-05 08:31:41', '2025-12-05 08:31:41'),
(7, 1, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 09:09:16', '2025-12-05 09:09:16', '2025-12-05 09:09:16'),
(8, 1, NULL, 'nPQBkKV7WbN744XnFDV5mD22cEaGAQLYXoEBpvWe', '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 09:14:42', '2025-12-05 09:14:42', '2025-12-05 09:14:42'),
(9, 1, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 09:48:46', '2025-12-05 09:48:46', '2025-12-05 09:48:46'),
(10, 1, NULL, '9sAPNjRwzbSo2kYuCiLMoeprs3Xa63GSLsw2gvDf', '66.249.82.161', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-05 09:56:26', '2025-12-05 09:56:26', '2025-12-05 09:56:26'),
(11, 1, NULL, 'i5zuUbYRO68PcxyBCD6KTdLUcDHNfI5yvBYpLL4T', '66.249.82.161', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-05 09:56:27', '2025-12-05 09:56:27', '2025-12-05 09:56:27'),
(12, 1, NULL, 'NDjjxLxTQLq971gwxOVZhj89wI5unZOxBnbJfltl', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-05 09:56:30', '2025-12-05 09:56:30', '2025-12-05 09:56:30'),
(13, 1, NULL, 'uknSpYAUudqd0bk9EBXiwaIIGAVvCJkeGzGMClJU', '66.249.82.164', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-05 09:56:30', '2025-12-05 09:56:30', '2025-12-05 09:56:30'),
(14, 1, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', '183.81.24.83', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2025-12-05 11:04:01', '2025-12-05 11:04:01', '2025-12-05 11:04:01'),
(15, 1, NULL, 'u4lRTGD6VTv8Zb5MUhPcKemiecMU5Kj9QLIMjykd', '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 01:26:06', '2025-12-06 01:26:06', '2025-12-06 01:26:06'),
(16, 1, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 02:39:21', '2025-12-06 02:39:21', '2025-12-06 02:39:21'),
(17, 1, NULL, 'VLpSEhxUSDGgPtcGyHztnsdoXs2yp0jeM1KIe5uy', '66.249.71.37', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 02:43:02', '2025-12-06 02:43:02', '2025-12-06 02:43:02'),
(18, 1, NULL, 'OReXrsvBqYxZusmZN6c1dq8ezv0ru6tsdxW9y9vK', '66.249.65.70', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 02:43:03', '2025-12-06 02:43:03', '2025-12-06 02:43:03'),
(19, 1, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 03:27:58', '2025-12-06 03:27:58', '2025-12-06 03:27:58'),
(20, 2, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 03:31:23', '2025-12-06 03:31:23', '2025-12-06 03:31:23'),
(21, 2, NULL, 'iVyCj8FUoI3DW3chtzyAwa9q4A78NfylKajRwp39', '66.249.71.37', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 03:33:10', '2025-12-06 03:33:10', '2025-12-06 03:33:10'),
(22, 2, NULL, 'IS9rJTGfDZvJAjTGOqcFXDfxkPRXWbtBiB6FjNOE', '66.249.71.38', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 03:33:11', '2025-12-06 03:33:11', '2025-12-06 03:33:11'),
(23, 1, NULL, 'QyrKWFPwYl7R9jCXyP7oeD7uHW8mwvsyr48QvIVp', '66.249.65.71', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 03:34:15', '2025-12-06 03:34:15', '2025-12-06 03:34:15'),
(24, 1, NULL, 'ka4JtdpqsA1fWf4sAfRIwCEUMOmUes7DSSg4M3x0', '66.249.65.71', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 03:34:15', '2025-12-06 03:34:15', '2025-12-06 03:34:15'),
(25, 2, NULL, 'JDfZUAR8DAKFnSdbd1a4nTxL9iAKbqjo5Qu1yb8U', '66.249.82.162', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-06 03:52:28', '2025-12-06 03:52:28', '2025-12-06 03:52:28'),
(26, 2, NULL, 'B7WDQzbdOGYa6fcxdjhrZGBm3bgeMQybO3GcrhgD', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-06 03:52:28', '2025-12-06 03:52:28', '2025-12-06 03:52:28'),
(27, 2, NULL, 'G3f1NWHw4CnzHpcyE7CXKSFvKrQfOt9CEDkpsclT', '66.249.82.164', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-06 03:52:33', '2025-12-06 03:52:33', '2025-12-06 03:52:33'),
(28, 2, NULL, 'ejz0VlCwUhq9HRStzgQEsIfyTaq2G0RjQqLdqsww', '66.249.82.165', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-06 03:52:33', '2025-12-06 03:52:33', '2025-12-06 03:52:33'),
(29, 2, NULL, 'CQzmjQUCcwKAoJd3Kt1ajDI9AJRRMus1EWOKgmTa', '66.249.71.38', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:02:00', '2025-12-06 04:02:00', '2025-12-06 04:02:00'),
(30, 2, NULL, 'dVNFuVVedTrnxRQOeuOU2JQK8t6AkikMUzNWRe4O', '66.249.65.70', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:02:01', '2025-12-06 04:02:01', '2025-12-06 04:02:01'),
(31, 1, NULL, 'aBbIXi8YGKwUZMwtifM8CGo2G8mGK2xg3L5PAgx0', '66.249.71.37', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:02:29', '2025-12-06 04:02:29', '2025-12-06 04:02:29'),
(32, 1, NULL, 'UpoITIF6P7mXFJ4QyyDsAKOskjusy77aGyIoOTIg', '66.249.65.72', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:02:30', '2025-12-06 04:02:30', '2025-12-06 04:02:30'),
(33, 2, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 04:26:59', '2025-12-06 04:26:59', '2025-12-06 04:26:59'),
(34, 3, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 04:27:15', '2025-12-06 04:27:15', '2025-12-06 04:27:15'),
(35, 3, NULL, 'rxiVe7cqFDKgp9NOjjygUNrHXHc5kdJWNUTUwmrd', '66.249.71.37', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:28:48', '2025-12-06 04:28:48', '2025-12-06 04:28:48'),
(36, 3, NULL, 'g16daHIz5bp17BSD5tsIdoniwLdQWxS9Go6KdT31', '66.249.65.70', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:28:49', '2025-12-06 04:28:49', '2025-12-06 04:28:49'),
(37, 2, NULL, 'Wmbv2TONDCG34LRnnGKM771gsRld9WPpe6ST7H9K', '66.249.71.37', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:33:21', '2025-12-06 04:33:21', '2025-12-06 04:33:21'),
(38, 2, NULL, 'jfr2IOtwSoJn5EvmTO4zjqmKR9fvp0poBpOGtKSh', '66.249.65.70', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:33:21', '2025-12-06 04:33:21', '2025-12-06 04:33:21'),
(39, 3, NULL, 'FZZBj5RK0BvZMHlHWwB3rQ1G0Tmac2PeYLOHz44n', '66.249.65.70', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:35:00', '2025-12-06 04:35:00', '2025-12-06 04:35:00'),
(40, 3, NULL, 'Rgry0MJ3szHZjhf6oWXekTIrJ0m8W4uhffUbOYng', '66.249.65.70', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:35:01', '2025-12-06 04:35:01', '2025-12-06 04:35:01'),
(41, 1, NULL, 'ynycrw7PDOEbqLQiGeV6Nryan65j8WI2e7dxWTlm', '66.249.71.37', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:35:39', '2025-12-06 04:35:39', '2025-12-06 04:35:39'),
(42, 1, NULL, 'iLI0GzcapRLLharid3tzjQhhQMkxK6NkpANhodx8', '66.249.65.72', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 04:35:39', '2025-12-06 04:35:39', '2025-12-06 04:35:39'),
(43, 2, NULL, 'V0pKs9kxb8AxNMaVmBsvdToXJAhINbgeFB68kJk9', '66.249.71.38', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; GoogleOther)', '2025-12-06 04:59:24', '2025-12-06 04:59:24', '2025-12-06 04:59:24'),
(44, 3, NULL, 'EdCFi2l0GUoPi1f3o8RICXmHo7A58euytIps1xpE', '66.249.65.70', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; GoogleOther)', '2025-12-06 06:53:17', '2025-12-06 06:53:17', '2025-12-06 06:53:17'),
(45, 1, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', '113.23.88.250', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2025-12-06 09:33:43', '2025-12-06 09:33:43', '2025-12-06 09:33:43'),
(46, 1, NULL, 'HxrwocQeObRCZEl9WGD4GWFyO4PrOACBr04pmg6i', '66.249.65.72', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 09:37:00', '2025-12-06 09:37:00', '2025-12-06 09:37:00'),
(47, 1, NULL, 'UWzKaQTLtaNWy8Tpf6I6IpWzy3iGGcCG2YfmdYVi', '66.249.65.71', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 09:37:01', '2025-12-06 09:37:01', '2025-12-06 09:37:01'),
(48, 1, NULL, 'PFxv0rogcZHclWrXJSBl8lvJjSg4frJfc8xmUr1q', '66.249.71.37', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 09:37:47', '2025-12-06 09:37:47', '2025-12-06 09:37:47'),
(49, 1, NULL, 'DO7SJzWPkDFTaHaFyBW9e4FXdei0NC5eA9WEkfzL', '66.249.71.39', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-06 09:37:47', '2025-12-06 09:37:47', '2025-12-06 09:37:47'),
(50, 1, NULL, 'MZhczgZdFN9SAjYv1VebwazugHOTtrZvGQHSPsY5', '66.249.65.71', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; GoogleOther)', '2025-12-06 12:21:45', '2025-12-06 12:21:45', '2025-12-06 12:21:45'),
(51, 2, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', '113.185.40.221', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2025-12-06 15:10:22', '2025-12-06 15:10:22', '2025-12-06 15:10:22'),
(52, 2, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', '113.23.88.250', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2025-12-07 06:15:33', '2025-12-07 06:15:33', '2025-12-07 06:15:33'),
(53, 3, NULL, 'n1vfWz7CO3oqC2iOdBFtg5VoGdAufDKDmzqaLPIz', '66.249.65.71', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 09:57:00', '2025-12-07 09:57:00', '2025-12-07 09:57:00'),
(54, 3, NULL, '6FlcdWYzAErWZ3BeeOKJb5sKVlzIC9pnZsksKWEj', '66.249.71.39', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 09:57:01', '2025-12-07 09:57:01', '2025-12-07 09:57:01'),
(55, 1, NULL, 'XfOYEFb12ATLk37YHq57ELJtBIRq1OOhxr8k6FTp', '66.249.71.37', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 09:58:05', '2025-12-07 09:58:05', '2025-12-07 09:58:05'),
(56, 1, NULL, 'ExL6Cfyq19zPa395nD3qqLcP8FQtgnLwDGi4XoTu', '66.249.65.72', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 09:58:06', '2025-12-07 09:58:06', '2025-12-07 09:58:06'),
(57, 2, NULL, 'zbhhURcHnbMQfWuM6uS3ji1E8aSdFtgUgDuDdpLy', '66.249.71.37', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 09:58:46', '2025-12-07 09:58:46', '2025-12-07 09:58:46'),
(58, 2, NULL, 'KigIXwuyfjLDrmQUX7s3cSaIs4KNBZlYvpHEa9Y8', '66.249.65.72', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 09:58:47', '2025-12-07 09:58:47', '2025-12-07 09:58:47'),
(59, 1, 1, NULL, '113.23.88.250', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 11:16:52', '2025-12-07 11:16:52', '2025-12-07 11:16:52'),
(60, 4, 1, NULL, '113.23.88.250', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 14:36:01', '2025-12-07 14:36:01', '2025-12-07 14:36:01'),
(61, 4, NULL, 'X9D7Cl7E4w3rbfJsFB6u8fjd3D8IAy7iTGa02ZJp', '66.249.65.71', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 14:36:35', '2025-12-07 14:36:35', '2025-12-07 14:36:35'),
(62, 4, NULL, 'NXDmIsTZr7Z5TXB69isrwHwrWdAE1JmGb6WLidjO', '66.249.65.71', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 14:36:35', '2025-12-07 14:36:35', '2025-12-07 14:36:35'),
(63, 2, NULL, 'a8Fod3SuhS3Baqcx8HepXs6cC2IdWaB7c11aRshJ', '66.249.65.71', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 14:37:24', '2025-12-07 14:37:24', '2025-12-07 14:37:24'),
(64, 2, NULL, 'OSBLoneSucJBDheGruvZjhJg1VbAgE4tIva3IPvH', '66.249.65.71', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 14:37:24', '2025-12-07 14:37:24', '2025-12-07 14:37:24'),
(65, 1, NULL, 'FdWMZLg7xqh9t0RK5I4dAomTEgBtTS4WjvKJh1rd', '66.249.71.38', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 14:38:06', '2025-12-07 14:38:06', '2025-12-07 14:38:06'),
(66, 1, NULL, 'gDSyfgPM0XpYv0JKVxJUsVy5FpE2ArjntaeiCErW', '66.249.65.72', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 14:38:07', '2025-12-07 14:38:07', '2025-12-07 14:38:07'),
(67, 3, NULL, 'WFkWx8aKe3tbub0cfCwwfRlegHgKQ2ZOppg0O5Ud', '66.249.65.71', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 14:38:32', '2025-12-07 14:38:32', '2025-12-07 14:38:32'),
(68, 3, NULL, 'gAkhHruuKUbauj1Kaup8ndhGM7SySSB8z3tbgws3', '66.249.65.71', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 14:38:32', '2025-12-07 14:38:32', '2025-12-07 14:38:32'),
(69, 4, NULL, 'EbUqjnmHirbU7Ubif0s1A25YoDnkTpmlTQ5rOnmJ', '66.249.71.38', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 15:34:14', '2025-12-07 15:34:14', '2025-12-07 15:34:14'),
(70, 4, NULL, 'kM4KqBt7256TVIUSX72N28y3pDi56thPgOPqejjr', '66.249.65.72', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 15:34:14', '2025-12-07 15:34:14', '2025-12-07 15:34:14'),
(71, 4, NULL, 'MXc0VHAYUmJhPcStJzQQNDUEMDsV6zwhB3SMZQQd', '66.249.71.38', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 16:01:58', '2025-12-07 16:01:58', '2025-12-07 16:01:58'),
(72, 4, NULL, 'jumUcgBk9qsSopMHLchcSvW3DFRz9jRU5gm7utpn', '66.249.65.70', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-07 16:02:00', '2025-12-07 16:02:00', '2025-12-07 16:02:00'),
(73, 2, NULL, 'LASGe6cQ5ICaGlQ51M0cxmzOyThQA2ggRty2O5nE', '66.249.71.37', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-08 01:14:10', '2025-12-08 01:14:10', '2025-12-08 01:14:10'),
(74, 2, NULL, 'Ls8GU2xs17CbX8xYLbqar2TZwzEgR32dUWL0Df0c', '66.249.65.72', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-08 01:14:11', '2025-12-08 01:14:11', '2025-12-08 01:14:11'),
(75, 3, NULL, '4Muu44Dc3pp80rOwqHpBVM5sJzLrxgKntw5M1xEY', '66.249.71.37', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-08 01:14:44', '2025-12-08 01:14:44', '2025-12-08 01:14:44'),
(76, 3, NULL, 'C5WLa6uxjk4NOtI2sAplEqig6YvTWZE0VNt4l6Pb', '66.249.71.39', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-08 01:14:44', '2025-12-08 01:14:44', '2025-12-08 01:14:44'),
(77, 1, NULL, 'AlnmosmlTIFMNKoWnFaE9pUVDuP2Ax3fKi4sXKF6', '66.249.71.39', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-08 01:15:13', '2025-12-08 01:15:13', '2025-12-08 01:15:13'),
(78, 1, NULL, '1vMgGYJsmGpJM1mKh9oPYs52WawR0F3N9EP6NUW6', '66.249.65.72', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-08 01:15:14', '2025-12-08 01:15:14', '2025-12-08 01:15:14'),
(79, 4, NULL, 'bmOfXsNjJZ2WBWzZneG7q4IGZ1hDIgkEC88A02I2', '66.249.65.71', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-08 01:15:43', '2025-12-08 01:15:43', '2025-12-08 01:15:43'),
(80, 4, NULL, '00uYdLXnLMZzDeVOA2LG15sNzYWQe9sx64ypr3rD', '66.249.65.71', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-08 01:15:43', '2025-12-08 01:15:43', '2025-12-08 01:15:43'),
(81, 1, NULL, '1SewiPAV4Zq4ekL3VXDmVoy5WU4Lcav9DMrjNZ6X', '113.23.88.250', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-08 12:37:55', '2025-12-08 12:37:55', '2025-12-08 12:37:55'),
(82, 4, NULL, '2sTZBqV1DHtoplSlOb6ixSu2BAAx5CiqXtJJ4sVM', '113.23.88.250', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 12:38:15', '2025-12-08 12:38:15', '2025-12-08 12:38:15'),
(83, 4, NULL, 'n4rhTr3FbPUnYiv1xBPpsqs8qN3ju72f6pSmaOfh', '20.27.94.140', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot', '2025-12-09 01:29:55', '2025-12-09 01:29:55', '2025-12-09 01:29:55'),
(84, 4, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 01:31:10', '2025-12-09 01:31:10', '2025-12-09 01:31:10'),
(85, 3, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 01:31:29', '2025-12-09 01:31:29', '2025-12-09 01:31:29'),
(86, 4, NULL, 'JCHvABLObsxFReebTg2VYlicQFmApHZhZARGKN3D', '14.224.155.244', 'Googlebot', '2025-12-09 01:33:10', '2025-12-09 01:33:10', '2025-12-09 01:33:10'),
(87, 1, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 01:45:30', '2025-12-09 01:45:30', '2025-12-09 01:45:30'),
(88, 2, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 01:47:46', '2025-12-09 01:47:46', '2025-12-09 01:47:46'),
(89, 1, NULL, 'uV2VK2B6PfmOUXdgUCmHUd96CRE1FdNCsumBPk1I', '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 02:07:37', '2025-12-09 02:07:37', '2025-12-09 02:07:37'),
(90, 4, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 02:10:05', '2025-12-09 02:10:05', '2025-12-09 02:10:05'),
(91, 4, NULL, 'yPszaJ77ForxwHtYAd3lYdvxJY93t2IB4MCfNbQc', '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 02:10:33', '2025-12-09 02:10:33', '2025-12-09 02:10:33'),
(92, 4, NULL, 'QIvfr7BHhg91CcN11lh2hM1GFdXonLXu7XikCpT8', '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 02:14:15', '2025-12-09 02:14:15', '2025-12-09 02:14:15'),
(93, 4, NULL, 'w3v9hoFbgIiz5IJCayQySoTaVdXjCfiiNmF3E76w', '66.249.82.161', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 02:15:20', '2025-12-09 02:15:20', '2025-12-09 02:15:20'),
(94, 4, NULL, 'QLPtTDAxGQGSICRiv7GsGFDPp6vFGDTm4v13zdb0', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 02:15:20', '2025-12-09 02:15:20', '2025-12-09 02:15:20'),
(95, 4, NULL, 'svBukhBosR1AoTejnAudWgEQrplHYxZ8V0ORzuBC', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 02:15:24', '2025-12-09 02:15:24', '2025-12-09 02:15:24'),
(96, 4, NULL, 'c6IFveph1R46YOILkKIQFeZGHXCwU7FFHl57PMNO', '66.249.82.165', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 02:15:24', '2025-12-09 02:15:24', '2025-12-09 02:15:24'),
(97, 2, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 02:18:01', '2025-12-09 02:18:01', '2025-12-09 02:18:01'),
(98, 1, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2025-12-09 02:25:21', '2025-12-09 02:25:21', '2025-12-09 02:25:21'),
(99, 2, NULL, 'lSYJokD59ThhokFihVlWuySCwJ7ERjHkSUDfCEi1', '66.249.82.165', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 02:32:37', '2025-12-09 02:32:37', '2025-12-09 02:32:37'),
(100, 2, NULL, '666ayh7e9RxmYtzi0RGEOBAfK439sxf3BRlhrDdf', '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 02:38:47', '2025-12-09 02:38:47', '2025-12-09 02:38:47'),
(101, 2, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 02:51:01', '2025-12-09 02:51:01', '2025-12-09 02:51:01'),
(102, 2, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-09 03:22:14', '2025-12-09 03:22:14', '2025-12-09 03:22:14'),
(103, 2, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 04:10:37', '2025-12-09 04:10:37', '2025-12-09 04:10:37'),
(104, 2, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-09 04:43:40', '2025-12-09 04:43:40', '2025-12-09 04:43:40'),
(105, 2, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-09 06:24:28', '2025-12-09 06:24:28', '2025-12-09 06:24:28'),
(106, 2, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-09 06:56:51', '2025-12-09 06:56:51', '2025-12-09 06:56:51'),
(107, 4, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-09 07:08:11', '2025-12-09 07:08:11', '2025-12-09 07:08:11'),
(108, 3, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-09 07:08:19', '2025-12-09 07:08:19', '2025-12-09 07:08:19'),
(109, 2, NULL, '1YZX9FyrbxCl9KmbtayVzrS8AIkg5QGifQtwUJTT', '66.249.82.161', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 07:12:04', '2025-12-09 07:12:04', '2025-12-09 07:12:04'),
(110, 2, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2025-12-09 07:18:28', '2025-12-09 07:18:28', '2025-12-09 07:18:28'),
(111, 3, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-09 07:40:12', '2025-12-09 07:40:12', '2025-12-09 07:40:12'),
(112, 2, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2025-12-09 08:02:04', '2025-12-09 08:02:04', '2025-12-09 08:02:04'),
(113, 3, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-09 08:13:00', '2025-12-09 08:13:00', '2025-12-09 08:13:00'),
(114, 3, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 08:49:13', '2025-12-09 08:49:13', '2025-12-09 08:49:13'),
(115, 3, NULL, 'TQNtTTGa7hEjXRdf3o1QDSzLYEFKlYz1nUiVQRzk', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:51:49', '2025-12-09 08:51:49', '2025-12-09 08:51:49'),
(116, 3, NULL, 'gO1WPuKNRzHecCg0ClYvNzDyS57gSlsu7I4gVta4', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:51:50', '2025-12-09 08:51:50', '2025-12-09 08:51:50'),
(117, 3, NULL, 'wcHEGDLPXWQAGzv6MDnp3xoZ1vEdNFJ3p6thTupb', '66.249.82.164', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:51:53', '2025-12-09 08:51:53', '2025-12-09 08:51:53'),
(118, 3, NULL, 'z7RENO548OAaCifKyIaMnsh6d8wafW8VIPFmNJ80', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:51:53', '2025-12-09 08:51:53', '2025-12-09 08:51:53'),
(119, 3, NULL, '0wUk0hkg8bONXg2cT0uLQfQ3NKoevJRBQB0JQj7R', '66.249.82.161', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:56:04', '2025-12-09 08:56:04', '2025-12-09 08:56:04'),
(120, 3, NULL, 'ghVzKEIVFkXnsJF0675RThlbP0MFJSf6POjQgam5', '66.249.82.162', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:56:04', '2025-12-09 08:56:04', '2025-12-09 08:56:04'),
(121, 3, NULL, '50iXkjG0HSYsg4y0MAJLoi9vGhN9AmuSECDloNJt', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:56:07', '2025-12-09 08:56:07', '2025-12-09 08:56:07'),
(122, 3, NULL, 'IC9esDlnUDLZmOu6D7hy4RwTLvC9f80qbbSa3S6u', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:56:07', '2025-12-09 08:56:07', '2025-12-09 08:56:07'),
(123, 2, NULL, 'VN4SY9vAfDY75sqNqjPVfne1VDxIMbqpXbOgELtf', '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.208 Mobile/15E148 Safari/604.1', '2025-12-09 08:58:25', '2025-12-09 08:58:25', '2025-12-09 08:58:25'),
(124, 3, NULL, 'GAx3jdLuGeSwQKaypAuOhMPxiyS0aFFKcFEf6iQx', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:59:19', '2025-12-09 08:59:19', '2025-12-09 08:59:19'),
(125, 3, NULL, 'lGVfbf5UUBPkUnQ72Sb1IFokniZfSgY5tOGfiBIF', '66.249.82.161', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:59:19', '2025-12-09 08:59:19', '2025-12-09 08:59:19'),
(126, 3, NULL, 'P7hfQg70VV1FFQQv60Th3E30Jcqxwe5WXs19HCki', '66.249.82.164', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:59:23', '2025-12-09 08:59:23', '2025-12-09 08:59:23'),
(127, 3, NULL, 'pdiEHsLwler6zXLik3M4WRGoGEFpt3fQe5ljZdy7', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 08:59:23', '2025-12-09 08:59:23', '2025-12-09 08:59:23'),
(128, 2, NULL, 'nDzM0vLEgozZe4COQzhDNVZMwCEqBWyz460trDTH', '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/397.0.836500703 Mobile/15E148 Safari/604.1', '2025-12-09 08:59:41', '2025-12-09 08:59:41', '2025-12-09 08:59:41'),
(129, 2, NULL, 'QuDFHBprfvaqFLeq5LldaazoxsSMbfZ4RLS1Brkl', '66.249.71.38', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', '2025-12-09 09:00:59', '2025-12-09 09:00:59', '2025-12-09 09:00:59'),
(130, 2, NULL, 'DIPaUpyYre4Z5Knk9tA0RzezeXMvYVdId7FtAh0C', '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/397.0.836500703 Mobile/15E148 Safari/604.1', '2025-12-09 09:01:13', '2025-12-09 09:01:13', '2025-12-09 09:01:13'),
(131, 3, NULL, '3r8yG4bYJDGh84bUrGsn8y1AUEVspkkS1iOr0Yl9', '66.249.82.161', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:13:44', '2025-12-09 09:13:44', '2025-12-09 09:13:44'),
(132, 3, NULL, 'EEhptEh0cPMsQ5jqP2TMlsCzQS9hU81ZFYoqClFq', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:13:45', '2025-12-09 09:13:45', '2025-12-09 09:13:45'),
(133, 2, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2025-12-09 09:13:46', '2025-12-09 09:13:46', '2025-12-09 09:13:46'),
(134, 3, NULL, 'rIXS3KlsVpBns4FYVKV6bweEr4mlOv8C5MtXMZM1', '66.249.82.165', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:13:48', '2025-12-09 09:13:48', '2025-12-09 09:13:48'),
(135, 3, NULL, 'wvSqlHgjzpZmhjFyfZ2HdUKdG9W5KEuocNiu1rx1', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:13:49', '2025-12-09 09:13:49', '2025-12-09 09:13:49'),
(136, 3, NULL, 'oyZ6UEgmDgH4XNO4cBsuPimRljL7CBvmDHl5ZQxP', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:17:13', '2025-12-09 09:17:13', '2025-12-09 09:17:13'),
(137, 3, NULL, 'nNuquvqmZJgHaJjzCuF5ckJRDyqKx8UcweazR2Zi', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:17:13', '2025-12-09 09:17:13', '2025-12-09 09:17:13'),
(138, 3, NULL, 'dV74aAuj8JCZNxL9QJRyXMClykrGLWVqVLWJvsxN', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:17:17', '2025-12-09 09:17:17', '2025-12-09 09:17:17'),
(139, 3, NULL, '5xoDS62qyfvzA2amr30OxX1TlAhfNvLlLE6Rx40y', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:17:17', '2025-12-09 09:17:17', '2025-12-09 09:17:17'),
(140, 3, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-09 09:20:22', '2025-12-09 09:20:22', '2025-12-09 09:20:22'),
(141, 3, NULL, 'Jq6kIWgL6IuxoVWuH55TKb2yXYZ6PDhWiXZLGZAN', '66.249.82.161', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:20:32', '2025-12-09 09:20:32', '2025-12-09 09:20:32'),
(142, 3, NULL, '3j1o5RgxCETpSvW0aDQVq6ZlwpypQ0bJu47Ak1gX', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:20:32', '2025-12-09 09:20:32', '2025-12-09 09:20:32'),
(143, 3, NULL, 'RrhC2KvgNqR8XuxCG0J5xiTPKOGvVZq6igzZO2AN', '66.249.82.164', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:20:35', '2025-12-09 09:20:35', '2025-12-09 09:20:35'),
(144, 3, NULL, '4naPFmwK13ALrdc8dmNqwJlTnZ8pFBoJ1tkqS5JV', '66.249.82.165', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:20:36', '2025-12-09 09:20:36', '2025-12-09 09:20:36'),
(145, 3, NULL, '3rpnXzPDd0WNVxB2Ri3sYJ2b08BTkoBLcouK3xJE', '66.249.82.162', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:23:05', '2025-12-09 09:23:05', '2025-12-09 09:23:05'),
(146, 3, NULL, 'gNqZAIvq0fu3xLqOvJSyhZ4e39C4ZLHLJa42bw11', '66.249.82.161', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:23:05', '2025-12-09 09:23:05', '2025-12-09 09:23:05'),
(147, 3, NULL, 'bPGWUPAgw1UBgf3hZ32raMn761FDWNyJlGrJxwGx', '66.249.82.164', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:23:09', '2025-12-09 09:23:09', '2025-12-09 09:23:09'),
(148, 3, NULL, '48PaOKTmBiaBiZ4y1HmHYQEIbZvcidNBJY3xGtfl', '66.249.82.165', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:23:10', '2025-12-09 09:23:10', '2025-12-09 09:23:10'),
(149, 3, NULL, 'FS7bdL9YN0x4SK3fJBEUSv09rd9iEvyDd1cPPPq4', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:49:59', '2025-12-09 09:49:59', '2025-12-09 09:49:59'),
(150, 3, NULL, 'c3rhtTeGsXDloXiYUyLNDXj33KqLIgPY4X3cxeV9', '66.249.82.161', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:49:59', '2025-12-09 09:49:59', '2025-12-09 09:49:59'),
(151, 3, NULL, 'y0BDfTPZqBWmIUfVVHyxMuqsZk37imnEdhRnPgcT', '66.249.82.165', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:50:03', '2025-12-09 09:50:03', '2025-12-09 09:50:03'),
(152, 3, NULL, 'Z2vfW1VNGDIS0zZpaTIHp9kTxm1YPyLmydxohs7R', '66.249.82.164', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 09:50:03', '2025-12-09 09:50:03', '2025-12-09 09:50:03'),
(153, 3, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 CrKey/1.54.250320', '2025-12-09 10:00:28', '2025-12-09 10:00:28', '2025-12-09 10:00:28'),
(154, 3, NULL, 'yAvdqNddHh19QoURaLcMp0U8h6zSKDvQRn1B2Abh', '66.249.82.161', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:05:10', '2025-12-09 10:05:10', '2025-12-09 10:05:10'),
(155, 3, NULL, '8D2LPKAzsH1BuCObkowB3x8FX4VAVN7QjKbDW68O', '66.249.82.162', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:05:11', '2025-12-09 10:05:11', '2025-12-09 10:05:11'),
(156, 3, NULL, 'jwdjOOUd1bZ2r5uDQzFHNNzTV2HvFL6LkUfphD2O', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:05:14', '2025-12-09 10:05:14', '2025-12-09 10:05:14'),
(157, 3, NULL, 'BPeDidGkflFEU58L4mpZN8S3mCUTDvdMThpHwsoQ', '66.249.82.165', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:05:14', '2025-12-09 10:05:14', '2025-12-09 10:05:14'),
(158, 3, NULL, 'jA12nCo56heQgIz5bKktgTathYMrmdYTDgFsosgY', '66.249.82.162', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:22:25', '2025-12-09 10:22:25', '2025-12-09 10:22:25'),
(159, 3, NULL, 'l4TEkUrmkFvcW5T48Kxke0VloPrBrJEtpSJvfWdN', '66.249.82.161', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:22:26', '2025-12-09 10:22:26', '2025-12-09 10:22:26'),
(160, 3, NULL, 'Lg2BlXX3xQaJn28ylqIC3WD2q9T1FCSxKPHYxkX5', '66.249.82.164', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:22:29', '2025-12-09 10:22:29', '2025-12-09 10:22:29'),
(161, 3, NULL, 'RbI0d686WAOyQTUJP0RLYUhhYVEPCfRUJ5D1k4MD', '66.249.82.164', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:22:30', '2025-12-09 10:22:30', '2025-12-09 10:22:30'),
(162, 3, NULL, 'N9rBfhtlc7o9IwGSH7PYZzP5H0qBZmwXzyVKCWuz', '66.249.82.162', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:23:50', '2025-12-09 10:23:50', '2025-12-09 10:23:50'),
(163, 3, NULL, 'TkGKXXvb2gXaN6z62ZkKL2aUiDxPPlGHwcI2jSw6', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:23:50', '2025-12-09 10:23:50', '2025-12-09 10:23:50'),
(164, 3, NULL, 'jGf6kZDynwWOielg62BF0pxPQEG2n3fUCPsVscK8', '66.249.82.165', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:23:54', '2025-12-09 10:23:54', '2025-12-09 10:23:54'),
(165, 3, NULL, 'AscgWnxKnIvmu72lKYCOFV1sVUwmU2gkgjxJUWP2', '66.249.82.165', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:23:54', '2025-12-09 10:23:54', '2025-12-09 10:23:54'),
(166, 3, NULL, 'UTAWMznLPcycd8V91d0sWJoAjN8e2SxFiC6L5bAB', '66.249.82.162', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:28:30', '2025-12-09 10:28:30', '2025-12-09 10:28:30'),
(167, 3, NULL, 'zxFF7YXSuXUFd3SP6U5VKc7uRHRQSvwcpl7zBJN4', '66.249.82.163', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:28:30', '2025-12-09 10:28:30', '2025-12-09 10:28:30'),
(168, 3, NULL, 'DjkIefGG0hOeJv6vlyRzqad5Cnq5YxStHNIOdHPa', '66.249.82.163', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:28:34', '2025-12-09 10:28:34', '2025-12-09 10:28:34'),
(169, 3, NULL, '6XzxKc4yrrpHRuhVAMWAtPh86ox4jMWB9hkF8oip', '66.249.82.164', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-09 10:28:34', '2025-12-09 10:28:34', '2025-12-09 10:28:34'),
(170, 1, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 10:33:53', '2025-12-09 10:33:53', '2025-12-09 10:33:53'),
(171, 1, NULL, 'Mq1khOxzwqR0Q1PrTt0XJ3pTIwu5cXAUHmbUNO0V', '66.249.71.39', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-09 10:34:26', '2025-12-09 10:34:26', '2025-12-09 10:34:26'),
(172, 1, NULL, 'NqnoSKJx5z9a2C929ZeTDzcn8xzrpU4O0xIqWK7f', '66.249.65.71', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-09 10:34:26', '2025-12-09 10:34:26', '2025-12-09 10:34:26'),
(173, 3, NULL, 'pyeXgLJKMPhdrsLSl9SEVA6woaCXx0R8Fgn71Px5', '66.249.71.37', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-09 10:34:54', '2025-12-09 10:34:54', '2025-12-09 10:34:54'),
(174, 3, NULL, 'X04G7hX7e0g9aqvHOqbmLw7gQmKC1AkTnNkh29ln', '66.249.71.37', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-09 10:34:54', '2025-12-09 10:34:54', '2025-12-09 10:34:54'),
(175, 1, NULL, 'Edq4Tv4m7gF3z9hBA3lQ3gJwWA6uHRA3yNB0nqHX', '14.224.155.244', 'WhatsApp/2', '2025-12-09 10:35:13', '2025-12-09 10:35:13', '2025-12-09 10:35:13'),
(176, 4, NULL, 'OryD9ThXridMRdfJYnLyhqbrKgYoMEGZ62xp02G0', '66.249.71.37', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-09 10:35:24', '2025-12-09 10:35:24', '2025-12-09 10:35:24'),
(177, 4, NULL, 'tOZu34YksncYZzcAc0I3OBqgSKm6mqi2HNGAuY9B', '66.249.71.37', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-09 10:35:24', '2025-12-09 10:35:24', '2025-12-09 10:35:24'),
(178, 2, NULL, 'hNt44O4HbpBMNLYC6yXrD9r1WSJWCCobP0lassIc', '66.249.71.39', 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)', '2025-12-09 10:35:57', '2025-12-09 10:35:57', '2025-12-09 10:35:57'),
(179, 2, NULL, 'fN7HGIq7XNcSzJG3pN8FgxsE4D7z3NN8HP3KMB3z', '66.249.65.72', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)', '2025-12-09 10:35:58', '2025-12-09 10:35:58', '2025-12-09 10:35:58'),
(180, 1, NULL, 'topzhn9b1QO0fGYgK8X16MMbBnJrtvg99PjuMUzK', '113.23.88.250', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2025-12-09 11:13:26', '2025-12-09 11:13:26', '2025-12-09 11:13:26'),
(181, 1, NULL, 'cM0kAGBBIbJ730m2xiIeyQA3LpwalFf7gLvEEobZ', '113.23.88.250', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.208 Mobile/15E148 Safari/604.1', '2025-12-09 11:13:44', '2025-12-09 11:13:44', '2025-12-09 11:13:44'),
(182, 4, NULL, '0f1IgeSS2ja405IxhgvUYZlomfDA9xmsaPjCX3pQ', '66.249.65.70', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; GoogleOther)', '2025-12-09 11:57:25', '2025-12-09 11:57:25', '2025-12-09 11:57:25'),
(183, 1, NULL, 'nv30J5MRUvUYwEC2JzWDSvmVET1w36J0UaBFqggR', '142.44.225.142', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', '2025-12-09 20:00:14', '2025-12-09 20:00:14', '2025-12-09 20:00:14'),
(184, 3, NULL, 'poSO3ToXfZKHt9XXnISZbOadzLKrF6SVb1SrfsHH', '51.161.65.159', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', '2025-12-09 20:28:27', '2025-12-09 20:28:27', '2025-12-09 20:28:27'),
(185, 4, NULL, 'XW9Taq6PX3u6jDHWL3FPoDF6therIZ0uAl2MFUjU', '148.113.130.246', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', '2025-12-09 20:54:48', '2025-12-09 20:54:48', '2025-12-09 20:54:48'),
(186, 2, NULL, 'auhBzv7K0Ev4ATu8FtP5Qca3q1WXxosqNkdM3Dxr', '54.39.203.130', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', '2025-12-09 21:27:11', '2025-12-09 21:27:11', '2025-12-09 21:27:11'),
(187, 3, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-10 04:48:58', '2025-12-10 04:48:58', '2025-12-10 04:48:58');
INSERT INTO `product_views` (`id`, `product_id`, `account_id`, `session_id`, `ip`, `user_agent`, `viewed_at`, `created_at`, `updated_at`) VALUES
(188, 3, NULL, 'O7Ovn70aQXFqNG5TETw54zodiWtt5aFNIrspPdwo', '66.249.82.161', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-10 04:49:27', '2025-12-10 04:49:27', '2025-12-10 04:49:27'),
(189, 3, NULL, 'iEDvLK8YoYiNftUC8l7xADaovWKfSLODAe8TFTUy', '66.249.82.162', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-10 04:49:27', '2025-12-10 04:49:27', '2025-12-10 04:49:27'),
(190, 3, NULL, 'yR0Zt1IMGtoouYF0m3OI1leT0rIMvJrjLMqHW0o9', '66.249.82.165', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Chrome-Lighthouse', '2025-12-10 04:49:31', '2025-12-10 04:49:31', '2025-12-10 04:49:31'),
(191, 3, NULL, '2lYS1kSMr8HW0uzaCrFKYJC0JncVzcmeenk3HlCy', '66.249.82.164', 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', '2025-12-10 04:49:32', '2025-12-10 04:49:32', '2025-12-10 04:49:32'),
(192, 4, 1, NULL, '14.224.155.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-10 07:51:50', '2025-12-10 07:51:50', '2025-12-10 07:51:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `profiles`
--

CREATE TABLE `profiles` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính hồ sơ tài khoản',
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `fullname` varchar(191) DEFAULT NULL COMMENT 'Họ và tên',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Số điện thoại',
  `avatar` varchar(191) DEFAULT NULL COMMENT 'Ảnh đại diện',
  `gender` varchar(10) DEFAULT NULL COMMENT 'Giới tính',
  `birthday` date DEFAULT NULL COMMENT 'Ngày sinh',
  `extra` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dữ liệu mở rộng (JSON)' CHECK (json_valid(`extra`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL COMMENT 'Session ID',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP đăng nhập',
  `user_agent` text DEFAULT NULL COMMENT 'Thông tin trình duyệt',
  `payload` text NOT NULL COMMENT 'Dữ liệu session',
  `last_activity` int(11) NOT NULL COMMENT 'Hoạt động cuối (timestamp)'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính cấu hình',
  `key` varchar(191) NOT NULL COMMENT 'Khóa cấu hình (duy nhất)',
  `value` text DEFAULT NULL COMMENT 'Giá trị cấu hình',
  `type` varchar(191) NOT NULL DEFAULT 'string' COMMENT 'Kiểu dữ liệu (string, boolean, image, ...)',
  `group` varchar(191) DEFAULT NULL COMMENT 'Nhóm cấu hình',
  `label` varchar(191) DEFAULT NULL COMMENT 'Nhãn hiển thị',
  `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết',
  `is_public` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Có hiển thị công khai hay không',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `type`, `group`, `label`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'THẾ GIỚI CÂY XANH XWORLD', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(2, 'site_description', 'THẾ GIỚI CÂY XANH XWORLD – cây phong thủy, cây để bàn, cây lọc không khí, chậu cảnh đẹp. Decor không gian sống xanh – đẹp – bền. Giao cây tận nơi.', 'text', 'general', 'Mô tả SEO', NULL, 1, '2025-06-10 09:10:59', '2025-12-03 01:46:31'),
(3, 'site_logo', 'logo-xanhworld.vn.svg', 'image', 'general', 'Ảnh Logo', NULL, 1, '2025-06-10 09:10:59', '2025-09-09 12:40:05'),
(4, 'site_announcement', 'MIỄN PHÍ VẬN CHUYỂN...', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(5, 'site_favicon', 'favicon-xanhworld.vn.svg', 'image', 'general', 'Favicon chính', NULL, 1, '2025-06-10 09:10:59', '2025-09-15 11:03:08'),
(6, 'contact_email', 'xanhworldvietnam@gmail.com', 'email', 'general', 'Email hỗ trợ', NULL, 1, '2025-06-10 09:10:59', '2025-09-09 13:00:38'),
(7, 'contact_phone', '0827786198', 'text', 'general', 'Số điện thoại Đức Nobi 💖', NULL, 1, '2025-06-10 09:10:59', '2025-09-09 13:12:31'),
(8, 'contact_address', 'Xóm 3 - Xã Hà Đông - Thành Phố Hải Phòng', 'text', 'general', 'Địa chỉ bán hàng', NULL, 1, '2025-06-10 09:10:59', '2025-12-03 02:13:17'),
(9, 'seo_keywords', 'cây xanh, cây cảnh, cây văn phòng, cây phong thủy, cây nội thất, cây để bàn, cây trang trí, chậu cây cảnh, chậu xi măng, phụ kiện decor, setup góc làm việc, setup ban công, cây cảnh mini, cây cảnh để bàn, cây cảnh trong nhà, cây lọc không khí, cây hợp mệnh, cây thủy sinh, shop cây cảnh, xanhworld, xanh world', 'text', 'general', 'Keywords chính - Trang chủ', NULL, 1, '2025-06-10 09:10:59', '2025-12-03 01:44:27'),
(10, 'seo_author', 'Nguyễn Minh Đức (Đức Nobi)', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(11, 'facebook_link', 'https://www.facebook.com/ducnobi2004', 'url', 'general', 'Facebook Đức Nobi', NULL, 1, '2025-06-10 09:10:59', '2025-09-09 12:51:01'),
(12, 'twitter_link', 'https://twitter.com/', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(13, 'google_analytics', '', 'text', 'general', 'Google Analytics', NULL, 1, '2025-06-10 09:10:59', '2025-12-03 02:05:31'),
(14, 'enable_registration', 'true', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(15, 'enable_comments', 'true', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(16, 'enable_newsletter', 'true', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(17, 'site_timezone', 'Asia/Ho_Chi_Minh', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(18, 'site_language', 'vi', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(19, 'enable_ssl', 'true', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(20, 'maintenance_mode', '0', 'boolean', 'general', 'Chế độ bảo trì', NULL, 1, '2025-06-10 09:10:59', '2025-09-09 13:07:44'),
(21, 'copyright', 'Copyright &copy; {{ ((2025 != now()->year & 2025 < now()->year) ? \'2025 - \' : \'\'). now()->year }} <a style=\\\"color: green;\\\" href=\\\"{{ $settings->site_url }}\\\">{{ $settings->site_name }}</a>. All Rights Reserved.', 'textarea', 'general', 'Copyright Footer', NULL, 1, '2025-06-10 09:10:59', '2025-09-09 13:09:08'),
(22, 'contact_form_recipient', 'xanhworldvietnam@gmail.com', 'email', 'general', 'Email nhận thông tin', NULL, 1, '2025-06-10 09:10:59', '2025-12-03 01:55:55'),
(23, 'allow_file_uploads', '1', 'boolean', 'general', 'Cho phép tải lên FILE', 'Cho phép tải lên FILE', 1, '2025-06-10 09:10:59', '2025-09-09 12:43:04'),
(24, 'site_url', 'https://xanhworld.vn/', 'url', 'general', 'Domain', NULL, 1, '2025-06-10 09:10:59', '2025-12-05 09:58:49'),
(25, 'site_slug', '/', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(26, 'site_title', 'Thế Giới Cây Xanh XWORLD - Cửa hàng cây cảnh và cây phong thủy', 'text', 'general', 'Meta title - Trang chủ', NULL, 1, '2025-06-10 09:10:59', '2025-12-05 08:55:39'),
(27, 'google_search_console', '', 'text', 'general', 'Google Search Console', NULL, 1, '2025-06-10 09:10:59', '2025-12-03 02:05:49'),
(28, 'instagram_link', 'https://www.facebook.com/ducnobi2004', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(29, 'dmca', 'https://www.dmca.com/r/6x023q8', 'text', 'general', 'Chống sao chép', NULL, 1, '2025-06-10 09:10:59', '2025-12-05 09:12:50'),
(30, 'dmca_logo', 'https://images.dmca.com/Badges/DMCA_logo-grn-btn120w.png?ID=2fe593a9-a802-4d1b-9501-c3654a4771ed', 'text', 'general', 'Chống sao chép (IMG)', NULL, 1, '2025-06-10 09:10:59', '2025-12-03 02:05:06'),
(31, 'bo_cong_thuong', 'http://online.gov.vn/Home/WebDetails/137357', 'image', 'general', 'Ảnh bộ công thương', 'setting-bo_cong_thuong-1757497818.webp', 1, '2025-06-10 09:10:59', '2025-09-09 12:50:18'),
(32, 'contact_zalo', '0398951396', 'text', 'general', 'Zalo Đức Nobi 💖', NULL, 1, '2025-06-10 09:10:59', '2025-09-09 13:11:52'),
(33, 'telegram_link', 'https://discord.gg/nobifashion', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(34, 'discord_link', 'https://discord.gg/nobifashion', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(35, 'site_image', '', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', '2025-06-10 09:10:59'),
(36, 'google_tag_header', '<!-- Google Tag Manager -->\r\n<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':\r\nnew Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],\r\nj=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=\r\n\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);\r\n})(window,document,\'script\',\'dataLayer\',\'GTM-NZSDWPQW\');</script>\r\n<!-- End Google Tag Manager -->', 'text', 'general', 'Google Tag Header', NULL, 1, '2025-06-10 09:10:59', '2025-12-05 08:23:02'),
(37, 'google_tag_body', '<!-- Google Tag Manager (noscript) -->\r\n<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=GTM-NZSDWPQW\"\r\nheight=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\r\n<!-- End Google Tag Manager (noscript) -->', 'text', 'general', 'Thẻ Google Body', NULL, 1, '2025-06-10 09:10:59', '2025-12-05 08:23:19'),
(38, 'site_pinterest', '<meta name=\"p:domain_verify\" content=\"accf5ce84ca710c9250545e795682bbb\"/>', 'text', 'general', 'Xác minh Pinterest', NULL, 1, '2025-06-10 09:10:59', '2025-12-05 09:48:39'),
(39, 'site_banner', 'banner.webp', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', NULL),
(40, 'url_banner', 'setting-url_banner-1757498296.png', 'image', 'general', 'Banner chính', NULL, 1, '2025-06-10 09:10:59', '2025-09-09 12:58:16'),
(41, 'bing_tag_header', '', 'text', 'general', '', NULL, 1, '2025-06-10 09:10:59', NULL),
(43, 'postalCode', '180000', 'text', 'general', '', NULL, 1, '2025-06-10 17:31:16', NULL),
(44, 'subname', 'XWORLD', 'text', 'general', '', NULL, 1, '2025-06-15 17:23:46', NULL),
(45, 'city', 'Hải Phòng', 'text', 'general', 'Thành phố', NULL, 1, '2025-07-15 19:46:33', '2025-12-03 01:56:10'),
(46, 'district', 'Hải Phòng', 'text', 'general', 'Quận/Huyện', NULL, 1, '2025-07-15 19:47:28', '2025-12-03 01:57:02'),
(47, 'ward', 'PHƯỜNG LÃM HÀ', 'text', 'general', '', NULL, 1, '2025-07-15 19:47:28', NULL),
(48, 'detail_address', 'Xóm 3 - Xã Hà Đông - Thành Phố Hải Phòng', 'text', 'general', 'Địa chỉ bán hàng', NULL, 1, '2025-07-15 19:47:28', '2025-12-03 01:55:15'),
(49, 'latitude', '20.83943', 'text', 'general', '', NULL, 1, '2025-07-19 18:55:34', NULL),
(50, 'longitude', '106.65338', 'text', 'general', '', NULL, 1, '2025-07-19 18:55:34', NULL),
(51, 'enable_cart', 'true', 'text', 'general', '', NULL, 1, '2025-07-21 06:54:49', NULL),
(52, 'enable_order', 'true', 'text', 'general', '', NULL, 1, '2025-07-21 06:54:49', NULL),
(53, 'enable_payment', 'true', 'text', 'general', '', NULL, 1, '2025-07-21 06:57:43', NULL),
(54, 'is_demo', '0', 'boolean', 'general', NULL, NULL, 1, '2025-07-21 06:57:43', '2025-12-09 07:58:24'),
(55, 'site_tax_code', '030204013643', 'string', 'general', 'Mã số thuế', NULL, 1, '2025-10-30 17:15:00', '2025-11-16 17:45:39'),
(56, 'source_map', 'https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d15676.49041047659!2d106.688084!3d10.780834!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f2bc3a8979f%3A0xe5a3028117c2f383!2sHo%20Chi%20Minh%20City!5e0!3m2!1svi!2s!4v1764171111111!5m2!1svi!2s', 'string', 'general', 'Map XWORLD', NULL, 1, '2025-11-28 05:13:35', '2025-12-01 19:56:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sitemap_configs`
--

CREATE TABLE `sitemap_configs` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính cấu hình sitemap',
  `config_key` varchar(191) NOT NULL,
  `config_value` text DEFAULT NULL,
  `value_type` varchar(20) NOT NULL DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sitemap_configs`
--

INSERT INTO `sitemap_configs` (`id`, `config_key`, `config_value`, `value_type`, `created_at`, `updated_at`) VALUES
(1, 'enabled', '1', 'boolean', NULL, NULL),
(2, 'posts_enabled', '1', 'boolean', NULL, NULL),
(3, 'products_enabled', '1', 'boolean', NULL, NULL),
(4, 'categories_enabled', '1', 'boolean', NULL, NULL),
(5, 'tags_enabled', '1', 'boolean', NULL, NULL),
(6, 'pages_enabled', '1', 'boolean', NULL, NULL),
(7, 'images_enabled', '1', 'boolean', NULL, '2025-12-02 01:26:20'),
(8, 'ping_google_enabled', '1', 'boolean', NULL, NULL),
(9, 'ping_bing_enabled', '1', 'boolean', NULL, NULL),
(10, 'urls_per_file', '10000', 'integer', NULL, NULL),
(11, 'last_generated_at', '2025-12-02 15:26:05', 'datetime', NULL, '2025-12-02 01:26:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sitemap_excludes`
--

CREATE TABLE `sitemap_excludes` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính loại trừ sitemap',
  `type` varchar(50) NOT NULL DEFAULT 'url',
  `value` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tags`
--

CREATE TABLE `tags` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính tag',
  `name` varchar(191) NOT NULL COMMENT 'Tên tag',
  `slug` varchar(191) NOT NULL COMMENT 'Slug tag',
  `description` text DEFAULT NULL COMMENT 'Mô tả tag',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Kích hoạt',
  `usage_count` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số lần dùng',
  `entity_id` bigint(20) UNSIGNED NOT NULL COMMENT 'ID của entity',
  `entity_type` varchar(191) NOT NULL COMMENT 'Loại entity: product/post/...',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `description`, `is_active`, `usage_count`, `entity_id`, `entity_type`, `created_at`, `updated_at`) VALUES
(1, 'cây phát tài', 'cay-phat-tai-product-1', NULL, 1, 0, 1, 'App\\Models\\Product', '2025-12-05 07:43:56', '2025-12-05 07:43:56'),
(2, 'cây phong thủy', 'cay-phong-thuy-product-1', NULL, 1, 0, 1, 'App\\Models\\Product', '2025-12-05 07:43:56', '2025-12-05 07:43:56'),
(3, 'cây phát lộc', 'cay-phat-loc-product-1', NULL, 1, 0, 1, 'App\\Models\\Product', '2025-12-05 07:43:56', '2025-12-05 07:43:56'),
(4, 'cây phát tài núi', 'cay-phat-tai-nui-product-1', NULL, 1, 0, 1, 'App\\Models\\Product', '2025-12-05 07:43:56', '2025-12-05 07:43:56'),
(5, 'cây phát tài đỏ', 'cay-phat-tai-do-product-1', NULL, 1, 0, 1, 'App\\Models\\Product', '2025-12-05 07:43:56', '2025-12-05 07:43:56'),
(6, 'cây búp sen', 'cay-bup-sen-product-1', NULL, 1, 0, 1, 'App\\Models\\Product', '2025-12-05 07:43:56', '2025-12-05 07:43:56'),
(7, 'thiết mộc lan', 'thiet-moc-lan-product-1', NULL, 1, 0, 1, 'App\\Models\\Product', '2025-12-05 07:43:56', '2025-12-05 07:43:56'),
(8, 'cây kim tiền', 'cay-kim-tien-product-1', NULL, 1, 0, 1, 'App\\Models\\Product', '2025-12-05 07:43:56', '2025-12-05 07:43:56'),
(9, 'cây cảnh trong nhà', 'cay-canh-trong-nha-product-1', NULL, 1, 0, 1, 'App\\Models\\Product', '2025-12-05 07:43:56', '2025-12-05 07:43:56'),
(10, 'cây hợp mệnh', 'cay-hop-menh-product-1', NULL, 1, 0, 1, 'App\\Models\\Product', '2025-12-05 07:43:56', '2025-12-05 07:43:56'),
(11, 'cây phát tài', 'cay-phat-tai', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-05 09:56:13', '2025-12-05 09:56:13'),
(12, 'cây phong thủy', 'cay-phong-thuy', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-05 09:56:13', '2025-12-05 09:56:13'),
(13, 'cây phát lộc', 'cay-phat-loc', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-05 09:56:13', '2025-12-05 09:56:13'),
(14, 'cây phát tài núi', 'cay-phat-tai-nui', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-05 09:56:13', '2025-12-05 09:56:13'),
(15, 'cây phát tài đỏ', 'cay-phat-tai-do', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-05 09:56:13', '2025-12-05 09:56:13'),
(16, 'cây búp sen', 'cay-bup-sen', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-05 09:56:13', '2025-12-05 09:56:13'),
(17, 'thiết mộc lan', 'thiet-moc-lan', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-05 09:56:13', '2025-12-05 09:56:13'),
(18, 'cây kim tiền', 'cay-kim-tien', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-05 09:56:13', '2025-12-05 09:56:13'),
(19, 'cây cảnh trong nhà', 'cay-canh-trong-nha', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-05 09:56:13', '2025-12-05 09:56:13'),
(20, 'cây hợp mệnh', 'cay-hop-menh', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-05 09:56:13', '2025-12-05 09:56:13'),
(21, 'phong thủy', 'phong-thuy', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-06 03:27:29', '2025-12-06 03:27:29'),
(22, 'cây xanh văn phòng', 'cay-xanh-van-phong', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-06 03:27:29', '2025-12-06 03:27:29'),
(23, 'cây để bàn', 'cay-de-ban', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-06 03:27:29', '2025-12-06 03:27:29'),
(24, 'tài lộc', 'tai-loc', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-06 03:27:29', '2025-12-06 03:27:29'),
(25, 'thịnh vượng', 'thinh-vuong', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-06 03:27:29', '2025-12-06 03:27:29'),
(26, 'cây lưỡi hổ', 'cay-luoi-ho', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(27, 'cây lọc không khí', 'cay-loc-khong-khi', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(28, 'cây trong nhà', 'cay-trong-nha', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(29, 'bảo vệ sức khỏe', 'bao-ve-suc-khoe', NULL, 1, 0, 0, 'App\\Models\\Product', '2025-12-06 04:26:56', '2025-12-06 04:26:56'),
(30, 'cây lộc vừng', 'cay-loc-vung-product-4', NULL, 1, 0, 4, 'App\\Models\\Product', '2025-12-07 14:34:47', '2025-12-07 14:34:47'),
(31, 'cây công trình', 'cay-cong-trinh-product-4', NULL, 1, 0, 4, 'App\\Models\\Product', '2025-12-07 14:34:47', '2025-12-07 14:34:47'),
(32, 'cây bóng mát', 'cay-bong-mat-product-4', NULL, 1, 0, 4, 'App\\Models\\Product', '2025-12-07 14:34:47', '2025-12-07 14:34:47'),
(33, 'cây phong thủy', 'cay-phong-thuy-product-4', NULL, 1, 0, 4, 'App\\Models\\Product', '2025-12-07 14:34:47', '2025-12-07 14:34:47'),
(34, 'lộc vừng hoa đỏ', 'loc-vung-hoa-do-product-4', NULL, 1, 0, 4, 'App\\Models\\Product', '2025-12-07 14:34:47', '2025-12-07 14:34:47'),
(35, 'cây cảnh quan', 'cay-canh-quan-product-4', NULL, 1, 0, 4, 'App\\Models\\Product', '2025-12-07 14:34:47', '2025-12-07 14:34:47'),
(36, 'cây trồng đô thị', 'cay-trong-do-thi-product-4', NULL, 1, 0, 4, 'App\\Models\\Product', '2025-12-07 14:34:47', '2025-12-07 14:34:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vouchers`
--

CREATE TABLE `vouchers` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính voucher',
  `code` varchar(191) NOT NULL COMMENT 'Mã voucher',
  `name` varchar(191) DEFAULT NULL COMMENT 'Tên voucher',
  `description` text DEFAULT NULL COMMENT 'Mô tả voucher',
  `image` varchar(191) DEFAULT NULL COMMENT 'Ảnh hiển thị',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('percent','fixed','free_shipping') NOT NULL DEFAULT 'percent' COMMENT 'Loại voucher',
  `value` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá trị voucher',
  `max_discount` decimal(10,2) DEFAULT NULL COMMENT 'Giảm tối đa',
  `min_order_value` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Giá trị đơn tối thiểu',
  `usage_limit` int(11) DEFAULT NULL COMMENT 'Giới hạn dùng tối đa',
  `usage_limit_per_user` int(11) DEFAULT NULL COMMENT 'Giới hạn mỗi người',
  `start_time` datetime DEFAULT NULL COMMENT 'Thời gian bắt đầu',
  `end_time` datetime DEFAULT NULL COMMENT 'Thời gian kết thúc',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái kích hoạt',
  `apply_for` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Điều kiện áp dụng (JSON)' CHECK (json_valid(`apply_for`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `name`, `description`, `image`, `account_id`, `updated_by`, `type`, `value`, `max_discount`, `min_order_value`, `usage_limit`, `usage_limit_per_user`, `start_time`, `end_time`, `is_active`, `apply_for`, `created_at`, `updated_at`) VALUES
(1, 'SALE10', 'Giảm 10% đơn hàng', 'Giảm 10% cho tất cả sản phẩm, tối đa 50.000đ.', 'the-gioi-cay-xanh-xworld-sale-10.jpg', NULL, NULL, 'percent', 10.00, 50000.00, 0, 500, 1, '2025-01-01 00:00:00', '2026-01-31 23:59:00', 1, NULL, '2025-11-25 02:50:46', '2025-12-05 04:16:25'),
(2, 'GIAM30K', 'Giảm 30.000đ', 'Giảm trực tiếp 30.000đ cho mọi đơn từ 199.000đ.', 'the-gioi-cay-xanh-giam-gia-30000d.jpg', NULL, NULL, 'fixed', 30000.00, NULL, 199000, 300, 2, '2025-01-01 00:00:00', '2025-12-31 23:59:00', 1, NULL, '2025-11-25 02:50:46', '2025-12-05 04:19:43'),
(3, 'FREESHIP25K', 'Miễn phí vận chuyển', 'Miễn phí vận chuyển tối đa 25.000đ cho đơn từ 99.000đ.', 'the-gioi-cay-xanh-xworld-free-shipping-all-orders.webp', NULL, NULL, 'free_shipping', 0.00, 25000.00, 99000, 1000, 2, '2025-01-01 00:00:00', '2025-12-31 23:59:00', 1, NULL, '2025-11-25 02:50:46', '2025-12-05 04:22:49'),
(4, 'AOSOMI15', 'Giảm 15% Áo sơ mi', 'Giảm 15% cho các sản phẩm thuộc danh mục Áo sơ mi.', NULL, NULL, NULL, 'percent', 15.00, 60000.00, 0, 200, 1, '2025-01-01 00:00:00', '2025-06-30 23:59:59', 0, '{\"type\": \"category\", \"category_ids\": [10]}', '2025-11-25 02:50:46', '2025-12-01 19:08:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `voucher_histories`
--

CREATE TABLE `voucher_histories` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính lịch sử dùng voucher',
  `voucher_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền giảm',
  `ip` varchar(191) DEFAULT NULL COMMENT 'IP dùng voucher',
  `session_id` varchar(191) DEFAULT NULL COMMENT 'Session khách',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `voucher_user_usages`
--

CREATE TABLE `voucher_user_usages` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Khóa chính thống kê sử dụng voucher',
  `voucher_id` bigint(20) UNSIGNED NOT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` varchar(191) DEFAULT NULL COMMENT 'Session khách vãng lai',
  `usage_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lần dùng',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `accounts_email_unique` (`email`);

--
-- Chỉ mục cho bảng `account_email_verifications`
--
ALTER TABLE `account_email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_email_verifications_token_unique` (`token`),
  ADD KEY `account_email_verifications_account_id_foreign` (`account_id`);

--
-- Chỉ mục cho bảng `account_logs`
--
ALTER TABLE `account_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_logs_account_id_index` (`account_id`),
  ADD KEY `account_logs_admin_id_index` (`admin_id`);

--
-- Chỉ mục cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_model_type_model_id_index` (`model_type`,`model_id`),
  ADD KEY `activity_logs_account_id_index` (`account_id`),
  ADD KEY `activity_logs_action_index` (`action`),
  ADD KEY `activity_logs_created_at_index` (`created_at`);

--
-- Chỉ mục cho bảng `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `addresses_account_id_index` (`account_id`);

--
-- Chỉ mục cho bảng `address_audits`
--
ALTER TABLE `address_audits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `address_audits_address_id_index` (`address_id`),
  ADD KEY `address_audits_performed_by_index` (`performed_by`);

--
-- Chỉ mục cho bảng `affiliates`
--
ALTER TABLE `affiliates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `affiliates_code_unique` (`code`),
  ADD KEY `affiliates_account_id_foreign` (`account_id`);

--
-- Chỉ mục cho bảng `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `banners_start_at_index` (`start_at`),
  ADD KEY `banners_end_at_index` (`end_at`);

--
-- Chỉ mục cho bảng `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Chỉ mục cho bảng `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Chỉ mục cho bảng `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carts_product_id_foreign` (`product_id`),
  ADD KEY `carts_account_id_index` (`account_id`),
  ADD KEY `carts_session_id_index` (`session_id`);

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_items_cart_id_index` (`cart_id`),
  ADD KEY `cart_items_product_id_index` (`product_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_parent_id_index` (`parent_id`);

--
-- Chỉ mục cho bảng `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comments_commentable_id_index` (`commentable_id`),
  ADD KEY `comments_commentable_type_index` (`commentable_type`),
  ADD KEY `comments_parent_id_index` (`parent_id`),
  ADD KEY `comments_account_id_index` (`account_id`);

--
-- Chỉ mục cho bảng `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contacts_account_id_foreign` (`account_id`),
  ADD KEY `contacts_status_index` (`status`),
  ADD KEY `contacts_source_index` (`source`),
  ADD KEY `contacts_is_read_index` (`is_read`),
  ADD KEY `contacts_created_at_index` (`created_at`);

--
-- Chỉ mục cho bảng `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_replies_contact_id_index` (`contact_id`),
  ADD KEY `contact_replies_account_id_index` (`account_id`);

--
-- Chỉ mục cho bảng `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emails_email_unique` (`email`);

--
-- Chỉ mục cho bảng `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_templates_key_unique` (`key`);

--
-- Chỉ mục cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Chỉ mục cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `favorites_unique_owner` (`product_id`,`account_id`,`session_id`),
  ADD KEY `favorites_account_id_session_id_index` (`account_id`,`session_id`);

--
-- Chỉ mục cho bảng `flash_sales`
--
ALTER TABLE `flash_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flash_sales_created_by_foreign` (`created_by`),
  ADD KEY `flash_sales_status_index` (`status`),
  ADD KEY `flash_sales_start_time_index` (`start_time`),
  ADD KEY `flash_sales_end_time_index` (`end_time`),
  ADD KEY `flash_sales_status_is_active_start_time_end_time_index` (`status`,`is_active`,`start_time`,`end_time`);

--
-- Chỉ mục cho bảng `flash_sale_items`
--
ALTER TABLE `flash_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flash_sale_items_product_id_index` (`product_id`),
  ADD KEY `flash_sale_items_flash_sale_id_index` (`flash_sale_id`);

--
-- Chỉ mục cho bảng `flash_sale_price_logs`
--
ALTER TABLE `flash_sale_price_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flash_sale_price_logs_item_id_index` (`flash_sale_item_id`),
  ADD KEY `flash_sale_price_logs_changed_by_index` (`changed_by`),
  ADD KEY `flash_sale_price_logs_changed_at_index` (`changed_at`);

--
-- Chỉ mục cho bảng `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_movements_account_id_foreign` (`account_id`),
  ADD KEY `inventory_movements_product_id_created_at_index` (`product_id`,`created_at`),
  ADD KEY `inventory_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`);

--
-- Chỉ mục cho bảng `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_reserved_at_index` (`queue`,`reserved_at`);

--
-- Chỉ mục cho bảng `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `newsletters`
--
ALTER TABLE `newsletters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `newsletters_email_unique` (`email`),
  ADD UNIQUE KEY `newsletters_verify_token_unique` (`verify_token`),
  ADD KEY `newsletters_status_index` (`status`),
  ADD KEY `newsletters_source_index` (`source`),
  ADD KEY `newsletters_created_at_index` (`created_at`);

--
-- Chỉ mục cho bảng `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `newsletter_campaigns_created_by_index` (`created_by`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_account_id_is_read_index` (`account_id`,`is_read`),
  ADD KEY `notifications_type_created_at_index` (`type`,`created_at`),
  ADD KEY `notifications_created_at_index` (`created_at`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_code_unique` (`code`),
  ADD KEY `orders_shipping_address_id_foreign` (`shipping_address_id`),
  ADD KEY `orders_billing_address_id_foreign` (`billing_address_id`),
  ADD KEY `orders_voucher_id_foreign` (`voucher_id`),
  ADD KEY `orders_account_id_index` (`account_id`),
  ADD KEY `orders_session_id_index` (`session_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_product_id_foreign` (`product_id`),
  ADD KEY `order_items_order_id_index` (`order_id`);

--
-- Chỉ mục cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_order_id_foreign` (`order_id`),
  ADD KEY `payments_account_id_foreign` (`account_id`);

--
-- Chỉ mục cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Chỉ mục cho bảng `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `posts_slug_unique` (`slug`),
  ADD KEY `posts_account_id_foreign` (`account_id`),
  ADD KEY `posts_category_id_foreign` (`category_id`),
  ADD KEY `posts_created_by_foreign` (`created_by`);

--
-- Chỉ mục cho bảng `post_revisions`
--
ALTER TABLE `post_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_revisions_post_id_foreign` (`post_id`),
  ADD KEY `post_revisions_edited_by_foreign` (`edited_by`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_slug_unique` (`slug`),
  ADD UNIQUE KEY `products_sku_unique` (`sku`),
  ADD KEY `products_created_by_index` (`created_by`),
  ADD KEY `products_locked_by_foreign` (`locked_by`),
  ADD KEY `products_primary_category_id_index` (`primary_category_id`);

--
-- Chỉ mục cho bảng `product_faqs`
--
ALTER TABLE `product_faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_faqs_product_id_foreign` (`product_id`);

--
-- Chỉ mục cho bảng `product_how_tos`
--
ALTER TABLE `product_how_tos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_how_tos_product_id_foreign` (`product_id`);

--
-- Chỉ mục cho bảng `product_views`
--
ALTER TABLE `product_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_views_product_id_viewed_at_index` (`product_id`,`viewed_at`),
  ADD KEY `product_views_account_id_viewed_at_index` (`account_id`,`viewed_at`),
  ADD KEY `product_views_session_id_viewed_at_index` (`session_id`,`viewed_at`),
  ADD KEY `product_views_viewed_at_index` (`viewed_at`);

--
-- Chỉ mục cho bảng `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `profiles_account_id_unique` (`account_id`);

--
-- Chỉ mục cho bảng `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_account_id_index` (`account_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Chỉ mục cho bảng `sitemap_configs`
--
ALTER TABLE `sitemap_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sitemap_configs_config_key_unique` (`config_key`);

--
-- Chỉ mục cho bảng `sitemap_excludes`
--
ALTER TABLE `sitemap_excludes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sitemap_excludes_type_value_unique` (`type`,`value`);

--
-- Chỉ mục cho bảng `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tags_slug_unique` (`slug`),
  ADD KEY `tags_entity_index` (`entity_id`,`entity_type`);

--
-- Chỉ mục cho bảng `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vouchers_code_unique` (`code`),
  ADD KEY `vouchers_updated_by_foreign` (`updated_by`),
  ADD KEY `vouchers_account_id_index` (`account_id`);

--
-- Chỉ mục cho bảng `voucher_histories`
--
ALTER TABLE `voucher_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_histories_voucher_id_index` (`voucher_id`),
  ADD KEY `voucher_histories_order_id_index` (`order_id`),
  ADD KEY `voucher_histories_account_id_index` (`account_id`);

--
-- Chỉ mục cho bảng `voucher_user_usages`
--
ALTER TABLE `voucher_user_usages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_user_usages_unique` (`voucher_id`,`account_id`,`session_id`),
  ADD KEY `voucher_user_usages_account_id_foreign` (`account_id`),
  ADD KEY `voucher_user_usages_voucher_id_index` (`voucher_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính tự tăng của tài khoản', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `account_email_verifications`
--
ALTER TABLE `account_email_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính của phiên xác thực email';

--
-- AUTO_INCREMENT cho bảng `account_logs`
--
ALTER TABLE `account_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính log';

--
-- AUTO_INCREMENT cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính địa chỉ';

--
-- AUTO_INCREMENT cho bảng `address_audits`
--
ALTER TABLE `address_audits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `affiliates`
--
ALTER TABLE `affiliates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính affiliate';

--
-- AUTO_INCREMENT cho bảng `banners`
--
ALTER TABLE `banners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính banner', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính giỏ hàng', AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính mục giỏ hàng', AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính danh mục', AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT cho bảng `comments`
--
ALTER TABLE `comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính bình luận', AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính liên hệ';

--
-- AUTO_INCREMENT cho bảng `contact_replies`
--
ALTER TABLE `contact_replies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `emails`
--
ALTER TABLE `emails`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính tài khoản email gửi đi';

--
-- AUTO_INCREMENT cho bảng `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính yêu thích', AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `flash_sales`
--
ALTER TABLE `flash_sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính chương trình flash sale';

--
-- AUTO_INCREMENT cho bảng `flash_sale_items`
--
ALTER TABLE `flash_sale_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính mục flash sale';

--
-- AUTO_INCREMENT cho bảng `flash_sale_price_logs`
--
ALTER TABLE `flash_sale_price_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính';

--
-- AUTO_INCREMENT cho bảng `images`
--
ALTER TABLE `images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính job queue';

--
-- AUTO_INCREMENT cho bảng `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT cho bảng `newsletters`
--
ALTER TABLE `newsletters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính đăng ký nhận tin', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính thông báo', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính đơn hàng';

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính sản phẩm trong đơn';

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính token cá nhân';

--
-- AUTO_INCREMENT cho bảng `posts`
--
ALTER TABLE `posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính bài viết';

--
-- AUTO_INCREMENT cho bảng `post_revisions`
--
ALTER TABLE `post_revisions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính bản nháp bài viết';

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính sản phẩm', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `product_faqs`
--
ALTER TABLE `product_faqs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính FAQ sản phẩm', AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT cho bảng `product_how_tos`
--
ALTER TABLE `product_how_tos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính hướng dẫn sử dụng', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `product_views`
--
ALTER TABLE `product_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT cho bảng `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính hồ sơ tài khoản';

--
-- AUTO_INCREMENT cho bảng `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính cấu hình', AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT cho bảng `sitemap_configs`
--
ALTER TABLE `sitemap_configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính cấu hình sitemap', AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `sitemap_excludes`
--
ALTER TABLE `sitemap_excludes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính loại trừ sitemap';

--
-- AUTO_INCREMENT cho bảng `tags`
--
ALTER TABLE `tags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính tag', AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính voucher', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `voucher_histories`
--
ALTER TABLE `voucher_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính lịch sử dùng voucher';

--
-- AUTO_INCREMENT cho bảng `voucher_user_usages`
--
ALTER TABLE `voucher_user_usages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính thống kê sử dụng voucher';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
