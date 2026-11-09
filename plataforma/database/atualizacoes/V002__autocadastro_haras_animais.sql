-- MinhaTropa — V002: autocadastro do Haras pelo site, licença do Haras e cadastro de animais
--
-- Regra comercial (definida pelo Bruno em 09/11/2026): o próprio Haras se cadastra pelo site e usa grátis
-- até cadastrar o 1º animal. A partir do 2º animal precisa contratar a solução; aí as demais ferramentas são liberadas.
-- Idempotente.

ALTER TABLE empresa ADD COLUMN IF NOT EXISTS quantidade_animais_estimada VARCHAR(30) NULL AFTER area_hectares;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS origem ENUM('admin','site') NOT NULL DEFAULT 'admin' AFTER observacoes;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS licenca_ativa TINYINT(1) NOT NULL DEFAULT 0 AFTER situacao_motivo;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS licenca_liberada_em DATETIME NULL AFTER licenca_ativa;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS licenca_liberada_por BIGINT UNSIGNED NULL AFTER licenca_liberada_em;

CREATE TABLE IF NOT EXISTS animal (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid             CHAR(36)        NOT NULL,
    empresa_id       BIGINT UNSIGNED NOT NULL,
    nome             VARCHAR(120)    NOT NULL,
    sexo             ENUM('macho','femea') NULL,
    categoria        ENUM('matriz','garanhao','potro','potra','cavalo','egua','castrado','outro') NULL,
    raca             VARCHAR(80)     NULL,
    pelagem          VARCHAR(80)     NULL,
    data_nascimento  DATE            NULL,
    registro         VARCHAR(60)     NULL COMMENT 'Registro na associação da raça',
    microchip        VARCHAR(40)     NULL,
    pai              VARCHAR(120)    NULL,
    mae              VARCHAR(120)    NULL,
    observacoes      TEXT            NULL,
    criado_por       BIGINT UNSIGNED NULL,
    criado_em        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    excluido_em      DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_animal_uuid (uuid),
    KEY ix_animal_empresa (empresa_id, excluido_em),
    CONSTRAINT fk_animal_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO parametro_sistema (chave, valor, descricao)
VALUES ('animais_gratis', '1', 'Quantos animais um Haras sem licença pode cadastrar (período gratuito)');

-- Conferência
SELECT 'V002 ok' AS resultado, (SELECT COUNT(*) FROM animal) AS animais;
