-- MinhaTropa — V008: página pública de cada Haras / Fazenda (logomarca, capa, cores e apresentação)
--
-- Cada propriedade pode ter uma página simples em minhatropa.com.br/h/<endereço> (empresa.subsite_slug, do V003),
-- com a logomarca, uma imagem de capa, a cor principal e a cor de fundo escolhidas pelo proprietário,
-- um texto de apresentação e os contatos. Tudo opcional; a página só aparece com pagina_ativa = 1.
-- As imagens ficam no disco "midia" (fora do public_html). Idempotente.

ALTER TABLE empresa
    ADD COLUMN IF NOT EXISTS logo_arquivo VARCHAR(255) NULL COMMENT 'Logomarca do Haras / Fazenda (disco midia)' AFTER marca_arquivo,
    ADD COLUMN IF NOT EXISTS capa_arquivo VARCHAR(255) NULL COMMENT 'Imagem de capa da página (disco midia)' AFTER logo_arquivo,
    ADD COLUMN IF NOT EXISTS cor_primaria CHAR(7) NULL COMMENT 'Cor principal da página, #RRGGBB' AFTER capa_arquivo,
    ADD COLUMN IF NOT EXISTS cor_fundo CHAR(7) NULL COMMENT 'Cor de fundo da página, #RRGGBB' AFTER cor_primaria,
    ADD COLUMN IF NOT EXISTS pagina_ativa TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = página pública no ar' AFTER subsite_slug,
    ADD COLUMN IF NOT EXISTS pagina_slogan VARCHAR(150) NULL AFTER pagina_ativa,
    ADD COLUMN IF NOT EXISTS pagina_apresentacao TEXT NULL AFTER pagina_slogan,
    ADD COLUMN IF NOT EXISTS pagina_mostrar_contato TINYINT(1) NOT NULL DEFAULT 1 AFTER pagina_apresentacao,
    ADD COLUMN IF NOT EXISTS pagina_mostrar_endereco TINYINT(1) NOT NULL DEFAULT 1 AFTER pagina_mostrar_contato,
    ADD COLUMN IF NOT EXISTS instagram VARCHAR(100) NULL AFTER site_url,
    ADD COLUMN IF NOT EXISTS facebook VARCHAR(255) NULL AFTER instagram;

-- Conferência
SELECT 'V008 ok' AS resultado, COUNT(*) AS paginas_no_ar FROM empresa WHERE pagina_ativa = 1;
