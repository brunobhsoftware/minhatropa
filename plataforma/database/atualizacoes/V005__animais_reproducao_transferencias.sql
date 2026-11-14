-- MinhaTropa — V005: cadastro completo de animais, cobrições, nascimentos, transferências e morte/baixa

-- ============================================================
-- 1. Expandir tabela animal
-- ============================================================
ALTER TABLE animal
    ADD COLUMN IF NOT EXISTS especie ENUM('equino','asinino','muar') NOT NULL DEFAULT 'equino' AFTER empresa_id,
    ADD COLUMN IF NOT EXISTS nome_registro VARCHAR(150) NULL AFTER nome,
    ADD COLUMN IF NOT EXISTS apelido VARCHAR(80) NULL AFTER nome_registro,
    ADD COLUMN IF NOT EXISTS pelagem_complemento VARCHAR(80) NULL AFTER pelagem,
    ADD COLUMN IF NOT EXISTS numero_plantel VARCHAR(30) NULL AFTER microchip,
    ADD COLUMN IF NOT EXISTS local_baia VARCHAR(80) NULL AFTER numero_plantel,
    ADD COLUMN IF NOT EXISTS registro_associacao VARCHAR(30) NULL COMMENT 'Sigla: ABCCMM, ABQM, ABCCC…' AFTER registro,
    ADD COLUMN IF NOT EXISTS registro_definitivo TINYINT(1) NOT NULL DEFAULT 0 AFTER registro_associacao,
    ADD COLUMN IF NOT EXISTS dna_coletado TINYINT(1) NOT NULL DEFAULT 0 AFTER registro_definitivo,
    ADD COLUMN IF NOT EXISTS peso_kg DECIMAL(6,1) NULL AFTER dna_coletado,
    ADD COLUMN IF NOT EXISTS altura_cm DECIMAL(5,1) NULL AFTER peso_kg,
    ADD COLUMN IF NOT EXISTS castrado TINYINT(1) NOT NULL DEFAULT 0 AFTER altura_cm,
    ADD COLUMN IF NOT EXISTS pai_id BIGINT UNSIGNED NULL AFTER pai,
    ADD COLUMN IF NOT EXISTS mae_id BIGINT UNSIGNED NULL AFTER mae,
    ADD COLUMN IF NOT EXISTS data_aquisicao DATE NULL AFTER mae_id,
    ADD COLUMN IF NOT EXISTS forma_aquisicao ENUM('nascimento','compra','doacao','transferencia','outro') NULL AFTER data_aquisicao,
    ADD COLUMN IF NOT EXISTS valor_aquisicao DECIMAL(12,2) NULL AFTER forma_aquisicao,
    ADD COLUMN IF NOT EXISTS situacao ENUM('ativo','vendido','transferido','morto','emprestado','em_doma','reprodutor_externo') NOT NULL DEFAULT 'ativo' AFTER valor_aquisicao,
    ADD COLUMN IF NOT EXISTS situacao_data DATE NULL AFTER situacao,
    ADD COLUMN IF NOT EXISTS situacao_motivo VARCHAR(255) NULL AFTER situacao_data,
    ADD COLUMN IF NOT EXISTS morte_data DATE NULL AFTER situacao_motivo,
    ADD COLUMN IF NOT EXISTS morte_causa VARCHAR(255) NULL AFTER morte_data,
    ADD COLUMN IF NOT EXISTS morte_laudo TEXT NULL AFTER morte_causa,
    ADD COLUMN IF NOT EXISTS foto_principal VARCHAR(255) NULL AFTER morte_laudo;

