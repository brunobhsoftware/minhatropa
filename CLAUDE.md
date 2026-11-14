# MinhaTropa — contexto do projeto

Plataforma SaaS de gestão de Haras (minhatropa.com.br): cavalos, plantel, rotinas, saúde, reprodução, treinos,
custos e a administração de vários Haras numa só plataforma. Dono do projeto: Bruno (BH Software).
Um gerente parceiro vai implantar o sistema em Haras de todo o Brasil, por isso tudo é **multi-Haras** desde o início.
Idioma do projeto, da documentação e das conversas: **português do Brasil**.
Projeto irmão (mesma estrutura, mesma conta Hostinger): **foradafila.com.br** (`brunobhsoftware/foradafila`).

## Regras de texto

- **"Haras" sempre com H**, em títulos, menus, botões e descrições (o kit tem uma versão antiga "aras": não publicar).
- Não inventar preços ou políticas comerciais no site: só o que o Bruno definir.
- **Haras / Fazenda (13/11/2026):** a propriedade pode ser Haras ou Fazenda (`empresa.tipo_propriedade`). Texto genérico usa
  "Haras / Fazenda"; dentro do painel o nome acompanha o tipo (`$haras->rotulo()`, `$haras->doRotulo()` → "da Fazenda").
- **Plano por tamanho de plantel (13/11/2026, substitui o "1º animal grátis"):** a faixa vem da quantidade de animais **ativos**
  (vendido, transferido e morto não contam). Faixas na tabela `plano_plantel`: Gratuito até 5, Faixa 1 (6–10), Faixa 2 (11–20),
  21–50, 51–100 e ilimitado. Criar/alterar faixa é em Administração → Planos e módulos, sem mexer no código.
  No limite, o cadastro é bloqueado e aparece o aviso (modal) com a oferta da próxima faixa. Regra em `App\Support\Licenca`.
- **Preços:** `valor_mensal` das faixas e módulos está NULL = "Sob consulta". Só o Bruno define valores (Planos e módulos).
- **Módulos adicionais (add-ons):** `modulo_sistema` + `empresa_modulo`: `equipe` (acesso externo), `saude`, `manejo`, `nutricao`.
  Independentes da faixa. Não contratado aparece no menu com cadeado e "Módulo adicional" e abre a tela de benefícios com
  "Solicitar ativação"; a Administração ativa na ficha da propriedade. Regra em `App\Support\Modulos`.
  Saúde, Manejo e Nutrição têm só a estrutura (menu, rota, liberação): as telas de cada um ainda serão construídas.
- Cobrições, nascimentos e transferências continuam exigindo faixa paga (`empresa.licenca_ativa`, ligada ao definir a faixa).

## Onde está cada coisa

| Caminho | Conteúdo |
| --- | --- |
| `plataforma/` | Aplicação **Laravel 12** (site público + `/admin`). Publicada pela Action na branch `publicacao` |
| `plataforma/database/atualizacoes/V0xx__*.sql` | Scripts versionados do banco (idempotentes). Aplicados pela tela `/admin/banco` |
| `plataforma/tests/e2e/` | Testes de ponta a ponta (Playwright) contra banco limpo + `php artisan serve`: `fluxo-admin.js` (54) e depois `fluxo-cadastro.js` (74); apoio em `apoio.js` |
| `docs/identidade-visual/` | Logos recortadas (PNG transparente) e o kit original enviado pelo Bruno em `kit-original/` |
| `.github/workflows/build-plataforma.yml` | Publicação: `composer install --no-dev` e grava em `publicacao` |
| `.hostinger/site.json` | Alvo da publicação na Hostinger |

## Identidade visual (kit de 09/11/2026)

- Cores: Azul profundo `#101828`, Amarelo vivo `#FFD60A`, Branco `#FFFFFF`, Cinza claro `#F3F4F6`.
  O azul do topo/abertura do site é `#02264b` (`--azul-hero`), igual ao da foto de referência.
- Fontes: Space Grotesk (títulos) e Inter (textos), pelo Google Fonts.
- Logo oficial escolhida: **horizontal "MinhaTropa"** bicolor (Minha azul, Tropa amarela) com o símbolo do cavalo
  dentro da estrutura do Haras e o slogan "GESTÃO INTELIGENTE PARA HARAS". Versões em `plataforma/public/img/`:
  `logo-minhatropa.png` (fundo claro), `logo-minhatropa-branca.png` (fundo escuro), `simbolo-minhatropa*.png`, favicons.
  A logo vertical "minhatropa." do kit tem um defeito no slogan ("PAɅRA"): não usar até ser redesenhada.
  As logos são recortes de imagens geradas por IA: falta vetorizar (SVG) uma versão oficial.
