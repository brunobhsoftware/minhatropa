<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
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
        'alaza' => 'Alazã',
        'alaza_queimada' => 'Alazã-queimada',
        'baia' => 'Baia',
        'baia_amarilha' => 'Baia-amarilha',
        'baia_cereja' => 'Baia-cereja',
        'baia_escura' => 'Baia-escura',
        'branca' => 'Branca',
        'castanha' => 'Castanha',
        'castanha_escura' => 'Castanha-escura',
        'lobuna' => 'Lobuna',
        'pampa_alaza' => 'Pampa de alazã',
        'pampa_baia' => 'Pampa de baia',
        'pampa_castanha' => 'Pampa de castanha',
        'pampa_preta' => 'Pampa de preta',
        'preta' => 'Preta',
        'rosilha' => 'Rosilha',
        'ruca' => 'Ruça',
        'tordilha' => 'Tordilha',
        'zaina' => 'Zaina',
        'outra' => 'Outra',
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
        'registro', 'registro_associacao', 'registro_definitivo', 'microchip', 'dna_coletado',
        'numero_plantel', 'local_baia', 'peso_kg', 'altura_cm', 'castrado',
        'pai', 'pai_id', 'mae', 'mae_id',
        'data_aquisicao', 'forma_aquisicao', 'valor_aquisicao',
        'situacao', 'situacao_data', 'situacao_motivo',
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

    public function idade(): string
    {
        if (! $this->data_nascimento) {
            return '—';
        }
        $d = $this->data_nascimento->diff(now());

        return $d->y > 0 ? $d->y.' ano'.($d->y > 1 ? 's' : '') : $d->m.' mes'.($d->m === 1 ? '' : 'es');
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
