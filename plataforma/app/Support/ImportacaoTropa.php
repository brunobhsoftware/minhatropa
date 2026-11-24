<?php

namespace App\Support;

use App\Models\Animal;
use App\Models\Empresa;
use App\Support\Texto;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Exportação e importação do plantel inteiro por planilha (.xlsx / .csv).
 *
 * A ideia (pedido do Bruno, 23/11/2026): o Haras baixa uma planilha já com a estrutura do banco e toda a tropa
 * dentro, preenche à vontade no Excel e devolve o arquivo; o sistema cadastra tudo de uma vez.
 *
 *  - A 1ª coluna é o "Código" (uuid) do animal. Linha com código de um animal da propriedade = ATUALIZA aquele animal;
 *    linha sem código (ou com código desconhecido) = CADASTRA um animal novo.
 *  - As colunas de lista (espécie, categoria, pelagem, situação, etc.) aceitam o rótulo em português OU o código interno,
 *    sem diferença de acento/maiúsculas. O que não bate fica em branco e vira um aviso — a linha não é perdida por isso.
 *  - A importação respeita o limite da faixa de plantel: animais novos ativos entram até o limite; o resto é barrado com aviso.
 */
class ImportacaoTropa
{
    /**
     * Siglas aceitas na coluna Raça (além do nome por extenso). Fáceis de ampliar.
     * O que não casar por sigla nem pelo nome da lista entra como foi digitado (texto livre).
     */
    public const RACAS_SIGLAS = [
        'MM' => 'Mangalarga Marchador',
        'MMC' => 'Mangalarga Marchador',
        'ML' => 'Mangalarga',
        'MGL' => 'Mangalarga',
        'CP' => 'Campolina',
        'CPL' => 'Campolina',
        'QM' => 'Quarto de Milha',
        'CR' => 'Crioulo',
        'LUS' => 'Lusitano',
        'PSL' => 'Lusitano',
        'PSI' => 'Puro Sangue Inglês',
        'BH' => 'Brasileiro de Hipismo',
        'ARA' => 'Árabe',
        'PH' => 'Paint Horse',
        'APP' => 'Appaloosa',
        'PANT' => 'Pantaneiro',
        'AND' => 'Andaluz',
        'FRI' => 'Friesian',
        'PERCH' => 'Percheron',
    ];

    /** Colunas da planilha, na ordem: chave do campo => [rótulo visível, tipo de conversão]. */
    public static function colunas(): array
    {
        return [
            'uuid' => ['Código (não alterar)', 'codigo'],
            'nome' => ['Nome *', 'texto'],
            'nome_registro' => ['Nome de registro', 'texto'],
            'apelido' => ['Apelido', 'texto'],
            'especie' => ['Espécie', 'especie'],
            'sexo' => ['Sexo', 'sexo'],
            'categoria' => ['Categoria', 'categoria'],
            'raca' => ['Raça', 'raca'],
            'pelagem' => ['Pelagem', 'pelagem'],
            'pelagem_complemento' => ['Pelagem (outra, qual)', 'texto'],
            'data_nascimento' => ['Data de nascimento', 'data'],
            'numero_plantel' => ['Nº plantel', 'texto'],
            'local_baia' => ['Baia / local', 'texto'],
            'registro' => ['Registro (nº)', 'texto'],
            'registro_associacao' => ['Associação do registro', 'associacao'],
            'registro_definitivo' => ['Registro definitivo', 'bool'],
            'microchip' => ['Microchip', 'texto'],
            'dna_coletado' => ['DNA coletado', 'bool'],
            'peso_kg' => ['Peso (kg)', 'decimal'],
            'altura_cm' => ['Altura (cm)', 'decimal'],
            'castrado' => ['Castrado', 'bool'],
            'pai' => ['Pai (nome)', 'texto'],
            'mae' => ['Mãe (nome)', 'texto'],
            'data_aquisicao' => ['Data de aquisição', 'data'],
            'forma_aquisicao' => ['Forma de aquisição', 'forma'],
            'valor_aquisicao' => ['Valor de aquisição (R$)', 'decimal'],
            'situacao' => ['Situação', 'situacao'],
            'situacao_data' => ['Data da situação', 'data'],
            'situacao_motivo' => ['Motivo da situação', 'texto'],
            'observacoes' => ['Observações', 'texto'],
        ];
    }

