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

    /** Função que identifica o titular de uma propriedade. */
    public const PROPRIETARIO = 'proprietario';

    /** Funções do profissional. A mesma pessoa pode acumular várias. */
    public const ESPECIALIDADES = [
        'proprietario' => 'Proprietário de Haras/Fazenda',
        'veterinario' => 'Veterinário(a)',
        'gerente' => 'Gerente',
        'treinador' => 'Treinador(a)',
        'peao' => 'Peão',
        'tratador' => 'Tratador(a)',
        'ferrador' => 'Ferrador(a)',
        'domador' => 'Domador(a)',
        'montador' => 'Montador(a)',
        'inseminador' => 'Inseminador(a)',
        'nutricionista' => 'Nutricionista equino',
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
        'bairro', 'cidade', 'uf', 'estado_id', 'municipio_id', 'crmv', 'descricao',
        'situacao', 'situacao_motivo', 'criado_por', 'usuario_id', 'origem',
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

    public function temEspecialidade(string $codigo): bool
    {
        return in_array($codigo, $this->especialidades(), true);
    }

    /** Acrescenta uma função sem mexer nas outras (ex.: vira "Proprietário" ao titular uma propriedade). */
    public function acrescentarEspecialidade(string $codigo): void
    {
        if (isset(self::ESPECIALIDADES[$codigo])) {
            DB::table('profissional_especialidade')->insertOrIgnore(['profissional_id' => $this->id, 'especialidade' => $codigo]);
        }
    }

    /** Troca todas as funções pelas informadas (só as que existem na lista). */
    public function definirEspecialidades(array $codigos): void
    {
        $validas = array_values(array_unique(array_intersect($codigos, array_keys(self::ESPECIALIDADES))));
        DB::table('profissional_especialidade')->where('profissional_id', $this->id)->delete();
        foreach ($validas as $codigo) {
            DB::table('profissional_especialidade')->insert(['profissional_id' => $this->id, 'especialidade' => $codigo]);
        }
    }

    /** Busca na base global pelo CPF (só dígitos). */
    public static function porCpf(?string $cpf): ?self
    {
        $cpf = Documento::digitos($cpf);

        return strlen($cpf) === 11 ? self::naoExcluidos()->where('cpf', $cpf)->first() : null;
    }

    public function ativo(): bool
    {
        return $this->situacao === 'ativo';
    }

    /** "Bruno D. M." mascarado para telas públicas: só o primeiro nome e as iniciais. */
    public function nomeMascarado(): string
    {
        $partes = preg_split('/\s+/', trim($this->nome));
        $primeiro = array_shift($partes);

        return trim($primeiro.' '.collect($partes)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)).'.')->implode(' '));
    }

    /** "br***@dominio.com.br" para telas públicas. */
    public function emailMascarado(): ?string
    {
        if (! $this->email || ! str_contains($this->email, '@')) {
            return null;
        }
        [$u, $d] = explode('@', $this->email, 2);

        return mb_substr($u, 0, min(2, mb_strlen($u))).'***@'.$d;
    }

    public function especialidadesTexto(): string
    {
        $esps = $this->especialidades();

        return collect($esps)->map(fn ($e) => self::ESPECIALIDADES[$e] ?? $e)->implode(', ') ?: '—';
    }

    public function haras()
    {
        return $this->belongsToMany(Empresa::class, 'empresa_profissional')
            ->withPivot(['id', 'funcao', 'funcoes', 'titular', 'acesso_sistema', 'papel_acesso', 'situacao', 'data_inicio', 'data_fim']);
    }
}
