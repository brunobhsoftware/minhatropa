<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;

class LancamentoFinanceiro extends Model
{
    use TemUuid;

    protected $table = 'lancamento_financeiro';
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';
    protected $guarded = ['id'];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'recorrente' => 'boolean',
        'criado_em' => 'datetime',
        'atualizado_em' => 'datetime',
        'excluido_em' => 'datetime',
    ];

    const TIPOS = ['receita' => 'Receita', 'despesa' => 'Despesa'];
    const TIPO_CORES = ['receita' => '#10b981', 'despesa' => '#ef4444'];

    const SITUACOES = [
        'pendente' => 'Pendente',
        'pago' => 'Pago',
        'atrasado' => 'Atrasado',
        'cancelado' => 'Cancelado',
    ];
    const SITUACAO_CORES = [
        'pendente' => '#f59e0b',
        'pago' => '#10b981',
        'atrasado' => '#ef4444',
        'cancelado' => '#6b7280',
    ];

    const FORMAS_PAGAMENTO = [
        'dinheiro' => 'Dinheiro',
        'pix' => 'Pix',
        'cartao' => 'Cartão',
        'boleto' => 'Boleto',
        'transferencia' => 'Transferência',
        'cheque' => 'Cheque',
        'outro' => 'Outro',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaFinanceira::class, 'categoria_financeira_id');
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function criadoPor()
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }
}
