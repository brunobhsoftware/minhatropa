<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    use TemUuid;

    protected $table = 'plano';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = ['nome', 'descricao', 'limite_animais', 'limite_usuarios', 'valor_mensal', 'ativo', 'ordem'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'valor_mensal' => 'decimal:2'];
    }
}
