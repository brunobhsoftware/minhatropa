-- MinhaTropa — V018: Sufixo da propriedade, cadastro rápido e trilha da vida do animal

-- Base oficial de sufixos das associações (ABCCMM etc.): importada em lote, consultada no cadastro.
-- A propriedade só pode usar um sufixo que exista aqui e cujos dados batam (documento do proprietário).
CREATE TABLE IF NOT EXISTS sufixo_associacao (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    associacao VARCHAR(30) NOT NULL DEFAULT 'ABCCMM',
    codigo VARCHAR(30) NOT NULL,
    sufixo VARCHAR(120) NOT NULL,
    proprietario_nome VARCHAR(200) NULL,
    proprietario_documento VARCHAR(20) NULL,
    proprietario_celular VARCHAR(30) NULL,
    proprietario_telefone VARCHAR(30) NULL,
    uf CHAR(2) NULL,
    cidade VARCHAR(100) NULL,
    cep VARCHAR(10) NULL,
    importado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sufixo_assoc_codigo (associacao, codigo),
    UNIQUE KEY uk_sufixo_assoc_nome (associacao, sufixo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sufixo na propriedade: vinculado ao registro oficial da associação.
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS sufixo VARCHAR(120) NULL AFTER nome;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS sufixo_codigo VARCHAR(30) NULL AFTER sufixo;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS sufixo_associacao_id BIGINT UNSIGNED NULL AFTER sufixo_codigo;

-- Índice único para o sufixo (só entre os não-nulos)
SET @existe = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'empresa' AND INDEX_NAME = 'uk_empresa_sufixo');
SET @sql = IF(@existe = 0, 'CREATE UNIQUE INDEX uk_empresa_sufixo ON empresa (sufixo)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
DO 0;

-- Trilha da vida do animal: onde nasceu, onde foi criado, onde está
ALTER TABLE animal ADD COLUMN IF NOT EXISTS local_nascimento VARCHAR(200) NULL AFTER data_nascimento;
ALTER TABLE animal ADD COLUMN IF NOT EXISTS criador_nome VARCHAR(150) NULL AFTER local_nascimento;
ALTER TABLE animal ADD COLUMN IF NOT EXISTS criador_cidade VARCHAR(100) NULL AFTER criador_nome;
ALTER TABLE animal ADD COLUMN IF NOT EXISTS criador_uf CHAR(2) NULL AFTER criador_cidade;
ALTER TABLE animal ADD COLUMN IF NOT EXISTS proprietario_nome VARCHAR(150) NULL AFTER criador_uf;
ALTER TABLE animal ADD COLUMN IF NOT EXISTS proprietario_cidade VARCHAR(100) NULL AFTER proprietario_nome;
ALTER TABLE animal ADD COLUMN IF NOT EXISTS proprietario_uf CHAR(2) NULL AFTER proprietario_cidade;

-- ID do animal na associação (ex.: 1083311)
ALTER TABLE animal ADD COLUMN IF NOT EXISTS id_associacao VARCHAR(30) NULL AFTER registro_associacao;

-- Registro do pai e da mãe vindos do cadastro rápido (quando não são animais do sistema)
ALTER TABLE animal ADD COLUMN IF NOT EXISTS pai_registro VARCHAR(60) NULL AFTER pai;
ALTER TABLE animal ADD COLUMN IF NOT EXISTS mae_registro VARCHAR(60) NULL AFTER mae;

-- Vivo / bloqueado (da ficha da associação)
ALTER TABLE animal ADD COLUMN IF NOT EXISTS vivo TINYINT(1) NOT NULL DEFAULT 1 AFTER situacao;
ALTER TABLE animal ADD COLUMN IF NOT EXISTS bloqueado TINYINT(1) NOT NULL DEFAULT 0 AFTER vivo;
