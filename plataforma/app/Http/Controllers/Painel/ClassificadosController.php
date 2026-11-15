<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Classificado;
use App\Support\Auditoria;
use App\Support\Esquema;
use App\Support\FotosAnimal;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Comércio → Classificados: a propriedade põe um animal do plantel à venda, com preço e período.
 * Enquanto o anúncio está no ar, o animal aparece na página pública de classificados do site.
 */
class ClassificadosController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $this->empresa($request);
        $anuncios = Classificado::with('animal')->where('empresa_id', $empresa->id)->whereNull('excluido_em')
            ->orderByRaw("situacao = 'ativo' DESC")->orderByDesc('inicio')->orderByDesc('id')->get();

        return view('painel.classificados.index', [
            'anuncios' => $anuncios,
            'fotos' => FotosAnimal::principais($anuncios->pluck('animal_id')),
            'noAr' => $anuncios->filter(fn ($a) => $a->estado() === 'no_ar')->count(),
        ]);
    }

    public function create(Request $request)
    {
        $empresa = $this->empresa($request);
        $anuncio = new Classificado(['inicio' => Classificado::hoje(), 'contato_nome' => $empresa->responsavel_nome ?: $empresa->nome,
            'contato_whatsapp' => $empresa->whatsapp ?: $empresa->telefone_celular]);
        $escolhido = $request->query('animal') ? Animal::where('empresa_id', $empresa->id)->where('uuid', $request->query('animal'))->value('id') : null;

        return view('painel.classificados.form', ['anuncio' => $anuncio, 'animais' => $this->animaisLivres($empresa->id), 'escolhido' => $escolhido]);
    }

    public function store(Request $request)
    {
        $empresa = $this->empresa($request);
        $dados = $this->validar($request, null);
        $anuncio = Classificado::create($dados + ['situacao' => 'ativo', 'empresa_id' => $empresa->id, 'criado_por' => $request->user()->id]);
        Auditoria::registrar('classificado.criar', $anuncio, null, $dados);

        return redirect()->route('painel.classificados')->with('ok', 'Anúncio criado. '.($anuncio->estado() === 'no_ar' ? 'O animal já aparece nos classificados.' : 'Ele entra no ar em '.$anuncio->inicio->format('d/m/Y').'.'));
    }

    public function edit(Request $request, Classificado $classificado)
    {
        $this->doHaras($request, $classificado);

        return view('painel.classificados.form', ['anuncio' => $classificado, 'animais' => collect([$classificado->animal]), 'escolhido' => $classificado->animal_id]);
    }

    public function update(Request $request, Classificado $classificado)
    {
        $this->doHaras($request, $classificado);
        $antes = $classificado->getAttributes();
        $dados = $this->validar($request, $classificado);
        // Salvar de novo um anúncio retirado, vendido ou vencido põe o anúncio de volta no ar (com o novo período).
        $classificado->update($dados + ['situacao' => 'ativo', 'encerrado_em' => null]);
        Auditoria::registrar('classificado.editar', $classificado, $antes, $dados);

        return redirect()->route('painel.classificados')->with('ok', 'Anúncio salvo.');
    }

    /** Tira o anúncio do ar: retirado pela propriedade ou animal vendido. */
    public function encerrar(Request $request, Classificado $classificado)
    {
        $this->doHaras($request, $classificado);
        $motivo = $request->input('motivo') === 'vendido' ? 'vendido' : 'encerrado';
        $classificado->update(['situacao' => $motivo, 'encerrado_em' => now('UTC')]);
        Auditoria::registrar('classificado.encerrar', $classificado, null, ['motivo' => $motivo]);

        return redirect()->route('painel.classificados')->with('ok', $motivo === 'vendido'
            ? 'Anúncio marcado como vendido e retirado dos classificados. A saída do animal do plantel é registrada em Ocorrências → Transferências.'
            : 'Anúncio retirado dos classificados.');
    }

    private function empresa(Request $request)
    {
        abort_unless(Esquema::temV017(), 404);

        return $request->attributes->get('haras');
    }

    private function doHaras(Request $request, Classificado $classificado): void
    {
        abort_unless($classificado->empresa_id === $this->empresa($request)->id && ! $classificado->excluido_em, 404);
    }

    /** Animais ativos do plantel que ainda não têm anúncio aberto. */
    private function animaisLivres(int $empresaId)
    {
        $comAnuncio = Classificado::abertos()->where('empresa_id', $empresaId)->pluck('animal_id');

        return Animal::where('empresa_id', $empresaId)->whereNull('excluido_em')->where('situacao', 'ativo')
            ->whereNotIn('id', $comAnuncio)->orderBy('nome')->get();
    }

    private function validar(Request $request, ?Classificado $atual): array
    {
        $empresa = $request->attributes->get('haras');
        $request->merge([
            'preco' => self::valor($request->input('preco')),
            'contato_whatsapp' => preg_replace('/\D+/', '', (string) $request->input('contato_whatsapp')) ?: null,
        ]);
        $dados = $request->validate([
            'animal_id' => [$atual ? 'nullable' : 'required', 'integer'],
            'preco' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'inicio' => ['required', 'date'],
            'fim' => ['nullable', 'date', 'after_or_equal:inicio'],
            'descricao' => ['nullable', 'string', 'max:3000'],
            'contato_nome' => ['nullable', 'string', 'max:120'],
            'contato_whatsapp' => ['nullable', 'digits_between:10,11'],
        ], [
            'animal_id.required' => 'Escolha o animal que vai à venda.',
            'preco.required' => 'Informe o preço do animal.', 'preco.numeric' => 'Informe o preço em reais (ex.: 15.000,00).', 'preco.gt' => 'O preço precisa ser maior que zero.',
            'inicio.required' => 'Informe a partir de quando o anúncio fica no ar.',
            'fim.after_or_equal' => 'A data final não pode ser antes da data inicial.',
            'contato_whatsapp.digits_between' => 'Informe o WhatsApp com DDD (10 ou 11 números).',
        ]);
        if ($dados['fim'] ?? null) {
            if ($dados['fim'] < Classificado::hoje()) {
                throw ValidationException::withMessages(['fim' => 'A data final já passou. Escolha uma data de hoje em diante ou deixe sem prazo.']);
            }
        }
        if ($atual) {
            $dados['animal_id'] = $atual->animal_id;
        }
        $animal = Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->find($dados['animal_id']);
        if (! $animal || $animal->situacao !== 'ativo') {
            throw ValidationException::withMessages(['animal_id' => 'Só dá para anunciar animal ativo do plantel '.$empresa->doRotulo().'.']);
        }
        $outro = Classificado::abertos()->where('animal_id', $animal->id)->when($atual, fn ($q) => $q->where('id', '!=', $atual->id))->exists();
        if ($outro) {
            throw ValidationException::withMessages(['animal_id' => $animal->nome.' já tem um anúncio aberto. Altere ou retire o anúncio atual.']);
        }

        return $dados;
    }

    /** "15.000,00", "15000" ou "R$ 15.000" → 15000.00 */
    private static function valor($texto): ?string
    {
        $t = preg_replace('/[^\d,.]/', '', (string) $texto);
        if ($t === '') {
            return null;
        }
        if (str_contains($t, ',')) {
            $t = str_replace(',', '.', str_replace('.', '', $t));
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $t)) {
            $t = str_replace('.', '', $t);
        }

        return is_numeric($t) ? $t : (string) $texto;
    }
}
