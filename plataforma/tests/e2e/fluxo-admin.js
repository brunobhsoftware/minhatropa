// Administração: instalação, atualizações do banco, Haras / Fazenda por CPF ou CNPJ único, UF → Município e demais telas.
// Uso: banco limpo + php artisan serve; DB_PASSWORD do .env local em SENHA_BANCO.
const { chromium } = require('playwright');
const { B, conf, placar, cpf, local, aplicarBanco, pagina } = require('./apoio');
const SENHA_BANCO = process.env.SENHA_BANCO || 'Senha$%local123';
const CPF_JOAO = cpf(111222333);
(async () => {
  const b = await chromium.launch();
  const p = await pagina(b, { viewport: { width: 1366, height: 860 } });
  // instalação
  await p.goto(B + '/admin/entrar'); conf(p.url().endsWith('/admin/instalar'), 'entrar redireciona p/ instalar');
  await p.fill('[name=senha_banco]', 'errada'); await p.fill('[name=nome]', 'Bruno Teste'); await p.fill('[name=email]', 'bruno@bhsoftware.com.br');
  await p.fill('[name=senha]', 'SenhaForte123'); await p.fill('[name=senha_confirmation]', 'SenhaForte123');
  await p.click('button:has-text("Instalar")'); conf((await p.content()).includes('não confere'), 'senha do banco errada recusada');
  await p.fill('[name=senha_banco]', SENHA_BANCO); await p.fill('[name=senha]', 'SenhaForte123'); await p.fill('[name=senha_confirmation]', 'SenhaForte123');
  await p.click('button:has-text("Instalar")'); await p.waitForLoadState();
  conf(p.url() === B + '/admin', 'instalou e entrou: ' + p.url());
  conf((await p.content()).includes('Instalação concluída'), 'mensagem de instalação');
  await p.goto(B + '/admin/instalar'); conf(!p.url().endsWith('/instalar'), 'instalar some depois');

  // sem os scripts novos, as telas avisam em vez de quebrar
  await p.goto(B + '/admin/haras'); conf(p.url().endsWith('/admin/banco') && (await p.content()).includes('Aplique as atualizações'), 'Haras sem banco atual manda aplicar');
  const v = await pagina(b); await v.goto(B + '/cadastro'); conf((await v.content()).includes('sendo ativado'), 'cadastro sem banco atual avisa');
  const aplicados = await aplicarBanco(p);
  conf(aplicados.length === 6, 'V002 a V007 aplicados pela tela: ' + aplicados.join(','));
  await p.goto(B + '/admin/banco'); let c = await p.content(); conf(!c.includes('Pendente'), 'nenhum script pendente');
  await p.click('form:has(input[value^="V006"]) button'); await p.waitForLoadState();
  conf((await p.content()).includes('aplicado ('), 'reaplicar V006 é idempotente');

  // novo Haras, Pessoa Jurídica: CNPJ obrigatório e único, UF → Município
  await p.goto(B + '/admin/haras/novo');
  await p.fill('[name=nome]', 'Haras Vista Alegre'); await p.click('button:has-text("Cadastrar Haras / Fazenda")'); await p.waitForLoadState();
  c = await p.content(); conf(c.includes('Escolha a titularidade') && c.includes('obrigatório'), 'titularidade e documento obrigatórios');
  await p.fill('[name=nome]', 'Haras Vista Alegre'); await p.check('[name=tipo_documento][value=cnpj]');
  await p.fill('[name=documento]', '11222333000181'); await p.fill('[name=razao_social]', 'Vista Alegre Agropecuária Ltda'); await p.fill('[name=area_hectares]', '1.250,5');
  conf(await p.inputValue('[name=documento]') === '11.222.333/0001-81', 'máscara de CNPJ');
  await p.click('[data-aba=contato]'); await p.fill('[name=responsavel_nome]', 'João da Silva'); await p.fill('[name=whatsapp]', '31999998888');
  await p.click('[data-aba=endereco]');
  conf(await p.locator('[data-geo]:has(select[name=uf]) [data-geo-busca]').isDisabled(), 'município desabilitado até escolher a UF');
  await local(p, 'MG', 'Itaúna');
  await p.click('button:has-text("Cadastrar Haras / Fazenda")'); await p.waitForLoadState();
  c = await p.content(); conf(c.includes('cadastrado'), 'haras cadastrado'); conf(c.includes('11.222.333/0001-81'), 'cnpj formatado'); conf(c.includes('1.250,50 ha'), 'area');
  conf(c.includes('Itaúna/MG'), 'município gravado pela tabela do IBGE'); conf(c.includes('Pessoa Jurídica'), 'titularidade na ficha');
  // documento inválido e documento repetido
  await p.goto(B + '/admin/haras/novo'); await p.fill('[name=nome]', 'Haras X'); await p.check('[name=tipo_documento][value=cnpj]'); await p.fill('[name=documento]', '11.111.111/1111-11'); await p.fill('[name=razao_social]', 'X');
  await p.click('button:has-text("Cadastrar Haras / Fazenda")'); await p.waitForLoadState(); conf((await p.content()).includes('inválido'), 'cnpj inválido recusado');
  await p.fill('[name=documento]', '11222333000181'); await p.click('[data-aba=endereco]'); await local(p, 'SP', 'Campinas');
  await p.click('button:has-text("Cadastrar Haras / Fazenda")'); await p.waitForLoadState(); conf((await p.content()).includes('Já existe um Haras / Fazenda cadastrado com esse CPF/CNPJ'), 'CNPJ repetido recusado (índice único)');

  // Pessoa Física: só com CPF na base de Profissionais; a aba Criador começa pela busca por CPF
  await p.goto(B + '/admin/haras/novo'); await p.fill('[name=nome]', 'Fazenda Santa Rita'); await p.selectOption('[name=tipo_propriedade]', 'fazenda');
  await p.check('[name=tipo_documento][value=cpf]'); await p.fill('[name=documento]', CPF_JOAO);
  await p.click('[data-aba=criador]'); await p.waitForSelector('[data-titular-novo]');
  conf((await p.inputValue('#titularCpf')).replace(/\D/g, '') === CPF_JOAO, 'CPF do titular acompanha o CPF da propriedade');
  await p.click('[data-aba=endereco]'); await local(p, 'GO', 'Rio Verde');
  await p.click('button:has-text("Cadastrar Haras / Fazenda")'); await p.waitForLoadState();
  conf((await p.content()).includes('ainda não está na base de Profissionais'), 'PF sem cadastro de Profissional é bloqueada');
  // cadastra o profissional (com a função Proprietário) e tenta de novo
  await p.goto(B + '/admin/profissionais/novo?cpf=' + CPF_JOAO);
  conf((await p.inputValue('[name=cpf]')).replace(/\D/g, '') === CPF_JOAO, 'CPF já vem preenchido no cadastro de profissional');
  conf((await p.content()).includes('Proprietário de Haras/Fazenda'), 'função Proprietário de Haras/Fazenda existe');
  await p.fill('[name=nome]', 'João Proprietário'); await p.fill('[name=email]', 'joao@santarita.com.br'); await p.fill('[name=telefone]', '64999990000');
  await p.check('[name="especialidades[]"][value=proprietario]'); await p.check('[name="especialidades[]"][value=veterinario]');
  await local(p, 'GO', 'Rio Verde');
  await p.click('button:has-text("Cadastrar profissional")'); await p.waitForLoadState();
  c = await p.content(); conf(c.includes('cadastrado') && c.includes('Rio Verde/GO'), 'profissional cadastrado com município'); conf(c.includes('Veterinário') && c.includes('Proprietário de Haras/Fazenda'), 'profissional acumula funções');
  await p.goto(B + '/admin/haras/novo'); await p.fill('[name=nome]', 'Fazenda Santa Rita'); await p.selectOption('[name=tipo_propriedade]', 'fazenda');
  await p.check('[name=tipo_documento][value=cpf]'); await p.fill('[name=documento]', CPF_JOAO);
  await p.click('[data-aba=criador]'); await p.waitForSelector('[data-titular-ok]');
  conf((await p.textContent('#resultadoTitular')).includes('João Proprietário'), 'busca por CPF mostra o profissional');
  await p.click('[data-aba=contato]'); conf(await p.inputValue('[name=responsavel_nome]') === 'João Proprietário', 'dados do responsável puxados sem redigitar');
  await p.click('[data-aba=endereco]'); await local(p, 'GO', 'Rio Verde');
  await p.click('button:has-text("Cadastrar Haras / Fazenda")'); await p.waitForLoadState();
  c = await p.content(); conf(c.includes('Fazenda Fazenda Santa Rita cadastrado') || c.includes('Santa Rita cadastrado'), 'fazenda PF cadastrada');
  conf(c.includes('Criador / titular') && c.includes('João Proprietário') && c.includes('Titular'), 'titular vinculado na equipe');
  conf(c.includes('Dados da Fazenda'), 'nomenclatura acompanha o tipo (Fazenda)');
  await p.screenshot({ path: '/tmp/mt-admin-fazenda.png', fullPage: true });
  // trocar a UF limpa o município
  await p.click('text=Editar dados'); await p.waitForLoadState(); await p.click('[data-aba=endereco]');
  const geo = p.locator('[data-geo]:has(select[name=uf])');
  await p.waitForFunction(() => document.querySelector('[data-geo] [data-geo-busca]').value === 'Rio Verde');
  await geo.locator('[data-geo-uf]').selectOption('MT'); await p.waitForFunction(() => !document.querySelector('[data-geo] [data-geo-busca]').disabled);
  conf(await geo.locator('[data-geo-busca]').inputValue() === '' && await geo.locator('[data-geo-id]').inputValue() === '', 'trocar a UF limpa o município');
  await geo.locator('[data-geo-busca]').fill('goiânia'); c = await geo.locator('.geo__lista').textContent();
  conf(c.includes('Nenhum município'), 'lista traz só municípios da UF escolhida');
  await geo.locator('[data-geo-busca]').fill('cuia'); conf((await geo.locator('.geo__lista').textContent()).includes('Cuiabá'), 'busca digitável sem acento');
  await geo.locator('[data-geo-busca]').fill('');
  await p.click('button:has-text("Salvar alterações")'); await p.waitForLoadState(); conf((await p.content()).includes('Selecione o município'), 'município obrigatório ao salvar');

  // filtro por UF, vincular usuário, situação
  await p.goto(B + '/admin/haras?uf=MG'); c = await p.content(); conf(c.includes('Haras Vista Alegre') && !c.includes('Fazenda Santa Rita'), 'filtro por UF');
  await p.click('text=Haras Vista Alegre'); await p.waitForLoadState();
  await p.fill('form[action$="/usuarios"] [name=nome]', 'Carlos Gerente'); await p.fill('form[action$="/usuarios"] [name=email]', 'carlos@exemplo.com.br');
  await p.selectOption('form[action$="/usuarios"] [name=papel]', 'gerente'); await p.click('button:has-text("Dar acesso")'); await p.waitForLoadState();
  conf((await p.content()).includes('agora tem acesso'), 'usuario vinculado');
  await p.check('input[name=situacao][value=suspenso]'); await p.click('button:has-text("Alterar situação")'); await p.waitForLoadState();
  conf((await p.content()).includes('Informe o motivo'), 'motivo obrigatório');
  await p.check('input[name=situacao][value=ativo]'); await p.click('button:has-text("Alterar situação")'); await p.waitForLoadState();
  conf((await p.content()).includes('Ativo'), 'situacao ativo');
  await p.screenshot({ path: '/tmp/mt-admin-haras.png', fullPage: true });

  // planos e módulos: criar faixa nova sem alterar código
  await p.goto(B + '/admin/planos'); c = await p.content();
  conf(c.includes('Plano Gratuito') && c.includes('Faixa 2') && c.includes('Saúde Animal e Veterinária'), 'faixas e módulos na Administração');
  await p.click('summary:has-text("Nova faixa")'); await p.fill('details [name=nome]', 'Faixa 5'); await p.fill('details [name=limite_min]', '101'); await p.fill('details [name=limite_max]', '200');
  await p.click('button:has-text("Criar faixa")'); await p.waitForLoadState(); conf((await p.content()).includes('Faixa "Faixa 5" criada (De 101 a 200 animais)'), 'faixa nova criada pela tela');
  await p.screenshot({ path: '/tmp/mt-admin-planos.png', fullPage: true });

  // contato pelo site (UF → Município no lugar do texto livre)
  await v.goto(B + '/#contato');
  await v.fill('#contato [name=nome]', 'Maria Cliente'); await v.fill('#contato [name=telefone]', '31988887777'); await v.fill('#contato [name=haras]', 'Haras Boa Vista');
  conf(await v.locator('#contato [name=cidade_uf]').count() === 0, 'contato sem campo livre de cidade');
  await local(v, 'MG', 'Lavras');
  await v.click('button:has-text("Quero conhecer o MinhaTropa")'); await v.waitForLoadState();
  conf((await v.content()).includes('Recebemos seu contato'), 'contato enviado');
  await p.goto(B + '/admin/contatos'); c = await p.content(); conf(c.includes('Maria Cliente') && c.includes('Lavras/MG'), 'contato aparece no admin com município/UF');
  await p.click('button:has-text("Marcar como atendido")'); conf((await p.content()).includes('atendido'), 'contato atendido');
  // auditoria, usuarios, senha
  await p.goto(B + '/admin/auditoria'); conf((await p.content()).includes('haras.criar'), 'auditoria');
  await p.goto(B + '/admin/usuarios'); conf((await p.content()).includes('Bruno Teste'), 'admins');
  await p.goto(B + '/admin/minha-senha'); await p.fill('[name=senha_atual]', 'SenhaForte123'); await p.fill('[name=senha]', 'OutraSenha456'); await p.fill('[name=senha_confirmation]', 'OutraSenha456');
  await p.click('button:has-text("Alterar senha")'); conf((await p.content()).includes('Sua senha foi alterada'), 'trocar senha');
  await p.click('button:has-text("Sair")'); await p.waitForLoadState(); conf(p.url().endsWith('/admin/entrar'), 'saiu');
  await p.fill('[name=email]', 'bruno@bhsoftware.com.br'); await p.fill('[name=senha]', 'errada1'); await p.click('button:has-text("Entrar")');
  conf((await p.content()).includes('incorretos'), 'senha errada');
  await p.fill('[name=email]', 'bruno@bhsoftware.com.br'); await p.fill('[name=senha]', 'OutraSenha456'); await p.click('button:has-text("Entrar")'); await p.waitForLoadState();
  conf(p.url() === B + '/admin', 'login ok');
  // celular
  const m = await pagina(b, { viewport: { width: 390, height: 844 } });
  await m.context().addCookies(await p.context().cookies());
  for (const u of ['/admin/haras', '/admin/planos', '/admin/haras/novo']) {
    await m.goto(B + u); conf(await m.evaluate(() => document.documentElement.scrollWidth <= 390), 'sem rolagem lateral no celular: ' + u);
  }
  await m.screenshot({ path: '/tmp/mt-admin-mobile.png' });
  process.exitCode = placar() ? 1 : 0;
  await b.close();
})();
