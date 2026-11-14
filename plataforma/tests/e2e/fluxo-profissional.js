// Entrar/cadastrar como Haras / Fazenda ou Profissional, ambiente do profissional, cadastro completo da propriedade
// e página pública (logomarca, capa, cores). Uso: depois do fluxo-admin.js e do fluxo-cadastro.js.
const fs = require('fs');
const { chromium } = require('playwright');
const { B, conf, placar, cpf, local, pagina } = require('./apoio');
const CPF_RUI = cpf(444555666), CPF_CARLA = cpf(555666777);
const mascara = (d) => d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
// PNG 2×2 para os envios de imagem
const PNG = '/tmp/mt-teste.png';
fs.writeFileSync(PNG, Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAFElEQVR4nGP8z8Dwn4GBgYGJAQoAHxcCAr7oqj8AAAAASUVORK5CYII=', 'base64'));
(async () => {
  const b = await chromium.launch();
  const p = await pagina(b, { viewport: { width: 1366, height: 900 } });

  // ---------- Site: as duas opções em Entrar e em Cadastrar ----------
  await p.goto(B + '/'); await p.click('[data-opcoes=entrar] summary');
  let h = await p.textContent('[data-opcoes=entrar] .topo__opcoes-lista');
  conf(h.includes('Haras / Fazenda') && h.includes('Profissional'), 'Entrar oferece Haras / Fazenda ou Profissional');
  await p.screenshot({ path: '/tmp/mt-topo-entrar.png' });
  await p.click('[data-opcoes=cadastrar] summary');
  conf(!(await p.locator('[data-opcoes=entrar]').evaluate((d) => d.open)), 'abrir um menu fecha o outro');
  await p.click('[data-opcoes=cadastrar] >> text=Cadastrar como Profissional'); await p.waitForLoadState();
  conf(p.url().endsWith('/cadastro/profissional') && (await p.content()).includes('Cadastre-se como Profissional'), 'cadastro rápido de profissional pelo site');
  conf(!(await p.isChecked('[name="especialidades[]"][value=proprietario]')), 'cadastro avulso não força a função de proprietário');

  // ---------- Cadastro rápido de profissional → ambiente do profissional ----------
  await p.fill('[name=cpf]', CPF_RUI); await p.fill('[name=nome]', 'Rui Veterinário'); await p.fill('[name=email]', 'rui@exemplo.com.br'); await p.fill('[name=telefone]', '31977770000');
  await local(p, 'MG', 'Belo Horizonte'); await p.check('[name="especialidades[]"][value=veterinario]'); await p.fill('[name=crmv]', 'MG-12345');
  await p.fill('[name=senha]', 'senha1234'); await p.fill('[name=senha_confirmation]', 'senha1234');
  await p.screenshot({ path: '/tmp/mt-cadastro-profissional.png', fullPage: true });
  await p.click('button:has-text("Criar meu cadastro")'); await p.waitForLoadState();
  conf(p.url().endsWith('/profissional'), 'cai no ambiente do profissional: ' + p.url());
  h = await p.content();
  conf(h.includes('Rui Veterinário') && h.includes(mascara(CPF_RUI)) && h.includes('Veterinário') && h.includes('Belo Horizonte/MG'), 'ambiente mostra o cadastro do profissional');
  conf(h.includes('ainda não está vinculado a nenhum Haras / Fazenda'), 'sem propriedades ainda');
  conf((await p.goto(B + '/painel')).url().endsWith('/profissional'), 'profissional sem propriedade não entra no painel');
  // meus dados
  await p.goto(B + '/profissional/dados'); await p.check('[name="especialidades[]"][value=inseminador]'); await p.fill('[name=descricao]', 'Atendo a região central de Minas.');
  await p.click('button:has-text("Salvar")'); await p.waitForLoadState(); conf((await p.content()).includes('Seus dados foram salvos'), 'profissional edita os próprios dados');
  await p.goto(B + '/profissional'); conf((await p.content()).includes('Inseminador'), 'nova função aparece no cadastro');
  await p.screenshot({ path: '/tmp/mt-profissional.png', fullPage: true });

  // ---------- Do ambiente do profissional: cadastrar o próprio Haras ----------
  await p.click('.cabecalho >> text=Cadastrar Haras / Fazenda'); await p.waitForLoadState();
  h = await p.content(); conf(p.url().endsWith('/cadastro') && h.includes('Rui Veterinário') && h.includes(mascara(CPF_RUI)), 'cadastro da propriedade usa a conta e o CPF do profissional');
  await p.fill('[name=haras]', 'Haras Três Rios'); await local(p, 'MG', 'Curvelo'); await p.selectOption('[name=quantidade_animais]', 'Até 20'); await p.check('[name=aceite]');
  await p.click('button:has-text("Cadastrar meu Haras / Fazenda")'); await p.waitForLoadState();
  conf(p.url() === B + '/painel' && (await p.content()).includes('Haras Três Rios'), 'propriedade criada e painel aberto');
  conf((await p.content()).includes('Ambiente do profissional'), 'painel tem o atalho para o ambiente do profissional');
  // segundo cadastro com o mesmo CPF não é permitido; CNPJ sim
  await p.goto(B + '/cadastro'); h = await p.content();
  conf(await p.locator('[name=titularidade][value=cpf]').isDisabled() && h.includes('Cada CPF vale para uma única propriedade'), 'mesmo CPF não cadastra segunda propriedade');

  // ---------- Entrar: escolha do ambiente ----------
  await p.goto(B + '/painel'); await p.click('button:has-text("Sair")'); await p.waitForLoadState();
  await p.goto(B + '/entrar?ambiente=profissional');
  conf(await p.isChecked('[name=ambiente][value=profissional]'), 'entrar já vem com Profissional escolhido');
  await p.screenshot({ path: '/tmp/mt-entrar.png' });
  await p.fill('[name=email]', 'rui@exemplo.com.br'); await p.fill('[name=senha]', 'senha1234'); await p.click('button:has-text("Entrar")'); await p.waitForLoadState();
  conf(p.url().endsWith('/profissional'), 'entrar como profissional abre o ambiente do profissional');
  h = await p.content(); conf(h.includes('Haras Três Rios') && h.includes('Proprietário titular'), 'ambiente lista a propriedade em que é titular');
  await p.click('tr:has-text("Haras Três Rios") button:has-text("Abrir painel")'); await p.waitForLoadState();
  conf(p.url() === B + '/painel', 'do ambiente do profissional abre o painel da propriedade');
  await p.click('button:has-text("Sair")'); await p.waitForLoadState();
  await p.check('[name=ambiente][value=haras]', { force: true }); await p.fill('[name=email]', 'rui@exemplo.com.br'); await p.fill('[name=senha]', 'senha1234'); await p.click('button:has-text("Entrar")'); await p.waitForLoadState();
  conf(p.url() === B + '/painel', 'entrar como Haras / Fazenda abre o painel');

  // ---------- Cadastro completo da propriedade + página pública ----------
  await p.goto(B + '/painel/haras');
  h = (await p.locator('.aba-cadastro').allTextContents()).join('|');
  conf(['Identificação', 'Contato', 'Endereço', 'Correspondência', 'Criação', 'Página e marca'].every((a) => h.includes(a)), 'painel com todas as seções do cadastro: ' + h);
  const campos = await p.locator('form[data-abas] [name]').evaluateAll((els) => [...new Set(els.map((e) => e.name))].length);
  conf(campos >= 50, 'cadastro completo no painel (' + campos + ' campos)');
  await p.click('[data-aba=contato]'); await p.fill('[name=telefone_comercial]', '3832220000'); await p.fill('[name=whatsapp]', '31977770000');
  await p.click('[data-aba=endereco]'); await p.fill('[name=como_chegar]', 'Entrar no km 12 da BR-135.'); await p.fill('[name=latitude]', '-18.756'); await p.fill('[name=longitude]', '-44.431');
  await p.click('[data-aba=correspondencia]'); await p.fill('[name=corresp_logradouro]', 'Rua da Bahia'); await local(p, 'MG', 'Belo Horizonte', 'corresp_');
  await p.click('[data-aba=criacao]'); await p.check('[name="racas[]"][value="Mangalarga Marchador"]'); await p.check('[name="marchas[]"] >> nth=0'); await p.check('[name="finalidades[]"] >> nth=0');
  await p.click('[data-aba=pagina]');
  await p.check('[name=pagina_ativa]'); await p.fill('[name=subsite_slug]', 'tres-rios'); await p.fill('[name=pagina_slogan]', 'Marcha, tradição e manejo cuidadoso');
  await p.fill('[name=pagina_apresentacao]', 'Criamos Mangalarga Marchador em Curvelo desde 1998.'); await p.fill('[name=cor_primaria]', '#7a1f1f'); await p.fill('[name=cor_fundo]', '#fff7e6');
  await p.setInputFiles('[name=logo]', PNG); await p.setInputFiles('[name=capa]', PNG); await p.fill('[name=instagram]', '@harastresrios'); await p.fill('[name=marca_descricao]', 'Ferro TR dentro de um círculo');
  await p.screenshot({ path: '/tmp/mt-pagina-form.png', fullPage: true });
  await p.click('button:has-text("Salvar")'); await p.waitForLoadState();
  h = await p.content(); conf(h.includes('Dados do Haras salvos') && h.includes('/h/tres-rios'), 'cadastro completo salvo e página no ar');
  await p.click('[data-aba=criacao]'); conf(await p.isChecked('[name="racas[]"][value="Mangalarga Marchador"]'), 'raças gravadas pelo proprietário');
  await p.click('[data-aba=correspondencia]'); await p.waitForFunction(() => document.querySelector('[name=corresp_municipio_id]').value !== '');
  conf(await p.inputValue('[name=corresp_logradouro]') === 'Rua da Bahia', 'endereço de correspondência gravado');
  await p.click('[data-aba=pagina]'); conf(await p.locator('img[data-imagem=logo]').count() === 1 && await p.locator('img[data-imagem=capa]').count() === 1, 'logomarca e capa enviadas');
  const v = await pagina(b, { viewport: { width: 1280, height: 900 } });
  const r = await v.goto(B + '/h/tres-rios'); h = await v.content();
  conf(r.status() === 200 && h.includes('Haras Três Rios') && h.includes('Marcha, tradição e manejo cuidadoso') && h.includes('desde 1998'), 'página pública da propriedade');
  conf(h.includes('--primaria:#7A1F1F') && h.includes('--fundo:#FFF7E6') && h.includes('--texto:#101828'), 'cores escolhidas aplicadas (texto escuro sobre fundo claro)');
  conf(h.includes('Mangalarga Marchador') && h.includes('Curvelo/MG') && h.includes('@harastresrios') && h.includes('Ver no mapa') && h.includes('wa.me/5531977770000'), 'criação, local e contatos na página');
  const img = await v.locator('.capa__logo img').getAttribute('src'); const ri = await v.request.get(img);
  conf(ri.status() === 200 && (ri.headers()['content-type'] || '').includes('image/png'), 'logomarca servida do disco de mídia');
  await v.screenshot({ path: '/tmp/mt-pagina-publica.png', fullPage: true });
  const vm = await pagina(b, { viewport: { width: 390, height: 844 } }); await vm.goto(B + '/h/tres-rios');
  conf(await vm.evaluate(() => document.documentElement.scrollWidth <= 390), 'página pública sem rolagem lateral no celular'); await vm.screenshot({ path: '/tmp/mt-pagina-publica-mobile.png', fullPage: true });
  // envio inválido e endereço repetido
  await p.setInputFiles('[name=logo]', { name: 'x.svg', mimeType: 'image/svg+xml', buffer: Buffer.from('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>') });
  await p.fill('[name=subsite_slug]', 'haras-vista-alegre'); await p.click('button:has-text("Salvar")'); await p.waitForLoadState();
  h = await p.content(); conf(h.includes('JPG, PNG ou WebP') && h.includes('já está em uso'), 'SVG recusado e endereço de página repetido recusado');
  conf(await p.locator('#aba-pagina').isVisible(), 'erro abre a aba onde está o problema');
  // tirar do ar
  await p.goto(B + '/painel/haras'); await p.click('[data-aba=pagina]'); await p.uncheck('[name=pagina_ativa]'); await p.check('[name=remover_capa]'); await p.click('button:has-text("Salvar")'); await p.waitForLoadState();
  conf((await v.goto(B + '/h/tres-rios')).status() === 404, 'página fora do ar responde 404');
  await p.click('[data-aba=pagina]'); conf(await p.locator('img[data-imagem=capa]').count() === 0 && await p.locator('img[data-imagem=logo]').count() === 1, 'capa removida, logomarca mantida');

  // ---------- Profissional cadastrado por um Haras ativa o próprio acesso ----------
  await p.goto(B + '/painel/profissionais/novo?cpf=' + CPF_CARLA);
  await p.fill('[name=nome]', 'Carla Treinadora'); await p.fill('[name=email]', 'carla@exemplo.com.br'); await p.check('[name="funcoes[]"][value=treinador]');
  await p.click('button:has-text("Cadastrar e vincular")'); await p.waitForLoadState(); conf((await p.content()).includes('Carla Treinadora cadastrado(a)'), 'Haras cadastra a profissional pelo CPF');
  const k = await pagina(b); await k.goto(B + '/cadastro'); await k.check('[name=titularidade][value=cpf]'); await k.fill('#documento', CPF_CARLA); await k.waitForSelector('.consulta-cpf--ok');
  h = await k.textContent('#resultadoCpf'); conf(h.includes('Carla T.') && !h.includes('Carla Treinadora') && !h.includes('carla@exemplo.com.br'), 'consulta pública mostra nome e e-mail mascarados');
  await k.goto(B + '/cadastro/profissional'); await k.fill('[name=cpf]', CPF_CARLA); await k.fill('[name=email]', 'outra@exemplo.com.br'); await k.fill('[name=senha]', 'senha1234'); await k.fill('[name=senha_confirmation]', 'senha1234');
  await k.click('button:has-text("Criar meu cadastro")'); await k.waitForLoadState(); conf((await k.content()).includes('com outro e-mail (ca***@exemplo.com.br)'), 'ativar acesso exige o e-mail do cadastro');
  await k.fill('[name=email]', 'carla@exemplo.com.br'); await k.fill('[name=senha]', 'senha1234'); await k.fill('[name=senha_confirmation]', 'senha1234'); await k.click('button:has-text("Criar meu cadastro")'); await k.waitForLoadState();
  h = await k.content(); conf(k.url().endsWith('/profissional') && h.includes('Haras Três Rios') && h.includes('Treinador') && h.includes('Sem acesso ao painel'), 'profissional ativa o acesso e vê onde está vinculada');
  await k.goto(B + '/cadastro/profissional'); conf(k.url().endsWith('/profissional'), 'logado não refaz o cadastro de profissional');

  // ---------- Dono em CNPJ (sem cadastro de profissional) no ambiente do profissional ----------
  const z = await pagina(b); await z.goto(B + '/entrar?ambiente=profissional'); await z.fill('[name=email]', 'ze@serraazul.com.br'); await z.fill('[name=senha]', 'senha1234'); await z.click('button:has-text("Entrar")'); await z.waitForLoadState();
  h = await z.content(); conf(z.url().endsWith('/profissional') && await z.locator('[data-sem-profissional]').count() === 1 && h.includes('Haras Serra Azul'), 'conta sem cadastro de profissional é convidada a completar e vê as propriedades');

  // celular
  const m = await pagina(b, { viewport: { width: 390, height: 844 } }); await m.context().addCookies(await p.context().cookies());
  for (const u of ['/profissional', '/profissional/dados', '/painel/haras', '/cadastro']) { await m.goto(B + u); conf(await m.evaluate(() => document.documentElement.scrollWidth <= 390), 'sem rolagem lateral no celular: ' + u); }
  await m.goto(B + '/painel/haras'); await m.click('[data-aba=pagina]'); await m.screenshot({ path: '/tmp/mt-pagina-form-mobile.png', fullPage: true });
  const s = await pagina(b, { viewport: { width: 390, height: 844 } });
  await s.goto(B + '/'); await s.click('.topo__menu'); await s.click('[data-opcoes=entrar] summary'); conf(await s.evaluate(() => document.documentElement.scrollWidth <= 390), 'menu do site no celular sem rolagem lateral'); await s.screenshot({ path: '/tmp/mt-topo-mobile.png' });
  await s.goto(B + '/entrar'); conf(await s.evaluate(() => document.documentElement.scrollWidth <= 390), 'entrar no celular sem rolagem lateral');
  process.exitCode = placar() ? 1 : 0;
  await b.close();
})();
