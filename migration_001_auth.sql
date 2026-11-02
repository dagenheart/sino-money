-- Run once on an existing Sino Money database (bank).
-- Back up your database before applying.
CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE cards ADD COLUMN user_id INT NULL;
ALTER TABLE cards ADD UNIQUE KEY uq_cards_card_number (card_number);
ALTER TABLE cards ADD INDEX idx_cards_user_id (user_id);
ALTER TABLE cards ADD CONSTRAINT fk_cards_user FOREIGN KEY (user_id) REFERENCES users(id);
-- Existing demo cards remain unassigned. New registrations create their own cards.
