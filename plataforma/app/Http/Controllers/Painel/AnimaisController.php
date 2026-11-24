<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Support\Auditoria;
use App\Support\CadastroRapido;
use App\Support\Esquema;
use App\Support\FotosAnimal;
use App\Support\ImportacaoTropa;
use App\Support\Licenca;
use App\Support\Sociedade;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AnimaisController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $query = Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em');

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('registro', 'like', "%{$busca}%")
                  ->orWhere('microchip', 'like', "%{$busca}%")
                  ->orWhere('numero_plantel', 'like', "%{$busca}%");
            });
        }
        if (Esquema::temV005()) {
            if ($especie = $request->input('especie')) {
                $query->where('especie', $especie);
            }
            if ($situacao = $request->input('situacao')) {
                $query->where('situacao', $situacao);
            }
        }
        if ($categoria = $request->input('categoria')) {
            $query->where('categoria', $categoria);
        }

        $animais = $query->orderBy('nome')->get();

        $base = Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em');
        if (Esquema::temV005()) {
            $totais = (clone $base)->selectRaw("COUNT(*) as total, SUM(situacao='ativo') as ativos, SUM(sexo='macho') as machos, SUM(sexo='femea') as femeas")->first();
        } else {
            $totais = (clone $base)->selectRaw("COUNT(*) as total, COUNT(*) as ativos, SUM(sexo='macho') as machos, SUM(sexo='femea') as femeas")->first();
        }

        return view('painel.animais.index', [
            'animais' => $animais,
            'fotos' => FotosAnimal::principais($animais->pluck('id')),
            'totais' => $totais,
            'podeCadastrar' => Licenca::podeCadastrarAnimal($empresa),
            'ativosPlantel' => Licenca::animais($empresa),
            // Animais de outras propriedades em que esta é sócia (lançada pelo CNPJ/CPF dela)
            'sociedades' => Sociedade::animaisDoSocio($empresa->documento),
            'limite' => Licenca::limite($empresa),
            'faixa' => Licenca::faixaAtual($empresa),
        ]);
    }

    /** Tela "Importar tropa": baixar a planilha-modelo com a tropa atual e enviar o arquivo preenchido. */
    public function importar(Request $request)
    {
        $empresa = $request->attributes->get('haras');

        return view('painel.animais.importar', [
            'colunas' => ImportacaoTropa::colunas(),
            'total' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->count(),
            'limite' => Licenca::limite($empresa),
            'ativos' => Licenca::animais($empresa),
            'podeEscrever' => \App\Support\Permissao::pode($request->attributes->get('papel'), 'animais', true),
            'resultado' => session('resultado_importacao'),
        ]);
    }

    /** Baixa um .xlsx com toda a tropa, na estrutura do banco: serve de modelo e de backup para reimportar. */
    public function exportar(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $caminho = ImportacaoTropa::exportar($empresa);
        $nome = 'tropa-'.(Str::slug($empresa->nome) ?: 'minhatropa').'-'.now('America/Sao_Paulo')->format('Y-m-d').'.xlsx';
        Auditoria::registrar('animal.exportar', $empresa, null, [
            'total' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->count(),
        ]);

        return response()->download($caminho, $nome, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Recebe a planilha preenchida e cadastra/atualiza a tropa de uma vez (resultado mostrado na própria tela). */
    public function importarProcessar(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $request->validate(
            ['arquivo' => ['required', 'file', 'max:10240']],
            [
                'arquivo.required' => 'Escolha a planilha preenchida para importar.',
                'arquivo.file' => 'Envio inválido. Tente de novo.',
                'arquivo.max' => 'A planilha é muito grande (máximo 10 MB).',
            ]
        );

        $arquivo = $request->file('arquivo');
        $ext = strtolower($arquivo->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            return back()->withErrors(['arquivo' => 'Formato não aceito. Use o modelo em Excel (.xlsx) ou um arquivo .csv.']);
        }

        try {
            $resultado = ImportacaoTropa::importar($empresa, $arquivo->getRealPath(), $request->user()->id);
        } catch (\Throwable $e) {
            return back()->withErrors(['arquivo' => 'Não foi possível ler a planilha. Confira se é o modelo em .xlsx (ou .csv) e tente de novo.']);
        }
        Licenca::esquecerFaixas();

        return redirect()->route('painel.animais.importar')->with('resultado_importacao', $resultado);
    }

    public function create(Request $request)
    {
        // Trava de limite: no limite da faixa, o cadastro é bloqueado e a tela mostra o aviso com a oferta de upgrade.
        if (! Licenca::podeCadastrarAnimal($request->attributes->get('haras'))) {
            return redirect()->route('painel.animais')->with('limite_plantel', true);
        }

        return view('painel.animais.form', ['animal' => new Animal, 'socios' => collect(), 'fotos' => collect()]);
    }

    public function store(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $dados = $this->validar($request);
        $request->validate(FotosAnimal::regras(), FotosAnimal::mensagens());
        $socios = Sociedade::validar($request, $empresa);
        // Só animal que entra no plantel ativo conta para a faixa (vendido, transferido e morto ficam de fora).
        $contaNoPlantel = ! in_array($dados['situacao'] ?? 'ativo', Licenca::FORA_DO_PLANTEL, true);
        if ($contaNoPlantel && ! Licenca::podeCadastrarAnimal($empresa)) {
            return redirect()->route('painel.animais')->with('limite_plantel', true);
        }
        $animal = Animal::create($dados + ['empresa_id' => $empresa->id, 'criado_por' => $request->user()->id]);
        Sociedade::salvar($animal, $socios, $request->user()->id);
        Auditoria::registrar('animal.criar', $animal, null, $socios ? ['socios' => $socios] : null);
        try {
            $this->salvarFotos($request, $animal);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // O animal já está cadastrado: volta para a edição só para trocar a foto que não deu certo.
            return redirect()->to(route('painel.animais.editar', $animal).'#fotos')->withErrors($e->errors())->with('ok', "{$animal->nome} cadastrado(a). Falta só acertar a foto.");
        }

        // Com sócios, abre a ficha: é lá que ficam os links de convite para enviar a quem ainda não tem cadastro.
        return $socios
            ? redirect()->route('painel.animais.show', $animal)->with('ok', "{$animal->nome} cadastrado(a) com ".count($socios).' sócio(s).')
            : redirect()->route('painel.animais')->with('ok', "{$animal->nome} cadastrado(a).");
    }

    public function show(Request $request, Animal $animal)
    {
        $this->doHaras($request, $animal);

        return view('painel.animais.show', [
            'animal' => $animal,
            'fotos' => FotosAnimal::doAnimal($animal),
            'socios' => Sociedade::doAnimal($animal),
            'parteDaPropriedade' => Sociedade::parteDaPropriedade($animal),
            'arvore' => $animal->arvoreGenealogica(3),
        ]);
    }

    public function edit(Request $request, Animal $animal)
    {
        $this->doHaras($request, $animal);

        return view('painel.animais.form', ['animal' => $animal, 'socios' => Sociedade::doAnimal($animal), 'fotos' => FotosAnimal::doAnimal($animal)]);
    }

    public function update(Request $request, Animal $animal)
    {
        $this->doHaras($request, $animal);
        $antes = $animal->getAttributes();
        $dados = $this->validar($request);
        $request->validate(FotosAnimal::regras(), FotosAnimal::mensagens());
        $socios = Sociedade::validar($request, $request->attributes->get('haras'));
        // Voltar um animal ao plantel ativo também respeita o limite da faixa.
        $estavaFora = in_array($animal->situacao, Licenca::FORA_DO_PLANTEL, true);
        $ficaDentro = ! in_array($dados['situacao'] ?? $animal->situacao, Licenca::FORA_DO_PLANTEL, true);
        if ($estavaFora && $ficaDentro && ! Licenca::podeCadastrarAnimal($request->attributes->get('haras'))) {
            return redirect()->route('painel.animais')->with('limite_plantel', true);
        }
        $animal->update($dados);
        if (Sociedade::disponivel() && $request->has('sociedade')) {
            Sociedade::salvar($animal, $socios, $request->user()->id);
        }
        Auditoria::registrar('animal.editar', $animal, $antes, $socios ? ['socios' => $socios] : null);
        $this->salvarFotos($request, $animal);

        return redirect()->route('painel.animais.show', $animal)->with('ok', "Dados de {$animal->nome} salvos.");
    }

    /**
     * Próximo número de plantel da propriedade (JSON): o maior número já usado + 1, mantendo os zeros à esquerda.
     * Conta todos os animais da propriedade (vendido, morto e excluído também), para não repetir número.
     */
    public function proximoNumero(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $maior = null;
        foreach (Animal::where('empresa_id', $empresa->id)->whereNotNull('numero_plantel')->pluck('numero_plantel') as $n) {
            $n = trim((string) $n);
            if ($n !== '' && ctype_digit($n) && strlen($n) <= 15 && ($maior === null || (int) $n > (int) $maior)) {
                $maior = $n;
            }
        }
        $proximo = $maior === null ? '1' : str_pad((string) ((int) $maior + 1), strlen($maior), '0', STR_PAD_LEFT);

        return response()->json(['numero' => $proximo, 'ultimo' => $maior]);
    }

    /** Busca de sócio pelo CPF ou CNPJ (JSON): diz se já tem cadastro no MinhaTropa e traz o nome. */
    public function buscarSocio(Request $request)
    {
        $doc = \App\Support\Documento::digitos($request->query('documento'));
        if (! in_array(strlen($doc), [11, 14], true) || ! \App\Support\Documento::valido($doc)) {
            return response()->json(['situacao' => 'invalido', 'mensagem' => 'CPF ou CNPJ inválido.']);
        }
        if ($doc === $request->attributes->get('haras')->documento) {
            return response()->json(['situacao' => 'invalido', 'mensagem' => 'Esse é o documento desta propriedade. A parte dela é o que sobra dos sócios.']);
        }
        $achado = Sociedade::localizar($doc);

        return response()->json($achado
            ? ['situacao' => 'encontrado', 'tipo' => $achado['tipo'], 'nome' => $achado['nome'], 'detalhe' => $achado['detalhe']]
            : ['situacao' => 'novo', 'tipo' => strlen($doc) === 11 ? 'profissional' : 'propriedade']);
    }

    public function morte(Request $request, Animal $animal)
    {
        $this->doHaras($request, $animal);
        $request->validate([
            'morte_data' => ['required', 'date', 'before_or_equal:today'],
            'morte_causa' => ['nullable', 'string', 'max:255'],
            'morte_laudo' => ['nullable', 'string', 'max:5000'],
        ]);
        $antes = $animal->getAttributes();
        $animal->update([
            'situacao' => 'morto',
            'situacao_data' => $request->input('morte_data'),
            'morte_data' => $request->input('morte_data'),
            'morte_causa' => $request->input('morte_causa'),
            'morte_laudo' => $request->input('morte_laudo'),
        ]);
        Auditoria::registrar('animal.morte', $animal, $antes);

        return redirect()->route('painel.animais.show', $animal)->with('ok', "Óbito de {$animal->nome} registrado.");
    }

    /** Exclusão lógica de um animal (cadastro errado). Fica fora da lista e não conta na faixa; permanece no histórico. */
    public function destroy(Request $request, Animal $animal)
    {
        $this->doHaras($request, $animal);
        $antes = $animal->getAttributes();
        $animal->excluido_em = now('UTC');
        $animal->save();
        Auditoria::registrar('animal.excluir', $animal, $antes);
        Licenca::esquecerFaixas();

        return redirect()->route('painel.animais')->with('ok', "{$animal->nome} excluído(a) do plantel.");
    }

    /** Exclusão em massa pela lista (ex.: tropa importada no Haras errado). Só animais desta propriedade. */
    public function excluirVarios(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $request->validate(
            ['uuids' => ['required', 'array', 'min:1'], 'uuids.*' => ['string']],
            ['uuids.required' => 'Selecione ao menos um animal para excluir.']
        );
        $animais = Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')
            ->whereIn('uuid', $request->input('uuids'))->get();
        $n = 0;
        foreach ($animais as $animal) {
            $antes = $animal->getAttributes();
            $animal->excluido_em = now('UTC');
            $animal->save();
            Auditoria::registrar('animal.excluir', $animal, $antes);
            $n++;
        }
        Licenca::esquecerFaixas();

        $msg = $n === 0 ? 'Nenhum animal foi excluído.' : ($n === 1 ? '1 animal excluído do plantel.' : "{$n} animais excluídos do plantel.");

        return redirect()->route('painel.animais')->with($n > 0 ? 'ok' : 'aviso', $msg);
    }

    public function cadastroRapido(Request $request)
    {
        $request->validate(['texto' => ['required', 'string', 'max:10000']]);
        $dados = CadastroRapido::interpretar($request->input('texto'));

        return response()->json($dados);
    }

    public function buscarGenitor(Request $request)
    {
        $q = $request->input('q', '');
        $sexo = $request->input('sexo');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $query = Animal::whereNull('excluido_em')
            ->where('situacao', '!=', 'morto')
            ->where(function ($qb) use ($q) {
                $qb->where('nome', 'like', "%{$q}%")
                    ->orWhere('registro', 'like', "%{$q}%");
            });

        if ($sexo) {
            $query->where('sexo', $sexo);
        }

        $animais = $query->with('empresa:id,nome')->limit(20)->get(['id', 'nome', 'raca', 'registro', 'empresa_id']);

        return response()->json($animais->map(fn ($a) => [
            'id' => $a->id,
            'nome' => $a->nome,
            'raca' => $a->raca ?? '',
            'registro' => $a->registro ?? '',
            'haras' => $a->empresa?->nome ?? '',
        ]));
    }

    /** Foto do animal (posição 1 a 3), só para quem acessa a propriedade. O arquivo fica fora do public_html. */
    public function foto(Request $request, Animal $animal, int $posicao)
    {
        $this->doHaras($request, $animal);
        $foto = FotosAnimal::doAnimal($animal)->get($posicao);
        $disco = \Illuminate\Support\Facades\Storage::disk('midia');
        abort_unless($foto && str_starts_with($foto->arquivo, 'animais/'.$animal->empresa_id.'/') && $disco->exists($foto->arquivo), 404);

        return response()->file($disco->path($foto->arquivo), ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=604800', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function salvarFotos(Request $request, Animal $animal): void
    {
        $mudou = FotosAnimal::salvar($request, $animal, $request->user()->id);
        if ($mudou) {
            Auditoria::registrar('animal.fotos', $animal, null, ['posicoes' => $mudou]);
        }
    }

    private function doHaras(Request $request, Animal $animal): void
    {
        abort_unless($animal->empresa_id === $request->attributes->get('haras')->id && ! $animal->excluido_em, 404);
    }

    private function validar(Request $request): array
    {
        if (! Esquema::temV005()) {
            return $request->validate([
                'nome' => ['required', 'string', 'max:120'],
                'sexo' => ['nullable', Rule::in(['macho', 'femea'])],
                'categoria' => ['nullable', Rule::in(array_keys(Animal::CATEGORIAS))],
                'raca' => ['nullable', 'string', 'max:80'],
                'pelagem' => ['nullable', 'string', 'max:80'],
                'data_nascimento' => ['nullable', 'date', 'before_or_equal:today'],
                'registro' => ['nullable', 'string', 'max:60'],
                'microchip' => ['nullable', 'string', 'max:40'],
                'pai' => ['nullable', 'string', 'max:120'],
                'mae' => ['nullable', 'string', 'max:120'],
                'observacoes' => ['nullable', 'string', 'max:5000'],
            ]);
        }

        return $request->validate([
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
            'local_nascimento' => ['nullable', 'string', 'max:200'],
            'criador_nome' => ['nullable', 'string', 'max:150'],
            'criador_cidade' => ['nullable', 'string', 'max:100'],
            'criador_uf' => ['nullable', 'string', 'size:2'],
            'proprietario_nome' => ['nullable', 'string', 'max:150'],
            'proprietario_cidade' => ['nullable', 'string', 'max:100'],
            'proprietario_uf' => ['nullable', 'string', 'size:2'],
            'registro' => ['nullable', 'string', 'max:60'],
            'registro_associacao' => ['nullable', Rule::in(array_keys(Animal::ASSOCIACOES))],
            'id_associacao' => ['nullable', 'string', 'max:30'],
            'registro_definitivo' => ['nullable', 'boolean'],
            'microchip' => ['nullable', 'string', 'max:40'],
            'dna_coletado' => ['nullable', 'boolean'],
            'numero_plantel' => ['nullable', 'string', 'max:30'],
            'local_baia' => ['nullable', 'string', 'max:80'],
            'peso_kg' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'altura_cm' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'castrado' => ['nullable', 'boolean'],
            'pai' => ['nullable', 'string', 'max:120'],
            'pai_id' => ['nullable', 'integer', 'exists:animal,id'],
            'pai_registro' => ['nullable', 'string', 'max:60'],
            'mae' => ['nullable', 'string', 'max:120'],
            'mae_id' => ['nullable', 'integer', 'exists:animal,id'],
            'mae_registro' => ['nullable', 'string', 'max:60'],
            'data_aquisicao' => ['nullable', 'date', 'before_or_equal:today'],
            'forma_aquisicao' => ['nullable', Rule::in(array_keys(Animal::FORMAS_AQUISICAO))],
            'valor_aquisicao' => ['nullable', 'numeric', 'min:0'],
            'situacao' => ['nullable', Rule::in(array_keys(Animal::SITUACOES))],
            'situacao_data' => ['nullable', 'date'],
            'situacao_motivo' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
