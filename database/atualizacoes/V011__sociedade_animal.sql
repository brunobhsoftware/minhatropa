-- MinhaTropa — V011: sociedade do animal (sócios e percentuais)
--
-- Um animal pode ser 100% da propriedade onde está cadastrado ou ter sócios: pessoas (CPF, base de Profissionais)
-- e outras propriedades (CNPJ, Haras / Fazenda). Cada linha é um sócio com o seu percentual; a parte da propriedade
-- dona do cadastro é o que sobra até 100%. O sócio é identificado pelo documento: quando ele se cadastra no
-- MinhaTropa (pelo link de convite ou por conta própria), passa a ver o animal sem nenhum ajuste aqui. Idempotente.

CREATE TABLE IF NOT EXISTS animal_socio (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    animal_id       BIGINT UNSIGNED NOT NULL,
    tipo_documento  ENUM('cpf','cnpj') NOT NULL,
    documento       VARCHAR(14)     NOT NULL COMMENT 'CPF ou CNPJ do sócio, só dígitos',
    nome            VARCHAR(150)    NOT NULL COMMENT 'Nome informado no lançamento (vale enquanto o sócio não se cadastra)',
    percentual      DECIMAL(5,2)    NOT NULL,
    convite_token   CHAR(40)        NOT NULL COMMENT 'Link de convite para o sócio se cadastrar',
    criado_por      BIGINT UNSIGNED NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_animal_socio (animal_id, documento),
    UNIQUE KEY uk_animal_socio_token (convite_token),
    KEY ix_animal_socio_documento (documento),
    CONSTRAINT fk_animal_socio_animal FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conferência
SELECT 'V011 ok' AS resultado, COUNT(*) AS socios FROM animal_socio;
