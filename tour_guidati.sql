-- ============================================================
-- Tour Guidati — Database Setup
-- Tecnologie del Web 2025/2026 — Prof. Alfonso Pierantonio
-- ============================================================
-- Default passwords (bcrypt of 'password'):
--   admin   → username: admin,   password: password
--   utente  → username: mario,   password: password
-- CHANGE PASSWORDS after first login!
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `tour_guidati`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `tour_guidati`;

-- ==============================
-- USERS-GROUPS-SERVICES
-- ==============================

CREATE TABLE `groups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) UNIQUE NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(20),
    `avatar` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `users_has_groups` (
    `users_id` INT NOT NULL,
    `groups_id` INT NOT NULL,
    PRIMARY KEY (`users_id`, `groups_id`),
    FOREIGN KEY (`users_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`groups_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `services` (
    `username` VARCHAR(50) PRIMARY KEY,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `url` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `services_has_groups` (
    `services_username` VARCHAR(50) NOT NULL,
    `groups_id` INT NOT NULL,
    PRIMARY KEY (`services_username`, `groups_id`),
    FOREIGN KEY (`services_username`) REFERENCES `services`(`username`) ON DELETE CASCADE,
    FOREIGN KEY (`groups_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- DOMAIN TABLES
-- ==============================

CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `description` TEXT,
    `icon` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `locations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `city` VARCHAR(100) NOT NULL,
    `province` VARCHAR(50),
    `region` VARCHAR(100),
    `country` VARCHAR(50) DEFAULT 'Italia',
    `latitude` DECIMAL(10,8),
    `longitude` DECIMAL(11,8),
    `address` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `guides` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `bio` TEXT,
    `profile_photo` VARCHAR(255),
    `languages` VARCHAR(255),
    `specialization` VARCHAR(255),
    `email` VARCHAR(100),
    `phone` VARCHAR(20),
    `rating_avg` DECIMAL(3,2) DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tours` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) UNIQUE NOT NULL,
    `description` TEXT NOT NULL,
    `short_description` VARCHAR(500),
    `duration_minutes` INT NOT NULL,
    `max_participants` INT DEFAULT 10,
    `price_per_person` DECIMAL(10,2) NOT NULL,
    `difficulty_level` ENUM('facile','medio','difficile') DEFAULT 'facile',
    `meeting_point` TEXT,
    `included_services` TEXT,
    `what_to_bring` TEXT,
    `categories_id` INT NOT NULL,
    `locations_id` INT NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `rating_avg` DECIMAL(3,2) DEFAULT 0.00,
    `total_reviews` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`categories_id`) REFERENCES `categories`(`id`),
    FOREIGN KEY (`locations_id`) REFERENCES `locations`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tours_has_guides` (
    `tours_id` INT NOT NULL,
    `guides_id` INT NOT NULL,
    `is_lead_guide` TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`tours_id`, `guides_id`),
    FOREIGN KEY (`tours_id`) REFERENCES `tours`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`guides_id`) REFERENCES `guides`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tour_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tours_id` INT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `alt_text` VARCHAR(200),
    `is_cover` TINYINT(1) DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`tours_id`) REFERENCES `tours`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `time_slots` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tours_id` INT NOT NULL,
    `slot_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `available_seats` INT NOT NULL,
    `booked_seats` INT DEFAULT 0,
    `status` ENUM('disponibile','pieno','cancellato') DEFAULT 'disponibile',
    `notes` TEXT,
    FOREIGN KEY (`tours_id`) REFERENCES `tours`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `users_id` INT NOT NULL,
    `time_slots_id` INT NOT NULL,
    `total_participants` INT NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('in_attesa','confermata','cancellata','completata') DEFAULT 'in_attesa',
    `booking_code` VARCHAR(20) UNIQUE NOT NULL,
    `additional_notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`users_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`time_slots_id`) REFERENCES `time_slots`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `booking_participants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bookings_id` INT NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `is_primary_contact` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`bookings_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `users_id` INT NOT NULL,
    `tours_id` INT NOT NULL,
    `bookings_id` INT,
    `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `title` VARCHAR(200),
    `comment` TEXT,
    `is_approved` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`users_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`tours_id`) REFERENCES `tours`(`id`),
    FOREIGN KEY (`bookings_id`) REFERENCES `bookings`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- SEED DATA
-- ==============================

-- Groups
INSERT INTO `groups` (`name`, `description`) VALUES
('admin',           'Amministratori con accesso completo al sistema'),
('guide',           'Guide turistiche che gestiscono i tour'),
('registered_user', 'Utenti registrati che possono prenotare tour'),
('guest',           'Visitatori non registrati con accesso solo in lettura');

-- Services
INSERT INTO `services` (`username`, `display_name`, `description`, `url`) VALUES
('tours_view',          'Visualizza Tour',           'Navigazione e ricerca dei tour disponibili',   '/tours.php'),
('booking',             'Prenotazione Tour',          'Accesso al flusso di prenotazione',            '/booking.php'),
('reviews',             'Scrivi Recensione',          'Possibilità di scrivere recensioni',           '/tour_detail.php'),
('profile',             'Profilo Utente',             'Modifica dati personali',                      '/profile.php'),
('my_bookings',         'Le Mie Prenotazioni',        'Visualizzazione prenotazioni personali',       '/my_bookings.php'),
('admin_dashboard',     'Dashboard Admin',            'Accesso al pannello di amministrazione',       '/admin/'),
('admin_tours',         'Gestione Tour',              'CRUD tour',                                    '/admin/tours/'),
('admin_guides',        'Gestione Guide',             'CRUD guide',                                   '/admin/guides/'),
('admin_slots',         'Gestione Slot',              'CRUD slot temporali',                          '/admin/slots/'),
('admin_bookings',      'Gestione Prenotazioni',      'Visualizzazione e gestione prenotazioni',      '/admin/bookings/'),
('admin_reviews',       'Moderazione Recensioni',     'Approvazione e rifiuto recensioni',            '/admin/reviews/'),
('admin_users',         'Gestione Utenti',            'CRUD utenti',                                  '/admin/users/'),
('admin_categories',    'Gestione Categorie',         'CRUD categorie tour',                          '/admin/categories/'),
('admin_locations',     'Gestione Luoghi',            'CRUD luoghi',                                  '/admin/locations/');

-- Services → Groups mapping
-- guest: solo tours_view
INSERT INTO `services_has_groups` VALUES
('tours_view', 4);

-- registered_user: tours + booking + reviews + profile + my_bookings
INSERT INTO `services_has_groups` VALUES
('tours_view', 3),
('booking', 3),
('reviews', 3),
('profile', 3),
('my_bookings', 3);

-- guide: same as registered_user
INSERT INTO `services_has_groups` VALUES
('tours_view', 2),
('booking', 2),
('reviews', 2),
('profile', 2),
('my_bookings', 2);

-- admin: everything
INSERT INTO `services_has_groups` VALUES
('tours_view', 1),
('booking', 1),
('reviews', 1),
('profile', 1),
('my_bookings', 1),
('admin_dashboard', 1),
('admin_tours', 1),
('admin_guides', 1),
('admin_slots', 1),
('admin_bookings', 1),
('admin_reviews', 1),
('admin_users', 1),
('admin_categories', 1),
('admin_locations', 1);

-- Users (password = 'password' for all)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO `users` (`username`, `password_hash`, `email`, `first_name`, `last_name`, `phone`, `is_active`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@tourguidati.it', 'Admin', 'Sistema', NULL, 1),
('mario', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mario.rossi@email.it', 'Mario', 'Rossi', '+39 333 1234567', 1),
('giulia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'giulia.bianchi@email.it', 'Giulia', 'Bianchi', '+39 347 9876543', 1);

-- Users → Groups
INSERT INTO `users_has_groups` VALUES (1, 1); -- admin → admin
INSERT INTO `users_has_groups` VALUES (2, 3); -- mario → registered_user
INSERT INTO `users_has_groups` VALUES (3, 3); -- giulia → registered_user

-- Categories
INSERT INTO `categories` (`name`, `slug`, `description`, `icon`) VALUES
('Musei',              'musei',       'Tour guidati nei più importanti musei italiani',              'museum'),
('Monumenti',          'monumenti',   'Visite ai monumenti storici e architettonici',                'landmark'),
('Cammini Storici',    'cammini',     'Percorsi a piedi su antiche vie storiche',                    'footprints'),
('Natura e Parchi',    'natura',      'Escursioni in parchi nazionali e riserve naturali',           'tree'),
('Arte e Cultura',     'arte',        'Itinerari nei quartieri artistici e nei centri culturali',    'palette');

-- Locations
INSERT INTO `locations` (`city`, `province`, `region`, `country`, `latitude`, `longitude`) VALUES
('Roma',          'RM', 'Lazio',          'Italia', 41.89474000, 12.48220000),
('Firenze',       'FI', 'Toscana',        'Italia', 43.76956000, 11.25581000),
('Venezia',       'VE', 'Veneto',         'Italia', 45.44085000, 12.31552000),
('Napoli',        'NA', 'Campania',       'Italia', 40.85216000, 14.26811000),
('Cinque Terre',  'SP', 'Liguria',        'Italia', 44.10500000, 9.72800000);

-- Guides
INSERT INTO `guides` (`first_name`, `last_name`, `bio`, `languages`, `specialization`, `email`, `phone`, `rating_avg`, `is_active`) VALUES
('Elena',   'Ferrari',  'Storica dell''arte con 15 anni di esperienza. Specializzata in arte rinascimentale e romana. Ha conseguito il dottorato in Storia dell''Arte a Bologna.',        'Italiano, Inglese, Francese',       'Arte Rinascimentale, Archeologia Romana',  'elena.ferrari@tourguidati.it',  '+39 328 1234001', 4.80, 1),
('Marco',   'Conti',    'Naturalista e accompagnatore di trekking. Guida ufficiale del Parco Nazionale delle Cinque Terre. Appassionato di geologia e botanica locale.',               'Italiano, Inglese, Tedesco',         'Natura, Trekking, Geologia',              'marco.conti@tourguidati.it',    '+39 328 1234002', 4.70, 1),
('Sofia',   'Ricci',    'Esperta di storia medievale e rinascimentale fiorentina. Collabora con la Galleria degli Uffizi per visite speciali. Parla cinque lingue.',                   'Italiano, Inglese, Spagnolo, Francese, Tedesco', 'Arte Fiorentina, Storia Medievale',  'sofia.ricci@tourguidati.it',    '+39 328 1234003', 4.90, 1);

-- Tours
INSERT INTO `tours` (`title`, `slug`, `description`, `short_description`, `duration_minutes`, `max_participants`, `price_per_person`, `difficulty_level`, `meeting_point`, `included_services`, `what_to_bring`, `categories_id`, `locations_id`, `is_active`, `rating_avg`, `total_reviews`) VALUES
(
  'I Fori Imperiali: Cuore dell''Antica Roma',
  'fori-imperiali-roma',
  '<p>Immergiti nel cuore dell''antica Roma con questo esclusivo tour guidato dei Fori Imperiali. Passeggia tra le colonne millenarie dove senatori e imperatori decidevano le sorti del mondo, ascolta storie di battaglie, intrighi e trionfi narrate dalla nostra guida esperta.</p><p>Il percorso si snoda attraverso il Foro Romano, il Palatino e i Fori di Augusto, Nerva e Traiano. Scoprirai come vivevano i romani, le loro usanze quotidiane e la grandiosità di un''impero che ha plasmato la nostra civiltà.</p>',
  'Un viaggio nel tempo attraverso i più importanti siti archeologici di Roma, tra colonne millenarie e storie di imperatori.',
  180, 12, 35.00, 'medio',
  'Ingresso principale del Colosseo, Via Sacra 1, Roma',
  'Ingresso ai siti, audioguida, mappa del percorso, assistenza h24',
  'Scarpe comode, crema solare, acqua, documento d''identità',
  2, 1, 1, 4.80, 24
),
(
  'Galleria degli Uffizi: Capolavori del Rinascimento',
  'uffizi-rinascimento-firenze',
  '<p>La Galleria degli Uffizi ospita una delle più grandi collezioni d''arte al mondo. Con questa visita guidata esclusiva, salterai la fila e potrai ammirare da vicino la Nascita di Venere di Botticelli, l''Annunciazione di Leonardo da Vinci e tantissimi altri capolavori del Rinascimento.</p><p>La nostra guida esperta ti condurrà attraverso i secoli, dalla pittura medievale fino all''arte barocca, raccontando le storie dei mecenati Medici e degli artisti geniali che hanno trasformato Firenze nel centro culturale del mondo.</p>',
  'Scopri i capolavori del Rinascimento italiano nella più famosa pinacoteca del mondo, senza attese in fila.',
  150, 10, 45.00, 'facile',
  'Piazzale degli Uffizi 6, Firenze — sotto la statua di Dante',
  'Biglietto prioritario skip-the-line, guida certificata, auricolari wireless',
  'Abbigliamento adeguato (no spalle scoperte), documento d''identità',
  1, 2, 1, 4.90, 31
),
(
  'Sentiero Azzurro delle Cinque Terre',
  'sentiero-azzurro-cinque-terre',
  '<p>Il Sentiero Azzurro è il percorso di trekking più famoso d''Italia, che collega i cinque caratteristici borghi marinari delle Cinque Terre affacciati sul Mar Ligure. Questo tour vi porterà lungo viste mozzafiato, attraverso vigneti terrazzati e caruggi colorati.</p><p>Partiremo da Riomaggiore e, seguendo il sentiero costiero, raggiungeremo Monterosso al Mare. Durante il cammino, la nostra guida naturalista vi spiegherà la flora locale, la storia dei borghi e le tradizioni della pesca.</p>',
  'Trekking sul leggendario Sentiero Azzurro tra i cinque borghi colorati affacciati sul Mar Ligure.',
  300, 15, 28.00, 'medio',
  'Stazione ferroviaria di Riomaggiore, Piazza Unità d''Italia 1',
  'Guida naturalista certificata, mappa del sentiero, kit di pronto soccorso',
  'Scarpe da trekking, abbigliamento a strati, acqua (min. 1,5 L), snack',
  3, 5, 1, 4.70, 18
),
(
  'Napoli Sotterranea e Quartieri Spagnoli',
  'napoli-sotterranea-quartieri',
  '<p>Esplora i segreti di Napoli in questo tour unico che combina la visita alle gallerie sotterranee greco-romane con una passeggiata autentica nei Quartieri Spagnoli. Scoprirai la Napoli nascosta: acquedotti romani del IV sec. a.C., cisterne greche, gallerie della Seconda Guerra Mondiale.</p><p>Nella seconda parte del tour, percorrerai i vicoletti dei Quartieri Spagnoli, assaggerai le migliori specialità locali con degustazione inclusa e conoscerai la vita vera dei napoletani.</p>',
  'Tour unico tra la Napoli sotterranea e i pittoreschi Quartieri Spagnoli, con degustazione di specialità locali.',
  240, 10, 38.00, 'facile',
  'Piazza San Gaetano 68, Napoli — davanti all''ingresso della Napoli Sotterranea',
  'Ingresso Napoli Sotterranea, degustazione prodotti tipici, guida certificata',
  'Scarpe chiuse, abbigliamento leggero, torcia (opzionale)',
  2, 4, 1, 4.60, 12
);

-- Tour images
INSERT INTO `tour_images` (`tours_id`, `image_path`, `alt_text`, `is_cover`, `sort_order`) VALUES
(1, 'uploads/tours/fori-cover.jpg',    'Fori Imperiali al tramonto',          1, 0),
(1, 'uploads/tours/fori-2.jpg',        'Colonna di Traiano',                  0, 1),
(1, 'uploads/tours/fori-3.jpg',        'Via Sacra nel Foro Romano',           0, 2),
(2, 'uploads/tours/uffizi-cover.jpg',  'Galleria degli Uffizi, Firenze',      1, 0),
(2, 'uploads/tours/uffizi-2.jpg',      'Nascita di Venere, Botticelli',       0, 1),
(2, 'uploads/tours/uffizi-3.jpg',      'Sala delle sculture antiche',         0, 2),
(3, 'uploads/tours/cinque-cover.jpg',  'Vista panoramica Cinque Terre',       1, 0),
(3, 'uploads/tours/cinque-2.jpg',      'Sentiero tra i borghi',               0, 1),
(3, 'uploads/tours/cinque-3.jpg',      'Riomaggiore dal mare',                0, 2),
(4, 'uploads/tours/napoli-cover.jpg',  'Gallerie sotterranee di Napoli',      1, 0),
(4, 'uploads/tours/napoli-2.jpg',      'Quartieri Spagnoli',                  0, 1),
(4, 'uploads/tours/napoli-3.jpg',      'Degustazione specialità napoletane',  0, 2);

-- Tours ↔ Guides
INSERT INTO `tours_has_guides` VALUES (1, 1, 1); -- Fori: Elena (lead)
INSERT INTO `tours_has_guides` VALUES (2, 3, 1); -- Uffizi: Sofia (lead)
INSERT INTO `tours_has_guides` VALUES (2, 1, 0); -- Uffizi: Elena (support)
INSERT INTO `tours_has_guides` VALUES (3, 2, 1); -- Cinque Terre: Marco (lead)
INSERT INTO `tours_has_guides` VALUES (4, 1, 1); -- Napoli: Elena (lead)

-- Time slots (dates in June-July 2026)
INSERT INTO `time_slots` (`tours_id`, `slot_date`, `start_time`, `end_time`, `available_seats`, `booked_seats`, `status`) VALUES
-- Tour 1: Fori Imperiali
(1, '2026-06-07', '09:00:00', '12:00:00', 12, 4,  'disponibile'),
(1, '2026-06-07', '15:00:00', '18:00:00', 12, 0,  'disponibile'),
(1, '2026-06-14', '09:00:00', '12:00:00', 12, 12, 'pieno'),
(1, '2026-06-21', '09:00:00', '12:00:00', 12, 2,  'disponibile'),
(1, '2026-06-21', '15:00:00', '18:00:00', 12, 0,  'disponibile'),
(1, '2026-07-05', '09:00:00', '12:00:00', 12, 0,  'disponibile'),
(1, '2026-07-12', '09:00:00', '12:00:00', 12, 0,  'disponibile'),
-- Tour 2: Uffizi
(2, '2026-06-06', '10:00:00', '12:30:00', 10, 3,  'disponibile'),
(2, '2026-06-13', '10:00:00', '12:30:00', 10, 0,  'disponibile'),
(2, '2026-06-20', '10:00:00', '12:30:00', 10, 9,  'disponibile'),
(2, '2026-06-27', '10:00:00', '12:30:00', 10, 0,  'disponibile'),
(2, '2026-07-04', '10:00:00', '12:30:00', 10, 0,  'disponibile'),
(2, '2026-07-11', '10:00:00', '12:30:00', 10, 0,  'disponibile'),
-- Tour 3: Cinque Terre
(3, '2026-06-06', '08:00:00', '13:00:00', 15, 5,  'disponibile'),
(3, '2026-06-13', '08:00:00', '13:00:00', 15, 0,  'disponibile'),
(3, '2026-06-20', '08:00:00', '13:00:00', 15, 0,  'disponibile'),
(3, '2026-06-27', '08:00:00', '13:00:00', 15, 0,  'disponibile'),
(3, '2026-07-11', '08:00:00', '13:00:00', 15, 0,  'disponibile'),
-- Tour 4: Napoli
(4, '2026-06-08', '10:00:00', '14:00:00', 10, 2,  'disponibile'),
(4, '2026-06-15', '10:00:00', '14:00:00', 10, 0,  'disponibile'),
(4, '2026-06-22', '10:00:00', '14:00:00', 10, 0,  'disponibile'),
(4, '2026-07-06', '10:00:00', '14:00:00', 10, 0,  'disponibile');

-- Sample bookings (user mario = id 2)
INSERT INTO `bookings` (`users_id`, `time_slots_id`, `total_participants`, `total_price`, `status`, `booking_code`, `additional_notes`, `created_at`) VALUES
(2, 1,  2, 70.00,  'confermata',  'TG-2026-AAB001', NULL,                           '2026-05-15 10:30:00'),
(2, 8,  3, 135.00, 'completata',  'TG-2026-AAB002', 'Accesso per disabili richiesto', '2026-05-10 14:00:00');

-- Booking participants
INSERT INTO `booking_participants` (`bookings_id`, `first_name`, `last_name`, `is_primary_contact`) VALUES
(1, 'Mario',  'Rossi',   1),
(1, 'Laura',  'Rossi',   0),
(2, 'Mario',  'Rossi',   1),
(2, 'Anna',   'Verdi',   0),
(2, 'Carlo',  'Verdi',   0);

-- Sample reviews (approved)
INSERT INTO `reviews` (`users_id`, `tours_id`, `bookings_id`, `rating`, `title`, `comment`, `is_approved`, `created_at`) VALUES
(2, 2, 2, 5, 'Esperienza straordinaria!',  'Sofia è una guida eccezionale, conosce ogni dettaglio delle opere. Ho visto la Nascita di Venere in modo completamente nuovo. Consigliatissimo!', 1, '2026-05-12 09:00:00'),
(3, 1, NULL, 4, 'Roma magica al tramonto', 'Elena racconta la storia romana con una passione contagiosa. Il tramonto sui Fori è uno spettacolo indimenticabile.', 1, '2026-05-18 16:30:00'),
(2, 1, 1, 5, 'Il meglio di Roma',         'Tour impeccabile, guida preparatissima. Si vede tutto senza correre e con un sacco di aneddoti interessanti.', 1, '2026-05-20 11:00:00');

-- Pending review (to moderate)
INSERT INTO `reviews` (`users_id`, `tours_id`, `rating`, `title`, `comment`, `is_approved`, `created_at`) VALUES
(3, 3, 5, 'Sentiero mozzafiato', 'Marco è un ottimo naturalista, le spiegazioni sulla flora e sulla storia dei borghi sono state fantastiche. Purtroppo il sentiero era un po'' affollato.', 0, '2026-05-21 20:00:00');
