<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use App\Support\Texto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Empresa;

class Animal extends Model
{
    use TemUuid;

    protected $table = 'animal';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    public const ESPECIES = [
        'equino' => 'Equino',
        'asinino' => 'Asinino',
        'muar' => 'Muar',
    ];

    public const CATEGORIAS_POR_ESPECIE = [
        'equino' => [
            'garanhao' => 'Garanhão',
            'matriz' => 'Matriz (Égua)',
            'potro' => 'Potro',
            'potra' => 'Potra',
            'cavalo' => 'Cavalo',
            'egua' => 'Égua',
            'castrado' => 'Castrado',
            'receptora' => 'Receptora',
            'servico' => 'Cavalo de serviço',
            'outro' => 'Outro',
        ],
        'asinino' => [
            'jumento' => 'Jumento',
            'jumenta' => 'Jumenta',
            'jumentinho' => 'Jumentinho',
            'jumentinha' => 'Jumentinha',
            'outro' => 'Outro',
        ],
        'muar' => [
            'burro' => 'Burro',
            'mula' => 'Mula',
            'burro_novo' => 'Burro novo',
            'mula_nova' => 'Mula nova',
            'outro' => 'Outro',
        ],
    ];

    public const CATEGORIAS = [
        'matriz' => 'Matriz', 'garanhao' => 'Garanhão', 'potro' => 'Potro', 'potra' => 'Potra',
        'cavalo' => 'Cavalo', 'egua' => 'Égua', 'castrado' => 'Castrado',
        'receptora' => 'Receptora', 'servico' => 'Cavalo de serviço',
        'jumento' => 'Jumento', 'jumenta' => 'Jumenta', 'jumentinho' => 'Jumentinho', 'jumentinha' => 'Jumentinha',
        'burro' => 'Burro', 'mula' => 'Mula', 'burro_novo' => 'Burro novo', 'mula_nova' => 'Mula nova',
        'outro' => 'Outro',
    ];

    public const PELAGENS = [
        // Branca
        'branca' => 'Branca',
        'branca_pseudo_albino' => 'Branca Pseudo-albino',
        // Preta
        'preta' => 'Preta',
        'preta_maltinta' => 'Preta Maltinta',
        'preta_azeviche' => 'Preta Azeviche',
        // Alazã
        'alaza' => 'Alazã',
        'alaza_amarilha' => 'Alazã Amarilha',
        'alaza_amarilha_escura' => 'Alazã Amarilha Escura',
        'alaza_amarilha_escura_apatacada' => 'Alazã Amarilha Escura Apatacada',
        'alaza_cereja' => 'Alazã Cereja',
        'alaza_acima_de_baia' => 'Alazã Acima de Baia',
        'alaza_tostada_crinalva' => 'Alazã Tostada Crinalva',
        'alaza_clara_bragada' => 'Alazã Clara Bragada',
        // Castanha
        'castanha' => 'Castanha',
        'castanha_clara' => 'Castanha Clara',
        'castanha_escura' => 'Castanha Escura',
        'castanha_zaina' => 'Castanha Zaina',
        'castanha_pinhao' => 'Castanha Pinhão',
        // Baia
        'baia' => 'Baia',
        'baia_clara' => 'Baia Clara',
        'baia_clara_apatacada' => 'Baia Clara Apatacada',
        'baia_palha' => 'Baia Palha',
        'baia_escura_apatacada' => 'Baia Escura Apatacada',
        'baia_encerada' => 'Baia Encerada',
        // Pelo de Rato
        'pelo_de_rato' => 'Pelo de Rato',
        // Tordilha
        'tordilha' => 'Tordilha',
        'tordilha_negra' => 'Tordilha Negra',
        'tordilha_escura_apatacada' => 'Tordilha Escura Apatacada',
        'tordilha_clara_apatacada' => 'Tordilha Clara Apatacada',
        'tordilha_clara' => 'Tordilha Clara',
        'tordilha_ruca' => 'Tordilha Ruça',
        'tordilha_carda_clara' => 'Tordilha Carda Clara',
        'tordilha_carda_escura' => 'Tordilha Carda Escura',
        'tordilha_pedres' => 'Tordilha Pedrês',
        // Rosilha
        'rosilha_castanha' => 'Rosilha Castanha',
        'rosilha_castanha_escura' => 'Rosilha Castanha Escura',
        'rosilha_baia' => 'Rosilha Baia',
        'rosilha_preta' => 'Rosilha Preta',
        // Lobuna
        'lobuna_clara' => 'Lobuna Clara',
        'lobuna_escura' => 'Lobuna Escura',
        // Ruão
        'ruao_claro' => 'Ruão Claro',
        'ruao_escuro' => 'Ruão Escuro',
        // Pampa
        'pampa_de_preto' => 'Pampa de Preto',
        'preto_pampa' => 'Preto Pampa',
        'pampa_de_alaza' => 'Pampa de Alazã',
        'alaza_pampa' => 'Alazã Pampa',
        'pampa_de_castanha' => 'Pampa de Castanha',
        'castanha_pampa' => 'Castanha Pampa',
        'pampa_de_tordilha' => 'Pampa de Tordilha',
        'rosilha_castanha_pampa' => 'Rosilha Castanha Pampa',
        // Apalusa
        'alaza_apalusa' => 'Alazã Apalusa',
        'apalusa_mantada' => 'Apalusa Mantada',
        'tordilha_apalusa_mantada' => 'Tordilha Apalusa Mantada',
        'tostada_mantada' => 'Tostada Mantada',
        'preta_apalusa_mantada' => 'Preta Apalusa Mantada',
        'preta_nevada' => 'Preta Nevada',
        'castanha_nevada' => 'Castanha Nevada',
        'alaza_tostada_persa' => 'Alazã Tostada Persa',
        // Outra
        'outra' => 'Outra (digitar)',
    ];

