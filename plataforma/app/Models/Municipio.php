<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Município (tabela do IBGE). */
class Municipio extends Model
{
    protected $table = 'municipio';

    public $timestamps = false;

    protected $fillable = ['estado_id', 'nome', 'codigo_ibge'];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }
}
