@extends('layouts.painel')
@section('titulo', 'Criador e equipe')
@section('conteudo')
@php $podeEditar = \App\Support\Permissao::pode($papel, 'equipe', true); @endphp
<div class="cabecalho">
    <div><h1>Criador e equipe</h1><p>Criador / proprietário e profissionais vinculados a {{ $haras->nome }}, todos identificados pelo CPF na base de Profissionais.</p></div>
    @if ($podeEditar)<a href="{{ route('painel.profissionais.novo') }}" class="botao botao--primario"><x-icone nome="busca" :tamanho="18" /> Buscar por CPF</a>@endif
</div>

@if ($proprietario && ! $moduloEquipe)
<div class="faixa-gratis">
    <div><b>Dê login próprio para a sua equipe</b><p>Com o módulo "Gestão de Equipe e Acesso Externo", gerentes, peões, treinadores e veterinários entram com o próprio e-mail e só veem o que o papel deles permite.</p></div>
    <a href="{{ route('painel.modulos.show', 'equipe') }}" class="botao botao--escuro"><x-icone nome="cadeado" :tamanho="16" /> Conhecer o módulo</a>
</div>
@endif

<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Nome</th><th>CPF</th><th>Funções nesta propriedade</th><th>Contato</th><th>Acesso ao sistema</th><th>Situação</th></tr></thead>
        <tbody>
        @forelse ($vinculos as $v)
            @php $funcoes = array_filter(explode(',', (string) $v->funcoes)); @endphp
            <tr data-vinculo="{{ $v->vinculo_id }}">
                <td><b>{{ $v->nome }}</b>@if ($v->titular)<br><span class="selo titular-selo">Criador / proprietário titular</span>@endif</td>
                <td>{{ \App\Support\Documento::formatar($v->cpf) }}</td>
                <td>{{ $v->funcao ?: '—' }}</td>
                <td>{{ $v->telefone ?: ($v->whatsapp ?: '—') }}</td>
                <td>
                    @if ($v->acesso_sistema)
                        <span class="selo selo--ativo">{{ \App\Models\EmpresaUsuario::PAPEIS[$v->papel_acesso] ?? 'Com acesso' }}</span><br><small class="suave">{{ $v->login_email }}</small>
                    @else
                        <span class="suave">Sem acesso</span>
                    @endif
                </td>
                <td><span class="selo selo--{{ $v->vinculo_situacao }}">{{ $v->vinculo_situacao === 'ativo' ? 'Ativo' : 'Desativado' }}</span></td>
            </tr>
            @if ($podeEditar)
            <tr><td colspan="6" style="padding-top:0;border-top:0">
                <details class="recolhivel" style="margin-top:0;border-top:0;padding-top:0" @if (old('vinculo') == $v->vinculo_id) open @endif>
                    <summary>Funções{{ $proprietario && ! $v->titular ? ' e acesso' : '' }} de {{ strtok($v->nome, ' ') }}</summary>
                    <form method="post" action="{{ route('painel.profissionais.editar', $v->vinculo_id) }}" class="formulario">@csrf @method('put')
                        <div class="grade-opcoes">
                            @foreach (\App\Models\Profissional::ESPECIALIDADES as $valor => $texto)
                            <label class="opcao-check"><input type="checkbox" name="funcoes[]" value="{{ $valor }}" @checked(in_array($valor, $funcoes))> {{ $texto }}</label>
                            @endforeach
                        </div>
                        <div class="linha-form">
                            <select name="vinculo_situacao" aria-label="Situação do vínculo"><option value="ativo" @selected($v->vinculo_situacao === 'ativo')>Vínculo ativo</option>@unless ($v->titular)<option value="desativado" @selected($v->vinculo_situacao === 'desativado')>Vínculo desativado</option>@endunless</select>
                            <button class="botao botao--contorno botao--pequeno">Salvar funções</button>
                        </div>
                    </form>
                    @if ($proprietario && ! $v->titular && $v->usuario_id !== auth()->id())
                        <h3 style="margin-top:14px">Acesso externo (login próprio)</h3>
                        @if (! $moduloEquipe)
                            <p class="suave">Faz parte do módulo "Gestão de Equipe e Acesso Externo". <a href="{{ route('painel.modulos.show', 'equipe') }}">Conhecer e solicitar</a></p>
                        @elseif ($v->acesso_sistema)
                            <form method="post" action="{{ route('painel.profissionais.acesso', $v->vinculo_id) }}" data-confirmar="Retirar o acesso de {{ $v->nome }}?">@csrf @method('put')
                                <input type="hidden" name="conceder" value="0"><button class="botao botao--contorno botao--pequeno">Retirar acesso</button></form>
                        @elseif ($v->vinculo_situacao === 'ativo')
                            <form method="post" action="{{ route('painel.profissionais.acesso', $v->vinculo_id) }}" class="acesso-form">@csrf @method('put')
                                <input type="hidden" name="conceder" value="1"><input type="hidden" name="vinculo" value="{{ $v->vinculo_id }}">
                                <label class="campo"><span>Permissão (papel)</span><select name="papel">@foreach (\App\Support\Permissao::PAPEIS_EQUIPE as $valor => $texto)<option value="{{ $valor }}">{{ $texto }}</option>@endforeach</select></label>
                                @if (! $v->email)<label class="campo"><span>E-mail de acesso *</span><input type="email" name="email" value="{{ old('vinculo') == $v->vinculo_id ? old('email') : '' }}" maxlength="190" required></label>
                                @else<p class="suave" style="margin:0">Vai entrar com o e-mail <b>{{ $v->email }}</b>.</p>@endif
                                <label class="campo"><span>Senha provisória</span><input type="text" name="senha" minlength="8" autocomplete="off"><small>Mínimo 8 caracteres, com letras e números. Deixe em branco se a pessoa já tem login no MinhaTropa.</small></label>
                                <div><button class="botao botao--primario botao--pequeno">Dar acesso</button></div>
                                <small class="suave">Ninguém da equipe vê o plano, os módulos contratados nem os dados da sua assinatura.</small>
                            </form>
                        @endif
                    @endif
                </details>
            </td></tr>
            @endif
        @empty
            <tr><td colspan="6"><div class="vazio"><p>Ninguém vinculado ainda. Busque pelo CPF para vincular o criador, veterinários, gerentes, tratadores e toda a equipe.</p>@if ($podeEditar)<a href="{{ route('painel.profissionais.novo') }}" class="botao botao--primario botao--pequeno">Buscar por CPF</a>@endif</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
