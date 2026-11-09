const { chromium } = require('playwright');
const B=process.env.BASE_URL || 'http://127.0.0.1:8000';
// Uso: banco limpo + php artisan serve; DB_PASSWORD do .env local em SENHA_BANCO.
const SENHA_BANCO=process.env.SENHA_BANCO || 'Senha$%local123';
let ok=0, falhas=0;
const conf=(c,m)=>{ if(c){ok++;} else {falhas++; console.log('FALHOU:',m);} };
(async () => {
  const b = await chromium.launch();
  const p = await b.newPage({ viewport: { width: 1366, height: 860 } });
  // contato pelo site (antes da instalação: tabela não existe → mensagem de erro amigável)
  await p.goto(B+'/admin/entrar'); conf(p.url().endsWith('/admin/instalar'),'entrar redireciona p/ instalar');
  await p.screenshot({path:'/tmp/mt-instalar.png',fullPage:true});
  await p.fill('[name=senha_banco]','errada'); await p.fill('[name=nome]','Bruno Teste'); await p.fill('[name=email]','bruno@bhsoftware.com.br');
  await p.fill('[name=senha]','SenhaForte123'); await p.fill('[name=senha_confirmation]','SenhaForte123');
  await p.click('button:has-text("Instalar")'); conf((await p.content()).includes('não confere'),'senha do banco errada recusada');
  await p.fill('[name=senha_banco]',SENHA_BANCO); await p.fill('[name=senha]','SenhaForte123'); await p.fill('[name=senha_confirmation]','SenhaForte123');
  await p.click('button:has-text("Instalar")'); await p.waitForLoadState();
  conf(p.url()===B+'/admin','instalou e entrou: '+p.url());
  conf((await p.content()).includes('Instalação concluída'),'mensagem de instalação');
  await p.screenshot({path:'/tmp/mt-admin-inicio.png',fullPage:true});
  // instalar de novo bloqueado
  await p.goto(B+'/admin/instalar'); conf(!p.url().endsWith('/instalar'),'instalar some depois');
  // novo haras
  await p.goto(B+'/admin/haras/novo');
  await p.fill('[name=nome]','Haras Vista Alegre'); await p.fill('[name=documento]','11.222.333/0001-81');
  await p.fill('[name=cidade]','Itaúna'); await p.selectOption('[name=uf]','MG'); await p.fill('[name=area_hectares]','1.250,5');
  await p.fill('[name=responsavel_nome]','João da Silva'); await p.fill('[name=whatsapp]','31999998888');
  await p.click('button:has-text("Cadastrar Haras")'); await p.waitForLoadState();
  let c=await p.content(); conf(c.includes('cadastrado'),'haras cadastrado'); conf(c.includes('11.222.333/0001-81'),'cnpj formatado'); conf(c.includes('1.250,50 ha'),'area');
  // documento inválido
  await p.goto(B+'/admin/haras/novo'); await p.fill('[name=nome]','Haras X'); await p.fill('[name=documento]','11.111.111/1111-11');
  await p.click('button:has-text("Cadastrar Haras")'); conf((await p.content()).includes('inválido'),'cnpj inválido recusado');
  // vincular usuário
  await p.goto(B+'/admin/haras'); await p.click('text=Haras Vista Alegre'); await p.waitForLoadState();
  await p.fill('form[action$="/usuarios"] [name=nome]','Carlos Gerente'); await p.fill('form[action$="/usuarios"] [name=email]','carlos@exemplo.com.br');
  await p.selectOption('form[action$="/usuarios"] [name=papel]','gerente'); await p.click('button:has-text("Dar acesso")'); await p.waitForLoadState();
  conf((await p.content()).includes('agora tem acesso'),'usuario vinculado');
  // situação suspenso sem motivo
  await p.check('input[name=situacao][value=suspenso]'); await p.click('button:has-text("Alterar situação")'); await p.waitForLoadState();
  conf((await p.content()).includes('Informe o motivo'),'motivo obrigatório');
  await p.check('input[name=situacao][value=ativo]'); await p.click('button:has-text("Alterar situação")'); await p.waitForLoadState();
  conf((await p.content()).includes('Ativo'),'situacao ativo');
  await p.screenshot({path:'/tmp/mt-admin-haras.png',fullPage:true});
  // contato pelo site
  const v = await b.newPage(); await v.goto(B+'/#contato');
  await v.fill('#contato [name=nome]','Maria Cliente'); await v.fill('#contato [name=telefone]','31988887777'); await v.fill('#contato [name=haras]','Haras Boa Vista');
  await v.click('button:has-text("Quero conhecer o MinhaTropa")'); await v.waitForLoadState();
  conf((await v.content()).includes('Recebemos seu contato'),'contato enviado');
  await p.goto(B+'/admin/contatos'); c=await p.content(); conf(c.includes('Maria Cliente'),'contato aparece no admin');
  await p.click('button:has-text("Marcar como atendido")'); conf((await p.content()).includes('atendido'),'contato atendido');
  // banco
  await p.goto(B+'/admin/banco'); c=await p.content(); conf(c.includes('V001') && c.includes('Aplicado'),'V001 aparece aplicado');
  p.on('dialog', d=>d.accept()); await p.click('button:has-text("Aplicar de novo")'); await p.waitForLoadState();
  conf((await p.content()).includes('aplicado ('),'reaplicar V001 idempotente');
  await p.screenshot({path:'/tmp/mt-admin-banco.png',fullPage:true});
  // auditoria, usuarios, senha
  await p.goto(B+'/admin/auditoria'); conf((await p.content()).includes('haras.criar'),'auditoria');
  await p.goto(B+'/admin/usuarios'); conf((await p.content()).includes('Bruno Teste'),'admins');
  await p.goto(B+'/admin/minha-senha'); await p.fill('[name=senha_atual]','SenhaForte123'); await p.fill('[name=senha]','OutraSenha456'); await p.fill('[name=senha_confirmation]','OutraSenha456');
  await p.click('button:has-text("Alterar senha")'); conf((await p.content()).includes('Sua senha foi alterada'),'trocar senha');
  // sair e entrar; não admin bloqueado
  await p.click('button:has-text("Sair")'); await p.waitForLoadState(); conf(p.url().endsWith('/admin/entrar'),'saiu');
  await p.fill('[name=email]','bruno@bhsoftware.com.br'); await p.fill('[name=senha]','errada1'); await p.click('button:has-text("Entrar")');
  conf((await p.content()).includes('incorretos'),'senha errada');
  await p.screenshot({path:'/tmp/mt-admin-entrar.png'});
  await p.fill('[name=email]','bruno@bhsoftware.com.br'); await p.fill('[name=senha]','OutraSenha456'); await p.click('button:has-text("Entrar")'); await p.waitForLoadState();
  conf(p.url()===B+'/admin','login ok');
  // celular admin
  const m = await b.newPage({ viewport:{width:390,height:844} }); 
  await m.context().addCookies(await p.context().cookies());
  await m.goto(B+'/admin/haras'); await m.screenshot({path:'/tmp/mt-admin-mobile.png'});
  conf(await m.evaluate(()=>document.documentElement.scrollWidth<=390),'admin sem rolagem lateral');
  console.log('ok',ok,'falhas',falhas);
  await b.close();
})();
