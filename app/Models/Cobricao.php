<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cobricao extends Model
{
    use TemUuid;

    protected $table = 'cobricao';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    public const TIPOS = [
        'monta_natural' => 'Monta natural',
        'inseminacao_artificial' => 'Inseminação artificial',
        'transferencia_embriao' => 'Transferência de embrião',
    ];

    public const SUBTIPOS = [
        'semen_fresco' => 'Sêmen fresco',
        'semen_refrigerado' => 'Sêmen refrigerado',
        'semen_congelado' => 'Sêmen congelado',
        'embriao_fresco' => 'Embrião fresco',
        'embriao_congelado' => 'Embrião congelado',
    ];

    public const RESULTADOS = [
        'pendente' => 'Pendente',
        'confirmada' => 'Confirmada (prenha)',
        'nao_confirmada' => 'Não confirmada',
        'absorvida' => 'Absorvida',
        'abortada' => 'Abortada',
        'nascimento' => 'Nascimento',
    ];

    public const CAMPOS = [
        'egua_id', 'garanhao_id', 'garanhao_nome', 'receptora_id',
        'tipo', 'subtipo', 'data_cobricao', 'data_confirmacao', 'data_prevista_parto',
        'veterinario_id', 'local', 'resultado', 'observacoes',
    ];

    protected $fillable = [...self::CAMPOS, 'empresa_id', 'criado_por'];

    protected function casts(): array
    {
        return [
            'data_cobricao' => 'date',
            'data_confirmacao' => 'date',
            'data_prevista_parto' => 'date',
        ];
    }

    public function egua(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'egua_id');
    }

    public function garanhao(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'garanhao_id');
    }

    public function receptora(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'receptora_id');
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'veterinario_id');
    }

    public function nascimento(): HasOne
    {
        return $this->hasOne(Nascimento::class, 'cobricao_id');
    }

    public function nomeGaranhao(): string
    {
        return $this->garanhao?->nome ?? $this->garanhao_nome ?? '—';
    }

    public function tipoTexto(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo ?? '—';
    }

    public function resultadoTexto(): string
    {
        return self::RESULTADOS[$this->resultado] ?? $this->resultado ?? '—';
    }
}
