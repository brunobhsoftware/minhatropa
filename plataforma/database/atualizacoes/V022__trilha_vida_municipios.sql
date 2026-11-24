-- MinhaTropa — V022: Adicionar municipio_id para criador e proprietário no cadastro do animal

ALTER TABLE animal ADD COLUMN IF NOT EXISTS criador_municipio_id INT NULL AFTER criador_uf;
ALTER TABLE animal ADD COLUMN IF NOT EXISTS proprietario_municipio_id INT NULL AFTER proprietario_uf;
