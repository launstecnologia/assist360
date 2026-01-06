# Documentação Completa do Sistema: Assistência 360°

Este documento descreve os dois principais componentes da plataforma: o portal voltado ao cliente final (inquilino) e o portal administrativo (operador).

---

## Parte 1: Plataforma Assistência 360° (Portal do Inquilino)

### 1. Tela de Login e Localização de Imobiliária

Esta é a tela inicial de acesso à plataforma de assistência. O login é projetado para que o usuário (locatário/inquilino) primeiro localize e selecione uma imobiliária específica antes de se autenticar.

**Observação:** O sistema opera como "white label", adaptando sua logomarca (ex: "KSS Assistência" ou "TOPX") dependendo do parceiro ou da imobiliária que está sendo acessada.

A tela oferece duas formas distintas de localizar a imobiliária:

#### Método 1: Por Localização (Busca Manual)

Nesta aba, o usuário encontra a imobiliária usando um sistema de filtros dependentes:

- **Estado:** O usuário seleciona o estado (ex: SP) em uma lista.
- **Cidade:** O campo é preenchido com as cidades que possuem imobiliárias cadastradas naquele estado (ex: CRAVINHOS).
- **Imobiliária:** O usuário seleciona a imobiliária específica (ex: Imobiliária X) na cidade escolhida.

#### Método 2: Por CEP (Busca Rápida)

Nesta aba, o usuário pode localizar a imobiliária ou o imóvel diretamente pelo CEP:

- **CEP da imobiliária ou imóvel:** O usuário insere o código postal e o sistema faz a busca da imobiliária associada.

### 2. Autenticação do Usuário

Após a imobiliária ser localizada (por qualquer um dos dois métodos), o formulário de login é apresentado para o usuário preencher seus dados de acesso:

- **CPF/CNPJ do Locatário (Inquilino):** Campo para inserir o documento de identificação do inquilino.
- **Senha:** A senha de acesso ao portal da imobiliária.

O usuário então clica em "Acessar Assistência" para logar.

**Detalhe da Implementação (API):**

O processo de autenticação não ocorre diretamente nesta aplicação. Ao clicar em "Acessar Assistência", os dados de CPF/CNPJ e Senha são enviados para uma API externa da KSS. Esta API é responsável por validar as credenciais do usuário. Se a autenticação for bem-sucedida, a API retorna a permissão de acesso, permitindo que o usuário entre na plataforma.

A tela também fornece links de apoio, como "Esqueci a Senha" e "Não estou conseguindo acesso".

### 3. Painel Principal (Dashboard)

Após o login ser validado pela API da KSS, o usuário é direcionado para o Painel Principal. Esta tela é o hub central para o inquilino gerenciar suas solicitações de assistência.

#### 3.1. Boas-Vindas e Ação Rápida

No topo, uma faixa de boas-vindas personaliza a saudação ao usuário (ex: "Olá, Lucas!"). Nesta faixa, encontra-se o botão de ação principal (CTA):

- **"Nova Solicitação":** Este botão inicia o fluxo para o usuário criar um novo pedido de assistência ou serviço.

#### 3.2. Resumo de Solicitações

Dois cartões de estatística fornecem uma visão rápida do status dos serviços:

- **Solicitações Ativas:** Contador de chamados em andamento.
- **Serviços Concluídos:** Contador de serviços já finalizados.

#### 3.3. Listagem: "Suas Solicitações"

Exibe o histórico de solicitações do usuário, detalhando:

- **Serviço:** O nome do serviço solicitado (ex: Cópia de chave simples).
- **Status:** Etiqueta visual do estado atual (ex: Serviço Concluído).
- **Protocolo:** O código de identificação único (ex: KS1966179).
- **Descrição:** Um breve texto sobre o problema.
- **Data de Criação:** O registro de quando a solicitação foi aberta.

#### 3.4. Seção: "Seus Dados"

Exibe as informações pessoais do usuário (Nome, WhatsApp), com um botão "Editar" para atualizações.

#### 3.5. Seção: "Seus Imóveis"

Lista o(s) imóvel(is) vinculado(s) ao contrato do inquilino, exibindo o endereço completo e dados do contrato.

#### 3.6. Seção: "Status da Conta"

Um painel informativo que mostra o status da conta (ex: Ativo) e a data/hora da última sincronização de dados.

### 4. Fluxo de Nova Solicitação (Usuário Logado)

