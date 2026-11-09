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
