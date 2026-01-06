<?php
/**
 * EducaTudo - Definição de Rotas
 * Rotas organizadas por perfil de usuário
 */

// Router é passado pelo App.php

// Rotas públicas (sem autenticação)
$router->get('/', 'AuthController@loginAluno');
$router->get('/admin', 'AuthController@loginAdmin');
$router->get('/professor', 'AuthController@loginProfessor');
$router->get('/pais', 'AuthController@loginPais');

// Rotas de autenticação
$router->post('/login', 'AuthController@autenticar');
$router->get('/logout', 'AuthController@logout');
$router->get('/recuperar-senha', 'AuthController@recuperarSenha');
$router->post('/enviar-recuperacao', 'AuthController@enviarRecuperacao');

// Middleware de autenticação
$router->middleware('Auth', function($router) {
    
    // Rotas para Alunos (perfil: aluno)
    $router->get('/dashboard', 'StudentController@dashboard');
    
    // Alteração obrigatória de senha
    $router->get('/aluno/alterar-senha-obrigatoria', 'StudentController@alterarSenhaObrigatoria');
    $router->post('/aluno/alterar-senha-obrigatoria', 'StudentController@processarAlteracaoSenha');
    
    // Chat Tudinha
    $router->get('/chat', 'StudentController@chat');
    $router->post('/chat/conversa', 'StudentController@createConversation');
    $router->post('/chat/mensagem', 'StudentController@sendMessage');
    $router->post('/chat/upload-imagem', 'StudentController@uploadImage');
    $router->get('/chat/mensagens', 'StudentController@getMessages');
    $router->get('/chat/conversa-info', 'StudentController@getConversationInfo');
    $router->post('/chat/conversa/excluir', 'StudentController@deleteConversation');
    
    // Jogos
    $router->get('/jogos', 'JogoController@index');
    
    // Jogo do Milhão
    $router->get('/jogo-milhao', 'JogoMilhaoController@index');
    $router->get('/jogo-milhao/jogar', 'JogoMilhaoController@jogar');
    $router->post('/jogo-milhao/iniciar', 'JogoMilhaoController@iniciarPartida');
    $router->post('/jogo-milhao/continuar', 'JogoMilhaoController@continuarPartida');
    $router->post('/jogo-milhao/responder', 'JogoMilhaoController@responderPergunta');
    $router->post('/jogo-milhao/ajuda', 'JogoMilhaoController@usarAjuda');
    $router->post('/jogo-milhao/abandonar', 'JogoMilhaoController@abandonar');
    $router->post('/jogo-milhao/heartbeat', 'JogoMilhaoController@heartbeat');
    $router->post('/jogo-milhao/verificar-partida', 'JogoMilhaoController@verificarPartida');
    $router->post('/jogo-milhao/limpar-orfas', 'JogoMilhaoController@limparOrfas');
    
// Avatar
$router->get('/avatar', 'AvatarController@index');
$router->post('/avatar/salvar', 'AvatarController@salvar');
$router->get('/avatar/gerar', 'AvatarController@gerarAvatar');
$router->get('/avatar/svg', 'AvatarController@gerarSvg');

// Exercícios
$router->get('/exercicios', 'ExerciciosController@index');
$router->get('/exercicios/iniciar', 'ExerciciosController@iniciar');
$router->post('/exercicios/responder', 'ExerciciosController@responder');
$router->get('/exercicios/finalizar', 'ExerciciosController@finalizar');
$router->post('/exercicios/finalizar', 'ExerciciosController@finalizar');
$router->get('/exercicios/resultado', 'ExerciciosController@resultado');
$router->get('/exercicios/historico', 'ExerciciosController@historico');
    
    // Redações
    $router->get('/redacoes', 'StudentController@essays');
    $router->get('/redacoes/escrever', 'StudentController@escreverRedacao');
    $router->get('/redacoes/escrever-livre', 'StudentController@escreverRedacaoLivre');
    $router->get('/redacoes/transcrever', 'StudentController@transcreverImagemPage');
    $router->get('/redacoes/historico', 'StudentController@historicoRedacoes');
    $router->get('/redacoes/{id}', 'StudentController@verRedacao');
    $router->post('/redacoes/criar', 'StudentController@createEssay');
    $router->post('/redacoes/gerar-tema', 'StudentController@gerarTemaIA');
    $router->post('/redacoes/transcrever-imagem', 'StudentController@transcreverImagem');
    $router->post('/redacoes/corrigir', 'StudentController@corrigirRedacao');
    
    // Sistema de Jornadas do Aluno
    $router->get('/jornadas', 'JornadaController@index');
    $router->get('/jornadas/{id}', 'JornadaController@show');
    $router->get('/jornadas/{jornada_id}/aula/{aula_id}', 'JornadaController@aula');
    $router->post('/jornadas/salvar-resumo', 'JornadaController@salvarResumo');
    $router->post('/jornadas/enviar-duvida', 'JornadaController@enviarDuvida');
    $router->post('/jornadas/concluir-aula', 'JornadaController@concluirAula');
    
    // Simulados ENEM
    $router->get('/simulados', 'SimuladoController@index');
    $router->get('/simulados/criar', 'SimuladoController@criar');
    $router->post('/simulados/criar', 'SimuladoController@criarSimulado');
    $router->get('/simulados/iniciar', 'SimuladoController@iniciar');
    $router->post('/simulados/responder', 'SimuladoController@responder');
    $router->post('/simulados/finalizar', 'SimuladoController@finalizar');
    $router->get('/simulados/resultado', 'SimuladoController@resultado');
    
    $router->get('/jogos', 'JogoController@index');
    $router->get('/jogos/xadrez', 'JogoController@xadrez');
    $router->get('/jogos/damas', 'JogoController@damas');
    $router->get('/jogos/milhao', 'JogoController@milhao');
    
    $router->get('/relatorios', 'RelatorioController@index');
    $router->get('/relatorios/desempenho', 'RelatorioController@desempenho');
    $router->get('/relatorios/jornada', 'RelatorioController@jornada');
    $router->get('/relatorios/redacao', 'RelatorioController@redacao');
    
    // Rotas para Professores (perfil: professor)
    $router->get('/professor/dashboard', 'ProfessorController@dashboard');
    $router->get('/professor/student', 'ProfessorController@student');
    $router->get('/professor/student/{id}', 'ProfessorController@viewStudent');
    $router->post('/professor/student/{id}/password', 'ProfessorController@updateStudentPassword');
    // Redirecionamento da rota antiga para a nova
    $router->get('/professor/student-journey', function() {
        header('Location: ' . URL . '/professor/jornadas');
        exit;
    });
    
    // Sistema de Jornadas do Professor
    $router->get('/professor/jornadas', 'ProfessorJornadaController@index');
    $router->get('/professor/jornadas/criar', 'ProfessorJornadaController@criar');
    $router->post('/professor/jornadas', 'ProfessorJornadaController@salvar');
    $router->get('/professor/jornadas/{id}', 'ProfessorJornadaController@show');
    $router->get('/professor/jornadas/{id}/editar', 'ProfessorJornadaController@editar');
    $router->post('/professor/jornadas/{id}/atualizar', 'ProfessorJornadaController@atualizar');
    $router->get('/professor/jornadas/{id}/alunos', 'ProfessorJornadaController@verAlunos');
    $router->post('/professor/jornadas/adicionar-aula', 'ProfessorJornadaController@adicionarAula');
    $router->post('/professor/jornadas/toggle-status', 'ProfessorJornadaController@toggleStatus');
    
    // Exercícios das Jornadas
    $router->get('/professor/jornadas/{id}/exercicios', 'ProfessorJornadaController@exercicios');
    $router->get('/professor/jornadas/{id}/exercicios/criar', 'ProfessorJornadaController@exercicioForm');
    $router->get('/professor/jornadas/{id}/exercicios/ia', 'ProfessorJornadaController@exercicioIAForm');
    $router->get('/professor/jornadas/{jornada_id}/exercicios/resultado/{exercicio_id}', 'ProfessorJornadaController@exercicioResultado');
    $router->get('/professor/jornadas/{id}/exercicios/{exercicio_id}/editar', 'ProfessorJornadaController@exercicioForm');
    $router->post('/professor/jornadas/criar-exercicio', 'ProfessorJornadaController@criarExercicio');
    $router->post('/professor/jornadas/gerar-exercicio-ia', 'ProfessorJornadaController@gerarExercicioIA');
    $router->post('/professor/jornadas/aprovar-exercicio-ia', 'ProfessorJornadaController@aprovarExercicioIA');
    $router->post('/professor/jornadas/publicar-exercicio', 'ProfessorJornadaController@publicarExercicio');
    
    // Blocos de Conteúdo das Jornadas
    $router->get('/professor/jornadas/{id}/blocos', 'BlocosConteudoController@index');
    $router->post('/professor/jornadas/{id}/blocos/adicionar', 'BlocosConteudoController@adicionarBloco');
    $router->post('/professor/jornadas/{id}/blocos/atualizar-ordem', 'BlocosConteudoController@atualizarOrdem');
    $router->post('/professor/jornadas/{id}/blocos/{bloco_id}/editar', 'BlocosConteudoController@editarBloco');
    $router->post('/professor/jornadas/{id}/blocos/{bloco_id}/remover', 'BlocosConteudoController@removerBloco');
    $router->get('/professor/jornadas/exercicios/{id}', 'ProfessorJornadaController@buscarExercicio');
    
    // Análise de Resumos
    $router->get('/professor/jornadas/{id}/analise-resumos', 'ProfessorJornadaController@analiseResumos');
    $router->get('/professor/jornadas/resumos/{id}', 'ProfessorJornadaController@buscarResumo');
    $router->post('/professor/jornadas/gerar-explicacao-complementar', 'ProfessorJornadaController@gerarExplicacaoComplementar');
    
    // Dúvidas e Comunicação
    $router->post('/professor/jornadas/responder-duvida', 'ProfessorJornadaController@responderDuvida');
    
    // Teste OpenAI
    $router->get('/professor/testar-openai', 'ProfessorJornadaController@testarOpenAI');
    
    // Rotas antigas mantidas para compatibilidade
    $router->get('/professor/alunos', 'ProfessorController@alunos');
    $router->get('/professor/jornadas-aluno', 'ProfessorController@jornadasAluno');
    
    $router->get('/professor/exercicios', 'ProfessorController@exercicios');
    $router->get('/professor/exercicios/criar', 'ProfessorController@criarExercicio');
    $router->post('/professor/exercicios', 'ProfessorController@salvarExercicio');
    $router->get('/professor/exercicios/{id}/aprovar', 'ProfessorController@aprovarExercicio');
    
    $router->get('/professor/turmas', 'ProfessorController@turmas');
    $router->get('/professor/turmas/{id}', 'ProfessorController@turmaDetalhes');
    $router->get('/professor/turmas/{id}/alunos', 'ProfessorController@alunosTurma');
    
    $router->get('/professor/relatorios', 'ProfessorController@relatorios');
    $router->get('/professor/relatorios/turma/{id}', 'ProfessorController@relatorioTurma');
    $router->get('/professor/relatorios/aluno/{id}', 'ProfessorController@relatorioAluno');
    
    // Rotas para Administração (perfil: admin_escola)
    $router->get('/admin/dashboard', 'AdminController@dashboard');
    
    // Gestão de Alunos
    $router->get('/admin/students', 'AdminController@alunos');
    $router->get('/admin/students/create', 'AdminController@criarAluno');
    $router->post('/admin/students', 'AdminController@salvarAluno');
    $router->post('/admin/students/upload-excel', 'AdminController@uploadExcelAlunos');
    $router->get('/admin/students/{id}', 'AdminController@mostrarAluno');
    $router->get('/admin/students/{id}/edit', 'AdminController@editarAluno');
    $router->put('/admin/students/{id}', 'AdminController@atualizarAluno');
    $router->delete('/admin/students/{id}', 'AdminController@excluirAluno');
    $router->post('/admin/students/{id}/toggle-status', 'AdminController@toggleStatusAluno');
    
    // Gestão de Professores
    $router->get('/admin/teachers', 'AdminController@professores');
    $router->get('/admin/teachers/create', 'AdminController@criarProfessor');
    $router->post('/admin/teachers', 'AdminController@salvarProfessor');
    $router->get('/admin/teachers/{id}/edit', 'AdminController@editarProfessor');
    $router->put('/admin/teachers/{id}', 'AdminController@atualizarProfessor');
    $router->delete('/admin/teachers/{id}', 'AdminController@excluirProfessor');
    $router->post('/admin/teachers/{id}/toggle-status', 'AdminController@toggleStatusProfessor');
    
    // Sistema de Jornadas do Admin
    $router->get('/admin/jornadas', 'AdminJornadaController@index');
    $router->get('/admin/jornadas/{id}', 'AdminJornadaController@show');
    $router->get('/admin/jornadas/professor/{professor_id}', 'AdminJornadaController@porProfessor');
    $router->get('/admin/jornadas/turma/{turma_id}', 'AdminJornadaController@porTurma');
    $router->get('/admin/jornadas/relatorio', 'AdminJornadaController@relatorio');
    $router->post('/admin/jornadas/toggle-status', 'AdminJornadaController@toggleStatus');
    
    // Gestão de Jornadas (antiga)
    $router->get('/admin/journeys', 'AdminController@jornadas');
    $router->get('/admin/journeys/create', function() {
        header('Location: ' . URL . '/admin/jornadas');
        exit;
    });
    // Compatibilidade: redireciona URLs antigas em inglês para as novas em português
    $router->get('/admin/journeys/{id}', function($id) {
        header('Location: ' . URL . '/admin/jornadas/' . $id);
        exit;
    });
    $router->get('/admin/journeys/{id}/edit', function($id) {
        header('Location: ' . URL . '/admin/jornadas/' . $id);
        exit;
    });
    
    // Gestão de Exercícios
    $router->get('/admin/exercises', 'AdminController@exercicios');
    $router->get('/admin/exercises/create', 'AdminController@criarExercicio');
    $router->post('/admin/exercises', 'AdminController@salvarExercicio');
    $router->get('/admin/exercises/{id}/edit', 'AdminController@editarExercicio');
    $router->put('/admin/exercises/{id}', 'AdminController@atualizarExercicio');
    $router->delete('/admin/exercises/{id}', 'AdminController@excluirExercicio');
    $router->post('/admin/exercises/import', 'AdminController@importarExercicios');
    $router->get('/admin/exercises/export', 'AdminController@exportarExercicios');
    $router->get('/admin/exercises/{id}/export', 'AdminController@exportarExercicios');
    
    // Gerenciamento de Questões
    $router->get('/admin/exercises/{id}/questions', 'AdminController@gerenciarQuestoes');
    $router->get('/admin/exercises/{id}/questions/create', 'AdminController@adicionarQuestao');
    $router->post('/admin/exercises/{id}/questions', 'AdminController@salvarQuestao');
    $router->get('/admin/exercises/{listaId}/questions/{questaoId}/edit', 'AdminController@editarQuestao');
    $router->put('/admin/exercises/{listaId}/questions/{questaoId}', 'AdminController@atualizarQuestao');
    $router->delete('/admin/exercises/{listaId}/questions/{questaoId}', 'AdminController@excluirQuestao');
    $router->post('/admin/exercises/{id}/questions/reorder', 'AdminController@reordenarQuestoes');
    
    // Configurações
    $router->get('/admin/settings', 'AdminController@configuracoes');
    $router->put('/admin/settings', 'AdminController@salvarConfiguracoes');
    
    // Configurações Avançadas (Dev)
    $router->get('/admin/dev', 'AdminController@dev');
    $router->post('/admin/dev/modules', 'AdminController@salvarModulos');
    
    // Layout do Sistema (Dev Settings)
    $router->get('/admin/dev/layout', 'AdminController@layout');
    $router->post('/admin/dev/layout/save', 'AdminController@saveLayout');
    $router->post('/admin/dev/layout/upload', 'AdminController@uploadImage');
    
    // CRUD de Usuários
    $router->get('/admin/usuarios', 'UsuarioController@index');
    $router->get('/admin/usuarios/create', 'UsuarioController@create');
    $router->post('/admin/usuarios', 'UsuarioController@store');
    $router->get('/admin/usuarios/{id}/edit', 'UsuarioController@edit');
    $router->post('/admin/usuarios/{id}', 'UsuarioController@update');
    $router->post('/admin/usuarios/{id}/avatar', 'UsuarioController@uploadAvatar');
    $router->post('/admin/usuarios/{id}/senha', 'UsuarioController@changePassword');
    
    // Webhooks (Dev Settings)
    $router->get('/admin/dev/webhooks', 'AdminWebhookController@index');
    $router->post('/admin/dev/webhooks', 'AdminWebhookController@create');
    $router->put('/admin/dev/webhooks/{id}', 'AdminWebhookController@update');
    $router->delete('/admin/dev/webhooks/{id}', 'AdminWebhookController@delete');
    $router->post('/admin/dev/webhooks/{id}/test', 'AdminWebhookController@test');
    
    // Gestão de Matérias
    $router->get('/admin/subjects', 'AdminController@materias');
    $router->get('/admin/subjects/create', 'AdminController@criarMateria');
    $router->post('/admin/subjects', 'AdminController@salvarMateria');
    $router->get('/admin/subjects/{id}/edit', 'AdminController@editarMateria');
    $router->put('/admin/subjects/{id}', 'AdminController@atualizarMateria');
    $router->delete('/admin/subjects/{id}', 'AdminController@excluirMateria');
    
    // Gestão de Turmas (CRUD Completo)
    $router->get('/admin/turmas', 'TurmaController@index');
    $router->get('/admin/turmas/create', 'TurmaController@create');
    $router->post('/admin/turmas', 'TurmaController@store');
    $router->get('/admin/turmas/{id}', 'TurmaController@show');
    $router->get('/admin/turmas/{id}/edit', 'TurmaController@edit');
    $router->post('/admin/turmas/{id}', 'TurmaController@update');
    $router->delete('/admin/turmas/{id}', 'TurmaController@destroy');
    $router->post('/admin/turmas/{id}/toggle-status', 'TurmaController@toggleStatus');
    $router->get('/admin/turmas/by-ano-letivo', 'TurmaController@getByAnoLetivo');
    $router->get('/admin/turmas/by-serie', 'TurmaController@getBySerie');
    
    // Relatórios Administrativos
    $router->get('/admin/reports', 'AdminController@relatorios');
    $router->get('/admin/reports/school', 'AdminController@relatorioEscola');
    $router->get('/admin/reports/classes', 'AdminController@relatorioTurmas');
    $router->get('/admin/reports/teachers', 'AdminController@relatorioProfessores');
    
    // Rotas para Pais (perfil: pai)
    $router->get('/pais/dashboard', 'PaisController@dashboard');
    $router->get('/pais/filhos', 'PaisController@filhos');
    $router->get('/pais/filhos/{id}', 'PaisController@filhoDetalhes');
    $router->get('/pais/filhos/{id}/desempenho', 'PaisController@desempenhoFilho');
    $router->get('/pais/filhos/{id}/jornadas', 'PaisController@jornadasFilho');
    $router->get('/pais/filhos/{id}/redacoes', 'PaisController@redacoesFilho');
    $router->get('/pais/filhos/{id}/relatorios', 'PaisController@relatoriosFilho');
    
    // Comunicação
    $router->get('/pais/mensagens', 'PaisController@mensagens');
    $router->post('/pais/mensagens', 'PaisController@enviarMensagem');
    $router->get('/pais/notificacoes', 'PaisController@notificacoes');
    
    // ==============================================
    // SISTEMA DE NOTIFICAÇÕES
    // ==============================================
    
    // Rotas para Admin - Gerenciar Notificações
    $router->get('/admin/notifications', 'NotificacaoController@index');
    $router->get('/admin/notifications/create', 'NotificacaoController@create');
    $router->post('/admin/notifications/store', 'NotificacaoController@store');
    $router->get('/admin/notifications/{id}', 'NotificacaoController@show');
    $router->get('/admin/notifications/{id}/edit', 'NotificacaoController@edit');
    $router->post('/admin/notifications/{id}/update', 'NotificacaoController@update');
    $router->get('/admin/notifications/{id}/delete', 'NotificacaoController@delete');
    
    // API para Admin - Notificações
    $router->get('/admin/notifications/api/nao-lidas', 'NotificacaoController@apiNaoLidas');
    $router->post('/admin/notifications/api/marcar-lida', 'NotificacaoController@apiMarcarLida');
    
    // Rotas para Professor - Notificações para Turma
    $router->get('/professor/notifications', 'ProfessorNotificacaoController@index');
    $router->get('/professor/notifications/create', 'ProfessorNotificacaoController@create');
    $router->post('/professor/notifications/store', 'ProfessorNotificacaoController@store');
    $router->get('/professor/notifications/{id}', 'ProfessorNotificacaoController@show');
    $router->get('/professor/notifications/{id}/delete', 'ProfessorNotificacaoController@delete');
    
    // Rotas para Todos os Usuários - Visualizar Notificações
    $router->get('/notifications', 'NotificacaoUsuarioController@index');
    $router->get('/notifications/atualizar', 'NotificacaoUsuarioController@atualizar');
    $router->get('/notifications/{id}', 'NotificacaoUsuarioController@show');
    $router->get('/notifications/{id}/marcar-lida', 'NotificacaoUsuarioController@marcarLida');
    
    // API para Todos os Usuários - Notificações
    $router->get('/notifications/api/nao-lidas', 'NotificacaoUsuarioController@apiNaoLidas');
    $router->post('/notifications/api/marcar-lida', 'NotificacaoUsuarioController@apiMarcarLida');
    $router->get('/notifications/api/recentes', 'NotificacaoUsuarioController@apiRecentes');
    $router->post('/notifications/api/marcar-todas-lidas', 'NotificacaoUsuarioController@apiMarcarTodasLidas');
});