Iniciado pelo botão "Nova Solicitação", este fluxo guia o usuário por 5 etapas:

#### Etapa 1: Endereço

O usuário seleciona um dos "Endereços Salvos" (imóveis) vinculados ao seu contrato. Esta lista é fornecida pela API da KSS.

Ele também informa a "Finalidade da Locação" (Residencial/Comercial e Casa/Apartamento).

#### Etapa 2: Serviço

A tela exibe as Categorias de Serviço (ex: Chaveiro, Eletricista).

Ao clicar em uma categoria, ela se expande para mostrar os Tipos de Serviço específicos (ex: Cópia de chave simples).

O usuário seleciona o serviço exato que deseja.

#### Etapa 3: Descrição

O usuário fornece detalhes sobre o problema:

- **Local da Manutenção:** Campo de texto (ex: "Banheiro Social").
- **Descrição do Problema:** Área de texto detalhada.
- **Fotos (Opcional):** Área para upload de imagens para ajudar a diagnosticar o problema.

#### Etapa 4: Agendamento

O usuário define sua disponibilidade.

Alertas Importantes são exibidos (Aviso de Condomínio e Aviso de Responsável no Local).

O usuário seleciona uma ou mais datas no calendário e um ou mais períodos de horário (janelas de atendimento).

O sistema informa que esta é uma preferência e que a confirmação final virá por WhatsApp/aplicativo.

#### Etapa 5: Confirmação

A tela final exibe um Resumo da Solicitação com todos os dados coletados (Endereço, Serviço, Descrição, Horários).

Os Avisos Importantes são exibidos novamente.

O usuário deve marcar um Termo de Aceite (checkbox) confirmando que leu os avisos e concorda com as condições.

Ao clicar em "Finalizar", a solicitação é aberta.

### 5. Fluxo de Solicitação Avulsa (Preenchimento Manual)

Este fluxo é acessado por usuários não logados (provavelmente pelo link "Não estou conseguindo acesso").

#### Etapa 1: Dados Pessoais

O usuário informa seus dados de contato básicos: Nome Completo, CPF e WhatsApp.

#### Etapa 2: Endereço

Como o usuário não está logado, ele preenche o endereço manualmente.

- **Tipo de Imóvel:** "Comercial" ou "Residencial".
- **Subtipo do Imóvel (Condicional):** O campo "Casa" ou "Apartamento" só aparece se "Residencial" for selecionado.
- **CEP:** O usuário digita o CEP, e o sistema usa uma API de busca de CEP para preencher automaticamente os campos seguintes.
- **Demais campos:** Rua/Avenida, Número, Complemento, Bairro, Cidade, Estado.

#### Etapa 3: Serviço

O sistema exibe as Categorias de Serviço.

**Regra de Negócio:** A lista de serviços é filtrada dinamicamente com base na seleção (Residencial ou Comercial) da etapa anterior.

O usuário seleciona a Categoria e, em seguida, o Tipo de Serviço.

#### Etapa 4: Fotos e Horários

- **Horários Preferenciais (Obrigatório):** O usuário seleciona as datas e janelas de horário de sua preferência.
- **Fotos (Opcional):** Uma área de upload permite anexar até 5 fotos (máx. 5MB cada).

#### Etapa 5: Confirmação

Um Resumo da Solicitação é exibido (Dados Pessoais, Endereço, Serviço, Horários).

O usuário deve aceitar os "termos e condições de prestação de serviços" (checkbox).

- Ao clicar no link de termos, um modal é aberto com o texto legal (Prestação, Emergências, Privacidade, Responsabilidades).
- **Regra de Negócio:** O telefone de emergência exibido no modal é dinâmico e pode mudar.

Ao clicar em "Enviar Solicitação", o chamado avulso é aberto.

### 6. Fluxo de Recuperação de Senha ("Esqueci a Senha")

Este fluxo é iniciado na Tela de Login (Seção 1).

**Lógica de Redirecionamento (Instância):**

- **Pré-requisito:** O usuário deve primeiro ter selecionado a imobiliária à qual está vinculado (por CEP ou filtro).
- **Redirecionamento Contextual:** Ao clicar em "Esqueci a Senha", o sistema redireciona o usuário para a página de recuperação de senha da própria instância/imobiliária selecionada.
- **White Label:** A página mantém a identidade visual da imobiliária (ex: "TOPX").
- **Interação (API):** O usuário informa seu CPF/CNPJ. A página envia essa informação, junto com o ID da imobiliária, para a API da KSS, que é quem de fato gerencia o envio do link de redefinição e a atualização da senha no banco de dados.

