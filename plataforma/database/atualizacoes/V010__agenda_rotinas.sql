-- MinhaTropa — V010: agenda e rotinas

CREATE TABLE IF NOT EXISTS tipo_rotina (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    empresa_id  BIGINT UNSIGNED NULL,
    nome        VARCHAR(100) NOT NULL,
    descricao   VARCHAR(255) NULL,
    icone       VARCHAR(30) NULL,
    cor         VARCHAR(7) NULL,
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tipo_rotina_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE,
    UNIQUE KEY uk_tipo_rotina_empresa_nome (empresa_id, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO tipo_rotina (empresa_id, nome, descricao, icone, cor) VALUES
    (NULL, 'Alimentação',        'Fornecimento de ração, feno e água',         '🍎', '#4CAF50'),
    (NULL, 'Vacinação',          'Aplicação de vacinas',                        '💉', '#2196F3'),
    (NULL, 'Vermifugação',       'Controle de parasitas internos',              '💊', '#9C27B0'),
    (NULL, 'Ferrageamento',      'Casqueamento e ferrageamento',                '🔧', '#FF9800'),
    (NULL, 'Banho/Higiene',      'Banho, escovação e limpeza',                  '🧼', '#00BCD4'),
    (NULL, 'Exercício',          'Treino, caminhada ou exercício dirigido',      '🏇', '#F44336'),
    (NULL, 'Suplementação',      'Administração de suplementos',                '🧪', '#795548'),
    (NULL, 'Exame veterinário',  'Consulta ou exame clínico',                   '🩺', '#607D8B'),
    (NULL, 'Controle de peso',   'Pesagem periódica',                           '⚖️', '#3F51B5'),
    (NULL, 'Outro',              'Outra rotina não categorizada',               '📋', '#9E9E9E');

CREATE TABLE IF NOT EXISTS rotina (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uuid             CHAR(36) NOT NULL,
    empresa_id       BIGINT UNSIGNED NOT NULL,
    tipo_rotina_id   BIGINT UNSIGNED NULL,
    titulo           VARCHAR(150) NOT NULL,
    descricao        TEXT NULL,
    animal_id        BIGINT UNSIGNED NULL,
    frequencia       ENUM('unica','diaria','semanal','quinzenal','mensal','personalizada') NOT NULL DEFAULT 'unica',
    frequencia_dias  INT UNSIGNED NULL,
    dia_semana       TINYINT UNSIGNED NULL,
    dia_mes          TINYINT UNSIGNED NULL,
    hora_prevista    TIME NULL,
    data_inicio      DATE NOT NULL,
    data_fim         DATE NULL,
    responsavel_id   BIGINT UNSIGNED NULL,
    prioridade       ENUM('baixa','normal','alta','urgente') NOT NULL DEFAULT 'normal',
    ativa            TINYINT(1) NOT NULL DEFAULT 1,
    criado_por       BIGINT UNSIGNED NOT NULL,
    criado_em        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    excluido_em      TIMESTAMP NULL,
    UNIQUE KEY uk_rotina_uuid (uuid),
    INDEX ix_rotina_empresa (empresa_id, ativa, excluido_em),
    CONSTRAINT fk_rotina_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE,
    CONSTRAINT fk_rotina_tipo FOREIGN KEY (tipo_rotina_id) REFERENCES tipo_rotina (id) ON DELETE SET NULL,
    CONSTRAINT fk_rotina_animal FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE SET NULL,
    CONSTRAINT fk_rotina_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuario (id) ON DELETE SET NULL,
    CONSTRAINT fk_rotina_criador FOREIGN KEY (criado_por) REFERENCES usuario (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS execucao_rotina (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36) NOT NULL,
    rotina_id       BIGINT UNSIGNED NOT NULL,
    empresa_id      BIGINT UNSIGNED NOT NULL,
    data_prevista   DATE NOT NULL,
    data_execucao   DATETIME NULL,
    executado_por   BIGINT UNSIGNED NULL,
    situacao        ENUM('pendente','concluida','atrasada','cancelada') NOT NULL DEFAULT 'pendente',
    observacoes     TEXT NULL,
    criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_execucao_uuid (uuid),
    INDEX ix_execucao_agenda (empresa_id, data_prevista, situacao),
    INDEX ix_execucao_rotina (rotina_id, data_prevista),
    CONSTRAINT fk_execucao_rotina FOREIGN KEY (rotina_id) REFERENCES rotina (id) ON DELETE CASCADE,
    CONSTRAINT fk_execucao_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE,
    CONSTRAINT fk_execucao_executor FOREIGN KEY (executado_por) REFERENCES usuario (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'V010 ok' AS resultado;
