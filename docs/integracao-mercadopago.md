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

### 2.1 Conexão por propriedade (a base)
Tela no painel da propriedade (**Financeiro → Mercado Pago da empresa**), espelhando o Fora da Fila:
- Cadastrar a **aplicação própria** do haras/fazenda (Client ID/Secret; Access Token, Public Key e webhook secret
  de teste e de produção), gravando em `conta_pagamento` (modo `propria`, segredos criptografados).
- **Verificar de quem é o token** (`/users/me`): token de produção válido → ativa a conta (`status = ativa`).
- **Endereço do webhook por conta** (`/webhooks/mercadopago/{uuid da conta}`), conferido pela assinatura dela.
- **Pagamento de teste** da conta (homologação) com o token de teste.
- Alternativa **plataforma** (vendas na conta da BH com repasse) para quem não tem conta MP — decisão do Bruno.

### 2.2 Fluxos de cobrança (em cima da conexão)
Gerar cobrança (Checkout/Pix/cartão) na conta da propriedade para:
- **Venda de animal** (Classificados → "comprar" com pagamento).
- **Embrião / congelamento de sêmen / cobrição** (serviços de reprodução cobrados).
Cada cobrança vira um registro de pagamento vinculado ao recurso (animal, cobrição…), confirmado por **webhook**
(nunca só pelo retorno), com baixa automática no que foi pago.

## 3. Decisões a confirmar (antes dos fluxos que mexem com dinheiro)

1. **Quem recebe**: só **aplicação própria** da propriedade (cada haras com sua conta MP) ou também o modo
   **plataforma** (BH recebe e repassa), como no Fora da Fila? Sugiro os dois, começando pela própria.
2. **Comissão da plataforma**: cobra comissão do Minha Tropa sobre as vendas (split `application_fee`, como no
   Fora da Fila) ou sem comissão nesta fase?
3. **O que é cobrado primeiro**: começar pela **venda de animal** (Classificados) e depois os serviços de
   reprodução (embrião, congelamento, cobrição)?
4. **Checkout**: Checkout Pro (redireciona ao MP) para homologar rápido, depois Pix/cartão transparente na própria
   página (como evoluiu o Fora da Fila)?

## 4. Ordem sugerida

1. **Conexão por propriedade** (Financeiro → Mercado Pago da empresa) + verificar token + webhook por conta. ← base
2. **Cobrança de venda de animal** (Classificados) com confirmação por webhook.
3. **Cobrança dos serviços de reprodução** (embrião, congelamento, cobrição).
4. Comissão/split, repasses e extrato, conforme a decisão 2.
5. Homologação por conta (testes obrigatórios) antes de produção.

## 5. Segurança

- Tokens e segredos **criptografados** com a APP_KEY; nunca no código, no Git ou em logs.
- Número de cartão **nunca** passa pelo servidor (Checkout Pro / formulário seguro do MP).
- Webhook confere a **assinatura** e consulta a API (`GET /v1/payments/{id}`) — não confia no corpo do aviso.