---

## Parte 2: Portal do Operador (Assistência 360°)

### 1. Tela de Login do Operador

Esta é a tela de acesso exclusiva para a equipe interna (operadores, VGT, KSS Seguros), acessada através do endpoint `/operador`.

Diferente do portal do inquilino (que exige seleção de imobiliária), este é um portal administrativo com acesso direto.

A tela apresenta um formulário de login padrão:

- **Título:** Portal do Operador
- **Subtítulo:** Acesso exclusivo para operadores VGT / KSS seguros.
- **Usuário:** Campo para inserir o nome de usuário (login) do operador.
- **Senha:** Campo para inserir a senha de acesso.

O operador insere suas credenciais e clica em "Entrar" para acessar o painel de gerenciamento.

### 2. Painel Principal (Dashboard do Operador)

Após o login bem-sucedido, o operador é direcionado ao Dashboard, que é a tela principal do portal administrativo.

A interface é dividida em três áreas principais:

#### 2.1. Barra de Navegação Lateral (Menu)

À esquerda, um menu fixo permite ao operador navegar por todas as seções do sistema:

- Dashboard (Tela atual)
- Kanban
- Solicitações
- Solicitação Manual
- Imobiliárias
- Categorias
- Templates WhatsApp
- Configurações
- Usuários

#### 2.2. Cabeçalho e Rodapé

- **Cabeçalho:** Exibe o título ("Portal do Operador") e os detalhes do usuário logado (ex: "Administrador do Sistema - Operador VGT"), com um botão "Sair".
- **Rodapé:** Confirma o status de "Login realizado" e saúda o usuário.

#### 2.3. Área de Conteúdo (Dashboard)

A tela principal do dashboard é composta por:

- **Filtro de Data:** Permite ao operador filtrar as estatísticas por um período específico.
- **Cartões de Estatística (Resumo):** Uma visão geral dos chamados (Total de Solicitações, Novas Solicitações, Buscando Prestador, Serviços Agendados).
- **Solicitações Recentes:** Uma lista das últimas solicitações recebidas. Cada item exibe Protocolo, Status, Detalhes, e a Imobiliária de origem.

### 3. Tela: Kanban Board e Lógica de Negócios

Esta é a principal tela de gerenciamento de fluxo, onde os operadores visualizam e movem as solicitações entre diferentes estágios.

#### 3.1. Visão Geral da Interface

A tela apresenta um layout Kanban padrão com colunas que representam os status do atendimento. Os "cards" (solicitações) podem ser arrastados ("drag and drop") entre as colunas.

- **Filtros:** A tela permite filtrar os cards visíveis por `Imobiliária`.
- **Cards:** Cada card exibe um resumo da solicitação (ID, serviço, cliente, endereço, data).

#### 3.2. Estrutura de Colunas e Fluxo

Para simplificar o processo, o Kanban será focado em no máximo quatro colunas principais. Colunas como "Concluído" ou "Cancelado" são removidas da visualização principal, pois um serviço resolvido já saiu do fluxo e um cancelado é eliminado.

##### Coluna 1: Nova Solicitação

- **Entrada:** Novas solicitações entram aqui.
- **Ação:** O operador faz a checagem inicial.
- **Saída:** Após a verificação, o card é movido para "Buscando Prestador".

##### Coluna 2: Buscando Prestador

- **Função:** Esta é a coluna de negociação de agendamento com o parceiro (referido como "Mawdy"). O card **permanece nesta coluna** durante todo o processo de agendamento.
- **Sub-Status (Condições):** Para rastrear o "ping pong" da negociação, serão implementados status internos:
  - "Aguardando confirmação da Mawdy".
  - "Aguardando confirmação do locatário".

**Cenários de Agendamento:**

- **Cenário 1 (Ideal):** O locatário passa uma data. A Mawdy aceita ("acata") e retorna confirmado. O card move para "Serviço Agendado".
- **Cenário 2 (Negociação):** O locatário passa uma data. A Mawdy retorna com *outras datas*. O locatário confirma uma dessas datas. A Mawdy dá o "OK" final (retornando o protocolo). O card move para "Serviço Agendado".
- **Cenário 3 (Loop):** O locatário passa uma data. A Mawdy retorna com outras datas. O locatário *não aceita nenhuma* e retorna com *novas* datas. O ciclo recomeça até um acordo. O card permanece em "Buscando Prestador".

