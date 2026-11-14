<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoRotina extends Model
{
    protected $table = 'tipo_rotina';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;

    protected $fillable = ['empresa_id', 'nome', 'descricao', 'icone', 'cor'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function scopeDisponiveis($query, int $empresaId)
    {
        return $query->where(fn ($q) => $q->whereNull('empresa_id')->orWhere('empresa_id', $empresaId))
                     ->orderByRaw('empresa_id IS NULL DESC, nome');
    }
}
