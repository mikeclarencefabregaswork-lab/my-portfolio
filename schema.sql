-- ============================================================
--  Mike Clarence Fabregas — Portfolio Database Schema
--  Engine: MySQL 8.0+  |  Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS portfolio_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE portfolio_db;

-- ------------------------------------------------------------
--  USERS  (admin login)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(80)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,          -- bcrypt via password_hash()
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin — password is:  admin123
-- Hash produced by:  password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO users (username, password) VALUES
('admin', '$2y$12$YourHashHere.ReplaceThisWithAProperHashBeforeDeployment');
-- ⚠  Run this in PHP to regenerate a real hash:
--    echo password_hash('YourChosenPassword', PASSWORD_BCRYPT);

-- ------------------------------------------------------------
--  EXPERIENCES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS experiences (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  year         VARCHAR(30)   NOT NULL,
  role         VARCHAR(120)  NOT NULL,
  company      VARCHAR(120)  NOT NULL,
  description  TEXT          NOT NULL,
  technologies JSON          NOT NULL,       -- e.g. ["React","Node.js"]
  sort_order   TINYINT UNSIGNED DEFAULT 0,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
  'Collaborated with a small agile team to deliver responsive marketing pages and internal dashboards. Improved page-load performance by 35 % through asset optimisation and lazy-loading strategies.',
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
  image_path   VARCHAR(500)  NOT NULL DEFAULT 'uploads/placeholder.png',
  category     ENUM('web_app','photo_editing','video_editing') NOT NULL DEFAULT 'web_app',
  sort_order   TINYINT UNSIGNED DEFAULT 0,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO projects (title, description, technologies, project_url, image_path, category, sort_order) VALUES
(
  'Personal Portfolio Website',
  'A fully responsive dark-mode portfolio built with React and Framer Motion. Features smooth scroll animations, dynamic project sections, and a clean typographic hierarchy that showcases skills and experience.',
  '["React","Tailwind CSS","Framer Motion","Vite"]',
  'https://github.com/mikeclarencefabregas',
  'uploads/portfolio.png',
  'web_app',
  1
),
(
  'E-Commerce Platform',
  'Full-stack shopping application with product catalogues, cart management, Stripe payment integration, and an admin dashboard for inventory control. Secured with JWT authentication and role-based access.',
  '["React","Node.js","Express","MongoDB","Stripe API","JWT"]',
  'https://github.com/mikeclarencefabregas',
  'uploads/ecommerce.png',
  'web_app',
  2
),
(
  'Task Management App',
  'Kanban-style task manager with drag-and-drop columns, real-time updates via WebSockets, deadline notifications, and team collaboration features. Deployed on Railway with a PostgreSQL backend.',
  '["React","Socket.io","Node.js","PostgreSQL","Tailwind CSS"]',
  'https://github.com/mikeclarencefabregas',
  'uploads/taskapp.png',
  'web_app',
  3
),
(
  'Brand Identity — Brew & Co.',
  'Complete visual identity package for a specialty coffee brand: logo design, colour palette, typography selection, social-media templates, and print-ready packaging mockups crafted in Adobe Photoshop and Illustrator.',
  '["Adobe Photoshop","Adobe Illustrator","Brand Design"]',
  'https://github.com/mikeclarencefabregas',
  'uploads/brew_brand.png',
  'photo_editing',
  1
),
(
  'Event Poster Series',
  'Set of 6 promotional posters for a campus music festival. Layered photo manipulation, typographic composition, and consistent colour grading across the series to achieve a cohesive visual campaign.',
  '["Adobe Photoshop","Typography","Color Grading"]',
  'https://github.com/mikeclarencefabregas',
  'uploads/posters.png',
  'photo_editing',
  2
),
(
  'Short Film — "Hilaga"',
  'Cinematic short film shot in Batangas showcasing local culture. Full post-production pipeline: colour grading with DaVinci Resolve, motion graphics in After Effects, and audio mixing for a 12-minute final cut.',
  '["DaVinci Resolve","Adobe After Effects","Adobe Premiere Pro"]',
  'https://github.com/mikeclarencefabregas',
  'uploads/hilaga.png',
  'video_editing',
  1
),
(
  'YouTube Channel Intro Pack',
  'Animated intro and outro sequences for a tech YouTube channel. Custom motion graphics, logo reveals, and lower-thirds created in After Effects, resulting in a 40 % increase in viewer retention during opening seconds.',
  '["Adobe After Effects","Motion Graphics","Adobe Premiere Pro"]',
  'https://github.com/mikeclarencefabregas',
  'uploads/yt_intro.png',
  'video_editing',
  2
);