##### Coluna 3: Serviço Agendado

- **Entrada:** O card chega aqui após a confirmação da Mawdy.
- **Regra de Cancelamento:** Uma notificação é disparada. O cliente pode cancelar até um dia antes da visita. Se o fizer, ele "perderá uma assistência" (um crédito de serviço).

**Resultados do Atendimento:**

- **Sucesso:** "Prestador foi, resolveu, concluiu". Ação: Dispara a pesquisa de satisfação (NCP).
- **Necessidade de Peça:** "Prestador foi, precisou comprar peça". Ação: O card move para "Pendências".

##### Coluna 4: Pendências

- **Função:** Gerencia chamados em espera, primariamente "Aguardando peça".
- **Regra (Prazo):** O locatário tem até 10 dias para comprar a peça.

**Fluxo de Notificação:**

- Um lembrete é enviado a cada 2 dias.
- Nos últimos 2 dias, os lembretes são diários, avisando que ele "vai perder o atendimento" se não confirmar a compra.
- A mensagem de lembrete conterá um link de ação.

**Ação do Locatário (Compra da Peça):**

- O locatário clica no link, informa que "comprou a peça" e escolhe 3 novos horários.
- O card **volta para "Buscando Prestador"**. A nova solicitação à Mawdy usará o número de protocolo original, garantindo que não conte como um novo chamado.

#### 3.3. Lógica de Pós-Atendimento (Link de Confirmação)

Após a visita agendada, o cliente receberá um link de confirmação com opções:

1. **Serviço realizado:**
   - **Ação:** Dispara a Pesquisa de Satisfação (NCP).

2. **Comprar peças:**
   - **Ação:** Move o card para **"Pendências"** e inicia o fluxo de 10 dias.

3. **Prestador não compareceu:**
   - **Ação:** Exibe uma mensagem de desculpas e oferece um novo agendamento. O card retorna para "Buscando Prestador".

4. **Precisei me ausentar:**
   - **Ação:** O card retorna para o fluxo de reagendamento ("Buscando Prestador").

5. **Outros**

#### 3.4. Pesquisa de Satisfação (NCP)

- **Gatilho:** Disparada após o cliente confirmar "Serviço realizado".
- **Perguntas (Escala 0-10):**
  1. O que achou do atendimento da imobiliária?
  2. O que achou do app Assistência 360?
  3. O que achou do prestador de serviço?
- **Dados:** Os resultados devem ser enviados para o Dashboard do Operador.

### 4. Tela: Solicitações (Listagem Geral)

Esta tela, acessada pelo item de menu "Solicitações", serve como o repositório central de todos os chamados. Diferente do Dashboard, que mostra apenas os mais recentes, esta tela lista todas as solicitações.

#### 4.1. Ferramentas de Filtragem

No topo da lista, o operador tem ferramentas para localizar chamados específicos:

- **Busca:** Um campo de busca livre para "Buscar por protocolo, tipo ou cliente".
- **Filtro de Status:** Um menu dropdown (exibindo "Todos os status") que permite filtrar a lista por um status específico (ex: "Nova Solicitação", "Em Atendimento", etc.).

#### 4.2. Lista de Solicitações

Abaixo dos filtros, cada solicitação é apresentada em um "card" detalhado, contendo as seguintes informações:

- **Identificação:** O Protocolo (ex: KS1759878) e o Status atual (ex: Nova Solicitação).
- **Cliente:** Nome do cliente (ex: Lucas Ramos de Moraes).
- **Serviço:** Detalhes do serviço (ex: Chaveiro - Abertura de portas e portões) e a data.
- **Tags:** Informações contextuais (ex: Residencial).
- **Endereço:** O local do atendimento (ex: Avenida Costábile Romano, 521).
- **Documento:** CPF do cliente.
- **Descrição:** Um trecho da descrição do problema (ex: Descrição: Ddd).

#### 4.3. Ações Rápidas

Dentro de cada card, o operador tem acesso a ações rápidas para gerenciamento direto da fila:

