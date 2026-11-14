<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use App\Support\Documento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Uma propriedade (Haras / Fazenda) cliente da plataforma, identificada por CPF ou CNPJ único. */
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

    /** Tipos oferecidos no cadastro pelo site (os demais ficam disponíveis na Administração). */
    public const TIPOS_CADASTRO = ['haras' => 'Haras', 'fazenda' => 'Fazenda'];

    /** Como o sistema chama a propriedade quando não sabe o tipo. */
    public const GENERICO = 'Haras / Fazenda';

    public const TITULARIDADES = ['cpf' => 'Pessoa Física (CPF)', 'cnpj' => 'Pessoa Jurídica (CNPJ)'];

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
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'estado_id', 'municipio_id', 'latitude', 'longitude', 'como_chegar',
        'corresp_cep', 'corresp_logradouro', 'corresp_numero', 'corresp_complemento', 'corresp_bairro', 'corresp_cidade', 'corresp_uf', 'corresp_estado_id', 'corresp_municipio_id',
        'area_hectares', 'finalidades_criacao', 'preferencias_marcha',
        'marca_descricao', 'marca_arquivo', 'site_url', 'subsite_slug',
        'logo_arquivo', 'capa_arquivo', 'cor_primaria', 'cor_fundo', 'pagina_ativa', 'pagina_slogan', 'pagina_apresentacao',
        'pagina_mostrar_contato', 'pagina_mostrar_endereco', 'instagram', 'facebook',
        'plano_id', 'observacoes', 'titular_profissional_id'];

    public const QUANTIDADES = ['Até 20', '21 a 50', '51 a 150', '151 a 500', 'Mais de 500'];

    protected $fillable = [...self::CAMPOS, 'situacao', 'situacao_motivo', 'criado_por', 'quantidade_animais_estimada', 'origem', 'licenca_ativa', 'licenca_liberada_em', 'licenca_liberada_por'];

    protected function casts(): array
    {
        return ['area_hectares' => 'decimal:2', 'latitude' => 'decimal:12', 'longitude' => 'decimal:12', 'excluido_em' => 'datetime', 'licenca_ativa' => 'boolean', 'pagina_ativa' => 'boolean', 'pagina_mostrar_contato' => 'boolean', 'pagina_mostrar_endereco' => 'boolean', 'licenca_liberada_em' => 'datetime'];
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

    /** Pessoa física titular (proprietário / criador), da base de Profissionais. */
    public function titular(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'titular_profissional_id');
    }

    /** "Haras", "Fazenda"… conforme o tipo escolhido no cadastro. */
    public function rotulo(): string
    {
        return self::TIPOS_PROPRIEDADE[$this->tipo_propriedade] ?? 'Haras';
    }

    /** "do Haras", "da Fazenda", "do Sítio"… para os textos das telas. */
    public function doRotulo(): string
    {
        $feminino = in_array($this->tipo_propriedade, ['fazenda', 'estancia', 'chacara'], true);
        if ($this->tipo_propriedade === 'outro') {
            return 'da propriedade';
        }

        return ($feminino ? 'da ' : 'do ').$this->rotulo();
    }

    /** Troca as raças criadas pela propriedade (só as da lista). */
    public function definirRacas(array $racas): void
    {
        $validas = array_values(array_unique(array_intersect($racas, self::RACAS)));
        \Illuminate\Support\Facades\DB::table('empresa_raca')->where('empresa_id', $this->id)->delete();
        foreach ($validas as $raca) {
            \Illuminate\Support\Facades\DB::table('empresa_raca')->insert(['empresa_id' => $this->id, 'raca' => $raca]);
        }
    }

    /** Endereço público de uma imagem da propriedade (logo, capa ou marca), ou null se não houver. */
    public function imagemUrl(string $tipo): ?string
    {
        $arquivo = $this->{$tipo.'_arquivo'} ?? null;

        if (! $arquivo) {
            return null;
        }
        // Marca enviada pelo modo antigo ficava dentro de public/uploads.
        if (! str_starts_with($arquivo, 'propriedades/')) {
            return asset($arquivo);
        }

        return route('midia.propriedade', ['uuid' => $this->uuid, 'tipo' => $tipo]).'?v='.substr(md5($arquivo), 0, 8);
    }

    /** Endereço da página pública, se estiver no ar. */
    public function paginaUrl(): ?string
    {
        return ($this->pagina_ativa ?? false) && $this->subsite_slug ? route('propriedade.pagina', $this->subsite_slug) : null;
    }

    public function pessoaFisica(): bool
    {
        return $this->tipo_documento === 'cpf';
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
