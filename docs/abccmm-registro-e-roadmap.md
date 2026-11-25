# Registro e reprodução — escopo e roteiro

Como o Minha Tropa trata **registro** (ABCCMM e outras associações) e **reprodução** da tropa.

> **Princípio (decisão do Bruno, 24/11/2026).** O que é **função da associação NÃO entra na nossa
> aplicação**: resenha, 12 medidas morfométricas, avaliação de marcha, validação de DNA, livros
> (MM1…MM6), emissão de registro provisório/definitivo. Isso é da ABCCMM. O Minha Tropa só **guarda
> os dados da tropa**: número do registro, se está registrado ou não, se é provisório ou definitivo,
> associação, microchip. O **foco de construção é o controle da tropa**: cobrição, inseminação,
> embriões e nascimento — é o que os Haras querem gerenciar.

## 1. Referência: o fluxo oficial da ABCCMM (só para contexto)

Serve para entender por que guardamos certos campos — **não** para replicar o processo.

1. **Cobertura/cobrição** comunicada ao SRG até o fim do mês seguinte; égua e garanhão com registro
   definitivo. Tipos: monta natural, IA, transferência de embrião (TE).
2. **Nascimento** comunicado ao SRG.
3. **Resenha + Registro Provisório** (potro ao pé; microchip; DNA): **função da ABCCMM**.
4. **Registro Definitivo** (resenha nova, morfometria, marcha, marca no braço): **função da ABCCMM**.

Do nosso lado interessa apenas **o número e o status** que a ABCCMM devolve, mais a cadeia de
reprodução que o criador controla (cobrição → nascimento → produto).

## 2. Registro no sistema — só armazenamento

Campos que **já existem** no `animal` e bastam:
- `registro` (número), `registro_associacao`, `id_associacao`, `registro_definitivo` (booleano:
  provisório × definitivo), `microchip`, `dna_coletado`.

Ajustes possíveis (pequenos, a confirmar):
- Trocar o booleano por um **status simples** de exibição: `sem registro` · `provisório` ·
  `definitivo` (continua sendo só um dado informado, sem processo por trás).
- Mostrar esses campos de forma clara na ficha e na Consulta (a Consulta já exibe todos).

**Não construir:** tela de resenha, morfometria, marcha, livros, validação de DNA, emissão de
registro. Fora de escopo.

## 3. Reprodução — o foco (a tabela já suporta tudo)

As tabelas `cobricao` e `nascimento` (V005) já cobrem o essencial:

**Cobrição** (`cobricao`): égua, garanhão (cadastrado ou externo pelo nome), **receptora** (para TE),
`tipo` (**monta natural / inseminação artificial / transferência de embrião**), `subtipo` (sêmen
fresco/refrigerado/congelado), data da cobrição, data de confirmação de gestação, previsão de parto,
veterinário, `resultado` (pendente · confirmada · não confirmada · absorvida · abortada · nascimento).

**Nascimento** (`nascimento`): vínculo com a cobrição, mãe, pai (cadastrado ou externo), data, tipo de
parto, sexo, nome do produto, peso ao nascer, vivo/natimorto, veterinário, e `animal_id` do **produto
gerado**.

Ou seja, o modelo de inseminação e embriões já está pronto no banco. O trabalho é de **telas e fluxo**.

## 4. Roteiro item a item (foco em reprodução)

- **Fase 0 — Consulta exibe todos os dados.** ✅ feito (Trilha da vida + ID na associação).
- **Fase 1 — Cobrição completa.** Revisar o formulário para expor bem os três tipos:
  - **Monta natural**: égua + garanhão.
  - **Inseminação artificial**: égua + garanhão (doador do sêmen) + subtipo do sêmen (fresco/
    refrigerado/congelado) + central/origem.
  - **Transferência de embrião**: doadora (égua) + garanhão + **receptora** + data da TE.
  - Acompanhamento: diagnóstico de gestação (data + resultado) e previsão de parto; situação da
    cobrição evoluindo (pendente → confirmada → nascimento/absorvida/abortada).
- **Fase 2 — Nascimento → produto.** No nascimento, botão **"Gerar cadastro do produto"**: cria o
  `animal` já com mãe/pai, criador = propriedade, local de nascimento = propriedade, espécie Equino,
  e vincula à cobrição. Fecha a cobrição como "nascimento".
- **Fase 3 — Painel de reprodução.** Visão por égua: cobrições, gestações em andamento, previsões de
  parto do período, histórico de produtos. (Ocupa parte do menu "Reprodução", hoje "em breve".)
- **Fase 4 — Registro (armazenamento).** Ajustar os campos de registro para o status simples
  (sem/provisório/definitivo) e deixar claros na ficha. Sem qualquer processo de associação.
- **Fase 5 — Revisão das abas "em breve"** restantes, conforme a prioridade do Bruno.

## 5. Decisões registradas (Bruno, 24/11/2026)

1. **Registro vira status** `sem registro / provisório / definitivo` (só exibição; substitui o
   booleano `registro_definitivo`). A fazer no cadastro, ficha e Consulta.
2. **Receptora de TE pode ser externa** (animal do próprio plantel ou cadastrada só pelo nome).
3. **Sêmen congelado** fica como item próprio do menu do animal (controle de estoque de doses,
   origem) — construção posterior.

## 6. Menu por animal (abas da ficha / Consulta)

Espelha o menu da ABCCMM, **sem os itens que são função da associação**. Itens **removidos / não
implementar**: Livro (de elite), 2ª via, Controle de inscrição, Bloqueio, **Resenhas**, **Exames**.

Menu final: **Dados · Saúde · Cobrições · Nascimentos · Transferências · Baixas · Genealogia
(com Filhos) · Premiações · Alertas (breve) · Sêmen congelado (breve) · Situação.**

- **Saúde** (novo): lista os eventos de saúde do animal (vacinas, vermifugações, exames, tratamentos).
- **Genealogia**: passa a mostrar a ascendência (árvore) **e** a seção **Filhos (produtos)** — a
  aba "Filhos" separada foi incorporada aqui.
- **Premiações** (novo, feito): cadastro simples — evento, data, local (+ colocação). Gerenciado na
  ficha/Consulta do dono; tabela `premiacao` (V023).

## 7. Próximos passos

- **Registro → status** (decisão 1): migração + cadastro + exibição.
- **Fase 1 — Cobrição completa** (monta / IA / embrião, receptora externa, diagnóstico de gestação).
- **Fase 2 — Nascimento → produto**.
- **Sêmen congelado** (estoque de doses) e **Alertas**.
