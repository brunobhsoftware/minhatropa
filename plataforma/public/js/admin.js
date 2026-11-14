// MinhaTropa — Administração (JS puro, sem dependências).
(function () {
  'use strict';

  // Menu lateral no celular
  document.querySelectorAll('[data-menu-alternar]').forEach(function (b) {
    b.addEventListener('click', function () {
      var aberto = document.body.classList.toggle('menu-aberto');
      b.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });
  });
  document.addEventListener('click', function (e) {
    if (document.body.classList.contains('menu-aberto') && !e.target.closest('.menu-lateral, [data-menu-alternar]')) {
      document.body.classList.remove('menu-aberto');
    }
  });

  // Confirmação antes de enviar
  document.querySelectorAll('form[data-confirmar]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirmar'))) e.preventDefault();
    });
  });

  // Mostrar/esconder um bloco
  document.querySelectorAll('[data-alternar]').forEach(function (b) {
    b.addEventListener('click', function () {
      var alvo = document.querySelector(b.getAttribute('data-alternar'));
      if (alvo) alvo.hidden = !alvo.hidden;
    });
  });

  // Linha de tabela clicável
  document.querySelectorAll('tr[data-href]').forEach(function (tr) {
    tr.addEventListener('click', function (e) {
      if (!e.target.closest('a, button, input, select')) window.location = tr.getAttribute('data-href');
    });
  });

  // Máscaras simples
  var mascaras = {
    documento: function (v) {
      v = v.replace(/\D/g, '').slice(0, 14);
      if (v.length <= 11) return v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
      return v.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
    },
    cpf: function (v) { return v.replace(/\D/g, '').slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'); },
    cnpj: function (v) { return v.replace(/\D/g, '').slice(0, 14).replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2'); },
    telefone: function (v) {
      v = v.replace(/\D/g, '').slice(0, 11);
      if (v.length > 10) return v.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');
      if (v.length > 6) return v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
      if (v.length > 2) return v.replace(/^(\d{2})(\d*)/, '($1) $2');
      return v;
    },
    cep: function (v) { v = v.replace(/\D/g, '').slice(0, 8); return v.length > 5 ? v.slice(0, 5) + '-' + v.slice(5) : v; }
  };
  document.querySelectorAll('[data-mascara]').forEach(function (i) {
    var fn = mascaras[i.getAttribute('data-mascara')];
    if (!fn) return;
    i.addEventListener('input', function () { i.value = fn(i.value); });
  });

  // Endereço pelo CEP (ViaCEP)
  document.querySelectorAll('[data-cep]').forEach(function (i) {
    i.addEventListener('blur', function () {
      var cep = i.value.replace(/\D/g, '');
      if (cep.length !== 8 || !window.fetch) return;
      fetch('https://viacep.com.br/ws/' + cep + '/json/').then(function (r) { return r.json(); }).then(function (d) {
        if (d.erro) return;
        var f = i.form;
        var preencher = function (nome, valor) { var c = f.querySelector('[name=' + nome + ']'); if (c && !c.value && valor) c.value = valor; };
        preencher('logradouro', d.logradouro); preencher('bairro', d.bairro);
        // UF e município: seleciona nas listas do IBGE (o ViaCEP devolve o código IBGE do município)
        var geo = (i.closest('.grade-2, .grade-3, section, form') || f).querySelector('[data-geo]');
        if (geo && geo.geoDefinir && !geo.querySelector('[data-geo-id]').value) geo.geoDefinir(d.uf, d.ibge);
      }).catch(function () {});
    });
  });
})();
