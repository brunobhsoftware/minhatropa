<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalReferencia extends Model
{
    use TemUuid;

    protected $table = 'animal_referencia';

    public $timestamps = false;

    const CREATED_AT = null;

    protected $fillable = [
        'nome', 'registro', 'registro_associacao', 'especie', 'sexo',
        'criado_por_empresa_id', 'criado_por', 'animal_id', 'vinculado_em',
    ];

    protected function casts(): array
    {
        return [
            'vinculado_em' => 'datetime',
            'criado_em' => 'datetime',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'criado_por_empresa_id');
    }
}
