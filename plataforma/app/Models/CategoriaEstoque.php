<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;

class CategoriaEstoque extends Model
{
    use TemUuid;

    protected $table = 'categoria_estoque';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $guarded = ['id'];

    public function itens()
    {
        return $this->hasMany(ItemEstoque::class, 'categoria_id');
    }
}
