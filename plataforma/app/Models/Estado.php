<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** UF (tabela do IBGE). O id é o código IBGE da UF. */
class Estado extends Model
{
    protected $table = 'estado';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['id', 'sigla_uf', 'nome', 'regiao'];
}
