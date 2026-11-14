// MinhaTropa — seleção em cascata UF → Município (tabelas do IBGE). JS puro, usado no site, no painel e na Administração.
// O município fica desabilitado até a UF ser escolhida; a lista traz só os municípios daquela UF e aceita digitação para buscar.
// Trocar a UF limpa o município e recarrega a lista.
(function () {
  'use strict';
  var cache = {};
  var semAcento = function (t) { return String(t).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); };

  function carregar(url, uf) {
    if (!cache[uf]) {
      cache[uf] = fetch(url + '/' + encodeURIComponent(uf), { headers: { Accept: 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error('geo'); return r.json(); })
        .catch(function (e) { delete cache[uf]; throw e; });
    }
    return cache[uf];
  }

  function iniciar(caixa) {
    var uf = caixa.querySelector('[data-geo-uf]');
    var busca = caixa.querySelector('[data-geo-busca]');
    var oculto = caixa.querySelector('[data-geo-id]');
    var lista = caixa.querySelector('.geo__lista');
    var url = caixa.getAttribute('data-geo-url');
    var filtro = /Todos/.test(busca.getAttribute('placeholder') || '');
    var municipios = [];   // [[id, nome, ibge], ...] da UF atual
    var escolhido = null;  // [id, nome, ibge]
    var marcado = -1;

    function fechar() { lista.hidden = true; busca.setAttribute('aria-expanded', 'false'); marcado = -1; }
    function escolher(m) {
      escolhido = m; oculto.value = m ? m[0] : ''; busca.value = m ? m[1] : ''; fechar();
      oculto.dispatchEvent(new Event('change', { bubbles: true }));
    }
    function mostrar() {
      var termo = semAcento(busca.value.trim());
      var achados = municipios.filter(function (m) { return !termo || semAcento(m[1]).indexOf(termo) !== -1; });
      achados.sort(function (a, b) { // quem começa pelo termo vem primeiro
        var ia = semAcento(a[1]).indexOf(termo) === 0 ? 0 : 1, ib = semAcento(b[1]).indexOf(termo) === 0 ? 0 : 1;
        return ia - ib;
      });
      lista.innerHTML = '';
      achados.slice(0, 60).forEach(function (m, i) {
        var li = document.createElement('li');
        li.setAttribute('role', 'option'); li.setAttribute('data-id', m[0]); li.textContent = m[1];
        if (escolhido && escolhido[0] === m[0]) li.setAttribute('aria-selected', 'true');
        li.addEventListener('mousedown', function (e) { e.preventDefault(); escolher(m); });
        lista.appendChild(li);
      });
      if (!achados.length) {
        var vazio = document.createElement('li'); vazio.className = 'geo__vazio'; vazio.textContent = 'Nenhum município encontrado nesta UF';
        lista.appendChild(vazio);
      } else if (achados.length > 60) {
        var mais = document.createElement('li'); mais.className = 'geo__vazio'; mais.textContent = 'Digite para ver mais municípios…';
        lista.appendChild(mais);
      }
      lista.hidden = false; busca.setAttribute('aria-expanded', 'true'); marcado = -1;
    }
    function marcar(i) {
      var itens = lista.querySelectorAll('li[role=option]');
      if (!itens.length) return;
      marcado = (i + itens.length) % itens.length;
      itens.forEach(function (li, n) { li.classList.toggle('marcado', n === marcado); });
      itens[marcado].scrollIntoView({ block: 'nearest' });
    }

    // Carrega os municípios da UF. manter = id (ou código IBGE, com porIbge) a deixar selecionado.
    function trocarUf(manter, porIbge) {
      var sigla = uf.value;
      municipios = []; escolhido = null; oculto.value = ''; busca.value = ''; fechar();
      if (!sigla) {
        busca.disabled = true; busca.placeholder = filtro ? 'Todos os municípios' : 'Selecione a UF primeiro';
        return Promise.resolve();
      }
      busca.disabled = true; busca.placeholder = 'Carregando municípios…';
      return carregar(url, sigla).then(function (dados) {
        if (uf.value !== sigla) return;
        municipios = dados; busca.disabled = false;
        busca.placeholder = filtro ? 'Todos os municípios' : 'Digite para buscar o município';
        if (manter) {
          var m = dados.filter(function (d) { return String(d[porIbge ? 2 : 0]) === String(manter); })[0];
          if (m) { escolhido = m; oculto.value = m[0]; busca.value = m[1]; }
        }
      }).catch(function () {
        busca.placeholder = 'Não foi possível carregar. Escolha a UF de novo.';
      });
    }

    uf.addEventListener('change', function () { trocarUf(null).then(function () { if (!busca.disabled && uf.value && !filtro) busca.focus(); }); });
    busca.addEventListener('focus', function () { if (municipios.length) { busca.select(); mostrar(); } });
    busca.addEventListener('input', function () {
      if (escolhido && busca.value !== escolhido[1]) { escolhido = null; oculto.value = ''; }
      mostrar();
    });
    busca.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); if (lista.hidden) mostrar(); marcar(marcado + 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(marcado - 1); }
      else if (e.key === 'Enter') {
        if (lista.hidden) return;
        var itens = lista.querySelectorAll('li[role=option]');
        var alvo = itens[marcado >= 0 ? marcado : 0];
        if (alvo) {
          e.preventDefault();
          escolher(municipios.filter(function (m) { return String(m[0]) === alvo.getAttribute('data-id'); })[0]);
        }
      } else if (e.key === 'Escape') { fechar(); }
    });
    busca.addEventListener('blur', function () {
      // Texto digitado que não foi escolhido na lista não vale: só entra município da tabela.
      var texto = semAcento(busca.value.trim());
      if (!escolhido && texto) {
        var exato = municipios.filter(function (m) { return semAcento(m[1]) === texto; });
        if (exato.length === 1) { escolher(exato[0]); return; }
      }
      busca.value = escolhido ? escolhido[1] : '';
      fechar();
    });

    caixa.geoDefinir = function (sigla, ibge) { // usado pelo preenchimento por CEP
      if (!sigla || !uf.querySelector('option[value="' + sigla + '"]')) return;
      uf.value = sigla; trocarUf(ibge, true);
    };
    if (uf.value) trocarUf(caixa.getAttribute('data-geo-municipio'));
  }

  function tudo(raiz) { (raiz || document).querySelectorAll('[data-geo]').forEach(function (c) { if (!c.geoPronto) { c.geoPronto = true; iniciar(c); } }); }
  window.MTGeo = { iniciar: tudo };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { tudo(); }); else tudo();
})();
