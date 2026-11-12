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

    public const TIPOS_PROPRIEDADE = [
        'haras' => 'Haras',
        'fazenda' => 'Fazenda',
        'criatorio' => 'Criatório',
        'sitio' => 'Sítio',
        'estancia' => 'Estância',
        'chacara' => 'Chácara',
        'outro' => 'Outro',
    ];

    public const RACAS = [
        'Mangalarga Marchador',
        'Mangalarga',
        'Campolina',
        'Quarto de Milha',
        'Crioulo',
        'Lusitano',
        'Puro Sangue Inglês',
        'Brasileiro de Hipismo',
        'Árabe',
        'Paint Horse',
        'Appaloosa',
        'Pantaneiro',
        'Pampa',
        'Andaluz',
        'Friesian',
        'Percheron',
        'Bretão',
        'Pônei',
        'Nordestino',
        'Lavradeiro',
        'Campeiro',
        'Marajoara',
        'Outras',
    ];

    public const MARCHAS = [
        'batida' => 'Marcha Batida',
        'picada' => 'Marcha Picada',
        'centro' => 'Marcha de Centro',
        'trote' => 'Trote',
        'galope' => 'Galope',
    ];

    public const FINALIDADES = [
        'criacao' => 'Criação / Reprodução',
        'esporte' => 'Esporte / Competição',
        'lazer' => 'Lazer / Passeio',
        'trabalho' => 'Trabalho',
        'exposicao' => 'Exposição',
        'venda' => 'Venda',
    ];

    public const CAMPOS = ['nome', 'tipo_propriedade', 'razao_social', 'tipo_documento', 'documento', 'inscricao_estadual', 'email', 'telefone', 'whatsapp',
        'responsavel_nome', 'responsavel_email', 'responsavel_telefone', 'telefone_residencial', 'telefone_celular', 'telefone_comercial',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'latitude', 'longitude', 'como_chegar',
        'corresp_cep', 'corresp_logradouro', 'corresp_numero', 'corresp_complemento', 'corresp_bairro', 'corresp_cidade', 'corresp_uf',
        'area_hectares', 'finalidades_criacao', 'preferencias_marcha',
        'marca_descricao', 'marca_arquivo', 'site_url', 'subsite_slug',
        'plano_id', 'observacoes'];

    public const QUANTIDADES = ['Até 20', '21 a 50', '51 a 150', '151 a 500', 'Mais de 500'];

    protected $fillable = [...self::CAMPOS, 'situacao', 'situacao_motivo', 'criado_por', 'quantidade_animais_estimada', 'origem', 'licenca_ativa', 'licenca_liberada_em', 'licenca_liberada_por'];

    protected function casts(): array
    {
        return ['area_hectares' => 'decimal:2', 'latitude' => 'decimal:12', 'longitude' => 'decimal:12', 'excluido_em' => 'datetime', 'licenca_ativa' => 'boolean', 'licenca_liberada_em' => 'datetime'];
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

    public function correspCidadeUf(): string
    {
        return trim(collect([$this->corresp_cidade, $this->corresp_uf])->filter()->implode('/')) ?: '—';
    }

    public function situacaoTexto(): string
    {
        return self::SITUACOES[$this->situacao] ?? $this->situacao;
    }

    public function tipoPropriedadeTexto(): string
    {
        return self::TIPOS_PROPRIEDADE[$this->tipo_propriedade] ?? '—';
    }

    public function finalidadesArray(): array
    {
        return $this->finalidades_criacao ? explode(',', $this->finalidades_criacao) : [];
    }

    public function marchasArray(): array
    {
        return $this->preferencias_marcha ? explode(',', $this->preferencias_marcha) : [];
    }

    public function racas(): array
    {
        return \DB::table('empresa_raca')->where('empresa_id', $this->id)->pluck('raca')->toArray();
    }
}
