-- MinhaTropa — V004: cadastro de profissionais e vínculo com Haras
--
-- Base única de profissionais da plataforma (veterinários, tratadores, peões, etc.).
-- O proprietário do Haras pode vincular um profissional à sua propriedade e dar acesso ao sistema.
-- O administrador pode ativar, desativar ou banir profissionais.
-- Idempotente.

CREATE TABLE IF NOT EXISTS profissional (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid              CHAR(36)        NOT NULL,
    nome              VARCHAR(150)    NOT NULL,
    cpf               VARCHAR(14)     NOT NULL COMMENT 'Só dígitos, único',
    email             VARCHAR(190)    NULL,
    telefone          VARCHAR(30)     NULL COMMENT 'Celular principal',
    telefone_fixo     VARCHAR(30)     NULL,
    whatsapp          VARCHAR(30)     NULL,
    data_nascimento   DATE            NULL,
    cep               VARCHAR(9)      NULL,
    logradouro        VARCHAR(190)    NULL,
    numero            VARCHAR(20)     NULL,
    complemento       VARCHAR(100)    NULL,
    bairro            VARCHAR(100)    NULL,
    cidade            VARCHAR(100)    NULL,
    uf                CHAR(2)         NULL,
    crmv              VARCHAR(30)     NULL COMMENT 'Registro no CRMV (veterinários)',
    descricao         TEXT            NULL COMMENT 'Experiência, qualificações',
    situacao          ENUM('ativo','inativo','banido') NOT NULL DEFAULT 'ativo',
    situacao_motivo   VARCHAR(255)    NULL COMMENT 'Motivo de inativação ou banimento',
    criado_por        BIGINT UNSIGNED NULL,
    criado_em         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em     DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    excluido_em       DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_profissional_uuid (uuid),
    UNIQUE KEY uk_profissional_cpf (cpf),
    KEY ix_profissional_nome (nome),
    KEY ix_profissional_situacao (situacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profissional_especialidade (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    profissional_id   BIGINT UNSIGNED NOT NULL,
    especialidade     VARCHAR(60)     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_prof_espec (profissional_id, especialidade),
    CONSTRAINT fk_prof_espec_prof FOREIGN KEY (profissional_id) REFERENCES profissional (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empresa_profissional (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id        BIGINT UNSIGNED NOT NULL,
    profissional_id   BIGINT UNSIGNED NOT NULL,
    funcao            VARCHAR(100)    NULL COMMENT 'O que faz neste Haras',
    acesso_sistema    TINYINT(1)      NOT NULL DEFAULT 0,
    usuario_id        BIGINT UNSIGNED NULL COMMENT 'Se tem acesso, qual usuario',
    data_inicio       DATE            NULL,
    data_fim          DATE            NULL,
    situacao          ENUM('ativo','desativado') NOT NULL DEFAULT 'ativo',
    criado_em         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em     DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_emp_prof (empresa_id, profissional_id),
    KEY ix_emp_prof_prof (profissional_id),
    CONSTRAINT fk_emp_prof_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE,
    CONSTRAINT fk_emp_prof_prof FOREIGN KEY (profissional_id) REFERENCES profissional (id) ON DELETE CASCADE,
    CONSTRAINT fk_emp_prof_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conferência
SELECT 'V004 ok' AS resultado;
