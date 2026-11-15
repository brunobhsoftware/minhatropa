-- MinhaTropa — V013: controle de estoque (módulo nutrição)

CREATE TABLE IF NOT EXISTS categoria_estoque (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id  BIGINT UNSIGNED NOT NULL,
    uuid        CHAR(36) NOT NULL,
    nome        VARCHAR(120) NOT NULL,
    descricao   VARCHAR(500) NULL,
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    excluido_em DATETIME NULL,
    UNIQUE KEY uk_categoria_estoque_uuid (uuid),
    KEY idx_categoria_estoque_empresa (empresa_id),
    CONSTRAINT fk_categoria_estoque_empresa FOREIGN KEY (empresa_id) REFERENCES empresa(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_estoque (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id      BIGINT UNSIGNED NOT NULL,
    uuid            CHAR(36) NOT NULL,
    categoria_id    BIGINT UNSIGNED NULL,
    nome            VARCHAR(200) NOT NULL,
    unidade         VARCHAR(30) NOT NULL DEFAULT 'un' COMMENT 'un, kg, L, mL, saco, dose, caixa',
    estoque_atual   DECIMAL(12,3) NOT NULL DEFAULT 0,
    estoque_minimo  DECIMAL(12,3) NULL COMMENT 'abaixo disso mostra alerta',
    custo_unitario  DECIMAL(12,2) NULL,
    localizacao     VARCHAR(120) NULL COMMENT 'galpão, baia, prateleira',
    observacoes     VARCHAR(2000) NULL,
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    excluido_em     DATETIME NULL,
    criado_por      BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_item_estoque_uuid (uuid),
    KEY idx_item_estoque_empresa (empresa_id),
    KEY idx_item_estoque_categoria (categoria_id),
    CONSTRAINT fk_item_estoque_empresa FOREIGN KEY (empresa_id) REFERENCES empresa(id),
    CONSTRAINT fk_item_estoque_categoria FOREIGN KEY (categoria_id) REFERENCES categoria_estoque(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movimentacao_estoque (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id      BIGINT UNSIGNED NOT NULL,
    uuid            CHAR(36) NOT NULL,
    item_id         BIGINT UNSIGNED NOT NULL,
    tipo            ENUM('entrada','saida','ajuste') NOT NULL,
    quantidade      DECIMAL(12,3) NOT NULL,
    custo_unitario  DECIMAL(12,2) NULL,
    motivo          VARCHAR(255) NULL,
    observacoes     VARCHAR(2000) NULL,
    data_movimentacao DATE NOT NULL,
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    criado_por      BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_movimentacao_estoque_uuid (uuid),
    KEY idx_movimentacao_empresa (empresa_id),
    KEY idx_movimentacao_item (item_id),
    KEY idx_movimentacao_data (data_movimentacao),
    CONSTRAINT fk_movimentacao_estoque_empresa FOREIGN KEY (empresa_id) REFERENCES empresa(id),
    CONSTRAINT fk_movimentacao_estoque_item FOREIGN KEY (item_id) REFERENCES item_estoque(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