    /** Pelagens agrupadas para o <select> com <optgroup>. */
    public const PELAGENS_GRUPOS = [
        'Branca' => ['branca', 'branca_pseudo_albino'],
        'Preta' => ['preta', 'preta_maltinta', 'preta_azeviche'],
        'Alazã' => ['alaza', 'alaza_amarilha', 'alaza_amarilha_escura', 'alaza_amarilha_escura_apatacada', 'alaza_cereja', 'alaza_acima_de_baia', 'alaza_tostada_crinalva', 'alaza_clara_bragada'],
        'Castanha' => ['castanha', 'castanha_clara', 'castanha_escura', 'castanha_zaina', 'castanha_pinhao'],
        'Baia' => ['baia', 'baia_clara', 'baia_clara_apatacada', 'baia_palha', 'baia_escura_apatacada', 'baia_encerada'],
        'Pelo de Rato' => ['pelo_de_rato'],
        'Tordilha' => ['tordilha', 'tordilha_negra', 'tordilha_escura_apatacada', 'tordilha_clara_apatacada', 'tordilha_clara', 'tordilha_ruca', 'tordilha_carda_clara', 'tordilha_carda_escura', 'tordilha_pedres'],
        'Rosilha' => ['rosilha_castanha', 'rosilha_castanha_escura', 'rosilha_baia', 'rosilha_preta'],
        'Lobuna' => ['lobuna_clara', 'lobuna_escura'],
        'Ruão' => ['ruao_claro', 'ruao_escuro'],
        'Pampa' => ['pampa_de_preto', 'preto_pampa', 'pampa_de_alaza', 'alaza_pampa', 'pampa_de_castanha', 'castanha_pampa', 'pampa_de_tordilha', 'rosilha_castanha_pampa'],
        'Apalusa' => ['alaza_apalusa', 'apalusa_mantada', 'tordilha_apalusa_mantada', 'tostada_mantada', 'preta_apalusa_mantada', 'preta_nevada', 'castanha_nevada', 'alaza_tostada_persa'],
    ];

    public const SITUACOES = [
        'ativo' => 'Ativo',
        'vendido' => 'Vendido',
        'transferido' => 'Transferido',
        'morto' => 'Morto',
        'emprestado' => 'Emprestado',
        'em_doma' => 'Em doma',
        'reprodutor_externo' => 'Reprodutor externo',
    ];

    public const FORMAS_AQUISICAO = [
        'nascimento' => 'Nascimento no Haras',
        'compra' => 'Compra',
        'doacao' => 'Doação',
        'transferencia' => 'Transferência',
        'outro' => 'Outro',
    ];

    public const ASSOCIACOES = [
        'ABCCMM' => 'ABCCMM — Mangalarga Marchador',
        'ABCCMangalarga' => 'ABCCMangalarga — Mangalarga',
        'ABCCC' => 'ABCCC — Campolina',
        'ABQM' => 'ABQM — Quarto de Milha',
        'ABCCC_Crioulo' => 'ABCCC — Crioulo',
        'ABPSL' => 'ABPSL — Lusitano',
        'JCB' => 'JCB — Puro Sangue Inglês',
        'ABCCH' => 'ABCCH — Brasileiro de Hipismo',
        'ABCCA' => 'ABCCA — Árabe',
        'APHA' => 'APHA — Paint Horse',
        'ABCA' => 'ABCA — Appaloosa',
        'outra' => 'Outra',
    ];

    public const CAMPOS = [
        'nome', 'nome_registro', 'apelido', 'especie', 'sexo', 'categoria', 'raca',
        'pelagem', 'pelagem_complemento', 'data_nascimento',
        'local_nascimento', 'criador_nome', 'criador_cidade', 'criador_uf',
        'proprietario_nome', 'proprietario_cidade', 'proprietario_uf',
        'registro', 'registro_associacao', 'id_associacao', 'registro_definitivo', 'microchip', 'dna_coletado',
        'numero_plantel', 'local_baia', 'peso_kg', 'altura_cm', 'castrado',
        'pai', 'pai_id', 'pai_referencia_id', 'pai_registro',
        'mae', 'mae_id', 'mae_referencia_id', 'mae_registro',
        'data_aquisicao', 'forma_aquisicao', 'valor_aquisicao',
        'situacao', 'vivo', 'bloqueado', 'situacao_data', 'situacao_motivo',
        'morte_data', 'morte_causa', 'morte_laudo',
        'foto_principal', 'observacoes',
    ];

