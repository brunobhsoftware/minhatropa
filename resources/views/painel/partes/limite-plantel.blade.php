{{-- Trava de limite: aviso (modal) quando o cadastro de mais um animal ultrapassa a faixa de plantel em vigor. --}}
@php
    $lFaixa = \App\Support\Licenca::faixaAtual($haras);
    $lLimite = \App\Support\Licenca::limite($haras);
    $lTotal = \App\Support\Licenca::animais($haras);
    $lProxima = \App\Support\Licenca::proximaFaixa($haras);
    $lSolicitada = \App\Support\Licenca::faixaSolicitada($haras);
    $lDono = ($papel ?? '') === 'proprietario';
@endphp
<dialog class="modal" id="modalLimite" open aria-labelledby="modalLimiteTitulo">
    <div class="modal__caixa">
        <h2 id="modalLimiteTitulo">Limite do plantel atingido</h2>
        <p>{{ $lFaixa?->gratuito() ? 'O '.$lFaixa->nome : 'A faixa "'.($lFaixa?->nome ?? 'atual').'"' }} permite <b>até {{ $lLimite }} animais ativos</b> e {{ $haras->nome }} já tem <b>{{ $lTotal }}</b>. Por isso o novo cadastro foi bloqueado.</p>
        @if ($lProxima)
            <div class="modal__oferta">
                <small>Próxima faixa</small>
                <b>{{ $lProxima->nome }} — {{ $lProxima->faixaTexto() }}</b>
                <span>{{ $lProxima->valorTexto() }}</span>
            </div>
            @if ($lSolicitada)
                <p class="suave">Você já pediu a mudança para "{{ $lSolicitada->nome }}". Nossa equipe entra em contato para liberar.</p>
            @elseif ($lDono)
                <form method="post" action="{{ route('painel.plano.solicitar') }}" class="modal__acoes">
                    @csrf <input type="hidden" name="faixa" value="{{ $lProxima->id }}">
                    <button class="botao botao--primario">Fazer upgrade para {{ $lProxima->nome }}</button>
                    <a href="{{ route('painel.plano') }}" class="botao botao--contorno">Ver todas as faixas</a>
                </form>
            @else
                <p class="suave">Peça ao proprietário para mudar a faixa de plantel.</p>
            @endif
        @else
            <p class="suave">Fale com o atendimento para ampliar o plantel: <a href="mailto:atendimento@minhatropa.com.br">atendimento@minhatropa.com.br</a>.</p>
        @endif
        <form method="dialog" class="modal__fechar"><button class="botao botao--contorno botao--pequeno" data-fechar-modal>Agora não</button></form>
    </div>
</dialog>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var d = document.getElementById('modalLimite'); if (!d) return;
  if (d.showModal) { d.close(); d.showModal(); }
  d.querySelector('[data-fechar-modal]').addEventListener('click', function (e) { e.preventDefault(); d.close ? d.close() : d.removeAttribute('open'); });
});
</script>
