USE game_balance;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица опросов
CREATE TABLE IF NOT EXISTS polls (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_polls_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица вариантов ответа
CREATE TABLE IF NOT EXISTS options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    poll_id BIGINT UNSIGNED NOT NULL,
    text VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_options_poll
        FOREIGN KEY (poll_id) REFERENCES polls(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица голосов
CREATE TABLE IF NOT EXISTS votes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    poll_id BIGINT UNSIGNED NOT NULL,
    option_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_votes_poll
        FOREIGN KEY (poll_id) REFERENCES polls(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_votes_option
        FOREIGN KEY (option_id) REFERENCES options(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_votes_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    -- один голос на пользователя в одном опросе
    CONSTRAINT uq_votes_poll_user UNIQUE (poll_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица логов голосования
CREATE TABLE IF NOT EXISTS vote_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    poll_id BIGINT UNSIGNED NULL,
    option_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    message VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vote_logs_poll
        FOREIGN KEY (poll_id) REFERENCES polls(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_vote_logs_option
        FOREIGN KEY (option_id) REFERENCES options(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_vote_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
