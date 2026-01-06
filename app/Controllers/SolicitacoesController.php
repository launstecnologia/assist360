<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Solicitacao;
use App\Models\Status;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Imobiliaria;

class SolicitacoesController extends Controller
{
    private Solicitacao $solicitacaoModel;
    private Status $statusModel;
    private Categoria $categoriaModel;
    private Subcategoria $subcategoriaModel;
    private Imobiliaria $imobiliariaModel;

    public function __construct()
    {
        // Não exigir autenticação para rotas de cron
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/cron/') === false) {
            // Verificar se é uma requisição AJAX/API antes de redirecionar
            $isAjax = $this->isAjax() || 
                     (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                     strpos($requestUri, '/api') !== false ||
                     strpos($requestUri, '/buscar/api') !== false;
            
            if (!$this->isAuthenticated()) {
                if (!$isAjax) {
                    // Apenas redirecionar se não for AJAX
                    $this->redirect(url('login'));
                }
                // Se for AJAX, deixar o método específico tratar
            }
        }
        
        $this->solicitacaoModel = new Solicitacao();
        $this->statusModel = new Status();
        $this->categoriaModel = new Categoria();
        $this->subcategoriaModel = new Subcategoria();
        $this->imobiliariaModel = new Imobiliaria();
    }

    public function index(): void
    {
        $filtros = [
            'imobiliaria_id' => $this->input('imobiliaria_id'),
            'status_id' => $this->input('status_id'),
            'categoria_id' => $this->input('categoria_id'),
            'data_inicio' => $this->input('data_inicio'),
            'data_fim' => $this->input('data_fim')
        ];

        // Remover filtros vazios
        $filtros = array_filter($filtros, fn($value) => !empty($value));

        $solicitacoes = $this->solicitacaoModel->getKanbanData();
        
        // Aplicar filtros
        if (!empty($filtros)) {
            $solicitacoes = array_filter($solicitacoes, function($solicitacao) use ($filtros) {
                foreach ($filtros as $campo => $valor) {
                    if ($campo === 'data_inicio') {
                        if ($solicitacao['created_at'] < $valor) return false;
                    } elseif ($campo === 'data_fim') {
                        if ($solicitacao['created_at'] > $valor) return false;
                    } else {
                        if ($solicitacao[$campo] != $valor) return false;
                    }
                }
                return true;
            });
        }

        $status = $this->statusModel->getKanban();
        $categorias = $this->categoriaModel->getAtivas();
        $imobiliarias = $this->imobiliariaModel->getAtivas();

        $this->view('solicitacoes.index', [
            'solicitacoes' => $solicitacoes,
            'status' => $status,
            'categorias' => $categorias,
            'imobiliarias' => $imobiliarias,
            'filtros' => $filtros
        ]);
    }

    public function alterarDataHora(): void
    {
        $status = $this->statusModel->getAtivos();
        $imobiliarias = $this->imobiliariaModel->getAtivas();

        $this->view('solicitacoes.alterar-data-hora', [
            'status' => $status,
            'imobiliarias' => $imobiliarias
        ]);
    }

    public function buscarApi(): void
    {
        // Limpar qualquer output anterior
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Verificar autenticação manualmente para retornar JSON em caso de erro
        if (!$this->isAuthenticated()) {
            $this->json([
                'success' => false,
                'error' => 'Não autenticado'
            ], 401);
            return;
        }
        
        try {
            // Buscar ID do status "Serviço Agendado"
            $sqlStatus = "SELECT id FROM status WHERE nome = 'Serviço Agendado' AND status = 'ATIVO' LIMIT 1";
            $statusObj = \App\Core\Database::fetch($sqlStatus);
            
            if (!$statusObj || empty($statusObj['id'])) {
                $this->json([
                    'success' => false,
                    'error' => 'Status "Serviço Agendado" não encontrado no banco de dados'
                ], 500);
                return;
            }
            
            $statusAgendado = (int)$statusObj['id'];
            
            // Para requisições GET, usar $_GET diretamente
            $filtros = [
                'numero_solicitacao' => $_GET['numero_solicitacao'] ?? null,
                'numero_contrato' => $_GET['numero_contrato'] ?? null,
                'locatario_nome' => $_GET['locatario_nome'] ?? null,
                'imobiliaria_id' => $_GET['imobiliaria_id'] ?? null,
                'data_inicio' => $_GET['data_inicio'] ?? null,
                'data_fim' => $_GET['data_fim'] ?? null,
                'agendamento_inicio' => $_GET['agendamento_inicio'] ?? null,
                'agendamento_fim' => $_GET['agendamento_fim'] ?? null
            ];

            // Remover filtros vazios
            $filtros = array_filter($filtros, fn($value) => !empty($value));

            // ✅ Buscar apenas solicitações com status "Serviço Agendado"
            // Usar CONCAT para gerar número de solicitação caso a coluna não exista
            $sql = "
                SELECT 
                    s.id,
                    CONCAT('KSS', s.id) as numero_solicitacao,
                    s.numero_contrato,
                    s.data_agendamento,
                    s.horario_agendamento,
                    s.created_at,
                    l.nome as locatario_nome
                FROM solicitacoes s
                LEFT JOIN locatarios l ON s.locatario_id = l.id
                WHERE s.status_id = ?
            ";

            $params = [$statusAgendado];

            if (!empty($filtros['numero_solicitacao'])) {
                $sql .= " AND CONCAT('KSS', s.id) LIKE ?";
                $search = '%' . $filtros['numero_solicitacao'] . '%';
                $params[] = $search;
            }

            if (!empty($filtros['numero_contrato'])) {
                $sql .= " AND s.numero_contrato LIKE ?";
                $params[] = '%' . $filtros['numero_contrato'] . '%';
            }

            if (!empty($filtros['locatario_nome'])) {
                $sql .= " AND l.nome LIKE ?";
                $params[] = '%' . $filtros['locatario_nome'] . '%';
            }

            if (!empty($filtros['imobiliaria_id'])) {
                $sql .= " AND s.imobiliaria_id = ?";
                $params[] = $filtros['imobiliaria_id'];
            }

            if (!empty($filtros['data_inicio'])) {
                $sql .= " AND DATE(s.created_at) >= ?";
                $params[] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $sql .= " AND DATE(s.created_at) <= ?";
                $params[] = $filtros['data_fim'];
            }

            if (!empty($filtros['agendamento_inicio'])) {
                $sql .= " AND DATE(s.data_agendamento) >= ?";
                $params[] = $filtros['agendamento_inicio'];
            }

            if (!empty($filtros['agendamento_fim'])) {
                $sql .= " AND DATE(s.data_agendamento) <= ?";
                $params[] = $filtros['agendamento_fim'];
            }

            $sql .= " ORDER BY s.data_agendamento ASC, s.created_at DESC LIMIT 500";

            try {
                $solicitacoes = \App\Core\Database::fetchAll($sql, $params);
            } catch (\PDOException $pdoError) {
                error_log('Erro PDO em buscarApi: ' . $pdoError->getMessage());
                error_log('SQL: ' . $sql);
                error_log('Params: ' . json_encode($params));
                $this->json([
                    'success' => false,
                    'error' => 'Erro no banco de dados: ' . $pdoError->getMessage()
                ], 500);
                return;
            } catch (\Exception $dbError) {
                error_log('Erro genérico em buscarApi: ' . $dbError->getMessage());
                $this->json([
                    'success' => false,
                    'error' => 'Erro ao buscar solicitações: ' . $dbError->getMessage()
                ], 500);
                return;
            }

            $this->json([
                'success' => true,
                'solicitacoes' => $solicitacoes
            ]);
        } catch (\Exception $e) {
            error_log('Erro em buscarApi: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            error_log('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->json([
                'success' => false,
                'error' => 'Erro ao buscar solicitações: ' . $e->getMessage()
            ], 500);
        }
    }

    public function atualizarDataHoraBulk(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['ids']) || !is_array($data['ids'])) {
            $this->json(['success' => false, 'error' => 'IDs não fornecidos'], 400);
            return;
        }

        if (empty($data['data_inicio']) || empty($data['horario_inicio'])) {
            $this->json(['success' => false, 'error' => 'Data e horário de início são obrigatórios'], 400);
            return;
        }

        $ids = $data['ids'];
        $dataInicio = $data['data_inicio'];
        $horarioInicio = $data['horario_inicio'];
        $dataFim = $data['data_fim'] ?? null;
        $horarioFim = $data['horario_fim'] ?? null;

        // Formatar data para exibição (dd/mm/yyyy)
        $dataFormatada = date('d/m/Y', strtotime($dataInicio));

        // Formatar horários (remover segundos se houver)
        $horarioInicioFormatado = substr($horarioInicio, 0, 5);
        $horarioFimFormatado = $horarioFim ? substr($horarioFim, 0, 5) : null;

        // Formatar horário completo no formato "08:00 às 11:00" para horario_agendamento
        $horarioAgendamento = $horarioInicioFormatado;
        if ($horarioFimFormatado) {
            $horarioAgendamento = $horarioInicioFormatado . ' às ' . $horarioFimFormatado;
        }

        // Formatar horario_confirmado_raw no formato "dd/mm/yyyy - HH:MM-HH:MM" (com hífen)
        $horarioConfirmadoRaw = $dataFormatada . ' - ' . $horarioInicioFormatado;
        if ($horarioFimFormatado) {
            $horarioConfirmadoRaw .= '-' . $horarioFimFormatado;
        }

        $atualizados = 0;
        $erros = [];

        foreach ($ids as $id) {
            try {
                // Buscar solicitação atual para preservar confirmed_schedules se existir
                $solicitacaoAtual = $this->solicitacaoModel->find($id);
                
                $updateData = [
                    'data_agendamento' => $dataInicio,
                    'horario_agendamento' => $horarioAgendamento,
                    'horario_confirmado_raw' => $horarioConfirmadoRaw,
                    'horario_confirmado' => 1
                ];

                if ($dataFim) {
                    // Se tem data fim, usar ela, senão usar data início
                    $updateData['data_fim'] = $dataFim;
                }

                // Atualizar confirmed_schedules se existir
                $timeValue = $horarioFimFormatado ? ($horarioInicioFormatado . '-' . $horarioFimFormatado) : $horarioInicioFormatado;
                
                if (!empty($solicitacaoAtual['confirmed_schedules'])) {
                    $confirmedSchedules = json_decode($solicitacaoAtual['confirmed_schedules'], true);
                    if (is_array($confirmedSchedules) && !empty($confirmedSchedules)) {
                        // Atualizar o último horário confirmado
                        $lastIndex = count($confirmedSchedules) - 1;
                        $confirmedSchedules[$lastIndex] = [
                            'date' => $dataInicio,
                            'time' => $timeValue,
                            'raw' => $horarioConfirmadoRaw,
                            'source' => $confirmedSchedules[$lastIndex]['source'] ?? 'operator',
                            'confirmed_at' => date('c')
                        ];
                        $updateData['confirmed_schedules'] = json_encode($confirmedSchedules);
                    } else {
                        // Se não existe ou está vazio, criar novo
                        $updateData['confirmed_schedules'] = json_encode([[
                            'date' => $dataInicio,
                            'time' => $timeValue,
                            'raw' => $horarioConfirmadoRaw,
                            'source' => 'operator',
                            'confirmed_at' => date('c')
                        ]]);
                    }
                } else {
                    // Criar novo confirmed_schedules
                    $updateData['confirmed_schedules'] = json_encode([[
                        'date' => $dataInicio,
                        'time' => $timeValue,
                        'raw' => $horarioConfirmadoRaw,
                        'source' => 'operator',
                        'confirmed_at' => date('c')
                    ]]);
                }

                $this->solicitacaoModel->update($id, $updateData);
                $atualizados++;
            } catch (\Exception $e) {
                $erros[] = "Solicitação #{$id}: " . $e->getMessage();
                error_log("Erro ao atualizar solicitação #{$id}: " . $e->getMessage());
            }
        }

