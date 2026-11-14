<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use App\Support\Documento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Profissional extends Model
{
    use TemUuid;

    protected $table = 'profissional';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    public const ESPECIALIDADES = [
        'veterinario' => 'Veterinário(a)',
        'tratador' => 'Tratador(a)',
        'ferrador' => 'Ferrador(a)',
        'domador' => 'Domador(a)',
        'montador' => 'Montador(a)',
        'peao' => 'Peão',
        'inseminador' => 'Inseminador(a)',
        'nutricionista' => 'Nutricionista equino',
        'gerente' => 'Gerente',
        'administrador' => 'Administrador(a)',
        'cozinheiro' => 'Cozinheiro(a)',
        'caseiro' => 'Caseiro(a)',
        'mordomo' => 'Mordomo(a)',
        'jardineiro' => 'Jardineiro(a)',
        'motorista' => 'Motorista',
        'seguranca' => 'Segurança',
        'outro' => 'Outro',
    ];

    public const SITUACOES = [
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
        'banido' => 'Banido',
    ];

    protected $fillable = [
        'nome', 'cpf', 'email', 'telefone', 'telefone_fixo', 'whatsapp',
        'data_nascimento', 'cep', 'logradouro', 'numero', 'complemento',
        'bairro', 'cidade', 'uf', 'crmv', 'descricao',
        'situacao', 'situacao_motivo', 'criado_por',
    ];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date', 'excluido_em' => 'datetime'];
    }

    public function scopeNaoExcluidos($query)
    {
        return $query->whereNull('excluido_em');
    }

    public function cpfFormatado(): string
    {
        return $this->cpf ? Documento::formatar($this->cpf) : '—';
    }

    public function cidadeUf(): string
    {
        return trim(collect([$this->cidade, $this->uf])->filter()->implode('/')) ?: '—';
    }

    public function situacaoTexto(): string
    {
        return self::SITUACOES[$this->situacao] ?? $this->situacao;
    }

    public function especialidades(): array
    {
        return DB::table('profissional_especialidade')
            ->where('profissional_id', $this->id)
            ->pluck('especialidade')
            ->toArray();
    }

    public function especialidadesTexto(): string
    {
        $esps = $this->especialidades();

        return collect($esps)->map(fn ($e) => self::ESPECIALIDADES[$e] ?? $e)->implode(', ') ?: '—';
    }

    public function haras()
    {
        return $this->belongsToMany(Empresa::class, 'empresa_profissional')
            ->withPivot(['id', 'funcao', 'acesso_sistema', 'situacao', 'data_inicio', 'data_fim']);
    }
}