    /** Só os rótulos, na ordem (cabeçalho da planilha). */
    public static function rotulos(): array
    {
        return array_values(array_map(fn ($c) => $c[0], self::colunas()));
    }

    // ============================================================ EXPORTAÇÃO

    /** Gera o .xlsx com toda a tropa da propriedade e devolve o caminho do arquivo temporário. */
    public static function exportar(Empresa $empresa): string
    {
        $colunas = self::colunas();
        $animais = Animal::where('empresa_id', $empresa->id)
            ->whereNull('excluido_em')
            ->orderBy('nome')
            ->get();

        $linhas = [];
        foreach ($animais as $a) {
            $linha = [];
            foreach ($colunas as $campo => [$rotulo, $tipo]) {
                $linha[] = self::paraPlanilha($a, $campo, $tipo);
            }
            $linhas[] = $linha;
        }

        return Planilha::escrever(self::rotulos(), $linhas, 'Tropa');
    }

    /** Converte o valor de um campo do animal para texto da planilha (rótulos legíveis, datas e números em pt-BR). */
    private static function paraPlanilha(Animal $a, string $campo, string $tipo): string
    {
        $v = $a->getAttribute($campo);
        if ($campo === 'uuid') {
            return (string) $a->uuid;
        }
        if ($v === null || $v === '') {
            return '';
        }

        return match ($tipo) {
            'data' => self::dataParaBr($v),
            'bool' => $v ? 'Sim' : 'Não',
            'decimal' => self::decimalParaBr($v),
            'especie' => Animal::ESPECIES[$v] ?? (string) $v,
            'sexo' => $v === 'macho' ? 'Macho' : ($v === 'femea' ? 'Fêmea' : (string) $v),
            'categoria' => Animal::CATEGORIAS[$v] ?? (string) $v,
            'pelagem' => Animal::PELAGENS[$v] ?? (string) $v,
            'associacao' => Animal::ASSOCIACOES[$v] ?? (string) $v,
            'forma' => Animal::FORMAS_AQUISICAO[$v] ?? (string) $v,
            'situacao' => Animal::SITUACOES[$v] ?? (string) $v,
            default => (string) $v,
        };
    }

    private static function dataParaBr($v): string
    {
        try {
            return \Illuminate\Support\Carbon::parse($v)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $v;
        }
    }

    private static function decimalParaBr($v): string
    {
        $n = (float) $v;
        // Sem casas desnecessárias: inteiro fica sem vírgula; o resto com vírgula decimal.
        if (floor($n) == $n) {
            return (string) (int) $n;
        }

        return rtrim(rtrim(number_format($n, 2, ',', ''), '0'), ',');
    }

    // ============================================================ IMPORTAÇÃO

