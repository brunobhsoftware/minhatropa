# Integração Mercado Pago — Minha Tropa

Replica o modelo já validado no **Fora da Fila** (`brunobhsoftware/foradafila`, `docs/integracao-pagamentos.md`):
aplicação única da BH Software + cada propriedade liga a própria conta, e a plataforma cobra em nome dela.
Base para a parte financeira: **venda de animais, embriões, congelamento de sêmen e cobrições — tudo cobrado
pelo Mercado Pago**.

## 1. O que já existe no Minha Tropa (portado do Fora da Fila)

- **Client** `App\Pagamentos\MercadoPago`: credenciais (parametro_sistema, segredos criptografados com a APP_KEY),
  OAuth (`urlAutorizacao`/`trocarCodigo`/`renovarToken`), token da aplicação e de teste, API de Orders
  (`criarOrder`/`order`/`reembolsarOrder`), `/users/me`, e conferência da assinatura dos webhooks.
- **`config/pagamentos.php`** com `MP_*` do `.env`.
- **Administração → Mercado Pago** (`/admin/mercadopago`): cadastro das chaves da aplicação da BH (Client ID/Secret,
  webhook secret, Access Token, Public Key + variantes de teste), pagamento de teste e lista de webhooks.
- **Webhook** `POST /webhooks/mercadopago` (`WebhookController`).
- **Tabela `conta_pagamento`** (V015), por empresa: `modo` (plataforma/propria), `status`, `client_id`,
  `client_secret_cript`, `public_key`, `webhook_secret_cript` e os `teste_*` — tudo pronto para a aplicação
  própria de cada propriedade.

## 2. O que falta (o pedido do Bruno)

### 2.1 Conexão por propriedade — **só OAuth de um clique** (decisão do Bruno, 25/11/2026)
Diferente do Fora da Fila (que passou pela fase manual de Client ID/Secret), no Minha Tropa a propriedade **não
digita nada técnico**. A integração com a conta bancária do haras é por **um clique**:
- Tela no painel (**Financeiro → Mercado Pago**) com o botão **"Conectar Mercado Pago"**.
- OAuth: redireciona para o Mercado Pago, o dono do haras autoriza, o MP volta com o `code`, a plataforma troca
  pelos tokens (`MercadoPago::trocarCodigo`) e grava em `conta_pagamento` **modo `direta`** (tokens criptografados,
  `conta_externa_id` = user_id, `email_conta`/apelido via `/users/me`). O dinheiro cai **direto na conta do haras**.
- "Conta conectada: <e-mail>" + **Desconectar**. Renovação do token pelo agendador (`renovarToken`).
- **Sem** cadastro de aplicação própria por haras, **sem** Client ID/Secret na tela, **sem** blocos de teste/produção.
  A aplicação é única (a da BH, já configurada em Administração → Mercado Pago).
- Pré-requisitos: a aplicação da BH precisa estar **aprovada no MP para OAuth de marketplace** (no Fora da Fila já
  está e é assim que funciona hoje) e `MP_CLIENT_ID`/`MP_CLIENT_SECRET` definidos. Falta só acrescentar `direta` ao
  enum `conta_pagamento.modo` (hoje `plataforma`/`propria`) — migração pequena.

### 2.2 Fluxos de cobrança (em cima da conexão)
Gerar cobrança (Checkout/Pix/cartão) na conta conectada da propriedade para:
- **Venda de animal** (Classificados → "comprar" com pagamento).
- **Embrião / congelamento de sêmen / cobrição** (serviços de reprodução cobrados).
Cada cobrança vira um registro de pagamento vinculado ao recurso (animal, cobrição…), confirmado por **webhook**
(nunca só pelo retorno), com baixa automática no que foi pago.

## 3. Decisões a confirmar (antes dos fluxos que mexem com dinheiro)

A conexão (2.1) já está definida (OAuth de um clique) e pode ser construída sem depender destas respostas.

1. **Comissão da plataforma**: o Minha Tropa retém comissão nas vendas (split `application_fee` na conta conectada,
   como no Fora da Fila) ou sem comissão nesta fase?
2. **O que é cobrado primeiro**: começar pela **venda de animal** (Classificados) e depois os serviços de
   reprodução (embrião, congelamento, cobrição)?
3. **Checkout**: Checkout Pro (redireciona ao MP) para homologar rápido, depois Pix/cartão transparente na própria
   página (como evoluiu o Fora da Fila)?

## 4. Ordem sugerida

1. **Conexão por propriedade** — Financeiro → "Conectar Mercado Pago" (OAuth de um clique), modo `direta`. ← base
2. **Cobrança de venda de animal** (Classificados) com confirmação por webhook.
3. **Cobrança dos serviços de reprodução** (embrião, congelamento, cobrição).
4. Comissão/split, repasses e extrato, conforme a decisão 2.
5. Homologação por conta (testes obrigatórios) antes de produção.

## 5. Segurança

- Tokens e segredos **criptografados** com a APP_KEY; nunca no código, no Git ou em logs.
- Número de cartão **nunca** passa pelo servidor (Checkout Pro / formulário seguro do MP).
- Webhook confere a **assinatura** e consulta a API (`GET /v1/payments/{id}`) — não confia no corpo do aviso.
