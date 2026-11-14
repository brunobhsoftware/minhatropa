<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecucaoRotina extends Model
{
    use TemUuid;

    protected $table = 'execucao_rotina';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;

    public const SITUACOES = [
        'pendente' => 'Pendente',
        'concluida' => 'Concluída',
        'atrasada' => 'Atrasada',
        'cancelada' => 'Cancelada',
    ];

    protected $fillable = [
        'rotina_id', 'empresa_id', 'data_prevista', 'data_execucao',
        'executado_por', 'situacao', 'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_prevista' => 'date',
            'data_execucao' => 'datetime',
        ];
    }

    public function rotina(): BelongsTo
    {
        return $this->belongsTo(Rotina::class, 'rotina_id');
    }

    public function executadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'executado_por');
    }
}
