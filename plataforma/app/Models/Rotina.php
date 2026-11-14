<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Rotina extends Model
{
    use TemUuid;

    protected $table = 'rotina';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    public const FREQUENCIAS = [
        'unica' => 'Única',
        'diaria' => 'Diária',
        'semanal' => 'Semanal',
        'quinzenal' => 'Quinzenal',
        'mensal' => 'Mensal',
        'personalizada' => 'Personalizada',
    ];

    public const PRIORIDADES = [
        'baixa' => 'Baixa',
        'normal' => 'Normal',
        'alta' => 'Alta',
        'urgente' => 'Urgente',
    ];

    protected $fillable = [
        'empresa_id', 'tipo_rotina_id', 'titulo', 'descricao', 'animal_id',
        'frequencia', 'frequencia_dias', 'dia_semana', 'dia_mes', 'hora_prevista',
        'data_inicio', 'data_fim', 'responsavel_id', 'prioridade', 'ativa', 'criado_por',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'ativa' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function tipoRotina(): BelongsTo
    {
        return $this->belongsTo(TipoRotina::class, 'tipo_rotina_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsavel_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }

    public function execucoes(): HasMany
    {
        return $this->hasMany(ExecucaoRotina::class, 'rotina_id');
    }

    public function frequenciaTexto(): string
    {
        $texto = self::FREQUENCIAS[$this->frequencia] ?? $this->frequencia;
        if ($this->frequencia === 'semanal' && $this->dia_semana !== null) {
            $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
            $texto .= ' ('.$dias[$this->dia_semana].')';
        } elseif ($this->frequencia === 'mensal' && $this->dia_mes) {
            $texto .= ' (dia '.$this->dia_mes.')';
        } elseif ($this->frequencia === 'personalizada' && $this->frequencia_dias) {
            $texto .= ' (a cada '.$this->frequencia_dias.' dias)';
        }
        return $texto;
    }

    public function proximaExecucao(): ?Carbon
    {
        $hoje = Carbon::today();
        if ($this->data_fim && $hoje->gt($this->data_fim)) {
            return null;
        }

        $base = $hoje->lt($this->data_inicio) ? $this->data_inicio->copy() : $hoje->copy();

        return match ($this->frequencia) {
            'unica' => $this->data_inicio->gte($hoje) ? $this->data_inicio->copy() : null,
            'diaria' => $base,
            'semanal' => $this->proximaSemanal($base),
            'quinzenal' => $this->proximaQuinzenal($base),
            'mensal' => $this->proximaMensal($base),
            'personalizada' => $this->proximaPersonalizada($base),
            default => null,
        };
    }

    private function proximaSemanal(Carbon $base): Carbon
    {
        $dia = $this->dia_semana ?? 1;
        $data = $base->copy()->startOfWeek()->addDays($dia === 0 ? 6 : $dia - 1);
        return $data->lt($base) ? $data->addWeek() : $data;
    }

    private function proximaQuinzenal(Carbon $base): Carbon
    {
        $inicio = $this->data_inicio->copy();
        $diff = $inicio->diffInDays($base);
        $ciclos = intdiv($diff, 14);
        $data = $inicio->addDays($ciclos * 14);
        return $data->lt($base) ? $data->addDays(14) : $data;
    }

    private function proximaMensal(Carbon $base): Carbon
    {
        $dia = min($this->dia_mes ?? 1, $base->daysInMonth);
        $data = $base->copy()->day($dia);
        return $data->lt($base) ? $data->addMonthNoOverflow() : $data;
    }

    private function proximaPersonalizada(Carbon $base): Carbon
    {
        $n = max($this->frequencia_dias ?? 1, 1);
        $inicio = $this->data_inicio->copy();
        $diff = $inicio->diffInDays($base);
        $ciclos = intdiv($diff, $n);
        $data = $inicio->addDays($ciclos * $n);
        return $data->lt($base) ? $data->addDays($n) : $data;
    }

    public function gerarExecucoes(Carbon $ate): int
    {
        if (! $this->ativa || $this->excluido_em) {
            return 0;
        }

        $ultimaExistente = $this->execucoes()->max('data_prevista');
        $inicio = $ultimaExistente
            ? Carbon::parse($ultimaExistente)->addDay()
            : ($this->data_inicio->copy());

        if ($inicio->gt($ate)) {
            return 0;
        }

        $datas = [];
        $cursor = $inicio->copy();

        while ($cursor->lte($ate)) {
            if ($this->data_fim && $cursor->gt($this->data_fim)) {
                break;
            }

            $deveCriar = match ($this->frequencia) {
                'unica' => $cursor->equalTo($this->data_inicio),
                'diaria' => true,
                'semanal' => $cursor->dayOfWeek === ($this->dia_semana ?? 1),
                'quinzenal' => $this->data_inicio->diffInDays($cursor) % 14 === 0,
                'mensal' => $cursor->day === min($this->dia_mes ?? 1, $cursor->daysInMonth),
                'personalizada' => $this->data_inicio->diffInDays($cursor) % max($this->frequencia_dias ?? 1, 1) === 0,
                default => false,
            };

            if ($deveCriar) {
                $datas[] = $cursor->toDateString();
            }

            $cursor->addDay();
            if (count($datas) > 365) {
                break;
            }
        }

        $existentes = $this->execucoes()
            ->whereIn('data_prevista', $datas)
            ->pluck('data_prevista')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $novas = array_diff($datas, $existentes);
        $registros = [];
        foreach ($novas as $data) {
            $registros[] = [
                'uuid' => (string) Str::uuid(),
                'rotina_id' => $this->id,
                'empresa_id' => $this->empresa_id,
                'data_prevista' => $data,
                'situacao' => 'pendente',
                'criado_em' => now(),
            ];
        }

        if ($registros) {
            ExecucaoRotina::insert($registros);
        }

        return count($registros);
    }
}
