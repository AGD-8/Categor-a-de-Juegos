-- Archivo: database_schema.sql
-- Script MySQL para una aplicación de valoración de juegos de PlayStation

CREATE DATABASE IF NOT EXISTS playstation_db;
USE playstation_db;

-- Tabla 1: Juegos
CREATE TABLE games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    platform VARCHAR(50) NOT NULL, -- PS4, PS5, etc.
    release_year INT,
    developer VARCHAR(100),
    genre VARCHAR(50)
);

-- Tabla 2: Valoraciones
-- Relación 1:N (Un juego puede tener muchas valoraciones)
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    score INT NOT NULL CHECK (score BETWEEN 1 AND 5), -- Puntuación del 1 al 5
    comment TEXT,
    rating_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

-- Inserción de registros iniciales
INSERT INTO games (title, platform, release_year, developer, genre) VALUES 
('The Last of Us Part II', 'PS4', 2020, 'Naughty Dog', 'Acción-Aventura'),
('God of War Ragnarök', 'PS5', 2022, 'Santa Monica Studio', 'Acción-Aventura'),
('Bloodborne', 'PS4', 2015, 'FromSoftware', 'Accion RPG'),
('Horizon Forbidden West', 'PS5', 2022, 'Guerrilla Games', 'Mundo Abierto'),
('Ghost of Tsushima', 'PS4', 2020, 'Sucker Punch', 'Acción');

INSERT INTO ratings (game_id, score, comment, user_name) VALUES 
(1, 5, 'Impactante y emocionante hasta el final.', 'Alejandro'),
(1, 4, 'Gráficos increíbles, aunque la trama es algo divisiva.', 'Maria92'),
(2, 5, 'Mejor que el anterior en todo. Una épica total.', 'KratosFan'),
(3, 5, 'Dificultad justa y una ambientación gótica suprema.', 'Hunter_V'),
(4, 4, 'Visualmente es lo más puntero de PS5.', 'TechGeek'),
(5, 5, 'El sistema de combate con katana es perfecto.', 'SamuraiX'),
(5, 5, 'Una de las mejores direcciones de arte que he visto.', 'Alejandro');

