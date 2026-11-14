// MinhaTropa — site público (JS puro).
(function () {
  'use strict';
  var botao = document.querySelector('[data-menu]');
  if (botao) {
    botao.addEventListener('click', function () {
      var aberto = document.body.classList.toggle('menu-aberto');
      botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });
    document.querySelectorAll('.topo__nav a').forEach(function (a) {
      a.addEventListener('click', function () { document.body.classList.remove('menu-aberto'); botao.setAttribute('aria-expanded', 'false'); });
    });
  }
  // Menus "Entrar" e "Cadastre-se" do topo: abre um por vez e fecha ao clicar fora ou com Esc
  var opcoes = document.querySelectorAll('.topo__opcoes');
  opcoes.forEach(function (d) { d.addEventListener('toggle', function () { if (d.open) opcoes.forEach(function (o) { if (o !== d) o.open = false; }); }); });
  document.addEventListener('click', function (e) { opcoes.forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') opcoes.forEach(function (d) { d.open = false; }); });
  // Máscara de CPF
  document.querySelectorAll('[data-mascara=cpf]').forEach(function (i) {
    var m = function () { i.value = i.value.replace(/\D/g, '').slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'); };
    i.addEventListener('input', m); m();
  });
  // Máscara de telefone no formulário de contato
  document.querySelectorAll('input[type=tel]').forEach(function (i) {
    i.addEventListener('input', function () {
      var v = i.value.replace(/\D/g, '').slice(0, 11);
      if (v.length > 10) v = v.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');
      else if (v.length > 6) v = v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
      else if (v.length > 2) v = v.replace(/^(\d{2})(\d*)/, '($1) $2');
      i.value = v;
    });
  });
})();
