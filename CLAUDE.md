# MinhaTropa — contexto do projeto

Plataforma SaaS de gestão de Haras (minhatropa.com.br): cavalos, plantel, rotinas, saúde, reprodução, treinos,
custos e a administração de vários Haras numa só plataforma. Dono do projeto: Bruno (BH Software).
Um gerente parceiro vai implantar o sistema em Haras de todo o Brasil, por isso tudo é **multi-Haras** desde o início.
Idioma do projeto, da documentação e das conversas: **português do Brasil**.
Projeto irmão (mesma estrutura, mesma conta Hostinger): **foradafila.com.br** (`brunobhsoftware/foradafila`).

## Regras de texto

- **"Haras" sempre com H**, em títulos, menus, botões e descrições (o kit tem uma versão antiga "aras": não publicar).
- Não inventar preços, teste grátis ou políticas comerciais no site: só o que o Bruno definir.

## Onde está cada coisa

| Caminho | Conteúdo |
| --- | --- |
| `plataforma/` | Aplicação **Laravel 12** (site público + `/admin`). Publicada pela Action na branch `publicacao` |
| `plataforma/database/atualizacoes/V0xx__*.sql` | Scripts versionados do banco (idempotentes). Aplicados pela tela `/admin/banco` |
| `plataforma/tests/e2e/fluxo-admin.js` | Teste de ponta a ponta (Playwright, 24 conferências) contra banco limpo + `php artisan serve` |
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
  (ignora SELECT/SHOW de conferência), registra em `parametro_sistema` (`banco:V0xx`) e na auditoria. Sem DELIMITER/triggers.
  Cabeçalho obrigatório na 1ª linha do script: `-- MinhaTropa — V0xx: descrição`. **Toda mudança de banco = novo script V0xx idempotente.**
- Administração (`/admin`, middleware `admin` = `usuario.admin_plataforma`; os demais recebem 404): Início (números),
  **Haras** (lista com busca/filtro, cadastro com CPF/CNPJ validado, endereço com ViaCEP, situação teste/ativo/suspenso/encerrado
  com motivo, pessoas com acesso e papel), **Contatos do site**, **Administradores** (criar, editar, senha provisória),
  **Atualizações do banco**, **Auditoria**, Minha senha. Itens "em breve": Planos e cobrança, Mercado Pago, E-mails enviados.
- Usuário que não é administrador não entra ainda (a área dos Haras é a próxima etapa).
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
2. Mercado Pago, planos e cobrança, envio de e-mails (noreply@/atendimento@) — mesma estrutura do Fora da Fila.
