
# 🧾 Documentação Técnica — KSS Seguros

## 1. Visão Geral do Sistema

O **KSS Seguros** é um sistema web (PWA + Painel Administrativo) desenvolvido para digitalizar e automatizar o processo de **solicitação de assistências residenciais**.  
Seu objetivo é eliminar o atendimento manual via telefone, centralizar informações e melhorar a experiência do locatário, operadores KSI e prestadores de serviço.

### 🎯 Objetivos
- Reduzir o tempo médio de abertura de chamados em até 70%.
- Automatizar notificações via WhatsApp.
- Melhorar a comunicação entre locatário, imobiliária e prestador.
- Centralizar todos os dados e históricos de atendimentos.

---

## 2. Arquitetura Técnica

### 🧱 Stack
- **Linguagem:** PHP 8.2+
- **Padrão:** MVC (Model–View–Controller)
- **Paradigma:** Orientado a Objetos (POO)
- **Gerenciador:** Composer (PSR-4 Autoload)
- **Front-end:** TailwindCSS 3.x
- **Banco de Dados:** MySQL / MariaDB
- **URLs Amigáveis:** via `.htaccess` e roteador interno
- **Autenticação:** Sessões PHP + middleware
- **Implantação:** Apache 2.x com mod_rewrite ativo

---

## 3. Estrutura de Pastas

```

/app
├── /Controllers
│    ├── SolicitacoesController.php
│    ├── ImobiliariasController.php
│    ├── UsuariosController.php
│    └── ...
├── /Models
│    ├── Solicitacao.php
│    ├── Imobiliaria.php
│    ├── Usuario.php
│    ├── Categoria.php
│    ├── Status.php
│    └── ...
├── /Views
│    ├── /layouts
│    │    └── admin.php
│    ├── /solicitacoes
│    ├── /usuarios
│    ├── /imobiliarias
│    └── ...
├── /Core
│    ├── Router.php
│    ├── Controller.php
│    ├── Database.php
│    └── View.php
├── /Config
│    ├── config.php
│    ├── database.php
│    └── routes.php
└── /Public
├── /assets
│    ├── css/
│    ├── js/
│    └── images/
├── index.php
└── .htaccess

````

---

## 4. Banco de Dados e Modelos

O modelo de dados segue o schema definido em **Prisma**, adaptado para o uso com PDO no PHP.

### 🗂️ Entidades Principais

| Tabela | Descrição |
|--------|------------|
| **imobiliarias** | Cadastro de imobiliárias e suas configurações de integração KSI |
| **usuarios** | Operadores e administradores do sistema |
| **categorias / subcategorias** | Tipos de assistências disponíveis |
| **solicitacoes** | Chamados criados pelos locatários |
| **status** | Estados do fluxo (Kanban) |
| **fotos** | Uploads de imagens das solicitações |
| **notificacoes** | Histórico de mensagens via Evolution API |
| **cache_api_ksi** | Armazenamento temporário de respostas de API |
| **logs_api_ksi** | Registro de chamadas à API KSI |
| **configuracoes_sistema** | Parametrizações gerais |

### 🔢 Enums Importantes

- **NivelPermissao:** `ADMINISTRADOR`, `OPERADOR`  
- **TipoAssistencia:** `RESIDENCIAL`, `COMERCIAL`  
- **TipoNotificacao:** `WHATSAPP`, `SMS`, `EMAIL`, `PUSH`  
- **TipoConfig:** `STRING`, `NUMBER`, `BOOLEAN`, `JSON`

---

## 5. Fluxo Principal da Solicitação

```mermaid
flowchart TD
    A[Locatário abre solicitação] --> B[Envio à API KSI para validação]
    B --> C[Operador visualiza no Kanban]
    C --> D[Status: "Nova Solicitação"]
    D --> E[Buscando Prestador (Mawdy)]
    E --> F[Prestador confirmado]
    F --> G[Serviço Agendado]
    G --> H[Atendimento executado]
    H --> I{Peça necessária?}
    I -->|Sim| J[Pendência: aguardando peça]
    I -->|Não| K[Concluído - NCP gerado]
    J --> L[Locatário informa compra da peça]
    L --> E
    K --> M[Fim do fluxo]
````

---

## 6. Regras de Negócio

### 🗓️ Agendamento

* O locatário escolhe até **3 opções de datas e horários**.
* O sistema bloqueia **sábados e domingos**.
* Aplica o **prazo mínimo** de cada subcategoria.
* Valida **horário comercial (8h às 18h)**.

### 🚨 Emergências

* Fora do horário comercial → redirecionamento para **0800**.
* Emergência sinalizada altera automaticamente o fluxo e notifica o operador.

### 🧾 Cancelamentos

* Permitido até **1 dia antes** do agendamento.
* Cancelamentos fora do prazo descontam uma assistência do usuário.

### 🧩 Peças e Pendências

* Caso o serviço exija peça, gera status “**Aguardando Peça**”.
* Locatário recebe lembretes automáticos a cada **2 dias**.
* Se não houver retorno em **10 dias**, o chamado é encerrado.

---

## 7. Notificações Automatizadas

