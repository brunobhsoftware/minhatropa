<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Model;

class EventoSaude extends Model
{
    use TemUuid;

    protected $table = 'evento_saude';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    const TIPOS = [
        'vacina'        => 'Vacina',
        'vermifugacao'  => 'Vermifugação',
        'exame'         => 'Exame',
        'tratamento'    => 'Tratamento',
        'cirurgia'      => 'Cirurgia',
        'medicacao'     => 'Medicação',
        'outro'         => 'Outro',
    ];

    const TIPO_CORES = [
        'vacina'        => '#10b981',
        'vermifugacao'  => '#8b5cf6',
        'exame'         => '#3b82f6',
        'tratamento'    => '#f59e0b',
        'cirurgia'      => '#ef4444',
        'medicacao'     => '#06b6d4',
        'outro'         => '#6b7280',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'data_evento' => 'date',
        'data_proxima' => 'date',
        'custo' => 'decimal:2',
        'peso_animal' => 'decimal:2',
        'temperatura' => 'decimal:1',
    ];

    public function tipoTexto(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function criadoPor()
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }
}
