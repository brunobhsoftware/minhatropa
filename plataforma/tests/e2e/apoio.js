// Apoio dos testes de ponta a ponta (Playwright): CPF válido, seletor UF → Município e conferências.
const B = process.env.BASE_URL || 'http://127.0.0.1:8000';
let ok = 0, falhas = 0;
const conf = (c, m) => { if (c) ok++; else { falhas++; console.log('FALHOU:', m); } };
const placar = () => { console.log('ok', ok, 'falhas', falhas); return falhas; };

/** Gera um CPF válido a partir de 9 dígitos. */
function cpf(base9) {
  const d = String(base9).padStart(9, '0').split('').map(Number);
  for (let t = 9; t < 11; t++) { let s = 0; for (let i = 0; i < t; i++) s += d[i] * (t + 1 - i); d.push(((10 * s) % 11) % 10); }
  return d.join('');
}

/** Escolhe UF e município no seletor em cascata (o município só habilita depois da UF). */
async function local(pagina, uf, municipio, prefixo = '') {
  const caixa = pagina.locator(`[data-geo]:has(select[name="${prefixo}uf"])`);
  await caixa.locator('[data-geo-uf]').selectOption(uf);
  const busca = caixa.locator('[data-geo-busca]');
  await busca.waitFor({ state: 'visible' });
  await pagina.waitForFunction((el) => !el.disabled, await busca.elementHandle());
  await busca.fill(municipio);
  await caixa.locator('.geo__lista li[role=option]', { hasText: new RegExp('^' + municipio + '$') }).first().click();
}

/** Aplica pela Administração todos os scripts do banco ainda pendentes, na ordem. */
async function aplicarBanco(pagina) {
  const aplicados = [];
  for (const v of ['V002', 'V003', 'V004', 'V005', 'V006', 'V007']) {
    await pagina.goto(B + '/admin/banco');
    await pagina.click(`form:has(input[value^="${v}"]) button`);
    await pagina.waitForLoadState();
    if ((await pagina.content()).includes(v + '__')) aplicados.push(v);
  }
  return aplicados;
}

/** Página nova que só fala com o servidor de teste (fontes e ViaCEP ficam de fora). */
async function pagina(navegador, opcoes = {}) {
  const contexto = await navegador.newContext(opcoes);
  await contexto.route((url) => !url.href.startsWith(B), (rota) => rota.abort());
  const p = await contexto.newPage();
  p.on('dialog', (d) => d.accept());
  p.on('pageerror', (e) => { falhas++; console.log('ERRO DE JS:', e.message); });
  return p;
}

module.exports = { B, conf, placar, cpf, local, aplicarBanco, pagina };
