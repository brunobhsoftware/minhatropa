-- MinhaTropa — V019: pré-cadastro genealógico para vinculação automática de pai/mãe

CREATE TABLE IF NOT EXISTS animal_referencia (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid             CHAR(36)        NOT NULL,
    nome             VARCHAR(150)    NOT NULL COMMENT 'Nome completo do animal (ex: Gigante da Agrotexas)',
    registro         VARCHAR(60)     NULL COMMENT 'Número de registro na associação',
    registro_associacao VARCHAR(30)  NULL COMMENT 'Sigla da associação (ABCCMM, ABQM…)',
    especie          ENUM('equino','asinino','muar') NOT NULL DEFAULT 'equino',
    sexo             ENUM('macho','femea') NULL,
    criado_por_empresa_id BIGINT UNSIGNED NULL COMMENT 'Propriedade que informou primeiro',
    criado_por       BIGINT UNSIGNED NULL COMMENT 'Usuário que informou',
    animal_id        BIGINT UNSIGNED NULL COMMENT 'Animal real vinculado — preenchido quando alguém cadastra o animal de fato',
    vinculado_em     DATETIME        NULL,
    criado_em        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_referencia_uuid (uuid),
    KEY ix_referencia_registro (registro),
    KEY ix_referencia_nome (nome(50)),
    KEY ix_referencia_animal (animal_id),
    CONSTRAINT fk_referencia_empresa FOREIGN KEY (criado_por_empresa_id) REFERENCES empresa (id) ON DELETE SET NULL,
    CONSTRAINT fk_referencia_animal FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Colunas na tabela animal para vincular ao pré-cadastro quando o genitor foi informado por referência
ALTER TABLE animal
    ADD COLUMN IF NOT EXISTS pai_referencia_id BIGINT UNSIGNED NULL AFTER pai_id,
    ADD COLUMN IF NOT EXISTS mae_referencia_id BIGINT UNSIGNED NULL AFTER mae_id;

SET @fk_pai_ref = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = 'fk_animal_pai_ref' AND TABLE_SCHEMA = DATABASE());
SET @sql_pai_ref = IF(@fk_pai_ref = 0,
    'ALTER TABLE animal ADD CONSTRAINT fk_animal_pai_ref FOREIGN KEY (pai_referencia_id) REFERENCES animal_referencia (id) ON DELETE SET NULL',
    'DO 0');
PREPARE stmt FROM @sql_pai_ref; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_mae_ref = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = 'fk_animal_mae_ref' AND TABLE_SCHEMA = DATABASE());
SET @sql_mae_ref = IF(@fk_mae_ref = 0,
    'ALTER TABLE animal ADD CONSTRAINT fk_animal_mae_ref FOREIGN KEY (mae_referencia_id) REFERENCES animal_referencia (id) ON DELETE SET NULL',
    'DO 0');
PREPARE stmt FROM @sql_mae_ref; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Conferência
SELECT 'V019 ok' AS resultado;
