<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pedido de contato/demonstração feito no site. */
class Contato extends Model
{
    protected $table = 'contato';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = null;

    protected $fillable = ['nome', 'email', 'telefone', 'haras', 'cidade_uf', 'quantidade_animais', 'mensagem', 'origem', 'ip', 'lido_em'];

    protected function casts(): array
    {
        return ['lido_em' => 'datetime', 'criado_em' => 'datetime'];
    }
}
