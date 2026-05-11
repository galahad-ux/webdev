# Momo Vacation — SQL Migration Guide

Run these commands on your alwaysdata database (`momo-vacation_bdd`).
Execute them **in order**. Each section is independent if you need to rerun one.

---

## 1. New tables

```sql
-- Agency profiles (users with role='agency')
CREATE TABLE IF NOT EXISTS `agency` (
  `agency_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`agency_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `agency_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Trip packages (replaces accommodation as the main product)
CREATE TABLE IF NOT EXISTS `trip_package` (
  `package_id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) NOT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`package_id`),
  KEY `agency_id` (`agency_id`),
  KEY `destination_id` (`destination_id`),
  CONSTRAINT `trip_package_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `agency` (`agency_id`) ON DELETE CASCADE,
  CONSTRAINT `trip_package_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destination` (`destination_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Trip package translations (fr/en)
CREATE TABLE IF NOT EXISTS `trip_package_translation` (
  `translation_id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `language_code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `highlights` text DEFAULT NULL,
  PRIMARY KEY (`translation_id`),
  UNIQUE KEY `package_lang` (`package_id`, `language_code`),
  CONSTRAINT `trip_pkg_trans_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `trip_package` (`package_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Agency-defined departure dates per package
CREATE TABLE IF NOT EXISTS `trip_date` (
  `date_id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `departure_date` date NOT NULL,
  `return_date` date NOT NULL,
  `duration_nights` int(11) NOT NULL,
  `price_per_person` decimal(10,2) NOT NULL,
  `max_spots` int(11) NOT NULL DEFAULT 20,
  `booked_spots` int(11) NOT NULL DEFAULT 0,
  `status` varchar(50) DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`date_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `trip_date_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `trip_package` (`package_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- What is included in the package (hotel, transport, activities)
-- max_participants NULL = no limit (hotel/transport); set for activities
CREATE TABLE IF NOT EXISTS `trip_inclusion` (
  `inclusion_id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'hotel, transport, activity',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`inclusion_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `trip_inclusion_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `trip_package` (`package_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Trip bookings (users booking a specific trip_date)
CREATE TABLE IF NOT EXISTS `trip_booking` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date_id` int(11) NOT NULL,
  `num_participants` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'confirmed',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`booking_id`),
  KEY `user_id` (`user_id`),
  KEY `date_id` (`date_id`),
  CONSTRAINT `trip_booking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `trip_booking_ibfk_2` FOREIGN KEY (`date_id`) REFERENCES `trip_date` (`date_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Invoices / payment records (card details are NEVER stored, only last4)
CREATE TABLE IF NOT EXISTS `invoice` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `card_last4` char(4) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT current_timestamp(),
  `invoice_number` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'paid',
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `booking_id` (`booking_id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `trip_booking` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Support tickets (user → admin messaging)
CREATE TABLE IF NOT EXISTS `ticket` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) DEFAULT 'open',
  `admin_reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## 2. Index on user.role (performance)

```sql
ALTER TABLE `user` ADD INDEX IF NOT EXISTS `idx_user_role` (`role`);
```

---

## 3. Sample data — Agency account

**Before running:** generate a bcrypt hash for the agency password with PHP:
```
php -r "echo password_hash('AgencyPass1', PASSWORD_DEFAULT);"
```
Replace `PASTE_HASH_HERE` below with the output.

```sql
INSERT INTO `user` (`name`, `email`, `phone_number`, `password`, `role`, `is_verified`, `language`, `account_status`)
VALUES ('Momo Agency Admin', 'agency@momo-travel.com', '+33600000001', 'PASTE_HASH_HERE', 'agency', 1, 'fr', 'active');

SET @agency_uid = LAST_INSERT_ID();

INSERT INTO `agency` (`user_id`, `company_name`, `description`, `verified`)
VALUES (@agency_uid, 'Momo Voyages', 'Organisateur de voyages de luxe depuis 2020. Spécialiste des circuits culturels et des escapades sur mesure.', 1);

SET @agency_id = LAST_INSERT_ID();
```

---

## 4. Sample data — Trip packages (Paris, Tokyo, London)

```sql
-- === PARIS ===
INSERT INTO `trip_package` (`agency_id`, `destination_id`, `image_url`, `base_price`, `status`)
VALUES (@agency_id, 1, 'images/destinations/Paris/Paris.webp', 1200.00, 'published');
SET @pkg1 = LAST_INSERT_ID();

INSERT INTO `trip_package_translation` (`package_id`, `language_code`, `name`, `description`, `highlights`) VALUES
(@pkg1, 'en', 'Paris Romantic Escape', 'An unforgettable 5-night trip to the City of Light, blending iconic landmarks, gourmet dining, and luxury accommodation.', 'Eiffel Tower • Louvre Museum • Seine Dinner Cruise • 4★ Hotel'),
(@pkg1, 'fr', 'Escapade Romantique à Paris', 'Un voyage inoubliable de 5 nuits dans la Ville Lumière, mêlant monuments emblématiques, gastronomie et hébergement de luxe.', 'Tour Eiffel • Musée du Louvre • Croisière Dîner • Hôtel 4★');

INSERT INTO `trip_date` (`package_id`, `departure_date`, `return_date`, `duration_nights`, `price_per_person`, `max_spots`, `booked_spots`) VALUES
(@pkg1, '2026-06-10', '2026-06-15', 5, 1200.00, 20, 3),
(@pkg1, '2026-07-05', '2026-07-10', 5, 1350.00, 20, 8),
(@pkg1, '2026-08-15', '2026-08-20', 5, 1450.00, 15, 0);

INSERT INTO `trip_inclusion` (`package_id`, `type`, `name`, `description`, `max_participants`, `sort_order`) VALUES
(@pkg1, 'transport', 'Vol A/R Paris CDG', 'Billet inclus, bagage 23 kg inclus', NULL, 1),
(@pkg1, 'hotel', 'Le Meurice 4★', 'Chambre double, petit-déjeuner inclus chaque matin', NULL, 2),
(@pkg1, 'activity', 'Visite guidée du Louvre', 'Visite privée de 3h avec un expert en histoire de l\'art', 15, 3),
(@pkg1, 'activity', 'Croisière dîner sur la Seine', 'Dîner gastronomique 3 plats à bord d\'une péniche', 20, 4);

-- === TOKYO ===
INSERT INTO `trip_package` (`agency_id`, `destination_id`, `image_url`, `base_price`, `status`)
VALUES (@agency_id, 4, 'images/destinations/Tokyo/Tokyo.webp', 2500.00, 'published');
SET @pkg2 = LAST_INSERT_ID();

INSERT INTO `trip_package_translation` (`package_id`, `language_code`, `name`, `description`, `highlights`) VALUES
(@pkg2, 'en', 'Tokyo Zen Discovery', 'A 7-night immersion into Japanese culture and modern life, from bustling Shibuya to serene Nikko temples.', 'Aman Tokyo • Shinkansen Pass • Tea Ceremony • Sushi Masterclass'),
(@pkg2, 'fr', 'Découverte Zen de Tokyo', 'Une immersion de 7 nuits dans la culture japonaise, de Shibuya animé aux temples sereins de Nikko.', 'Aman Tokyo • Pass Shinkansen • Cérémonie du Thé • Masterclass Sushi');

INSERT INTO `trip_date` (`package_id`, `departure_date`, `return_date`, `duration_nights`, `price_per_person`, `max_spots`, `booked_spots`) VALUES
(@pkg2, '2026-09-05', '2026-09-12', 7, 2500.00, 12, 2),
(@pkg2, '2026-10-10', '2026-10-17', 7, 2800.00, 12, 0),
(@pkg2, '2026-11-01', '2026-11-08', 7, 2600.00, 10, 5);

INSERT INTO `trip_inclusion` (`package_id`, `type`, `name`, `description`, `max_participants`, `sort_order`) VALUES
(@pkg2, 'transport', 'Vol A/R Paris-Tokyo', 'Classe affaires, 2 bagages 23 kg inclus', NULL, 1),
(@pkg2, 'transport', 'Pass JR Rail 7 jours', 'Shinkansen + transports locaux JR illimités', NULL, 2),
(@pkg2, 'hotel', 'Aman Tokyo', 'Suite panoramique, accès spa et piscine inclus', NULL, 3),
(@pkg2, 'activity', 'Cérémonie du thé traditionnelle', 'Expérience authentique dans un salon de thé du XVIe siècle', 8, 4),
(@pkg2, 'activity', 'Masterclass sushi avec chef étoilé', 'Apprenez l\'art du sushi avec un chef Michelin', 10, 5);

-- === LONDON ===
INSERT INTO `trip_package` (`agency_id`, `destination_id`, `image_url`, `base_price`, `status`)
VALUES (@agency_id, 2, 'images/destinations/Londres/London.webp', 980.00, 'published');
SET @pkg3 = LAST_INSERT_ID();

INSERT INTO `trip_package_translation` (`package_id`, `language_code`, `name`, `description`, `highlights`) VALUES
(@pkg3, 'en', 'London Classic Weekend', 'A premium 3-night escape to the heart of London, combining history, culture and unmissable entertainment.', 'The Savoy • Tower of London • Thames Cruise • West End Show'),
(@pkg3, 'fr', 'Week-end Classique à Londres', 'Un séjour premium de 3 nuits au cœur de Londres, alliant histoire, culture et divertissements incontournables.', 'The Savoy • Tour de Londres • Croisière Tamise • Comédie Musicale');

INSERT INTO `trip_date` (`package_id`, `departure_date`, `return_date`, `duration_nights`, `price_per_person`, `max_spots`, `booked_spots`) VALUES
(@pkg3, '2026-06-20', '2026-06-23', 3, 980.00, 25, 5),
(@pkg3, '2026-07-18', '2026-07-21', 3, 1100.00, 25, 12),
(@pkg3, '2026-09-25', '2026-09-28', 3, 950.00, 20, 0);

INSERT INTO `trip_inclusion` (`package_id`, `type`, `name`, `description`, `max_participants`, `sort_order`) VALUES
(@pkg3, 'transport', 'Eurostar Paris-Londres A/R', 'Standard Premier, 1 bagage inclus', NULL, 1),
(@pkg3, 'hotel', 'The Savoy', 'Chambre classique, accès au Beaufort Bar inclus', NULL, 2),
(@pkg3, 'activity', 'Visite guidée de la Tour de Londres', 'Guide expert, accès aux Joyaux de la Couronne', 20, 3),
(@pkg3, 'activity', 'Comédie musicale West End', 'Billet catégorie A pour un spectacle en cours', 25, 4);
```

---

## 5. Quick verification

Run this after the migration to confirm all tables exist:

```sql
SHOW TABLES LIKE '%agency%';
SHOW TABLES LIKE '%trip%';
SHOW TABLES LIKE '%invoice%';
SHOW TABLES LIKE '%ticket%';
SELECT COUNT(*) FROM trip_package;   -- should return 3
SELECT COUNT(*) FROM trip_date;      -- should return 9
SELECT COUNT(*) FROM trip_inclusion; -- should return 13
```
