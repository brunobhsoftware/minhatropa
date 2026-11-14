<?php

namespace App\Support;

use App\Models\Animal;
use App\Models\Empresa;
use App\Models\Profissional;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sociedade do animal: o animal é 100% da propriedade ou tem sócios, cada um com o seu percentual.
 * Sócio pessoa é identificado pelo CPF (base de Profissionais); sócio propriedade, pelo CNPJ (Haras / Fazenda).
 * A parte da propriedade dona do cadastro é o que sobra até 100%. O vínculo é pelo documento: quando o sócio
 * se cadastra (pelo link de convite ou sozinho), já enxerga o animal.
 */
class Sociedade
{
    public static function disponivel(): bool
    {
        return Esquema::temV011();
    }

    /** Quem tem cadastro com este documento: ['tipo' => profissional|propriedade, 'nome' => ...] ou null. */
    public static function localizar(string $documento): ?array
    {
        $documento = Documento::digitos($documento);
        if (strlen($documento) === 11) {
            $p = Profissional::porCpf($documento);

            return $p ? ['tipo' => 'profissional', 'nome' => $p->nome, 'detalhe' => $p->cidadeUf(), 'conta' => (bool) $p->usuario_id] : null;
        }
        if (strlen($documento) === 14) {
            $e = Empresa::naoExcluidas()->where('documento', $documento)->first();

            return $e ? ['tipo' => 'propriedade', 'nome' => $e->nome, 'detalhe' => $e->rotulo().' · '.$e->cidadeUf(), 'conta' => true] : null;
        }

        return null;
    }

    /** Sócios do animal, com o nome atual de quem já se cadastrou e a situação do convite. */
    public static function doAnimal(Animal $animal): Collection
    {
        if (! self::disponivel() || ! $animal->exists) {
            return collect();
        }

        return DB::table('animal_socio')->where('animal_id', $animal->id)->orderByDesc('percentual')->orderBy('nome')->get()->map(function ($s) {
            $achado = self::localizar($s->documento);
            $s->cadastrado = (bool) $achado;
            $s->com_conta = (bool) ($achado['conta'] ?? false);
            $s->nome_atual = $achado['nome'] ?? $s->nome;
            $s->detalhe = $achado['detalhe'] ?? null;
            $s->convite_url = route('convite.socio', $s->convite_token);

            return $s;
        });
    }

    /** Parte da propriedade dona do cadastro: o que sobra dos sócios. */
    public static function parteDaPropriedade(Animal $animal): float
    {
        if (! self::disponivel() || ! $animal->exists) {
            return 100.0;
        }

        return round(100 - (float) DB::table('animal_socio')->where('animal_id', $animal->id)->sum('percentual'), 2);
    }

    /**
     * Valida os sócios vindos do formulário: socios[i][documento], [nome], [percentual]; sociedade = 'propria' | 'socios'.
     *
     * @return array<int, array{tipo_documento:string, documento:string, nome:string, percentual:float}>
     */
    public static function validar(Request $request, Empresa $empresa): array
    {
        if (! self::disponivel() || $request->input('sociedade') !== 'socios') {
            return [];
        }
        $linhas = [];
        $erros = [];
        foreach ((array) $request->input('socios', []) as $i => $linha) {
            $doc = Documento::digitos($linha['documento'] ?? '');
            $pct = (float) str_replace(',', '.', (string) ($linha['percentual'] ?? ''));
            if ($doc === '' && $pct == 0.0 && blank($linha['nome'] ?? null)) {
                continue; // linha em branco
            }
            $n = count($linhas) + 1;
            if (! in_array(strlen($doc), [11, 14], true) || ! Documento::valido($doc)) {
                $erros["socios.$i.documento"] = "Sócio {$n}: CPF ou CNPJ inválido.";

                continue;
            }
            if ($doc === $empresa->documento) {
                $erros["socios.$i.documento"] = "Sócio {$n}: é o documento desta propriedade. A parte dela é o que sobra dos sócios.";

                continue;
            }
            if (isset($linhas[$doc])) {
                $erros["socios.$i.documento"] = "Sócio {$n}: documento repetido.";

                continue;
            }
            if ($pct <= 0 || $pct > 100) {
                $erros["socios.$i.percentual"] = "Sócio {$n}: informe o percentual (maior que 0 e até 100).";

                continue;
            }
            $achado = self::localizar($doc);
            $nome = $achado['nome'] ?? trim((string) ($linha['nome'] ?? ''));
            if ($nome === '') {
                $erros["socios.$i.nome"] = "Sócio {$n}: ainda não tem cadastro no MinhaTropa. Informe o nome para gerar o convite.";

                continue;
            }
            $linhas[$doc] = ['tipo_documento' => strlen($doc) === 11 ? 'cpf' : 'cnpj', 'documento' => $doc, 'nome' => mb_substr($nome, 0, 150), 'percentual' => round($pct, 2)];
        }
        if (! $erros && ! $linhas) {
            $erros['sociedade'] = 'Lance pelo menos um sócio ou marque que o animal é 100% da propriedade.';
        }
        if (! $erros && round(array_sum(array_column($linhas, 'percentual')), 2) > 100) {
            $erros['sociedade'] = 'A soma dos percentuais dos sócios passa de 100%.';
        }
        if ($erros) {
            throw ValidationException::withMessages($erros);
        }

        return array_values($linhas);
    }

    /** Grava os sócios do animal: mantém o convite de quem já estava, inclui os novos e tira os que saíram. */
    public static function salvar(Animal $animal, array $socios, ?int $usuarioId): void
    {
        if (! self::disponivel()) {
            return;
        }
        DB::transaction(function () use ($animal, $socios, $usuarioId) {
            $ficam = array_column($socios, 'documento');
            DB::table('animal_socio')->where('animal_id', $animal->id)->when($ficam, fn ($q) => $q->whereNotIn('documento', $ficam))->delete();
            foreach ($socios as $s) {
                $existe = DB::table('animal_socio')->where('animal_id', $animal->id)->where('documento', $s['documento'])->first();
                if ($existe) {
                    DB::table('animal_socio')->where('id', $existe->id)->update(['nome' => $s['nome'], 'percentual' => $s['percentual'], 'atualizado_em' => now()]);
                } else {
                    DB::table('animal_socio')->insert($s + ['animal_id' => $animal->id, 'convite_token' => Str::random(40), 'criado_por' => $usuarioId, 'criado_em' => now()]);
                }
            }
        });
    }

    /** Animais de outras propriedades em que este documento (CPF do profissional ou CNPJ da propriedade) é sócio. */
    public static function animaisDoSocio(?string $documento): Collection
    {
        if (! self::disponivel() || blank($documento)) {
            return collect();
        }

        return DB::table('animal_socio as s')->join('animal as a', 'a.id', '=', 's.animal_id')->join('empresa as e', 'e.id', '=', 'a.empresa_id')
            ->where('s.documento', $documento)->whereNull('a.excluido_em')->whereNull('e.excluido_em')
            ->orderBy('a.nome')
            ->get(['a.nome', 'a.raca', 'a.sexo', 'a.situacao', 's.percentual', 'e.nome as propriedade', 'e.tipo_propriedade', 'e.cidade', 'e.uf']);
    }

    /** "33,5%" */
    public static function pct($valor): string
    {
        return rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',').'%';
    }
}
