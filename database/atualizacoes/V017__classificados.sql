-- MinhaTropa — V017: classificados (animais à venda)
--
-- A propriedade marca um animal do plantel como "à venda", com preço e período. Enquanto o anúncio está no ar
-- (situação ativo, dentro do período), o animal aparece na página pública de classificados do site.
-- Um animal tem no máximo um anúncio aberto por vez (regra na aplicação). Idempotente.

CREATE TABLE IF NOT EXISTS classificado (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid              CHAR(36)        NOT NULL,
    empresa_id        BIGINT UNSIGNED NOT NULL,
    animal_id         BIGINT UNSIGNED NOT NULL,
    preco             DECIMAL(12,2)   NOT NULL,
    descricao         TEXT            NULL,
    inicio            DATE            NOT NULL COMMENT 'Primeiro dia no ar',
    fim               DATE            NULL COMMENT 'Último dia no ar; vazio = sem prazo',
    situacao          ENUM('ativo','encerrado','vendido') NOT NULL DEFAULT 'ativo',
    contato_nome      VARCHAR(120)    NULL,
    contato_whatsapp  VARCHAR(20)     NULL COMMENT 'Só dígitos, com DDD',
    encerrado_em      DATETIME        NULL,
    criado_por        BIGINT UNSIGNED NULL,
    criado_em         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em     DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    excluido_em       DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_classificado_uuid (uuid),
    KEY ix_classificado_empresa (empresa_id, situacao),
    KEY ix_classificado_animal (animal_id),
    KEY ix_classificado_no_ar (situacao, inicio, fim),
    CONSTRAINT fk_classificado_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id),
    CONSTRAINT fk_classificado_animal FOREIGN KEY (animal_id) REFERENCES animal (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conferência
SELECT 'V017 ok' AS resultado, COUNT(*) AS anuncios FROM classificado;
