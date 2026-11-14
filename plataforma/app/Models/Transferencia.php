<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transferencia extends Model
{
    use TemUuid;

    protected $table = 'transferencia';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    public const TIPOS = [
        'venda' => 'Venda',
        'doacao' => 'Doação',
        'emprestimo' => 'Empréstimo',
        'devolucao' => 'Devolução',
        'outro' => 'Outro',
    ];

    public const SITUACOES = [
        'solicitada' => 'Solicitada',
        'aceita' => 'Aceita',
        'recusada' => 'Recusada',
        'cancelada' => 'Cancelada',
        'concluida' => 'Concluída',
    ];

    public const CAMPOS = [
        'animal_id', 'empresa_origem_id', 'empresa_destino_id', 'destino_externo',
        'tipo', 'valor', 'data_transferencia', 'situacao',
        'solicitado_por', 'respondido_por', 'respondido_em', 'motivo_recusa', 'observacoes',
    ];

    protected $fillable = [...self::CAMPOS, 'criado_por'];

    protected function casts(): array
    {
        return [
            'data_transferencia' => 'date',
            'respondido_em' => 'datetime',
            'valor' => 'decimal:2',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_origem_id');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_destino_id');
    }

    public function nomeDestino(): string
    {
        return $this->destino?->nome ?? $this->destino_externo ?? '—';
    }

    public function tipoTexto(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo ?? '—';
    }

    public function situacaoTexto(): string
    {
        return self::SITUACOES[$this->situacao] ?? $this->situacao ?? '—';
    }
}
