# ABCCMM — Lógica de registro e roteiro de implementação

Estudo do fluxo oficial de registro da **ABCCMM** (Associação Brasileira dos Criadores do Cavalo
Mangalarga Marchador) e como aplicá-lo no Minha Tropa, item a item.

> **Princípio.** O Minha Tropa gerencia o **lado do criador** no processo da ABCCMM: declara
> coberturas e nascimentos, registra a resenha, guarda os números de registro e acompanha os
> prazos. **Quem emite o registro oficial é a ABCCMM** (o SRG tem delegação do MAPA). O sistema
> não "emite" registro; ele espelha e organiza o processo e guarda os dados e números informados
> pelo criador. Nomenclatura livre de tabelas/campos (banco MySQL/Hostinger).

## 1. O fluxo oficial (SRG da ABCCMM)

O Serviço de Registro Genealógico (SRG) conduz o produto por quatro momentos:

1. **Cobertura / cobrição.** O criador comunica a cobertura ao SRG **até o fim do mês seguinte**
   ao da cobertura (depois disso há multa). Égua e garanhão precisam ter **Registro Definitivo**.
   Tipos: monta natural, inseminação artificial (IA) e transferência de embrião (TE).

2. **Nascimento.** O nascimento do produto é comunicado ao SRG dentro do prazo da raça.

3. **Resenha inicial + Registro Provisório.** Com o **potro ao pé** (preferencialmente até o
   desmame; **até 24 meses sem multa**), um técnico credenciado faz a **resenha** — a "CPF do
   cavalo": descrição detalhada (pelagem, marcas de nascença, particularidades, redemunhos),
   verificação de **defeitos desclassificantes** (ex.: albinoidismo, prognatismo), **implantação
   do microchip** e **coleta de pelo para DNA**. O **Registro Provisório só é emitido após o DNA
   confirmar a paternidade/maternidade**. Livros: **MM1** (machos) e **MM2** (fêmeas).

4. **Registro Definitivo.** Com o animal adulto, nova visita técnica: **nova resenha**, conferência
   do **microchip**, **avaliação morfológica e de marcha**, **12 medidas morfométricas**, aferição
   de **altura** dentro do padrão (machos 1,47–1,57 m, ideal 1,52; fêmeas 1,40–1,54 m, ideal 1,46),
   **mínimo de 50% dos pontos** e ausência de faltas graves. Aprovado, o animal é **marcado no
   braço**. Machos: aos 30 meses devem ter os dois testículos na bolsa; a partir de 36 meses são
   apresentados para o definitivo. Livros: **MM3/MM4** (Aberto, por adjudicação) e **MM5/MM6**
   (Fechado).

Cadeia resumida: **Cobrição → Nascimento → Resenha (Provisório: microchip + DNA) → [desenvolvimento]
→ Definitivo (resenha nova + morfometria + marcha + marca no braço).**

## 2. O que o sistema já tem

- **Animal** (`animal`): identificação, trilha da vida (criador, proprietário, local de nascimento),
  genealogia (pai/mãe + pré-cadastro `animal_referencia`), fotos, sociedade/consórcio. Campos de
  registro já existentes: `registro`, `registro_associacao`, `id_associacao`, `registro_definitivo`
  (booleano), `microchip`, `dna_coletado` (booleano), `pelagem`/`pelagem_complemento`, `peso_kg`,
  `altura_cm`, `castrado`.
- **Cobrições** (`cobricao`, V005) e **Nascimentos** (`nascimento`, V005): telas existem, com dados
  básicos.
- **Pelagens**: catálogo ABCCMM (V021).
- **Consulta de animais**: exibe todos os dados do cadastro, incluindo a Trilha da vida (feito agora).

## 3. O que falta (lacunas para a lógica ABCCMM)

- **Status do registro**: hoje só há o booleano `registro_definitivo`. Falta o **ciclo**
  (sem registro → nascimento comunicado → provisório → definitivo) com datas, números e livro.
- **Resenha estruturada**: hoje só há `pelagem`. Falta a resenha de verdade — particularidades
  (marcas na cabeça e nos membros), **redemunhos**, microchip (nº + local), DNA (coleta, laboratório,
  resultado), defeitos desclassificantes conferidos, e, no definitivo, **morfometria (12 medidas)**
  e **marcha**.
