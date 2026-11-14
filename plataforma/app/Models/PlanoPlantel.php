<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Faixa de plantel: o plano base do Haras / Fazenda é definido pela quantidade de animais ativos. */
class PlanoPlantel extends Model
{
    protected $table = 'plano_plantel';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = ['codigo', 'nome', 'descricao', 'limite_min', 'limite_max', 'valor_mensal', 'ativo', 'ordem'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'limite_min' => 'integer', 'limite_max' => 'integer', 'valor_mensal' => 'decimal:2'];
    }

    public function gratuito(): bool
    {
        return $this->valor_mensal !== null && (float) $this->valor_mensal == 0.0;
    }

    public function ilimitado(): bool
    {
        return $this->limite_max === null;
    }

    /** "Até 5 animais", "De 6 a 10 animais", "Mais de 100 animais". */
    public function faixaTexto(): string
    {
        if ($this->ilimitado()) {
            return $this->limite_min > 1 ? 'Mais de '.($this->limite_min - 1).' animais' : 'Animais ilimitados';
        }

        return $this->limite_min <= 1 ? "Até {$this->limite_max} animais" : "De {$this->limite_min} a {$this->limite_max} animais";
    }

    /** Preço para a tela. Sem valor definido, não inventa: "Sob consulta". */
    public function valorTexto(): string
    {
        if ($this->valor_mensal === null) {
            return 'Sob consulta';
        }

        return $this->gratuito() ? 'Grátis' : moeda($this->valor_mensal).' / mês';
    }
}
