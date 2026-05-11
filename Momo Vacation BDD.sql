-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql-momo-vacation.alwaysdata.net
-- Generation Time: May 04, 2026 at 02:56 PM
-- Server version: 10.11.15-MariaDB
-- PHP Version: 8.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `momo-vacation_bdd`
--

-- --------------------------------------------------------

--
-- Table structure for table `accommodation`
--

CREATE TABLE `accommodation` (
  `accommodation_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accommodation`
--

INSERT INTO `accommodation` (`accommodation_id`, `type`, `price_per_night`, `rating`, `image_url`, `latitude`, `longitude`, `destination_id`, `created_at`) VALUES
(1, 'Hotels', 850.00, 9.5, 'images/book/hotel/meurice.webp', 48.86480000, 2.32800000, 1, '2026-04-10 13:50:41'),
(2, 'Hotels', 650.00, 9.4, 'images/book/hotel/savoy.webp', 51.51010000, -0.12030000, 2, '2026-04-10 13:50:41'),
(3, 'Apartments', 180.00, 8.8, 'images/book/hotel/soho.webp', 51.51360000, -0.13650000, 2, '2026-04-10 13:50:41'),
(4, 'Hotels', 900.00, 9.1, 'images/book/hotel/plaza.webp', 40.76440000, -73.97440000, 3, '2026-04-10 13:50:41'),
(5, 'Hotels', 1100.00, 9.7, 'images/book/hotel/aman.webp', 35.68850000, 139.76430000, 4, '2026-04-10 13:50:41'),
(6, 'Hotels', 250.00, 9.2, 'images/book/hotel/mena.webp', 29.98450000, 31.13250000, 5, '2026-04-10 13:50:41'),
(7, 'Hotels', 550.00, 9.3, 'images/book/hotel/peninsula.webp', 22.29510000, 114.17180000, 6, '2026-04-10 13:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `accommodation_translation`
--

CREATE TABLE `accommodation_translation` (
  `translation_id` int(11) NOT NULL,
  `accommodation_id` int(11) NOT NULL,
  `language_code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accommodation_translation`
--

INSERT INTO `accommodation_translation` (`translation_id`, `accommodation_id`, `language_code`, `name`, `description`) VALUES
(1, 1, 'en', 'Le Meurice', 'A Dorchester Collection hotel offering timeless elegance, a 2-star Michelin restaurant, and stunning views of the Tuileries Garden.'),
(2, 2, 'en', 'The Savoy', 'A world-famous luxury hotel situated on the banks of the River Thames, blending Edwardian and Art Deco styles.'),
(3, 3, 'en', 'Soho Artist Loft', 'A beautiful, modern open-space apartment in the vibrant heart of Soho. Perfect for short stays.'),
(4, 4, 'en', 'The Plaza', 'Iconic luxury hotel at the corner of Fifth Avenue and Central Park South, offering unparalleled service and history.'),
(5, 5, 'en', 'Aman Tokyo', 'An exclusive sanctuary high above the Japanese capital, featuring minimalist design, a serene spa, and city views.'),
(6, 6, 'en', 'Marriott Mena House', 'Historic hotel offering breathtaking, direct views of the Great Pyramids of Giza right from your balcony.'),
(7, 7, 'en', 'The Peninsula', 'Known as the \"Grande Dame of the Far East\", offering classic luxury, high tea, and sweeping views of Victoria Harbour.'),
(8, 1, 'fr', 'Le Meurice', 'Un hôtel Dorchester Collection offrant une élégance intemporelle, un restaurant 2 étoiles Michelin et une vue imprenable sur le jardin des Tuileries.'),
(9, 2, 'fr', 'The Savoy', 'Un hôtel de luxe de renommée mondiale situé sur les rives de la Tamise, mêlant les styles édouardien et Art déco.'),
(10, 3, 'fr', 'Loft d\'Artiste à Soho', 'Un magnifique appartement moderne et ouvert dans le quartier animé de Soho. Parfait pour de courts séjours.'),
(11, 4, 'fr', 'Le Plaza', 'Hôtel de luxe emblématique à l\'angle de la Cinquième Avenue et de Central Park South, offrant un service et une histoire incomparables.'),
(12, 5, 'fr', 'Aman Tokyo', 'Un sanctuaire exclusif surplombant la capitale japonaise, avec un design minimaliste, un spa serein et une vue panoramique sur la ville.'),
(13, 6, 'fr', 'Marriott Mena House', 'Hôtel historique offrant une vue directe et époustouflante sur les grandes pyramides de Gizeh, directement depuis votre balcon.'),
(14, 7, 'fr', 'The Peninsula', 'Surnommée la \"Grande Dame d\'Extrême-Orient\", elle offre un luxe classique, le thé de l\'après-midi et une vue imprenable sur le port Victoria.');

-- --------------------------------------------------------

--
-- Table structure for table `agency`
--

CREATE TABLE `agency` (
  `agency_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agency`
--

INSERT INTO `agency` (`agency_id`, `user_id`, `company_name`, `description`, `logo_url`, `verified`, `created_at`) VALUES
(1, 12, 'Momo Voyages', 'Organisateur de voyages de luxe depuis 2020. Spécialiste des circuits culturels et des escapades sur mesure.', NULL, 1, '2026-05-04 12:38:35');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `accommodation_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'confirmed',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `user_id`, `accommodation_id`, `check_in`, `check_out`, `total_price`, `status`, `created_at`) VALUES
(1, 4, 7, '2026-04-14', '2026-04-26', 6600.00, 'confirmed', '2026-04-14 10:23:17');

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `comment_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `status` varchar(50) DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `destination`
--

CREATE TABLE `destination` (
  `destination_id` int(11) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destination`
--

INSERT INTO `destination` (`destination_id`, `latitude`, `longitude`, `created_at`, `image_url`) VALUES
(1, 48.85660000, 2.35220000, '2026-04-10 13:50:41', 'images/destinations/Paris/Paris.webp'),
(2, 51.50740000, -0.12780000, '2026-04-10 13:50:41', 'images/destinations/London/London.webp'),
(3, 40.71280000, -74.00600000, '2026-04-10 13:50:41', 'images/destinations/New_York/New_York.webp'),
(4, 35.67620000, 139.65030000, '2026-04-10 13:50:41', 'images/destinations/Tokyo/Tokyo.webp'),
(5, 30.04440000, 31.23570000, '2026-04-10 13:50:41', 'images/destinations/Cairo/Cairo.webp'),
(6, 22.31930000, 114.16940000, '2026-04-10 13:50:41', 'images/destinations/Hongkong/Hongkong.webp');

-- --------------------------------------------------------

--
-- Table structure for table `destination_translation`
--

CREATE TABLE `destination_translation` (
  `translation_id` int(11) NOT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `language_code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destination_translation`
--

INSERT INTO `destination_translation` (`translation_id`, `destination_id`, `language_code`, `name`, `slogan`) VALUES
(1, 1, 'en', 'Paris', 'Want to see la vie en Rose?'),
(2, 1, 'fr', 'Paris', 'Envie de voir la vie en rose ?'),
(3, 2, 'en', 'London', 'Keep calm and visit London'),
(4, 2, 'fr', 'Londres', 'Restez calme et visitez Londres'),
(5, 3, 'en', 'New York', 'Take a bite of the Big Apple'),
(6, 3, 'fr', 'New York', 'Croquez la Grosse Pomme à pleines dents'),
(7, 4, 'en', 'Tokyo', 'Chase the cherry blossoms'),
(8, 4, 'fr', 'Tokyo', 'Partez à la chasse aux cerisiers en fleurs'),
(9, 5, 'en', 'Cairo', 'Walk among the pharaohs'),
(10, 5, 'fr', 'Le Caire', 'Marchez sur les traces des pharaons'),
(11, 6, 'en', 'Hong Kong', 'Wander in the inspired streets'),
(12, 6, 'fr', 'Hong Kong', 'Flânez dans les rues qui ont inspiré les plus grands');

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `starting_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `destination_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `card_last4` char(4) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT current_timestamp(),
  `invoice_number` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'paid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `keyword`
--

CREATE TABLE `keyword` (
  `keyword_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `keyword_translation`
--

CREATE TABLE `keyword_translation` (
  `translation_id` int(11) NOT NULL,
  `keyword_id` int(11) DEFAULT NULL,
  `language_code` varchar(10) NOT NULL,
  `wording` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE `post` (
  `post_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `text` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `postimage`
--

CREATE TABLE `postimage` (
  `postimage_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `post_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_has_keyword`
--

CREATE TABLE `post_has_keyword` (
  `post_id` int(11) NOT NULL,
  `keyword_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `private_message`
--

CREATE TABLE `private_message` (
  `private_message_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `rating` int(11) DEFAULT 5,
  `status` varchar(50) DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review`
--

INSERT INTO `review` (`review_id`, `user_id`, `title`, `comment`, `rating`, `status`, `created_at`) VALUES
(1, 5, 'Un séjour magique à Paris', 'Le processus de réservation a été incroyablement fluide. L\'hôtel Le Meurice était exactement comme décrit sur la plateforme, avec une vue imprenable. Je recommande vivement Momo Travel pour vos escapades !', 5, 'published', '2026-04-14 10:37:32'),
(2, 6, 'Seamless booking for Tokyo', 'I booked the Aman Tokyo through Momo Travel and the whole experience was flawless. The platform is beautiful, easy to navigate, and the secure payment gave me total peace of mind. 10/10.', 5, 'published', '2026-04-14 10:37:32'),
(3, 7, 'Parfait pour un week-end londonien', 'Plateforme très agréable à utiliser avec de très bons filtres de recherche. Nous avons pu sécuriser nos dates pour le Savoy en quelques clics. Un petit bémol sur la météo à Londres, mais Momo Travel a fait un sans faute !', 4, 'published', '2026-04-14 10:37:32');

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

CREATE TABLE `ticket` (
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) DEFAULT 'open',
  `admin_reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_booking`
--

CREATE TABLE `trip_booking` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_id` int(11) NOT NULL,
  `num_participants` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'confirmed',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_date`
--

CREATE TABLE `trip_date` (
  `date_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `departure_date` date NOT NULL,
  `return_date` date NOT NULL,
  `duration_nights` int(11) NOT NULL,
  `price_per_person` decimal(10,2) NOT NULL,
  `max_spots` int(11) NOT NULL DEFAULT 20,
  `booked_spots` int(11) NOT NULL DEFAULT 0,
  `status` varchar(50) DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_date`
--

INSERT INTO `trip_date` (`date_id`, `package_id`, `departure_date`, `return_date`, `duration_nights`, `price_per_person`, `max_spots`, `booked_spots`, `status`, `created_at`) VALUES
(1, 1, '2026-06-10', '2026-06-15', 5, 1200.00, 20, 3, 'available', '2026-05-04 12:42:43'),
(2, 1, '2026-07-05', '2026-07-10', 5, 1350.00, 20, 8, 'available', '2026-05-04 12:42:43'),
(3, 1, '2026-08-15', '2026-08-20', 5, 1450.00, 15, 0, 'available', '2026-05-04 12:42:43'),
(4, 2, '2026-09-05', '2026-09-12', 7, 2500.00, 12, 2, 'available', '2026-05-04 12:42:43'),
(5, 2, '2026-10-10', '2026-10-17', 7, 2800.00, 12, 0, 'available', '2026-05-04 12:42:43'),
(6, 2, '2026-11-01', '2026-11-08', 7, 2600.00, 10, 5, 'available', '2026-05-04 12:42:43'),
(7, 3, '2026-06-20', '2026-06-23', 3, 980.00, 25, 5, 'available', '2026-05-04 12:42:43'),
(8, 3, '2026-07-18', '2026-07-21', 3, 1100.00, 25, 12, 'available', '2026-05-04 12:42:43'),
(9, 3, '2026-09-25', '2026-09-28', 3, 950.00, 20, 0, 'available', '2026-05-04 12:42:43');

-- --------------------------------------------------------

--
-- Table structure for table `trip_inclusion`
--

CREATE TABLE `trip_inclusion` (
  `inclusion_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'hotel, transport, activity',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_inclusion`
--

INSERT INTO `trip_inclusion` (`inclusion_id`, `package_id`, `type`, `name`, `description`, `max_participants`, `sort_order`) VALUES
(1, 1, 'transport', 'Vol A/R Paris CDG', 'Billet inclus, bagage 23 kg inclus', NULL, 1),
(2, 1, 'hotel', 'Le Meurice 4★', 'Chambre double, petit-déjeuner inclus chaque matin', NULL, 2),
(3, 1, 'activity', 'Visite guidée du Louvre', 'Visite privée de 3h avec un expert en histoire de l\'art', 15, 3),
(4, 1, 'activity', 'Croisière dîner sur la Seine', 'Dîner gastronomique 3 plats à bord d\'une péniche', 20, 4),
(5, 2, 'transport', 'Vol A/R Paris-Tokyo', 'Classe affaires, 2 bagages 23 kg inclus', NULL, 1),
(6, 2, 'transport', 'Pass JR Rail 7 jours', 'Shinkansen + transports locaux JR illimités', NULL, 2),
(7, 2, 'hotel', 'Aman Tokyo', 'Suite panoramique, accès spa et piscine inclus', NULL, 3),
(8, 2, 'activity', 'Cérémonie du thé traditionnelle', 'Expérience authentique dans un salon de thé du XVIe siècle', 8, 4),
(9, 2, 'activity', 'Masterclass sushi avec chef étoilé', 'Apprenez l\'art du sushi avec un chef Michelin', 10, 5),
(10, 3, 'transport', 'Eurostar Paris-Londres A/R', 'Standard Premier, 1 bagage inclus', NULL, 1),
(11, 3, 'hotel', 'The Savoy', 'Chambre classique, accès au Beaufort Bar inclus', NULL, 2),
(12, 3, 'activity', 'Visite guidée de la Tour de Londres', 'Guide expert, accès aux Joyaux de la Couronne', 20, 3),
(13, 3, 'activity', 'Comédie musicale West End', 'Billet catégorie A pour un spectacle en cours', 25, 4);

-- --------------------------------------------------------

--
-- Table structure for table `trip_package`
--

CREATE TABLE `trip_package` (
  `package_id` int(11) NOT NULL,
  `agency_id` int(11) NOT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_package`
--

INSERT INTO `trip_package` (`package_id`, `agency_id`, `destination_id`, `image_url`, `base_price`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'images/destinations/Paris/Paris.webp', 1200.00, 'published', '2026-05-04 12:42:43', '2026-05-04 12:42:43'),
(2, 1, 4, 'images/destinations/Tokyo/Tokyo.webp', 2500.00, 'published', '2026-05-04 12:42:43', '2026-05-04 12:42:43'),
(3, 1, 2, 'images/destinations/Londres/London.webp', 980.00, 'published', '2026-05-04 12:42:43', '2026-05-04 12:42:43');

-- --------------------------------------------------------

--
-- Table structure for table `trip_package_translation`
--

CREATE TABLE `trip_package_translation` (
  `translation_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `language_code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `highlights` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_package_translation`
--

INSERT INTO `trip_package_translation` (`translation_id`, `package_id`, `language_code`, `name`, `description`, `highlights`) VALUES
(1, 1, 'en', 'Paris Romantic Escape', 'An unforgettable 5-night trip to the City of Light, blending iconic landmarks, gourmet dining, and luxury accommodation.', 'Eiffel Tower • Louvre Museum • Seine Dinner Cruise • 4★ Hotel'),
(2, 1, 'fr', 'Escapade Romantique à Paris', 'Un voyage inoubliable de 5 nuits dans la Ville Lumière, mêlant monuments emblématiques, gastronomie et hébergement de luxe.', 'Tour Eiffel • Musée du Louvre • Croisière Dîner • Hôtel 4★'),
(3, 2, 'en', 'Tokyo Zen Discovery', 'A 7-night immersion into Japanese culture and modern life, from bustling Shibuya to serene Nikko temples.', 'Aman Tokyo • Shinkansen Pass • Tea Ceremony • Sushi Masterclass'),
(4, 2, 'fr', 'Découverte Zen de Tokyo', 'Une immersion de 7 nuits dans la culture japonaise, de Shibuya animé aux temples sereins de Nikko.', 'Aman Tokyo • Pass Shinkansen • Cérémonie du Thé • Masterclass Sushi'),
(5, 3, 'en', 'London Classic Weekend', 'A premium 3-night escape to the heart of London, combining history, culture and unmissable entertainment.', 'The Savoy • Tower of London • Thames Cruise • West End Show'),
(6, 3, 'fr', 'Week-end Classique à Londres', 'Un séjour premium de 3 nuits au cœur de Londres, alliant histoire, culture et divertissements incontournables.', 'The Savoy • Tour de Londres • Croisière Tamise • Comédie Musicale');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user',
  `dark_theme` tinyint(1) DEFAULT 0,
  `consent_card` tinyint(1) DEFAULT 0,
  `language` varchar(10) DEFAULT 'fr',
  `activate_notification` tinyint(1) DEFAULT 1,
  `account_status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_code` varchar(10) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `name`, `email`, `phone_number`, `password`, `profile_picture`, `role`, `dark_theme`, `consent_card`, `language`, `activate_notification`, `account_status`, `created_at`, `updated_at`, `stripe_customer_id`, `is_verified`, `verification_code`, `reset_token`, `reset_expires`) VALUES
(4, 'Larry', 'larry@gmail.com', '', '$2y$12$5SU.eREv.tOmA.xoSkpWiekpXDx9c4LW4l6gtFFPYENgMychYR10y', 'images/profile/larry_4/image.webp', 'user', 0, 0, 'fr', 0, 'active', '2026-04-10 12:02:12', '2026-04-24 07:11:33', NULL, 1, NULL, 'a17986a76403506e1db4c1083489dd240b837cc3cc299cf6004b479f06ca9d3d', '2026-04-24 10:11:33'),
(5, 'Alice Dupont', 'alice.d@example.com', '', '$2y$12$5SU.eREv.tOmB.xoSljhfDx9c4LW4l6gtFFPYENgMychYR10y', NULL, 'user', 0, 0, 'fr', 1, 'active', '2026-04-14 10:37:32', '2026-04-21 13:28:17', NULL, 1, NULL, NULL, NULL),
(6, 'Marcus Johnson', 'marcus.j@example.com', '', '$2y$12$5SU.eREv.tOmB.xoSljhfDx9c4LW4l6glmhsdufhpeuro', NULL, 'user', 0, 0, 'en', 1, 'active', '2026-04-14 10:37:32', '2026-04-21 13:28:19', NULL, 1, NULL, NULL, NULL),
(7, 'Sophie Martin', 'sophie.m@example.com', '', '$2y$12$5SU.eREv.tOmB.xosdsssssseH9c4LW4l6glmhsdufhpeuro', NULL, 'user', 0, 0, 'fr', 1, 'active', '2026-04-14 10:37:32', '2026-04-21 13:28:22', NULL, 1, NULL, NULL, NULL),
(8, 'benjamin', 'benjamin.dinon@eleve.isep.fr', '', '$2y$12$lFw65OmTov6CGAcSP/XDSOJ1EOl9vFuXeDBxH0Th.zgWG3ylzI1Lu', NULL, 'user', 0, 0, 'fr', 1, 'active', '2026-04-14 13:29:07', '2026-04-21 13:36:00', NULL, 1, NULL, '8e84c8a205a8d553e5775581d14dfcf487d374dbe38039b78441f65c88a87e03', '2026-04-21 16:36:00'),
(11, 'larry2', 'larry2@gmail.com', '', '$2y$12$iXTbBrkEAJePhUXCjngJduyGXtzwaZX94i5AXUJbq20umw6EreDZO', NULL, 'user', 0, 0, 'fr', 1, 'active', '2026-04-24 07:46:54', '2026-04-24 07:46:54', NULL, 0, NULL, NULL, NULL),
(12, 'Momo Agency Admin', 'agency@momo-travel.com', '+33600000001', '$2y$12$QYH9qZwjodmRlfCanY.I4em9xs6NAO4XxWjqadaIQ0XCkQeNU.Qyy% ', NULL, 'agency', 0, 0, 'fr', 1, 'active', '2026-05-04 12:38:35', '2026-05-04 12:38:35', NULL, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_has_keyword`
--

CREATE TABLE `user_has_keyword` (
  `user_id` int(11) NOT NULL,
  `keyword_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accommodation`
--
ALTER TABLE `accommodation`
  ADD PRIMARY KEY (`accommodation_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `accommodation_translation`
--
ALTER TABLE `accommodation_translation`
  ADD PRIMARY KEY (`translation_id`),
  ADD UNIQUE KEY `accommodation_id` (`accommodation_id`,`language_code`);

--
-- Indexes for table `agency`
--
ALTER TABLE `agency`
  ADD PRIMARY KEY (`agency_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `accommodation_id` (`accommodation_id`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `destination`
--
ALTER TABLE `destination`
  ADD PRIMARY KEY (`destination_id`);

--
-- Indexes for table `destination_translation`
--
ALTER TABLE `destination_translation`
  ADD PRIMARY KEY (`translation_id`),
  ADD UNIQUE KEY `destination_id` (`destination_id`,`language_code`),
  ADD KEY `idx_dest_lang` (`language_code`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `destination_id` (`destination_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `keyword`
--
ALTER TABLE `keyword`
  ADD PRIMARY KEY (`keyword_id`);

--
-- Indexes for table `keyword_translation`
--
ALTER TABLE `keyword_translation`
  ADD PRIMARY KEY (`translation_id`),
  ADD UNIQUE KEY `keyword_id` (`keyword_id`,`language_code`),
  ADD KEY `idx_keyword_lang` (`language_code`);

--
-- Indexes for table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `destination_id` (`destination_id`),
  ADD KEY `idx_post_status` (`status`);

--
-- Indexes for table `postimage`
--
ALTER TABLE `postimage`
  ADD PRIMARY KEY (`postimage_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `post_has_keyword`
--
ALTER TABLE `post_has_keyword`
  ADD PRIMARY KEY (`post_id`,`keyword_id`),
  ADD KEY `keyword_id` (`keyword_id`);

--
-- Indexes for table `private_message`
--
ALTER TABLE `private_message`
  ADD PRIMARY KEY (`private_message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trip_booking`
--
ALTER TABLE `trip_booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `date_id` (`date_id`);

--
-- Indexes for table `trip_date`
--
ALTER TABLE `trip_date`
  ADD PRIMARY KEY (`date_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `trip_inclusion`
--
ALTER TABLE `trip_inclusion`
  ADD PRIMARY KEY (`inclusion_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `trip_package`
--
ALTER TABLE `trip_package`
  ADD PRIMARY KEY (`package_id`),
  ADD KEY `agency_id` (`agency_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `trip_package_translation`
--
ALTER TABLE `trip_package_translation`
  ADD PRIMARY KEY (`translation_id`),
  ADD UNIQUE KEY `package_lang` (`package_id`,`language_code`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_role` (`role`);

--
-- Indexes for table `user_has_keyword`
--
ALTER TABLE `user_has_keyword`
  ADD PRIMARY KEY (`user_id`,`keyword_id`),
  ADD KEY `keyword_id` (`keyword_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accommodation`
--
ALTER TABLE `accommodation`
  MODIFY `accommodation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `accommodation_translation`
--
ALTER TABLE `accommodation_translation`
  MODIFY `translation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `agency`
--
ALTER TABLE `agency`
  MODIFY `agency_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `destination`
--
ALTER TABLE `destination`
  MODIFY `destination_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `destination_translation`
--
ALTER TABLE `destination_translation`
  MODIFY `translation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `keyword`
--
ALTER TABLE `keyword`
  MODIFY `keyword_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `keyword_translation`
--
ALTER TABLE `keyword_translation`
  MODIFY `translation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post`
--
ALTER TABLE `post`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `postimage`
--
ALTER TABLE `postimage`
  MODIFY `postimage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `private_message`
--
ALTER TABLE `private_message`
  MODIFY `private_message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ticket`
--
ALTER TABLE `ticket`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trip_booking`
--
ALTER TABLE `trip_booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trip_date`
--
ALTER TABLE `trip_date`
  MODIFY `date_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `trip_inclusion`
--
ALTER TABLE `trip_inclusion`
  MODIFY `inclusion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `trip_package`
--
ALTER TABLE `trip_package`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `trip_package_translation`
--
ALTER TABLE `trip_package_translation`
  MODIFY `translation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accommodation`
--
ALTER TABLE `accommodation`
  ADD CONSTRAINT `accommodation_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destination` (`destination_id`) ON DELETE SET NULL;

--
-- Constraints for table `accommodation_translation`
--
ALTER TABLE `accommodation_translation`
  ADD CONSTRAINT `accommodation_translation_ibfk_1` FOREIGN KEY (`accommodation_id`) REFERENCES `accommodation` (`accommodation_id`) ON DELETE CASCADE;

--
-- Constraints for table `agency`
--
ALTER TABLE `agency`
  ADD CONSTRAINT `agency_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`accommodation_id`) REFERENCES `accommodation` (`accommodation_id`) ON DELETE CASCADE;

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `destination_translation`
--
ALTER TABLE `destination_translation`
  ADD CONSTRAINT `destination_translation_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destination` (`destination_id`) ON DELETE CASCADE;

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destination` (`destination_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `trip_booking` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `keyword_translation`
--
ALTER TABLE `keyword_translation`
  ADD CONSTRAINT `keyword_translation_ibfk_1` FOREIGN KEY (`keyword_id`) REFERENCES `keyword` (`keyword_id`) ON DELETE CASCADE;

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `post_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destination` (`destination_id`) ON DELETE SET NULL;

--
-- Constraints for table `postimage`
--
ALTER TABLE `postimage`
  ADD CONSTRAINT `postimage_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_has_keyword`
--
ALTER TABLE `post_has_keyword`
  ADD CONSTRAINT `post_has_keyword_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_has_keyword_ibfk_2` FOREIGN KEY (`keyword_id`) REFERENCES `keyword` (`keyword_id`) ON DELETE CASCADE;

--
-- Constraints for table `private_message`
--
ALTER TABLE `private_message`
  ADD CONSTRAINT `private_message_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_message_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_booking`
--
ALTER TABLE `trip_booking`
  ADD CONSTRAINT `trip_booking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trip_booking_ibfk_2` FOREIGN KEY (`date_id`) REFERENCES `trip_date` (`date_id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_date`
--
ALTER TABLE `trip_date`
  ADD CONSTRAINT `trip_date_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `trip_package` (`package_id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_inclusion`
--
ALTER TABLE `trip_inclusion`
  ADD CONSTRAINT `trip_inclusion_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `trip_package` (`package_id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_package`
--
ALTER TABLE `trip_package`
  ADD CONSTRAINT `trip_package_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `agency` (`agency_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trip_package_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destination` (`destination_id`) ON DELETE SET NULL;

--
-- Constraints for table `trip_package_translation`
--
ALTER TABLE `trip_package_translation`
  ADD CONSTRAINT `trip_pkg_trans_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `trip_package` (`package_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_has_keyword`
--
ALTER TABLE `user_has_keyword`
  ADD CONSTRAINT `user_has_keyword_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_has_keyword_ibfk_2` FOREIGN KEY (`keyword_id`) REFERENCES `keyword` (`keyword_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
