-- MinhaTropa — V006: Haras / Fazenda por CPF ou CNPJ, UF e município (IBGE), faixas de plantel e módulos adicionais
--
-- Arquitetura do ecossistema (os nomes seguem a convenção do projeto: tabelas no singular):
--   profissional            pessoas físicas, CPF único                      ("profissionais")
--   empresa                 o Haras / Fazenda, CPF ou CNPJ único            ("propriedades")
--   empresa_profissional    vínculo N:N com função, titular e acesso        ("propriedade_profissionais")
--   animal                  plantel da propriedade (conta para a faixa)
--   plano_plantel           faixas de plantel (limite_min, limite_max, valor, ativo)
--   assinatura_empresa      faixa contratada por propriedade                ("assinaturas_propriedade")
--   modulo_sistema          módulos adicionais                              ("modulos_sistema")
--   empresa_modulo          módulos solicitados/ativos por propriedade      ("haras_modulos_ativos")
--   estado / municipio      tabela do IBGE (os dados entram no V007)
-- Preços: valor_mensal NULL = a definir pelo Bruno (o sistema mostra "sob consulta").
-- Idempotente. Variáveis com SET (a tela /admin/banco ignora comandos que começam com SELECT).

-- ============================================================
-- 1. Estados e municípios (IBGE)
-- ============================================================
CREATE TABLE IF NOT EXISTS estado (
    id           SMALLINT UNSIGNED NOT NULL COMMENT 'Código IBGE da UF (11 a 53)',
    sigla_uf     CHAR(2)           NOT NULL,
    nome         VARCHAR(60)       NOT NULL,
    regiao       VARCHAR(20)       NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_estado_sigla (sigla_uf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS municipio (
    id           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    estado_id    SMALLINT UNSIGNED NOT NULL,
    nome         VARCHAR(100)      NOT NULL,
    codigo_ibge  INT UNSIGNED      NOT NULL COMMENT 'Código IBGE de 7 dígitos',
    PRIMARY KEY (id),
    UNIQUE KEY uk_municipio_ibge (codigo_ibge),
    KEY ix_municipio_estado (estado_id, nome),
    CONSTRAINT fk_municipio_estado FOREIGN KEY (estado_id) REFERENCES estado (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Propriedade (Haras / Fazenda): CPF ou CNPJ único, titular e localização padronizada
-- ============================================================
ALTER TABLE empresa
    ADD COLUMN IF NOT EXISTS estado_id SMALLINT UNSIGNED NULL AFTER uf,
    ADD COLUMN IF NOT EXISTS municipio_id INT UNSIGNED NULL AFTER estado_id,
    ADD COLUMN IF NOT EXISTS corresp_estado_id SMALLINT UNSIGNED NULL AFTER corresp_uf,
    ADD COLUMN IF NOT EXISTS corresp_municipio_id INT UNSIGNED NULL AFTER corresp_estado_id,
    ADD COLUMN IF NOT EXISTS titular_profissional_id BIGINT UNSIGNED NULL COMMENT 'Pessoa física titular (proprietário/criador)' AFTER documento;

-- Documento em branco vira NULL e o tipo acompanha o tamanho (11 = CPF, 14 = CNPJ)
UPDATE empresa SET documento = NULL WHERE documento = '';
UPDATE empresa SET tipo_documento = 'cpf' WHERE documento IS NOT NULL AND CHAR_LENGTH(documento) = 11 AND tipo_documento IS NULL;
UPDATE empresa SET tipo_documento = 'cnpj' WHERE documento IS NOT NULL AND CHAR_LENGTH(documento) = 14 AND tipo_documento IS NULL;
-- Cadastros antigos sem tipo de propriedade passam a "Haras"
UPDATE empresa SET tipo_propriedade = 'haras' WHERE tipo_propriedade IS NULL;

-- CPF/CNPJ único. Só cria o índice se não houver documentos repetidos (se houver, a tela de Haras avisa).
SET @mt_dup = (SELECT COUNT(*) FROM (SELECT documento FROM empresa WHERE documento IS NOT NULL GROUP BY documento HAVING COUNT(*) > 1) d);
SET @mt_tem = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'empresa' AND INDEX_NAME = 'uk_empresa_documento');
SET @mt_sql = IF(@mt_dup = 0 AND @mt_tem = 0, 'ALTER TABLE empresa ADD UNIQUE KEY uk_empresa_documento (documento)', 'DO 0');
PREPARE mt_stmt FROM @mt_sql;
EXECUTE mt_stmt;
DEALLOCATE PREPARE mt_stmt;

SET @mt_tem = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'empresa' AND CONSTRAINT_NAME = 'fk_empresa_estado');
SET @mt_sql = IF(@mt_tem = 0, 'ALTER TABLE empresa ADD CONSTRAINT fk_empresa_estado FOREIGN KEY (estado_id) REFERENCES estado (id), ADD CONSTRAINT fk_empresa_municipio FOREIGN KEY (municipio_id) REFERENCES municipio (id), ADD CONSTRAINT fk_empresa_corresp_estado FOREIGN KEY (corresp_estado_id) REFERENCES estado (id), ADD CONSTRAINT fk_empresa_corresp_municipio FOREIGN KEY (corresp_municipio_id) REFERENCES municipio (id), ADD CONSTRAINT fk_empresa_titular FOREIGN KEY (titular_profissional_id) REFERENCES profissional (id) ON DELETE SET NULL', 'DO 0');
PREPARE mt_stmt FROM @mt_sql;
EXECUTE mt_stmt;
DEALLOCATE PREPARE mt_stmt;

-- ============================================================
-- 3. Profissional: localização padronizada e login próprio
-- ============================================================
ALTER TABLE profissional
    ADD COLUMN IF NOT EXISTS estado_id SMALLINT UNSIGNED NULL AFTER uf,
    ADD COLUMN IF NOT EXISTS municipio_id INT UNSIGNED NULL AFTER estado_id,
    ADD COLUMN IF NOT EXISTS usuario_id BIGINT UNSIGNED NULL COMMENT 'Login próprio do profissional, quando tem' AFTER cpf,
    ADD COLUMN IF NOT EXISTS origem ENUM('admin','haras','site') NOT NULL DEFAULT 'admin' AFTER situacao_motivo;

SET @mt_tem = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'profissional' AND CONSTRAINT_NAME = 'fk_profissional_estado');
SET @mt_sql = IF(@mt_tem = 0, 'ALTER TABLE profissional ADD CONSTRAINT fk_profissional_estado FOREIGN KEY (estado_id) REFERENCES estado (id), ADD CONSTRAINT fk_profissional_municipio FOREIGN KEY (municipio_id) REFERENCES municipio (id), ADD CONSTRAINT fk_profissional_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE SET NULL', 'DO 0');
PREPARE mt_stmt FROM @mt_sql;
EXECUTE mt_stmt;
DEALLOCATE PREPARE mt_stmt;

-- ============================================================
-- 4. Vínculo profissional × propriedade: funções, titular e acesso externo
-- ============================================================
ALTER TABLE empresa_profissional
    ADD COLUMN IF NOT EXISTS funcoes VARCHAR(255) NULL COMMENT 'Códigos das funções nesta propriedade, separados por vírgula' AFTER funcao,
    ADD COLUMN IF NOT EXISTS titular TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = proprietário/criador titular' AFTER funcoes,
    ADD COLUMN IF NOT EXISTS papel_acesso VARCHAR(20) NULL COMMENT 'Papel (RBAC) do acesso externo; espelha empresa_usuario.papel' AFTER acesso_sistema;

-- ============================================================
-- 5. Faixas de plantel (plano base) e assinatura da propriedade
-- ============================================================
CREATE TABLE IF NOT EXISTS plano_plantel (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo        VARCHAR(40)     NOT NULL,
    nome          VARCHAR(100)    NOT NULL,
    descricao     VARCHAR(255)    NULL,
    limite_min    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Primeiro animal da faixa',
    limite_max    INT UNSIGNED    NULL COMMENT 'Último animal da faixa; NULL = ilimitado',
    valor_mensal  DECIMAL(12,2)   NULL COMMENT 'NULL = a definir (sob consulta); 0 = gratuito',
    ativo         TINYINT(1)      NOT NULL DEFAULT 1,
    ordem         INT             NOT NULL DEFAULT 0,
    criado_em     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_plano_plantel_codigo (codigo),
    KEY ix_plano_plantel_faixa (ativo, limite_min)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO plano_plantel (codigo, nome, descricao, limite_min, limite_max, valor_mensal, ordem) VALUES
    ('gratuito', 'Plano Gratuito', 'Até 5 animais ativos, sem custo', 0, 5, 0.00, 10),
    ('faixa_1', 'Faixa 1', 'De 6 a 10 animais ativos', 6, 10, NULL, 20),
    ('faixa_2', 'Faixa 2', 'De 11 a 20 animais ativos', 11, 20, NULL, 30),
    ('faixa_3', 'Faixa 3', 'De 21 a 50 animais ativos', 21, 50, NULL, 40),
    ('faixa_4', 'Faixa 4', 'De 51 a 100 animais ativos', 51, 100, NULL, 50),
    ('ilimitado', 'Plantel ilimitado', 'Mais de 100 animais ativos', 101, NULL, NULL, 60);

CREATE TABLE IF NOT EXISTS assinatura_empresa (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id            BIGINT UNSIGNED NOT NULL,
    plano_plantel_id      BIGINT UNSIGNED NOT NULL COMMENT 'Faixa em vigor',
    plano_solicitado_id   BIGINT UNSIGNED NULL COMMENT 'Upgrade pedido pelo proprietário, aguardando liberação',
    solicitado_em         DATETIME        NULL,
    solicitado_por        BIGINT UNSIGNED NULL,
    iniciada_em           DATETIME        NULL,
    liberada_por          BIGINT UNSIGNED NULL,
    criado_em             DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em         DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_assinatura_empresa (empresa_id),
    CONSTRAINT fk_assinatura_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE,
    CONSTRAINT fk_assinatura_plano FOREIGN KEY (plano_plantel_id) REFERENCES plano_plantel (id),
    CONSTRAINT fk_assinatura_solicitado FOREIGN KEY (plano_solicitado_id) REFERENCES plano_plantel (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quem já tinha a licença liberada (regra antiga) continua sem limite de animais.
INSERT IGNORE INTO assinatura_empresa (empresa_id, plano_plantel_id, iniciada_em)
    SELECT e.id, p.id, COALESCE(e.licenca_liberada_em, UTC_TIMESTAMP()) FROM empresa e JOIN plano_plantel p ON p.codigo = 'ilimitado' WHERE e.licenca_ativa = 1;

-- Fim da regra "só o 1º animal é grátis": o gratuito passa a ser a primeira faixa (até 5 animais).
UPDATE parametro_sistema SET valor = '5', descricao = 'Animais do plano gratuito (usado só enquanto as faixas de plantel não existem)' WHERE chave = 'animais_gratis';

-- ============================================================
-- 6. Módulos adicionais (add-ons) e liberação por propriedade
-- ============================================================
CREATE TABLE IF NOT EXISTS modulo_sistema (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo        VARCHAR(40)     NOT NULL,
    nome          VARCHAR(100)    NOT NULL,
    resumo        VARCHAR(255)    NULL,
    beneficios    TEXT            NULL COMMENT 'Um benefício por linha (tela explicativa)',
    icone         VARCHAR(30)     NULL,
    valor_mensal  DECIMAL(12,2)   NULL COMMENT 'NULL = a definir (sob consulta)',
    ativo         TINYINT(1)      NOT NULL DEFAULT 1,
    ordem         INT             NOT NULL DEFAULT 0,
    criado_em     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_modulo_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO modulo_sistema (codigo, nome, resumo, beneficios, icone, ordem) VALUES
    ('equipe', 'Gestão de Equipe e Acesso Externo', 'Dê login próprio a gerentes, peões, treinadores e veterinários, cada um com a sua permissão.',
     'Vincule profissionais pelo CPF e dê a cada um um login próprio\nPermissões por função (gerente, veterinário, treinador, tratador, só consulta)\nSua equipe administra a propriedade sem ver os dados da sua assinatura\nDesative um acesso a qualquer momento, sem perder o histórico', 'usuarios', 10),
    ('saude', 'Saúde Animal e Veterinária', 'Controle clínico, vacinas, vermifugação, exames e histórico médico de cada animal.',
     'Calendário de vacinas e vermifugação por animal\nExames e histórico médico completo\nVeterinários responsáveis vinculados, que acessam e registram ocorrências\nAlertas do que está para vencer', 'saude', 20),
    ('manejo', 'Tratamento e Manejo', 'Rotinas diárias de cuidados, casqueamento e ferrageamento, medicações em curso e protocolos de recuperação.',
     'Rotina diária de cuidados por animal ou por lote\nAgenda de casqueamento e ferrageamento\nMedicações em curso, com dose e horário\nProtocolos de recuperação acompanhados dia a dia', 'manejo', 30),
    ('nutricao', 'Nutrição e Alimentação', 'Dietas individualizadas, ração, suplementação, volumoso e estoque de insumos alimentares.',
     'Dieta individualizada por animal\nRação, suplementação e volumoso\nEstoque de insumos alimentares com aviso de reposição\nConsumo e custo de alimentação por animal', 'nutricao', 40);

CREATE TABLE IF NOT EXISTS empresa_modulo (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id      BIGINT UNSIGNED NOT NULL,
    modulo_id       BIGINT UNSIGNED NOT NULL,
    situacao        ENUM('solicitado','ativo','desativado') NOT NULL DEFAULT 'solicitado',
    solicitado_em   DATETIME        NULL,
    solicitado_por  BIGINT UNSIGNED NULL,
    ativado_em      DATETIME        NULL,
    ativado_por     BIGINT UNSIGNED NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_empresa_modulo (empresa_id, modulo_id),
    CONSTRAINT fk_empresa_modulo_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE,
    CONSTRAINT fk_empresa_modulo_modulo FOREIGN KEY (modulo_id) REFERENCES modulo_sistema (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conferência
SELECT 'V006 ok' AS resultado, (SELECT COUNT(*) FROM plano_plantel) AS faixas, (SELECT COUNT(*) FROM modulo_sistema) AS modulos;