-- FK genealogia cross-Haras (sem restrição de empresa_id)
SELECT COUNT(*) INTO @fk_pai FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_NAME = 'fk_animal_pai' AND TABLE_SCHEMA = DATABASE();
SET @sql_pai = IF(@fk_pai = 0,
    'ALTER TABLE animal ADD CONSTRAINT fk_animal_pai FOREIGN KEY (pai_id) REFERENCES animal (id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql_pai; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @fk_mae FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_NAME = 'fk_animal_mae' AND TABLE_SCHEMA = DATABASE();
SET @sql_mae = IF(@fk_mae = 0,
    'ALTER TABLE animal ADD CONSTRAINT fk_animal_mae FOREIGN KEY (mae_id) REFERENCES animal (id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql_mae; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Índice por situação
SELECT COUNT(*) INTO @ix_sit FROM information_schema.STATISTICS
    WHERE INDEX_NAME = 'ix_animal_situacao' AND TABLE_SCHEMA = DATABASE();
SET @sql_ix = IF(@ix_sit = 0,
    'ALTER TABLE animal ADD INDEX ix_animal_situacao (empresa_id, situacao, excluido_em)',
    'SELECT 1');
PREPARE stmt FROM @sql_ix; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Expandir ENUM de categoria (adicionar novas)
ALTER TABLE animal MODIFY COLUMN categoria ENUM(
    'matriz','garanhao','potro','potra','cavalo','egua','castrado',
    'receptora','servico',
    'jumento','jumenta','jumentinho','jumentinha',
    'burro','mula','burro_novo','mula_nova',
    'outro'
) NULL;

-- ============================================================
-- 2. Cobrições (reprodução)
-- ============================================================
CREATE TABLE IF NOT EXISTS cobricao (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid             CHAR(36)        NOT NULL,
    empresa_id       BIGINT UNSIGNED NOT NULL,
    egua_id          BIGINT UNSIGNED NOT NULL,
    garanhao_id      BIGINT UNSIGNED NULL COMMENT 'Pode ser de outro Haras',
    garanhao_nome    VARCHAR(150)    NULL COMMENT 'Nome quando não cadastrado',
    receptora_id     BIGINT UNSIGNED NULL COMMENT 'Receptora para TE',
    tipo             ENUM('monta_natural','inseminacao_artificial','transferencia_embriao') NOT NULL DEFAULT 'monta_natural',
    subtipo          VARCHAR(80)     NULL COMMENT 'Técnica específica: sêmen fresco, refrigerado, congelado…',
    data_cobricao    DATE            NOT NULL,
    data_confirmacao DATE            NULL,
    data_prevista_parto DATE         NULL,
    veterinario_id   BIGINT UNSIGNED NULL,
    local            VARCHAR(150)    NULL,
    resultado        ENUM('pendente','confirmada','nao_confirmada','absorvida','abortada','nascimento') NOT NULL DEFAULT 'pendente',
    observacoes      TEXT            NULL,
    criado_por       BIGINT UNSIGNED NULL,
    criado_em        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    excluido_em      DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_cobricao_uuid (uuid),
    KEY ix_cobricao_empresa (empresa_id, excluido_em),
    KEY ix_cobricao_egua (egua_id),
    KEY ix_cobricao_garanhao (garanhao_id),
    CONSTRAINT fk_cobricao_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id),
    CONSTRAINT fk_cobricao_egua FOREIGN KEY (egua_id) REFERENCES animal (id),
    CONSTRAINT fk_cobricao_garanhao FOREIGN KEY (garanhao_id) REFERENCES animal (id) ON DELETE SET NULL,
    CONSTRAINT fk_cobricao_receptora FOREIGN KEY (receptora_id) REFERENCES animal (id) ON DELETE SET NULL,
    CONSTRAINT fk_cobricao_veterinario FOREIGN KEY (veterinario_id) REFERENCES profissional (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Nascimentos
-- ============================================================
CREATE TABLE IF NOT EXISTS nascimento (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid             CHAR(36)        NOT NULL,
    empresa_id       BIGINT UNSIGNED NOT NULL,
    animal_id        BIGINT UNSIGNED NULL COMMENT 'Animal nascido (criado automaticamente ou depois)',
    cobricao_id      BIGINT UNSIGNED NULL COMMENT 'Vínculo com a cobrição, se houver',
    mae_id           BIGINT UNSIGNED NOT NULL,
    pai_id           BIGINT UNSIGNED NULL,
    pai_nome         VARCHAR(150)    NULL COMMENT 'Nome do pai quando não cadastrado',
    data_nascimento  DATETIME        NOT NULL,
    tipo_parto       ENUM('normal','distocia','cesariana','prematuro') NOT NULL DEFAULT 'normal',
    sexo             ENUM('macho','femea','indefinido') NULL,
    nome_produto     VARCHAR(120)    NULL COMMENT 'Nome dado ao produto',
    peso_nascimento  DECIMAL(5,1)    NULL COMMENT 'kg',
    vivo             TINYINT(1)      NOT NULL DEFAULT 1,
    veterinario_id   BIGINT UNSIGNED NULL,
    observacoes      TEXT            NULL,
    criado_por       BIGINT UNSIGNED NULL,
    criado_em        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    excluido_em      DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_nascimento_uuid (uuid),
    KEY ix_nascimento_empresa (empresa_id, excluido_em),
    KEY ix_nascimento_mae (mae_id),
    CONSTRAINT fk_nascimento_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id),
    CONSTRAINT fk_nascimento_animal FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE SET NULL,
    CONSTRAINT fk_nascimento_cobricao FOREIGN KEY (cobricao_id) REFERENCES cobricao (id) ON DELETE SET NULL,
    CONSTRAINT fk_nascimento_mae FOREIGN KEY (mae_id) REFERENCES animal (id),
    CONSTRAINT fk_nascimento_pai FOREIGN KEY (pai_id) REFERENCES animal (id) ON DELETE SET NULL,
    CONSTRAINT fk_nascimento_veterinario FOREIGN KEY (veterinario_id) REFERENCES profissional (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Transferências entre Haras
-- ============================================================
CREATE TABLE IF NOT EXISTS transferencia (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid                CHAR(36)        NOT NULL,
    animal_id           BIGINT UNSIGNED NOT NULL,
    empresa_origem_id   BIGINT UNSIGNED NOT NULL,
    empresa_destino_id  BIGINT UNSIGNED NULL COMMENT 'NULL se destino externo',
    destino_externo     VARCHAR(200)    NULL COMMENT 'Nome do destino quando não é Haras da plataforma',
    tipo                ENUM('venda','doacao','emprestimo','devolucao','outro') NOT NULL DEFAULT 'venda',
    valor               DECIMAL(12,2)   NULL,
    data_transferencia  DATE            NOT NULL,
    situacao            ENUM('solicitada','aceita','recusada','cancelada','concluida') NOT NULL DEFAULT 'concluida',
    solicitado_por      BIGINT UNSIGNED NULL,
    respondido_por      BIGINT UNSIGNED NULL,
    respondido_em       DATETIME        NULL,
    motivo_recusa       VARCHAR(255)    NULL,
    observacoes         TEXT            NULL,
    criado_por          BIGINT UNSIGNED NULL,
    criado_em           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_transferencia_uuid (uuid),
    KEY ix_transferencia_origem (empresa_origem_id),
    KEY ix_transferencia_destino (empresa_destino_id),
    KEY ix_transferencia_animal (animal_id),
    CONSTRAINT fk_transferencia_animal FOREIGN KEY (animal_id) REFERENCES animal (id),
    CONSTRAINT fk_transferencia_origem FOREIGN KEY (empresa_origem_id) REFERENCES empresa (id),
    CONSTRAINT fk_transferencia_destino FOREIGN KEY (empresa_destino_id) REFERENCES empresa (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. Conferência
-- ============================================================
SELECT 'V005 ok' AS resultado;
