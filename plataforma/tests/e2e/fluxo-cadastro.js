// Autocadastro do Haras, período gratuito (1 animal) e liberação da licença.
// Uso: depois do fluxo-admin.js (admin bruno@bhsoftware.com.br / OutraSenha456), com php artisan serve.
const { chromium } = require('playwright');
const B = process.env.BASE_URL || 'http://127.0.0.1:8000';
let ok = 0, falhas = 0;
const conf = (c, m) => { if (c) ok++; else { falhas++; console.log('FALHOU:', m); } };
(async () => {
  const b = await chromium.launch();
  const a = await b.newPage(); a.on('dialog', d => d.accept());
  await a.goto(B + '/admin/entrar'); await a.fill('[name=email]', 'bruno@bhsoftware.com.br'); await a.fill('[name=senha]', 'OutraSenha456'); await a.click('button:has-text("Entrar")');
  // sem V002 o cadastro avisa em vez de quebrar
  const v = await b.newPage(); await v.goto(B + '/cadastro'); conf((await v.content()).includes('sendo ativado'), 'cadastro sem V002 avisa');
  // aplica V002
  await a.goto(B + '/admin/banco'); await a.click('form:has(input[value^="V002"]) button'); await a.waitForLoadState();
  conf((await a.content()).includes('V002__autocadastro_haras_animais.sql aplicado'), 'V002 aplicado');
  // autocadastro
  const c = await b.newPage({ viewport: { width: 1366, height: 900 } });
  await c.goto(B + '/'); await c.click('.topo__acoes >> text=Cadastre seu Haras'); await c.waitForLoadState();
  await c.screenshot({ path: '/tmp/mt-cadastro.png', fullPage: true });
  await c.fill('[name=haras]', 'Haras Boa Esperança'); await c.fill('[name=cidade]', 'Lavras'); await c.selectOption('[name=uf]', 'MG');
  await c.selectOption('[name=quantidade_animais]', '21 a 50'); await c.fill('[name=nome]', 'Ana Proprietária'); await c.fill('[name=email]', 'ana@harasboa.com.br');
  await c.fill('[name=telefone]', '35999990000'); await c.fill('[name=senha]', 'senha1234'); await c.fill('[name=senha_confirmation]', 'senha1234');
  await c.click('button:has-text("Criar minha conta")'); conf((await c.content()).includes('Confirme que leu'), 'aceite obrigatório');
  await c.fill('[name=senha]', 'senha1234'); await c.fill('[name=senha_confirmation]', 'senha1234'); await c.check('[name=aceite]');
  await c.click('button:has-text("Criar minha conta")'); await c.waitForLoadState();
  conf(c.url() === B + '/painel', 'entrou no painel: ' + c.url()); conf((await c.content()).includes('Período gratuito'), 'faixa gratuito');
  await c.screenshot({ path: '/tmp/mt-painel.png', fullPage: true });
  // 1º animal
  await c.goto(B + '/painel/animais/novo'); await c.fill('[name=nome]', 'Estrela'); await c.selectOption('[name=categoria]', 'matriz'); await c.fill('[name=raca]', 'Mangalarga Marchador');
  await c.click('button:has-text("Cadastrar animal")'); await c.waitForLoadState(); conf((await c.content()).includes('Estrela cadastrado'), '1º animal');
  // 2º bloqueado
  await c.goto(B + '/painel/animais/novo'); conf(c.url().endsWith('/painel/licenca'), '2º animal bloqueado');
  await c.screenshot({ path: '/tmp/mt-licenca.png', fullPage: true });
  // dados do haras
  await c.goto(B + '/painel/haras'); await c.fill('[name=logradouro]', 'Estrada Velha, km 5'); await c.fill('[name=cep]', '37200000'); await c.click('button:has-text("Salvar")');
  conf((await c.content()).includes('Dados do Haras salvos'), 'configuração do haras');
  // admin libera licença
  await a.goto(B + '/admin/haras?busca=Boa'); await a.click('text=Haras Boa Esperança'); await a.waitForLoadState();
  conf((await a.content()).includes('Período gratuito'), 'admin vê período gratuito');
  await a.click('button:has-text("Liberar licença")'); await a.waitForLoadState(); conf((await a.content()).includes('Licença liberada'), 'licença liberada');
  await c.goto(B + '/painel/animais/novo'); conf(c.url().endsWith('/painel/animais/novo'), 'com licença, 2º liberado');
  await c.fill('[name=nome]', 'Trovão'); await c.click('button:has-text("Cadastrar animal")'); await c.waitForLoadState(); conf((await c.content()).includes('Trovão cadastrado'), '2º animal com licença');
  // sair / entrar
  await c.click('button:has-text("Sair")'); await c.waitForLoadState(); conf(c.url().endsWith('/entrar'), 'saiu');
  await c.fill('[name=email]', 'ana@harasboa.com.br'); await c.fill('[name=senha]', 'senha1234'); await c.click('button:has-text("Entrar")'); await c.waitForLoadState();
  conf(c.url() === B + '/painel', 'entrou de novo');
  const r = await c.goto(B + '/admin'); conf(r.status() === 404, 'admin 404 para Haras');
  // e-mail repetido
  const d = await b.newPage(); await d.goto(B + '/cadastro'); await d.fill('[name=haras]', 'X'); await d.fill('[name=cidade]', 'Y'); await d.selectOption('[name=uf]', 'SP');
  await d.selectOption('[name=quantidade_animais]', 'Até 20'); await d.fill('[name=nome]', 'Z'); await d.fill('[name=email]', 'ana@harasboa.com.br'); await d.fill('[name=telefone]', '11999990000');
  await d.fill('[name=senha]', 'senha1234'); await d.fill('[name=senha_confirmation]', 'senha1234'); await d.check('[name=aceite]'); await d.click('button:has-text("Criar minha conta")');
  conf((await d.content()).includes('Já existe uma conta'), 'email repetido');
  console.log('ok', ok, 'falhas', falhas); await b.close();
})();
