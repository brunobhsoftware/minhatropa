<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nascimento extends Model
{
    use TemUuid;

    protected $table = 'nascimento';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    public const TIPOS_PARTO = [
        'normal' => 'Normal',
        'distocia' => 'Distócia',
        'cesariana' => 'Cesariana',
        'prematuro' => 'Prematuro',
    ];

    public const CAMPOS = [
        'animal_id', 'cobricao_id', 'mae_id', 'pai_id', 'pai_nome',
        'data_nascimento', 'tipo_parto', 'sexo', 'nome_produto',
        'peso_nascimento', 'vivo', 'veterinario_id', 'observacoes',
    ];

    protected $fillable = [...self::CAMPOS, 'empresa_id', 'criado_por'];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'datetime',
            'peso_nascimento' => 'decimal:1',
            'vivo' => 'boolean',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function cobricao(): BelongsTo
    {
        return $this->belongsTo(Cobricao::class, 'cobricao_id');
    }

    public function mae(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'mae_id');
    }

    public function pai(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'pai_id');
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'veterinario_id');
    }

    public function nomePai(): string
    {
        return $this->pai?->nome ?? $this->pai_nome ?? '—';
    }

    public function tipoPartoTexto(): string
    {
        return self::TIPOS_PARTO[$this->tipo_parto] ?? $this->tipo_parto ?? '—';
    }
}
