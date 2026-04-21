-- APW_Juegos — Complete Database Schema for MariaDB
-- Run this file once to set up the database

CREATE DATABASE IF NOT EXISTS apw_juegos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apw_juegos;

-- ─── USERS ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar        VARCHAR(255) DEFAULT 'default_avatar.png',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── GAMES ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS games (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    platform     ENUM('PS4','PS5','Xbox') NOT NULL,
    developer    VARCHAR(100),
    release_year INT,
    genre        VARCHAR(100),
    description  TEXT,
    image        VARCHAR(255) DEFAULT 'default_cover.png',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── REVIEWS ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    game_id    INT NOT NULL,
    user_id    INT NOT NULL,
    rating     INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── CHAT MESSAGES ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chat_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    game_id    INT NOT NULL,
    user_id    INT NOT NULL,
    message    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── SEED DATA ─────────────────────────────────────────────────────────────────
-- Default admin user  (password: admin123)
INSERT INTO users (username, password_hash) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO games (title, platform, developer, release_year, genre, description, image) VALUES
('The Last of Us Part II', 'PS4', 'Naughty Dog',       2020, 'Acción-Aventura', 'Una historia épica de supervivencia, amor y venganza en un mundo post-apocalíptico.', 'thelastofus2.png'),
('God of War Ragnarök',    'PS5', 'Santa Monica Studio',2022, 'Acción-Aventura', 'Kratos y Atreus se enfrentan al apocalipsis nórdico en esta épica aventura.',          'godofwar.png'),
('Bloodborne',             'PS4', 'FromSoftware',       2015, 'Acción RPG',      'Explora la oscura ciudad de Yharnam en un mundo gótico lleno de bestias y secretos.',     'bloodborne.png'),
('Horizon Forbidden West', 'PS5', 'Guerrilla Games',    2022, 'Mundo Abierto',   'Aloy continúa su viaje descubriendo los secretos del oeste prohibido.',                  'horizonforbiddenwest.png'),
('Ghost of Tsushima',      'PS4', 'Sucker Punch',       2020, 'Acción',          'Conviértete en el Ghost mientras defiendes Tsushima contra la invasión mongola.',          'ghostoftsushima.png')
ON DUPLICATE KEY UPDATE title = title;
