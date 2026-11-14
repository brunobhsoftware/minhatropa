-- MinhaTropa — V012: clínica veterinária (eventos de saúde)

CREATE TABLE IF NOT EXISTS evento_saude (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36) NOT NULL,
    empresa_id      BIGINT UNSIGNED NOT NULL,
    animal_id       BIGINT UNSIGNED NOT NULL,
    tipo            ENUM('vacina','vermifugacao','exame','tratamento','cirurgia','medicacao','outro') NOT NULL DEFAULT 'outro',
    titulo          VARCHAR(200) NOT NULL,
    descricao       TEXT NULL,
    data_evento     DATE NOT NULL,
    data_proxima    DATE NULL,
    medicamento     VARCHAR(200) NULL,
    dosagem         VARCHAR(100) NULL,
    via_administracao VARCHAR(100) NULL,
    lote            VARCHAR(100) NULL,
    peso_animal     DECIMAL(8,2) NULL,
    temperatura     DECIMAL(4,1) NULL,
    custo           DECIMAL(12,2) NULL,
    observacoes     TEXT NULL,
    criado_por      BIGINT UNSIGNED NOT NULL,
    criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    excluido_em     TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uk_evento_saude_uuid (uuid),
    KEY idx_evento_empresa_animal (empresa_id, animal_id),
    KEY idx_evento_empresa_tipo (empresa_id, tipo),
    KEY idx_evento_empresa_data (empresa_id, data_evento),
    CONSTRAINT fk_evento_saude_empresa FOREIGN KEY (empresa_id) REFERENCES empresa(id),
    CONSTRAINT fk_evento_saude_animal FOREIGN KEY (animal_id) REFERENCES animal(id),
    CONSTRAINT fk_evento_saude_criador FOREIGN KEY (criado_por) REFERENCES usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'V012 ok' AS resultado;
