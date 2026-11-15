-- MinhaTropa — V014: financeiro

CREATE TABLE IF NOT EXISTS categoria_financeira (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id  BIGINT UNSIGNED NULL COMMENT 'NULL = categoria global pré-definida',
    nome        VARCHAR(100) NOT NULL,
    tipo        ENUM('receita','despesa','ambos') NOT NULL DEFAULT 'ambos',
    icone       VARCHAR(30) NULL,
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_catfin_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE,
    INDEX ix_catfin_empresa (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categoria_financeira (empresa_id, nome, tipo) VALUES
    (NULL, 'Ração e alimentação',       'despesa'),
    (NULL, 'Medicamentos e veterinário','despesa'),
    (NULL, 'Ferrageamento',             'despesa'),
    (NULL, 'Mão de obra',               'despesa'),
    (NULL, 'Reprodução (cobrição/sêmen)','despesa'),
    (NULL, 'Venda de animal',           'receita'),
    (NULL, 'Venda de cobertura',        'receita'),
    (NULL, 'Pensão / hospedagem',       'receita'),
    (NULL, 'Transporte',                'despesa'),
    (NULL, 'Manutenção / infraestrutura','despesa'),
    (NULL, 'Impostos e taxas',          'despesa'),
    (NULL, 'Outro',                     'ambos');

CREATE TABLE IF NOT EXISTS lancamento_financeiro (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                    CHAR(36) NOT NULL,
    empresa_id              BIGINT UNSIGNED NOT NULL,
    tipo                    ENUM('receita','despesa') NOT NULL,
    categoria_financeira_id BIGINT UNSIGNED NULL,
    descricao               VARCHAR(255) NOT NULL,
    valor                   DECIMAL(12,2) NOT NULL,
    data_vencimento         DATE NOT NULL,
    data_pagamento          DATE NULL,
    situacao                ENUM('pendente','pago','atrasado','cancelado') NOT NULL DEFAULT 'pendente',
    forma_pagamento         ENUM('dinheiro','pix','cartao','boleto','transferencia','cheque','outro') NULL,
    animal_id               BIGINT UNSIGNED NULL,
    documento               VARCHAR(100) NULL COMMENT 'Nota fiscal, contrato etc.',
    observacoes             TEXT NULL,
    recorrente              TINYINT(1) NOT NULL DEFAULT 0,
    parcela_atual           SMALLINT UNSIGNED NULL,
    parcela_total           SMALLINT UNSIGNED NULL,
    lancamento_pai_id       BIGINT UNSIGNED NULL,
    criado_por              BIGINT UNSIGNED NOT NULL,
    criado_em               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    excluido_em             TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uk_lancfin_uuid (uuid),
    CONSTRAINT fk_lancfin_empresa    FOREIGN KEY (empresa_id)              REFERENCES empresa (id) ON DELETE CASCADE,
    CONSTRAINT fk_lancfin_categoria  FOREIGN KEY (categoria_financeira_id) REFERENCES categoria_financeira (id) ON DELETE SET NULL,
    CONSTRAINT fk_lancfin_animal     FOREIGN KEY (animal_id)               REFERENCES animal (id) ON DELETE SET NULL,
    CONSTRAINT fk_lancfin_pai        FOREIGN KEY (lancamento_pai_id)       REFERENCES lancamento_financeiro (id) ON DELETE SET NULL,
    CONSTRAINT fk_lancfin_criador    FOREIGN KEY (criado_por)              REFERENCES usuario (id),
    INDEX ix_lancfin_venc    (empresa_id, data_vencimento, situacao),
    INDEX ix_lancfin_tipo    (empresa_id, tipo, data_vencimento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'V014 ok' AS resultado;
