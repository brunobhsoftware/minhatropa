<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use TemUuid;

    protected $table = 'animal';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    public const CATEGORIAS = [
        'matriz' => 'Matriz', 'garanhao' => 'Garanhão', 'potro' => 'Potro', 'potra' => 'Potra',
        'cavalo' => 'Cavalo', 'egua' => 'Égua', 'castrado' => 'Castrado', 'outro' => 'Outro',
    ];

    public const CAMPOS = ['nome', 'sexo', 'categoria', 'raca', 'pelagem', 'data_nascimento', 'registro', 'microchip', 'pai', 'mae', 'observacoes'];

    protected $fillable = [...self::CAMPOS, 'empresa_id', 'criado_por'];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date'];
    }

    public function idade(): string
    {
        if (! $this->data_nascimento) {
            return '—';
        }
        $d = $this->data_nascimento->diff(now());

        return $d->y > 0 ? $d->y.' ano'.($d->y > 1 ? 's' : '') : $d->m.' mes'.($d->m === 1 ? '' : 'es');
    }
}
