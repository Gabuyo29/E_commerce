-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2025 at 06:25 PM
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
-- Database: `e_commerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled','processing') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `total_price`, `status`, `created_at`) VALUES
(61, 11, 2990.00, 'delivered', '2025-06-18 18:58:40'),
(62, 11, 2990.00, 'delivered', '2025-06-18 19:00:04'),
(63, 11, 999.00, 'delivered', '2025-06-19 02:24:39');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `size`, `quantity`, `price`) VALUES
(65, 61, 74, 'S', 1, 2990.00),
(66, 62, 74, 'S', 1, 2990.00),
(67, 63, 53, 'S', 1, 999.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `category` varchar(50) NOT NULL,
  `sizes` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `description`, `price`, `image`, `stock`, `category`, `sizes`, `username`, `user_id`, `active`) VALUES
(53, 'COOLMAX® Regular Fit T-shirt', 'T-shirt in heavyweight jersey with a round, rib-trimmed neckline and a straight-cut hem. Regular fit for comfortable wear and a classic silhouette. The T-shirt incorporates COOLMAX® functional fabric, a unique, soft, comfortable and fast-drying polyester fibre that efficiently wicks moisture while regulating temperature.', 999.00, 'uploads/RegularFit.avif', 99, 'Mens_Wear', 'S', 'ADMIN', 10, 0),
(54, 'Regular Fit Waffled T-shirt', 'Regular-fit T-shirt in medium-weight waffled jersey with a comfortable, classic silhouette. Ribbed crew neck and a straight-cut hem.', 899.00, 'uploads/RegularFitWaffled.avif', 97, 'Mens_Wear', 'M', 'ADMIN', 10, 1),
(56, 'Regular-Fit Textured Resort Shirt', 'Regular-fit shirt in textured, woven cotton with a comfortable, classic silhouette. Resort collar, buttons without placket, a chest pocket, and short sleeves. Straight-cut hem.', 1790.00, 'uploads/FitTextured.avif', 97, 'Mens_Wear', 'M', 'ADMIN', 10, 1),
(57, 'Loose-Fit Waffled Polo Shirt', 'Loose-fit, short-sleeved polo shirt in a soft, waffled knit with a generous but not oversized silhouette. Collar, V-shaped opening at front, and a straight hem.', 1490.00, 'uploads/LooseWafflePolo.avif', 95, 'Mens_Wear', 'L', 'ADMIN', 10, 1),
(58, 'COOLMAX® Regular Fit T-shirt', 'T-shirt in heavyweight jersey with a round, rib-trimmed neckline and a straight-cut hem. Regular fit for comfortable wear and a classic silhouette. The T-shirt incorporates COOLMAX® functional fabric, a unique, soft, comfortable and fast-drying polyester fibre that efficiently wicks moisture while regulating temperature.', 999.00, 'uploads/RegularFit.avif', 96, 'Mens_Wear', 'XS', 'ADMIN', 10, 1),
(59, 'COOLMAX® Regular Fit T-shirt', 'T-shirt in heavyweight jersey with a round, rib-trimmed neckline and a straight-cut hem. Regular fit for comfortable wear and a classic silhouette. The T-shirt incorporates COOLMAX® functional fabric, a unique, soft, comfortable and fast-drying polyester fibre that efficiently wicks moisture while regulating temperature.', 999.00, 'uploads/RegularFit.avif', 100, 'Mens_Wear', 'M', 'ADMIN', 10, 1),
(60, 'COOLMAX® Regular Fit T-shirt', 'T-shirt in heavyweight jersey with a round, rib-trimmed neckline and a straight-cut hem. Regular fit for comfortable wear and a classic silhouette. The T-shirt incorporates COOLMAX® functional fabric, a unique, soft, comfortable and fast-drying polyester fibre that efficiently wicks moisture while regulating temperature.', 999.00, 'uploads/RegularFit.avif', 99, 'Mens_Wear', 'XL', 'ADMIN', 10, 1),
(61, 'COOLMAX® Regular Fit T-shirt', 'T-shirt in heavyweight jersey with a round, rib-trimmed neckline and a straight-cut hem. Regular fit for comfortable wear and a classic silhouette. The T-shirt incorporates COOLMAX® functional fabric, a unique, soft, comfortable and fast-drying polyester fibre that efficiently wicks moisture while regulating temperature.', 999.00, 'uploads/RegularFit.avif', 100, 'Mens_Wear', 'L', 'ADMIN', 10, 1),
(62, 'Short Blouse', 'Short blouse in crisp cotton poplin. Elasticized, picot-trimmed square neckline, tapered section at waist with pleats at front and narrow elastic, and short sleeves with elasticized cuffs.', 899.00, 'uploads/shortblouse.avif', 78, 'Womens_Wear', 'XS', 'ADMIN', 10, 1),
(63, 'Oversized Fit T-Shirt', 'Oversized, boxy T-shirt in heavyweight cotton jersey with a baggy, extra-loose silhouette. Ribbed crew neck, dropped shoulders, yoke at back, and a rolled, raw-edge hem.', 1490.00, 'uploads/OversizedFit.avif', 98, 'Mens_Wear', 'L', 'ADMIN', 10, 1),
(64, 'Ruffle-Trimmed Strappy Top', 'Short top in woven fabric with gathers and ruffles. Adjustable, extra-narrow shoulder straps and a V-shaped neckline at front with narrow drawstring.', 999.00, 'uploads/Ruffle-Trimmed.avif', 100, 'Womens_Wear', 'XS', 'ADMIN', 10, 1),
(65, 'Eyelet-Embroidered Dress with Tie Shoulder Straps', 'Calf-length, A-line dress in an airy, woven linen and viscose blend. Extra-narrow, tie-top shoulder straps, V-neck at front, and eyelet-embroidered section around upper edge. Gathers at front and back, and a narrow, vertical lace inset at front. Unlined.', 2990.00, 'uploads/Ruffle-Trimmed.avif', 100, 'Womens_Wear', 'M', 'ADMIN', 10, 1),
(66, 'Tiered Tie-Strap Dress', 'Long, loose-fit dress in woven cotton with gathered tiers and a generous A-line silhouette. Extra-narrow, tie-top shoulder straps with decorative charm at ends. Unlined.', 4990.00, 'uploads/TieredTie.avif', 100, 'Womens_Wear', 'M', 'ADMIN', 10, 1),
(67, 'Trumpet-Sleeved Tunic Dress', 'Short, loose-fit, A-line dress in airy, woven fabric with delicate eyelash-stitched edges. Ruffled neckline with an extra-narrow drawstring with gold-colored pendants, and a V-shaped opening at front. Extra-narrow removable tie belt at waist and long raglan trumpet sleeves with ruffle trim above cuffs. Unlined.', 2990.00, 'uploads/Trumpet.avif', 100, 'Womens_Wear', 'M', 'ADMIN', 10, 1),
(68, 'Oversized Dobby-Weave Shirt', 'Oversized shirt in dobby-weave fabric. Collar, buttons at front, and a yoke at back. Dropped shoulders and long sleeves with button at cuffs. Gently rounded hem.', 2990.00, 'uploads/Dobby.avif', 100, 'Womens_Wear', 'S', 'ADMIN', 10, 1),
(69, 'Belted Twill Shorts', 'Loose-fit shorts in viscose-blend twill. High waist, a wide braided belt, and zip fly with concealed button and hook-and-bar fastener. Diagonal side pockets, a welt back pocket, and pleats at front for added volume.', 1590.00, 'uploads/belted.avif', 100, 'Womens_Wear', 'S', 'ADMIN', 10, 1),
(70, 'Embroidered Twill Shorts', 'Loose-fit shorts in twill. High waist, wide, removable tie belt, and a concealed zipper at one side. Scalloped-edge hems with contrasting embroidery and eyelet embroidery.', 2490.00, 'uploads/Embroidered.avif', 100, 'Womens_Wear', 'L', 'ADMIN', 10, 1),
(71, 'Oversized-Fit Long-Sleeved Jersey Shirt', 'Boxy, oversized shirt in medium-weight cotton jersey with a printed design and an extra-loose silhouette. Round, ribbed neck, dropped shoulders, and long sleeves. Straight-cut hem.', 1790.00, 'uploads/JerseyShirt.avif', 100, 'Womens_Wear', 'S', 'ADMIN', 10, 1),
(72, 'Oversized-Fit Short-Sleeved Linen-Blend Shirt', 'Oversized, short-sleeved shirt in an airy cotton and linen blend with an extra-loose silhouette. Turn-down collar, classic button placket, an open chest pocket, and a straight-cut hem. Cotton and linen blends combine the softness of cotton with the sturdiness of linen, creating a beautiful, textured fabric that is breathable and perfectly draped.', 1790.00, 'uploads/Blend.avif', 100, 'Mens_Wear', 'M', 'ADMIN', 10, 1),
(73, 'Oversized-Fit Cropped T-Shirt', 'Boxy, oversized crop T-shirt in cotton sweatshirt fabric with a printed design. Extra-loose silhouette. Round, ribbed neck, dropped shoulders, and a raw edges at cuffs and hem.', 1790.00, 'uploads/Cropped.avif', 100, 'Mens_Wear', 'M', 'ADMIN', 10, 1),
(74, 'Loose-Fit Parachute Pants', 'Loose-fit parachute pants in woven cotton with a generous but not oversized silhouette. Drawstring and covered elastic at waistband and a zip fly with button. Side pockets with flap and button, back pocket with flap and button, and cargo leg pockets with hook-loop fasteners. Stitched pleats at knees and concealed drawstring at hems.', 2990.00, 'uploads/parachute.avif', 98, 'Mens_Wear', 'S', 'ADMIN', 10, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_role` enum('admin','customer') NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `created_at`, `user_role`, `address`, `contact_number`) VALUES
(10, 'ADMIN', 'johngabuyo29@gmail.com', '123', '2025-04-23 08:17:59', 'admin', NULL, NULL),
(11, 'john', 'jhnmrc29@gmail.com', '123', '2025-04-29 09:25:46', 'customer', 'Biñan, Laguna', '09384575877'),
(13, 'aldwin', 'aldwinonofre@gmail.com', '$2y$10$hyrbacU1dJIThBw.bybiZu7J.SPlQJLt.fdfxMqp4DA7KcnVQCeDS', '2025-06-18 08:22:54', 'customer', 'Delapaz Foodpark', '09213421523');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
