<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaUsuario extends Model
{
    protected $table = 'empresa_usuario';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    public const PAPEIS = [
        'proprietario' => 'Proprietário',
        'gerente' => 'Gerente',
        'veterinario' => 'Veterinário',
        'treinador' => 'Treinador',
        'tratador' => 'Tratador',
        'financeiro' => 'Financeiro',
        'consulta' => 'Só consulta',
    ];

    protected $fillable = ['empresa_id', 'usuario_id', 'papel', 'status'];
}