        $this->json([
            'success' => true,
            'message' => "{$atualizados} solicitação(ões) atualizada(s) com sucesso",
            'atualizados' => $atualizados,
            'erros' => $erros
        ]);
    }

    public function show(int $id): void
    {
        $solicitacao = $this->solicitacaoModel->getDetalhes($id);
        
        if (!$solicitacao) {
            $this->view('errors.404');
            return;
        }

        // Parsear e validar confirmed_schedules
        $solicitacao['confirmed_schedules'] = $this->parsearConfirmedSchedules($solicitacao['confirmed_schedules'] ?? null);
        
        // Parsear datas_opcoes e horarios_opcoes
        $solicitacao['datas_opcoes'] = $this->parsearJsonField($solicitacao['datas_opcoes'] ?? null);
        $solicitacao['horarios_opcoes'] = $this->parsearJsonField($solicitacao['horarios_opcoes'] ?? null);

        // Buscar fotos (se tabela existir)
        try {
            $fotos = $this->solicitacaoModel->getFotos($id);
        } catch (\Exception $e) {
            $fotos = [];
        }
        
        // Buscar histórico
        try {
            $historico = $this->solicitacaoModel->getHistoricoStatus($id);
        } catch (\Exception $e) {
            $historico = [];
        }
        
        $statusDisponiveis = $this->statusModel->getAtivos();

        $this->view('solicitacoes.show', [
            'solicitacao' => $solicitacao,
            'fotos' => $fotos,
            'historico' => $historico,
            'statusDisponiveis' => $statusDisponiveis
        ]);
    }

    public function api(int $id): void
    {
        $solicitacao = $this->solicitacaoModel->getDetalhes($id);
        
        if (!$solicitacao) {
            $this->json([
                'success' => false,
                'message' => 'Solicitação não encontrada'
            ], 404);
            return;
        }

        // Parsear e validar confirmed_schedules
        $solicitacao['confirmed_schedules'] = $this->parsearConfirmedSchedules($solicitacao['confirmed_schedules'] ?? null);
        
        // Parsear datas_opcoes e horarios_opcoes
        $solicitacao['datas_opcoes'] = $this->parsearJsonField($solicitacao['datas_opcoes'] ?? null);
        $solicitacao['horarios_opcoes'] = $this->parsearJsonField($solicitacao['horarios_opcoes'] ?? null);

        // Buscar fotos da solicitação
        $fotos = $this->solicitacaoModel->getFotos($id);
        $solicitacao['fotos'] = $fotos;
        
        // Buscar histórico de WhatsApp
        $whatsappHistorico = $this->getWhatsAppHistorico($id);
        $solicitacao['whatsapp_historico'] = $whatsappHistorico;
        
        // Buscar histórico de status (linha do tempo)
        try {
            $historicoStatus = $this->solicitacaoModel->getHistoricoStatus($id);
        } catch (\Exception $e) {
            $historicoStatus = [];
        }
        $solicitacao['historico_status'] = $historicoStatus;
        
        // Buscar links de ações (tokens gerados)
        $linksAcoes = $this->getLinksAcoes($id, $solicitacao);
        $solicitacao['links_acoes'] = $linksAcoes;
        
        $this->json([
            'success' => true,
            'solicitacao' => $solicitacao
        ]);
    }
    
    /**
     * Parseia e valida confirmed_schedules
     * 
     * @param mixed $confirmedSchedules Valor do campo confirmed_schedules
     * @return array|null Array validado ou null
     */
    private function parsearConfirmedSchedules($confirmedSchedules): ?array
    {
        if (empty($confirmedSchedules)) {
            return null;
        }
        
        // Se já é array, validar estrutura
        if (is_array($confirmedSchedules)) {
            $validated = [];
            foreach ($confirmedSchedules as $schedule) {
                if (is_array($schedule) && !empty($schedule['raw'])) {
                    $validated[] = $schedule;
                }
            }
            return !empty($validated) ? $validated : null;
        }
        
        // Se é string, tentar parsear JSON
        if (is_string($confirmedSchedules)) {
            $parsed = json_decode($confirmedSchedules, true);
        
            // Verificar se houve erro no parse
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Erro ao parsear confirmed_schedules: " . json_last_error_msg());
                return null;
            }
            
            // Validar estrutura
            if (is_array($parsed)) {
                $validated = [];
                foreach ($parsed as $schedule) {
                    if (is_array($schedule) && !empty($schedule['raw'])) {
                        $validated[] = $schedule;
                    }
                }
                return !empty($validated) ? $validated : null;
            }
        }
        
        return null;
    }
    
    /**
     * Parseia um campo JSON (string para array)
     * 
     * @param mixed $field Valor do campo
     * @return array|null Array parseado ou null
     */
    private function parsearJsonField($field): ?array
    {
        if (empty($field)) {
            return null;
        }
        
        // Se já é array, retornar
        if (is_array($field)) {
            return $field;
        }
        
        // Se é string, tentar parsear JSON
        if (is_string($field)) {
            $parsed = json_decode($field, true);
            
            // Verificar se houve erro no parse
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            
            // Retornar array se válido
            if (is_array($parsed)) {
                return $parsed;
        }
        }
        
        return null;
    }

    /**
     * Reenvia mensagem WhatsApp
     */
    public function reenviarWhatsapp(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }

        try {
            // Ler dados do JSON
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            
            $tipo = $json['tipo'] ?? $this->input('tipo');
            $extraData = $json['extra_data'] ?? $this->input('extra_data', []);
            
            if (empty($tipo)) {
                $this->json(['success' => false, 'message' => 'Tipo de mensagem é obrigatório'], 400);
                return;
            }
            
            // Verificar se a solicitação existe
            $solicitacao = $this->solicitacaoModel->find($id);
            if (!$solicitacao) {
                $this->json(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
                return;
            }
            
            // Reenviar mensagem
            $this->enviarNotificacaoWhatsApp($id, $tipo, $extraData);
            
            $this->json([
                'success' => true,
                'message' => 'Mensagem reenviada com sucesso'
            ]);
            
        } catch (\Exception $e) {
            error_log('Erro ao reenviar WhatsApp: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Erro ao reenviar mensagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorna contagem de solicitações do mesmo contrato e categoria nos últimos 12 meses
     * GET /admin/solicitacoes/historico-utilizacao?numero_contrato=XXX&categoria_id=YYY
     */
    public function historicoUtilizacao(): void
    {
        $this->requireAuth();
        
        $numeroContrato = $this->input('numero_contrato', '');
        $categoriaId = $this->input('categoria_id');
        
        if (empty($numeroContrato)) {
            $this->json([
                'success' => false,
                'message' => 'Número do contrato é obrigatório'
            ], 400);
            return;
        }
        
        try {
            // Calcular data de 12 meses atrás
            $dataInicio = date('Y-m-d', strtotime('-12 months'));
            $dataFim = date('Y-m-d');
            
            // Buscar contagem de solicitações do mesmo contrato e categoria nos últimos 12 meses
            $sql = "
                SELECT COUNT(*) as total
                FROM solicitacoes
                WHERE numero_contrato = ?
                AND DATE(created_at) >= ?
                AND DATE(created_at) <= ?
            ";
            
            $params = [$numeroContrato, $dataInicio, $dataFim];
            
            // Adicionar filtro por categoria se fornecido
            if (!empty($categoriaId)) {
                $sql .= " AND categoria_id = ?";
                $params[] = $categoriaId;
            }
            
            $resultado = \App\Core\Database::fetch($sql, $params);
            
            $total = (int) ($resultado['total'] ?? 0);
            
            $this->json([
                'success' => true,
                'total' => $total,
                'periodo' => '12 meses',
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim
            ]);
            
        } catch (\Exception $e) {
            error_log('Erro ao buscar histórico de utilização: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Erro ao buscar histórico de utilização'
            ], 500);
        }
    }

    public function edit(int $id): void
    {
        $solicitacao = $this->solicitacaoModel->getDetalhes($id);
        
        if (!$solicitacao) {
            $this->view('errors.404');
            return;
        }

        $categorias = $this->categoriaModel->getAtivas();
        $subcategorias = $this->subcategoriaModel->getByCategoria($solicitacao['categoria_id']);
        $status = $this->statusModel->getAtivos();
        $imobiliarias = $this->imobiliariaModel->getAtivas();

        $this->view('solicitacoes.edit', [
            'solicitacao' => $solicitacao,
            'categorias' => $categorias,
            'subcategorias' => $subcategorias,
            'status' => $status,
            'imobiliarias' => $imobiliarias
        ]);
    }

    public function update(int $id): void
    {
        if (!$this->isPost()) {
            $this->redirect("/solicitacoes/$id/edit");
        }

        $data = [
            'categoria_id' => $this->input('categoria_id'),
            'subcategoria_id' => $this->input('subcategoria_id'),
            'status_id' => $this->input('status_id'),
            'locatario_nome' => $this->input('locatario_nome'),
            'locatario_telefone' => $this->input('locatario_telefone'),
            'locatario_email' => $this->input('locatario_email'),
            'imovel_endereco' => $this->input('imovel_endereco'),
            'imovel_numero' => $this->input('imovel_numero'),
            'imovel_complemento' => $this->input('imovel_complemento'),
            'imovel_bairro' => $this->input('imovel_bairro'),
            'imovel_cidade' => $this->input('imovel_cidade'),
            'imovel_estado' => $this->input('imovel_estado'),
            'imovel_cep' => $this->input('imovel_cep'),
            'descricao_problema' => $this->input('descricao_problema'),
            'observacoes' => $this->input('observacoes'),
            'prioridade' => $this->input('prioridade'),
            'data_agendamento' => $this->input('data_agendamento'),
            'horario_agendamento' => $this->input('horario_agendamento'),
            'prestador_nome' => $this->input('prestador_nome'),
            'prestador_telefone' => $this->input('prestador_telefone'),
            'valor_orcamento' => $this->input('valor_orcamento'),
            'numero_ncp' => $this->input('numero_ncp'),
            'avaliacao_satisfacao' => $this->input('avaliacao_satisfacao')
        ];

        // ✅ REMOVIDO: Não adicionar disponibilidade na descrição do problema
        // A descrição deve permanecer como o usuário escreveu, sem modificações automáticas

        $errors = $this->validate([
            'categoria_id' => 'required',
            'subcategoria_id' => 'required',
            'status_id' => 'required',
            'locatario_nome' => 'required|min:3',
            'locatario_telefone' => 'required',
            'imovel_endereco' => 'required|min:5'
        ], $data);

        if (!empty($errors)) {
            $solicitacao = $this->solicitacaoModel->getDetalhes($id);
            $categorias = $this->categoriaModel->getAtivas();
            $subcategorias = $this->subcategoriaModel->getByCategoria($data['categoria_id']);
            $status = $this->statusModel->getAtivos();
            $imobiliarias = $this->imobiliariaModel->getAtivas();

            $this->view('solicitacoes.edit', [
                'solicitacao' => $solicitacao,
                'categorias' => $categorias,
                'subcategorias' => $subcategorias,
                'status' => $status,
                'imobiliarias' => $imobiliarias,
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        try {
            $this->solicitacaoModel->update($id, $data);
            $this->redirect("/solicitacoes/$id");
        } catch (\Exception $e) {
            $this->view('solicitacoes.edit', [
                'error' => 'Erro ao atualizar solicitação: ' . $e->getMessage(),
                'solicitacao' => $this->solicitacaoModel->getDetalhes($id)
            ]);
        }
    }

    public function updateStatus(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $statusId = $this->input('status_id');
        $observacoes = $this->input('observacoes');
        $user = $this->getUser();

        if (!$statusId) {
            $this->json(['error' => 'Status é obrigatório'], 400);
            return;
        }

        try {
            // Buscar a solicitação atual
            $solicitacao = $this->solicitacaoModel->find($id);
            if (!$solicitacao) {
                $this->json(['error' => 'Solicitação não encontrada'], 404);
                return;
            }
            
            // Buscar status atual e novo status
            $sqlAtual = "SELECT nome FROM status WHERE id = ?";
            $statusAtualObj = \App\Core\Database::fetch($sqlAtual, [$solicitacao['status_id']]);
            $statusAtual = $statusAtualObj['nome'] ?? null;
            
            $sqlNovo = "SELECT nome FROM status WHERE id = ?";
            $statusNovoObj = \App\Core\Database::fetch($sqlNovo, [$statusId]);
            $statusNovo = $statusNovoObj['nome'] ?? null;
            
            // Validação: Se está em "Buscando Prestador" e tentando mudar para "Serviço Agendado"
            if ($statusAtual === 'Buscando Prestador' && $statusNovo === 'Serviço Agendado') {
                // Verificar se há horário confirmado
                $temHorarioConfirmado = false;
                
                // Verificar horario_confirmado_raw
                if (!empty($solicitacao['horario_confirmado_raw']) && trim($solicitacao['horario_confirmado_raw'])) {
                    $temHorarioConfirmado = true;
                }
                
                // Verificar confirmed_schedules
                if (!$temHorarioConfirmado && !empty($solicitacao['confirmed_schedules'])) {
                    $confirmed = json_decode($solicitacao['confirmed_schedules'], true);
                    if (is_array($confirmed) && count($confirmed) > 0) {
                        $temHorarioConfirmado = true;
                    }
                }
                
                // Verificar data_agendamento e horario_agendamento
                if (!$temHorarioConfirmado && !empty($solicitacao['data_agendamento']) && !empty($solicitacao['horario_agendamento'])) {
                    $temHorarioConfirmado = true;
                }
                
                if (!$temHorarioConfirmado) {
                    $this->json([
                        'error' => 'É necessário ter um horário confirmado para mudar de "Buscando Prestador" para "Serviço Agendado"',
                        'requires_schedule' => true
                    ], 400);
                    return;
                }
            }
            
            $success = $this->solicitacaoModel->updateStatus($id, $statusId, $user['id'], $observacoes);
            
            if ($success) {
                // Buscar nome do status
                $sql = "SELECT nome FROM status WHERE id = ?";
                $status = \App\Core\Database::fetch($sql, [$statusId]);
                $statusNome = $status['nome'] ?? 'Atualizado';
                
                // ✅ Se mudou para "Serviço Agendado", atualizar condição para "Agendamento Confirmado"
                if ($statusNovo === 'Serviço Agendado') {
                    $condicaoModel = new \App\Models\Condicao();
                    $condicaoConfirmada = $condicaoModel->findByNome('Agendamento Confirmado');
                    if (!$condicaoConfirmada) {
                        $condicaoConfirmada = $condicaoModel->findByNome('Data Aceita pelo Prestador');
                    }
                    if (!$condicaoConfirmada) {
                        $sqlCondicao = "SELECT * FROM condicoes WHERE (nome LIKE '%Agendamento Confirmado%' OR nome LIKE '%Data Aceita pelo Prestador%') AND status = 'ATIVO' LIMIT 1";
                        $condicaoConfirmada = \App\Core\Database::fetch($sqlCondicao);
                    }
                    
                    if ($condicaoConfirmada) {
                        $this->solicitacaoModel->update($id, ['condicao_id' => $condicaoConfirmada['id']]);
                        error_log("DEBUG updateStatus [ID:{$id}] - ✅ Condição alterada para 'Agendamento Confirmado' (ID: {$condicaoConfirmada['id']})");
                    } else {
                        error_log("DEBUG updateStatus [ID:{$id}] - ⚠️ Condição 'Agendamento Confirmado' não encontrada no banco de dados");
                    }
                }
                
                // ✅ Se mudou para "Concluído", atualizar contagem de CPF
                if ($statusNovo === 'Concluído') {
                    try {
                        // Buscar CPF do locatário da solicitação
                        $cpfLocatario = $solicitacao['locatario_cpf'] ?? null;
                        $imobiliariaId = $solicitacao['imobiliaria_id'] ?? null;
                        $categoriaId = $solicitacao['categoria_id'] ?? null;
                        
                        if ($cpfLocatario && $imobiliariaId) {
                            $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
                            $solicitacaoManualModel->atualizarContagemCPF(
                                preg_replace('/[^0-9]/', '', $cpfLocatario),
                                $imobiliariaId,
                                $categoriaId
                            );
                            error_log("DEBUG updateStatus [ID:{$id}] - ✅ Contagem de CPF atualizada para: {$cpfLocatario}");
                        }
                    } catch (\Exception $e) {
                        error_log("DEBUG updateStatus [ID:{$id}] - ⚠️ Erro ao atualizar contagem de CPF: " . $e->getMessage());
                    }
                }
                
                // ✅ Se mudou de "Buscando Prestador" para "Serviço Agendado", enviar "Horário Confirmado"
                if ($statusAtual === 'Buscando Prestador' && $statusNovo === 'Serviço Agendado') {
                    // Buscar dados de agendamento da solicitação atualizada
                    $solicitacaoAtualizada = $this->solicitacaoModel->find($id);
                    $dataAgendamento = $solicitacaoAtualizada['data_agendamento'] ?? null;
                    $horarioAgendamento = $solicitacaoAtualizada['horario_agendamento'] ?? null;
                    
                    // Formatar horário completo
                    $horarioCompleto = '';
                    if ($dataAgendamento && $horarioAgendamento) {
                        $dataFormatada = date('d/m/Y', strtotime($dataAgendamento));
                        $horarioCompleto = $dataFormatada . ' - ' . $horarioAgendamento;
                    }
                    
                    // Enviar apenas "Horário Confirmado"
                    $this->enviarNotificacaoWhatsApp($id, 'Horário Confirmado', [
                        'data_agendamento' => $dataAgendamento ? date('d/m/Y', strtotime($dataAgendamento)) : '',
                        'horario_agendamento' => $horarioAgendamento ?? '',
                        'horario_servico' => $horarioCompleto
                    ]);
                } else {
                    // Para outras mudanças de status, enviar "Atualização de Status"
                    // ✅ Não enviar WhatsApp quando mudar para "Buscando Prestador"
                    if ($statusNome !== 'Buscando Prestador') {
                        $this->enviarNotificacaoWhatsApp($id, 'Atualização de Status', [
                            'status_atual' => $statusNome
                        ]);
                    }
                }
                
                $this->json(['success' => true, 'message' => 'Status atualizado com sucesso']);
            } else {
                $this->json(['error' => 'Erro ao atualizar status'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao atualizar status: ' . $e->getMessage()], 500);
        }
    }

    public function getSubcategorias(): void
    {
        $categoriaId = $this->input('categoria_id');
        
        if (!$categoriaId) {
            $this->json(['error' => 'Categoria é obrigatória'], 400);
            return;
        }

        $subcategorias = $this->subcategoriaModel->getByCategoria($categoriaId);
        $this->json($subcategorias);
    }

    public function getHorariosDisponiveis(): void
    {
        $subcategoriaId = $this->input('subcategoria_id');
        $data = $this->input('data');
        
        if (!$subcategoriaId || !$data) {
            $this->json(['error' => 'Subcategoria e data são obrigatórios'], 400);
            return;
        }

        $horarios = $this->subcategoriaModel->getHorariosDisponiveis($subcategoriaId, $data);
        $this->json($horarios);
    }

    // Métodos para o fluxo operacional
    public function criarSolicitacao(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $data = [
            'imobiliaria_id' => $this->input('imobiliaria_id'),
            'categoria_id' => $this->input('categoria_id'),
            'subcategoria_id' => $this->input('subcategoria_id'),
            'locatario_id' => $this->input('locatario_id'),
            'locatario_nome' => $this->input('locatario_nome'),
            'locatario_telefone' => $this->input('locatario_telefone'),
            'locatario_email' => $this->input('locatario_email'),
            'imovel_endereco' => $this->input('imovel_endereco'),
            'imovel_numero' => $this->input('imovel_numero'),
            'imovel_complemento' => $this->input('imovel_complemento'),
            'imovel_bairro' => $this->input('imovel_bairro'),
            'imovel_cidade' => $this->input('imovel_cidade'),
            'imovel_estado' => $this->input('imovel_estado'),
            'imovel_cep' => $this->input('imovel_cep'),
            'descricao_problema' => $this->input('descricao_problema'),
            'tipo_atendimento' => $this->input('tipo_atendimento', 'RESIDENCIAL'),
            'datas_opcoes' => json_decode($this->input('datas_opcoes', '[]'), true),
            'prioridade' => $this->input('prioridade', 'NORMAL')
        ];

        // Validar campos obrigatórios
        $errors = $this->validate([
            'imobiliaria_id' => 'required',
            'categoria_id' => 'required',
            'subcategoria_id' => 'required',
            'locatario_nome' => 'required|min:3',
            'locatario_telefone' => 'required|min:10',
            'imovel_endereco' => 'required|min:5',
            'imovel_numero' => 'required',
            'imovel_bairro' => 'required',
            'imovel_cidade' => 'required',
            'imovel_estado' => 'required|min:2',
            'imovel_cep' => 'required|min:8',
            'descricao_problema' => 'required|min:10',
            'datas_opcoes' => 'required'
        ], $data);

        if (!empty($errors)) {
            $this->json(['error' => 'Dados inválidos', 'details' => $errors], 400);
            return;
        }

        // Validar datas
        $datasErrors = $this->solicitacaoModel->validarDatasOpcoes($data['datas_opcoes']);
        if (!empty($datasErrors)) {
            $this->json(['error' => 'Datas inválidas', 'details' => $datasErrors], 400);
            return;
        }

        // Verificar limite de solicitações da categoria (se houver número de contrato)
        $tipoQualificacao = 'CORTESIA'; // Default
        if (!empty($data['numero_contrato'])) {
            $categoriaModel = new \App\Models\Categoria();
            $verificacaoLimite = $categoriaModel->verificarLimiteSolicitacoes($data['categoria_id'], $data['numero_contrato']);
            
            if (!$verificacaoLimite['permitido']) {
                // Se excedeu o limite, marcar como não qualificada mas ainda permitir criar
                // A solicitação será criada normalmente como "Nova Solicitação" mas marcada como não qualificada
                $tipoQualificacao = 'NAO_QUALIFICADA';
                // Adicionar marcador nas observações indicando que foi por quantidade
                if (isset($data['observacoes'])) {
                    $data['observacoes'] .= "\n⚡ EXCEDEU_LIMITE_QUANTIDADE: Limite de solicitações da categoria excedido (Total: {$verificacaoLimite['total_atual']}/{$verificacaoLimite['limite']})";
                } else {
                    $data['observacoes'] = "⚡ EXCEDEU_LIMITE_QUANTIDADE: Limite de solicitações da categoria excedido (Total: {$verificacaoLimite['total_atual']}/{$verificacaoLimite['limite']})";
                }
                // Não bloquear mais, apenas marcar como não qualificada
            }
        }

        try {
            // Validar máximo de 3 horários
            $horariosOpcoes = $data['datas_opcoes'] ?? [];
            if (count($horariosOpcoes) > 3) {
                $this->json(['error' => 'Máximo de 3 horários permitidos'], 400);
                return;
            }
            
            if (empty($horariosOpcoes)) {
                $this->json(['error' => 'É necessário selecionar pelo menos 1 horário'], 400);
                return;
            }
            
            // Converter datas_opcoes para horarios_opcoes (formato esperado)
            $horariosFormatados = [];
            foreach ($horariosOpcoes as $dataOpcao) {
                if (is_string($dataOpcao) && preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s*-\s*(\d{2}:\d{2})-(\d{2}:\d{2})/', $dataOpcao, $matches)) {
                    $horariosFormatados[] = $dataOpcao;
                } else {
                    // Tentar converter formato ISO para formato esperado
                    try {
                        $dt = new \DateTime($dataOpcao);
                        $horariosFormatados[] = $dt->format('d/m/Y') . ' - 08:00-11:00'; // Formato padrão
                    } catch (\Exception $e) {
                        // Ignorar data inválida
                    }
                }
            }
            
            // Salvar horários formatados em ambos os campos para preservar dados do locatário
            $data['horarios_opcoes'] = json_encode($horariosFormatados);
            $data['datas_opcoes'] = json_encode($horariosFormatados); // ✅ Preservar também em datas_opcoes
            
            // Adicionar tipo_qualificacao apenas se a coluna existir
            if ($this->solicitacaoModel->colunaExisteBanco('tipo_qualificacao')) {
                // Tipo de Qualificação: CORTESIA (dentro do limite) ou NAO_QUALIFICADA (excedeu limite)
                // A solicitação será criada normalmente como "Nova Solicitação" no kanban, mas marcada como não qualificada se excedeu o limite
                $data['tipo_qualificacao'] = $tipoQualificacao;
            }
            
            // Gerar número da solicitação
            $data['numero_solicitacao'] = $this->solicitacaoModel->gerarNumeroSolicitacao();
            
            // Gerar token de confirmação
            $data['token_confirmacao'] = $this->solicitacaoModel->gerarTokenConfirmacao();
            
            // Definir condição inicial: "Aguardando Resposta do Prestador"
            $condicaoModel = new \App\Models\Condicao();
            $condicaoAguardando = $condicaoModel->findByNome('Aguardando Resposta do Prestador');
            if ($condicaoAguardando) {
                $data['condicao_id'] = $condicaoAguardando['id'];
            }
            
            // Definir status inicial: "Nova Solicitação" ou "Buscando Prestador"
            $statusNova = $this->getStatusId('Nova Solicitação');
            if (!$statusNova) {
                $statusNova = $this->getStatusId('Buscando Prestador');
            }
            if ($statusNova) {
                $data['status_id'] = $statusNova;
            }
            
            // Definir data limite para cancelamento (1 dia antes da primeira data)
            if (!empty($horariosFormatados)) {
                $primeiraDataStr = $horariosFormatados[0];
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $primeiraDataStr, $dateMatches)) {
                    $primeiraData = new \DateTime($dateMatches[3] . '-' . $dateMatches[2] . '-' . $dateMatches[1]);
                    $data['data_limite_cancelamento'] = $primeiraData->modify('-1 day')->format('Y-m-d');
                }
            }
            
            // Criar solicitação
            $solicitacaoId = $this->solicitacaoModel->create($data);
            
            // Enviar notificação WhatsApp
            // Verificar se é não qualificada para enviar mensagem específica
            if (!empty($data['tipo_qualificacao']) && $data['tipo_qualificacao'] === 'NAO_QUALIFICADA') {
                $observacao = $data['observacao_qualificacao'] ?? 'Não se enquadra nos critérios estabelecidos.';
                $this->enviarNotificacaoWhatsApp($solicitacaoId, 'Não Qualificado', [
                    'observacao' => $observacao
                ]);
            } else {
                $this->enviarNotificacaoWhatsApp($solicitacaoId, 'Nova Solicitação');
            }
            
            $this->json([
                'success' => true,
                'solicitacao_id' => $solicitacaoId,
                'numero_solicitacao' => $data['numero_solicitacao'],
                'message' => 'Solicitação criada com sucesso'
            ]);
            
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao criar solicitação: ' . $e->getMessage()], 500);
        }
    }

    public function confirmarDatas(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $solicitacaoId = $this->input('solicitacao_id');
        $dataConfirmada = $this->input('data_confirmada');
        $mawdyData = [
            'mawdy_id' => $this->input('mawdy_id'),
            'mawdy_nome' => $this->input('mawdy_nome'),
            'mawdy_telefone' => $this->input('mawdy_telefone'),
            'mawdy_email' => $this->input('mawdy_email')
        ];

        try {
            $data = [
                'data_confirmada' => $dataConfirmada,
                'data_agendamento' => $dataConfirmada,
                'mawdy_id' => $mawdyData['mawdy_id'],
                'mawdy_nome' => $mawdyData['mawdy_nome'],
                'mawdy_telefone' => $mawdyData['mawdy_telefone'],
                'mawdy_email' => $mawdyData['mawdy_email'],
                'status_id' => $this->getStatusId('Serviço Agendado')
            ];

            $this->solicitacaoModel->update($solicitacaoId, $data);
            
            // Buscar dados da solicitação para enviar no WhatsApp
            $solicitacao = $this->solicitacaoModel->find($solicitacaoId);
            
            // Enviar notificação WhatsApp
            $this->enviarNotificacaoWhatsApp($solicitacaoId, 'agendado', [
                'data_agendamento' => $dataConfirmada ? date('d/m/Y', strtotime($dataConfirmada)) : '',
                'horario_agendamento' => $solicitacao['horario_agendamento'] ?? 'A confirmar'
            ]);
            
            $this->json(['success' => true, 'message' => 'Datas confirmadas com sucesso']);
            
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao confirmar datas: ' . $e->getMessage()], 500);
        }
    }

    public function cancelarSolicitacao(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $solicitacaoId = $this->input('solicitacao_id');
        $motivo = $this->input('motivo', 'Cancelado pelo locatário');

        try {
            // Verificar se pode cancelar
            if (!$this->solicitacaoModel->podeCancelar($solicitacaoId)) {
                $this->json(['error' => 'Não é possível cancelar esta solicitação'], 400);
                return;
            }

            $this->solicitacaoModel->update($solicitacaoId, [
                'status_id' => $this->getStatusId('Cancelado'),
                'observacoes' => $motivo
            ]);

            $this->json(['success' => true, 'message' => 'Solicitação cancelada com sucesso']);
            
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao cancelar solicitação: ' . $e->getMessage()], 500);
        }
    }

    public function confirmarAtendimento(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $token = $this->input('token');
        $confirmacao = $this->input('confirmacao');
        $avaliacoes = [
            'imobiliaria' => $this->input('avaliacao_imobiliaria'),
            'app' => $this->input('avaliacao_app'),
            'prestador' => $this->input('avaliacao_prestador'),
            'comentarios' => $this->input('comentarios_avaliacao')
        ];

        try {
            // Buscar solicitação pelo token
            $sql = "SELECT id FROM solicitacoes WHERE token_confirmacao = ?";
            $solicitacao = \App\Core\Database::fetch($sql, [$token]);
            
            if (!$solicitacao) {
                $this->json(['error' => 'Token inválido'], 400);
                return;
            }

            $this->solicitacaoModel->confirmarAtendimento($solicitacao['id'], $confirmacao, $avaliacoes);
            
            // Enviar notificação WhatsApp
            $this->enviarNotificacaoWhatsApp($solicitacao['id'], 'concluido');
            
            $this->json(['success' => true, 'message' => 'Atendimento confirmado com sucesso']);
            
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao confirmar atendimento: ' . $e->getMessage()], 500);
        }
    }

    public function informarCompraPeca(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $solicitacaoId = $this->input('solicitacao_id');
        $novasDatas = json_decode($this->input('novas_datas', '[]'), true);

        try {
            // Validar novas datas
            $datasErrors = $this->solicitacaoModel->validarDatasOpcoes($novasDatas);
            if (!empty($datasErrors)) {
                $this->json(['error' => 'Datas inválidas', 'details' => $datasErrors], 400);
                return;
            }

            $this->solicitacaoModel->update($solicitacaoId, [
                'datas_opcoes' => $novasDatas,
                'status_id' => $this->getStatusId('Buscando Prestador'),
                'data_limite_peca' => null,
                'data_ultimo_lembrete' => null,
                'lembretes_enviados' => 0
            ]);

            $this->json(['success' => true, 'message' => 'Compra de peça informada com sucesso']);
            
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao informar compra de peça: ' . $e->getMessage()], 500);
        }
    }

    public function expirarSolicitacoes(): void
    {
        try {
            $solicitacoes = $this->solicitacaoModel->getSolicitacoesExpiradas();
            
            foreach ($solicitacoes as $solicitacao) {
                $this->solicitacaoModel->update($solicitacao['id'], [
                    'status_id' => $this->getStatusId('Expirado')
                ]);
            }

            $this->json([
                'success' => true,
                'message' => 'Solicitações expiradas',
                'count' => count($solicitacoes)
            ]);
            
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao expirar solicitações: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verifica e envia notificações 1 hora antes do prestador chegar
     * Deve ser chamado via cron job periodicamente (ex: a cada 5 minutos)
     * 
     * Endpoint público para cron job (sem autenticação)
     * GET /cron/notificacoes-pre-servico?token=SEU_TOKEN
     */
    public function cronNotificacoesPreServico(): void
    {
        // Validar token se fornecido (opcional para compatibilidade)
        $token = $this->input('token');
        if ($token) {
            $tokenEsperado = env('CRON_SECRET_TOKEN', 'kss_cron_secret_2024');
            if ($token !== $tokenEsperado) {
                $this->json(['error' => 'Token inválido'], 403);
                return;
            }
        }
        
        $this->processarNotificacoesPreServico();
    }

    /**
     * Processa as notificações pré-serviço
     * Método interno que pode ser chamado por cron ou manualmente
     */
    private function processarNotificacoesPreServico(): void
    {
        try {
            // Buscar solicitações com status "Serviço Agendado" que têm horário confirmado
            $sql = "
                SELECT s.*, st.nome as status_nome
                FROM solicitacoes s
                INNER JOIN status st ON s.status_id = st.id
                WHERE st.nome = 'Serviço Agendado'
                AND s.horario_confirmado = 1
                AND s.horario_confirmado_raw IS NOT NULL
                AND s.notificacao_pre_servico_enviada = 0
                AND s.data_agendamento IS NOT NULL
                AND s.horario_agendamento IS NOT NULL
            ";
            
            $solicitacoes = \App\Core\Database::fetchAll($sql);
            $enviadas = 0;
            $erros = [];
            
            foreach ($solicitacoes as $solicitacao) {
                try {
                    // Calcular quando o prestador deve chegar (1 hora antes do horário agendado)
                    $dataAgendamento = $solicitacao['data_agendamento'];
                    $horarioAgendamento = $solicitacao['horario_agendamento'];
                    
                    // Parsear horário (formato pode ser "HH:MM:SS" ou "HH:MM")
                    $horarioParts = explode(':', $horarioAgendamento);
                    $hora = (int)($horarioParts[0] ?? 0);
                    $minuto = (int)($horarioParts[1] ?? 0);
                    
                    // Log dos dados brutos
                    error_log("DEBUG Cron Pré-Serviço [ID:{$solicitacao['id']}] - Dados brutos:");
                    error_log("  - data_agendamento: " . $dataAgendamento);
                    error_log("  - horario_agendamento: " . $horarioAgendamento);
                    error_log("  - hora parseada: " . $hora);
                    error_log("  - minuto parseado: " . $minuto);
                    
                    // Criar DateTime para o horário agendado
                    $dataHoraAgendamento = new \DateTime($dataAgendamento . ' ' . sprintf('%02d:%02d:00', $hora, $minuto));
                    
                    // Calcular janela de notificação: 1 hora antes do agendamento até o horário agendado
                    $dataHoraInicioJanela = clone $dataHoraAgendamento;
                    $dataHoraInicioJanela->modify('-1 hour');
                    
                    // Verificar se estamos dentro da janela (entre 1h antes e o horário agendado)
                    $agora = new \DateTime();
                    
                    // Calcular janela estendida: até 30 minutos após o horário agendado (para casos onde o cron atrasou)
                    $dataHoraLimiteJanela = clone $dataHoraAgendamento;
                    $dataHoraLimiteJanela->modify('+30 minutes');
                    
                    // Verificar se agora está entre o início da janela (1h antes) e 30 minutos após o agendado
                    $estaNaJanela = ($agora >= $dataHoraInicioJanela && $agora <= $dataHoraLimiteJanela);
                    
                    // Log para debug
                    error_log("DEBUG Cron Pré-Serviço [ID:{$solicitacao['id']}] - Agora: " . $agora->format('Y-m-d H:i:s'));
                    error_log("DEBUG Cron Pré-Serviço [ID:{$solicitacao['id']}] - Início janela (1h antes): " . $dataHoraInicioJanela->format('Y-m-d H:i:s'));
                    error_log("DEBUG Cron Pré-Serviço [ID:{$solicitacao['id']}] - Horário agendado: " . $dataHoraAgendamento->format('Y-m-d H:i:s'));
                    error_log("DEBUG Cron Pré-Serviço [ID:{$solicitacao['id']}] - Limite janela (+30min): " . $dataHoraLimiteJanela->format('Y-m-d H:i:s'));
                    error_log("DEBUG Cron Pré-Serviço [ID:{$solicitacao['id']}] - Está na janela: " . ($estaNaJanela ? 'SIM' : 'NÃO'));
                    
                    // Se está dentro da janela de 1 hora antes até 30 minutos depois
                    if ($estaNaJanela) {
                        // Criar token para a página de ações
                        $tokenModel = new \App\Models\ScheduleConfirmationToken();
                        $protocol = $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacao['id']);
                        $token = $tokenModel->createToken(
                            $solicitacao['id'],
                            $protocol,
                            $dataAgendamento,
                            $horarioAgendamento,
                            'pre_servico'
                        );
                        
                        // Enviar notificação WhatsApp
                        // Usar URL base configurada para links WhatsApp
                        $config = require __DIR__ . '/../Config/config.php';
                        $whatsappConfig = $config['whatsapp'] ?? [];
                        $baseUrl = $whatsappConfig['links_base_url'] ?? \App\Core\Url::base();
                        $baseUrl = rtrim($baseUrl, '/');
                        $linkAcoes = $baseUrl . '/acoes-servico?token=' . $token;
                        
                        // Calcular período de chegada (1 hora antes até o horário agendado)
                        $periodoInicio = clone $dataHoraInicioJanela;
                        $periodoFim = clone $dataHoraAgendamento;
                        $periodoTexto = $periodoInicio->format('H:i') . ' às ' . $periodoFim->format('H:i');
                        
                        $this->enviarNotificacaoWhatsApp($solicitacao['id'], 'Lembrete Pré-Serviço', [
                            'link_acoes_servico' => $linkAcoes,
                            'data_agendamento' => date('d/m/Y', strtotime($dataAgendamento)),
                            'horario_agendamento' => date('H:i', strtotime($horarioAgendamento)),
                            'periodo_chegada' => $periodoTexto
                        ]);
                        
                        // Marcar como enviada
                        $this->solicitacaoModel->update($solicitacao['id'], [
                            'notificacao_pre_servico_enviada' => 1
                        ]);
                        
                        $enviadas++;
                        error_log("✅ Notificação pré-serviço enviada para solicitação #{$solicitacao['id']}");
                    } else {
                        error_log("⏳ Solicitação #{$solicitacao['id']} - Fora da janela de envio (ainda não chegou na hora ou já passou mais de 30min do agendamento)");
                    }
                } catch (\Exception $e) {
                    $erros[] = "Solicitação #{$solicitacao['id']}: " . $e->getMessage();
                    error_log("❌ Erro ao processar notificação pré-serviço para solicitação #{$solicitacao['id']}: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
            }
            
            error_log("=== FINALIZANDO PROCESSAMENTO CRON PRÉ-SERVIÇO - Enviadas: {$enviadas}, Total verificadas: " . count($solicitacoes) . " ===");
            
            $resultado = [
                'success' => true,
                'message' => 'Notificações pré-serviço processadas',
                'enviadas' => $enviadas,
                'total_verificadas' => count($solicitacoes),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($erros)) {
                $resultado['erros'] = $erros;
            }
            
            // Se chamado via HTTP, retornar JSON
            if (php_sapi_name() !== 'cli') {
                $this->json($resultado);
            } else {
                // Se chamado via CLI, apenas logar
                echo json_encode($resultado, JSON_PRETTY_PRINT) . "\n";
            }
            
        } catch (\Exception $e) {
            $erro = ['error' => 'Erro ao enviar notificações pré-serviço: ' . $e->getMessage()];
            error_log('❌ Erro geral no processamento de notificações pré-serviço: ' . $e->getMessage());
            
            if (php_sapi_name() !== 'cli') {
                $this->json($erro, 500);
            } else {
                echo json_encode($erro, JSON_PRETTY_PRINT) . "\n";
            }
        }
    }

    /**
     * Verifica e envia notificações 1 hora antes do prestador chegar
     * Endpoint para chamada manual (requer autenticação)
     */
    public function enviarNotificacoesPreServico(): void
    {
        $this->requireAuth();
        $this->processarNotificacoesPreServico();
    }

    /**
     * Processa notificações após o horário agendado.
     * Envia "Confirmação de Serviço" com link para informar o que aconteceu
     * exatamente no horário final do agendamento.
     * 
     * Se o usuário já fez ação no link do pré-serviço, reutiliza o mesmo link.
     * Caso contrário, cria um novo link.
     */
    private function processarNotificacoesPosServico(): void
    {
        try {
            error_log("=== INICIANDO PROCESSAMENTO CRON PÓS-SERVIÇO ===" . date('Y-m-d H:i:s'));
            
            // Buscar solicitações com status "Serviço Agendado" que já passaram do horário
            $sql = "
                SELECT s.*, st.nome as status_nome
                FROM solicitacoes s
                INNER JOIN status st ON s.status_id = st.id
                WHERE st.nome = 'Serviço Agendado'
                AND s.horario_confirmado = 1
                AND s.horario_confirmado_raw IS NOT NULL
                AND s.horario_confirmado_raw != ''
                AND s.notificacao_pos_servico_enviada = 0
                AND s.data_agendamento IS NOT NULL
                AND s.horario_agendamento IS NOT NULL
                AND s.data_agendamento >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
            ";
            
            $solicitacoes = \App\Core\Database::fetchAll($sql);
            error_log("Total de solicitações encontradas para verificação: " . count($solicitacoes));
            
            $enviadas = 0;
            $erros = [];
            
            foreach ($solicitacoes as $solicitacao) {
                try {
                    $dataAgendamento = $solicitacao['data_agendamento'];
                    $horarioAgendamento = $solicitacao['horario_agendamento'];
                    $horarioRawConfirmado = trim((string)($solicitacao['horario_confirmado_raw'] ?? ''));

                    // Parsear horário inicial
                    $horarioParts = explode(':', $horarioAgendamento);
                    $horaInicio = (int)($horarioParts[0] ?? 0);
                    $minutoInicio = (int)($horarioParts[1] ?? 0);

                    $dataHoraInicio = new \DateTime($dataAgendamento . ' ' . sprintf('%02d:%02d:00', $horaInicio, $minutoInicio));

                    // Determinar horário final a partir do raw (dd/mm/aaaa - HH:MM às HH:MM ou dd/mm/aaaa - HH:MM-HH:MM)
                    $dataHoraFim = null;
                    if ($horarioRawConfirmado !== '') {
                        // Aceitar ambos os formatos: "às" ou "-"
                        $regexRaw = '/^(?<data>\d{2}\/\d{2}\/\d{4})\s*-\s*(?<inicio>\d{2}:\d{2})(?::\d{2})?\s*(?:às|-)\s*(?<fim>\d{2}:\d{2})(?::\d{2})?$/';
                        if (preg_match($regexRaw, $horarioRawConfirmado, $matches)) {
                            $dataFim = \DateTime::createFromFormat('d/m/Y H:i', $matches['data'] . ' ' . $matches['fim']);
                            if ($dataFim instanceof \DateTime) {
                                $dataHoraFim = $dataFim;
                            }
                        }
                    }

                    if (!$dataHoraFim) {
                        // Fallback: considerar duração padrão de 3h após horário inicial
                        $dataHoraFim = clone $dataHoraInicio;
                        $dataHoraFim->modify('+3 hours');
                    }

                    $agora = new \DateTime();
                    
                    // Calcular janela de envio: a partir do horário final até 24 horas depois (para casos onde o cron atrasou)
                    $dataHoraLimiteEnvio = clone $dataHoraFim;
                    $dataHoraLimiteEnvio->modify('+24 hours');
                    
                    // Enviar a partir do horário final até 24 horas depois
                    error_log(sprintf(
                        "DEBUG Cron Pós-Serviço [ID:%d] - Início:%s Fim:%s Limite Envio (24h depois):%s Agora:%s",
                        $solicitacao['id'],
                        $dataHoraInicio->format('Y-m-d H:i:s'),
                        $dataHoraFim->format('Y-m-d H:i:s'),
                        $dataHoraLimiteEnvio->format('Y-m-d H:i:s'),
                        $agora->format('Y-m-d H:i:s')
                    ));

                    // Só enviar se já passou o horário final e ainda está dentro da janela de 24h
                    if ($agora >= $dataHoraFim && $agora <= $dataHoraLimiteEnvio) {
                        $tokenModel = new \App\Models\ScheduleConfirmationToken();
                        $protocol = $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacao['id']);
                        
                        // Verificar se houve ação no link do pré-serviço
                        $tokenPreServico = $tokenModel->getTokenPreServico($solicitacao['id']);
                        $houveAcaoPreServico = false;
                        $token = null;
                        
                        if ($tokenPreServico) {
                            // Verificar se o token ainda é válido (não expirou)
                            $expiresAt = new \DateTime($tokenPreServico['expires_at']);
                            $agora = new \DateTime();
                            
                            if ($agora < $expiresAt) {
                                // Token ainda válido, reutilizar mesmo que tenha sido usado
                                $houveAcaoPreServico = ($tokenPreServico['used_at'] !== null);
                                $token = $tokenPreServico['token'];
                                
                                if ($houveAcaoPreServico) {
                                    error_log("✅ Reutilizando token do pré-serviço para solicitação #{$solicitacao['id']} (já houve ação e token ainda válido)");
                                } else {
                                    error_log("✅ Reutilizando token do pré-serviço para solicitação #{$solicitacao['id']} (token criado mas sem ação ainda)");
                                }
                            } else {
                                // Token expirado, criar novo
                                $token = $tokenModel->createToken(
                                    $solicitacao['id'],
                                    $protocol,
                                    $dataAgendamento,
                                    $horarioAgendamento,
                                    'pos_servico'
                                );
                                error_log("✅ Criado novo token pós-serviço para solicitação #{$solicitacao['id']} (token pré-serviço expirado)");
                            }
                        } else {
                            // Não existe token do pré-serviço, criar novo
                            $token = $tokenModel->createToken(
                                $solicitacao['id'],
                                $protocol,
                                $dataAgendamento,
                                $horarioAgendamento,
                                'pos_servico'
                            );
                            error_log("✅ Criado novo token pós-serviço para solicitação #{$solicitacao['id']} (sem token pré-serviço)");
                        }
                        
                        // Enviar notificação WhatsApp
                        // Usar URL base configurada para links WhatsApp
                        $config = require __DIR__ . '/../Config/config.php';
                        $whatsappConfig = $config['whatsapp'] ?? [];
                        $baseUrl = $whatsappConfig['links_base_url'] ?? \App\Core\Url::base();
                        $baseUrl = rtrim($baseUrl, '/');
                        $linkAcoes = $baseUrl . '/acoes-servico?token=' . $token;
                        
                        $this->enviarNotificacaoWhatsApp($solicitacao['id'], 'Confirmação de Serviço', [
                            'link_acoes_servico' => $linkAcoes,
                            'data_agendamento' => date('d/m/Y', strtotime($dataAgendamento)),
                            'horario_agendamento' => date('H:i', strtotime($horarioAgendamento))
                        ]);
                        
                        // Marcar como enviada
                        $this->solicitacaoModel->update($solicitacao['id'], [
                            'notificacao_pos_servico_enviada' => 1
                        ]);
                        
                        $enviadas++;
                        error_log("✅ Notificação pós-serviço enviada para solicitação #{$solicitacao['id']} (houve ação pré-serviço: " . ($houveAcaoPreServico ? 'SIM' : 'NÃO') . ")");
                    } elseif ($agora < $dataHoraFim) {
                        error_log("⏳ Solicitação #{$solicitacao['id']} - Aguardando horário final do agendamento.");
                    } else {
                        error_log("⚠️ Solicitação #{$solicitacao['id']} - Passou do limite de 24h para envio. Não será enviado automaticamente.");
                    }
                } catch (\Exception $e) {
                    $erros[] = "Solicitação #{$solicitacao['id']}: " . $e->getMessage();
                    error_log("❌ Erro ao processar notificação pós-serviço para solicitação #{$solicitacao['id']}: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
            }
            
            error_log("=== FINALIZANDO PROCESSAMENTO CRON PÓS-SERVIÇO - Enviadas: {$enviadas}, Total verificadas: " . count($solicitacoes) . " ===");
            
            $resultado = [
                'success' => true,
                'message' => 'Notificações pós-serviço processadas',
                'enviadas' => $enviadas,
                'total_verificadas' => count($solicitacoes),
                'timestamp' => date('Y-m-d H:i:s'),
                'criterio_envio' => 'A partir do horário final do agendamento até 24h depois'
            ];
            
            if (!empty($erros)) {
                $resultado['erros'] = $erros;
            }
            
            if (php_sapi_name() !== 'cli') {
                $this->json($resultado);
            } else {
                echo json_encode($resultado, JSON_PRETTY_PRINT) . "\n";
            }
            
        } catch (\Exception $e) {
            $erro = ['error' => 'Erro ao enviar notificações pós-serviço: ' . $e->getMessage()];
            error_log('❌ Erro geral no processamento de notificações pós-serviço: ' . $e->getMessage());
            
            if (php_sapi_name() !== 'cli') {
                $this->json($erro, 500);
            } else {
                echo json_encode($erro, JSON_PRETTY_PRINT) . "\n";
            }
        }
    }

    /**
     * Endpoint público para cron job de notificações pós-serviço
     * GET /cron/notificacoes-pos-servico?token=SEU_TOKEN
     */
    public function cronNotificacoesPosServico(): void
    {
        // Validar token se fornecido (opcional para compatibilidade)
        $token = $this->input('token');
        if ($token) {
            $tokenEsperado = env('CRON_SECRET_TOKEN', 'kss_cron_secret_2024');
            if ($token !== $tokenEsperado) {
                $this->json(['error' => 'Token inválido'], 403);
                return;
            }
        }
        
        $this->processarNotificacoesPosServico();
    }

    /**
     * Endpoint para chamada manual (requer autenticação)
     */
    public function enviarNotificacoesPosServico(): void
    {
        $this->requireAuth();
        $this->processarNotificacoesPosServico();
    }

    /**
     * Verifica e envia lembretes de compra de peça
     * Deve ser chamado via cron job periodicamente (ex: a cada 5 minutos)
     * 
     * Endpoint público para cron job (sem autenticação)
     * GET /cron/lembretes-peca
     */
    public function cronLembretesPeca(): void
    {
        // Limpar buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Log para debug
        error_log("CRON Lembretes Peça: Método chamado - " . date('Y-m-d H:i:s'));
        
        try {
            $this->processarLembretesPeca();
        } catch (\Exception $e) {
            error_log("CRON Lembretes Peça: Erro - " . $e->getMessage());
            $this->json([
                'success' => false,
                'error' => 'Erro ao processar lembretes: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], 500);
        } catch (\Throwable $e) {
            error_log("CRON Lembretes Peça: Erro fatal - " . $e->getMessage());
            $this->json([
                'success' => false,
                'error' => 'Erro fatal ao processar lembretes: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Processa os lembretes de compra de peça
     * Método interno que pode ser chamado por cron ou manualmente
     */
    private function processarLembretesPeca(): void
    {
        try {
            // Buscar solicitações que precisam de lembrete
            $solicitacoes = $this->solicitacaoModel->getSolicitacoesParaLembrete();
            $enviadas = 0;
            $erros = [];
            
            foreach ($solicitacoes as $solicitacao) {
                try {
                    // Verificar se ainda está dentro do prazo de 10 dias
                    if (!empty($solicitacao['data_limite_peca'])) {
                        $dataLimite = new \DateTime($solicitacao['data_limite_peca']);
                        $agora = new \DateTime();
                        
                        if ($agora > $dataLimite) {
                            // Prazo expirado, não enviar mais lembretes
                            continue;
                        }
                        
                        // Calcular dias restantes
                        $diasRestantes = $agora->diff($dataLimite)->days;
                        
                        // Enviar notificação com informações do prazo
                        $this->enviarNotificacaoWhatsApp($solicitacao['id'], 'lembrete_peca', [
                            'dias_restantes' => $diasRestantes,
                            'data_limite' => date('d/m/Y', strtotime($solicitacao['data_limite_peca']))
                        ]);
                        
                        $this->solicitacaoModel->atualizarLembrete($solicitacao['id']);
                        $enviadas++;
                        error_log("✅ Lembrete de peça enviado para solicitação #{$solicitacao['id']}");
                    } else {
                        // Sem data limite, enviar lembrete normal
                        $this->enviarNotificacaoWhatsApp($solicitacao['id'], 'lembrete_peca');
                        $this->solicitacaoModel->atualizarLembrete($solicitacao['id']);
                        $enviadas++;
                        error_log("✅ Lembrete de peça enviado para solicitação #{$solicitacao['id']}");
                    }
                } catch (\Exception $e) {
                    $erros[] = "Solicitação #{$solicitacao['id']}: " . $e->getMessage();
                    error_log("❌ Erro ao processar lembrete de peça para solicitação #{$solicitacao['id']}: " . $e->getMessage());
                }
            }
            
            $resultado = [
                'success' => true,
                'message' => 'Lembretes de peça processados',
                'enviadas' => $enviadas,
                'total_verificadas' => count($solicitacoes),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($erros)) {
                $resultado['erros'] = $erros;
            }
            
            // Se chamado via HTTP, retornar JSON
            if (php_sapi_name() !== 'cli') {
                $this->json($resultado);
            } else {
                // Se chamado via CLI, apenas logar
                error_log("CRON Lembretes Peça: " . json_encode($resultado));
            }
            
        } catch (\Exception $e) {
            $erro = [
                'success' => false,
                'error' => 'Erro ao processar lembretes de peça: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            error_log("❌ Erro no CRON de lembretes de peça: " . $e->getMessage());
            
            if (php_sapi_name() !== 'cli') {
                $this->json($erro, 500);
            }
        }
    }

    /**
     * Endpoint para chamada manual (requer autenticação)
     */
    public function enviarLembretes(): void
    {
        $this->requireAuth();
        $this->processarLembretesPeca();
    }

    private function enviarNotificacaoWhatsApp(int $solicitacaoId, string $tipo, array $extraData = []): void
    {
        try {
            $whatsappService = new \App\Services\WhatsAppService();
            $result = $whatsappService->sendMessage($solicitacaoId, $tipo, $extraData);
            
            if (!$result['success']) {
                error_log('Erro WhatsApp: ' . $result['message']);
            }
        } catch (\Exception $e) {
            error_log('Erro ao enviar WhatsApp: ' . $e->getMessage());
        }
    }

    private function getStatusId(string $statusNome): int
    {
        $sql = "SELECT id FROM status WHERE nome = ? LIMIT 1";
        $status = \App\Core\Database::fetch($sql, [$statusNome]);
        return $status['id'] ?? 1;
    }

    public function confirmarHorario(int $id): void
    {
        // ✅ Limpar TODOS os buffers ANTES de qualquer coisa
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // ✅ Desabilitar exibição de erros IMEDIATAMENTE
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        
        // ✅ Função para SEMPRE retornar JSON válido
        $retornarJson = function($success, $message = '', $error = '') {
            // Limpar TODOS os buffers
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Desabilitar exibição de erros
            @ini_set('display_errors', '0');
            
            // Limpar qualquer output anterior
            @ob_end_clean();
            
            // Retornar JSON válido
            @http_response_code($success ? 200 : 500);
            @header('Content-Type: application/json; charset=utf-8');
            @header('Cache-Control: no-cache, must-revalidate');
            
            $response = ['success' => $success];
            if ($success && !empty($message)) {
                $response['message'] = $message;
            }
            if (!$success && !empty($error)) {
                $response['error'] = $error;
            }
            
            $json = @json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                $json = json_encode(['success' => false, 'error' => 'Erro ao serializar resposta'], JSON_UNESCAPED_UNICODE);
            }
            
            echo $json;
            @flush();
            @exit;
        };
        
        // ✅ Registrar erro fatal handler
        register_shutdown_function(function() use ($retornarJson) {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
                error_log('Erro FATAL em confirmarHorario: ' . $error['message'] . ' em ' . $error['file'] . ':' . $error['line']);
                $retornarJson(false, '', 'Erro fatal: ' . $error['message']);
            }
        });
        
        $horario = null;
        $user = null;
        $jaSalvou = false;
        
        try {
            if (!$this->isPost()) {
                $retornarJson(false, '', 'Método não permitido');
                return;
            }

            // ✅ Ler JSON do body (caso seja enviado via fetch)
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            
            // ✅ Aceitar horário do JSON ou do form
            $horario = $json['horario'] ?? $this->input('horario');
            $protocoloSeguradora = $json['protocolo_seguradora'] ?? $this->input('protocolo_seguradora');
            $user = $this->getUser();

            if (!$horario) {
                $retornarJson(false, '', 'Horário é obrigatório');
                return;
            }

            // ✅ Buscar status "Agendamento Confirmado" ou "Serviço Agendado"
            $statusModel = new \App\Models\Status();
            $statusAgendado = $statusModel->findByNome('Agendamento Confirmado');
            if (!$statusAgendado) {
                $statusAgendado = $statusModel->findByNome('Agendamento confirmado');
            }
            if (!$statusAgendado) {
                // Fallback para "Serviço Agendado"
                $statusAgendado = $statusModel->findByNome('Serviço Agendado');
            }
            if (!$statusAgendado) {
                $sql = "SELECT * FROM status WHERE (nome LIKE '%Agendamento Confirmado%' OR nome LIKE '%Serviço Agendado%') AND status = 'ATIVO' LIMIT 1";
                $statusAgendado = \App\Core\Database::fetch($sql);
            }
            
            if (!$statusAgendado || !isset($statusAgendado['id'])) {
                $retornarJson(false, '', 'Status "Agendamento Confirmado" ou "Serviço Agendado" não encontrado');
                return;
            }
            
            // ✅ Buscar condição "Data Aceita pelo Prestador" ou "Agendamento Confirmado"
            $condicaoModel = new \App\Models\Condicao();
            $condicaoConfirmada = $condicaoModel->findByNome('Data Aceita pelo Prestador');
            if (!$condicaoConfirmada) {
                $condicaoConfirmada = $condicaoModel->findByNome('Agendamento Confirmado');
            }
            if (!$condicaoConfirmada) {
                $sqlCondicao = "SELECT * FROM condicoes WHERE (nome LIKE '%Agendamento Confirmado%' OR nome LIKE '%Data Aceita pelo Prestador%') AND status = 'ATIVO' LIMIT 1";
                $condicaoConfirmada = \App\Core\Database::fetch($sqlCondicao);
            }
            
            // ✅ Validação: Protocolo da seguradora é obrigatório para mudar para "Serviço Agendado"
            if (empty($protocoloSeguradora) || trim($protocoloSeguradora) === '') {
                $retornarJson(false, '', 'É obrigatório preencher o protocolo da seguradora para confirmar o horário e mudar para "Serviço Agendado"');
                return;
            }
            
            // Validar formato do horário
            $timestamp = strtotime($horario);
            if ($timestamp === false) {
                // Tentar parsear formato ISO ou outros formatos
                try {
                    $dt = new \DateTime($horario);
                    $timestamp = $dt->getTimestamp();
                } catch (\Exception $e) {
                    error_log('Erro ao parsear horário: ' . $horario . ' - ' . $e->getMessage());
                    $retornarJson(false, '', 'Formato de horário inválido: ' . $horario);
                    return;
                }
            }
            
            if ($timestamp === false) {
                error_log('Erro: timestamp ainda é false após tentar parsear: ' . $horario);
                $retornarJson(false, '', 'Formato de horário inválido: ' . $horario);
                return;
            }
            
            $dataAg = date('Y-m-d', $timestamp);
            $horaAg = date('H:i:s', $timestamp);
            
            if ($dataAg === false || $horaAg === false) {
                error_log('Erro ao formatar data/hora do timestamp: ' . $timestamp);
                $retornarJson(false, '', 'Erro ao processar data/hora');
                return;
            }

            // ✅ Processar horário e adicionar ao confirmed_schedules
            $solicitacaoAtual = $this->solicitacaoModel->find($id);
            if (!$solicitacaoAtual) {
                $retornarJson(false, '', 'Solicitação não encontrada');
                return;
            }
            
            // ✅ VALIDAÇÃO: Verificar se o status atual é "Serviço Agendado"
            // Não é possível confirmar horário sem estar em "Serviço Agendado"
            $statusAtualId = $solicitacaoAtual['status_id'] ?? null;
            if ($statusAtualId) {
                $sqlStatusAtual = "SELECT nome FROM status WHERE id = ?";
                $statusAtualData = \App\Core\Database::fetch($sqlStatusAtual, [$statusAtualId]);
                $statusAtualNome = $statusAtualData['nome'] ?? '';
                
                // Verificar se o status atual não é "Serviço Agendado"
                if ($statusAtualNome !== 'Serviço Agendado') {
                    $retornarJson(false, '', 'É necessário alterar o status para "Serviço Agendado" antes de confirmar um horário. Por favor, altere o status primeiro.');
                    return;
                }
            }
            
            // Formatar horário para raw (mesmo formato do offcanvas)
            $horarioFormatado = date('d/m/Y', $timestamp) . ' - ' . date('H:i', $timestamp) . '-' . date('H:i', strtotime('+3 hours', $timestamp));
            
            // Buscar confirmed_schedules existentes
            $confirmedExistentes = [];
            if (!empty($solicitacaoAtual['confirmed_schedules'])) {
                try {
                    // Se já for array, usar diretamente; se for string, parsear
                    if (is_array($solicitacaoAtual['confirmed_schedules'])) {
                        $confirmedExistentes = $solicitacaoAtual['confirmed_schedules'];
                    } else {
                        $confirmedExistentes = json_decode($solicitacaoAtual['confirmed_schedules'], true) ?? [];
                    }
                    if (!is_array($confirmedExistentes)) {
                        $confirmedExistentes = [];
                    }
                } catch (\Exception $e) {
                    error_log('Erro ao parsear confirmed_schedules: ' . $e->getMessage());
                    $confirmedExistentes = [];
                }
            }
            
            // ✅ Função auxiliar para normalizar horários
            $normalizarHorario = function($raw) {
                $raw = trim((string)$raw);
                $raw = preg_replace('/\s+/', ' ', $raw); // Normalizar espaços múltiplos
                return $raw;
            };
            
            // ✅ Função auxiliar para comparar horários de forma precisa (mesma lógica do atualizarDetalhes)
            $compararHorarios = function($raw1, $raw2) {
                $raw1Norm = preg_replace('/\s+/', ' ', trim((string)$raw1));
                $raw2Norm = preg_replace('/\s+/', ' ', trim((string)$raw2));
                
                // Comparação exata primeiro (mais precisa)
                if ($raw1Norm === $raw2Norm) {
                    return true;
                }
                
                // Comparação por regex - extrair data e hora inicial E FINAL EXATAS
                // Formato esperado: "dd/mm/yyyy - HH:MM-HH:MM"
                $regex = '/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})-(\d{2}:\d{2})/';
                $match1 = preg_match($regex, $raw1Norm, $m1);
                $match2 = preg_match($regex, $raw2Norm, $m2);
                
                if ($match1 && $match2) {
                    // ✅ Comparar data, hora inicial E hora final EXATAS (não apenas data e hora inicial)
                    // Isso garante que apenas horários EXATOS sejam considerados iguais
                    return ($m1[1] === $m2[1] && $m1[2] === $m2[2] && $m1[3] === $m2[3]);
                }
                
                // Se não conseguir comparar por regex, retornar false (não é match)
                return false;
            };
            
            $horarioFormatadoNorm = $normalizarHorario($horarioFormatado);
            
            // ✅ Verificar se já existe este horário confirmado (usando comparação precisa)
            $horarioJaConfirmado = false;
            $horarioExistente = null;
            foreach ($confirmedExistentes as $existente) {
                if (!isset($existente['raw']) || empty($existente['raw'])) {
                    continue;
                }
                $existenteRawNorm = $normalizarHorario($existente['raw']);
                if ($compararHorarios($horarioFormatadoNorm, $existenteRawNorm)) {
                    $horarioJaConfirmado = true;
                    $horarioExistente = $existente;
                    break;
                }
            }
            
            // Se não existe, adicionar aos confirmados
            if (!$horarioJaConfirmado) {
                $confirmedExistentes[] = [
                    'date' => $dataAg,
                    'time' => date('H:i', $timestamp) . '-' . date('H:i', strtotime('+3 hours', $timestamp)),
                    'raw' => $horarioFormatadoNorm, // ✅ Usar formato normalizado
                    'source' => 'operator',
                    'confirmed_at' => date('c')
                ];
            } else {
                // ✅ Se já existe, garantir que está usando o formato normalizado
                $confirmedExistentes = array_map(function($item) use ($horarioFormatadoNorm, $normalizarHorario, $compararHorarios) {
                    if (isset($item['raw']) && $compararHorarios($item['raw'], $horarioFormatadoNorm)) {
                        $item['raw'] = $horarioFormatadoNorm; // ✅ Normalizar formato
                    }
                    return $item;
                }, $confirmedExistentes);
            }

            // Atualizar solicitação
            // ✅ Usar horarioFormatadoNorm em vez de horarioFormatado para consistência
            $dadosUpdate = [
                'data_agendamento' => $dataAg,
                'horario_agendamento' => $horaAg,
                'status_id' => $statusAgendado['id'],
                'horario_confirmado' => 1,
                'horario_confirmado_raw' => $horarioFormatadoNorm, // ✅ Usar formato normalizado
                'confirmed_schedules' => json_encode($confirmedExistentes),
                'protocolo_seguradora' => trim($protocoloSeguradora) // ✅ Salvar protocolo da seguradora
            ];
            
            // ✅ Adicionar condição "Data Aceita pelo Prestador" ou "Agendamento Confirmado" quando admin confirma
            if ($condicaoConfirmada) {
                $dadosUpdate['condicao_id'] = $condicaoConfirmada['id'];
                error_log("DEBUG confirmarHorario [ID:{$id}] - ✅ Condição alterada para 'Agendamento Confirmado' (ID: {$condicaoConfirmada['id']})");
            } else {
                error_log("DEBUG confirmarHorario [ID:{$id}] - ⚠️ Condição 'Agendamento Confirmado' não encontrada no banco de dados");
            }
            
            // ✅ DEBUG: Log antes de remover duplicatas
            error_log("DEBUG confirmarHorario [ID:{$id}] - confirmedExistentes ANTES de remover duplicatas: " . json_encode($confirmedExistentes));
            error_log("DEBUG confirmarHorario [ID:{$id}] - horarioFormatadoNorm: {$horarioFormatadoNorm}");
            error_log("DEBUG confirmarHorario [ID:{$id}] - Total antes de remover duplicatas: " . count($confirmedExistentes));
            
            // ✅ Remover duplicatas finais (segurança extra) - ANTES de salvar
            $confirmedFinalUnicos = [];
            $rawsJaAdicionados = [];
            foreach ($confirmedExistentes as $index => $item) {
                if (!isset($item['raw']) || empty($item['raw'])) {
                    error_log("DEBUG confirmarHorario [ID:{$id}] - Item {$index} sem raw, pulando");
                    continue;
                }
                $rawNorm = $normalizarHorario($item['raw']);
                
                // Verificar se já foi adicionado
                $jaAdicionado = false;
                foreach ($rawsJaAdicionados as $idx => $rawJaAdd) {
                    if ($compararHorarios($rawNorm, $rawJaAdd)) {
                        $jaAdicionado = true;
                        error_log("DEBUG confirmarHorario [ID:{$id}] - ⚠️ DUPLICATA DETECTADA! Item {$index} com raw '{$rawNorm}' já existe no índice {$idx} como '{$rawJaAdd}'");
                        break;
                    }
                }
                
                if (!$jaAdicionado) {
                    $confirmedFinalUnicos[] = $item;
                    $rawsJaAdicionados[] = $rawNorm;
                    error_log("DEBUG confirmarHorario [ID:{$id}] - ✅ Item {$index} adicionado: '{$rawNorm}'");
                }
            }
            
            // ✅ DEBUG: Log após remover duplicatas
            error_log("DEBUG confirmarHorario [ID:{$id}] - confirmedFinalUnicos APÓS remover duplicatas: " . json_encode($confirmedFinalUnicos));
            error_log("DEBUG confirmarHorario [ID:{$id}] - Total APÓS remover duplicatas: " . count($confirmedFinalUnicos));
            
            // Validar que confirmed_schedules é JSON válido
            $confirmedJsonFinal = json_encode($confirmedFinalUnicos);
            if ($confirmedJsonFinal === false) {
                error_log('Erro ao serializar confirmed_schedules: ' . json_last_error_msg());
                $retornarJson(false, '', 'Erro ao processar horários confirmados');
                return;
            }
            $dadosUpdate['confirmed_schedules'] = $confirmedJsonFinal;
            
            // ✅ Verificar se já salvou (proteção contra duplicação)
            if ($jaSalvou) {
                error_log('AVISO: Tentativa de salvar confirmarHorario duas vezes para ID: ' . $id);
                $retornarJson(false, '', 'Operação já foi processada');
                return;
            }
            
            try {
                $this->solicitacaoModel->update($id, $dadosUpdate);
                $jaSalvou = true; // ✅ Marcar como salvo
            } catch (\Exception $e) {
                error_log('Erro ao atualizar solicitação: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                $retornarJson(false, '', 'Erro ao salvar solicitação: ' . $e->getMessage());
                return;
            }
            
            // Registrar histórico
            if ($user && isset($user['id'])) {
                try {
                    $this->solicitacaoModel->updateStatus($id, $statusAgendado['id'], $user['id'], 
                        'Horário confirmado: ' . $horarioFormatado);
                } catch (\Exception $e) {
                    // Log do erro mas não bloquear a resposta
                    error_log('Erro ao atualizar status no histórico: ' . $e->getMessage());
                }
            }
            
            // ✅ Enviar notificação WhatsApp (em background, não bloquear)
            try {
                // Buscar dados atualizados da solicitação para garantir que temos o telefone correto
                $solicitacaoAtual = $this->solicitacaoModel->find($id);
                
                // Verificar se tem telefone antes de enviar
                $telefone = $solicitacaoAtual['locatario_telefone'] ?? null;
                if (empty($telefone) && !empty($solicitacaoAtual['locatario_id'])) {
                    // Buscar telefone do locatário
                    $sqlLocatario = "SELECT telefone FROM locatarios WHERE id = ?";
                    $locatario = \App\Core\Database::fetch($sqlLocatario, [$solicitacaoAtual['locatario_id']]);
                    $telefone = $locatario['telefone'] ?? null;
                }
                
                if (!empty($telefone)) {
                    // Formatar horário completo para exibição
                    $horarioIntervalo = date('H:i', $timestamp) . ' às ' . date('H:i', strtotime('+3 hours', $timestamp));
                    $horarioCompleto = $horarioFormatadoNorm ?? date('d/m/Y', $timestamp) . ' - ' . $horarioIntervalo;
                    
                    $this->enviarNotificacaoWhatsApp($id, 'Horário Confirmado', [
                        'data_agendamento' => date('d/m/Y', $timestamp),
                        'horario_agendamento' => $horarioIntervalo, // ✅ Formato: "08:00 às 11:00"
                        'horario_servico' => $horarioCompleto,
                        'horario_confirmado_raw' => $horarioFormatadoNorm ?? $horarioFormatado
                    ]);
                    
                    error_log("DEBUG WhatsApp [ID:{$id}] - WhatsApp enviado para telefone: {$telefone}");
                } else {
                    error_log("DEBUG WhatsApp [ID:{$id}] - ⚠️ Telefone não encontrado, WhatsApp NÃO enviado");
                }
            } catch (\Exception $e) {
                // Ignorar erro de WhatsApp, não bloquear a resposta
                error_log('Erro ao enviar WhatsApp [ID:' . $id . ']: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
            }
            
            // ✅ Retornar sucesso
            $retornarJson(true, 'Horário confirmado com sucesso');
            
        } catch (\Exception $e) {
            error_log('Erro em confirmarHorario [ID:' . $id . ']: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            error_log('Horário recebido: ' . var_export($horario, true));
            
            $retornarJson(false, '', 'Erro ao confirmar horário: ' . $e->getMessage());
            
        } catch (\Throwable $e) {
            error_log('Erro fatal em confirmarHorario [ID:' . $id . ']: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            error_log('Horário recebido: ' . var_export($horario ?? 'N/A', true));
            
            $retornarJson(false, '', 'Erro inesperado ao confirmar horário: ' . $e->getMessage());
            
        } catch (\Exception $e) {
            error_log('Erro EXCEPCIONAL em confirmarHorario [ID:' . $id . ']: ' . $e->getMessage());
            $retornarJson(false, '', 'Erro ao confirmar horário: ' . $e->getMessage());
        } catch (\Throwable $e) {
            error_log('Erro FATAL em confirmarHorario [ID:' . $id . ']: ' . $e->getMessage());
            $retornarJson(false, '', 'Erro inesperado ao confirmar horário: ' . $e->getMessage());
        }
    }

    public function confirmarHorariosBulk(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $schedules = $payload['schedules'] ?? []; // [{date: 'YYYY-MM-DD', time: 'HH:MM'|'HH:MM-HH:MM', raw: '...'}]

        if (empty($schedules) || !is_array($schedules)) {
            $this->json(['error' => 'Nenhum horário informado'], 400);
            return;
        }

        try {
            // Normalizar confirmados para salvar como JSON
            $confirmed = [];
            foreach ($schedules as $s) {
                $confirmed[] = [
                    'date' => $s['date'] ?? null,
                    'time' => $s['time'] ?? null,
                    'raw'  => $s['raw'] ?? trim(($s['date'] ?? '') . ' ' . ($s['time'] ?? '')),
                    'source' => 'operator',
                    'confirmed_at' => date('c')
                ];
            }

            // Último será o agendamento principal
            $last = end($confirmed);
            $dataAg = (!empty($last['date'])) ? date('Y-m-d', strtotime($last['date'])) : null;
            // Se time for faixa, inclui apenas início
            $horaRaw = $last['time'] ?? '';
            $horaAg = preg_match('/^\d{2}:\d{2}/', $horaRaw, $m) ? ($m[0] . ':00') : (!empty($horaRaw) ? $horaRaw : null);

            // Atualizar registro
            $this->solicitacaoModel->update($id, [
                'data_agendamento' => $dataAg,
                'horario_agendamento' => $horaAg,
                'horario_confirmado' => 1,
                'horario_confirmado_raw' => $last['raw'],
                'confirmed_schedules' => json_encode($confirmed)
            ]);

            // Enviar WhatsApp (opcional)
            $solicitacaoAtual = $this->solicitacaoModel->find($id);
            $horarioIntervalo = $this->extrairIntervaloHorario(
                $solicitacaoAtual['horario_confirmado_raw'] ?? $horaRaw ?? null,
                $solicitacaoAtual['horario_agendamento'] ?? $horaAg ?? null,
                $solicitacaoAtual
            );
            
            $this->enviarNotificacaoWhatsApp($id, 'Horário Confirmado', [
                'data_agendamento' => (!empty($dataAg)) ? date('d/m/Y', strtotime($dataAg)) : '',
                'horario_agendamento' => $horarioIntervalo
            ]);

            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function desconfirmarHorario(int $id): void
    {
        // ✅ Limpar TODOS os buffers ANTES de qualquer coisa
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // ✅ Desabilitar exibição de erros IMEDIATAMENTE
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        
        // ✅ Função para SEMPRE retornar JSON válido
        $retornarJson = function($success, $message = '', $error = '') {
            // Limpar TODOS os buffers
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Desabilitar exibição de erros
            @ini_set('display_errors', '0');
            
            // Limpar qualquer output anterior
            @ob_end_clean();
            
            // Retornar JSON válido
            @http_response_code($success ? 200 : 500);
            @header('Content-Type: application/json; charset=utf-8');
            @header('Cache-Control: no-cache, must-revalidate');
            
            $response = ['success' => $success];
            if ($success && !empty($message)) {
                $response['message'] = $message;
            }
            if (!$success && !empty($error)) {
                $response['error'] = $error;
            }
            
            $json = @json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                $json = json_encode(['success' => false, 'error' => 'Erro ao serializar resposta'], JSON_UNESCAPED_UNICODE);
            }
            
            echo $json;
            @flush();
            @exit;
        };
        
        $oldErrorReporting = error_reporting(E_ALL);
        $oldDisplayErrors = ini_set('display_errors', '0');
        
        $horario = null;
        $user = null;
        
        try {
            if (!$this->isPost()) {
                $retornarJson(false, '', 'Método não permitido');
                return;
            }

            // ✅ Ler JSON do body (caso seja enviado via fetch)
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            
            // ✅ Aceitar horário do JSON ou do form
            $horario = $json['horario'] ?? $this->input('horario');
            $user = $this->getUser();
            
            if (!$user || !isset($user['id'])) {
                $retornarJson(false, '', 'Usuário não autenticado');
                return;
            }

            // ✅ Buscar solicitação atual
            $solicitacaoAtual = $this->solicitacaoModel->find($id);
            if (!$solicitacaoAtual) {
                $retornarJson(false, '', 'Solicitação não encontrada');
                return;
            }
            
            // ✅ Buscar confirmed_schedules existentes
            $confirmedExistentes = [];
            if (!empty($solicitacaoAtual['confirmed_schedules'])) {
                try {
                    if (is_array($solicitacaoAtual['confirmed_schedules'])) {
                        $confirmedExistentes = $solicitacaoAtual['confirmed_schedules'];
                    } else {
                        $confirmedExistentes = json_decode($solicitacaoAtual['confirmed_schedules'], true) ?? [];
                    }
                    if (!is_array($confirmedExistentes)) {
                        $confirmedExistentes = [];
                    }
                } catch (\Exception $e) {
                    error_log('Erro ao parsear confirmed_schedules: ' . $e->getMessage());
                    $confirmedExistentes = [];
                }
            }
            
            // ✅ Função auxiliar para normalizar horários
            $normalizarHorario = function($raw) {
                $raw = trim((string)$raw);
                $raw = preg_replace('/\s+/', ' ', $raw);
                return $raw;
            };
            
            // ✅ Função auxiliar para comparar horários de forma precisa
            $compararHorarios = function($raw1, $raw2) {
                $raw1Norm = preg_replace('/\s+/', ' ', trim((string)$raw1));
                $raw2Norm = preg_replace('/\s+/', ' ', trim((string)$raw2));
                
                // Comparação exata primeiro
                if ($raw1Norm === $raw2Norm) {
                    return true;
                }
                
                // Comparação por regex - extrair data e hora inicial E FINAL EXATAS
                $regex = '/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})-(\d{2}:\d{2})/';
                $match1 = preg_match($regex, $raw1Norm, $m1);
                $match2 = preg_match($regex, $raw2Norm, $m2);
                
                if ($match1 && $match2) {
                    // ✅ Comparar data, hora inicial E hora final EXATAS
                    return ($m1[1] === $m2[1] && $m1[2] === $m2[2] && $m1[3] === $m2[3]);
                }
                
                return false;
            };
            
            // ✅ Se horário foi especificado, remover apenas esse horário
            if (!empty($horario)) {
                $horarioFormatadoNorm = $normalizarHorario($horario);
                
                // ✅ Remover apenas o horário específico do array
                $confirmedFinal = [];
                foreach ($confirmedExistentes as $item) {
                    if (!isset($item['raw']) || empty($item['raw'])) {
                        continue;
                    }
                    $itemRawNorm = $normalizarHorario($item['raw']);
                    
                    // ✅ Se for o horário a ser removido, não adicionar
                    if ($compararHorarios($itemRawNorm, $horarioFormatadoNorm)) {
                        error_log("DEBUG desconfirmarHorario [ID:{$id}] - Removendo horário: {$itemRawNorm}");
                        continue; // Pular este item
                    }
                    
                    // Adicionar os outros horários
                    $confirmedFinal[] = $item;
                }
                
                error_log("DEBUG desconfirmarHorario [ID:{$id}] - Total antes: " . count($confirmedExistentes));
                error_log("DEBUG desconfirmarHorario [ID:{$id}] - Total depois: " . count($confirmedFinal));
                
                $confirmedExistentes = $confirmedFinal;
            } else {
                // ✅ Se não especificou horário, limpar todos (comportamento antigo)
                $confirmedExistentes = [];
            }
            
            // ✅ Buscar status "Nova Solicitação" ou "Pendente" se não há mais horários confirmados
            $statusNova = null;
            if (empty($confirmedExistentes)) {
                $sqlStatus = "SELECT id FROM status WHERE nome IN ('Nova Solicitação', 'Pendente') LIMIT 1";
                $statusNova = \App\Core\Database::fetch($sqlStatus);
            }
            
            // ✅ Preparar dados de atualização
            $dadosUpdate = [
                'confirmed_schedules' => json_encode($confirmedExistentes)
            ];
            
            // ✅ Se não há mais horários confirmados, limpar campos de agendamento
            if (empty($confirmedExistentes)) {
                $dadosUpdate['data_agendamento'] = null;
                $dadosUpdate['horario_agendamento'] = null;
                $dadosUpdate['horario_confirmado'] = 0;
                $dadosUpdate['horario_confirmado_raw'] = null;
                
                if ($statusNova && isset($statusNova['id'])) {
                    $dadosUpdate['status_id'] = $statusNova['id'];
                }
            } else {
                // ✅ Se ainda há horários confirmados, atualizar com o último horário
                $last = end($confirmedExistentes);
                $dataAg = (!empty($last['date'])) ? date('Y-m-d', strtotime($last['date'])) : null;
                $horaRaw = $last['time'] ?? '';
                $horaAg = preg_match('/^\d{2}:\d{2}/', $horaRaw, $m) ? ($m[0] . ':00') : (!empty($horaRaw) ? $horaRaw : null);
                
                $dadosUpdate['data_agendamento'] = $dataAg;
                $dadosUpdate['horario_agendamento'] = $horaAg;
                $dadosUpdate['horario_confirmado'] = 1;
                $dadosUpdate['horario_confirmado_raw'] = $last['raw'] ?? null;
            }
            
            // ✅ Atualizar solicitação
            $this->solicitacaoModel->update($id, $dadosUpdate);
            
            // ✅ Registrar histórico
            if ($user && isset($user['id'])) {
                $statusId = $statusNova && isset($statusNova['id']) ? $statusNova['id'] : $solicitacaoAtual['status_id'];
                $mensagem = !empty($horario) ? "Horário desconfirmado: {$horario}" : 'Todos os horários foram desconfirmados';
                $this->solicitacaoModel->updateStatus($id, $statusId, $user['id'], $mensagem);
            }
            
            $retornarJson(true, 'Horário desconfirmado com sucesso');
            
        } catch (\Exception $e) {
            error_log('Erro em desconfirmarHorario [ID:' . $id . ']: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            error_log('Horário recebido: ' . var_export($horario, true));
            $retornarJson(false, '', 'Erro ao desconfirmar horário: ' . $e->getMessage());
        } catch (\Throwable $e) {
            error_log('Erro fatal em desconfirmarHorario [ID:' . $id . ']: ' . $e->getMessage());
            $retornarJson(false, '', 'Erro inesperado ao desconfirmar horário: ' . $e->getMessage());
        }
    }
    
    public function solicitarNovosHorarios(int $id): void
    {
        // ✅ Iniciar output buffering ANTES de qualquer coisa (captura qualquer output indesejado)
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        // ✅ Desabilitar exibição de erros para evitar HTML na resposta
        $oldErrorReporting = error_reporting(E_ALL);
        $oldDisplayErrors = ini_set('display_errors', '0');
        
        try {
            if (!$this->isPost()) {
                $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
                return;
            }

            // ✅ Ler JSON do body (caso seja enviado via fetch)
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            
            // ✅ Aceitar observação do JSON ou do form
            $observacao = $json['observacao'] ?? $this->input('observacao');
            $user = $this->getUser();
            
            if (!$user || !isset($user['id'])) {
                $this->json(['success' => false, 'error' => 'Usuário não autenticado'], 401);
                return;
            }

            // Limpar horários atuais
            $this->solicitacaoModel->update($id, [
                'horarios_opcoes' => null
            ]);
            
            // Registrar no histórico
            $solicitacao = $this->solicitacaoModel->find($id);
            if (!$solicitacao) {
                $this->json(['success' => false, 'error' => 'Solicitação não encontrada'], 404);
                return;
            }
            
            $this->solicitacaoModel->updateStatus($id, 
                $solicitacao['status_id'], 
                $user['id'], 
                'Horários indisponíveis. Motivo: ' . ($observacao ?? 'Não informado'));
            
            // Enviar notificação WhatsApp solicitando novos horários (em background, não bloquear)
            try {
                $this->enviarNotificacaoWhatsApp($id, 'Horário Sugerido', [
                    'data_agendamento' => 'A definir',
                    'horario_agendamento' => 'Aguardando novas opções'
                ]);
            } catch (\Exception $e) {
                // Ignorar erro de WhatsApp, não bloquear a resposta
                error_log('Erro ao enviar WhatsApp: ' . $e->getMessage());
            }
            
            $this->json(['success' => true, 'message' => 'Solicitação de novos horários enviada']);
            
        } catch (\Exception $e) {
            error_log('Erro em solicitarNovosHorarios: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->json(['success' => false, 'error' => 'Erro ao solicitar novos horários: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            error_log('Erro fatal em solicitarNovosHorarios: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erro inesperado ao solicitar novos horários'], 500);
        } finally {
            // ✅ Limpar qualquer output buffer antes de retornar JSON
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Restaurar configurações anteriores
            error_reporting($oldErrorReporting);
            if ($oldDisplayErrors !== false) {
                ini_set('display_errors', $oldDisplayErrors);
            }
        }
    }

    /**
     * Adiciona horário sugerido pela seguradora
     */
    public function adicionarHorarioSeguradora(int $id): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        $oldErrorReporting = error_reporting(E_ALL);
        $oldDisplayErrors = ini_set('display_errors', '0');
        
        try {
            if (!$this->isPost()) {
                $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
                return;
            }

            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            
            $horario = $json['horario'] ?? $this->input('horario');
            $data = $json['data'] ?? $this->input('data');
            $horaInicio = $json['hora_inicio'] ?? $this->input('hora_inicio');
            $horaFim = $json['hora_fim'] ?? $this->input('hora_fim');
            
            if (empty($horario) || empty($data)) {
                $this->json(['success' => false, 'error' => 'Horário e data são obrigatórios'], 400);
                return;
            }

            // Buscar solicitação atual
            $solicitacao = $this->solicitacaoModel->find($id);
            if (!$solicitacao) {
                $this->json(['success' => false, 'error' => 'Solicitação não encontrada'], 404);
                return;
            }

            // IMPORTANTE: Quando admin adiciona horários da seguradora, deve SUBSTITUIR os horários do locatário
            // horarios_opcoes passa a conter APENAS os horários da seguradora
            
            // Buscar horários da seguradora existentes (se já houver)
            $horariosSeguradora = [];
            if (!empty($solicitacao['horarios_indisponiveis']) && !empty($solicitacao['horarios_opcoes'])) {
                $horariosSeguradora = json_decode($solicitacao['horarios_opcoes'], true) ?? [];
                if (!is_array($horariosSeguradora)) {
                    $horariosSeguradora = [];
                }
            }

            // Verificar se horário já existe
            if (in_array($horario, $horariosSeguradora)) {
                $this->json(['success' => false, 'error' => 'Este horário já foi adicionado'], 400);
                return;
            }

            // Adicionar novo horário da seguradora
            $horariosSeguradora[] = $horario;

            // Buscar status atual
            $sqlStatus = "SELECT nome FROM status WHERE id = ?";
            $statusAtual = \App\Core\Database::fetch($sqlStatus, [$solicitacao['status_id']]);
            $statusNome = $statusAtual['nome'] ?? '';
            
            // Atualizar solicitação
            // IMPORTANTE: Quando admin adiciona horários, SUBSTITUI os horários do locatário
            // Limpar confirmed_schedules e dados de agendamento quando admin substitui horários
            $updateData = [
                'horarios_opcoes' => json_encode($horariosSeguradora),
                'horarios_indisponiveis' => 1
            ];
            
            // ✅ Se status é "Buscando Prestador", mudar condição para "Aguardando Locatário"
            if ($statusNome === 'Buscando Prestador') {
                $condicaoModel = new \App\Models\Condicao();
                $condicaoAguardando = $condicaoModel->findByNome('Aguardando Locatário');
                
                // Se não encontrar, buscar qualquer condição com "Aguardando" e "Locatário"
                if (!$condicaoAguardando) {
                    $sqlCondicao = "SELECT * FROM condicoes WHERE (nome LIKE '%Aguardando%Locatário%' OR nome LIKE '%Aguardando Locatário%') AND status = 'ATIVO' LIMIT 1";
                    $condicaoAguardando = \App\Core\Database::fetch($sqlCondicao);
                }
                
                if ($condicaoAguardando) {
                    $updateData['condicao_id'] = $condicaoAguardando['id'];
                    error_log("DEBUG adicionarHorarioSeguradora [ID:{$id}] - ✅ Condição alterada para 'Aguardando Locatário' (ID: {$condicaoAguardando['id']})");
                } else {
                    error_log("DEBUG adicionarHorarioSeguradora [ID:{$id}] - ⚠️ Condição 'Aguardando Locatário' não encontrada no banco de dados");
                }
            } else {
                error_log("DEBUG adicionarHorarioSeguradora [ID:{$id}] - Status atual: '{$statusNome}' (não é 'Buscando Prestador')");
            }
            
            // Se é a primeira vez adicionando horários da seguradora, preservar dados originais do locatário
            if (empty($solicitacao['horarios_indisponiveis'])) {
                // ✅ Preservar horários originais do locatário em datas_opcoes se ainda não estiverem lá
                if (empty($solicitacao['datas_opcoes']) && !empty($solicitacao['horarios_opcoes'])) {
                    $updateData['datas_opcoes'] = $solicitacao['horarios_opcoes'];
                }
                
                // Limpar confirmações anteriores
                $updateData['confirmed_schedules'] = null;
                $updateData['horario_confirmado'] = 0;
                $updateData['horario_confirmado_raw'] = null;
                $updateData['data_agendamento'] = null;
                $updateData['horario_agendamento'] = null;
            }
            
            $this->solicitacaoModel->update($id, $updateData);

            // Enviar notificação WhatsApp com horário sugerido
            // ✅ Não enviar "Horário Sugerido" se o status for "Serviço Agendado"
            if ($statusNome !== 'Serviço Agendado') {
            try {
                // Formatar data corretamente (aceitar diferentes formatos)
                $dataFormatada = '';
                if (!empty($data)) {
                    // Tentar formato YYYY-MM-DD primeiro
                    $dataObj = \DateTime::createFromFormat('Y-m-d', $data);
                    if ($dataObj) {
                        $dataFormatada = $dataObj->format('d/m/Y');
                    } else {
                        // Tentar formato dd/mm/YYYY
                        $dataObj = \DateTime::createFromFormat('d/m/Y', $data);
                        if ($dataObj) {
                            $dataFormatada = $data;
                        } else {
                            // Tentar strtotime como fallback
                            $timestamp = strtotime($data);
                            if ($timestamp !== false) {
                                $dataFormatada = date('d/m/Y', $timestamp);
                            }
                        }
                    }
                }
                
                // Formatar horário corretamente
                $horarioFormatado = '';
                if (!empty($horaInicio) && !empty($horaFim)) {
                    // Remover segundos se houver
                    $horaInicioLimpa = preg_replace('/:\d{2}$/', '', $horaInicio);
                    $horaFimLimpa = preg_replace('/:\d{2}$/', '', $horaFim);
                    $horarioFormatado = $horaInicioLimpa . '-' . $horaFimLimpa;
                } elseif (!empty($horario)) {
                    // Tentar extrair horário do campo 'horario' se não tiver horaInicio/horaFim
                    // Formato esperado: "dd/mm/yyyy - HH:MM-HH:MM"
                    if (preg_match('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $horario, $matches)) {
                        $horarioFormatado = $matches[1] . '-' . $matches[2];
                    }
                }
                
                $this->enviarNotificacaoWhatsApp($id, 'Horário Sugerido', [
                    'data_agendamento' => $dataFormatada,
                    'horario_agendamento' => $horarioFormatado
                ]);
            } catch (\Exception $e) {
                error_log('Erro ao enviar WhatsApp: ' . $e->getMessage());
            }
            } else {
                error_log("DEBUG adicionarHorarioSeguradora [ID:{$id}] - WhatsApp 'Horário Sugerido' NÃO enviado (status é 'Serviço Agendado')");
            }

            $this->json(['success' => true, 'message' => 'Horário adicionado com sucesso']);

        } catch (\Exception $e) {
            error_log('Erro em adicionarHorarioSeguradora: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erro ao adicionar horário: ' . $e->getMessage()], 500);
        } finally {
            while (ob_get_level()) {
                ob_end_clean();
            }
            error_reporting($oldErrorReporting);
            if ($oldDisplayErrors !== false) {
                ini_set('display_errors', $oldDisplayErrors);
            }
        }
    }

    /**
     * Remove horário sugerido pela seguradora
     */
    public function removerHorarioSeguradora(int $id): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        $oldErrorReporting = error_reporting(E_ALL);
        $oldDisplayErrors = ini_set('display_errors', '0');
        
        try {
            if (!$this->isPost()) {
                $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
                return;
            }

            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            
            $horario = $json['horario'] ?? $this->input('horario');
            
            if (empty($horario)) {
                $this->json(['success' => false, 'error' => 'Horário é obrigatório'], 400);
                return;
            }

            // Buscar solicitação atual
            $solicitacao = $this->solicitacaoModel->find($id);
            if (!$solicitacao) {
                $this->json(['success' => false, 'error' => 'Solicitação não encontrada'], 404);
                return;
            }

            // Buscar horários existentes
            $horariosExistentes = [];
            if (!empty($solicitacao['horarios_opcoes'])) {
                $horariosExistentes = json_decode($solicitacao['horarios_opcoes'], true) ?? [];
                if (!is_array($horariosExistentes)) {
                    $horariosExistentes = [];
                }
            }

            // Remover horário
            $horariosExistentes = array_filter($horariosExistentes, function($h) use ($horario) {
                return $h !== $horario;
            });
            $horariosExistentes = array_values($horariosExistentes); // Reindexar

            // Atualizar solicitação
            $this->solicitacaoModel->update($id, [
                'horarios_opcoes' => !empty($horariosExistentes) ? json_encode($horariosExistentes) : null
            ]);

            $this->json(['success' => true, 'message' => 'Horário removido com sucesso']);

        } catch (\Exception $e) {
            error_log('Erro em removerHorarioSeguradora: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erro ao remover horário: ' . $e->getMessage()], 500);
        } finally {
            while (ob_get_level()) {
                ob_end_clean();
            }
            error_reporting($oldErrorReporting);
            if ($oldDisplayErrors !== false) {
                ini_set('display_errors', $oldDisplayErrors);
            }
        }
    }

    /**
     * Confirma realização do serviço
     */
    public function confirmarServico(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $servicoRealizado = $this->input('servico_realizado');
        $prestadorCompareceu = $this->input('prestador_compareceu');
        $precisaComprarPecas = $this->input('precisa_comprar_pecas');
        $observacoes = $this->input('observacoes');
        $user = $this->getUser();

        try {
            $solicitacao = $this->solicitacaoModel->find($id);
            
            if (!$solicitacao) {
                $this->json(['error' => 'Solicitação não encontrada'], 404);
                return;
            }

            // Atualizar observações
            if (!empty($observacoes)) {
                $this->solicitacaoModel->update($id, [
                    'observacoes' => $observacoes
                ]);
            }

            // Montar mensagem de histórico
            $historico = "Confirmação de serviço:\n";
            $historico .= $servicoRealizado ? "✅ Serviço realizado\n" : "";
            $historico .= !$prestadorCompareceu ? "🚫 Prestador não compareceu\n" : "";
            $historico .= $precisaComprarPecas ? "🔧 Precisa comprar peças\n" : "";
            $historico .= $observacoes ? "📝 Obs: $observacoes" : "";
            
            // Registrar histórico
            $this->solicitacaoModel->updateStatus($id, $solicitacao['status_id'], $user['id'], $historico);

            // Enviar notificação WhatsApp
            $this->enviarNotificacaoWhatsApp($id, 'Confirmação de Serviço', [
                'horario_servico' => date('d/m/Y H:i', strtotime($solicitacao['data_agendamento']))
            ]);

            $this->json(['success' => true, 'message' => 'Confirmação registrada com sucesso']);
            
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function atualizarDetalhes(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $observacoes = $this->input('observacoes');
        $precisaReembolso = $this->input('precisa_reembolso');
        $valorReembolso = $this->input('valor_reembolso');
        $protocoloSeguradora = $this->input('protocolo_seguradora');
        $horariosIndisponiveis = $this->input('horarios_indisponiveis');
        
        // Tentar ler JSON cru (caso o front envie via fetch JSON)
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $horariosSeguradora = $json['horarios_seguradora'] ?? null;
        
        // ✅ Ler status_id e condicao_id do JSON ou do input
        $statusId = $json['status_id'] ?? $this->input('status_id');
        $condicaoId = $json['condicao_id'] ?? $this->input('condicao_id');
        
        $schedulesFromJson = null; // null = não foi enviado, array = foi enviado (pode ser vazio)
        $schedulesFoiEnviado = false;
        
        // Verificar se schedules foi enviado no JSON
        if (is_array($json) && array_key_exists('schedules', $json)) {
            $schedulesFromJson = is_array($json['schedules']) ? $json['schedules'] : [];
            $schedulesFoiEnviado = true;
        }
        
        // Também aceitar schedules por form (pode ser string JSON ou array já parseado)
        $schedulesForm = $this->input('schedules');
        if ($schedulesForm !== null && $schedulesForm !== '') {
            // ✅ Se já for array (do JSON parseado pelo Controller), usar diretamente
            if (is_array($schedulesForm)) {
                $schedulesFromJson = $schedulesForm;
                $schedulesFoiEnviado = true;
            } elseif (is_string($schedulesForm)) {
                // ✅ Se for string, tentar parsear
                $tmp = json_decode($schedulesForm, true);
                if (is_array($tmp)) {
                    $schedulesFromJson = $tmp;
                    $schedulesFoiEnviado = true;
                }
            }
        }

        try {
            // Buscar solicitação atual para preservar horários originais
            $solicitacaoAtual = $this->solicitacaoModel->find($id);
            if (!$solicitacaoAtual) {
                $this->json(['success' => false, 'error' => 'Solicitação não encontrada'], 404);
                return;
            }
            
            // ✅ Validação: Verificar se está tentando mudar para "Serviço Agendado" sem protocolo
            if ($statusId) {
                $sql = "SELECT nome FROM status WHERE id = ?";
                $status = \App\Core\Database::fetch($sql, [$statusId]);
                
                if ($status && $status['nome'] === 'Serviço Agendado') {
                    if (empty($protocoloSeguradora) || trim($protocoloSeguradora) === '') {
                        $this->json([
                            'success' => false,
                            'error' => 'É obrigatório preencher o protocolo da seguradora para mudar para "Serviço Agendado"',
                            'requires_protocol' => true
                        ], 400);
                        return;
                    }
                }
            }
            
            $dados = [
                'observacoes' => $observacoes
            ];
            
            // ✅ Adicionar status_id se foi alterado
            if ($statusId) {
                $dados['status_id'] = $statusId;
            }
            
            // ✅ Adicionar condicao_id se foi alterado
            if ($condicaoId !== null && $condicaoId !== '') {
                $condicaoIdValue = $condicaoId ?: null;
                // Verificar se a condição realmente mudou
                $condicaoAtual = $solicitacaoAtual['condicao_id'] ?? null;
                if ($condicaoAtual != $condicaoIdValue) {
                    $dados['condicao_id'] = $condicaoIdValue;
                }
            }

            // Adicionar protocolo se fornecido
            if ($protocoloSeguradora !== null && $protocoloSeguradora !== '') {
                $dados['protocolo_seguradora'] = $protocoloSeguradora;
            }

            // Adicionar campos de reembolso
            if ($precisaReembolso === true || $precisaReembolso === 'true' || $precisaReembolso === 1) {
                $dados['precisa_reembolso'] = 1;
                $valorConvertido = floatval($valorReembolso);
                $dados['valor_reembolso'] = $valorConvertido > 0 ? $valorConvertido : null;
            } else {
                $dados['precisa_reembolso'] = 0;
                $dados['valor_reembolso'] = null;
            }
            
            // Adicionar campo de horários indisponíveis
            // IMPORTANTE: Quando marcar horarios_indisponiveis, os horários do locatário são SUBSTITUÍDOS pelos da seguradora
            if ($horariosIndisponiveis === true || $horariosIndisponiveis === 'true' || $horariosIndisponiveis === 1) {
                $dados['horarios_indisponiveis'] = 1;
            } else {
                $dados['horarios_indisponiveis'] = 0;
            }
            
            // Processar horários da seguradora se foram enviados
            $horariosSeguradoraSalvos = false;
            $enviarNotificacaoHorariosIndisponiveis = false;
            if ($horariosSeguradora !== null && is_array($horariosSeguradora) && !empty($horariosSeguradora)) {
                try {
                    // IMPORTANTE: Quando admin adiciona horários da seguradora, deve SUBSTITUIR os horários do locatário
                    // horarios_opcoes passa a conter APENAS os horários da seguradora
                    $eraPrimeiraVez = empty($solicitacaoAtual['horarios_indisponiveis']);
                    
                    // Salvar horários da seguradora em horarios_opcoes (SUBSTITUINDO os horários do locatário)
                    $dados['horarios_opcoes'] = json_encode($horariosSeguradora);
                    $dados['horarios_indisponiveis'] = 1;
                    // Limpar confirmed_schedules e dados de agendamento quando admin substitui horários
                    if ($eraPrimeiraVez) {
                        // ✅ Preservar horários originais do locatário em datas_opcoes se ainda não estiverem lá
                        if (empty($solicitacaoAtual['datas_opcoes']) && !empty($solicitacaoAtual['horarios_opcoes'])) {
                            $dados['datas_opcoes'] = $solicitacaoAtual['horarios_opcoes'];
                        }
                        
                        $dados['confirmed_schedules'] = null;
                        $dados['horario_confirmado'] = 0;
                        $dados['horario_confirmado_raw'] = null;
                        $dados['data_agendamento'] = null;
                        $dados['horario_agendamento'] = null;
                    }
                    $horariosSeguradoraSalvos = true;
                    
                    // ✅ Se status é "Buscando Prestador", mudar condição para "Aguardando Locatário"
                    $sqlStatus = "SELECT nome FROM status WHERE id = ?";
                    $statusAtual = \App\Core\Database::fetch($sqlStatus, [$solicitacaoAtual['status_id']]);
                    $statusNome = $statusAtual['nome'] ?? '';
                    
                    if ($statusNome === 'Buscando Prestador') {
                        $condicaoModel = new \App\Models\Condicao();
                        $condicaoAguardando = $condicaoModel->findByNome('Aguardando Locatário');
                        
                        // Se não encontrar, buscar qualquer condição com "Aguardando" e "Locatário"
                        if (!$condicaoAguardando) {
                            $sqlCondicao = "SELECT * FROM condicoes WHERE (nome LIKE '%Aguardando%Locatário%' OR nome LIKE '%Aguardando Locatário%') AND status = 'ATIVO' LIMIT 1";
                            $condicaoAguardando = \App\Core\Database::fetch($sqlCondicao);
                        }
                        
                        if ($condicaoAguardando) {
                            $dados['condicao_id'] = $condicaoAguardando['id'];
                            error_log("DEBUG atualizarDetalhes [ID:{$id}] - ✅ Condição alterada para 'Aguardando Locatário' (ID: {$condicaoAguardando['id']})");
                        } else {
                            error_log("DEBUG atualizarDetalhes [ID:{$id}] - ⚠️ Condição 'Aguardando Locatário' não encontrada no banco de dados");
                        }
                    } else {
                        error_log("DEBUG atualizarDetalhes [ID:{$id}] - Status atual: '{$statusNome}' (não é 'Buscando Prestador')");
                    }
                    
                    // Se é a primeira vez marcando "Nenhum horário disponível" e há horários, enviar notificação
                    if ($eraPrimeiraVez) {
                        $enviarNotificacaoHorariosIndisponiveis = true;
                    }
                } catch (\Exception $e) {
                    error_log('Erro ao processar horários da seguradora: ' . $e->getMessage());
                    // Não bloquear o salvamento, apenas logar o erro
                }
            }

            // Debug log
            error_log('Dados recebidos: ' . json_encode([
                'id' => $id,
                'precisa_reembolso' => $precisaReembolso,
                'valor_reembolso_raw' => $valorReembolso,
                'valor_reembolso_convertido' => isset($dados['valor_reembolso']) ? $dados['valor_reembolso'] : 'null',
                'horarios_seguradora' => $horariosSeguradora !== null ? (is_array($horariosSeguradora) ? count($horariosSeguradora) : 'not array') : 'null',
                'dados_keys' => array_keys($dados)
            ]));

            // ✅ Se schedules foi enviado explicitamente (mesmo que vazio), processar confirmação
            // IMPORTANTE: schedulesFromJson contém apenas os horários MARCADOS (checked)
            // Se um horário estava confirmado e não está na lista, significa que foi DESMARCADO
            // IMPORTANTE: Só processar schedules se foi explicitamente enviado no JSON
            if ($schedulesFoiEnviado && $schedulesFromJson !== null) {
                // ✅ Buscar solicitação atual e horários disponíveis
                // Não buscar novamente se já foi buscado acima
                if (!isset($solicitacaoAtual)) {
                    $solicitacaoAtual = $this->solicitacaoModel->find($id);
                }
                
                // ✅ VALIDAÇÃO: Verificar se o status atual é "Serviço Agendado" antes de confirmar horários
                // Não é possível confirmar horários sem estar em "Serviço Agendado"
                $statusAtualId = $solicitacaoAtual['status_id'] ?? null;
                $statusAtualNome = '';
                if ($statusAtualId) {
                    $sqlStatusAtual = "SELECT nome FROM status WHERE id = ?";
                    $statusAtualData = \App\Core\Database::fetch($sqlStatusAtual, [$statusAtualId]);
                    $statusAtualNome = $statusAtualData['nome'] ?? '';
                }
                
                // Se está tentando confirmar horários (schedulesFromJson não está vazio)
                // e o status não é "Serviço Agendado" E não está mudando para "Serviço Agendado"
                if (!empty($schedulesFromJson) && $statusAtualNome !== 'Serviço Agendado') {
                    // Verificar se está mudando o status para "Serviço Agendado" neste mesmo update
                    $statusMudandoParaAgendado = false;
                    if (isset($dados['status_id'])) {
                        $sqlStatusNovo = "SELECT nome FROM status WHERE id = ?";
                        $statusNovoData = \App\Core\Database::fetch($sqlStatusNovo, [$dados['status_id']]);
                        $statusNovoNome = $statusNovoData['nome'] ?? '';
                        if ($statusNovoNome === 'Serviço Agendado') {
                            $statusMudandoParaAgendado = true;
                        }
                    }
                    
                    // Se não está mudando para "Serviço Agendado", bloquear
                    if (!$statusMudandoParaAgendado) {
                        $this->json([
                            'success' => false,
                            'error' => 'É necessário alterar o status para "Serviço Agendado" antes de confirmar horários. Por favor, altere o status primeiro.'
                        ], 400);
                        return;
                    }
                }
                
                $confirmedExistentes = [];
                
                if (!empty($solicitacaoAtual['confirmed_schedules'])) {
                    try {
                        $confirmedExistentes = json_decode($solicitacaoAtual['confirmed_schedules'], true) ?? [];
                        if (!is_array($confirmedExistentes)) {
                            $confirmedExistentes = [];
                        }
                    } catch (\Exception $e) {
                        $confirmedExistentes = [];
                    }
                }
                
                // ✅ Se schedulesFromJson está vazio (todos desmarcados), limpar todos os confirmados
                // IMPORTANTE: Só limpar se foi explicitamente enviado como array vazio
                if (is_array($schedulesFromJson) && empty($schedulesFromJson)) {
                    // Usuário desmarcou todos - limpar confirmações
                    $dados['horario_confirmado'] = 0;
                    $dados['horario_confirmado_raw'] = null;
                    $dados['data_agendamento'] = null;
                    $dados['horario_agendamento'] = null;
                    $dados['confirmed_schedules'] = json_encode([]);
                    // Voltar status para "Nova Solicitação" se estava agendado
                    try {
                        $statusNova = $this->getStatusId('Nova Solicitação');
                        if ($statusNova) {
                            $dados['status_id'] = $statusNova;
                        }
                    } catch (\Exception $e) {
                        // Ignorar erro de status, manter status atual
                    }
                } else if (!empty($schedulesFromJson)) {
                    // ✅ Processar horários selecionados (MARCADOS)
                    // IMPORTANTE: schedulesFromJson contém apenas os checkboxes MARCADOS
                    
                    // ✅ DEBUG: Log do que está sendo recebido
                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - schedulesFromJson recebido: " . json_encode($schedulesFromJson));
                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - confirmedExistentes: " . json_encode($confirmedExistentes));
                    
                    $confirmedFinal = [];
                    $rawsSelecionados = [];
                    
                    // 1. Coletar raws dos horários selecionados (REMOVER DUPLICATAS JÁ AQUI)
                    $rawsUnicos = [];
                    foreach ($schedulesFromJson as $s) {
                        $raw = trim($s['raw'] ?? trim(($s['date'] ?? '') . ' ' . ($s['time'] ?? '')));
                        $rawNorm = preg_replace('/\s+/', ' ', trim((string)$raw));
                        
                        // ✅ Verificar se já está na lista de únicos (evitar duplicatas no input)
                        $jaExiste = false;
                        foreach ($rawsUnicos as $rawUnico) {
                            if ($rawNorm === $rawUnico) {
                                $jaExiste = true;
                                break;
                            }
                        }
                        
                        if (!$jaExiste) {
                            $rawsUnicos[] = $rawNorm;
                            $rawsSelecionados[] = $rawNorm;
                        }
                    }
                    
                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - rawsSelecionados (após remover duplicatas): " . json_encode($rawsSelecionados));
                    
                    // ✅ Função auxiliar para normalizar e comparar horários de forma precisa
                    $normalizarHorario = function($raw) {
                        // Normalizar: remover espaços extras, padronizar formato
                        $raw = trim((string)$raw);
                        $raw = preg_replace('/\s+/', ' ', $raw); // Normalizar espaços múltiplos
                        return $raw;
                    };
                    
                    // ✅ Função auxiliar para comparar horários de forma precisa
                    $compararHorarios = function($raw1, $raw2) {
                        $raw1Norm = preg_replace('/\s+/', ' ', trim((string)$raw1));
                        $raw2Norm = preg_replace('/\s+/', ' ', trim((string)$raw2));
                        
                        // Comparação exata primeiro (mais precisa)
                        if ($raw1Norm === $raw2Norm) {
                            return true;
                        }
                        
                        // Comparação por regex - extrair data e hora inicial E FINAL EXATAS
                        // Formato esperado: "dd/mm/yyyy - HH:MM-HH:MM"
                        $regex = '/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})-(\d{2}:\d{2})/';
                        $match1 = preg_match($regex, $raw1Norm, $m1);
                        $match2 = preg_match($regex, $raw2Norm, $m2);
                        
                        if ($match1 && $match2) {
                            // ✅ Comparar data, hora inicial E hora final EXATAS (não apenas data e hora inicial)
                            // Isso garante que apenas horários EXATOS sejam considerados iguais
                            return ($m1[1] === $m2[1] && $m1[2] === $m2[2] && $m1[3] === $m2[3]);
                        }
                        
                        // Se não conseguir comparar por regex, retornar false (não é match)
                        return false;
                    };
                    
                    // 2. Para cada horário selecionado (usar rawsUnicos para evitar processar duplicatas)
                    // ✅ Usar array temporário para evitar duplicatas
                    $confirmedTemp = [];
                    $rawsProcessados = []; // ✅ Rastrear quais raws já foram processados
                    
                    // ✅ Processar apenas os horários únicos selecionados
                    foreach ($rawsSelecionados as $rawSelecionado) {
                        $rawNorm = $normalizarHorario($rawSelecionado);
                        
                        // ✅ Verificar se já processamos este raw (segunda camada de proteção)
                        if (in_array($rawNorm, $rawsProcessados, true)) {
                            error_log("DEBUG atualizarDetalhes [ID:{$id}] - ⚠️ Raw já processado, pulando: {$rawNorm}");
                            continue;
                        }
                        $rawsProcessados[] = $rawNorm;
                        
                        // ✅ Verificar se já existe nos confirmados existentes (comparação precisa)
                        $horarioExistente = null;
                        foreach ($confirmedExistentes as $existente) {
                            $existenteRaw = trim($existente['raw'] ?? '');
                            if ($compararHorarios($rawNorm, $existenteRaw)) {
                                $horarioExistente = $existente;
                                break;
                            }
                        }
                        
                        // ✅ Verificar se já está em confirmedTemp (evitar duplicatas no mesmo processamento)
                        $jaExisteNoTemp = false;
                        foreach ($confirmedTemp as $temp) {
                            $tempRaw = trim($temp['raw'] ?? '');
                            if ($compararHorarios($rawNorm, $tempRaw)) {
                                $jaExisteNoTemp = true;
                                break;
                            }
                        }
                        
                        // Se já existe no temp, pular (evitar duplicata)
                        if ($jaExisteNoTemp) {
                            error_log("DEBUG atualizarDetalhes [ID:{$id}] - ⚠️ Raw já existe no confirmedTemp, pulando: {$rawNorm}");
                            continue;
                        }
                        
                        // ✅ Buscar dados completos do scheduleFromJson para este raw
                        $scheduleData = null;
                        foreach ($schedulesFromJson as $s) {
                            $sRaw = trim($s['raw'] ?? trim(($s['date'] ?? '') . ' ' . ($s['time'] ?? '')));
                            $sRawNorm = $normalizarHorario($sRaw);
                            if ($compararHorarios($rawNorm, $sRawNorm)) {
                                $scheduleData = $s;
                                break;
                            }
                        }
                        
                        // Se existe nos confirmados existentes, manter (preserva confirmed_at original)
                        if ($horarioExistente) {
                            $confirmedTemp[] = $horarioExistente;
                            error_log("DEBUG atualizarDetalhes [ID:{$id}] - ✅ Horário existente preservado: {$rawNorm}");
                        } else {
                            // Se não existe, criar novo confirmado
                            $confirmedTemp[] = [
                                'date' => $scheduleData['date'] ?? null,
                                'time' => $scheduleData['time'] ?? null,
                                'raw'  => $rawNorm,
                                'source' => 'operator',
                                'confirmed_at' => date('c')
                            ];
                            error_log("DEBUG atualizarDetalhes [ID:{$id}] - ✅ Novo horário confirmado criado: {$rawNorm}");
                        }
                    }
                    
                    // ✅ Usar confirmedTemp como confirmedFinal (já sem duplicatas)
                    $confirmedFinal = $confirmedFinalLimpo = $confirmedTemp;
                    
                    // ✅ DEBUG: Log final antes de salvar
                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - confirmedFinal (antes de salvar): " . json_encode($confirmedFinal));
                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - Total de horários confirmados: " . count($confirmedFinal));
                    
                    // ✅ Se não há mais nenhum confirmado, limpar agendamento
                    if (empty($confirmedFinalLimpo)) {
                        $dados['horario_confirmado'] = 0;
                        $dados['horario_confirmado_raw'] = null;
                        $dados['data_agendamento'] = null;
                        $dados['horario_agendamento'] = null;
                        $dados['confirmed_schedules'] = json_encode([]);
                        // Voltar status para "Nova Solicitação"
                        try {
                            $statusNova = $this->getStatusId('Nova Solicitação');
                            if ($statusNova) {
                                $dados['status_id'] = $statusNova;
                            }
                        } catch (\Exception $e) {
                            // Ignorar erro de status, manter status atual
                        }
                    } else {
                        // ✅ Último horário vira o agendamento principal
                        $last = end($confirmedFinalLimpo);
                        $dataAg = (!empty($last['date'])) ? date('Y-m-d', strtotime($last['date'])) : null;
                        $horaRaw = $last['time'] ?? '';
                        $horaAg = preg_match('/^\d{2}:\d{2}/', $horaRaw, $m) ? ($m[0] . ':00') : (!empty($horaRaw) ? $horaRaw : null);

                        $dados['data_agendamento'] = $dataAg;
                        $dados['horario_agendamento'] = $horaAg;
                        $dados['horario_confirmado'] = 1;
                        $dados['horario_confirmado_raw'] = $last['raw'];
                        $dados['confirmed_schedules'] = json_encode($confirmedFinalLimpo);
                        
                        // ✅ Só mudar status para "Serviço Agendado" se o usuário não alterou manualmente o status
                        // Verificar se o usuário já definiu um status_id manualmente antes de forçar "Serviço Agendado"
                        $statusIdManual = $statusId ?? null; // status_id que o usuário escolheu no select
                        if (empty($statusIdManual)) {
                            // Se não foi definido manualmente, mudar para "Serviço Agendado"
                            $dados['status_id'] = $this->getStatusId('Serviço Agendado');
                            error_log("DEBUG atualizarDetalhes [ID:{$id}] - Status alterado automaticamente para 'Serviço Agendado' (há horários confirmados)");
                            
                            // ✅ Quando muda para "Serviço Agendado", atualizar condição para "Agendamento Confirmado"
                            $condicaoModel = new \App\Models\Condicao();
                            $condicaoConfirmada = $condicaoModel->findByNome('Agendamento Confirmado');
                            if (!$condicaoConfirmada) {
                                $condicaoConfirmada = $condicaoModel->findByNome('Data Aceita pelo Prestador');
                            }
                            if (!$condicaoConfirmada) {
                                $sqlCondicao = "SELECT * FROM condicoes WHERE (nome LIKE '%Agendamento Confirmado%' OR nome LIKE '%Data Aceita pelo Prestador%') AND status = 'ATIVO' LIMIT 1";
                                $condicaoConfirmada = \App\Core\Database::fetch($sqlCondicao);
                            }
                            
                            if ($condicaoConfirmada) {
                                $dados['condicao_id'] = $condicaoConfirmada['id'];
                                error_log("DEBUG atualizarDetalhes [ID:{$id}] - ✅ Condição alterada para 'Agendamento Confirmado' (ID: {$condicaoConfirmada['id']})");
                            } else {
                                error_log("DEBUG atualizarDetalhes [ID:{$id}] - ⚠️ Condição 'Agendamento Confirmado' não encontrada no banco de dados");
                            }
                        } else {
                            // Se foi definido manualmente, manter o status escolhido pelo usuário
                            $dados['status_id'] = $statusIdManual;
                            error_log("DEBUG atualizarDetalhes [ID:{$id}] - Status mantido pelo usuário: " . $statusIdManual);
                            
                            // ✅ Se o status manual escolhido for "Serviço Agendado", também atualizar condição
                            $sqlStatusManual = "SELECT nome FROM status WHERE id = ?";
                            $statusManual = \App\Core\Database::fetch($sqlStatusManual, [$statusIdManual]);
                            if ($statusManual && $statusManual['nome'] === 'Serviço Agendado') {
                                $condicaoModel = new \App\Models\Condicao();
                                $condicaoConfirmada = $condicaoModel->findByNome('Agendamento Confirmado');
                                if (!$condicaoConfirmada) {
                                    $condicaoConfirmada = $condicaoModel->findByNome('Data Aceita pelo Prestador');
                                }
                                if (!$condicaoConfirmada) {
                                    $sqlCondicao = "SELECT * FROM condicoes WHERE (nome LIKE '%Agendamento Confirmado%' OR nome LIKE '%Data Aceita pelo Prestador%') AND status = 'ATIVO' LIMIT 1";
                                    $condicaoConfirmada = \App\Core\Database::fetch($sqlCondicao);
                                }
                                
                                if ($condicaoConfirmada) {
                                    $dados['condicao_id'] = $condicaoConfirmada['id'];
                                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - ✅ Condição alterada para 'Agendamento Confirmado' (status manual: Serviço Agendado)");
                                }
                            }
                        }
                        
                        // ✅ Enviar notificação WhatsApp quando horários são adicionados pelo admin
                        try {
                            // Buscar dados atualizados da solicitação para garantir que temos o telefone correto
                            $solicitacaoAtual = $this->solicitacaoModel->find($id);
                            
                            // Verificar se tem telefone antes de enviar
                            $telefone = $solicitacaoAtual['locatario_telefone'] ?? null;
                            if (empty($telefone) && !empty($solicitacaoAtual['locatario_id'])) {
                                // Buscar telefone do locatário
                                $sqlLocatario = "SELECT telefone FROM locatarios WHERE id = ?";
                                $locatario = \App\Core\Database::fetch($sqlLocatario, [$solicitacaoAtual['locatario_id']]);
                                $telefone = $locatario['telefone'] ?? null;
                            }
                            
                            if (!empty($telefone)) {
                                // Identificar horários NOVOS adicionados pelo admin (não os que já existiam)
                                $horariosNovos = [];
                                foreach ($confirmedFinalLimpo as $confirmado) {
                                    $confirmadoRaw = $confirmado['raw'] ?? '';
                                    $source = $confirmado['source'] ?? 'operator';
                                    $jaExistia = false;
                                    
                                    // Verificar se este horário já estava confirmado antes
                                    foreach ($confirmedExistentes as $existente) {
                                        $existenteRaw = $existente['raw'] ?? '';
                                        // Comparação normalizada
                                        $raw1Norm = preg_replace('/\s+/', ' ', trim($confirmadoRaw));
                                        $raw2Norm = preg_replace('/\s+/', ' ', trim($existenteRaw));
                                        if ($raw1Norm === $raw2Norm) {
                                            $jaExistia = true;
                                            break;
                                        }
                                    }
                                    
                                    // Se é um horário novo E foi adicionado pelo admin (source='operator' ou não tem source definido)
                                    if (!$jaExistia && ($source === 'operator' || empty($confirmado['source']))) {
                                        $horariosNovos[] = $confirmado;
                                    }
                                }
                                
                                // Se há horários novos adicionados pelo admin, enviar notificação "Horário Sugerido"
                                // ✅ Não enviar "Horário Sugerido" se o status for ou estiver mudando para "Serviço Agendado"
                                $statusAtualNome = '';
                                $statusAnteriorNome = '';
                                
                                // Buscar status atual (novo)
                                if (isset($dados['status_id'])) {
                                    $sqlStatusAtual = "SELECT nome FROM status WHERE id = ?";
                                    $statusAtual = \App\Core\Database::fetch($sqlStatusAtual, [$dados['status_id']]);
                                    $statusAtualNome = $statusAtual['nome'] ?? '';
                                } else {
                                    $sqlStatusAtual = "SELECT nome FROM status WHERE id = ?";
                                    $statusAtual = \App\Core\Database::fetch($sqlStatusAtual, [$solicitacaoAtual['status_id']]);
                                    $statusAtualNome = $statusAtual['nome'] ?? '';
                                }
                                
                                // Buscar status anterior
                                $sqlStatusAnterior = "SELECT nome FROM status WHERE id = ?";
                                $statusAnterior = \App\Core\Database::fetch($sqlStatusAnterior, [$solicitacaoAtual['status_id']]);
                                $statusAnteriorNome = $statusAnterior['nome'] ?? '';
                                
                                // Não enviar "Horário Sugerido" se:
                                // 1. Status atual for "Serviço Agendado"
                                // 2. Status anterior for "Buscando Prestador" e status atual for "Serviço Agendado"
                                $naoEnviarHorarioSugerido = ($statusAtualNome === 'Serviço Agendado') || 
                                                             ($statusAnteriorNome === 'Buscando Prestador' && $statusAtualNome === 'Serviço Agendado');
                                
                                if (!empty($horariosNovos) && !$naoEnviarHorarioSugerido) {
                                    // Formatar lista de horários para a mensagem
                                    $horariosLista = [];
                                    foreach ($horariosNovos as $horarioNovo) {
                                        $raw = $horarioNovo['raw'] ?? '';
                                        // Remover segundos se houver (qualquer segundo, não apenas :00)
                                        $raw = preg_replace('/(\d{2}:\d{2}):\d{2}-(\d{2}:\d{2}):\d{2}/', '$1-$2', $raw);
                                        $horariosLista[] = $raw;
                                    }
                                    $horariosTexto = implode(', ', $horariosLista);
                                    
                                    // Extrair data e horário do primeiro horário novo para a mensagem
                                    $primeiroHorario = $horariosNovos[0] ?? null;
                                    $dataAgendamento = '';
                                    $horarioAgendamento = '';
                                    
                                    if ($primeiroHorario) {
                                        // Tentar extrair do campo 'date' e 'time'
                                        if (!empty($primeiroHorario['date'])) {
                                            // Converter de YYYY-MM-DD para dd/mm/YYYY
                                            $dataObj = \DateTime::createFromFormat('Y-m-d', $primeiroHorario['date']);
                                            if ($dataObj) {
                                                $dataAgendamento = $dataObj->format('d/m/Y');
                                            } else {
                                                // Tentar formato dd/mm/yyyy
                                                $dataObj = \DateTime::createFromFormat('d/m/Y', $primeiroHorario['date']);
                                                if ($dataObj) {
                                                    $dataAgendamento = $primeiroHorario['date'];
                                                }
                                            }
                                        }
                                        
                                        if (!empty($primeiroHorario['time'])) {
                                            $horarioAgendamento = $primeiroHorario['time'];
                                            // Remover segundos se houver (qualquer segundo, não apenas :00)
                                            $horarioAgendamento = preg_replace('/(\d{2}:\d{2}):\d{2}-(\d{2}:\d{2}):\d{2}/', '$1-$2', $horarioAgendamento);
                                        }
                                        
                                        // Se não conseguiu extrair de 'date' e 'time', tentar extrair do 'raw'
                                        if (empty($dataAgendamento) || empty($horarioAgendamento)) {
                                            $raw = $primeiroHorario['raw'] ?? '';
                                            // Formato esperado: "dd/mm/yyyy - HH:MM-HH:MM" ou "dd/mm/yyyy - HH:MM:SS-HH:MM:SS"
                                            if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})(?::\d{2})?-(\d{2}:\d{2})(?::\d{2})?/', $raw, $matches)) {
                                                $dataAgendamento = $matches[1];
                                                // Remover segundos se houver
                                                $horaInicio = preg_replace('/:\d{2}$/', '', $matches[2]);
                                                $horaFim = preg_replace('/:\d{2}$/', '', $matches[3]);
                                                $horarioAgendamento = $horaInicio . '-' . $horaFim;
                                            }
                                        }
                                    }
                                    
                                    // Enviar notificação "Horário Sugerido" para o locatário escolher
                                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - Primeiro horário novo: " . json_encode($primeiroHorario));
                                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - Data extraída: " . $dataAgendamento);
                                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - Horário extraído: " . $horarioAgendamento);
                                    
                                    $this->enviarNotificacaoWhatsApp($id, 'Horário Sugerido', [
                                        'horarios_sugeridos' => $horariosTexto,
                                        'data_agendamento' => $dataAgendamento,
                                        'horario_agendamento' => $horarioAgendamento
                                    ]);
                                    
                                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - ✅ Notificação 'Horário Sugerido' enviada para telefone: {$telefone}");
                                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - Horários novos adicionados pelo admin: " . json_encode($horariosNovos));
                                } else {
                                    error_log("DEBUG atualizarDetalhes [ID:{$id}] - Nenhum horário novo adicionado pelo admin, notificação NÃO enviada");
                                }
                            } else {
                                error_log("DEBUG atualizarDetalhes [ID:{$id}] - ⚠️ Telefone não encontrado, WhatsApp NÃO enviado");
                            }
                        } catch (\Exception $e) {
                            // Ignorar erro de WhatsApp, não bloquear a resposta
                            error_log('Erro ao enviar WhatsApp no atualizarDetalhes [ID:' . $id . ']: ' . $e->getMessage());
                            error_log('Stack trace: ' . $e->getTraceAsString());
                        }
                    }
                }
            }

            // Debug: Log dos dados antes de atualizar
            error_log('Dados finais antes de atualizar: ' . json_encode($dados));
            
            try {
                $resultado = $this->solicitacaoModel->update($id, $dados);
                
                if ($resultado) {
                    $user = $this->getUser();
                    
                    // ✅ Registrar no histórico e enviar WhatsApp se status foi alterado
                    if (isset($dados['status_id']) && $dados['status_id'] != $solicitacaoAtual['status_id']) {
                        $observacaoStatus = 'Status alterado via detalhes da solicitação';
                        if (isset($dados['observacoes']) && !empty($dados['observacoes'])) {
                            $observacaoStatus .= '. ' . $dados['observacoes'];
                        }
                        $this->solicitacaoModel->updateStatus($id, $dados['status_id'], $user['id'] ?? null, $observacaoStatus);
                        
                        // ✅ Enviar notificação WhatsApp de mudança de status
                        try {
                            $sql = "SELECT nome FROM status WHERE id = ?";
                            $status = \App\Core\Database::fetch($sql, [$dados['status_id']]);
                            $statusNome = $status['nome'] ?? 'Atualizado';
                            
                            // Se mudou para "Serviço Agendado", enviar "Horário Confirmado" em vez de "Atualização de Status"
                            if ($statusNome === 'Serviço Agendado') {
                                // Buscar dados de agendamento da solicitação
                                $solicitacaoAtualizada = $this->solicitacaoModel->find($id);
                                $dataAgendamento = $solicitacaoAtualizada['data_agendamento'] ?? null;
                                $horarioAgendamento = $solicitacaoAtualizada['horario_agendamento'] ?? null;
                                $horarioConfirmadoRaw = $solicitacaoAtualizada['horario_confirmado_raw'] ?? null;
                                
                                // Extrair intervalo completo do horário (formato: "08:00 às 11:00")
                                $horarioIntervalo = $this->extrairIntervaloHorario($horarioConfirmadoRaw, $horarioAgendamento, $solicitacaoAtualizada);
                                
                                // Formatar horário completo
                                $horarioCompleto = '';
                                if ($dataAgendamento && $horarioIntervalo) {
                                    $dataFormatada = date('d/m/Y', strtotime($dataAgendamento));
                                    $horarioCompleto = $dataFormatada . ' - ' . $horarioIntervalo;
                                }
                                
                                $this->enviarNotificacaoWhatsApp($id, 'Horário Confirmado', [
                                    'data_agendamento' => $dataAgendamento ? date('d/m/Y', strtotime($dataAgendamento)) : '',
                                    'horario_agendamento' => $horarioIntervalo, // ✅ Usar intervalo completo
                                    'horario_servico' => $horarioCompleto
                                ]);
                                
                                error_log("WhatsApp de horário confirmado enviado [ID:{$id}] - Status: Serviço Agendado - Horário: {$horarioIntervalo}");
                            } else {
                                // Para outros status, enviar "Atualização de Status"
                                // ✅ Não enviar WhatsApp quando mudar para "Buscando Prestador"
                                if ($statusNome !== 'Buscando Prestador') {
                                    $this->enviarNotificacaoWhatsApp($id, 'Atualização de Status', [
                                        'status_atual' => $statusNome
                                    ]);
                                    
                                    error_log("WhatsApp de atualização de status enviado [ID:{$id}] - Novo status: " . $statusNome);
                                } else {
                                    error_log("WhatsApp NÃO enviado [ID:{$id}] - Status mudou para 'Buscando Prestador' (sem notificação)");
                                }
                            }
                        } catch (\Exception $e) {
                            error_log('Erro ao enviar WhatsApp de atualização de status [ID:' . $id . ']: ' . $e->getMessage());
                            // Não bloquear o salvamento se falhar o WhatsApp
                        }
                    }
                    
                    // ✅ Registrar no histórico se condição foi alterada
                    if (isset($dados['condicao_id'])) {
                        $condicaoAtual = $solicitacaoAtual['condicao_id'] ?? null;
                        if ($dados['condicao_id'] != $condicaoAtual) {
                            $observacaoCondicao = isset($dados['observacoes']) && !empty($dados['observacoes']) 
                                ? $dados['observacoes'] 
                                : null;
                            $this->solicitacaoModel->registrarMudancaCondicao($id, $dados['condicao_id'], $user['id'] ?? null, $observacaoCondicao);
                        }
                    }
                    
                    // Enviar WhatsApp se horários da seguradora foram salvos E é a primeira vez marcando "Nenhum horário disponível"
                    if ($enviarNotificacaoHorariosIndisponiveis && $horariosSeguradoraSalvos && !empty($horariosSeguradora)) {
                        try {
                            // Buscar solicitação atualizada para obter dados completos
                            $solicitacaoAtualizada = $this->solicitacaoModel->find($id);
                            
                            // Formatar horários para exibição
                            $horariosTexto = [];
                            foreach ($horariosSeguradora as $horario) {
                                // Extrair data e horário do formato "dd/mm/yyyy - HH:MM-HH:MM" ou "dd/mm/yyyy - HH:MM:SS-HH:MM:SS"
                                if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})(?::\d{2})?-(\d{2}:\d{2})(?::\d{2})?/', $horario, $matches)) {
                                    // Remover segundos se houver
                                    $horaInicio = preg_replace('/:\d{2}$/', '', $matches[2]);
                                    $horaFim = preg_replace('/:\d{2}$/', '', $matches[3]);
                                    $horariosTexto[] = $matches[1] . ' das ' . $horaInicio . ' às ' . $horaFim;
                                } else {
                                    $horariosTexto[] = $horario;
                                }
                            }
                            
                            // Usar o primeiro horário para data e horário de agendamento
                            $primeiroHorario = $horariosSeguradora[0] ?? '';
                            $dataAgendamento = '';
                            $horarioAgendamento = '';
                            
                            // Aceitar formato com ou sem segundos: "dd/mm/yyyy - HH:MM-HH:MM" ou "dd/mm/yyyy - HH:MM:SS-HH:MM:SS"
                            if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})(?::\d{2})?-(\d{2}:\d{2})(?::\d{2})?/', $primeiroHorario, $matches)) {
                                $dataAgendamento = $matches[1];
                                // Remover segundos se houver
                                $horaInicio = preg_replace('/:\d{2}$/', '', $matches[2]);
                                $horaFim = preg_replace('/:\d{2}$/', '', $matches[3]);
                                $horarioAgendamento = $horaInicio . '-' . $horaFim;
                            }
                            
                            // Enviar WhatsApp com horários sugeridos pela seguradora (template "Horário Sugerido" com link para escolher)
                            error_log("DEBUG horários seguradora [ID:{$id}] - Primeiro horário: " . $primeiroHorario);
                            error_log("DEBUG horários seguradora [ID:{$id}] - Data extraída: " . $dataAgendamento);
                            error_log("DEBUG horários seguradora [ID:{$id}] - Horário extraído: " . $horarioAgendamento);
                            
                            $this->enviarNotificacaoWhatsApp($id, 'Horário Sugerido', [
                                'data_agendamento' => $dataAgendamento,
                                'horario_agendamento' => $horarioAgendamento,
                                'horarios_sugeridos' => implode(', ', $horariosTexto)
                            ]);
                            
                            error_log("WhatsApp enviado para horários indisponíveis [ID:{$id}]: " . count($horariosSeguradora) . " horários sugeridos");
                        } catch (\Exception $e) {
                            // Ignorar erro de WhatsApp, não bloquear a resposta
                            error_log('Erro ao enviar WhatsApp para horários indisponíveis [ID:' . $id . ']: ' . $e->getMessage());
                        }
                    }
                    
                    $this->json([
                        'success' => true, 
                        'message' => 'Alterações salvas com sucesso',
                        'dados_salvos' => $dados
                    ]);
                } else {
                    error_log('Erro: update() retornou false');
                    $this->json(['success' => false, 'error' => 'Falha ao atualizar no banco de dados'], 500);
                }
            } catch (\Exception $e) {
                error_log('Erro no update(): ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e; // Re-lançar para ser capturado pelo catch externo
            }
        } catch (\Exception $e) {
            error_log('Erro ao salvar: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            // ✅ Garantir que sempre retorne JSON válido
            $this->json([
                'success' => false,
                'error' => 'Erro ao salvar alterações: ' . $e->getMessage(),
                'message' => 'Ocorreu um erro ao processar sua solicitação. Tente novamente.'
            ], 500);
        } catch (\Throwable $e) {
            // ✅ Capturar qualquer erro PHP (fatal errors, etc.)
            error_log('Erro fatal ao salvar: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->json([
                'success' => false,
                'error' => 'Erro inesperado',
                'message' => 'Ocorreu um erro inesperado. Tente novamente.'
            ], 500);
        }
    }
    
    // ============================================================
    // SOLICITAÇÕES MANUAIS
    // ============================================================
    
    /**
     * Listar todas as solicitações manuais
     */
    public function solicitacoesManuais(): void
    {
        $this->requireAuth();
        
        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
        
        // Filtros
        $inputMigrada = $this->input('migrada');
        
        // Por padrão, mostrar apenas solicitações não migradas (as migradas não devem aparecer)
        // Se o usuário selecionar "Migradas" (1), mostrar apenas migradas
        // Se o usuário selecionar "Aguardando Migração" (0) ou "Todas" (vazio), mostrar apenas não migradas
        $filtroMigrada = false; // Por padrão: apenas não migradas
        
        if ($inputMigrada === '1') {
            // Usuário quer ver apenas as migradas
            $filtroMigrada = true;
        } elseif ($inputMigrada === '0' || $inputMigrada === '' || $inputMigrada === null) {
            // "Aguardando Migração" ou "Todas" ou primeira carga: mostrar apenas não migradas
            $filtroMigrada = false;
        }
        
        $filtros = [
            'imobiliaria_id' => $this->input('imobiliaria_id'),
            'status_id' => $this->input('status_id'),
            'migrada' => $filtroMigrada,
            'busca' => $this->input('busca')
        ];
        
        // Filtros para passar à view (com valor original do input)
        $filtrosView = [
            'imobiliaria_id' => $this->input('imobiliaria_id'),
            'status_id' => $this->input('status_id'),
            'migrada_input' => $inputMigrada, // Valor original para o select
            'busca' => $this->input('busca')
        ];
        
        // Remover filtros vazios para a query (mas manter 'migrada' sempre)
        $filtrosFinais = [];
        foreach ($filtros as $key => $value) {
            if ($key === 'migrada') {
                // Sempre incluir o filtro de migrada
                $filtrosFinais[$key] = $value;
            } elseif ($value !== null && $value !== '') {
                $filtrosFinais[$key] = $value;
            }
        }
        
        // Buscar solicitações filtradas para a lista
        $solicitacoes = $solicitacaoManualModel->getAll($filtrosFinais);
        
        // Calcular validação de utilização para cada solicitação
        $categoriaModel = new \App\Models\Categoria();
        foreach ($solicitacoes as &$solicitacao) {
            $validacaoUtilizacao = null; // null = não verificado / sem dados suficientes
            if (!empty($solicitacao['cpf']) && !empty($solicitacao['imobiliaria_id']) && !empty($solicitacao['categoria_id'])) {
                $categoria = $categoriaModel->find((int)$solicitacao['categoria_id']);
                
                if ($categoria) {
                    $limite = $categoria['limite_solicitacoes_12_meses'] ?? null;
                    
                    // Se houver limite configurado, usar a contagem por CPF para validar utilização
                    if ($limite !== null && (int)$limite > 0) {
                        $verificacaoCpf = $solicitacaoManualModel->verificarQuantidadePorCPF(
                            $solicitacao['cpf'],
                            (int)$solicitacao['imobiliaria_id'],
                            (int)$solicitacao['categoria_id']
                        );
                        
                        $quantidade12Meses = (int)($verificacaoCpf['quantidade_12_meses'] ?? 0);
                        
                        // Aprovado se ainda não atingiu o limite
                        $validacaoUtilizacao = $quantidade12Meses < (int)$limite ? 1 : 0;
                    } else {
                        // Sem limite definido na categoria → considerar como aprovado
                        $validacaoUtilizacao = 1;
                    }
                }
            }
            $solicitacao['validacao_utilizacao'] = $validacaoUtilizacao;
        }
        unset($solicitacao); // Limpar referência
        
        // Buscar imobiliárias e status para os filtros
        $imobiliarias = $this->imobiliariaModel->getAll();
        $statusList = $this->statusModel->getAll();
        
        // Calcular estatísticas baseadas em todas as solicitações (sem filtro de migrada)
        $filtrosStats = $filtrosFinais;
        unset($filtrosStats['migrada']); // Remover filtro de migrada para contar todas
        $todasSolicitacoes = $solicitacaoManualModel->getAll($filtrosStats);
        
        // Estatísticas (baseadas em todas as solicitações)
        $stats = [
            'total' => count($todasSolicitacoes),
            'nao_migradas' => count(array_filter($todasSolicitacoes, fn($s) => empty($s['migrada']) || $s['migrada'] == 0)),
            'migradas' => count(array_filter($todasSolicitacoes, fn($s) => !empty($s['migrada']) && $s['migrada'] == 1))
        ];
        
        $this->view('solicitacoes.manuais', [
            'solicitacoes' => $solicitacoes,
            'imobiliarias' => $imobiliarias,
            'statusList' => $statusList,
            'stats' => $stats,
            'filtros' => $filtrosView
        ]);
    }
    
    /**
     * API: Retornar contagem de solicitações manuais não migradas (para atualização automática do badge)
     */
    public function apiContagemManuais(): void
    {
        $this->requireAuth();
        
        try {
            $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
            $naoMigradas = count($solicitacaoManualModel->getNaoMigradas(999));
            
            $this->json([
                'success' => true,
                'contagem' => $naoMigradas
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'contagem' => 0,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Retornar lista de solicitações manuais (para atualização automática da tabela)
     */
    public function apiListaManuais(): void
    {
        $this->requireAuth();
        
        try {
            $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
            
            // Filtros
            $inputMigrada = $this->input('migrada');
            $filtroMigrada = false;
            
            if ($inputMigrada === '1') {
                $filtroMigrada = true;
            }
            
            $filtros = [
                'imobiliaria_id' => $this->input('imobiliaria_id'),
                'status_id' => $this->input('status_id'),
                'migrada' => $filtroMigrada,
                'busca' => $this->input('busca')
            ];
            
            // Remover filtros vazios
            $filtrosFinais = [];
            foreach ($filtros as $key => $value) {
                if ($key === 'migrada') {
                    $filtrosFinais[$key] = $value;
                } elseif ($value !== null && $value !== '') {
                    $filtrosFinais[$key] = $value;
                }
            }
            
            // Buscar solicitações
            $solicitacoes = $solicitacaoManualModel->getAll($filtrosFinais);
            
            // Calcular validação de utilização
            $categoriaModel = new \App\Models\Categoria();
            foreach ($solicitacoes as &$solicitacao) {
                $validacaoUtilizacao = null;
                if (!empty($solicitacao['cpf']) && !empty($solicitacao['imobiliaria_id']) && !empty($solicitacao['categoria_id'])) {
                    $categoria = $categoriaModel->find((int)$solicitacao['categoria_id']);
                    
                    if ($categoria) {
                        $limite = $categoria['limite_solicitacoes_12_meses'] ?? null;
                        
                        if ($limite !== null && (int)$limite > 0) {
                            $verificacaoCpf = $solicitacaoManualModel->verificarQuantidadePorCPF(
                                $solicitacao['cpf'],
                                (int)$solicitacao['imobiliaria_id'],
                                (int)$solicitacao['categoria_id']
                            );
                            
                            $quantidade12Meses = (int)($verificacaoCpf['quantidade_12_meses'] ?? 0);
                            $validacaoUtilizacao = $quantidade12Meses < (int)$limite ? 1 : 0;
                        } else {
                            $validacaoUtilizacao = 1;
                        }
                    }
                }
                $solicitacao['validacao_utilizacao'] = $validacaoUtilizacao;
            }
            unset($solicitacao);
            
            // Estatísticas
            $filtrosStats = $filtrosFinais;
            unset($filtrosStats['migrada']);
            $todasSolicitacoes = $solicitacaoManualModel->getAll($filtrosStats);
            
            $stats = [
                'total' => count($todasSolicitacoes),
                'nao_migradas' => count(array_filter($todasSolicitacoes, fn($s) => empty($s['migrada']) || $s['migrada'] == 0)),
                'migradas' => count(array_filter($todasSolicitacoes, fn($s) => !empty($s['migrada']) && $s['migrada'] == 1))
            ];
            
            $this->json([
                'success' => true,
                'solicitacoes' => $solicitacoes,
                'stats' => $stats,
                'contagem' => $stats['nao_migradas']
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Exibir formulário para criar nova solicitação manual (admin)
     */
    public function novaSolicitacaoManual(): void
    {
        $this->requireAuth();
        
        // Buscar dados necessários para o formulário
        $imobiliarias = $this->imobiliariaModel->getAll();
        $categoriaModel = new \App\Models\Categoria();
        $subcategoriaModel = new \App\Models\Subcategoria();
        $categorias = $categoriaModel->getAtivas();
        $subcategorias = $subcategoriaModel->getAtivas();
        $statusList = $this->statusModel->getAll();
        
        // Organizar subcategorias por categoria
        foreach ($categorias as $key => $categoria) {
            $categorias[$key]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoria) {
                return $sub['categoria_id'] == $categoria['id'];
            }));
        }
        
        $this->view('solicitacoes.nova-manual', [
            'imobiliarias' => $imobiliarias,
            'categorias' => $categorias,
            'subcategorias' => $subcategorias,
            'statusList' => $statusList
        ]);
    }
    
    /**
     * Buscar dados do locatário por CPF (AJAX)
     */
    public function buscarLocatarioPorCpf(): void
    {
        $this->requireAuth();
        
        $cpf = preg_replace('/\D/', '', $this->input('cpf'));
        $imobiliariaId = $this->input('imobiliaria_id');
        
        if (empty($cpf) || empty($imobiliariaId)) {
            $this->json(['success' => false, 'error' => 'CPF e Imobiliária são obrigatórios'], 400);
            return;
        }
        
        $locatarioModel = new \App\Models\Locatario();
        $locatario = $locatarioModel->findByCpfAndImobiliaria($cpf, $imobiliariaId);
        
        if ($locatario) {
            // Buscar contrato se houver
            $contrato = null;
            if (!empty($locatario['ksi_cliente_id'])) {
                $sql = "SELECT * FROM locatarios_contratos WHERE imobiliaria_id = ? AND cpf = ? LIMIT 1";
                $contrato = \App\Core\Database::fetch($sql, [$imobiliariaId, $cpf]);
            }
            
            $this->json([
                'success' => true,
                'exists' => true,
                'data' => [
                    'nome' => $locatario['nome'] ?? '',
                    'whatsapp' => $locatario['whatsapp'] ?? '',
                    'telefone' => $locatario['telefone'] ?? '',
                    'email' => $locatario['email'] ?? '',
                    'endereco_logradouro' => $locatario['endereco_logradouro'] ?? '',
                    'endereco_numero' => $locatario['endereco_numero'] ?? '',
                    'endereco_complemento' => $locatario['endereco_complemento'] ?? '',
                    'endereco_bairro' => $locatario['endereco_bairro'] ?? '',
                    'endereco_cidade' => $locatario['endereco_cidade'] ?? '',
                    'endereco_estado' => $locatario['endereco_estado'] ?? '',
                    'endereco_cep' => $locatario['endereco_cep'] ?? '',
                    'numero_contrato' => $contrato['numero_contrato'] ?? ''
                ]
            ]);
        } else {
            $this->json([
                'success' => true,
                'exists' => false
            ]);
        }
    }
    
    /**
     * Processar criação de nova solicitação manual (admin)
     */
    public function criarSolicitacaoManual(): void
    {
        $this->requireAuth();
        
        try {
            // Validar dados obrigatórios
            $dados = [
                'imobiliaria_id' => $this->input('imobiliaria_id'),
                'nome_completo' => trim($this->input('nome_completo')),
                'cpf' => preg_replace('/\D/', '', $this->input('cpf')),
                'whatsapp' => trim($this->input('whatsapp')),
                'tipo_imovel' => $this->input('tipo_imovel'),
                'subtipo_imovel' => $this->input('subtipo_imovel'),
                'cep' => preg_replace('/\D/', '', $this->input('cep')),
                'endereco' => trim($this->input('endereco')),
                'numero' => trim($this->input('numero')),
                'complemento' => trim($this->input('complemento')),
                'bairro' => trim($this->input('bairro')),
                'cidade' => trim($this->input('cidade')),
                'estado' => trim($this->input('estado')),
                'categoria_id' => $this->input('categoria_id'),
                'subcategoria_id' => $this->input('subcategoria_id'),
                'descricao_problema' => trim($this->input('descricao_problema')),
                'numero_contrato' => trim($this->input('numero_contrato')) ?: null,
                'local_manutencao' => trim($this->input('local_manutencao')),
                'status_id' => $this->input('status_id'),
                'termos_aceitos' => true // Admin sempre aceita
            ];
            
            // Validar campos obrigatórios
            $camposObrigatorios = [
                'imobiliaria_id' => 'Imobiliária',
                'nome_completo' => 'Nome completo',
                'cpf' => 'CPF',
                'whatsapp' => 'WhatsApp',
                'tipo_imovel' => 'Tipo de imóvel',
                'cep' => 'CEP',
                'endereco' => 'Endereço',
                'numero' => 'Número',
                'bairro' => 'Bairro',
                'cidade' => 'Cidade',
                'estado' => 'Estado',
                'categoria_id' => 'Categoria',
                'subcategoria_id' => 'Subcategoria',
                'descricao_problema' => 'Descrição do problema'
            ];
            
            $erros = [];
            foreach ($camposObrigatorios as $campo => $label) {
                if (empty($dados[$campo])) {
                    $erros[] = "O campo '{$label}' é obrigatório";
                }
            }
            
            // Verificar se é requisição AJAX
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if (!empty($erros)) {
                if ($isAjax) {
                    $this->json(['success' => false, 'errors' => $erros], 400);
                } else {
                    $this->redirect(url('admin/solicitacoes-manuais/nova?error=' . urlencode(implode('. ', $erros))));
                }
                return;
            }
            
            // Validar CPF
            $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
            if (!$solicitacaoManualModel->validarCPF($dados['cpf'])) {
                if ($isAjax) {
                    $this->json(['success' => false, 'error' => 'CPF inválido'], 400);
                } else {
                    $this->redirect(url('admin/solicitacoes-manuais/nova?error=' . urlencode('CPF inválido')));
                }
                return;
            }
            
            // Validar WhatsApp
            $whatsappLimpo = preg_replace('/\D/', '', $dados['whatsapp']);
            if (strlen($whatsappLimpo) < 10 || strlen($whatsappLimpo) > 11) {
                if ($isAjax) {
                    $this->json(['success' => false, 'error' => 'WhatsApp inválido'], 400);
                } else {
                    $this->redirect(url('admin/solicitacoes-manuais/nova?error=' . urlencode('WhatsApp inválido')));
                }
                return;
            }
            $dados['whatsapp'] = $whatsappLimpo;
            
            // Verificar validacao_bolsao
            $cpfLimpo = preg_replace('/\D/', '', $dados['cpf']);
            $cpfEncontradoNaListagem = false;
            if (!empty($cpfLimpo) && !empty($dados['imobiliaria_id'])) {
                $sql = "SELECT * FROM locatarios_contratos 
                        WHERE imobiliaria_id = ? 
                        AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?";
                $cpfEncontradoNaListagem = \App\Core\Database::fetch($sql, [$dados['imobiliaria_id'], $cpfLimpo]) !== null;
            }
            
            // Definir validacao_bolsao
            $dados['validacao_bolsao'] = $cpfEncontradoNaListagem ? 1 : 0;
            
            // Verificar se a coluna tipo_qualificacao existe antes de usar
            $colunaTipoQualificacaoExiste = $solicitacaoManualModel->colunaExisteBanco('tipo_qualificacao');
            
            // Definir tipo_qualificacao apenas se a coluna existir
            if ($colunaTipoQualificacaoExiste) {
                // Verificar se admin escolheu tipo_qualificacao explicitamente
                $tipoQualificacaoInput = $this->input('tipo_qualificacao');
                if (!empty($tipoQualificacaoInput) && in_array($tipoQualificacaoInput, ['BOLSAO', 'CORTESIA', 'NAO_QUALIFICADA', 'REGRA_2'])) {
                    // Admin escolheu explicitamente - usar o valor escolhido
                    $dados['tipo_qualificacao'] = $tipoQualificacaoInput;
                    } else {
                    // Solicitações manuais SEM escolha do admin começam com NULL
                    // Admin escolhe depois entre CORTESIA ou NAO_QUALIFICADA
                    $dados['tipo_qualificacao'] = null;
                }
            }
            
            // Processar horários preferenciais
            $horariosRaw = $this->input('horarios_opcoes');
            if (!empty($horariosRaw)) {
                $horarios = is_string($horariosRaw) ? json_decode($horariosRaw, true) : $horariosRaw;
                $dados['horarios_preferenciais'] = $horarios;
            } else {
                $dados['horarios_preferenciais'] = [];
            }
            
            // Processar upload de fotos
            $fotos = [];
            if (!empty($_FILES['fotos']['name'][0])) {
                $fotos = $this->processarUploadFotosManual();
            }
            $dados['fotos'] = $fotos;
            
            // Verificar se o CPF existe no banco de dados
            $cpfLimpo = preg_replace('/\D/', '', $dados['cpf']);
            $locatarioModel = new \App\Models\Locatario();
            $locatarioExistente = $locatarioModel->findByCpfAndImobiliaria($cpfLimpo, $dados['imobiliaria_id']);
            
            // Se o CPF existir, criar como solicitação normal (vai para Kanban)
            if ($locatarioExistente) {
                // Buscar status inicial
                $statusInicial = $this->statusModel->findByNome('Nova Solicitação') 
                              ?? $this->statusModel->findByNome('Nova') 
                              ?? $this->statusModel->findByNome('NOVA')
                              ?? ['id' => 1];
                
                // Preparar horários
                $horarios = $dados['horarios_preferenciais'] ?? [];
                $horariosJson = !empty($horarios) ? json_encode($horarios) : null;
                
                // Preparar dados para solicitação normal
                $dadosSolicitacao = [
                    'imobiliaria_id' => $dados['imobiliaria_id'],
                    'categoria_id' => $dados['categoria_id'],
                    'subcategoria_id' => $dados['subcategoria_id'],
                    'status_id' => $statusInicial['id'],
                    
                    // Dados do locatário (usar dados existentes ou do formulário)
                    'locatario_id' => $locatarioExistente['id'],
                    'locatario_nome' => $locatarioExistente['nome'] ?? $dados['nome_completo'],
                    'locatario_cpf' => $cpfLimpo,
                    'locatario_telefone' => $locatarioExistente['whatsapp'] ?? $locatarioExistente['telefone'] ?? $dados['whatsapp'],
                    'locatario_email' => $locatarioExistente['email'] ?? null,
                    
                    // Dados do imóvel (usar do formulário ou do locatário)
                    'imovel_endereco' => $dados['endereco'] ?: ($locatarioExistente['endereco_logradouro'] ?? ''),
                    'imovel_numero' => $dados['numero'] ?: ($locatarioExistente['endereco_numero'] ?? ''),
                    'imovel_complemento' => $dados['complemento'] ?: ($locatarioExistente['endereco_complemento'] ?? null),
                    'imovel_bairro' => $dados['bairro'] ?: ($locatarioExistente['endereco_bairro'] ?? ''),
                    'imovel_cidade' => $dados['cidade'] ?: ($locatarioExistente['endereco_cidade'] ?? ''),
                    'imovel_estado' => $dados['estado'] ?: ($locatarioExistente['endereco_estado'] ?? ''),
                    'imovel_cep' => $dados['cep'] ?: ($locatarioExistente['endereco_cep'] ?? ''),
                    
                    // Descrição e detalhes
                    'descricao_problema' => $dados['descricao_problema'],
                    'local_manutencao' => $dados['local_manutencao'] ?? null,
                    // NOTA: local_manutencao não deve ser incluído em observacoes, ele tem seu próprio campo no banco
                    'observacoes' => "Tipo: " . ($dados['tipo_imovel'] ?? 'RESIDENCIAL'),
                    'prioridade' => 'NORMAL',
                    'tipo_atendimento' => strtoupper($dados['tipo_imovel'] ?? 'RESIDENCIAL'),
                    
                    // Horários preferenciais
                    'horarios_opcoes' => $horariosJson,
                    'datas_opcoes' => $horariosJson,
                    
                    // Fotos
                    'fotos' => !empty($dados['fotos']) ? json_encode($dados['fotos']) : null
                ];
                
                // Criar como solicitação normal
                $solicitacaoId = $this->solicitacaoModel->create($dadosSolicitacao);
                
                if ($solicitacaoId) {
                    // Enviar notificação WhatsApp
                    try {
                        $whatsappService = new \App\Services\WhatsAppService();
                        $whatsappService->sendMessage($solicitacaoId, 'Nova Solicitação');
                    } catch (\Exception $e) {
                        error_log('Erro ao enviar WhatsApp: ' . $e->getMessage());
                    }
                    
                    if ($isAjax) {
                        $this->json([
                            'success' => true, 
                            'message' => 'Solicitação criada com sucesso! (CPF verificado - vai para Kanban)', 
                            'id' => $solicitacaoId,
                            'is_normal' => true
                        ]);
                    } else {
                        $this->redirect(url('admin/kanban?success=' . urlencode('Solicitação criada com sucesso! (CPF verificado - ID: #' . $solicitacaoId . ')')));
                    }
                } else {
                    if ($isAjax) {
                        $this->json(['success' => false, 'error' => 'Erro ao criar solicitação. Tente novamente.'], 500);
                    } else {
                        $this->redirect(url('admin/solicitacoes-manuais/nova?error=' . urlencode('Erro ao criar solicitação. Tente novamente.')));
                    }
                }
                return;
            }
            
            // Se o CPF não existir, criar como solicitação manual (vai para admin)
            // Definir status padrão se não informado
            if (empty($dados['status_id'])) {
                $statusPadrao = $this->statusModel->findByNome('Nova Solicitação');
                $dados['status_id'] = $statusPadrao['id'] ?? 1;
            }
            
            // Criar solicitação manual
            // Verificar quantidade de solicitações por CPF antes de criar
            $verificacaoQuantidade = $solicitacaoManualModel->verificarQuantidadePorCPF(
                $dados['cpf'],
                $dados['imobiliaria_id'],
                $dados['categoria_id'] ?? null
            );
            
            // Log da verificação
            error_log("DEBUG [Solicitação Manual Admin] - CPF: " . preg_replace('/[^0-9]/', '', $dados['cpf']) . ", Quantidade total: {$verificacaoQuantidade['quantidade_total']}, Últimos 12 meses: {$verificacaoQuantidade['quantidade_12_meses']}");
            
            // Criar solicitação manual
            $id = $solicitacaoManualModel->create($dados);
            
            // Atualizar contagem de CPF após criar
            if ($id) {
                $solicitacaoManualModel->atualizarContagemCPF(
                    $dados['cpf'], 
                    $dados['imobiliaria_id'],
                    $dados['categoria_id'] ?? null
                );
            }
            
            if ($id) {
                if ($isAjax) {
                    $this->json(['success' => true, 'message' => 'Solicitação manual criada com sucesso!', 'id' => $id, 'is_normal' => false]);
                } else {
                    $this->redirect(url('admin/solicitacoes-manuais?success=' . urlencode('Solicitação manual criada com sucesso! ID: #' . $id)));
                }
            } else {
                if ($isAjax) {
                    $this->json(['success' => false, 'error' => 'Erro ao criar solicitação manual. Tente novamente.'], 500);
                } else {
                    $this->redirect(url('admin/solicitacoes-manuais/nova?error=' . urlencode('Erro ao criar solicitação manual. Tente novamente.')));
                }
            }
        } catch (\Exception $e) {
            error_log('Erro ao criar solicitação manual: ' . $e->getMessage());
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                $this->json(['success' => false, 'error' => 'Erro ao processar: ' . $e->getMessage()], 500);
            } else {
                $this->redirect(url('admin/solicitacoes-manuais/nova?error=' . urlencode('Erro ao processar: ' . $e->getMessage())));
            }
        }
    }
    
    /**
     * Processar upload de fotos para solicitação manual
     */
    private function processarUploadFotosManual(): array
    {
        $fotos = [];
        $uploadDir = __DIR__ . '/../../Public/uploads/solicitacoes-manuais/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        if (!empty($_FILES['fotos']['name'][0])) {
            $totalFiles = count($_FILES['fotos']['name']);
            
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['fotos']['tmp_name'][$i];
                    $originalName = $_FILES['fotos']['name'][$i];
                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    
                    // Validar extensão
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($extension, $allowedExtensions)) {
                        continue;
                    }
                    
                    // Gerar nome único
                    $newName = uniqid('foto_', true) . '.' . $extension;
                    $destination = $uploadDir . $newName;
                    
                    if (move_uploaded_file($tmpName, $destination)) {
                        $fotos[] = '/uploads/solicitacoes-manuais/' . $newName;
                    }
                }
            }
        }
        
        return $fotos;
    }
    
    /**
     * Exibir formulário para editar solicitação manual (admin)
     */
    public function editarSolicitacaoManual(int $id): void
    {
        $this->requireAuth();
        
        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
        $solicitacao = $solicitacaoManualModel->find($id);
        
        if (!$solicitacao) {
            $this->redirect(url('admin/solicitacoes-manuais?error=' . urlencode('Solicitação não encontrada')));
            return;
        }
        
        // Verificar se já foi migrada
        if (!empty($solicitacao['migrada_para_solicitacao_id'])) {
            $this->redirect(url('admin/solicitacoes-manuais?error=' . urlencode('Não é possível editar uma solicitação que já foi migrada')));
            return;
        }
        
        // Buscar dados necessários para o formulário
        $imobiliarias = $this->imobiliariaModel->getAll();
        $categoriaModel = new \App\Models\Categoria();
        $subcategoriaModel = new \App\Models\Subcategoria();
        $categorias = $categoriaModel->getAtivas();
        $subcategorias = $subcategoriaModel->getAtivas();
        $statusList = $this->statusModel->getAll();
        
        // Organizar subcategorias por categoria
        foreach ($categorias as $key => $categoria) {
            $categorias[$key]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoria) {
                return $sub['categoria_id'] == $categoria['id'];
            }));
        }
        
        // Decodificar JSONs
        if (!empty($solicitacao['horarios_preferenciais'])) {
            $solicitacao['horarios_preferenciais'] = is_string($solicitacao['horarios_preferenciais']) 
                ? json_decode($solicitacao['horarios_preferenciais'], true) 
                : $solicitacao['horarios_preferenciais'];
        }
        
        if (!empty($solicitacao['fotos'])) {
            $solicitacao['fotos'] = is_string($solicitacao['fotos']) 
                ? json_decode($solicitacao['fotos'], true) 
                : $solicitacao['fotos'];
        }
        
        $this->view('solicitacoes.editar-manual', [
            'solicitacao' => $solicitacao,
            'imobiliarias' => $imobiliarias,
            'categorias' => $categorias,
            'subcategorias' => $subcategorias,
            'statusList' => $statusList
        ]);
    }
    
    /**
     * Processar atualização de solicitação manual (admin)
     */
    public function atualizarSolicitacaoManual(int $id): void
    {
        $this->requireAuth();
        
        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
        $solicitacao = $solicitacaoManualModel->find($id);
        
        if (!$solicitacao) {
            $this->redirect(url('admin/solicitacoes-manuais?error=' . urlencode('Solicitação não encontrada')));
            return;
        }
        
        // Verificar se já foi migrada
        if (!empty($solicitacao['migrada_para_solicitacao_id'])) {
            $this->redirect(url('admin/solicitacoes-manuais?error=' . urlencode('Não é possível editar uma solicitação que já foi migrada')));
            return;
        }
        
        try {
            // Validar dados obrigatórios
            $dados = [
                'imobiliaria_id' => $this->input('imobiliaria_id'),
                'nome_completo' => trim($this->input('nome_completo')),
                'cpf' => preg_replace('/\D/', '', $this->input('cpf')),
                'whatsapp' => trim($this->input('whatsapp')),
                'tipo_imovel' => $this->input('tipo_imovel'),
                'subtipo_imovel' => $this->input('subtipo_imovel'),
                'cep' => preg_replace('/\D/', '', $this->input('cep')),
                'endereco' => trim($this->input('endereco')),
                'numero' => trim($this->input('numero')),
                'complemento' => trim($this->input('complemento')),
                'bairro' => trim($this->input('bairro')),
                'cidade' => trim($this->input('cidade')),
                'estado' => trim($this->input('estado')),
                'categoria_id' => $this->input('categoria_id'),
                'subcategoria_id' => $this->input('subcategoria_id'),
                'descricao_problema' => trim($this->input('descricao_problema')),
                'numero_contrato' => trim($this->input('numero_contrato')) ?: null,
                'local_manutencao' => trim($this->input('local_manutencao')),
                'status_id' => $this->input('status_id')
            ];
            
            // Validar campos obrigatórios
            $camposObrigatorios = [
                'imobiliaria_id' => 'Imobiliária',
                'nome_completo' => 'Nome completo',
                'cpf' => 'CPF',
                'whatsapp' => 'WhatsApp',
                'tipo_imovel' => 'Tipo de imóvel',
                'cep' => 'CEP',
                'endereco' => 'Endereço',
                'numero' => 'Número',
                'bairro' => 'Bairro',
                'cidade' => 'Cidade',
                'estado' => 'Estado',
                'categoria_id' => 'Categoria',
                'subcategoria_id' => 'Subcategoria',
                'descricao_problema' => 'Descrição do problema'
            ];
            
            $erros = [];
            foreach ($camposObrigatorios as $campo => $label) {
                if (empty($dados[$campo])) {
                    $erros[] = "O campo '{$label}' é obrigatório";
                }
            }
            
            if (!empty($erros)) {
                $this->redirect(url('admin/solicitacoes-manuais/' . $id . '/editar?error=' . urlencode(implode('. ', $erros))));
                return;
            }
            
            // Validar CPF
            if (!$solicitacaoManualModel->validarCPF($dados['cpf'])) {
                $this->redirect(url('admin/solicitacoes-manuais/' . $id . '/editar?error=' . urlencode('CPF inválido')));
                return;
            }
            
            // Validar WhatsApp
            $whatsappLimpo = preg_replace('/\D/', '', $dados['whatsapp']);
            if (strlen($whatsappLimpo) < 10 || strlen($whatsappLimpo) > 11) {
                $this->redirect(url('admin/solicitacoes-manuais/' . $id . '/editar?error=' . urlencode('WhatsApp inválido')));
                return;
            }
            $dados['whatsapp'] = $whatsappLimpo;
            
            // Processar horários preferenciais
            $horariosRaw = $this->input('horarios_opcoes');
            if (!empty($horariosRaw)) {
                $horarios = is_string($horariosRaw) ? json_decode($horariosRaw, true) : $horariosRaw;
                $dados['horarios_preferenciais'] = $horarios;
            } else {
                $dados['horarios_preferenciais'] = [];
            }
            
            // Processar upload de fotos
            $fotosExistentesInput = $this->input('fotos_existentes');
            $fotosExistentes = [];
            if (!empty($fotosExistentesInput)) {
                $fotosExistentes = is_string($fotosExistentesInput) 
                    ? json_decode($fotosExistentesInput, true) 
                    : $fotosExistentesInput;
                if (!is_array($fotosExistentes)) {
                    $fotosExistentes = [];
                }
            } else {
                // Se não vier no input, manter as existentes
                $fotosExistentes = !empty($solicitacao['fotos']) 
                    ? (is_string($solicitacao['fotos']) ? json_decode($solicitacao['fotos'], true) : $solicitacao['fotos'])
                    : [];
            }
            
            // Adicionar novas fotos se houver upload
            if (!empty($_FILES['fotos']['name'][0])) {
                $fotosNovas = $this->processarUploadFotosManual();
                $fotosExistentes = array_merge($fotosExistentes, $fotosNovas);
            }
            
            $dados['fotos'] = $fotosExistentes;
            
            // Definir status padrão se não informado
            if (empty($dados['status_id'])) {
                $statusPadrao = $this->statusModel->findByNome('Nova Solicitação');
                $dados['status_id'] = $statusPadrao['id'] ?? 1;
            }
            
            // Atualizar solicitação manual
            $atualizado = $solicitacaoManualModel->update($id, $dados);
            
            // Se o CPF ou categoria foi alterado, atualizar contagem
            if ($atualizado && !empty($dados['cpf']) && !empty($dados['imobiliaria_id'])) {
                $solicitacaoManualModel->atualizarContagemCPF(
                    $dados['cpf'], 
                    $dados['imobiliaria_id'],
                    $dados['categoria_id'] ?? null
                );
            }
            
            if ($atualizado) {
                $this->redirect(url('admin/solicitacoes-manuais?success=' . urlencode('Solicitação manual atualizada com sucesso!')));
            } else {
                $this->redirect(url('admin/solicitacoes-manuais/' . $id . '/editar?error=' . urlencode('Erro ao atualizar solicitação manual. Tente novamente.')));
            }
        } catch (\Exception $e) {
            error_log('Erro ao atualizar solicitação manual: ' . $e->getMessage());
            $this->redirect(url('admin/solicitacoes-manuais/' . $id . '/editar?error=' . urlencode('Erro ao processar: ' . $e->getMessage())));
        }
    }
    
    /**
     * API: Buscar dados de uma solicitação manual para edição
     */
    public function apiSolicitacaoManual(int $id): void
    {
        $this->requireAuth();
        
        try {
            $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
            $solicitacao = $solicitacaoManualModel->find($id);
            
            if (!$solicitacao) {
                $this->json(['error' => 'Solicitação não encontrada'], 404);
                return;
            }
            
            // Decodificar JSONs
            if (!empty($solicitacao['horarios_preferenciais'])) {
                $solicitacao['horarios_preferenciais'] = is_string($solicitacao['horarios_preferenciais']) 
                    ? json_decode($solicitacao['horarios_preferenciais'], true) 
                    : $solicitacao['horarios_preferenciais'];
            }
            
            if (!empty($solicitacao['fotos'])) {
                $solicitacao['fotos'] = is_string($solicitacao['fotos']) 
                    ? json_decode($solicitacao['fotos'], true) 
                    : $solicitacao['fotos'];
            }
            
            // Processar subcategorias múltiplas se houver nas observações
            $subcategoriaModel = new \App\Models\Subcategoria();
            $subcategoriasAdicionais = [];
            if (!empty($solicitacao['observacoes'])) {
                // Tentar extrair IDs das subcategorias do formato [SUBCATEGORIAS_IDS: [...]]
                if (preg_match('/\[SUBCATEGORIAS_IDS:\s*(\[[^\]]+\])\]/', $solicitacao['observacoes'], $matches)) {
                    $subcategoriasIds = json_decode($matches[1], true);
                    if (is_array($subcategoriasIds) && count($subcategoriasIds) > 1) {
                        foreach ($subcategoriasIds as $subId) {
                            $sub = $subcategoriaModel->find($subId);
                            if ($sub && !empty($sub['nome'])) {
                                $subcategoriasAdicionais[] = [
                                    'id' => $sub['id'],
                                    'nome' => $sub['nome'],
                                    'categoria_id' => $sub['categoria_id']
                                ];
                            }
                        }
                    }
                }
                // Alternativa: extrair da lista formatada "Serviços solicitados (X):"
                elseif (preg_match('/Serviços solicitados \(\d+\):\s*\n((?:\d+\.\s*[^\n]+\n?)+)/', $solicitacao['observacoes'], $matches)) {
                    $linhas = explode("\n", trim($matches[1]));
                    foreach ($linhas as $linha) {
                        if (preg_match('/^\d+\.\s*(.+)$/', trim($linha), $linhaMatch)) {
                            $subcategoriasAdicionais[] = [
                                'id' => null,
                                'nome' => trim($linhaMatch[1]),
                                'categoria_id' => null
                            ];
                        }
                    }
                }
            }
            $solicitacao['subcategorias_adicionais'] = $subcategoriasAdicionais;
            
            // Buscar dados necessários para o formulário
            $imobiliarias = $this->imobiliariaModel->getAll();
            $categoriaModel = new \App\Models\Categoria();
            $categorias = $categoriaModel->getAtivas();
            $subcategorias = $subcategoriaModel->getAtivas();
            $statusList = $this->statusModel->getAll();
            
            // Organizar subcategorias por categoria
            foreach ($categorias as $key => $categoria) {
                $categorias[$key]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoria) {
                    return $sub['categoria_id'] == $categoria['id'];
                }));
            }
            
            $this->json([
                'success' => true,
                'solicitacao' => $solicitacao,
                'imobiliarias' => $imobiliarias,
                'categorias' => $categorias,
                'statusList' => $statusList
            ]);
        } catch (\Exception $e) {
            error_log('Erro ao buscar solicitação manual via API: ' . $e->getMessage());
            $this->json(['error' => 'Erro ao buscar dados da solicitação'], 500);
        }
    }
    
    /**
     * API: Buscar dados para formulário de nova solicitação manual
     */
    public function apiNovaSolicitacaoManual(): void
    {
        $this->requireAuth();
        
        try {
            $imobiliarias = $this->imobiliariaModel->getAll();
            $categoriaModel = new \App\Models\Categoria();
            $subcategoriaModel = new \App\Models\Subcategoria();
            $categorias = $categoriaModel->getAtivas();
            $subcategorias = $subcategoriaModel->getAtivas();
            $statusList = $this->statusModel->getAll();
            
            // Organizar subcategorias por categoria
            foreach ($categorias as $key => $categoria) {
                $categorias[$key]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoria) {
                    return $sub['categoria_id'] == $categoria['id'];
                }));
            }
            
            $this->json([
                'success' => true,
                'imobiliarias' => $imobiliarias,
                'categorias' => $categorias,
                'statusList' => $statusList
            ]);
        } catch (\Exception $e) {
            error_log('Erro ao buscar dados para nova solicitação manual: ' . $e->getMessage());
            $this->json(['error' => 'Erro ao buscar dados'], 500);
        }
    }
    
    /**
     * Ver detalhes de uma solicitação manual (JSON para modal)
     */
    public function verSolicitacaoManual(int $id): void
    {
        $this->requireAuth();
        
        try {
            $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
            $solicitacao = $solicitacaoManualModel->getDetalhes($id);
            
            if (!$solicitacao) {
                $this->json(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
                return;
            }
            
            // Decodificar JSONs
            if (!empty($solicitacao['horarios_preferenciais'])) {
                $solicitacao['horarios_preferenciais'] = is_string($solicitacao['horarios_preferenciais']) 
                    ? json_decode($solicitacao['horarios_preferenciais'], true) 
                    : $solicitacao['horarios_preferenciais'];
            }
            
            if (!empty($solicitacao['fotos'])) {
                $solicitacao['fotos'] = is_string($solicitacao['fotos']) 
                    ? json_decode($solicitacao['fotos'], true) 
                    : $solicitacao['fotos'];
            }
            
            // Processar subcategorias múltiplas se houver nas observações
            $subcategoriaModel = new \App\Models\Subcategoria();
            $subcategoriasAdicionais = [];
            if (!empty($solicitacao['observacoes'])) {
                // Tentar extrair IDs das subcategorias do formato [SUBCATEGORIAS_IDS: [...]]
                if (preg_match('/\[SUBCATEGORIAS_IDS:\s*(\[[^\]]+\])\]/', $solicitacao['observacoes'], $matches)) {
                    $subcategoriasIds = json_decode($matches[1], true);
                    if (is_array($subcategoriasIds) && count($subcategoriasIds) > 1) {
                        foreach ($subcategoriasIds as $subId) {
                            $sub = $subcategoriaModel->find($subId);
                            if ($sub && !empty($sub['nome'])) {
                                $subcategoriasAdicionais[] = [
                                    'id' => $sub['id'],
                                    'nome' => $sub['nome'],
                                    'categoria_id' => $sub['categoria_id']
                                ];
                            }
                        }
                    }
                }
                // Alternativa: extrair da lista formatada "Serviços solicitados (X):"
                elseif (preg_match('/Serviços solicitados \(\d+\):\s*\n((?:\d+\.\s*[^\n]+\n?)+)/', $solicitacao['observacoes'], $matches)) {
                    $linhas = explode("\n", trim($matches[1]));
                    foreach ($linhas as $linha) {
                        if (preg_match('/^\d+\.\s*(.+)$/', trim($linha), $linhaMatch)) {
                            $subcategoriasAdicionais[] = [
                                'id' => null,
                                'nome' => trim($linhaMatch[1]),
                                'categoria_id' => null
                            ];
                        }
                    }
                }
            }
            $solicitacao['subcategorias_adicionais'] = $subcategoriasAdicionais;
            
            // Calcular validação de utilização (limite de solicitações)
            // Para solicitações manuais, usamos a contagem por CPF na tabela
            // solicitacoes_manuais_contagem_cpf em conjunto com o limite da categoria.
            $validacaoUtilizacao = null; // null = não verificado / sem dados suficientes
            if (!empty($solicitacao['cpf']) && !empty($solicitacao['imobiliaria_id']) && !empty($solicitacao['categoria_id'])) {
                $categoriaModel = new \App\Models\Categoria();
                $categoria = $categoriaModel->find((int)$solicitacao['categoria_id']);
                
                if ($categoria) {
                    $limite = $categoria['limite_solicitacoes_12_meses'] ?? null;
                    
                    // Se houver limite configurado, usar a contagem por CPF para validar utilização
                    if ($limite !== null && (int)$limite > 0) {
                        $verificacaoCpf = $solicitacaoManualModel->verificarQuantidadePorCPF(
                            $solicitacao['cpf'],
                            (int)$solicitacao['imobiliaria_id'],
                            (int)$solicitacao['categoria_id']
                        );
                        
                        $quantidade12Meses = (int)($verificacaoCpf['quantidade_12_meses'] ?? 0);
                        
                        // Aprovado se ainda não atingiu o limite
                        $validacaoUtilizacao = $quantidade12Meses < (int)$limite ? 1 : 0;
                    } else {
                        // Sem limite definido na categoria → considerar como aprovado
                        $validacaoUtilizacao = 1;
                    }
                }
            }
            $solicitacao['validacao_utilizacao'] = $validacaoUtilizacao;
            
            // Buscar lista de status para o dropdown
            $statusList = $this->statusModel->getAll();
            
            $this->json([
                'success' => true,
                'solicitacao' => $solicitacao,
                'statusList' => $statusList
            ]);
        } catch (\Exception $e) {
            error_log('Erro ao buscar solicitação manual: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Buscar histórico de WhatsApp de uma solicitação
     */
    private function getWhatsAppHistorico(int $solicitacaoId): array
    {
        $historico = [];
        $logFile = __DIR__ . '/../../storage/logs/whatsapp_evolution_api.log';
        
        if (!file_exists($logFile)) {
            return $historico;
        }
        
        try {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $currentEntry = null;
            
            foreach ($lines as $line) {
                // Procurar por linhas que começam com timestamp e contêm o ID da solicitação
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[(\w+)\] ID:(\d+)/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $status = $matches[2];
                    $id = (int)$matches[3];
                    
                    // Se for da solicitação atual, processar
                    if ($id === $solicitacaoId) {
                        // Extrair informações da linha
                        $tipo = 'N/A';
                        $protocolo = 'N/A';
                        $telefone = null;
                        $erro = null;
                        
                        if (preg_match('/Tipo:([^|]+)/', $line, $tipoMatch)) {
                            $tipo = trim($tipoMatch[1]);
                        }
                        if (preg_match('/Protocolo:([^|]+)/', $line, $protoMatch)) {
                            $protocolo = trim($protoMatch[1]);
                        }
                        if (preg_match('/Telefone:([^|]+)/', $line, $telMatch)) {
                            $telefone = trim($telMatch[1]);
                        }
                        if (preg_match('/ERRO:([^|]+)/', $line, $erroMatch)) {
                            $erro = trim($erroMatch[1]);
                        }
                        
                        $currentEntry = [
                            'timestamp' => $timestamp,
                            'status' => strtolower($status),
                            'tipo' => $tipo,
                            'protocolo' => $protocolo,
                            'telefone' => $telefone,
                            'erro' => $erro,
                            'mensagem' => null,
                            'detalhes' => null
                        ];
                    }
                }
                // Se encontrou uma linha de detalhes JSON
                elseif ($currentEntry && strpos($line, 'DETALHES:') !== false) {
                    $jsonPart = substr($line, strpos($line, 'DETALHES:') + 9);
                    $detalhes = json_decode($jsonPart, true);
                    
                    if ($detalhes && is_array($detalhes)) {
                        $currentEntry['detalhes'] = $detalhes;
                        
                        // Tentar extrair a mensagem dos detalhes
                        if (isset($detalhes['mensagem'])) {
                            // Mensagem completa salva no log
                            $currentEntry['mensagem'] = $detalhes['mensagem'];
                        } elseif (isset($detalhes['api_response']['message']['conversation'])) {
                            // Mensagem enviada pela API (já com variáveis substituídas)
                            $currentEntry['mensagem'] = $detalhes['api_response']['message']['conversation'];
                        } elseif (isset($detalhes['template_id'])) {
                            // Buscar template e tentar reconstruir a mensagem
                            try {
                                $templateModel = new \App\Models\WhatsappTemplate();
                                $template = $templateModel->find($detalhes['template_id']);
                                if ($template && !empty($template['corpo'])) {
                                    $mensagemTemplate = $template['corpo'];
                                    
                                    // Tentar substituir variáveis básicas se disponíveis nos detalhes
                                    if (isset($detalhes['protocolo'])) {
                                        $mensagemTemplate = str_replace('{{protocol}}', $detalhes['protocolo'], $mensagemTemplate);
                                        $mensagemTemplate = str_replace('{{protocolo}}', $detalhes['protocolo'], $mensagemTemplate);
                                    }
                                    if (isset($detalhes['cliente_nome'])) {
                                        $mensagemTemplate = str_replace('{{cliente_nome}}', $detalhes['cliente_nome'], $mensagemTemplate);
                                    }
                                    
                                    $currentEntry['mensagem'] = $mensagemTemplate;
                                }
                            } catch (\Exception $e) {
                                // Ignorar erro
                            }
                        }
                        
                        // Se ainda não tem mensagem, usar o template básico
                        if (empty($currentEntry['mensagem']) && isset($detalhes['message_type'])) {
                            $currentEntry['mensagem'] = 'Template: ' . $detalhes['message_type'];
                        }
                        
                        // Adicionar ao histórico
                        $historico[] = $currentEntry;
                        $currentEntry = null;
                    }
                }
            }
            
            // Ordenar por timestamp (mais recente primeiro)
            usort($historico, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            
        } catch (\Exception $e) {
            error_log('Erro ao ler histórico de WhatsApp: ' . $e->getMessage());
        }
        
        return $historico;
    }
    
    /**
     * Atualizar status de uma solicitação manual
     */
    public function atualizarStatusManual(int $id): void
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        
        try {
            $statusId = $this->input('status_id');
            
            if (empty($statusId)) {
                $this->json(['success' => false, 'message' => 'Status não informado'], 400);
                return;
            }
            
            $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
            $resultado = $solicitacaoManualModel->update($id, [
                'status_id' => $statusId
            ]);
            
            if ($resultado) {
                $this->json([
                    'success' => true,
                    'message' => 'Status atualizado com sucesso'
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'Erro ao atualizar status'], 500);
            }
        } catch (\Exception $e) {
            error_log('Erro ao atualizar status: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Migrar solicitação manual para o sistema principal
     */
    public function migrarParaSistema(int $id): void
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        
        try {
            $usuarioId = $_SESSION['user_id'] ?? null;
            
            if (!$usuarioId) {
                $this->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
                return;
            }
            
            $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
            $resultado = $solicitacaoManualModel->migrarParaSistema($id, $usuarioId);
            
            if ($resultado['success']) {
                // Enviar WhatsApp após migração bem-sucedida
                try {
                    // Verificar se a solicitação é não qualificada
                    $solicitacaoMigrada = $this->solicitacaoModel->find($resultado['solicitacao_id']);
                    if ($solicitacaoMigrada && !empty($solicitacaoMigrada['tipo_qualificacao']) && 
                        $solicitacaoMigrada['tipo_qualificacao'] === 'NAO_QUALIFICADA') {
                        // Se for não qualificada, enviar mensagem específica com observação
                        $observacao = $solicitacaoMigrada['observacao_qualificacao'] ?? 'Não se enquadra nos critérios estabelecidos.';
                        $this->enviarNotificacaoWhatsApp($resultado['solicitacao_id'], 'Não Qualificado', [
                            'observacao' => $observacao
                        ]);
                    } else {
                        // Se não for não qualificada, enviar mensagem padrão
                        $this->enviarNotificacaoWhatsApp($resultado['solicitacao_id'], 'Nova Solicitação');
                    }
                } catch (\Exception $e) {
                    error_log('Erro ao enviar WhatsApp após migração [ID:' . $resultado['solicitacao_id'] . ']: ' . $e->getMessage());
                    // Não bloquear a resposta de sucesso se o WhatsApp falhar
                }
                
                $this->json([
                    'success' => true,
                    'message' => $resultado['message'],
                    'solicitacao_id' => $resultado['solicitacao_id']
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => $resultado['message']
                ], 400);
            }
        } catch (\Exception $e) {
            error_log('Erro ao migrar solicitação: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * CICLO DE AGENDAMENTO - Etapa 2: Prestador aceita uma data
     * POST /admin/solicitacoes/{id}/aceitar-data-prestador
     */
    public function aceitarDataPrestador(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $horarioRaw = $json['horario_raw'] ?? $this->input('horario_raw');

        if (empty($horarioRaw)) {
            $this->json(['error' => 'Horário não informado'], 400);
            return;
        }

        try {
            $solicitacao = $this->solicitacaoModel->find($id);
            if (!$solicitacao) {
                $this->json(['error' => 'Solicitação não encontrada'], 404);
                return;
            }

            // Verificar se está na condição correta
            $condicaoModel = new \App\Models\Condicao();
            $condicaoAtual = $condicaoModel->find($solicitacao['condicao_id']);
            if (!$condicaoAtual || $condicaoAtual['nome'] !== 'Aguardando Resposta do Prestador') {
                $this->json(['error' => 'Solicitação não está aguardando resposta do prestador'], 400);
                return;
            }

            // Extrair data e horário do raw
            $dataAgendamento = null;
            $horarioAgendamento = null;
            if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $horarioRaw, $dateMatches)) {
                $dataAgendamento = $dateMatches[3] . '-' . $dateMatches[2] . '-' . $dateMatches[1];
            }
            if (preg_match('/(\d{2}:\d{2})-\d{2}:\d{2}/', $horarioRaw, $timeMatches)) {
                $horarioAgendamento = $timeMatches[1] . ':00';
            }

            // Atualizar condição para "Data Aceita pelo Prestador"
            $condicaoAceita = $condicaoModel->findByNome('Data Aceita pelo Prestador');
            if (!$condicaoAceita) {
                $this->json(['error' => 'Condição "Data Aceita pelo Prestador" não encontrada'], 500);
                return;
            }

            // Salvar em confirmed_schedules
            $confirmedSchedule = [
                'date' => $dataAgendamento,
                'time' => preg_match('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $horarioRaw, $t) ? ($t[1] . '-' . $t[2]) : '',
                'raw' => $horarioRaw,
                'source' => 'prestador',
                'confirmed_at' => date('c')
            ];

            $dadosUpdate = [
                'condicao_id' => $condicaoAceita['id'],
                'horario_confirmado_raw' => $horarioRaw,
                'confirmed_schedules' => json_encode([$confirmedSchedule])
            ];

            if ($dataAgendamento) {
                $dadosUpdate['data_agendamento'] = $dataAgendamento;
            }
            if ($horarioAgendamento) {
                $dadosUpdate['horario_agendamento'] = $horarioAgendamento;
            }

            // Status: "Aguardando Confirmação do Locatário"
            $statusAguardando = $this->getStatusId('Aguardando Confirmação do Locatário');
            if (!$statusAguardando) {
                $statusAguardando = $this->getStatusId('Buscando Prestador');
            }
            if ($statusAguardando) {
                $dadosUpdate['status_id'] = $statusAguardando;
            }

            $this->solicitacaoModel->update($id, $dadosUpdate);

            // Enviar notificação para locatário confirmar
            $this->enviarNotificacaoWhatsApp($id, 'Horário Sugerido', [
                'data_agendamento' => $dataAgendamento ? date('d/m/Y', strtotime($dataAgendamento)) : '',
                'horario_agendamento' => $horarioRaw
            ]);

            $this->json(['success' => true, 'message' => 'Data aceita pelo prestador. Locatário será notificado para confirmar.']);
        } catch (\Exception $e) {
            error_log('Erro ao aceitar data pelo prestador: ' . $e->getMessage());
            $this->json(['error' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * CICLO DE AGENDAMENTO - Etapa 2: Prestador recusa e propõe novas datas
     * POST /admin/solicitacoes/{id}/recusar-propor-datas
     */
    public function recusarProporDatas(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $novasDatas = $json['novas_datas'] ?? $this->input('novas_datas', []);

        if (empty($novasDatas) || !is_array($novasDatas)) {
            $this->json(['error' => 'É necessário informar pelo menos 1 nova data (máximo 3)'], 400);
            return;
        }

        // Limitar a 3 horários máximo
        if (count($novasDatas) > 3) {
            $novasDatas = array_slice($novasDatas, 0, 3);
        }

        try {
            $solicitacao = $this->solicitacaoModel->find($id);
            if (!$solicitacao) {
                $this->json(['error' => 'Solicitação não encontrada'], 404);
                return;
            }

            // Verificar se está na condição correta
            $condicaoModel = new \App\Models\Condicao();
            $condicaoAtual = $condicaoModel->find($solicitacao['condicao_id']);
            if (!$condicaoAtual || $condicaoAtual['nome'] !== 'Aguardando Resposta do Prestador') {
                $this->json(['error' => 'Solicitação não está aguardando resposta do prestador'], 400);
                return;
            }

            // Atualizar condição para "Prestador sem disponibilidade"
            $condicaoSemDisponibilidade = $condicaoModel->findByNome('Prestador sem disponibilidade');
            if (!$condicaoSemDisponibilidade) {
                $this->json(['error' => 'Condição "Prestador sem disponibilidade" não encontrada'], 500);
                return;
            }

            // Salvar novas datas em horarios_opcoes (SUBSTITUINDO as anteriores)
            $dadosUpdate = [
                'condicao_id' => $condicaoSemDisponibilidade['id'],
                'horarios_opcoes' => json_encode($novasDatas),
                'horarios_indisponiveis' => 1,
                'confirmed_schedules' => null,
                'horario_confirmado' => 0,
                'horario_confirmado_raw' => null,
                'data_agendamento' => null,
                'horario_agendamento' => null
            ];

            // Status: "Aguardando Confirmação do Locatário"
            $statusAguardando = $this->getStatusId('Aguardando Confirmação do Locatário');
            if (!$statusAguardando) {
                $statusAguardando = $this->getStatusId('Buscando Prestador');
            }
            if ($statusAguardando) {
                $dadosUpdate['status_id'] = $statusAguardando;
            }

            $this->solicitacaoModel->update($id, $dadosUpdate);

            // Enviar notificação para locatário com novas datas
            $horariosTexto = [];
            foreach ($novasDatas as $horario) {
                if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})-(\d{2}:\d{2})/', $horario, $matches)) {
                    $horariosTexto[] = $matches[1] . ' das ' . $matches[2] . ' às ' . $matches[3];
                } else {
                    $horariosTexto[] = $horario;
                }
            }

            $this->enviarNotificacaoWhatsApp($id, 'Horário Sugerido', [
                'horarios_sugeridos' => implode(', ', $horariosTexto)
            ]);

            $this->json(['success' => true, 'message' => 'Novas datas propostas. Locatário será notificado.']);
        } catch (\Exception $e) {
            error_log('Erro ao propor novas datas: ' . $e->getMessage());
            $this->json(['error' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * CICLO DE AGENDAMENTO - Etapa 3: Locatário aceita uma data
     * POST /admin/solicitacoes/{id}/aceitar-data-locatario
     */
    public function aceitarDataLocatario(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $horarioRaw = $json['horario_raw'] ?? $this->input('horario_raw');

        if (empty($horarioRaw)) {
            $this->json(['error' => 'Horário não informado'], 400);
            return;
        }

        try {
            $solicitacao = $this->solicitacaoModel->find($id);
            if (!$solicitacao) {
                $this->json(['error' => 'Solicitação não encontrada'], 404);
                return;
            }

            // Verificar se está na condição correta
            $condicaoModel = new \App\Models\Condicao();
            $condicaoAtual = $condicaoModel->find($solicitacao['condicao_id']);
            $condicaoNome = $condicaoAtual['nome'] ?? '';
            
            if ($condicaoNome !== 'Aguardando Confirmação do Locatário' && 
                $condicaoNome !== 'Prestador sem disponibilidade') {
                $this->json(['error' => 'Solicitação não está aguardando confirmação do locatário'], 400);
                return;
            }

            // Extrair data e horário do raw
            $dataAgendamento = null;
            $horarioAgendamento = null;
            if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $horarioRaw, $dateMatches)) {
                $dataAgendamento = $dateMatches[3] . '-' . $dateMatches[2] . '-' . $dateMatches[1];
            }
            if (preg_match('/(\d{2}:\d{2})-\d{2}:\d{2}/', $horarioRaw, $timeMatches)) {
                $horarioAgendamento = $timeMatches[1] . ':00';
            }

            // Atualizar condição para "Data Aceita pelo Locatário"
            $condicaoAceita = $condicaoModel->findByNome('Data Aceita pelo Locatário');
            if (!$condicaoAceita) {
                $this->json(['error' => 'Condição "Data Aceita pelo Locatário" não encontrada'], 500);
                return;
            }

            // Salvar em confirmed_schedules
            $confirmedSchedule = [
                'date' => $dataAgendamento,
                'time' => preg_match('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $horarioRaw, $t) ? ($t[1] . '-' . $t[2]) : '',
                'raw' => $horarioRaw,
                'source' => 'tenant',
                'confirmed_at' => date('c')
            ];

            $confirmedSchedules = [];
            if (!empty($solicitacao['confirmed_schedules'])) {
                $existing = json_decode($solicitacao['confirmed_schedules'], true);
                if (is_array($existing)) {
                    $confirmedSchedules = $existing;
                }
            }
            $confirmedSchedules[] = $confirmedSchedule;

            $dadosUpdate = [
                'condicao_id' => $condicaoAceita['id'],
                'horario_confirmado' => 1,
                'horario_confirmado_raw' => $horarioRaw,
                'confirmed_schedules' => json_encode($confirmedSchedules)
            ];

            if ($dataAgendamento) {
                $dadosUpdate['data_agendamento'] = $dataAgendamento;
            }
            if ($horarioAgendamento) {
                $dadosUpdate['horario_agendamento'] = $horarioAgendamento;
            }

            $this->solicitacaoModel->update($id, $dadosUpdate);

            $this->json(['success' => true, 'message' => 'Data aceita pelo locatário. Aguardando confirmação final do admin.']);
        } catch (\Exception $e) {
            error_log('Erro ao aceitar data pelo locatário: ' . $e->getMessage());
            $this->json(['error' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * CICLO DE AGENDAMENTO - Etapa 3: Locatário recusa todas as datas
     * POST /admin/solicitacoes/{id}/recusar-datas-locatario
     */
    public function recusarDatasLocatario(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        try {
            $solicitacao = $this->solicitacaoModel->find($id);
            if (!$solicitacao) {
                $this->json(['error' => 'Solicitação não encontrada'], 404);
                return;
            }

            // Verificar se está na condição correta
            $condicaoModel = new \App\Models\Condicao();
            $condicaoAtual = $condicaoModel->find($solicitacao['condicao_id']);
            $condicaoNome = $condicaoAtual['nome'] ?? '';
            
            if ($condicaoNome !== 'Aguardando Confirmação do Locatário' && 
                $condicaoNome !== 'Prestador sem disponibilidade') {
                $this->json(['error' => 'Solicitação não está aguardando confirmação do locatário'], 400);
                return;
            }

            // Atualizar condição para "Datas Recusadas pelo Locatário"
            $condicaoRecusada = $condicaoModel->findByNome('Datas Recusadas pelo Locatário');
            if (!$condicaoRecusada) {
                $this->json(['error' => 'Condição "Datas Recusadas pelo Locatário" não encontrada'], 500);
                return;
            }

            $dadosUpdate = [
                'condicao_id' => $condicaoRecusada['id'],
                'horarios_indisponiveis' => 0,
                'confirmed_schedules' => null,
                'horario_confirmado' => 0,
                'horario_confirmado_raw' => null,
                'data_agendamento' => null,
                'horario_agendamento' => null
            ];

            // Status: "Buscando Prestador" (ciclo reinicia)
            $statusBuscando = $this->getStatusId('Buscando Prestador');
            if ($statusBuscando) {
                $dadosUpdate['status_id'] = $statusBuscando;
            }

            $this->solicitacaoModel->update($id, $dadosUpdate);

            $this->json(['success' => true, 'message' => 'Datas recusadas. Prestador pode propor novas datas.']);
        } catch (\Exception $e) {
            error_log('Erro ao recusar datas pelo locatário: ' . $e->getMessage());
            $this->json(['error' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * CICLO DE AGENDAMENTO - Etapa 4: Confirmação final pelo admin/prestador
     * POST /admin/solicitacoes/{id}/confirmar-agendamento-final
     */
    public function confirmarAgendamentoFinal(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        try {
            $solicitacao = $this->solicitacaoModel->find($id);
            if (!$solicitacao) {
                $this->json(['error' => 'Solicitação não encontrada'], 404);
                return;
            }

            // Verificar se está na condição correta
            $condicaoModel = new \App\Models\Condicao();
            $condicaoAtual = $condicaoModel->find($solicitacao['condicao_id']);
            $condicaoNome = $condicaoAtual['nome'] ?? '';
            
            if ($condicaoNome !== 'Data Aceita pelo Locatário') {
                $this->json(['error' => 'Locatário ainda não aceitou uma data'], 400);
                return;
            }

            if (empty($solicitacao['horario_confirmado_raw'])) {
                $this->json(['error' => 'Nenhum horário foi aceito pelo locatário'], 400);
                return;
            }

            // Atualizar condição para "Serviço Agendado / Data Confirmada"
            $condicaoConfirmada = $condicaoModel->findByNome('Serviço Agendado / Data Confirmada');
            if (!$condicaoConfirmada) {
                // Tentar usar status "Serviço Agendado" como fallback
                $statusAgendado = $this->getStatusId('Serviço Agendado');
                if ($statusAgendado) {
                    $dadosUpdate = [
                        'status_id' => $statusAgendado,
                        'horario_confirmado' => 1
                    ];
                    $this->solicitacaoModel->update($id, $dadosUpdate);
                    $this->json(['success' => true, 'message' => 'Agendamento confirmado com sucesso!']);
                    return;
                }
                $this->json(['error' => 'Condição "Serviço Agendado / Data Confirmada" não encontrada'], 500);
                return;
            }

            // Status: "Serviço Agendado"
            $statusAgendado = $this->getStatusId('Serviço Agendado');
            if (!$statusAgendado) {
                $this->json(['error' => 'Status "Serviço Agendado" não encontrado'], 500);
                return;
            }

            $dadosUpdate = [
                'condicao_id' => $condicaoConfirmada['id'],
                'status_id' => $statusAgendado,
                'horario_confirmado' => 1
            ];

            $this->solicitacaoModel->update($id, $dadosUpdate);

            // Enviar notificação de confirmação
            $horarioIntervalo = $this->extrairIntervaloHorario(
                $solicitacao['horario_confirmado_raw'] ?? null,
                $solicitacao['horario_agendamento'] ?? null,
                $solicitacao
            );
            
            $this->enviarNotificacaoWhatsApp($id, 'Horário Confirmado', [
                'data_agendamento' => $solicitacao['data_agendamento'] ? date('d/m/Y', strtotime($solicitacao['data_agendamento'])) : '',
                'horario_agendamento' => $horarioIntervalo
            ]);

            $this->json(['success' => true, 'message' => 'Agendamento confirmado com sucesso!']);
        } catch (\Exception $e) {
            error_log('Erro ao confirmar agendamento final: ' . $e->getMessage());
            $this->json(['error' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Busca todos os links de ações (tokens) gerados para uma solicitação
     */
    private function getLinksAcoes(int $solicitacaoId, array $solicitacao): array
    {
        $links = [];
        
        // Buscar URL base configurada
        $config = require __DIR__ . '/../Config/config.php';
        $baseUrl = $config['whatsapp']['links_base_url'] ?? $config['app']['url'] ?? 'https://kss.launs.com.br';
        $baseUrl = rtrim($baseUrl, '/');
        
        // Buscar todos os tokens da solicitação
        $sql = "
            SELECT * FROM schedule_confirmation_tokens
            WHERE solicitacao_id = ?
            ORDER BY created_at DESC
        ";
        $tokens = \App\Core\Database::fetchAll($sql, [$solicitacaoId]);
        
        foreach ($tokens as $token) {
            $actionType = $token['action_type'] ?? '';
            $isUsed = !empty($token['used_at']);
            $isExpired = strtotime($token['expires_at']) < time();
            $status = $isUsed ? 'usado' : ($isExpired ? 'expirado' : 'ativo');
            
            // Determinar tipo de link baseado no action_type
            $tipoLink = '';
            $url = '';
            
            switch ($actionType) {
                case 'confirm':
                case 'confirmation':
                    $tipoLink = 'Confirmação de Horário';
                    $url = $baseUrl . '/confirmacao-horario?token=' . $token['token'];
                    break;
                case 'cancel':
                case 'cancellation':
                    $tipoLink = 'Cancelamento de Horário';
                    $url = $baseUrl . '/cancelamento-horario?token=' . $token['token'];
                    break;
                case 'reschedule':
                    $tipoLink = 'Reagendamento';
                    $url = $baseUrl . '/reagendamento-horario?token=' . $token['token'];
                    break;
                case 'compra_peca':
                    $tipoLink = 'Compra de Peça';
                    $url = $baseUrl . '/compra-peca?token=' . $token['token'];
                    break;
                case 'pre_servico':
                    $tipoLink = 'Ações Pré-Serviço';
                    $url = $baseUrl . '/acoes-servico?token=' . $token['token'];
                    break;
                case 'pos_servico':
                case 'service_status':
                    $tipoLink = 'Ações Pós-Serviço';
                    $url = $baseUrl . '/acoes-servico?token=' . $token['token'];
                    break;
                default:
                    $tipoLink = 'Ação Genérica';
                    $url = $baseUrl . '/confirmacao-horario?token=' . $token['token'];
            }
            
            $links[] = [
                'tipo' => $tipoLink,
                'url' => $url,
                'token' => $token['token'],
                'status' => $status,
                'criado_em' => $token['created_at'],
                'expira_em' => $token['expires_at'],
                'usado_em' => $token['used_at'] ?? null,
                'action_type' => $actionType
            ];
        }
        
        // Adicionar link de status público (permanente)
        $links[] = [
            'tipo' => 'Status da Solicitação',
            'url' => $baseUrl . '/status-servico?protocol=' . urlencode($solicitacao['numero_solicitacao'] ?? 'KSS' . $solicitacaoId),
            'token' => null,
            'status' => 'permanente',
            'criado_em' => null,
            'expira_em' => null,
            'usado_em' => null,
            'action_type' => 'status_publico'
        ];
        
        // Adicionar link de cancelamento de solicitação (permanente)
        $instancia = $solicitacao['imobiliaria_instancia'] ?? '';
        if (!empty($instancia)) {
            $links[] = [
                'tipo' => 'Cancelar Solicitação',
                'url' => $baseUrl . '/' . $instancia . '/solicitacoes/' . $solicitacaoId . '/cancelar',
                'token' => null,
                'status' => 'permanente',
                'criado_em' => null,
                'expira_em' => null,
                'usado_em' => null,
                'action_type' => 'cancelar_solicitacao'
            ];
        }
        
        return $links;
    }
    
    /**
     * Extrai o intervalo completo do horário no formato "08:00 às 11:00"
     * 
     * @param string|null $horarioConfirmadoRaw Horário no formato raw (ex: "25/11/2025 - 08:00-11:00")
     * @param string|null $horarioAgendamento Horário simples (ex: "08:00")
     * @param array|null $solicitacao Dados completos da solicitação
     * @return string Horário no formato "08:00 às 11:00" ou apenas "08:00" se não houver intervalo
     */
    private function extrairIntervaloHorario(?string $horarioConfirmadoRaw, ?string $horarioAgendamento, ?array $solicitacao = null): string
    {
        // Tentar extrair de horario_confirmado_raw primeiro
        if (!empty($horarioConfirmadoRaw)) {
            // Formato: "25/11/2025 - 08:00-11:00" ou "08:00-11:00"
            if (preg_match('/(\d{2}:\d{2})(?::\d{2})?-(\d{2}:\d{2})(?::\d{2})?/', $horarioConfirmadoRaw, $matches)) {
                $horaInicio = $matches[1];
                $horaFim = $matches[2];
                return $horaInicio . ' às ' . $horaFim;
            }
        }
        
        // Tentar extrair de confirmed_schedules
        if (!empty($solicitacao['confirmed_schedules'])) {
            $confirmed = is_string($solicitacao['confirmed_schedules']) 
                ? json_decode($solicitacao['confirmed_schedules'], true) 
                : $solicitacao['confirmed_schedules'];
            
            if (is_array($confirmed) && !empty($confirmed)) {
                // Pegar o último horário confirmado
                $ultimo = end($confirmed);
                if (!empty($ultimo['raw'])) {
                    // Formato: "25/11/2025 - 08:00-11:00"
                    if (preg_match('/(\d{2}:\d{2})(?::\d{2})?-(\d{2}:\d{2})(?::\d{2})?/', $ultimo['raw'], $matches)) {
                        $horaInicio = $matches[1];
                        $horaFim = $matches[2];
                        return $horaInicio . ' às ' . $horaFim;
                    }
                }
                // Tentar extrair de 'time' se existir
                if (!empty($ultimo['time']) && preg_match('/(\d{2}:\d{2})(?::\d{2})?-(\d{2}:\d{2})(?::\d{2})?/', $ultimo['time'], $matches)) {
                    $horaInicio = $matches[1];
                    $horaFim = $matches[2];
                    return $horaInicio . ' às ' . $horaFim;
                }
            }
        }
        
        // Tentar extrair de horarios_opcoes
        if (!empty($solicitacao['horarios_opcoes'])) {
            $horarios = is_string($solicitacao['horarios_opcoes']) 
                ? json_decode($solicitacao['horarios_opcoes'], true) 
                : $solicitacao['horarios_opcoes'];
            
            if (is_array($horarios) && !empty($horarios)) {
                // Pegar o primeiro horário disponível
                $primeiro = reset($horarios);
                if (is_string($primeiro) && preg_match('/(\d{2}:\d{2})(?::\d{2})?-(\d{2}:\d{2})(?::\d{2})?/', $primeiro, $matches)) {
                    $horaInicio = $matches[1];
                    $horaFim = $matches[2];
                    return $horaInicio . ' às ' . $horaFim;
                }
            }
        }
        
        // Fallback: retornar apenas o horário inicial se disponível
        if (!empty($horarioAgendamento)) {
            // Remover segundos se existirem
            $horario = preg_replace('/:00$/', '', $horarioAgendamento);
            return $horario;
        }
        
        return '';
    }

    /**
     * Método removido: naoQualificadas()
     * 
     * As solicitações não qualificadas agora aparecem na lista de "Solicitações Manuais".
     * Removido para consolidar tudo em uma única lista.
     */

    /**
     * Atualizar tipo de qualificação de uma solicitação
     */
    public function atualizarQualificacao(string $tipo, int $id): void
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }
        
        $tipoQualificacao = $this->input('tipo_qualificacao');
        $observacoesQualificacao = trim($this->input('observacoes_qualificacao') ?? '');
        
        // Validar observação obrigatória quando tipo_qualificacao = 'NAO_QUALIFICADA'
        if ($tipoQualificacao === 'NAO_QUALIFICADA' && empty($observacoesQualificacao)) {
            $this->json([
                'success' => false, 
                'error' => 'Observação é obrigatória para solicitações não qualificadas'
            ], 400);
            return;
        }
        
        // Permitir valores válidos do ENUM ou NULL (string vazia também vira NULL)
        if ($tipoQualificacao === '' || $tipoQualificacao === null) {
            $tipoQualificacao = null;
        } elseif (!in_array($tipoQualificacao, ['BOLSAO', 'CORTESIA', 'NAO_QUALIFICADA', 'REGRA_2'])) {
            $this->json(['success' => false, 'error' => 'Tipo de qualificação inválido'], 400);
            return;
        }
        
        try {
            if ($tipo === 'manual') {
                $model = new \App\Models\SolicitacaoManual();
            } else {
                $model = new \App\Models\Solicitacao();
            }
            
            $solicitacao = $model->find($id);
            if (!$solicitacao) {
                $this->json(['success' => false, 'error' => 'Solicitação não encontrada'], 404);
                return;
            }
            
            // Validar se o tipo_qualificacao é válido (já validado acima, mas garantir)
            if ($tipoQualificacao !== null && !in_array($tipoQualificacao, ['BOLSAO', 'CORTESIA', 'NAO_QUALIFICADA', 'REGRA_2'])) {
                $this->json(['success' => false, 'error' => 'Tipo de qualificação inválido'], 400);
                return;
            }
            
            // Preparar dados para atualização
            $dadosUpdate = ['tipo_qualificacao' => $tipoQualificacao];
            
            // Salvar observação de qualificação no campo específico
            // Limpar campo se tipo não for CORTESIA ou NAO_QUALIFICADA
            if (in_array($tipoQualificacao, ['CORTESIA', 'NAO_QUALIFICADA'])) {
                $dadosUpdate['observacao_qualificacao'] = !empty($observacoesQualificacao) ? trim($observacoesQualificacao) : null;
            } else {
                $dadosUpdate['observacao_qualificacao'] = null;
            }
            
            $atualizado = $model->update($id, $dadosUpdate);
            
            if ($atualizado) {
                // Se tipo_qualificacao for NAO_QUALIFICADA, atualizar status também
                if ($tipoQualificacao === 'NAO_QUALIFICADA') {
                    $statusModel = new \App\Models\Status();
                    // Tentar ambos os nomes possíveis (com e sem S)
                    $statusNaoQualificado = $statusModel->findByNome('Não qualificado');
                    if (!$statusNaoQualificado) {
                        $statusNaoQualificado = $statusModel->findByNome('Não Qualificados');
                    }
                    
                    if ($statusNaoQualificado && !empty($statusNaoQualificado['id'])) {
                        // Atualizar status da solicitação
                        if ($tipo === 'manual') {
                            // Se for manual, atualizar status_id na solicitação manual
                            $model->update($id, ['status_id' => (int)$statusNaoQualificado['id']]);
                        } else {
                            // Se for normal, atualizar status_id na solicitação
                            $solicitacaoModel = new \App\Models\Solicitacao();
                            $solicitacaoModel->update($id, ['status_id' => (int)$statusNaoQualificado['id']]);
                            
                            // Registrar no histórico
                            $usuarioId = $_SESSION['user_id'] ?? null;
                            if ($usuarioId) {
                                $sqlHistorico = "
                                    INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                                    VALUES (?, ?, ?, ?, NOW())
                                ";
                                \App\Core\Database::query($sqlHistorico, [
                                    $id,
                                    (int)$statusNaoQualificado['id'],
                                    $usuarioId,
                                    'Marcado como Não qualificado: ' . $observacoesQualificacao
                                ]);
                            }
                            
                            // Enviar mensagem WhatsApp com a observação
                            try {
                                $this->enviarNotificacaoWhatsApp($id, 'Não Qualificado', [
                                    'observacao' => $observacoesQualificacao
                                ]);
                            } catch (\Exception $e) {
                                error_log('Erro ao enviar WhatsApp para não qualificado [ID:' . $id . ']: ' . $e->getMessage());
                                // Não bloquear a resposta de sucesso se o WhatsApp falhar
                            }
                        }
                    }
                }
                
                $this->json([
                    'success' => true,
                    'message' => 'Tipo de qualificação atualizado com sucesso',
                    'tipo_qualificacao' => $tipoQualificacao
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'Erro ao atualizar tipo de qualificação'], 500);
            }
        } catch (\Exception $e) {
            error_log('Erro ao atualizar qualificação: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Corrigir solicitação que foi marcada como "Não Qualificado" automaticamente
     * Move para "Solicitação Manual" quando validacao_utilizacao = 0
     * POST /admin/solicitacoes/{id}/corrigir-para-manual
     */
    public function corrigirParaManual(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }
        
        try {
            // Buscar dados da solicitação
            $solicitacao = $this->solicitacaoModel->find($id);
            
            if (!$solicitacao) {
                $this->json(['success' => false, 'error' => 'Solicitação não encontrada'], 404);
                return;
            }
            
            // Verificar se já existe solicitação manual para este CPF/imobiliária/categoria na mesma data
            $sqlManual = "SELECT id FROM solicitacoes_manuais 
                          WHERE cpf = ? 
                          AND imobiliaria_id = ? 
                          AND categoria_id = ?
                          AND DATE(created_at) = DATE(?)";
            $solicitacaoManualExistente = \App\Core\Database::fetch($sqlManual, [
                $solicitacao['locatario_cpf'],
                $solicitacao['imobiliaria_id'],
                $solicitacao['categoria_id'],
                $solicitacao['created_at']
            ]);
            
            $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
            
            // Extrair tipo_imovel e local_manutencao das observações
            $tipoImovel = 'RESIDENCIAL';
            $localManutencao = null;
            
            if (!empty($solicitacao['observacoes'])) {
                $observacoes = $solicitacao['observacoes'];
                if (preg_match('/Tipo:\s*(\w+)/i', $observacoes, $matches)) {
                    $tipoImovel = strtoupper($matches[1]);
                }
                $linhas = explode("\n", $observacoes);
                if (!empty($linhas[0])) {
                    $localManutencao = trim($linhas[0]);
                }
            }
            
            if ($solicitacaoManualExistente) {
                // Atualizar solicitação manual existente
                $dadosAtualizacao = [
                    'nome_completo' => $solicitacao['locatario_nome'],
                    'whatsapp' => $solicitacao['locatario_telefone'],
                    'endereco' => $solicitacao['imovel_endereco'],
                    'numero' => $solicitacao['imovel_numero'],
                    'complemento' => $solicitacao['imovel_complemento'],
                    'bairro' => $solicitacao['imovel_bairro'],
                    'cidade' => $solicitacao['imovel_cidade'],
                    'estado' => $solicitacao['imovel_estado'],
                    'cep' => $solicitacao['imovel_cep'],
                    'descricao_problema' => $solicitacao['descricao_problema'],
                    'numero_contrato' => $solicitacao['numero_contrato'],
                    'validacao_bolsao' => $solicitacao['validacao_bolsao'] ?? 0,
                    'tipo_qualificacao' => null,
                    'observacao_qualificacao' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $solicitacaoManualModel->update($solicitacaoManualExistente['id'], $dadosAtualizacao);
                $solicitacaoManualId = $solicitacaoManualExistente['id'];
            } else {
                // Criar nova solicitação manual
                $dadosManual = [
                    'imobiliaria_id' => $solicitacao['imobiliaria_id'],
                    'nome_completo' => $solicitacao['locatario_nome'],
                    'cpf' => $solicitacao['locatario_cpf'],
                    'whatsapp' => $solicitacao['locatario_telefone'],
                    'tipo_imovel' => $tipoImovel,
                    'cep' => $solicitacao['imovel_cep'],
                    'endereco' => $solicitacao['imovel_endereco'],
                    'numero' => $solicitacao['imovel_numero'],
                    'complemento' => $solicitacao['imovel_complemento'],
                    'bairro' => $solicitacao['imovel_bairro'],
                    'cidade' => $solicitacao['imovel_cidade'],
                    'estado' => $solicitacao['imovel_estado'],
                    'categoria_id' => $solicitacao['categoria_id'],
                    'subcategoria_id' => $solicitacao['subcategoria_id'],
                    'numero_contrato' => $solicitacao['numero_contrato'],
                    'local_manutencao' => $localManutencao,
                    'descricao_problema' => $solicitacao['descricao_problema'],
                    'observacoes' => $solicitacao['observacoes'],
                    'validacao_bolsao' => $solicitacao['validacao_bolsao'] ?? 0,
                    'tipo_qualificacao' => null,
                    'observacao_qualificacao' => null,
                    'termos_aceitos' => 1,
                    'created_at' => $solicitacao['created_at'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $solicitacaoManualId = $solicitacaoManualModel->create($dadosManual);
            }
            
            // Atualizar a solicitação original: remover tipo_qualificacao e observacao_qualificacao
            $this->solicitacaoModel->update($id, [
                'tipo_qualificacao' => null,
                'observacao_qualificacao' => null
            ]);
            
            // Atualizar status para "Nova Solicitação"
            $statusNova = $this->statusModel->findByNome('Nova Solicitação') 
                       ?? $this->statusModel->findByNome('Nova') 
                       ?? $this->statusModel->findByNome('NOVA');
            
            if ($statusNova) {
                $this->solicitacaoModel->update($id, ['status_id' => $statusNova['id']]);
            }
            
            $this->json([
                'success' => true,
                'message' => 'Solicitação corrigida com sucesso! Movida para Solicitação Manual.',
                'solicitacao_manual_id' => $solicitacaoManualId
            ]);
            
        } catch (\Exception $e) {
            error_log('Erro ao corrigir solicitação: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }
}