    /**
     * Lê o arquivo enviado e cadastra/atualiza a tropa.
     * Devolve: ['total','criados','atualizados','ignorados','erros'=>[...], 'avisos'=>[...]].
     */
    public static function importar(Empresa $empresa, string $arquivo, int $usuarioId): array
    {
        $linhas = Planilha::ler($arquivo);
        $res = ['total' => 0, 'criados' => 0, 'atualizados' => 0, 'ignorados' => 0, 'erros' => [], 'avisos' => []];

        if (count($linhas) < 1) {
            $res['erros'][] = 'A planilha está vazia.';

            return $res;
        }

        // Cabeçalho → mapa coluna física (índice) por campo. Aceita o rótulo (sem acento/maiúsculas) ou a própria chave.
        $cabecalho = array_shift($linhas);
        $mapa = self::mapearColunas($cabecalho);
        if (! isset($mapa['nome'])) {
            $res['erros'][] = 'A planilha precisa ter a coluna "Nome". Baixe o modelo pelo botão acima e use esse formato.';

            return $res;
        }

        // Vagas no plantel segundo a faixa (animais ativos novos entram até o limite).
        $limite = Licenca::limite($empresa);
        $vagas = $limite === null ? PHP_INT_MAX : max(0, $limite - Licenca::animais($empresa));

        $colunas = self::colunas();
        $numeroLinha = 1; // a 1 é o cabeçalho; os dados começam na 2

        foreach ($linhas as $linha) {
            $numeroLinha++;
            // Linha em branco: ignora em silêncio.
            if (count(array_filter($linha, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $res['total']++;

            $bruto = [];
            foreach ($mapa as $campo => $idx) {
                $bruto[$campo] = trim((string) ($linha[$idx] ?? ''));
            }

            $nome = $bruto['nome'] ?? '';
            if ($nome === '') {
                $res['erros'][] = "Linha {$numeroLinha}: sem nome do animal — não cadastrada.";
                $res['ignorados']++;

                continue;
            }

            // Animal existente pela coluna Código (uuid) dentro da própria propriedade.
            $existente = null;
            $codigo = $bruto['uuid'] ?? '';
            if ($codigo !== '') {
                $existente = Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->where('uuid', $codigo)->first();
                if (! $existente) {
                    $res['avisos'][] = "Linha {$numeroLinha}: código \"{$codigo}\" não encontrado nesta propriedade — {$nome} foi cadastrado(a) como novo.";
                }
            }
            $novo = ! $existente;

            // Monta os campos convertidos.
            [$dados, $avisosLinha] = self::converter($bruto, $colunas, $existente, $numeroLinha);
            foreach ($avisosLinha as $a) {
                $res['avisos'][] = $a;
            }

            // Limite de faixa: só animal que entra (ou volta) para o plantel ativo consome vaga.
            $situacaoFinal = $dados['situacao'] ?? ($existente->situacao ?? 'ativo');
            $ficaDentro = ! in_array($situacaoFinal, Licenca::FORA_DO_PLANTEL, true);
            $estavaFora = $existente ? in_array($existente->situacao, Licenca::FORA_DO_PLANTEL, true) : true;
            $consomeVaga = $ficaDentro && $estavaFora;
            if ($consomeVaga && $vagas <= 0) {
                $res['erros'][] = "Linha {$numeroLinha}: {$nome} não cadastrado(a) — o plantel atingiu o limite da sua faixa.";
                $res['ignorados']++;

                continue;
            }

            // Validação final com as mesmas regras do cadastro individual.
            $erroValid = self::validar($dados);
            if ($erroValid) {
                $res['erros'][] = "Linha {$numeroLinha} ({$nome}): {$erroValid}";
                $res['ignorados']++;

                continue;
            }

            try {
                if ($novo) {
                    $animal = Animal::create($dados + ['empresa_id' => $empresa->id, 'criado_por' => $usuarioId]);
                    Auditoria::registrar('animal.importar.criar', $animal);
                    $res['criados']++;
                } else {
                    $antes = $existente->getAttributes();
                    $existente->update($dados);
                    Auditoria::registrar('animal.importar.editar', $existente, $antes);
                    $res['atualizados']++;
                }
                if ($consomeVaga) {
                    $vagas--;
                }
            } catch (\Throwable $e) {
                $res['erros'][] = "Linha {$numeroLinha} ({$nome}): não foi possível salvar.";
                $res['ignorados']++;
            }
        }

        Auditoria::registrar('animal.importar', $empresa, null, [
            'criados' => $res['criados'], 'atualizados' => $res['atualizados'], 'ignorados' => $res['ignorados'],
        ]);

        return $res;
    }

    /** Converte os valores de texto de uma linha nos campos do animal, recolhendo avisos de conversão. */
    private static function converter(array $bruto, array $colunas, ?Animal $existente, int $numeroLinha): array
    {
        $dados = [];
        $avisos = [];
        $novo = ! $existente;

        foreach ($colunas as $campo => [$rotulo, $tipo]) {
            if ($campo === 'uuid') {
                continue; // nunca gravado: é só a chave de correspondência
            }
            if ($campo === 'pelagem_complemento') {
                continue; // tratado junto com a pelagem, para não apagar o complemento de "Outra"
            }
            $valor = $bruto[$campo] ?? '';

            switch ($tipo) {
                case 'texto':
                    $dados[$campo] = $valor === '' ? null : $valor;
                    break;

                case 'bool':
                    $dados[$campo] = $valor === '' ? false : self::paraBool($valor);
                    break;

                case 'decimal':
                    if ($valor === '') {
                        $dados[$campo] = null;
                    } else {
                        $n = self::paraDecimal($valor);
                        if ($n === null) {
                            $avisos[] = "Linha {$numeroLinha}: \"{$rotulo}\" não é um número (\"{$valor}\") — deixado em branco.";
                            $dados[$campo] = null;
                        } else {
                            $dados[$campo] = $n;
                        }
                    }
                    break;

                case 'data':
                    if ($valor === '') {
                        $dados[$campo] = null;
                    } else {
                        $d = self::paraData($valor);
                        if ($d === null) {
                            $avisos[] = "Linha {$numeroLinha}: \"{$rotulo}\" com data inválida (\"{$valor}\") — deixada em branco.";
                            $dados[$campo] = null;
                        } else {
                            $dados[$campo] = $d;
                        }
                    }
                    break;

                case 'especie':
                    // Aceita a inicial (E = equino, A = asinino, M = muar). Em branco (ou não reconhecida) vira Equino.
                    $n = self::normalizar($valor);
                    $cod = match ($n) {
                        'e' => 'equino',
                        'a' => 'asinino',
                        'm' => 'muar',
                        default => self::casar($valor, Animal::ESPECIES),
                    };
                    if ($valor !== '' && $cod === null) {
                        $avisos[] = "Linha {$numeroLinha}: espécie \"{$valor}\" não reconhecida — usado Equino.";
                    }
                    $dados['especie'] = $cod ?? 'equino';
                    break;

                case 'sexo':
                    // Acha "macho"/"fêmea" em qualquer parte do texto (ex.: "PROVISÓRIO FÊMEA"); também aceita M/F.
                    // A string combinada da associação pode ter sido colada no campo Raça: se o Sexo não resolver, procura lá.
                    $cod = self::sexoDeTexto($valor) ?? self::sexoDeTexto($bruto['raca'] ?? '');
                    if ($valor !== '' && $cod === null) {
                        $avisos[] = "Linha {$numeroLinha}: sexo \"{$valor}\" não reconhecido — deixado em branco.";
                    }
                    $dados['sexo'] = $cod; // nulo quando em branco ou não reconhecido
                    break;

                case 'raca':
                    // Raça reconhecida no campo Raça; se não, tenta a sigla no início do campo Sexo (string combinada lá).
                    // Nada reconhecido: fica o texto digitado no campo Raça.
                    $rec = self::racaReconhecida($valor) ?? self::racaReconhecida($bruto['sexo'] ?? '');
                    $dados['raca'] = $rec ?? (trim($valor) !== '' ? mb_substr(trim($valor), 0, 80) : null);
                    break;

                case 'categoria':
                    $cod = self::casar($valor, Animal::CATEGORIAS);
                    if ($cod === null && $valor !== '') {
                        // Aceita também os rótulos por espécie que a pessoa vê no cadastro (ex.: "Matriz (Égua)").
                        foreach (Animal::CATEGORIAS_POR_ESPECIE as $cats) {
                            $cod = self::casar($valor, $cats);
                            if ($cod !== null) {
                                break;
                            }
                        }
                    }
                    if ($valor !== '' && $cod === null) {
                        $avisos[] = "Linha {$numeroLinha}: categoria \"{$valor}\" não reconhecida — deixada em branco.";
                    }
                    $dados['categoria'] = $cod;
                    break;

                case 'associacao':
                    $cod = self::casar($valor, Animal::ASSOCIACOES);
                    if ($valor !== '' && $cod === null) {
                        $avisos[] = "Linha {$numeroLinha}: associação \"{$valor}\" não reconhecida — deixada em branco.";
                    }
                    $dados['registro_associacao'] = $cod;
                    break;

                case 'forma':
                    $cod = self::casar($valor, Animal::FORMAS_AQUISICAO);
                    if ($valor !== '' && $cod === null) {
                        $avisos[] = "Linha {$numeroLinha}: forma de aquisição \"{$valor}\" não reconhecida — deixada em branco.";
                    }
                    $dados['forma_aquisicao'] = $cod;
                    break;

                case 'situacao':
                    // Em branco vira sempre Ativo (pedido do Bruno). "Vivo" também conta como Ativo.
                    // Valor não reconhecido mantém o atual do animal.
                    $cod = self::normalizar($valor) === 'vivo' ? 'ativo' : self::casar($valor, Animal::SITUACOES);
                    if ($valor === '') {
                        $dados['situacao'] = 'ativo';
                    } elseif ($cod !== null) {
                        $dados['situacao'] = $cod;
                    } else {
                        $avisos[] = "Linha {$numeroLinha}: situação \"{$valor}\" não reconhecida — mantida a atual.";
                        $dados['situacao'] = $novo ? 'ativo' : ($existente->situacao ?? 'ativo');
                    }
                    break;

                case 'pelagem':
                    // Pelagem fora da lista não trava: vira "Outra" e o texto digitado vai para o complemento.
                    $complemento = trim((string) ($bruto['pelagem_complemento'] ?? ''));
                    $cod = self::casar($valor, Animal::PELAGENS);
                    if ($valor === '') {
                        $dados['pelagem'] = null;
                        $dados['pelagem_complemento'] = $complemento === '' ? null : $complemento;
                    } elseif ($cod !== null) {
                        $dados['pelagem'] = $cod;
                        $dados['pelagem_complemento'] = $complemento === '' ? null : $complemento;
                    } else {
                        $dados['pelagem'] = 'outra';
                        $dados['pelagem_complemento'] = $complemento !== '' ? $complemento : $valor;
                    }
                    break;
            }
        }

        // Nomes 100% em MAIÚSCULAS (como no cadastro individual) e preenchimento do que faltou:
        //  - Nome de registro em branco copia o nome; Apelido em branco recebe o primeiro nome.
        $nome = Texto::maiusculas(trim((string) ($dados['nome'] ?? '')));
        if ($nome !== null && $nome !== '') {
            $dados['nome'] = mb_substr($nome, 0, 120);
            $dados['nome_registro'] = empty($dados['nome_registro'])
                ? mb_substr($nome, 0, 150)
                : mb_substr((string) Texto::maiusculas($dados['nome_registro']), 0, 150);
            if (empty($dados['apelido'])) {
                $primeiro = preg_split('/\s+/', $nome)[0] ?? $nome;
                $dados['apelido'] = mb_substr($primeiro, 0, 80);
            } else {
                $dados['apelido'] = mb_substr((string) Texto::maiusculas($dados['apelido']), 0, 80);
            }
        }

        // Deduções cruzadas, com tudo já convertido:
        //  - Raça de cavalo reconhecida (MM etc.) implica espécie Equino.
        if (! empty($dados['raca']) && in_array($dados['raca'], \App\Models\Empresa::RACAS, true) && $dados['raca'] !== 'Outras') {
            $dados['especie'] = 'equino';
        }
        //  - Associação em branco: deduz pela raça (MM → ABCCMM, QM → ABQM…).
        if (array_key_exists('registro_associacao', $dados) && empty($dados['registro_associacao'])) {
            $dados['registro_associacao'] = self::assocDaRaca($dados['raca'] ?? null);
        }
        //  - Categoria em branco num equino: fêmea → Égua, macho → Garanhão (padrão).
        if (empty($dados['categoria']) && ($dados['especie'] ?? null) === 'equino') {
            $dados['categoria'] = match ($dados['sexo'] ?? null) {
                'femea' => 'egua',
                'macho' => 'garanhao',
                default => $dados['categoria'] ?? null,
            };
        }

        return [$dados, $avisos];
    }

    /** Sexo a partir de um texto qualquer: acha "macho"/"fêmea" em qualquer parte, ou M/F. Nulo se não achar. */
    private static function sexoDeTexto(string $valor): ?string
    {
        $n = self::normalizar($valor);

        return match (true) {
            str_contains($n, 'femea') => 'femea',
            str_contains($n, 'macho') => 'macho',
            $n === 'f' => 'femea',
            $n === 'm' => 'macho',
            default => null,
        };
    }

    /**
     * Raça RECONHECIDA a partir de um texto: nome da lista, sigla exata, ou sigla no início
     * (ex.: "MM1 - PROVISÓRIO FÊMEA" → Mangalarga Marchador). Nulo quando não reconhece (não devolve texto livre).
     */
    private static function racaReconhecida(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        $alvo = self::normalizar($valor);

        // Nome por extenso da lista.
        foreach (\App\Models\Empresa::RACAS as $nome) {
            if (self::normalizar($nome) === $alvo) {
                return $nome;
            }
        }
        // Sigla exata.
        foreach (self::RACAS_SIGLAS as $sigla => $nome) {
            if (self::normalizar($sigla) === $alvo) {
                return $nome;
            }
        }
        // Sigla no início (siglas mais longas primeiro), seguida de não-letra (dígito, espaço, traço) ou fim.
        $siglas = self::RACAS_SIGLAS;
        uksort($siglas, fn ($a, $b) => mb_strlen((string) $b) <=> mb_strlen((string) $a));
        foreach ($siglas as $sigla => $nome) {
            $s = self::normalizar((string) $sigla);
            if ($s !== '' && str_starts_with($alvo, $s)) {
                $depois = mb_substr($alvo, mb_strlen($s), 1);
                if ($depois === '' || ! preg_match('/\p{L}/u', $depois)) {
                    return $nome;
                }
            }
        }

        return null;
    }

    /** Associação padrão pela raça (MM → ABCCMM etc.), usada quando a coluna Associação vem em branco. */
    private static function assocDaRaca(?string $raca): ?string
    {
        $mapa = [
            'Mangalarga Marchador' => 'ABCCMM',
            'Mangalarga' => 'ABCCMangalarga',
            'Campolina' => 'ABCCC',
            'Quarto de Milha' => 'ABQM',
            'Crioulo' => 'ABCCC_Crioulo',
            'Lusitano' => 'ABPSL',
            'Puro Sangue Inglês' => 'JCB',
            'Brasileiro de Hipismo' => 'ABCCH',
            'Árabe' => 'ABCCA',
            'Paint Horse' => 'APHA',
            'Appaloosa' => 'ABCA',
        ];

        return $raca ? ($mapa[$raca] ?? null) : null;
    }

    /** Monta o mapa campo => índice da coluna física, pelo cabeçalho enviado (rótulo ou chave, sem acento/maiúsculas). */
    private static function mapearColunas(array $cabecalho): array
    {
        $porRotulo = [];
        foreach (self::colunas() as $campo => [$rotulo, $tipo]) {
            $porRotulo[self::normalizar($rotulo)] = $campo;
            $porRotulo[self::normalizar(str_replace(' *', '', $rotulo))] = $campo;
            $porRotulo[self::normalizar($campo)] = $campo;
        }
        // Apelidos tolerantes para o cabeçalho.
        $porRotulo[self::normalizar('codigo')] = 'uuid';
        $porRotulo[self::normalizar('raça')] = 'raca';
        $porRotulo[self::normalizar('mae')] = 'mae';

        $mapa = [];
        foreach ($cabecalho as $i => $titulo) {
            $chave = self::normalizar((string) $titulo);
            if ($chave !== '' && isset($porRotulo[$chave]) && ! isset($mapa[$porRotulo[$chave]])) {
                $mapa[$porRotulo[$chave]] = $i;
            }
        }

        return $mapa;
    }

    /** Validação final de uma linha (mesmas regras do cadastro individual). Devolve a 1ª mensagem de erro ou null. */
    private static function validar(array $dados): ?string
    {
        $regras = [
            'nome' => ['required', 'string', 'max:120'],
            'nome_registro' => ['nullable', 'string', 'max:150'],
            'apelido' => ['nullable', 'string', 'max:80'],
            'especie' => ['nullable', Rule::in(array_keys(Animal::ESPECIES))],
            'sexo' => ['nullable', Rule::in(['macho', 'femea'])],
            'categoria' => ['nullable', Rule::in(array_keys(Animal::CATEGORIAS))],
            'raca' => ['nullable', 'string', 'max:80'],
            'pelagem' => ['nullable', Rule::in(array_keys(Animal::PELAGENS))],
            'pelagem_complemento' => ['nullable', 'string', 'max:80'],
            'data_nascimento' => ['nullable', 'date', 'before_or_equal:today'],
            'numero_plantel' => ['nullable', 'string', 'max:30'],
            'local_baia' => ['nullable', 'string', 'max:80'],
            'registro' => ['nullable', 'string', 'max:60'],
            'registro_associacao' => ['nullable', Rule::in(array_keys(Animal::ASSOCIACOES))],
            'microchip' => ['nullable', 'string', 'max:40'],
            'peso_kg' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'altura_cm' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'pai' => ['nullable', 'string', 'max:120'],
            'mae' => ['nullable', 'string', 'max:120'],
            'data_aquisicao' => ['nullable', 'date', 'before_or_equal:today'],
            'forma_aquisicao' => ['nullable', Rule::in(array_keys(Animal::FORMAS_AQUISICAO))],
            'valor_aquisicao' => ['nullable', 'numeric', 'min:0'],
            'situacao' => ['nullable', Rule::in(array_keys(Animal::SITUACOES))],
            'situacao_data' => ['nullable', 'date'],
            'situacao_motivo' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
        $v = Validator::make($dados, $regras);

        return $v->fails() ? $v->errors()->first() : null;
    }

    // ------------------------------------------------------------ conversões auxiliares

    /** Casa um valor de texto com um mapa código=>rótulo, sem diferença de acento/maiúsculas. Devolve o código ou null. */
    private static function casar(string $valor, array $mapa): ?string
    {
        if (trim($valor) === '') {
            return null;
        }
        $alvo = self::normalizar($valor);
        foreach ($mapa as $codigo => $rotulo) {
            if (self::normalizar((string) $codigo) === $alvo || self::normalizar((string) $rotulo) === $alvo) {
                return (string) $codigo;
            }
        }
        // Rótulos com traço/sigla (ex.: "ABCCMM — Mangalarga Marchador"): casa pela sigla antes do travessão.
        foreach ($mapa as $codigo => $rotulo) {
            $sigla = self::normalizar(preg_split('/[—\-–]/u', (string) $rotulo)[0] ?? '');
            if ($sigla !== '' && $sigla === $alvo) {
                return (string) $codigo;
            }
        }

        return null;
    }

    private static function paraBool(string $v): bool
    {
        $v = self::normalizar($v);

        return in_array($v, ['sim', 's', '1', 'true', 'verdadeiro', 'x', 'ok', 'y', 'yes'], true);
    }

    /** "1.234,56", "1234.56", "512,5", "R$ 1.200" → float; ou null se não parecer número. */
    private static function paraDecimal(string $v): ?float
    {
        $v = trim(str_ireplace(['r$', ' '], '', $v));
        if ($v === '') {
            return null;
        }
        $temVirgula = str_contains($v, ',');
        $temPonto = str_contains($v, '.');
        if ($temVirgula && $temPonto) {
            // O último separador é o decimal; o outro é separador de milhar.
            if (strrpos($v, ',') > strrpos($v, '.')) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            } else {
                $v = str_replace(',', '', $v);
            }
        } elseif ($temVirgula) {
            $v = str_replace(',', '.', $v);
        }
        // sobra só dígitos, ponto e sinal?
        if (! preg_match('/^-?\d*\.?\d+$/', $v)) {
            return null;
        }

        return (float) $v;
    }

    /** "dd/mm/aaaa", "aaaa-mm-dd", nº de série do Excel → "Y-m-d"; ou null. */
    private static function paraData(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        // Número de série do Excel (base 1899-12-30).
        if (preg_match('/^\d{1,6}(\.\d+)?$/', $v)) {
            $serie = (int) floor((float) $v);
            if ($serie > 59) {
                $serie--; // bug histórico do Excel (1900 tratado como bissexto)
            }
            try {
                return \Illuminate\Support\Carbon::create(1899, 12, 31)->addDays($serie)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        $v = str_replace(['.', '\\'], '/', $v);
        $v = str_replace('-', '/', $v);
        if (preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', $v, $m)) {
            [$ano, $mes, $dia] = [$m[1], $m[2], $m[3]];
        } elseif (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2,4})$#', $v, $m)) {
            [$dia, $mes, $ano] = [$m[1], $m[2], $m[3]];
            if (strlen($ano) === 2) {
                $ano = ((int) $ano <= 40 ? '20' : '19').$ano;
            }
        } else {
            return null;
        }
        if (! checkdate((int) $mes, (int) $dia, (int) $ano)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
    }

    /** minúsculas, sem acento, espaços colapsados — para comparar rótulos e cabeçalhos. */
    private static function normalizar(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $de = ['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ'];
        $para = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'];
        $s = str_replace($de, $para, $s);
        $s = preg_replace('/\s+/', ' ', $s);

        return trim($s);
    }
}
