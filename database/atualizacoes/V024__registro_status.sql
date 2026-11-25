-- MinhaTropa — V024: Status do registro na associação (sem_registro / provisorio / definitivo)

ALTER TABLE animal ADD COLUMN IF NOT EXISTS registro_status VARCHAR(20) NOT NULL DEFAULT 'sem_registro' AFTER registro_definitivo;

UPDATE animal
SET registro_status = CASE
    WHEN registro_definitivo = 1 THEN 'definitivo'
    WHEN registro IS NOT NULL AND registro <> '' THEN 'provisorio'
    ELSE 'sem_registro'
END
WHERE registro_status = 'sem_registro';
