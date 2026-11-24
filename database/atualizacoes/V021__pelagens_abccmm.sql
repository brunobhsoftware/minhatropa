-- MinhaTropa — V021: migração das chaves de pelagem para o catálogo ABCCMM (58 pelagens)
-- Converte chaves antigas para as novas, sem perder dados.

UPDATE animal SET pelagem = 'preta_maltinta'      WHERE pelagem = 'alaza_queimada';
UPDATE animal SET pelagem = 'baia_clara'           WHERE pelagem = 'baia_amarilha';
UPDATE animal SET pelagem = 'alaza_cereja'         WHERE pelagem = 'baia_cereja';
UPDATE animal SET pelagem = 'baia_escura_apatacada' WHERE pelagem = 'baia_escura';
UPDATE animal SET pelagem = 'lobuna_clara'         WHERE pelagem = 'lobuna';
UPDATE animal SET pelagem = 'rosilha_castanha'     WHERE pelagem = 'rosilha';
UPDATE animal SET pelagem = 'tordilha_ruca'        WHERE pelagem = 'ruca';
UPDATE animal SET pelagem = 'castanha_zaina'       WHERE pelagem = 'zaina';
UPDATE animal SET pelagem = 'pampa_de_alaza'       WHERE pelagem = 'pampa_alaza';
UPDATE animal SET pelagem = 'pampa_de_castanha'    WHERE pelagem = 'pampa_castanha';
UPDATE animal SET pelagem = 'pampa_de_preto'       WHERE pelagem = 'pampa_preta';
UPDATE animal SET pelagem = 'outra', pelagem_complemento = IFNULL(NULLIF(pelagem_complemento, ''), 'Pampa de baia') WHERE pelagem = 'pampa_baia';

SELECT 'V021 ok' AS resultado;
