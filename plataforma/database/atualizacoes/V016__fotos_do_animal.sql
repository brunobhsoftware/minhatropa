-- MinhaTropa — V016: fotos do animal (até 3 por animal)
--
-- Cada animal pode ter até 3 fotos, uma por posição (1, 2, 3). O arquivo NÃO fica no banco: fica na pasta de mídia
-- do servidor (disco "midia", fora do public_html) e aqui entra só o caminho. Toda foto é gravada num formato único:
-- retrato 3:4, 900 x 1200, JPEG compactado, seja qual for o tamanho do arquivo enviado. Idempotente.

CREATE TABLE IF NOT EXISTS animal_foto (
    id              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    empresa_id      BIGINT UNSIGNED   NOT NULL,
    animal_id       BIGINT UNSIGNED   NOT NULL,
    posicao         TINYINT UNSIGNED  NOT NULL COMMENT '1 a 3; a 1 é a foto principal',
    arquivo         VARCHAR(255)      NOT NULL COMMENT 'Caminho no disco de mídia',
    largura         SMALLINT UNSIGNED NOT NULL,
    altura          SMALLINT UNSIGNED NOT NULL,
    bytes           INT UNSIGNED      NOT NULL,
    criado_por      BIGINT UNSIGNED   NULL,
    criado_em       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_animal_foto (animal_id, posicao),
    KEY ix_animal_foto_empresa (empresa_id),
    CONSTRAINT fk_animal_foto_animal FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conferência
SELECT 'V016 ok' AS resultado, COUNT(*) AS fotos FROM animal_foto;
