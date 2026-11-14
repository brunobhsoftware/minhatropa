-- MinhaTropa — V009: cortesia (liberação gratuita de todos os módulos para propriedades em teste)
--
-- A Administração pode liberar gratuitamente, para um Haras / Fazenda, todos os módulos adicionais e as ferramentas
-- do plano (cobrições, nascimentos, transferências) e, se quiser, o plantel sem limite de animais.
-- A cortesia é só uma marca por cima do que a propriedade contratou: ao retirar, vale de novo a faixa e os módulos
-- que ela tinha. Nada é apagado. Idempotente.

ALTER TABLE empresa
    ADD COLUMN IF NOT EXISTS cortesia TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = tudo liberado gratuitamente (teste)' AFTER licenca_ativa,
    ADD COLUMN IF NOT EXISTS cortesia_plantel TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = cortesia inclui plantel sem limite' AFTER cortesia,
    ADD COLUMN IF NOT EXISTS cortesia_em DATETIME NULL AFTER cortesia_plantel,
    ADD COLUMN IF NOT EXISTS cortesia_por BIGINT UNSIGNED NULL AFTER cortesia_em,
    ADD COLUMN IF NOT EXISTS cortesia_obs VARCHAR(255) NULL COMMENT 'Anotação interna: para quem e por quê' AFTER cortesia_por;

-- Conferência
SELECT 'V009 ok' AS resultado, COUNT(*) AS em_cortesia FROM empresa WHERE cortesia = 1;
