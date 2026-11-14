{{-- Campos da página pública e da identidade da propriedade (logomarca, capa, cores, apresentação, redes). Usado no painel e na Administração. --}}
@php
    $pv = fn ($c, $padrao = null) => old($c, $empresa->{$c} ?? $padrao);
    $corP = $pv('cor_primaria') ?: \App\Support\PaginaPropriedade::COR_PRIMARIA;
    $corF = $pv('cor_fundo') ?: \App\Support\PaginaPropriedade::COR_FUNDO;
    $ativa = (bool) old('pagina_ativa', $empresa->pagina_ativa ?? false);
    $contato = (bool) old('pagina_mostrar_contato', $empresa->exists ? $empresa->pagina_mostrar_contato : true);
    $endereco = (bool) old('pagina_mostrar_endereco', $empresa->exists ? $empresa->pagina_mostrar_endereco : true);
@endphp
<section class="cartao">
    <div class="cartao__titulo"><h2>Página {{ $empresa->exists ? $empresa->doRotulo() : 'do Haras / Fazenda' }}</h2>
        @if ($empresa->exists && $empresa->paginaUrl())<a href="{{ $empresa->paginaUrl() }}" target="_blank" rel="noopener">Ver a página ↗</a>@endif</div>
    <p class="suave" style="margin-bottom:1rem">Uma página simples e pública da propriedade, com a logomarca, as cores, a apresentação e os contatos. Tudo é opcional.</p>
    <label class="opcao"><input type="checkbox" name="pagina_ativa" value="1" @checked($ativa)> <b>Deixar a página no ar</b></label>
    <div class="grade-2" style="margin-top:14px">
        <label class="campo"><span>Endereço da página</span>
            <div class="campo-prefixo"><span>{{ preg_replace('#^https?://#', '', url('/h')) }}/</span><input name="subsite_slug" value="{{ $pv('subsite_slug') }}" maxlength="60" placeholder="meu-haras" autocomplete="off"></div>
            <small>Letras minúsculas, números e hifens. Em branco, o sistema cria a partir do nome.</small></label>
        <label class="campo"><span>Frase de apresentação (slogan)</span><input name="pagina_slogan" value="{{ $pv('pagina_slogan') }}" maxlength="150" placeholder="Ex.: Tradição em Mangalarga Marchador desde 1985"></label>
    </div>
    <label class="campo" style="margin-top:14px"><span>Sobre a propriedade</span><textarea name="pagina_apresentacao" rows="5" maxlength="5000" placeholder="Conte a história, a estrutura e o trabalho da propriedade.">{{ $pv('pagina_apresentacao') }}</textarea></label>
    <div class="grade-2" style="margin-top:14px">
        <label class="opcao"><input type="checkbox" name="pagina_mostrar_contato" value="1" @checked($contato)> Mostrar telefone, WhatsApp, e-mail e redes na página</label>
        <label class="opcao"><input type="checkbox" name="pagina_mostrar_endereco" value="1" @checked($endereco)> Mostrar o endereço e como chegar na página</label>
    </div>
</section>

