<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Módulo adicional (add-on) que a propriedade pode contratar, independente da faixa de plantel. */
class ModuloSistema extends Model
{
    protected $table = 'modulo_sistema';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = ['codigo', 'nome', 'resumo', 'beneficios', 'icone', 'valor_mensal', 'ativo', 'ordem'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'valor_mensal' => 'decimal:2'];
    }

    public function getRouteKeyName(): string
    {
        return 'codigo';
    }

    /** @return array<int, string> */
    public function beneficiosLista(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $this->beneficios))));
    }

    public function valorTexto(): string
    {
        return $this->valor_mensal === null ? 'Sob consulta' : moeda($this->valor_mensal).' / mês';
    }
}