- Página inicial recriada em HTML/CSS a partir de `kit-original/01_LAYOUT_APROVADO/homepage_referencia_haras_corrigido_v2.png`.
  Fotos usadas: `hero-cavalo.jpg` e `por-do-sol.jpg` (recortes do layout aprovado). O painel no notebook/celular é HTML ("Imagem ilustrativa").

## Plataforma (Laravel)

- Laravel 12, PHP 8.3, Blade + CSS próprio (`public/css/site.css`, `public/css/admin.css`) + JS puro. **Sem Vite/npm.**
- Sem migrations do Laravel: as tabelas vêm dos scripts `database/atualizacoes/V0xx`. Sessão e cache em arquivo, fila sync.
- Sessões em `MT_ARMAZENAMENTO/sessoes` (fora do public_html em produção; a publicação reescreve o public_html).
- Auth no model `Usuario` (tabela `usuario`, coluna `senha_hash`). Login bloqueia 15 min após 5 erros.
- **Instalação sem phpMyAdmin** (`/admin/instalar`): só existe enquanto não há administrador com senha. Pede a senha do
  banco (do `.env` do servidor) como prova de dono, aplica o V001 e cria o administrador. Depois some (redireciona ao login).
- **Atualizações do banco** (`/admin/banco`, `App\Support\AtualizacaoBanco`): lista os `V0xx`, aplica comando por comando
  (ignora SELECT/SHOW de conferência), registra em `parametro_sistema` (`banco:V0xx`) e na auditoria. Sem DELIMITER/triggers
  (nem a palavra DELIMITER em comentário). Variável: `SET @x = (SELECT ...)`, nunca `SELECT ... INTO`; no PREPARE usar `DO 0`, não `SELECT 1`.
  Cabeçalho obrigatório na 1ª linha do script: `-- MinhaTropa — V0xx: descrição`. **Toda mudança de banco = novo script V0xx idempotente.**
- Administração (`/admin`, middleware `admin` = `usuario.admin_plataforma`; os demais recebem 404): Início (números),
  **Haras** (lista com busca/filtro, cadastro com CPF/CNPJ validado, endereço com ViaCEP, situação teste/ativo/suspenso/encerrado
  com motivo, pessoas com acesso e papel), **Contatos do site**, **Administradores** (criar, editar, senha provisória),
  **Planos e módulos** (faixas de plantel, módulos, pedidos de upgrade/ativação), **Atualizações do banco**, **Auditoria**, Minha senha.
  Itens "em breve": Cobrança on-line, Mercado Pago, E-mails enviados.
- **Área do Haras** (`/entrar`, `/painel`, middleware `haras` = `App\Http\Middleware\IdentificarHaras` + `App\Support\HarasAtual`
  na sessão; quem tem vários Haras escolhe em `/painel/escolher`): Início, Animais, Cobrições, Nascimentos, Transferências,
  Criador e equipe, Dados da propriedade, Plano e módulos, e os módulos adicionais.
- Testes: `fluxo-admin.js` (54) e depois `fluxo-cadastro.js` (74), contra banco limpo (`NODE_PATH` com o playwright; `SENHA_BANCO` = senha do `.env`).
- **Identificação única:** `empresa.documento` (CPF ou CNPJ) é obrigatório e único (`uk_empresa_documento`). Cadastro começa pela
  titularidade (PF/PJ). **Pessoa Física só cadastra se o CPF tiver cadastro ativo em `profissional`**: os dados são puxados e a pessoa
  vira titular (`empresa.titular_profissional_id`, `empresa_profissional.titular`). Sem cadastro, o site leva a `/cadastro/profissional`.
  A consulta pública de CPF devolve nome/e-mail mascarados e exige o mesmo e-mail do cadastro (não expor dados pessoais).
- **Profissionais** (`profissional`, CPF único) é a base de pessoas físicas; funções em `profissional_especialidade`
  (inclui "Proprietário de Haras/Fazenda"; acumula várias). Vínculo N:N em `empresa_profissional` (funções, titular, acesso).
  No painel ("Criador e equipe") e na aba "Criador / proprietário" da Administração, **o primeiro campo é sempre a busca por CPF**.