<section class="cartao">
    <h2>Logomarca, capa e cores</h2>
    <div class="grade-2">
        @foreach (['logo' => ['Logomarca', 'Aparece no topo da página. De preferência quadrada, com fundo transparente ou branco.'], 'capa' => ['Imagem de capa', 'Foto larga da propriedade ou dos animais, no fundo do topo da página.']] as $tipo => [$rotulo, $ajuda])
        <div class="campo imagem-campo"><span>{{ $rotulo }}</span>
            @if ($empresa->exists && $empresa->imagemUrl($tipo))
                <div class="imagem-campo__atual"><img src="{{ $empresa->imagemUrl($tipo) }}" alt="{{ $rotulo }} atual" data-imagem="{{ $tipo }}">
                    <label class="opcao"><input type="checkbox" name="remover_{{ $tipo }}" value="1"> Remover</label></div>
            @endif
            <input type="file" name="{{ $tipo }}" accept="image/png,image/jpeg,image/webp">
            <small>{{ $ajuda }} JPG, PNG ou WebP, até 2 MB.</small>
        </div>
        @endforeach
    </div>
    <div class="cores-campo" data-cores>
        <label class="campo"><span>Cor principal</span><span class="cor-campo"><input type="color" name="cor_primaria" value="{{ $corP }}" aria-label="Cor principal"><output>{{ strtoupper($corP) }}</output></span><small>Topo, botões e destaques.</small></label>
        <label class="campo"><span>Cor de fundo</span><span class="cor-campo"><input type="color" name="cor_fundo" value="{{ $corF }}" aria-label="Cor de fundo"><output>{{ strtoupper($corF) }}</output></span><small>Fundo da página. O texto se ajusta para ficar legível.</small></label>
        <div class="cores-previa" aria-hidden="true" style="--p:{{ $corP }};--f:{{ $corF }}">
            <div class="cores-previa__topo"><b>{{ $empresa->nome ?: 'Nome da propriedade' }}</b><span>Botão</span></div>
            <div class="cores-previa__corpo">Texto da página sobre a cor de fundo.</div>
        </div>
    </div>
</section>

<section class="cartao">
    <h2>Marca do criador</h2>
    <p class="suave" style="margin-bottom:1rem">Ferro ou marca que identifica os animais do criador.</p>
    <div class="grade-2">
        <label class="campo"><span>Descrição da marca</span><textarea name="marca_descricao" rows="3" maxlength="500" placeholder="Ex.: Ferro em forma de estrela com as iniciais BE">{{ $pv('marca_descricao') }}</textarea></label>
        <div class="campo imagem-campo"><span>Imagem da marca</span>
            @if ($empresa->exists && $empresa->imagemUrl('marca'))
                <div class="imagem-campo__atual"><img src="{{ $empresa->imagemUrl('marca') }}" alt="Marca atual" data-imagem="marca"><label class="opcao"><input type="checkbox" name="remover_marca" value="1"> Remover</label></div>
            @endif
            <input type="file" name="marca" accept="image/png,image/jpeg,image/webp"><small>JPG, PNG ou WebP, até 2 MB.</small>
        </div>
    </div>
</section>

<section class="cartao">
    <h2>Site e redes sociais</h2>
    <div class="grade-3">
        <label class="campo"><span>Site próprio (URL)</span><input type="url" name="site_url" value="{{ $pv('site_url') }}" maxlength="255" placeholder="https://www.meuharas.com.br"></label>
        <label class="campo"><span>Instagram</span><input name="instagram" value="{{ $pv('instagram') ? '@'.ltrim($pv('instagram'), '@') : '' }}" maxlength="100" placeholder="@meuharas"></label>
        <label class="campo"><span>Facebook (URL)</span><input type="url" name="facebook" value="{{ $pv('facebook') }}" maxlength="255" placeholder="https://www.facebook.com/meuharas"></label>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var caixa = document.querySelector('[data-cores]'); if (!caixa) return;
  var previa = caixa.querySelector('.cores-previa');
  // Mesma regra do servidor: texto escuro sobre cor clara, texto branco sobre cor escura.
  var sobre = function (hex) { var c = [1, 3, 5].map(function (i) { var v = parseInt(hex.substr(i, 2), 16) / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }); return (0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]) > 0.4 ? '#101828' : '#FFFFFF'; };
  var atualizar = function () {
    var p = caixa.querySelector('[name=cor_primaria]').value, f = caixa.querySelector('[name=cor_fundo]').value;
    previa.style.setProperty('--p', p); previa.style.setProperty('--f', f); previa.style.setProperty('--sp', sobre(p)); previa.style.setProperty('--sf', sobre(f));
    caixa.querySelectorAll('.cor-campo').forEach(function (c) { c.querySelector('output').textContent = c.querySelector('input').value.toUpperCase(); });
  };
  caixa.querySelectorAll('input[type=color]').forEach(function (i) { i.addEventListener('input', atualizar); });
  atualizar();
});
</script>
