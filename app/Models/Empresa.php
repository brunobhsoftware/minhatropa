<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use App\Support\Documento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Um Haras cliente da plataforma. */
class Empresa extends Model
{
    use TemUuid;

    protected $table = 'empresa';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    public const SITUACOES = [
        'teste' => 'Em teste',
        'ativo' => 'Ativo',
        'suspenso' => 'Suspenso',
        'encerrado' => 'Encerrado',
    ];

    public const CAMPOS = ['nome', 'razao_social', 'tipo_documento', 'documento', 'inscricao_estadual', 'email', 'telefone', 'whatsapp',
        'responsavel_nome', 'responsavel_email', 'responsavel_telefone', 'cep', 'logradouro', 'numero', 'complemento', 'bairro',
        'cidade', 'uf', 'area_hectares', 'plano_id', 'observacoes'];

    public const QUANTIDADES = ['Até 20', '21 a 50', '51 a 150', '151 a 500', 'Mais de 500'];

    protected $fillable = [...self::CAMPOS, 'situacao', 'situacao_motivo', 'criado_por', 'quantidade_animais_estimada', 'origem', 'licenca_ativa', 'licenca_liberada_em', 'licenca_liberada_por'];

    protected function casts(): array
    {
        return ['area_hectares' => 'decimal:2', 'excluido_em' => 'datetime', 'licenca_ativa' => 'boolean', 'licenca_liberada_em' => 'datetime'];
    }

    public function scopeNaoExcluidas($query)
    {
        return $query->whereNull('excluido_em');
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'empresa_usuario')->withPivot(['id', 'papel', 'status', 'criado_em']);
    }

    public function documentoFormatado(): string
    {
        return $this->documento ? Documento::formatar($this->documento) : '—';
    }

    public function cidadeUf(): string
    {
        return trim(collect([$this->cidade, $this->uf])->filter()->implode('/')) ?: '—';
    }

    public function situacaoTexto(): string
    {
        return self::SITUACOES[$this->situacao] ?? $this->situacao;
    }
}
