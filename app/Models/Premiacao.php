<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Premiação conquistada pelo animal (exposições, competições). Cadastro simples: evento, data, local. */
class Premiacao extends Model
{
    use TemUuid;

    protected $table = 'premiacao';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $guarded = ['id'];

    protected $casts = ['data' => 'date', 'excluido_em' => 'datetime'];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