- **UF → Município:** nunca usar texto livre para cidade/estado. Usar `<x-uf-municipio>` (+ `public/js/geo.js`) e
  `App\Support\Geografia` (regras, `colunas()`); tabelas `estado` e `municipio` (IBGE, 5.571 municípios, V007). As colunas
  `cidade`/`uf` continuam gravadas como cópia do nome oficial.
- **RBAC:** `App\Support\Permissao` (papel de `empresa_usuario` × área) + middleware `permissao:área` nas rotas do painel.
  Só o proprietário vê "Plano e módulos". Acesso externo da equipe exige o módulo `equipe`.
- Sem o banco até o V007, o painel mostra "em atualização" e as telas de Haras/Profissionais/Planos da Administração mandam para `/admin/banco`.
- Toda ação relevante grava em `auditoria` via `App\Support\Auditoria::registrar()` (senhas nunca entram).
- Datas: banco em UTC; tela em `America/Sao_Paulo` (helpers `data_local`, `para_input_data`, `utc_de_local` em `app/helpers.php`).

## Banco de dados (produção)

- MariaDB na Hostinger. Banco `u839930205_minhatropa`, usuário `u839930205_tropauser`. Conexão `127.0.0.1:3306`.
- Senha só no `.env.minhatropa` do servidor. Nunca no código, no Git, neste arquivo ou em respostas.
- Tabelas do V001: `parametro_sistema`, `usuario`, `plano`, `empresa` (o Haras), `empresa_usuario` (papel: proprietário,
  gerente, veterinário, treinador, tratador, financeiro, consulta), `contato` (formulário do site), `auditoria`.
- Convenções: InnoDB, utf8mb4, datas em UTC, dinheiro `DECIMAL(12,2)`, `id BIGINT` interno + `uuid` público (URLs usam o uuid),
  multi-Haras por `empresa_id` em toda tabela de negócio, exclusão lógica (`excluido_em`).

## E-mail (segunda etapa)

- Contas criadas pelo Bruno em 09/11/2026: `noreply@minhatropa.com.br` (avisos automáticos) e
  `atendimento@minhatropa.com.br` (atendimento; já aparece no site). SMTP da Hostinger (smtp.hostinger.com, SSL 465).
- Senhas só no `.env.minhatropa` do servidor. Hoje `MAIL_MAILER=log` (nada é enviado).

## Publicação (Hostinger)

- **Como publicar:** commit e push na `main` mexendo em `plataforma/`. A Action instala as dependências (sem dev) e grava
  o resultado na branch `publicacao` (commit normal, sem force push). A Hostinger acompanha `publicacao` (auto-deploy do GitHub).
- **Nunca** apontar a publicação automática para a `main`: levaria `docs/` e `CLAUDE.md` para o site.
- Não editar a branch `publicacao` à mão.
- O Laravel inteiro fica em `public_html`; o `.htaccess` da raiz manda tudo para `public/` e bloqueia arquivos ocultos.
- **`.env` de produção:** `/home/u839930205/domains/minhatropa.com.br/.env.minhatropa` (fora do `public_html`), lido por
  `bootstrap/app.php`. Para trocar: `hosting_files_generate-upload-url` e envio TUS para a **raiz** do servidor de arquivos
  (`.../api/tus/.env.minhatropa`, sem o `public_html` no caminho).
- Sem SSH: não há `php artisan` no servidor. Banco: `/admin/instalar` (1ª vez) e depois `/admin/banco`.
- A conta Hostinger `u839930205` também hospeda foradafila.com.br e outros sites: não mexer neles.

## Desenvolvimento local

- MariaDB local, `.env` a partir do `.env.example`, `php artisan serve`. O proxy da nuvem bloqueia downloads do Composer:
  o `vendor/` pode vir da branch `publicacao` (`git archive origin/publicacao vendor | tar -x -C plataforma`).

## Próximas etapas (combinadas com o Bruno)

1. Área dos Haras (login de cada Haras, cadastro de animais, agenda/rotinas, saúde, reprodução, treinos, financeiro, relatórios).
2. Mercado Pago, planos e cobrança (contratação on-line da licença), envio de e-mails (noreply@/atendimento@) — mesma estrutura do Fora da Fila.