- **Cobrição detalhada**: tipo (monta/IA/TE), diagnóstico de gestação, previsão de parto, prazo de
  comunicação.
- **Nascimento → produto**: gerar automaticamente o animal (produto) a partir do nascimento, com a
  trilha e a genealogia já preenchidas, vinculado à cobrição.
- **Sincronização**: ao registrar a resenha/provisório ou o definitivo, **atualizar
  automaticamente os campos de registro do animal**.
- **Prazos e alertas**: cobrição (fim do mês seguinte), provisório (24 meses), machos (30/36 meses).

## 4. Modelo de dados proposto (a confirmar)

**A. Ciclo de registro no `animal`** (migração nova):
- `registro_status`: `sem_registro` · `nascimento_comunicado` · `provisorio` · `definitivo` · `cancelado`.
- `registro_provisorio` (nº), `registro_provisorio_data`, `registro_definitivo_data`, `livro`
  (MM1…MM6). O campo `registro` passa a guardar o número vigente; `registro_definitivo` (booleano)
  vira derivado do status.

**B. Resenha** (tabela `resenha`, 1..N por animal — inicial/provisória e definitiva):
- `tipo` (inicial/definitiva), `data`, `tecnico_nome`/credencial, `pelagem` + particularidades
  (marcas de cabeça e membros, **redemunhos** com posição), sinais particulares, `microchip` + local,
  `dna_coletado`/`dna_laboratorio`/`dna_resultado`, defeitos desclassificantes conferidos, fotos.
- Só na **definitiva**: **12 medidas morfométricas**, **marcha** (tipo batida/picada + pontuação),
  altura aferida, pontos totais, resultado (apto/não).

**C. Cobrição** (estender `cobricao`): `tipo` (monta/IA/TE), estação de monta, diagnóstico de
gestação (data + resultado), previsão de parto, situação do prazo de comunicação.

**D. Nascimento** (estender `nascimento`): vínculo com a cobrição, sexo, pelagem, vivo/natimorto,
botão **"Gerar cadastro do produto"** que cria o `animal` já com mãe/pai, criador = propriedade,
local de nascimento = propriedade, espécie Equino.

## 5. Roteiro item a item

- **Fase 0 — Consulta exibe todos os dados.** ✅ feito (Trilha da vida + ID na associação na ficha).
- **Fase 1 — Ciclo de registro.** Migração do `registro_status` + datas/nº/livro; exibição na ficha
  e na Consulta; campo no cadastro. É a base de tudo.
- **Fase 2 — Resenha.** Tela e dados estruturados (particularidades, redemunhos, microchip, DNA,
  defeitos). Ao salvar a resenha inicial com DNA confirmado, **sincroniza** o status para provisório.
- **Fase 3 — Cobrição detalhada.** Tipo, diagnóstico de gestação, previsão de parto e alerta de prazo.
- **Fase 4 — Nascimento → produto.** Gera o animal a partir do nascimento, trilha e genealogia
  pré-preenchidas, vinculado à cobrição.
- **Fase 5 — Registro definitivo.** Morfometria (12 medidas), marcha, pontos, altura, marca no braço;
  sincroniza o status para definitivo e atualiza `registro`.
- **Fase 6 — Prazos/alertas e revisão das abas "em breve"** (Reprodução, Organograma, etc.).

## 6. Decisões a confirmar com o Bruno

1. **Papel do sistema**: confirmar que o Minha Tropa **registra os números oficiais informados pelo
   criador** (provisório/definitivo) e organiza o processo — não emite registro. (É só da ABCCMM.)
2. **Campos da resenha**: padronizar as particularidades em listas (marcas de cabeça/membros,
   redemunhos) ou deixar texto livre guiado? (Sugiro listas + "outra".)
3. **DNA como trava**: marcar "provisório obtido" só com DNA confirmado, ou registrar o que o criador
   informar e sinalizar pendência?
4. **Prazos/alertas**: quer os avisos automáticos (cobrição até o fim do mês seguinte; provisório até
   24 meses; machos 30/36 meses)?
5. **Multirraça**: a lógica detalhada (resenha/morfometria/marcha) é do Mangalarga Marchador. Para
   outras raças, manter os campos genéricos e só aplicar a régua ABCCMM quando a associação for ABCCMM?
