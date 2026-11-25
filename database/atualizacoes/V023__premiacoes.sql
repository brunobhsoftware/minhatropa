-- MinhaTropa — V023: Premiações conquistadas pelo animal (evento, data, local)

CREATE TABLE IF NOT EXISTS premiacao (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid          CHAR(36)        NOT NULL,
    empresa_id    BIGINT UNSIGNED NOT NULL,
    animal_id     BIGINT UNSIGNED NOT NULL,
    evento        VARCHAR(150)    NOT NULL,
    data          DATE            NULL,
    local         VARCHAR(150)    NULL,
    colocacao     VARCHAR(100)    NULL COMMENT 'Classificação/prêmio, quando houver',
    observacoes   TEXT            NULL,
    criado_por    BIGINT UNSIGNED NULL,
    criado_em     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    excluido_em   DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_premiacao_uuid (uuid),
    KEY ix_premiacao_empresa (empresa_id, excluido_em),
    KEY ix_premiacao_animal (animal_id, excluido_em),
    CONSTRAINT fk_premiacao_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id),
    CONSTRAINT fk_premiacao_animal FOREIGN KEY (animal_id) REFERENCES animal (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
