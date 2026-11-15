<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;

class MovimentacaoEstoque extends Model
{
    use TemUuid;

    protected $table = 'movimentacao_estoque';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    public const TIPOS = [
        'entrada' => 'Entrada',
        'saida' => 'Saída',
        'ajuste' => 'Ajuste',
    ];

    public function item()
    {
        return $this->belongsTo(ItemEstoque::class, 'item_id');
    }
}
