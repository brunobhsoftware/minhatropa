-- MinhaTropa — V020: checagem automática de sufixo por pontuação

-- Coluna de confirmação na propriedade
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS sufixo_confirmado TINYINT(1) NOT NULL DEFAULT 0 AFTER sufixo_associacao_id;

-- Conferência
SELECT 'V020 ok' AS resultado;
