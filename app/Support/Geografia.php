<?php

namespace App\Support;

use App\Models\Estado;
use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Localização padronizada: UF e município vêm sempre das tabelas do IBGE (estado / municipio),
 * escolhidos em cascata. Não existe campo de texto livre para cidade ou estado.
 * As colunas de texto cidade/uf continuam gravadas (cópia do nome oficial) para listagens e buscas.
 */
class Geografia
{
    private static $estados = null;

    /** @return \Illuminate\Support\Collection<int, Estado> */
    public static function estados()
    {
        return self::$estados ??= Estado::orderBy('sigla_uf')->get();
    }

    public static function siglas(): array
    {
        return self::estados()->pluck('sigla_uf')->all();
    }

    /** Municípios de uma UF para o seletor: [[id, nome, codigo_ibge], ...]. */
    public static function municipiosDaUf(string $sigla): array
    {
        $estado = self::estados()->firstWhere('sigla_uf', strtoupper($sigla));
        if (! $estado) {
            return [];
        }

        return Municipio::where('estado_id', $estado->id)->orderBy('nome')->get(['id', 'nome', 'codigo_ibge'])
            ->map(fn ($m) => [$m->id, $m->nome, $m->codigo_ibge])->all();
    }

    /**
     * Regras de validação do par UF + município. $prefixo: '' ou 'corresp_'.
     * O município precisa pertencer à UF escolhida.
     */
    public static function regras(Request $request, string $prefixo = '', bool $obrigatorio = false): array
    {
        $uf = strtoupper((string) $request->input($prefixo.'uf'));
        $estado = self::estados()->firstWhere('sigla_uf', $uf);
        $base = $obrigatorio ? 'required' : 'nullable';

        return [
            $prefixo.'uf' => [$base, Rule::in(self::siglas())],
            $prefixo.'municipio_id' => [$base, 'integer', Rule::exists('municipio', 'id')->where('estado_id', $estado?->id ?? 0)],
        ];
    }

    public static function mensagens(string $prefixo = ''): array
    {
        return [
            $prefixo.'uf.required' => 'Selecione a UF.',
            $prefixo.'uf.in' => 'Selecione uma UF da lista.',
            $prefixo.'municipio_id.required' => 'Selecione o município na lista (escolha a UF e depois o município).',
            $prefixo.'municipio_id.exists' => 'Selecione um município da UF escolhida.',
            $prefixo.'municipio_id.integer' => 'Selecione o município na lista.',
        ];
    }

    /**
     * Troca o que veio do formulário ({prefixo}uf, {prefixo}municipio_id) pelas colunas gravadas:
     * {prefixo}estado_id, {prefixo}municipio_id, {prefixo}uf e {prefixo}cidade (nome oficial).
     */
    public static function colunas(array $dados, string $prefixo = ''): array
    {
        $uf = strtoupper((string) ($dados[$prefixo.'uf'] ?? ''));
        $estado = $uf !== '' ? self::estados()->firstWhere('sigla_uf', $uf) : null;
        $municipio = filled($dados[$prefixo.'municipio_id'] ?? null) && $estado
            ? Municipio::where('estado_id', $estado->id)->find((int) $dados[$prefixo.'municipio_id']) : null;

        return [
            $prefixo.'uf' => $estado?->sigla_uf,
            $prefixo.'estado_id' => $estado?->id,
            $prefixo.'municipio_id' => $municipio?->id,
            $prefixo.'cidade' => $municipio?->nome,
        ] + $dados;
    }

    /** "Itaúna/MG" a partir do município. */
    public static function cidadeUf(?int $municipioId): ?string
    {
        $m = $municipioId ? Municipio::with('estado')->find($municipioId) : null;

        return $m ? $m->nome.'/'.$m->estado->sigla_uf : null;
    }
}
