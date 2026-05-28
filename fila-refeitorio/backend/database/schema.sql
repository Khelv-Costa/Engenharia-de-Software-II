-- =============================================================
-- Sistema de Fila Refeitório - Schema do Banco de Dados
-- Versão: 1.0.0
-- Compatível com: MySQL 5.7+ / MariaDB 10.3+
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- Base de dados
-- -------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS fila_refeitorio
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE fila_refeitorio;

-- -------------------------------------------------------------
-- Tabela: users
-- Armazena clientes e administradores
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,          -- bcrypt hash
    role        ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    active      TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- Tabela: services
-- Tipos de serviço disponíveis no refeitório
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80)   NOT NULL,
    description VARCHAR(255)  DEFAULT NULL,
    icon        VARCHAR(50)   DEFAULT '🍽️',
    prefix      CHAR(1)       NOT NULL,           -- ex: C (Café), A (Almoço)
    active      TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_prefix (prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- Tabela: tickets
-- Tickets/senhas gerados pelos clientes
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tickets (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    service_id    INT UNSIGNED NOT NULL,
    ticket_number VARCHAR(10)  NOT NULL,           -- ex: A001, C023
    status        ENUM('waiting','called','serving','completed','cancelled') NOT NULL DEFAULT 'waiting',
    position      INT UNSIGNED DEFAULT NULL,       -- posição calculada em runtime
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    called_at     DATETIME     DEFAULT NULL,
    completed_at  DATETIME     DEFAULT NULL,
    INDEX idx_service_status (service_id, status),
    INDEX idx_user_active    (user_id, status),
    INDEX idx_created        (created_at),
    CONSTRAINT fk_ticket_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_ticket_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- Tabela: queue_history
-- Histórico de todas as operações da fila (auditoria)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS queue_history (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT UNSIGNED NOT NULL,
    service_id  INT UNSIGNED NOT NULL,
    admin_id    INT UNSIGNED DEFAULT NULL,
    action      ENUM('created','called','completed','cancelled') NOT NULL,
    notes       VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_service_date (service_id, created_at),
    CONSTRAINT fk_history_ticket  FOREIGN KEY (ticket_id)  REFERENCES tickets(id)  ON DELETE CASCADE,
    CONSTRAINT fk_history_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_admin   FOREIGN KEY (admin_id)   REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- Dados iniciais
-- -------------------------------------------------------------

-- Serviços padrão
INSERT INTO services (name, description, icon, prefix) VALUES
('Café da Manhã', 'Serviço de café da manhã (06h - 09h)',  '☕', 'C'),
('Almoço',        'Serviço de almoço (11h30 - 14h)',       '🍽️', 'A'),
('Lanche',        'Serviço de lanche (15h - 17h)',          '🥪', 'L'),
('Jantar',        'Serviço de jantar (18h - 20h)',          '🌙', 'J');

-- Admin padrão (senha: Admin@123)
INSERT INTO users (name, email, password, role) VALUES
('Administrador', 'admin@refeitorio.ao',
 '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiUFSvXxEVFWgVmG5ZVQnFNelSH2', 'admin');

-- -------------------------------------------------------------
-- View útil: fila_atual
-- -------------------------------------------------------------
CREATE OR REPLACE VIEW vw_fila_atual AS
SELECT
    t.id,
    t.ticket_number,
    t.status,
    t.created_at,
    t.called_at,
    u.name   AS cliente_nome,
    s.name   AS servico_nome,
    s.prefix AS servico_prefix,
    s.icon   AS servico_icon,
    ROW_NUMBER() OVER (PARTITION BY t.service_id ORDER BY t.created_at) AS posicao
FROM tickets t
JOIN users    u ON u.id = t.user_id
JOIN services s ON s.id = t.service_id
WHERE t.status IN ('waiting', 'called', 'serving');
