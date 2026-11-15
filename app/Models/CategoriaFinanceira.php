<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaFinanceira extends Model
{
    protected $table = 'categoria_financeira';
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;
    protected $guarded = ['id'];
}
