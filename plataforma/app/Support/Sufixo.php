<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Sufixo
{
    public static function disponivel(): bool
    {
        return Esquema::temTabela('sufixo_associacao');
    }

    public static function buscar(string $termo, string $associacao = 'ABCCMM', int $limite = 20): array
    {
        $codigoLimpo = self::limparCodigo($termo);
        return DB::table('sufixo_associacao')
            ->where('associacao', $associacao)
            ->where(function ($q) use ($termo, $codigoLimpo) {
                $q->where('sufixo', 'like', "%{$termo}%")
                  ->orWhere('codigo', 'like', "%{$codigoLimpo}%")
                  ->orWhere('proprietario_nome', 'like', "%{$termo}%");
            })
            ->limit($limite)
            ->get()
            ->toArray();
    }

    public static function limparCodigo(string $codigo): string
    {
        return preg_replace('/\D/', '', trim($codigo));
    }

    public static function porCodigo(string $codigo, string $associacao = 'ABCCMM'): ?object
    {
        return DB::table('sufixo_associacao')
            ->where('associacao', $associacao)
            ->where('codigo', self::limparCodigo($codigo))
            ->first();
    }

    public static function porSufixo(string $sufixo, string $associacao = 'ABCCMM'): ?object
    {
        return DB::table('sufixo_associacao')
            ->where('associacao', $associacao)
            ->where('sufixo', $sufixo)
            ->first();
    }

    public static function sufixoEmUso(string $sufixo, ?int $excluirEmpresaId = null): bool
    {
        $q = DB::table('empresa')->where('sufixo', $sufixo)->whereNull('excluido_em');
        if ($excluirEmpresaId) {
            $q->where('id', '!=', $excluirEmpresaId);
        }

        return $q->exists();
    }

    /**
     * Checagem automática de sufixo por pontuação.
     *
     * Busca na base da associação registros cujo nome do sufixo bata (gate, acento/caixa-insensível
     * pelo collation do banco). Para cada candidato, pontua:
     *   - Proprietário completo   +60
     *   - Só primeiro nome        +30
     *   - Cidade                  +15
     *   - UF                      +15
     *
     * >= 60 → CONFIRMADO (grava vínculo, a não ser que o código já esteja em outra propriedade)
     * 30–59 → PENDENTE
     * < 30  → NAO_CONFIRMADO
     *
     * @param  \App\Models\Empresa  $empresa  Propriedade a conferir
     * @param  bool  $simular  true = só pontua, não grava
     * @return array{status: string, codigo: ?string, score: int, mensagem: string}
     */
    public static function conferir(\App\Models\Empresa $empresa, bool $simular = false): array
    {
        $nomeSufixo = trim($empresa->sufixo ?? '');
        if ($nomeSufixo === '') {
            return self::resultado('NAO_CONFIRMADO', null, 0, 'Sufixo não informado no cadastro.');
        }

        // Gate: buscar candidatos pelo nome do sufixo (collation cuida de acento/caixa)
        $candidatos = DB::table('sufixo_associacao')
            ->where('sufixo', $nomeSufixo)
            ->get();

        if ($candidatos->isEmpty()) {
            return self::resultado('NAO_CONFIRMADO', null, 0, 'Sufixo não localizado na base da associação.');
        }

        // Nome do proprietário da propriedade (titular ou responsavel_nome)
        $proprietario = trim($empresa->titular?->nome ?? $empresa->responsavel_nome ?? '');
        $cidadeEmpresa = trim($empresa->cidade ?? '');
        $ufEmpresa = trim($empresa->uf ?? '');

        $melhor = null;
        $melhorScore = -1;

        foreach ($candidatos as $c) {
            $score = 0;
            $propAssoc = trim($c->proprietario_nome ?? '');

            // Proprietário completo (mb_strtolower para normalizar; collation cuida do acento no banco,
            // mas aqui comparamos em PHP — usamos a mesma lógica sem acento)
            if ($propAssoc !== '' && $proprietario !== '') {
                if (self::igualSemAcento($propAssoc, $proprietario)) {
                    $score += 60;
                } elseif (self::igualSemAcento(self::primeiroNome($propAssoc), self::primeiroNome($proprietario))) {
                    $score += 30;
                }
            }

            // Cidade
            if ($cidadeEmpresa !== '' && trim($c->cidade ?? '') !== '') {
                if (self::igualSemAcento($c->cidade, $cidadeEmpresa)) {
                    $score += 15;
                }
            }

            // UF
            if ($ufEmpresa !== '' && trim($c->uf ?? '') !== '') {
                if (mb_strtoupper($c->uf) === mb_strtoupper($ufEmpresa)) {
                    $score += 15;
                }
            }

            if ($score > $melhorScore) {
                $melhorScore = $score;
                $melhor = $c;
            }
        }

        if ($melhorScore >= 60) {
            // Checar exclusividade: o código já está em outra propriedade?
            $outra = DB::table('empresa')
                ->where('sufixo_codigo', $melhor->codigo)
                ->where('sufixo_confirmado', 1)
                ->where('id', '!=', $empresa->id)
                ->whereNull('excluido_em')
                ->exists();

            if ($outra) {
                return self::resultado('CONFLITO', $melhor->codigo, $melhorScore,
                    'Código já vinculado a outra propriedade. Enviado para revisão.');
            }

            if (! $simular) {
                $empresa->update([
                    'sufixo_codigo' => $melhor->codigo,
                    'sufixo_associacao_id' => $melhor->id,
                    'sufixo_confirmado' => 1,
                ]);
                Auditoria::registrar('sufixo.confirmado', $empresa, null, [
                    'codigo' => $melhor->codigo,
                    'score' => $melhorScore,
                ]);
            }

            return self::resultado('CONFIRMADO', $melhor->codigo, $melhorScore,
                'Sufixo confirmado com sucesso pela associação.');
        }

        if ($melhorScore >= 30) {
            return self::resultado('PENDENTE', $melhor->codigo, $melhorScore,
                'Sufixo encontrado, mas sem confiança suficiente. Pendente de confirmação.');
        }

        return self::resultado('NAO_CONFIRMADO', $melhor->codigo ?? null, $melhorScore,
            'Dados não conferem com a associação.');
    }

    /** Monta o array-padrão de retorno da checagem. */
    private static function resultado(string $status, ?string $codigo, int $score, string $mensagem): array
    {
        return compact('status', 'codigo', 'score', 'mensagem');
    }

    /** Compara duas strings ignorando acento e caixa. */
    private static function igualSemAcento(string $a, string $b): bool
    {
        return self::normalizar($a) === self::normalizar($b);
    }

    /** Remove acentos e converte para minúsculas. */
    private static function normalizar(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        if (function_exists('transliterator_transliterate')) {
            return transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC', $s);
        }
        // Fallback para iconv
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);

        return $s ?: '';
    }

    /** Extrai o primeiro nome de uma string. */
    private static function primeiroNome(string $nome): string
    {
        $partes = preg_split('/\s+/', trim($nome));

        return $partes[0] ?? '';
    }

    public static function importarLote(array $linhas, string $associacao = 'ABCCMM'): int
    {
        $n = 0;
        foreach ($linhas as $l) {
            $codigo = self::limparCodigo($l['codigo'] ?? '');
            $sufixo = Texto::maiusculas(trim($l['sufixo'] ?? ''));
            if (! $codigo || ! $sufixo) {
                continue;
            }
            DB::table('sufixo_associacao')->updateOrInsert(
                ['associacao' => $associacao, 'codigo' => $codigo],
                [
                    'sufixo' => $sufixo,
                    'proprietario_nome' => Texto::maiusculas(trim($l['proprietario_nome'] ?? '')),
                    'proprietario_documento' => Documento::digitos($l['proprietario_documento'] ?? ''),
                    'proprietario_celular' => self::limparCodigo($l['proprietario_celular'] ?? ''),
                    'proprietario_telefone' => self::limparCodigo($l['proprietario_telefone'] ?? ''),
                    'uf' => mb_strtoupper(trim($l['uf'] ?? '')),
                    'cidade' => Texto::maiusculas(trim($l['cidade'] ?? '')),
                    'cep' => self::limparCodigo($l['cep'] ?? ''),
                    'importado_em' => now('UTC'),
                ]
            );
            $n++;
        }

        return $n;
    }
}
