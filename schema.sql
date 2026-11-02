-- Sino Money: clean schema for a NEW, EMPTY MySQL database.
-- Do not import this into an existing database; use migration_001_auth.sql for legacy installations.
CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cards (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(50) DEFAULT NULL,
    card_number VARCHAR(20) DEFAULT NULL,
    pin VARCHAR(4) NOT NULL DEFAULT '', -- Legacy compatibility only; unused by authentication
    balance INT DEFAULT NULL,
    user_id INT NULL,
    UNIQUE KEY uq_cards_card_number (card_number),
    KEY idx_cards_user_id (user_id),
    CONSTRAINT fk_cards_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE transactions (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    card_id INT DEFAULT NULL,
    amount INT DEFAULT NULL,
    type VARCHAR(100) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