| Evento            | Canal    | Frequência    | Ação                                  |
| ----------------- | -------- | ------------- | ------------------------------------- |
| Nova solicitação  | WhatsApp | Imediato      | Confirmação de recebimento            |
| Status alterado   | WhatsApp | Imediato      | Atualização de progresso              |
| Aguardando peça   | WhatsApp | A cada 2 dias | Lembrete para o locatário             |
| Serviço concluído | WhatsApp | Imediato      | Envio de NCP e pesquisa de satisfação |

---

## 8. Módulos do Sistema

### 📱 PWA do Locatário

* Login via **API KSI Imobiliária**.
* Seleção de imóvel e subcategoria.
* Upload de até 3 fotos.
* Escolha de datas de atendimento.
* Recebimento de notificações via WhatsApp.

### 🧑‍💻 Painel do Operador / Admin

* Visualização em **Kanban** de todas as solicitações.
* Edição de status por **drag & drop**.
* Filtros por **imobiliária, categoria e data**.
* Visualização detalhada (dados do cliente, fotos, endereço).
* Registro automático no **histórico de status**.

### 🏢 Gerenciamento de Imobiliárias

* Cadastro com **URL Base**, **Token**, **Instância** e parâmetros da API KSI.
* Controle de **ativação** e tempo de cache.

### ⚙️ Gerenciamento de Categorias / Subcategorias

* Nome, tipo de assistência, prazo mínimo, status 0800 e observações.
* Aplicação automática de regras de agendamento.

### 🧍‍♂️ Gerenciamento de Usuários

* Cadastro de operadores e administradores.
* Campos: nome, email, telefone, nível de permissão, status.
* Controle de acesso via sessão e middleware.

### 🪪 Gerenciamento de Status

* Criação e ordenação das colunas do Kanban.
* Configuração de cor, ícone e template de mensagem.
* Controle de notificações automáticas.

---

## 9. Integrações

### 🔗 API KSI Imobiliária

* Autenticação via token.
* Endpoints:

  * `CLIENTES_AUTENTICACOES`
  * `IMO_CTR_LOCATARIOS`
* Uso de cache e logs para reduzir carga.
* Retry automático em falhas de rede.

### 💬 Evolution API (WhatsApp)

* Envio de mensagens automáticas baseadas em eventos do sistema.
* Templates dinâmicos com placeholders (`[NOME]`, `[STATUS]`, `[LINK]`).

### ☁️ Uploads

* Armazenamento em **Cloudinary** ou **AWS S3**.
* Nome e URL gravados na tabela `fotos`.

---

## 10. Métricas e Indicadores

### 📊 Métricas de Usuário

* Quantidade de solicitações por período.
* Tempo médio de resolução.
* Taxa de satisfação pós-atendimento.

### ⚙️ Métricas Operacionais

* Eficiência do operador.
* Volume por imobiliária.
* Picos de demanda por horário/dia.

---

## 11. Cronograma de Desenvolvimento

| Fase             | Duração   | Entregas                                                 |
| ---------------- | --------- | -------------------------------------------------------- |
| **Fase 1 (MVP)** | 8 semanas | Autenticação KSI, Kanban básico, WhatsApp inicial        |
| **Fase 2**       | 4 semanas | Upload de fotos, agendamento avançado, painel completo   |
| **Fase 3**       | 3 semanas | PWA completo, notificações push, analytics e otimizações |

---

## 12. Diagramas Adicionais

### 📊 Fluxo do Operador / Admin

```mermaid
flowchart LR
    A[Nova Solicitação] --> B[Verificação de dados]
    B --> C[Atualizar Status: Buscando Prestador]
    C --> D[Prestador encontrado]
    D --> E[Confirmar Agendamento]
    E --> F[Serviço Agendado]
    F --> G[Atendimento realizado]
    G --> H[Concluído ou Pendência]
    H --> I[Fim]
```

---

## 13. Segurança e Permissões

* Autenticação por sessão PHP + middleware de verificação.
* Criptografia de senhas (bcrypt).
* Logs de acesso e tentativas de login.
* Controle de acesso por nível de permissão:

  * **Administrador:** total
  * **Operador:** somente solicitações e status

---

## 14. Padrões de Código

* PSR-4 (autoload e namespaces)
* PSR-12 (estilo e indentação)
* Classes iniciam com letra maiúscula (`SolicitacaoController`, `UsuarioModel`)
* Métodos usam camelCase (`getSolicitacoes`, `salvarUsuario`)
* Arquitetura separada em:

  * `/app/Core` → motor do framework
  * `/app/Models` → lógica de dados
  * `/app/Controllers` → controle de rotas
  * `/app/Views` → layout com TailwindCSS

---

## 15. Critérios de Aceite

* Locatário consegue abrir uma solicitação completa sem auxílio humano.
* Operador visualiza e gerencia o fluxo no Kanban.
* Notificações automáticas funcionam com Evolution API.
* PWA acessível e responsivo em mobile e desktop.
* Integração com imobiliárias estável e com cache ativo.

---

📄 **Versão:** 1.1
📅 **Atualizado em:** 24/10/2025
👤 **Responsável Técnico:** Lucas Moraes (CTO Launs)

```

---

Quer que eu gere esse conteúdo como **arquivo `.md` pronto para download** (com o nome `documentacao_tecnica.md`) agora?
```
