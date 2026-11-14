# MinhaTropa

Gestão inteligente para Haras / Fazendas — [minhatropa.com.br](https://minhatropa.com.br).

- `plataforma/` — aplicação Laravel 12 (site público e Administração em `/admin`).
- `plataforma/database/atualizacoes/` — scripts versionados do banco, aplicados pela Administração.
- `docs/identidade-visual/` — logos e kit de identidade visual.

Publicação: push na `main` → GitHub Action grava a branch `publicacao` → Hostinger atualiza o site.
Detalhes em `CLAUDE.md`.
