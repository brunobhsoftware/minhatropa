-- MinhaTropa — V015: infraestrutura de pagamentos (Mercado Pago) e fila de e-mails
-- Tabelas para integração com gateway de pagamento, webhooks e envio de e-mails pela plataforma.
-- Idempotente. Não consulta information_schema.

-- Gateways de pagamento disponíveis
CREATE TABLE IF NOT EXISTS gateway (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(30) NOT NULL,
  nome VARCHAR(60) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_gateway_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO gateway (codigo, nome) VALUES ('mercadopago', 'Mercado Pago');

-- Conta de pagamento por propriedade (credenciais da aplicação MP própria da empresa, ou plataforma)
CREATE TABLE IF NOT EXISTS conta_pagamento (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid CHAR(36) NOT NULL,
  empresa_id BIGINT UNSIGNED NOT NULL,
  gateway_id BIGINT UNSIGNED NOT NULL,
  modo ENUM('plataforma','propria') NOT NULL DEFAULT 'plataforma'
      COMMENT 'plataforma = conta da BH Software com repasse; propria = aplicação MP da própria empresa',
  status ENUM('pendente','conectada','ativa','suspensa','revogada') NOT NULL DEFAULT 'pendente',
  ambiente ENUM('teste','producao') NOT NULL DEFAULT 'producao',
  email_conta VARCHAR(150) NULL,
  -- OAuth / tokens (criptografados com APP_KEY)
  access_token_cript VARBINARY(2048) NULL,
  refresh_token_cript VARBINARY(2048) NULL,
  token_expira_em DATETIME NULL,
  -- Aplicação própria da empresa
  client_id VARCHAR(100) NULL,
  client_secret_cript VARBINARY(1024) NULL,
  public_key VARCHAR(255) NULL,
  webhook_secret_cript VARBINARY(1024) NULL,
  -- Credenciais de teste
  teste_access_token_cript VARBINARY(2048) NULL,
  teste_client_id VARCHAR(100) NULL,
  teste_client_secret_cript VARBINARY(1024) NULL,
  teste_public_key VARCHAR(255) NULL,
  teste_webhook_secret_cript VARBINARY(1024) NULL,
  -- Datas
  conectado_em DATETIME NULL,
  desconectado_em DATETIME NULL,
  ultimo_erro VARCHAR(500) NULL,
  ultimo_erro_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_conta_pagamento_uuid (uuid),
  KEY ix_conta_pagamento_empresa (empresa_id),
  CONSTRAINT fk_conta_pagamento_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id),
  CONSTRAINT fk_conta_pagamento_gateway FOREIGN KEY (gateway_id) REFERENCES gateway (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagamentos individuais (assinaturas, faixas, módulos)
CREATE TABLE IF NOT EXISTS pagamento (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid CHAR(36) NOT NULL,
  empresa_id BIGINT UNSIGNED NOT NULL,
  gateway_id BIGINT UNSIGNED NULL,
  conta_pagamento_id BIGINT UNSIGNED NULL,
  gateway_pedido_id VARCHAR(150) NULL COMMENT 'ID da order no gateway',
  gateway_pagamento_id VARCHAR(150) NULL COMMENT 'ID do pagamento no gateway',
  descricao VARCHAR(255) NULL,
  valor DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status ENUM('pendente','aguardando_pagamento','aprovado','rejeitado','cancelado','reembolsado','estorno_parcial','expirado') NOT NULL DEFAULT 'pendente',
  ambiente ENUM('teste','producao') NOT NULL DEFAULT 'producao',
  metodo_pagamento VARCHAR(60) NULL,
  parcelas TINYINT UNSIGNED NULL,
  dados_gateway TEXT NULL COMMENT 'JSON com dados adicionais do gateway',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pagamento_uuid (uuid),
  KEY ix_pagamento_empresa (empresa_id),
  KEY ix_pagamento_gateway_pedido (gateway_pedido_id),
  KEY ix_pagamento_gateway_pgto (gateway_pagamento_id),
  CONSTRAINT fk_pagamento_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id),
  CONSTRAINT fk_pagamento_gateway FOREIGN KEY (gateway_id) REFERENCES gateway (id),
  CONSTRAINT fk_pagamento_conta FOREIGN KEY (conta_pagamento_id) REFERENCES conta_pagamento (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eventos de webhook recebidos dos gateways
CREATE TABLE IF NOT EXISTS webhook_evento (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  gateway_id BIGINT UNSIGNED NULL,
  conta_pagamento_id BIGINT UNSIGNED NULL,
  id_evento_externo VARCHAR(150) NOT NULL,
  tipo VARCHAR(60) NULL,
  acao VARCHAR(60) NULL,
  recurso_id VARCHAR(100) NULL,
  request_id VARCHAR(100) NULL,
  cabecalhos TEXT NULL COMMENT 'JSON dos cabeçalhos relevantes',
  corpo TEXT NULL COMMENT 'JSON do corpo recebido',
  assinatura_valida TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('recebido','processado','ignorado','erro') NOT NULL DEFAULT 'recebido',
  erro VARCHAR(255) NULL,
  pagamento_id BIGINT UNSIGNED NULL,
  processado_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_webhook_evento_externo (id_evento_externo),
  KEY ix_webhook_evento_gateway (gateway_id),
  KEY ix_webhook_evento_recurso (recurso_id),
  CONSTRAINT fk_webhook_evento_gateway FOREIGN KEY (gateway_id) REFERENCES gateway (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fila de e-mails enviados pela plataforma (prova do que foi enviado e diagnóstico de falhas)
CREATE TABLE IF NOT EXISTS mensagem_envio (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NULL COMMENT 'Propriedade relacionada, quando aplicável',
  tipo VARCHAR(40) NOT NULL COMMENT 'Tipo do e-mail: senha, cadastro, aviso, etc.',
  canal VARCHAR(20) NOT NULL DEFAULT 'email',
  destinatario VARCHAR(150) NOT NULL,
  assunto VARCHAR(150) NOT NULL,
  corpo_renderizado MEDIUMTEXT NULL COMMENT 'HTML enviado (tokens de segurança removidos)',
  status ENUM('pendente','enviando','enviado','erro') NOT NULL DEFAULT 'pendente',
  tentativas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  erro VARCHAR(500) NULL,
  provedor_msg_id VARCHAR(150) NULL,
  prioridade TINYINT UNSIGNED NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  enviado_em DATETIME NULL,
  PRIMARY KEY (id),
  KEY ix_mensagem_envio_empresa (empresa_id),
  KEY ix_mensagem_envio_status (status),
  KEY ix_mensagem_envio_dest (destinatario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'V015 ok' AS resultado;
