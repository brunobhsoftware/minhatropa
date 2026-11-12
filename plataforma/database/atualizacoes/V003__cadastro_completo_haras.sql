-- MinhaTropa — V003: cadastro completo do Haras (tipo de propriedade, criação, marchas, marca do criador, subsite, coordenadas)
--
-- Amplia o cadastro do Haras com dados inspirados no sistema da ABCCMM:
-- tipo de propriedade, raças criadas, preferências de marcha, finalidade,
-- endereço do proprietário (separado da propriedade), coordenadas geográficas,
-- marca/logomarca do criador, domínio/subsite e dados complementares.
-- Idempotente.

-- Tipo de propriedade
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS tipo_propriedade ENUM('haras','fazenda','criatorio','sitio','estancia','chacara','outro') NULL AFTER nome;

-- Finalidade da criação (pode ter mais de uma)
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS finalidades_criacao VARCHAR(500) NULL COMMENT 'Separadas por vírgula: criacao,esporte,lazer,trabalho,exposicao,venda' AFTER area_hectares;

-- Preferências de marcha
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS preferencias_marcha VARCHAR(300) NULL COMMENT 'Separadas por vírgula: batida,picada,centro,trote,galope' AFTER finalidades_criacao;

-- Coordenadas geográficas da propriedade
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS latitude DECIMAL(15,12) NULL AFTER uf;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS longitude DECIMAL(15,12) NULL AFTER latitude;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS como_chegar TEXT NULL COMMENT 'Descrição de como chegar à propriedade' AFTER longitude;

-- Endereço do proprietário (correspondência, separado da propriedade)
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS corresp_cep VARCHAR(9) NULL AFTER como_chegar;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS corresp_logradouro VARCHAR(190) NULL AFTER corresp_cep;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS corresp_numero VARCHAR(20) NULL AFTER corresp_logradouro;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS corresp_complemento VARCHAR(100) NULL AFTER corresp_numero;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS corresp_bairro VARCHAR(100) NULL AFTER corresp_complemento;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS corresp_cidade VARCHAR(100) NULL AFTER corresp_bairro;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS corresp_uf CHAR(2) NULL AFTER corresp_cidade;

-- Telefones detalhados do proprietário
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS telefone_residencial VARCHAR(30) NULL AFTER responsavel_telefone;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS telefone_celular VARCHAR(30) NULL AFTER telefone_residencial;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS telefone_comercial VARCHAR(30) NULL AFTER telefone_celular;

-- Marca do criador e subsite
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS marca_descricao VARCHAR(500) NULL COMMENT 'Descrição da marca ou ferro do criador' AFTER observacoes;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS marca_arquivo VARCHAR(255) NULL COMMENT 'Caminho da imagem da marca/logomarca' AFTER marca_descricao;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS site_url VARCHAR(255) NULL COMMENT 'Site ou domínio próprio do Haras' AFTER marca_arquivo;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS subsite_slug VARCHAR(60) NULL COMMENT 'Slug para subsite: minhatropa.com.br/h/slug' AFTER site_url;

-- Slug único (só entre os não excluídos)
-- MariaDB não suporta partial unique index, então usamos uma trigger ou conferência no app
-- Para garantir unicidade no nível do banco:
CREATE UNIQUE INDEX IF NOT EXISTS uk_empresa_subsite ON empresa (subsite_slug);

-- Tabela de raças que o Haras cria (muitos-para-muitos)
CREATE TABLE IF NOT EXISTS empresa_raca (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id  BIGINT UNSIGNED NOT NULL,
    raca        VARCHAR(80)     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_empresa_raca (empresa_id, raca),
    CONSTRAINT fk_empresa_raca_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conferência
SELECT 'V003 ok' AS resultado;