- **Mudar Status:** Um menu dropdown (ex: Nova Solicitação) que permite ao operador alterar o status do chamado diretamente da lista.
- **Atalho para Detalhes:** O botão "Atribuir Prestador", assim como clicar no protocolo ou no nome do cliente, funciona como um link direto para a tela de "Detalhes da Solicitação" (documentada na Seção 5). É naquela tela de detalhes que o operador pode, de fato, gerenciar a atribuição do prestador e todas as outras ações do chamado.

### 5. Tela: Detalhes da Solicitação

Esta tela é o "coração" da operação, onde o operador analisa, gerencia e atualiza o status de um chamado específico. Ela é acessada ao clicar em qualquer solicitação listada no Dashboard ou na tela "Solicitações".

#### 5.1. Cabeçalho da Solicitação

Resumo rápido com Protocolo, Status/Tag, Descrição Breve, Data e um botão "Copiar Informações".

#### 5.2. Informações do Cliente e Local

- **Informações do Cliente:** Nome, CPF, Imobiliária.
- **Endereço:** Local do serviço.

#### 5.3. Detalhes do Problema

- **Descrição do Problema:** O texto original do cliente.
- **Serviço Personalizado:** Detalhes do serviço (Categoria, Subcategoria, Tipo) e um alerta para serviços personalizados.

#### 5.4. Disponibilidade e Documentos

- **Disponibilidade informada pelo Segurado:** Lista de horários de preferência do cliente.
- **Horários Indisponíveis?:** Checkbox para o operador.
- **Anexar Documentos:** Área de upload para o operador.

#### 5.5. Painel de Gerenciamento (Coluna Direita)

Esta é a área de ação do operador:

- **Observações do Segurado:** Campo de texto para notas internas.
- **Precisa de Reembolso?:** Checkbox.
- **Status da Solicitação:** Menu dropdown (o campo principal) para alterar o estado do chamado (ex: Nova Solicitação, Agendado, etc.).
- **Protocolo da Seguradora:** Campo para código externo.
- **Linha do Tempo (Log):** Histórico de todas as ações e mudanças de status do chamado.

### 6. Tela: Solicitações Manuais (Listagem)

#### 6.3. Ações

Diferente da tela "Solicitações" (Seção 4), esta listagem não possui ações rápidas. A única ação disponível é:

- **"Ver Detalhes":** Um botão que abre um modal específico para gerenciamento de chamados manuais (documentado na Seção 7), permitindo ao operador alterar o status e migrar o chamado para o sistema principal.

### 7. Modal: Detalhes da Solicitação Manual

Ao clicar em "Ver Detalhes" na lista de Solicitações Manuais (Seção 6), o sistema abre este modal. Esta é uma interface dedicada para a triagem e gerenciamento de chamados que entraram manualmente.

O modal é dividido em:

#### 7.1. Resumo dos Dados

- **Dados do Cliente:** Exibe Nome, CPF, WhatsApp e o Endereço preenchido manualmente.
- **Detalhes do Serviço:** Mostra o Tipo de Propriedade (Residencial, CASA), Categoria (Chaveiro), Subcategoria (Cópia de chave simples) e a Data de Criação.
- **Datas Preferidas:** Lista as datas de disponibilidade informadas pelo usuário (ex: 23/10/2025).

#### 7.2. Gerenciar Solicitação (Ações)

Esta é a área de ação principal do operador dentro do modal:

- **Alterar Status:** Um menu dropdown (ex: Nova Solicitação) que permite ao operador atualizar o andamento do chamado.
- **Migrar para Sistema Principal:** Um checkbox. Esta é uma ação-chave. Ao ser marcada, ela provavelmente converte este chamado "manual" (que está em uma lista de espera) em um chamado padrão, inserindo-o no fluxo normal de solicitações (na tela da Seção 4) e no Kanban para atribuição.

#### 7.3. Informações Técnicas

Um rodapé com dados de rastreamento do sistema:

- **ID da Solicitação:** O UUID único do chamado (ex: 7f08...f07d).
- **Termos Aceitos:** Confirmação se o usuário aceitou os termos (ex: Sim).

### 8. Tela: Gerenciar Imobiliárias

Esta tela, acessada pelo item de menu "Imobiliárias", é a área de administração onde os operadores podem cadastrar, visualizar e gerenciar todas as imobiliárias parceiras da plataforma.

#### 8.1. Ações e Filtros

No topo da tela, o operador encontra as ferramentas de gerenciamento:

- **Botão de Ação Principal:** "+ Nova Imobiliária", usado para abrir o formulário de cadastro de uma nova imobiliária parceira.
- **Barra de Busca:** Um campo para "Buscar por nome, razão social ou CNPJ...".
- **Filtros de Status:** Botões de alternância para filtrar a lista por "Todas", "Ativas" ou "Inativas".

#### 8.2. Lista de Imobiliárias

O corpo da tela exibe uma tabela com todas as imobiliárias cadastradas. As colunas são:

- **Nome Fantasia:** O nome comercial da imobiliária (ex: LAGO IMOBILIARIA LTDA, Imobiliária X).
- **Razão Social:** O nome legal da empresa (ex: L. R. DE MORAES ONLINE TECNOLOGIA LTDA).
- **CNPJ:** O identificador fiscal da empresa.
- **Cidade/Estado:** A localização da imobiliária.
- **Status:** Uma etiqueta visual (tag) que indica se a imobiliária está "Ativa" ou "Inativa".
- **Ações:** Botões de ícone para gerenciamento de cada item da lista:
  - **Editar (ícone de lápis):** Abre o formulário para alterar os dados da imobiliária.
  - **Configurar (ícone de varinha/config):** Provavelmente leva a uma tela de configurações específicas daquela imobiliária (como serviços, templates, etc.).
  - **Excluir (ícone de lixeira):** Remove a imobiliária do sistema.

### 9. Modal: Cadastrar Nova Imobiliária

Este modal é aberto quando o operador clica no botão "+ Nova Imobiliária" na tela "Gerenciar Imobiliárias" (Seção 8). Ele contém o formulário completo para registrar uma nova imobiliária parceira no sistema.

O formulário é dividido em quatro seções principais (todos os campos com * são obrigatórios):

#### 9.1. Dados de Identificação

- Nome Fantasia *
- Razão Social *
- CNPJ *
- Logo da Imobiliária: Um campo de upload de arquivo ("Escolher arquivo") com restrições de formato (PNG, JPG, JPEG, WEBP) e tamanho (máximo 2MB).
- CRECI: (Campo opcional para o registro do conselho).

#### 9.2. Endereço

- CEP *
- Endereço *
- Número *
- Complemento (Opcional)
- Bairro *
- Cidade *
- Estado *

**Observação:** O campo CEP provavelmente é usado para preencher automaticamente os outros campos de endereço ao ser digitado.

#### 9.3. Contato

- Telefone
- E-mail

#### 9.4. Integração

Esta é a seção técnica crucial que conecta a imobiliária à API da KSS e outros sistemas:

