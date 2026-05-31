-- ============================================================
--  Mike Clarence Fabregas — Portfolio Database Schema
--  Engine: MySQL 8.0+  |  Charset: utf8mb4
--
--  ⚠  DO NOT seed admin credentials here.
--     Use register.php to create your admin account safely,
--     so the password is hashed by PHP's password_hash().
-- ============================================================

CREATE DATABASE IF NOT EXISTS portfolio_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE portfolio_db;

-- ------------------------------------------------------------
--  USERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(80)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ⚠  No INSERT for users here — go to register.php to create
--    your admin account (password will be properly bcrypt-hashed).

-- ------------------------------------------------------------
--  EXPERIENCES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS experiences (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  year         VARCHAR(30)      NOT NULL,
  role         VARCHAR(120)     NOT NULL,
  company      VARCHAR(120)     NOT NULL,
  description  TEXT             NOT NULL,
  technologies JSON             NOT NULL,
  sort_order   TINYINT UNSIGNED DEFAULT 0,
  created_at   TIMESTAMP        DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO experiences (year, role, company, description, technologies, sort_order) VALUES
(
  '2024 — Present',
  'Fullstack Developer',
  'Freelance / Self-Employed',
  'Designing and building end-to-end web applications for local businesses and international clients. Responsibilities span requirements gathering, UI/UX wireframing, frontend implementation, REST API development, database modelling, and cloud deployment.',
  '["React","Node.js","Express","MongoDB","Tailwind CSS","MySQL"]',
  1
),
(
  '2023',
  'Frontend Developer Intern',
  'Tech Startup — Lipa City',
  'Collaborated with a small agile team to deliver responsive marketing pages and internal dashboards. Improved page-load performance by 35% through asset optimisation and lazy-loading strategies.',
  '["HTML5","CSS3","JavaScript","PHP","Bootstrap"]',
  2
),
(
  '2022',
  'IT Support & Web Maintenance',
  'Local Government Unit — Batangas',
  'Maintained government web portals, applied security patches, and provided first-line technical support to 50+ staff members. Documented network topology and wrote internal IT procedures.',
  '["PHP","MySQL","WordPress","Networking"]',
  3
);

-- ------------------------------------------------------------
--  PROJECTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(160)  NOT NULL,
  description  TEXT          NOT NULL,
  technologies JSON          NOT NULL,
  project_url  VARCHAR(500)  NOT NULL DEFAULT '#',
  image_path   VARCHAR(500)  NOT NULL DEFAULT 'projects/web/placeholder.png',
  category     ENUM('web_app','photo_editing','video_editing') NOT NULL DEFAULT 'web_app',
  sort_order   TINYINT UNSIGNED DEFAULT 0,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO projects (title, description, technologies, project_url, image_path, category, sort_order) VALUES
(
  'Personal Portfolio Website',
  'A fully responsive dark-mode portfolio built with React and Framer Motion. Features smooth scroll animations, dynamic project sections, and a clean typographic hierarchy.',
  '["React","Tailwind CSS","Framer Motion","Vite"]',
  'https://github.com/mikeclarencefabregas',
  'projects/web/placeholder.png',
  'web_app', 1
),
(
  'E-Commerce Platform',
  'Full-stack shopping application with product catalogues, cart management, Stripe payment integration, and an admin dashboard. Secured with JWT authentication and role-based access.',
  '["React","Node.js","Express","MongoDB","Stripe API","JWT"]',
  'https://github.com/mikeclarencefabregas',
  'projects/web/placeholder.png',
  'web_app', 2
),
(
  'Task Management App',
  'Kanban-style task manager with drag-and-drop columns, real-time updates via WebSockets, deadline notifications, and team collaboration features.',
  '["React","Socket.io","Node.js","PostgreSQL","Tailwind CSS"]',
  'https://github.com/mikeclarencefabregas',
  'projects/web/placeholder.png',
  'web_app', 3
),
(
  'Brand Identity — Brew & Co.',
  'Complete visual identity package for a specialty coffee brand: logo, colour palette, typography, social-media templates, and print-ready packaging mockups.',
  '["Adobe Photoshop","Adobe Illustrator","Brand Design"]',
  'https://github.com/mikeclarencefabregas',
  'projects/web/placeholder.png',
  'photo_editing', 1
),
(
  'Event Poster Series',
  'Set of 6 promotional posters for a campus music festival. Layered photo manipulation and consistent colour grading across the series.',
  '["Adobe Photoshop","Typography","Color Grading"]',
  'https://github.com/mikeclarencefabregas',
  'projects/web/placeholder.png',
  'photo_editing', 2
),
(
  'Short Film — "Hilaga"',
  'Cinematic short film shot in Batangas. Full post-production: colour grading with DaVinci Resolve, motion graphics in After Effects, audio mixing for a 12-minute cut.',
  '["DaVinci Resolve","Adobe After Effects","Adobe Premiere Pro"]',
  'https://github.com/mikeclarencefabregas',
  'projects/web/placeholder.png',
  'video_editing', 1
),
(
  'YouTube Channel Intro Pack',
  'Animated intro/outro sequences for a tech YouTube channel. Custom motion graphics and logo reveals, resulting in a 40% increase in viewer retention.',
  '["Adobe After Effects","Motion Graphics","Adobe Premiere Pro"]',
  'https://github.com/mikeclarencefabregas',
  'projects/web/placeholder.png',
  'video_editing', 2
);
