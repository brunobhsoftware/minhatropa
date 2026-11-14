// Onboarding do Haras / Fazenda (PF/PJ, CPF na base de Profissionais, UF → Município), faixas de plantel com trava,
// módulos adicionais (feature flags) e acesso externo por papel (RBAC).
// Uso: depois do fluxo-admin.js (admin bruno@bhsoftware.com.br / OutraSenha456), com php artisan serve.
const { chromium } = require('playwright');
const { B, conf, placar, cpf, local, pagina } = require('./apoio');
const CPF_ANA = cpf(222333444), CPF_PEDRO = cpf(333444555), CPF_JOAO = cpf(111222333);
const mascara = (d) => d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
(async () => {
  const b = await chromium.launch();
  const a = await pagina(b);
  await a.goto(B + '/admin/entrar'); await a.fill('[name=email]', 'bruno@bhsoftware.com.br'); await a.fill('[name=senha]', 'OutraSenha456'); await a.click('button:has-text("Entrar")'); await a.waitForLoadState();

  // ---------- Onboarding: Pessoa Física ----------
  const c = await pagina(b, { viewport: { width: 1366, height: 900 } });
  await c.goto(B + '/'); await c.click('.topo__acoes >> text=Cadastre seu Haras / Fazenda'); await c.waitForLoadState();
  let h = await c.content();
  conf(!h.includes('1º animal') && !h.includes('2º animal'), 'sem a regra antiga do 1º animal grátis');
  conf(h.includes('Até 5 animais grátis'), 'plano gratuito de até 5 animais');
  conf(await c.locator('#blocoRestante').isHidden(), 'primeiro passo é a titularidade (o resto fica escondido)');
  await c.check('[name=tipo_propriedade][value=fazenda]'); await c.check('[name=titularidade][value=cpf]');
  await c.fill('#documento', CPF_ANA); await c.waitForSelector('.consulta-cpf--bloqueio');
  conf((await c.textContent('#resultadoCpf')).includes('conclua primeiro o seu cadastro de Profissional'), 'CPF sem cadastro de Profissional bloqueia o avanço');
  conf(await c.locator('#blocoRestante').isHidden(), 'formulário continua travado');
  await c.screenshot({ path: '/tmp/mt-cadastro-bloqueio.png', fullPage: true });
  await c.click('#resultadoCpf a'); await c.waitForLoadState();
  conf(c.url().includes('/cadastro/profissional') && (await c.inputValue('[name=cpf]')) === mascara(CPF_ANA), 'direciona ao cadastro de Profissional com o CPF');
  conf(await c.isChecked('[name="especialidades[]"][value=proprietario]'), 'função Proprietário de Haras/Fazenda já marcada');
  await c.fill('[name=nome]', 'Ana Proprietária'); await c.fill('[name=email]', 'ana@fazendaboa.com.br'); await c.fill('[name=telefone]', '35999990000');
  await c.check('[name="especialidades[]"][value=treinador]');
  await c.click('button:has-text("Concluir e continuar")'); await c.waitForLoadState();
  conf((await c.content()).includes('Selecione a UF'), 'UF e município obrigatórios no cadastro de Profissional');
  await local(c, 'MG', 'Lavras'); await c.click('button:has-text("Concluir e continuar")'); await c.waitForLoadState();
  conf(c.url().includes('/cadastro?cpf=') && (await c.content()).includes('Cadastro de Profissional concluído'), 'volta ao cadastro da propriedade');
  await c.waitForSelector('.consulta-cpf--ok');
  conf((await c.textContent('#resultadoCpf')).includes('Ana Proprietária'), 'dados do profissional puxados pelo CPF');
  conf(await c.inputValue('[name=email]') === 'ana@fazendaboa.com.br' && (await c.inputValue('[name=telefone]')).replace(/\D/g, '') === '35999990000', 'e-mail e telefone preenchidos sem redigitação');
  conf(await c.locator('[name=nome]').isHidden(), 'PF não redigita o nome');
  conf((await c.textContent('#rotuloNome')).includes('Fazenda') || await c.isChecked('[name=tipo_propriedade][value=haras]'), 'rótulo acompanha o tipo');
  await c.check('[name=tipo_propriedade][value=fazenda]');
  await c.fill('[name=haras]', 'Fazenda Boa Esperança');
  conf(await c.locator('[data-geo-busca]').isDisabled(), 'município desabilitado até a UF');
  await c.selectOption('[name=quantidade_animais]', '21 a 50'); await c.fill('[name=senha]', 'senha1234'); await c.fill('[name=senha_confirmation]', 'senha1234'); await c.check('[name=aceite]');
  await c.click('button:has-text("Criar minha conta")'); await c.waitForLoadState();
  conf((await c.content()).includes('Selecione a UF'), 'UF/município obrigatórios no cadastro da propriedade');
  await c.waitForSelector('.consulta-cpf--ok'); await local(c, 'MG', 'Lavras');
  await c.fill('[name=senha]', 'senha1234'); await c.fill('[name=senha_confirmation]', 'senha1234'); await c.check('[name=aceite]');
  await c.screenshot({ path: '/tmp/mt-cadastro.png', fullPage: true });
  await c.click('button:has-text("Criar minha conta")'); await c.waitForLoadState();
  conf(c.url() === B + '/painel', 'entrou no painel: ' + c.url());
  h = await c.content();
  conf(h.includes('Dados da Fazenda') && h.includes('painel da Fazenda'), 'interface chama a propriedade de Fazenda');
  conf(h.includes('0 de 5 animais ativos'), 'faixa gratuita: 0 de 5');
  await c.screenshot({ path: '/tmp/mt-painel.png', fullPage: true });
  await c.goto(B + '/painel/profissionais'); h = await c.content();
  conf(h.includes('Ana Proprietária') && h.includes('Criador / proprietário titular'), 'profissional vinculado como proprietário titular');
  await c.goto(B + '/painel/haras'); h = await c.content();
  conf(h.includes(mascara(CPF_ANA)) && h.includes('Pessoa Física (CPF)'), 'propriedade indexada pelo CPF');
  await c.waitForFunction(() => document.querySelector('[data-geo-busca]').value === 'Lavras');
  await c.fill('[name=logradouro]', 'Estrada Velha, km 5'); await c.fill('[name=cep]', '37200000'); await c.click('button:has-text("Salvar")'); await c.waitForLoadState();
  conf((await c.content()).includes('Dados da Fazenda salvos'), 'dados da propriedade salvos');

  // ---------- Faixas de plantel: 5 grátis, trava no 6º, upgrade ----------
  const animal = async (nome) => { await c.goto(B + '/painel/animais/novo'); if (!c.url().endsWith('/novo')) return false; await c.fill('[name=nome]', nome); await c.click('button:has-text("Cadastrar animal")'); await c.waitForLoadState(); return (await c.content()).includes(nome + ' cadastrado'); };
  let cinco = true; for (const n of ['Estrela', 'Trovão', 'Faísca', 'Pérola', 'Zorro']) cinco = (await animal(n)) && cinco;
  conf(cinco, '5 animais cadastrados no plano gratuito');
  conf((await c.content()).includes('5 de 5'), 'contador 5 de 5');
  await c.goto(B + '/painel/animais/novo');
  conf(c.url().endsWith('/painel/animais'), '6º animal bloqueado');
  h = await c.textContent('#modalLimite');
  conf(h.includes('Limite do plantel atingido') && h.includes('até 5 animais ativos'), 'modal informa o limite atingido');
  conf(h.includes('Faixa 1') && h.includes('De 6 a 10 animais') && h.includes('Sob consulta'), 'modal oferece a próxima faixa, sem inventar preço');
  await c.screenshot({ path: '/tmp/mt-limite.png' });
  // tentativa direta por POST também é barrada
  const direto = await c.evaluate(async (base) => { const t = document.querySelector('input[name=_token]').value; const r = await fetch(base + '/painel/animais', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: '_token=' + encodeURIComponent(t) + '&nome=Burlador' }); return r.url; }, B);
  await c.goto(B + '/painel/animais'); conf(!(await c.content()).includes('Burlador'), 'POST direto do 6º animal também é barrado');
  // animal morto libera vaga (só animais ativos contam)
  await c.click('text=Zorro'); await c.waitForLoadState(); await c.click('text=Editar'); await c.waitForLoadState();
  await c.click('[data-aba=aquisicao]').catch(() => {}); await c.selectOption('[name=situacao]', 'vendido', { force: true }).catch(async () => { await c.evaluate(() => { document.querySelector('[name=situacao]').value = 'vendido'; }); });
  await c.click('button:has-text("Salvar")'); await c.waitForLoadState();
  await c.goto(B + '/painel/animais'); conf((await c.content()).includes('4 de 5'), 'animal vendido não conta: 4 de 5');
  conf(await animal('Cometa'), 'vaga liberada permite novo cadastro');
  // upgrade
  await c.goto(B + '/painel/animais/novo'); await c.click('#modalLimite button:has-text("Fazer upgrade para Faixa 1")'); await c.waitForLoadState();
  conf(c.url().endsWith('/painel/plano') && (await c.content()).includes('Pedido de mudança para "Faixa 1"'), 'upgrade solicitado pelo modal');
  await c.screenshot({ path: '/tmp/mt-plano.png', fullPage: true });
  await a.goto(B + '/admin/planos'); conf((await a.content()).includes('Fazenda Boa Esperança') && (await a.content()).includes('Upgrade de faixa: Faixa 1'), 'pedido aparece na Administração');
  await a.goto(B + '/admin/haras?busca=Boa'); await a.click('text=Fazenda Boa Esperança'); await a.waitForLoadState();
  conf((await a.content()).includes('pediu a mudança para'), 'ficha mostra o pedido');
  await a.click('button:has-text("Definir faixa")'); await a.waitForLoadState(); conf((await a.content()).includes('Faixa de plantel definida: Faixa 1'), 'admin libera a Faixa 1');
  conf(await animal('Relâmpago'), '6º animal liberado na Faixa 1');
  await c.goto(B + '/painel'); conf((await c.content()).includes('6 de 10 animais ativos'), 'faixa 1: 6 de 10');

  // ---------- Módulos adicionais (feature flags) ----------
  const menu = c.locator('[data-modulos] a');
  conf(await menu.count() === 4, '4 módulos no menu');
  conf((await c.textContent('[data-modulos]')).split('Módulo adicional').length - 1 === 4, 'módulos não contratados com o selo "Módulo adicional"');
  await c.click('[data-modulo=saude]'); await c.waitForLoadState(); h = await c.content();
  conf(h.includes('data-modulo-bloqueado="saude"') && h.includes('O que este módulo traz') && h.includes('Calendário de vacinas'), 'tela explicativa com os benefícios');
  await c.screenshot({ path: '/tmp/mt-modulo.png', fullPage: true });
  await c.click('button:has-text("Solicitar ativação")'); await c.waitForLoadState(); conf((await c.content()).includes('Ativação pedida'), 'ativação solicitada');
  await a.reload(); conf((await a.content()).includes('Solicitado pelo proprietário'), 'admin vê a solicitação do módulo');
  await a.click('button[data-modulo=saude]'); await a.waitForLoadState(); await a.click('button[data-modulo=equipe]'); await a.waitForLoadState();
  conf((await a.content()).includes('Gestão de Equipe e Acesso Externo" ativado'), 'admin ativa módulos');
  await c.goto(B + '/painel/modulos/saude'); conf((await c.content()).includes('Módulo ativo'), 'módulo de saúde liberado');
  conf(await c.locator('[data-modulo=saude][data-liberado="1"]').count() === 1 && await c.locator('[data-modulo=nutricao][data-liberado="0"]').count() === 1, 'menu reflete o que está liberado');

  // ---------- Equipe: busca por CPF primeiro, cadastro rápido e acesso externo (RBAC) ----------
  await c.goto(B + '/painel/profissionais/novo');
  conf(await c.locator('form.busca-cpf [name=cpf]').count() === 1 && await c.locator('[name=nome]').count() === 0, 'primeiro passo é só o CPF');
  await c.fill('[name=cpf]', CPF_JOAO); await c.click('button:has-text("Buscar")'); await c.waitForLoadState();
  conf(await c.locator('[data-encontrado]').count() === 1 && (await c.content()).includes('João Proprietário'), 'CPF existente: mostra os dados da base global');
  await c.check('[name="funcoes[]"][value=veterinario]'); await c.click('button:has-text("Vincular à propriedade")'); await c.waitForLoadState();
  conf((await c.content()).includes('João Proprietário vinculado(a) à equipe'), 'profissional da base global vinculado');
  await c.goto(B + '/painel/profissionais/novo?cpf=' + CPF_PEDRO);
  conf(await c.locator('[data-nao-encontrado]').count() === 1, 'CPF novo: oferece o cadastro rápido');
  await c.fill('[name=nome]', 'Pedro Tratador'); await c.fill('[name=telefone]', '35988880000'); await c.check('[name="funcoes[]"][value=peao]'); await c.check('[name="funcoes[]"][value=tratador]');
  await c.click('button:has-text("Cadastrar e vincular")'); await c.waitForLoadState();
  conf((await c.content()).includes('Pedro Tratador cadastrado(a) na base de Profissionais e vinculado(a)'), 'cadastro rápido + vínculo');
  await a.goto(B + '/admin/profissionais?funcao=peao'); conf((await a.content()).includes('Pedro Tratador'), 'profissional entrou na base global (filtro por função)');
  // acesso externo
  const linha = c.locator('details:has-text("acesso de Pedro")'); await linha.locator('summary').click();
  await linha.locator('[name=papel]').selectOption('tratador'); await linha.locator('[name=email]').fill('pedro@exemplo.com.br'); await linha.locator('[name=senha]').fill('provisoria1');
  await linha.locator('button:has-text("Dar acesso")').click(); await c.waitForLoadState();
  conf((await c.content()).includes('agora entra com o e-mail pedro@exemplo.com.br como Tratador'), 'acesso externo concedido');
  await c.screenshot({ path: '/tmp/mt-equipe.png', fullPage: true });
  const t = await pagina(b); await t.goto(B + '/entrar'); await t.fill('[name=email]', 'pedro@exemplo.com.br'); await t.fill('[name=senha]', 'provisoria1'); await t.click('button:has-text("Entrar")'); await t.waitForLoadState();
  conf(t.url() === B + '/painel', 'profissional entra com login próprio');
  h = await t.content();
  conf(h.includes('Fazenda Boa Esperança') && !h.includes('Plano e módulos') && !h.includes('animais ativos</b>'), 'tratador não vê plano/assinatura');
  conf((await t.goto(B + '/painel/plano')).status() === 403, 'plano bloqueado para a equipe (403)');
  conf((await t.goto(B + '/painel/animais')).status() === 200, 'tratador consulta os animais');
  conf((await t.goto(B + '/painel/animais/novo')).status() === 200 && (await t.goto(B + '/painel/haras')).status() === 403, 'dados da propriedade bloqueados para tratador');
  const post = await t.evaluate(async (base) => { const r = await fetch(base + '/painel/animais', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': '' }, body: 'nome=X' }); return r.status; }, B);
  conf(post === 419 || post === 403, 'tratador não cadastra animal (' + post + ')');
  await t.goto(B + '/painel/animais'); const tk = await t.evaluate(async (base) => { const m = document.querySelector('form input[name=_token]'); const r = await fetch(base + '/painel/animais', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: '_token=' + encodeURIComponent(m.value) + '&nome=X' }); return r.status; }, B);
  conf(tk === 403, 'RBAC: escrita em animais negada ao tratador (' + tk + ')');
  conf((await t.goto(B + '/painel/modulos/nutricao')).status() === 200 && (await t.content()).includes('Peça ao proprietário'), 'módulo bloqueado para a equipe: pede ao proprietário');
  // retirar acesso
  await c.goto(B + '/painel/profissionais'); const l2 = c.locator('details:has-text("acesso de Pedro")'); await l2.locator('summary').click(); await l2.locator('button:has-text("Retirar acesso")').click(); await c.waitForLoadState();
  conf((await t.goto(B + '/painel')).status() === 403, 'acesso retirado vale na hora');

  // ---------- Onboarding: Pessoa Jurídica e documento único ----------
  const d = await pagina(b); await d.goto(B + '/cadastro');
  await d.check('[name=titularidade][value=cnpj]'); await d.fill('#documento', '11444777000161');
  conf(await d.inputValue('#documento') === '11.444.777/0001-61' && await d.locator('#blocoRestante').isVisible(), 'PJ: CNPJ com máscara e formulário liberado');
  await d.fill('[name=razao_social]', 'Haras Serra Azul Ltda'); await d.fill('[name=haras]', 'Haras Serra Azul'); await local(d, 'SP', 'Campinas');
  await d.selectOption('[name=quantidade_animais]', 'Até 20'); await d.fill('[name=nome]', 'Zé Responsável'); await d.fill('[name=email]', 'ze@serraazul.com.br'); await d.fill('[name=telefone]', '19999990000');
  await d.fill('[name=senha]', 'senha1234'); await d.fill('[name=senha_confirmation]', 'senha1234'); await d.check('[name=aceite]'); await d.click('button:has-text("Criar minha conta")'); await d.waitForLoadState();
  conf(d.url() === B + '/painel' && (await d.content()).includes('Dados do Haras'), 'PJ cadastrada (Haras)');
  await d.click('button:has-text("Sair")'); await d.waitForLoadState();
  await d.goto(B + '/cadastro'); await d.check('[name=titularidade][value=cnpj]'); await d.fill('#documento', '11444777000161');
  await d.fill('[name=razao_social]', 'Outra'); await d.fill('[name=haras]', 'Outro Haras'); await local(d, 'SP', 'Campinas');
  await d.selectOption('[name=quantidade_animais]', 'Até 20'); await d.fill('[name=nome]', 'Outro'); await d.fill('[name=email]', 'outro@exemplo.com.br'); await d.fill('[name=telefone]', '19999991111');
  await d.fill('[name=senha]', 'senha1234'); await d.fill('[name=senha_confirmation]', 'senha1234'); await d.check('[name=aceite]'); await d.click('button:has-text("Criar minha conta")'); await d.waitForLoadState();
  conf((await d.content()).includes('Já existe um Haras / Fazenda cadastrado com esse CNPJ'), 'CNPJ repetido recusado');
  await d.goto(B + '/cadastro'); await d.check('[name=titularidade][value=cpf]'); await d.fill('#documento', CPF_ANA); await d.waitForSelector('.consulta-cpf--erro');
  conf((await d.textContent('#resultadoCpf')).includes('Já existe um Haras / Fazenda cadastrado com esse CPF'), 'CPF repetido recusado');
  // consulta pública do CPF não expõe dados pessoais
  await d.fill('#documento', ''); await d.fill('#documento', CPF_PEDRO); await d.waitForSelector('.consulta-cpf--ok'); h = await d.textContent('#resultadoCpf');
  conf(h.includes('Pedro T.') && !h.includes('Pedro Tratador') && !h.includes('pedro@exemplo.com.br'), 'consulta pública mostra o nome mascarado');

  // sair / entrar / admin 404 / celular
  await c.click('button:has-text("Sair")'); await c.waitForLoadState(); conf(c.url().endsWith('/entrar'), 'saiu');
  await c.fill('[name=email]', 'ana@fazendaboa.com.br'); await c.fill('[name=senha]', 'senha1234'); await c.click('button:has-text("Entrar")'); await c.waitForLoadState();
  conf(c.url() === B + '/painel', 'entrou de novo');
  conf((await c.goto(B + '/admin')).status() === 404, 'admin 404 para o cliente');
  const m = await pagina(b, { viewport: { width: 390, height: 844 } }); await m.context().addCookies(await c.context().cookies());
  for (const u of ['/painel', '/painel/plano', '/painel/profissionais', '/painel/modulos/manejo', '/painel/haras']) {
    await m.goto(B + u); conf(await m.evaluate(() => document.documentElement.scrollWidth <= 390), 'sem rolagem lateral no celular: ' + u);
  }
  await m.screenshot({ path: '/tmp/mt-painel-mobile.png', fullPage: true });
  const s = await pagina(b, { viewport: { width: 390, height: 844 } });
  for (const u of ['/cadastro', '/cadastro/profissional']) { await s.goto(B + u); if (u === '/cadastro') await s.check('[name=titularidade][value=cnpj]'); conf(await s.evaluate(() => document.documentElement.scrollWidth <= 390), 'sem rolagem lateral no celular: ' + u); }
  await s.screenshot({ path: '/tmp/mt-cadastro-mobile.png', fullPage: true });
  process.exitCode = placar() ? 1 : 0;
  await b.close();
})();
