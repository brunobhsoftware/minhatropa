<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditoria';

    public $timestamps = false;

    protected $fillable = ['empresa_id', 'usuario_id', 'acao', 'tabela', 'registro_id', 'antes', 'depois', 'ip'];

    protected function casts(): array
    {
        return ['antes' => 'array', 'depois' => 'array'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
