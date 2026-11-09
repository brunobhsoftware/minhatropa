-- MinhaTropa — V001: criação do banco (administração da plataforma, Haras, usuários, contatos e auditoria)
--
-- Banco de produção: u839930205_minhatropa (MariaDB na Hostinger).
-- Idempotente: pode rodar mais de uma vez (CREATE TABLE IF NOT EXISTS / INSERT IGNORE).
-- Aplicado pela tela /admin/instalar (primeira vez) ou por Administração → Atualizações do banco.
-- Convenções: InnoDB, utf8mb4, datas em UTC, dinheiro DECIMAL(12,2), id BIGINT interno + uuid público,
-- multi-Haras por empresa_id em toda tabela de negócio.

CREATE TABLE IF NOT EXISTS parametro_sistema (
    chave         VARCHAR(120)  NOT NULL,
    valor         TEXT          NULL,
    descricao     VARCHAR(255)  NULL,
    atualizado_em TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuario (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid              CHAR(36)        NOT NULL,
    nome              VARCHAR(150)    NOT NULL,
    email             VARCHAR(190)    NOT NULL,
    telefone          VARCHAR(30)     NULL,
    senha_hash        VARCHAR(255)    NULL,
    admin_plataforma  TINYINT(1)      NOT NULL DEFAULT 0,
    ativo             TINYINT(1)      NOT NULL DEFAULT 1,
    tentativas_login  INT UNSIGNED    NOT NULL DEFAULT 0,
    bloqueado_ate     DATETIME        NULL,
    ultimo_login_em   DATETIME        NULL,
    criado_em         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em     DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_usuario_uuid (uuid),
    UNIQUE KEY uk_usuario_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plano (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid             CHAR(36)        NOT NULL,
    nome             VARCHAR(100)    NOT NULL,
    descricao        VARCHAR(255)    NULL,
    limite_animais   INT UNSIGNED    NULL COMMENT 'NULL = sem limite',
    limite_usuarios  INT UNSIGNED    NULL COMMENT 'NULL = sem limite',
    valor_mensal     DECIMAL(12,2)   NULL,
    ativo            TINYINT(1)      NOT NULL DEFAULT 1,
    ordem            INT             NOT NULL DEFAULT 0,
    criado_em        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_plano_uuid (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cada Haras (fazenda/criatório) cliente da plataforma.
CREATE TABLE IF NOT EXISTS empresa (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid                CHAR(36)        NOT NULL,
    nome                VARCHAR(150)    NOT NULL COMMENT 'Nome do Haras (como aparece no sistema)',
    razao_social        VARCHAR(190)    NULL,
    tipo_documento      ENUM('cnpj','cpf') NULL,
    documento           VARCHAR(20)     NULL COMMENT 'Só dígitos',
    inscricao_estadual  VARCHAR(30)     NULL COMMENT 'Inscrição estadual ou de produtor rural',
    email               VARCHAR(190)    NULL,
    telefone            VARCHAR(30)     NULL,
    whatsapp            VARCHAR(30)     NULL,
    responsavel_nome    VARCHAR(150)    NULL,
    responsavel_email   VARCHAR(190)    NULL,
    responsavel_telefone VARCHAR(30)    NULL,
    cep                 VARCHAR(9)      NULL,
    logradouro          VARCHAR(190)    NULL,
    numero              VARCHAR(20)     NULL,
    complemento         VARCHAR(100)    NULL,
    bairro              VARCHAR(100)    NULL,
    cidade              VARCHAR(100)    NULL,
    uf                  CHAR(2)         NULL,
    area_hectares       DECIMAL(12,2)   NULL,
    plano_id            BIGINT UNSIGNED NULL,
    situacao            ENUM('ativo','teste','suspenso','encerrado') NOT NULL DEFAULT 'teste',
    situacao_motivo     VARCHAR(255)    NULL,
    observacoes         TEXT            NULL,
    criado_por          BIGINT UNSIGNED NULL,
    criado_em           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    excluido_em         DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_empresa_uuid (uuid),
    KEY ix_empresa_documento (documento),
    KEY ix_empresa_situacao (situacao),
    CONSTRAINT fk_empresa_plano FOREIGN KEY (plano_id) REFERENCES plano (id),
    CONSTRAINT fk_empresa_criado_por FOREIGN KEY (criado_por) REFERENCES usuario (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quem acessa cada Haras e com qual papel. Uma pessoa (ex.: o gerente que implanta) pode estar em vários Haras.
CREATE TABLE IF NOT EXISTS empresa_usuario (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id   BIGINT UNSIGNED NOT NULL,
    usuario_id   BIGINT UNSIGNED NOT NULL,
    papel        ENUM('proprietario','gerente','veterinario','treinador','tratador','financeiro','consulta') NOT NULL DEFAULT 'gerente',
    status       ENUM('ativo','convidado','desativado') NOT NULL DEFAULT 'ativo',
    criado_em    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME       NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_empresa_usuario (empresa_id, usuario_id),
    KEY ix_empresa_usuario_usuario (usuario_id),
    CONSTRAINT fk_empresa_usuario_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id),
    CONSTRAINT fk_empresa_usuario_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pedidos de contato/demonstração vindos do site público.
CREATE TABLE IF NOT EXISTS contato (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome        VARCHAR(150)    NOT NULL,
    email       VARCHAR(190)    NULL,
    telefone    VARCHAR(30)     NULL,
    haras       VARCHAR(150)    NULL,
    cidade_uf   VARCHAR(120)    NULL,
    quantidade_animais VARCHAR(30) NULL,
    mensagem    TEXT            NULL,
    origem      VARCHAR(60)     NOT NULL DEFAULT 'site',
    ip          VARCHAR(45)     NULL,
    lido_em     DATETIME        NULL,
    criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_contato_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auditoria (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id   BIGINT UNSIGNED NULL,
    usuario_id   BIGINT UNSIGNED NULL,
    acao         VARCHAR(80)     NOT NULL,
    tabela       VARCHAR(64)     NULL,
    registro_id  BIGINT UNSIGNED NULL,
    antes        JSON            NULL,
    depois       JSON            NULL,
    ip           VARCHAR(45)     NULL,
    criado_em    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_auditoria_empresa (empresa_id, criado_em),
    KEY ix_auditoria_usuario (usuario_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO parametro_sistema (chave, valor, descricao)
VALUES ('plataforma_nome', 'MinhaTropa', 'Nome da plataforma exibido nas telas');

-- Conferência
SELECT 'V001 ok' AS resultado, (SELECT COUNT(*) FROM usuario) AS usuarios, (SELECT COUNT(*) FROM empresa) AS haras;
