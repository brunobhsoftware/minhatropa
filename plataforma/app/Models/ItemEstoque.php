<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;

class ItemEstoque extends Model
{
    use TemUuid;

    protected $table = 'item_estoque';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $guarded = ['id'];

    public const UNIDADES = [
        'un' => 'Unidade',
        'kg' => 'Quilograma (kg)',
        'g' => 'Grama (g)',
        'L' => 'Litro (L)',
        'mL' => 'Mililitro (mL)',
        'saco' => 'Saco',
        'dose' => 'Dose',
        'caixa' => 'Caixa',
        'rolo' => 'Rolo',
        'par' => 'Par',
        'peca' => 'Peça',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaEstoque::class, 'categoria_id');
    }

    public function movimentacoes()
    {
        return $this->hasMany(MovimentacaoEstoque::class, 'item_id');
    }

    public function abaixoDoMinimo(): bool
    {
        return $this->estoque_minimo !== null && $this->estoque_atual < $this->estoque_minimo;
    }

    public function unidadeTexto(): string
    {
        return self::UNIDADES[$this->unidade] ?? $this->unidade;
    }
}
