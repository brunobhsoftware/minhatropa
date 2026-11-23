@extends('layouts.painel')
@section('titulo', 'Importar tropa')
@section('conteudo')
<div class="cabecalho">
    <div>
        <h1>Importar tropa</h1>
        <p>Baixe a planilha com a estrutura do plantel, preencha no Excel e envie de volta: {{ $haras->doRotulo() }} cadastra ou atualiza todos os animais de uma vez.</p>
    </div>
    <div class="acoes-cabecalho">
        <a href="{{ route('painel.animais') }}" class="botao botao--contorno"><x-icone nome="cavalo" :tamanho="18" /> Meu Plantel</a>
    </div>
</div>

@if ($resultado)
    @php
        $r = $resultado;
        $temProblema = ! empty($r['erros']) || ! empty($r['avisos']);
    @endphp
    <section class="cartao cartao--destaque" style="margin-bottom:1.5rem">
        <h2 style="margin-top:0">Resultado da importação</h2>
        <div class="grade-3" style="margin:1rem 0">
            <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Cadastrados</small><br><b style="font-size:1.6rem;color:var(--verde)">{{ $r['criados'] }}</b></div>
            <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Atualizados</small><br><b style="font-size:1.6rem">{{ $r['atualizados'] }}</b></div>
            <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Não importados</small><br><b style="font-size:1.6rem;{{ $r['ignorados'] ? 'color:var(--vermelho,#c0392b)' : '' }}">{{ $r['ignorados'] }}</b></div>
        </div>

        @if (! empty($r['erros']))
            <div class="alerta alerta--erro" role="alert" style="margin-bottom:.75rem">
                <p><b>Linhas não importadas ({{ count($r['erros']) }}):</b></p>
                <ul>@foreach ($r['erros'] as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if (! empty($r['avisos']))
            <details class="alerta alerta--aviso" style="margin-bottom:.75rem">
                <summary style="cursor:pointer"><b>Avisos ({{ count($r['avisos']) }})</b> — linhas importadas com algum campo ajustado</summary>
                <ul style="margin-top:.5rem">@foreach ($r['avisos'] as $a)<li>{{ $a }}</li>@endforeach</ul>
            </details>
        @endif

        @unless ($temProblema)
            <div class="alerta alerta--ok" role="status"><p>Tudo certo: {{ $r['criados'] + $r['atualizados'] }} de {{ $r['total'] }} animais processados sem nenhum ajuste.</p></div>
        @endunless

        <a href="{{ route('painel.animais') }}" class="botao botao--primario" style="margin-top:.5rem"><x-icone nome="cavalo" :tamanho="18" /> Ver o plantel</a>
    </section>
@endif

<div class="grade-2" style="align-items:start;gap:1.5rem">
    {{-- Passo 1: baixar a planilha-modelo com a tropa atual --}}
    <section class="cartao">
        <h2 style="margin-top:0">1. Baixar a planilha</h2>
        <p class="suave">
            A planilha vem com uma linha por animal já cadastrado
            @if ($total > 0)(<b>{{ $total }}</b> no momento)@else(o plantel ainda está vazio: você recebe só o modelo em branco com os títulos)@endif
            e todas as colunas da ficha do animal. Preencha novas linhas para cadastrar e edite as existentes para atualizar.
        </p>
        <a href="{{ route('painel.animais.exportar') }}" class="botao botao--primario botao--largo"><x-icone nome="banco" :tamanho="18" /> Baixar planilha da tropa (.xlsx)</a>
        <p class="suave" style="margin-top:.75rem;font-size:.85rem">Abre no Excel, no LibreOffice ou no Google Planilhas.</p>
    </section>

    {{-- Passo 2: enviar o arquivo preenchido --}}
    <section class="cartao">
        <h2 style="margin-top:0">2. Enviar preenchida</h2>
        @if ($podeEscrever)
            <form method="post" action="{{ route('painel.animais.importar.processar') }}" enctype="multipart/form-data" class="formulario" data-importar>
                @csrf
                <label for="arquivo-tropa" class="suave">Arquivo da planilha (.xlsx ou .csv)</label>
                <input type="file" name="arquivo" id="arquivo-tropa" accept=".xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                <button type="submit" class="botao botao--primario botao--largo" style="margin-top:1rem"><x-icone nome="seta" :tamanho="18" /> Importar tropa</button>
            </form>
            @if ($limite !== null)
                <p class="suave" style="margin-top:.75rem;font-size:.85rem">Sua faixa permite até <b>{{ $limite }}</b> animais ativos ({{ $ativos }} agora). Acima disso, o cadastro dos novos é barrado e aparece a oferta de upgrade.</p>
            @endif
        @else
            <p class="suave">Seu acesso a esta propriedade permite consultar, mas não importar animais. Peça ao proprietário para liberar.</p>
        @endif
    </section>
</div>

{{-- Como preencher --}}
<section class="cartao" style="margin-top:1.5rem">
    <h2 style="margin-top:0">Como preencher a planilha</h2>
    <ul class="lista-ajuda">
        <li><b>Não mexa na coluna "Código".</b> É ela que liga a linha ao animal: linha com código atualiza o animal; linha <b>sem código</b> (as que você acrescentar) cadastra um animal novo.</li>
        <li>Só o <b>Nome</b> é obrigatório. Os outros campos podem ficar em branco — e o sistema preenche sozinho o que der.</li>
        <li><b>Preenchimento automático quando a célula fica em branco:</b> Nome de registro recebe o próprio nome; Apelido recebe o primeiro nome; Espécie vira Equino; Situação vira Ativo.</li>
        <li><b>Sexo:</b> aceita Macho/Fêmea ou só <b>M</b> / <b>F</b>.</li>
        <li><b>Raça:</b> aceita o nome por extenso ou a sigla — ex.: <b>MM</b> = Mangalarga Marchador, QM = Quarto de Milha, PSI = Puro Sangue Inglês. O que não for sigla conhecida entra como foi digitado.</li>
        <li>Nas demais colunas de lista (Categoria, Pelagem, Forma de aquisição, Associação), escreva o nome como aparece no sistema — maiúsculas e acentos não importam. O que não for reconhecido fica em branco e entra como aviso, sem perder a linha.</li>
        <li><b>Datas</b> no formato dia/mês/ano (ex.: 15/03/2021). <b>Sim/Não</b> nas colunas Castrado, DNA coletado e Registro definitivo. Valores em R$ com vírgula (ex.: 12.000,00).</li>
        <li>A planilha é o retrato do plantel: numa linha já existente, uma célula apagada <b>limpa</b> aquele campo do animal (menos os de preenchimento automático acima).</li>
    </ul>

    <details style="margin-top:1rem">
        <summary style="cursor:pointer"><b>Ver todas as colunas</b></summary>
        <div class="tabela-rolagem" style="margin-top:.75rem"><table class="tabela">
            <thead><tr><th>Coluna</th><th>O que aceita</th></tr></thead>
            <tbody>
            @foreach ($colunas as $campo => [$rotulo, $tipo])
                <tr>
                    <td><b>{{ $rotulo }}</b></td>
                    <td class="suave">
                        @switch($tipo)
                            @case('codigo') Gerado pelo sistema — não alterar. @break
                            @case('data') Data (dia/mês/ano). @break
                            @case('bool') Sim ou Não. @break
                            @case('decimal') Número (ex.: 1.200,50). @break
                            @case('especie') {{ implode(', ', \App\Models\Animal::ESPECIES) }} (em branco: Equino). @break
                            @case('sexo') Macho ou Fêmea (também M ou F). @break
                            @case('raca') Nome da raça ou sigla (MM = Mangalarga Marchador). @break
                            @case('categoria') Garanhão, Matriz (Égua), Potro, Cavalo… @break
                            @case('pelagem') Nome da pelagem (fora da lista vira "Outra"). @break
                            @case('associacao') Sigla da associação (ABCCMM, ABQM, ABCCC…). @break
                            @case('situacao') {{ implode(', ', \App\Models\Animal::SITUACOES) }} (em branco: Ativo). @break
                            @case('forma') {{ implode(', ', \App\Models\Animal::FORMAS_AQUISICAO) }}. @break
                            @default Texto livre.
                        @endswitch
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </details>
</section>

<style>
    .lista-ajuda { margin:0; padding-left:1.1rem; display:flex; flex-direction:column; gap:.5rem; }
    .lista-ajuda li { line-height:1.45; }
    [data-importar] input[type=file] { display:block; width:100%; margin-top:.4rem; }
</style>
<script>
    // Evita envio duplo: desabilita o botão ao submeter.
    document.querySelector('[data-importar]')?.addEventListener('submit', function (e) {
        var b = this.querySelector('button[type=submit]');
        if (b) { b.disabled = true; b.textContent = 'Importando…'; }
    });
</script>
@endsection