    protected $fillable = [...self::CAMPOS, 'empresa_id', 'criado_por'];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'data_aquisicao' => 'date',
            'situacao_data' => 'date',
            'morte_data' => 'date',
            'registro_definitivo' => 'boolean',
            'dna_coletado' => 'boolean',
            'castrado' => 'boolean',
            'valor_aquisicao' => 'decimal:2',
            'peso_kg' => 'decimal:1',
            'altura_cm' => 'decimal:1',
        ];
    }

    // Nomes do animal sempre em MAIÚSCULAS (padrão do sistema), qualquer que seja a origem do cadastro.
    public function setNomeAttribute($valor): void
    {
        $this->attributes['nome'] = Texto::maiusculas($valor);
    }

    public function setNomeRegistroAttribute($valor): void
    {
        $this->attributes['nome_registro'] = Texto::maiusculas($valor);
    }

    public function setApelidoAttribute($valor): void
    {
        $this->attributes['apelido'] = Texto::maiusculas($valor);
    }

    public function idade(): string
    {
        if (! $this->data_nascimento) {
            return '—';
        }
        $d = $this->data_nascimento->diff(now());

        return $d->y > 0 ? $d->y.' ano'.($d->y > 1 ? 's' : '') : $d->m.' mes'.($d->m === 1 ? '' : 'es');
    }

    /**
     * Promove potro/potra com mais de 36 meses para a categoria adulta.
     * Macho: Garanhão (se tem registro) ou Cavalo.  Fêmea: Égua.
     * Retorna true se promoveu.
     */
    public function promoverSeAdulto(): bool
    {
        if (! $this->data_nascimento || ! in_array($this->categoria, ['potro', 'potra'], true)) {
            return false;
        }
        $meses = (int) $this->data_nascimento->diffInMonths(now());
        if ($meses < 36) {
            return false;
        }
        if ($this->categoria === 'potro') {
            $this->categoria = $this->registro ? 'garanhao' : 'cavalo';
        } else {
            $this->categoria = 'egua';
        }
        $this->save();

        return true;
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function paiAnimal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'pai_id');
    }

    public function maeAnimal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mae_id');
    }

    public function paiReferencia(): BelongsTo
    {
        return $this->belongsTo(AnimalReferencia::class, 'pai_referencia_id');
    }

    public function maeReferencia(): BelongsTo
    {
        return $this->belongsTo(AnimalReferencia::class, 'mae_referencia_id');
    }

    public function filhos(): HasMany
    {
        return $this->hasMany(self::class, 'pai_id');
    }

    public function filhosMae(): HasMany
    {
        return $this->hasMany(self::class, 'mae_id');
    }

    public function cobricoesEgua(): HasMany
    {
        return $this->hasMany(Cobricao::class, 'egua_id');
    }

    public function cobricoesGaranhao(): HasMany
    {
        return $this->hasMany(Cobricao::class, 'garanhao_id');
    }

    public function nascimentos(): HasMany
    {
        return $this->hasMany(Nascimento::class, 'animal_id');
    }

    public function nomePai(): string
    {
        return $this->paiAnimal?->nome ?? $this->pai ?? '—';
    }

    public function nomeMae(): string
    {
        return $this->maeAnimal?->nome ?? $this->mae ?? '—';
    }

    public function especieTexto(): string
    {
        return self::ESPECIES[$this->especie] ?? $this->especie ?? '—';
    }

    public function categoriaTexto(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria ?? '—';
    }

    public function pelagemTexto(): string
    {
        // Pelagem "Outra": o que vale é o que a pessoa digitou.
        if ($this->pelagem === 'outra' && $this->pelagem_complemento) {
            return $this->pelagem_complemento;
        }
        $p = self::PELAGENS[$this->pelagem] ?? $this->pelagem ?? '';
        if ($this->pelagem_complemento) {
            $p .= ' ('.$this->pelagem_complemento.')';
        }

        return $p ?: '—';
    }

    public function situacaoTexto(): string
    {
        return self::SITUACOES[$this->situacao] ?? $this->situacao ?? 'Ativo';
    }

    public function arvoreGenealogica(int $geracoes = 3): array
    {
        if ($geracoes <= 0) {
            return [];
        }

        $arvore = ['animal' => $this];

        $pai = $this->pai_id ? self::find($this->pai_id) : null;
        $mae = $this->mae_id ? self::find($this->mae_id) : null;

        $arvore['pai'] = $pai ? $pai->arvoreGenealogica($geracoes - 1) + ['animal' => $pai] : ['nome' => $this->pai];
        $arvore['mae'] = $mae ? $mae->arvoreGenealogica($geracoes - 1) + ['animal' => $mae] : ['nome' => $this->mae];

        return $arvore;
    }
}