- **Endpoint:** O URL da API da imobiliária (ex: https://api.exemplo.com).
- **Token:** O token de acesso (chave de API) para autenticação com o endpoint.
- **Instância:** O nome de identificação da instância, usado para roteamento e URLs (ex: lago, ksidemo).
- **KSI ID:** O identificador único desta imobiliária dentro do ecossistema KSS (ex: 42, 155), usado nas URLs da API KSS.

#### 9.5. Ações

Na parte inferior do modal, o operador tem dois botões:

- **Limpar:** Limpa todos os campos do formulário.
- **Cadastrar Imobiliária:** Salva o formulário e registra a nova imobiliária no sistema.

### 10. Tela: Gerenciar Categorias

Esta tela, acessada pelo item de menu "Categorias", é onde o operador define todos os serviços que podem ser solicitados na plataforma. A tela é dividida em duas abas:

- **Categorias:** O agrupamento principal de serviços (ex: Chaveiro, Eletricista).
- **Subcategorias:** Os serviços específicos dentro de cada categoria (ex: Cópia de chave simples).

#### 10.1. Aba: Categorias (Principal)

A tela principal lista as categorias de serviços cadastradas.

- **Ação Principal:** Um botão "+ Nova Categoria" no canto superior direito abre o modal de cadastro.
- **Lista de Categorias:** Uma tabela exibindo as categorias existentes com as seguintes colunas:
  - **Nome:** O nome da categoria (ex: Chaveiro, Desentupimento).
  - **Tipo:** O contexto onde esta categoria aparece (ex: "Comercial", "Residencial"). Esta é a regra de negócio que filtra os serviços nos fluxos do inquilino.
  - **Status:** Uma etiqueta de "Ativo" ou "Inativo".
  - **Ações:** Botões de ícone para:
    - **Editar (ícone de lápis):** Abre um modal similar ao de "Nova Categoria" para editar o item.
    - **Gerenciar Subcategorias (ícone de lista/seta):** Provavelmente redireciona para a aba "Subcategorias" com um filtro aplicado para esta categoria.
    - **Excluir (ícone de lixeira):** Remove a categoria.

#### 10.2. Fluxo: Nova Categoria

Ao clicar em "+ Nova Categoria", um modal é aberto para o cadastro:

- **Nome:** O operador digita o nome da nova categoria (ex: "Elétrica, Hidráulica").
- **Tipo de Propriedade:** O operador seleciona em um dropdown se esta categoria se aplica a imóveis do tipo "Residencial", "Comercial" ou ambos.
- **Ícone:**
  - O operador clica em "Selecione um ícone".
  - Um novo modal (o seletor de ícones) é aberto.
  - O operador localiza (usando a busca ou as abas de filtro) e clica no ícone desejado (ex: "Hidráulica", "Elétrica").
  - O modal de ícones se fecha e o ícone é selecionado no formulário principal.
- **Ações:** O operador clica em "Criar" para salvar a nova categoria ou "Cancelar" para fechar o modal.

### 11. Tela: Templates WhatsApp

Esta tela, acessada pelo item de menu "Templates WhatsApp", é uma área crítica para gerenciar todas as comunicações automáticas enviadas aos clientes via WhatsApp.

#### 11.1. Listagem de Templates

A tela principal exibe uma lista de todos os templates de mensagem configurados no sistema.

- **Ação Principal:** Um botão "+ Novo Template" no canto superior direito abre o modal de criação/edição.
- **Lista de Templates:** Cada "card" na lista representa um modelo de mensagem para um gatilho específico. Os exemplos mostrados incluem:
  - Nova Solicitação - Padrão
  - Horário Confirmado - Padrão
  - Horário Sugerido - Padrão
  - Confirmação de Serviço - Padrão
  - Atualização de Status - Padrão
- **Conteúdo do Template:** O card exibe o texto exato da mensagem, incluindo as variáveis dinâmicas (ex: `{{protocol}}`, `{{cliente_name}}`, `{{imobiliaria_name}}`) que são substituídas pelos dados reais do chamado.
- **Ações por Card:** Cada card possui ícones para:
  - **Editar (ícone de lápis):** Abre o modal de edição (Seção 11.2).
  - **Duplicar (ícone de cópia):** Cria uma cópia deste template.
  - **Excluir (ícone de lixeira):** Remove o template.

#### 11.2. Modal: Novo Template / Editar Template

Ao clicar em "+ Novo Template" ou no ícone "Editar", o modal é aberto, permitindo a configuração detalhada da mensagem.

- **Nome do Template:** Um nome interno para identificar o template (ex: "Novo Template").
- **Tipo de Mensagem:** O gatilho que dispara esta mensagem. É um menu dropdown crucial que define quando esta mensagem será enviada. As opções incluem:
  - Nova Solicitação (o padrão)
  - Atualização de Status
  - Horário Sugerido
  - Horário Confirmado
  - Confirmação de Serviço
- **Corpo da Mensagem:** A área de texto principal onde o operador digita a mensagem.
- **Variáveis Disponíveis:** Uma paleta de botões com todas as variáveis dinâmicas que podem ser usadas (ex: Protocolo, Contrato, Cliente, CPF, Telefone, Link Cancelamento, Prestador, etc.). Clicar em uma variável a insere no "Corpo da Mensagem".
- **Configurações (Toggle):**
  - **Ativo:** Um interruptor (toggle) para ativar ou desativar este template.
  - **Template Padrão:** Um interruptor que provavelmente define este como o template principal a ser usado para aquele "Tipo de Mensagem", caso haja múltiplos.
- **Ações do Modal:**
  - **Cancelar:** Fecha o modal.
  - **Salvar Template:** Salva as alterações.

### 12. Tela: Configurações (Gerenciar Status)

Esta tela, acessada pelo item de menu "Configurações", permite ao operador administrar as etapas e os status que governam o fluxo de vida de uma solicitação. O subtítulo "Configure os status do Kanban" confirma que estes são os status usados na tela "Kanban".

#### 12.1. Listagem de Status

A tela principal exibe a seção "Gerenciar Status", que lista todos os status cadastrados no sistema.

- **Ação Principal:** Um botão "+ Novo Status" no canto superior direito abre o modal de criação.
- **Lista de Status:** A tela exibe uma lista de todos os status, cada um com uma "tag" colorida que o identifica visualmente. Os status de exemplo incluem:
  - Nova Solicitação
  - Buscando Prestador
  - Retorno de Revisão
  - Serviço Agendado
  - Em Agendamento
  - Serviço Concluído
  - Pendente de Retorno
  - Cancelado
  - Prestador Não Compareceu
  - Aguardando Pagar
  - Concluído Automaticamente
- **Ações por Item:** Cada status na lista possui ícones para:
  - **Editar (ícone de lápis):** Abre um modal similar ao de "Novo Status" para editar o nome e a cor.
  - **Excluir (ícone de lixeira):** Remove o status do sistema (esta ação pode ser bloqueada se o status estiver em uso).

#### 12.2. Modal: Criar Novo Status

Ao clicar em "+ Novo Status", o modal é aberto:

- **Nome do Status:** Um campo de texto para o operador definir o nome do novo status (ex: "Em Análise").
- **Cor:** O operador seleciona a cor que representará este status em todo o sistema (tags, kanban, etc.). O clique no campo de cor abre um seletor de cores (color picker) para a escolha exata do tom (RGB, HSL).
- **Ações:** O operador clica em "Criar Status" para salvar ou "Cancelar".

### 13. Tela: Gerenciamento de Usuários

Esta tela, acessada pelo item de menu "Usuários", é onde os administradores do sistema podem criar, editar e gerenciar as contas de acesso de outros membros da equipe (Operadores ou Admins).

#### 13.1. Listagem de Usuários

A tela principal exibe uma tabela com todos os usuários do portal.

- **Ação Principal:** Um botão "+ Novo Usuário" no canto superior direito abre o modal de cadastro.
- **Barra de Busca:** Um campo para "Buscar por nome, email, CPF ou código de acesso...".
- **Lista de Usuários:** A tabela exibe as seguintes colunas:
  - **Código:** Um identificador único ou código de login (ex: vgt, operator1).
  - **Nome:** O nome do usuário (ex: vgtmg, Administrador do Sistema).
  - **E-mail:** O e-mail de contato/login.
  - **Data Cadastro:** A data em que o usuário foi criado.
  - **Nível Acesso:** A tag de permissão (ex: Admin, Operador).
  - **Status:** Um interruptor (toggle) para definir o usuário como "Ativo" ou "Inativo".
  - **Ações:** Botões de ícone para:
    - **Editar (ícone de lápis):** Abre o modal (Seção 13.2) para editar os dados do usuário.
    - **Redefinir Senha (ícone de chave):** Provavelmente dispara um fluxo de redefinição de senha.
    - **Excluir (ícone de lixeira):** Remove o usuário do sistema.

#### 13.2. Modal: Novo Usuário

Ao clicar em "+ Novo Usuário", um modal é aberto para o cadastro de um novo membro da equipe.

O formulário solicita:

- **Dados Pessoais:** Nome Completo\*, CPF\*, E-mail\*.
- **Endereço do Usuário:** Endereço\*, Número\*, Complemento, Bairro\*, Cidade\*, UF\*, CEP\*.
- **Credenciais e Permissões:**
  - **Senha\*:** Campo para definir a senha inicial do usuário.
  - **Nível de Acesso\*:** Um campo de seleção (dropdown ou radio buttons) para definir a permissão do usuário.
- **Regra de Negócio:** Conforme a observação, os únicos níveis disponíveis para cadastro são "Operador" e "Admin".

O administrador preenche os dados e clica em "Salvar" para criar o usuário ou "Cancelar" para fechar o modal.

---

## Resumo Executivo

O Sistema Assistência 360° é uma plataforma robusta de gestão de solicitações de assistência técnica, dividida em dois portais principais:

1. **Portal do Inquilino:** Interface amigável para solicitação de serviços, com suporte a dois fluxos (logado e não logado).
2. **Portal do Operador:** Painel administrativo completo para gerenciamento de solicitações, imobiliárias, categorias, templates e usuários.

A plataforma utiliza uma arquitetura white-label, permitindo customização por imobiliária, e integra-se com sistemas externos via APIs. O fluxo de solicitações é gerenciado através de um Kanban dinâmico com lógica sofisticada de agendamento e acompanhamento pós-atendimento.