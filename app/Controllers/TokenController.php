<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ScheduleConfirmationToken;
use App\Models\Solicitacao;

/**
 * Controller para processar tokens de confirmação/cancelamento de horários
 * 
 * Rotas públicas que não requerem autenticação
 */
class TokenController extends Controller
{
    private ScheduleConfirmationToken $tokenModel;
    private Solicitacao $solicitacaoModel;

    public function __construct()
    {
        // Não requer autenticação - rotas públicas
        $this->tokenModel = new ScheduleConfirmationToken();
        $this->solicitacaoModel = new Solicitacao();
    }

    /**
     * Exibe página de confirmação de horário
     * GET /confirmacao-horario?token=xxx
     */
    public function confirmacaoHorario(): void
    {
        $token = $this->input('token');

        if (!$token) {
            $this->view('token.error', [
                'title' => 'Token Inválido',
                'message' => 'Token não fornecido. Por favor, use o link completo enviado no WhatsApp.',
                'error_type' => 'missing_token'
            ]);
            return;
        }

        // Validar token
        $tokenData = $this->tokenModel->validateToken($token);

        if (!$tokenData) {
            $this->view('token.error', [
                'title' => 'Token Inválido ou Expirado',
                'message' => 'Este link de confirmação é inválido ou expirou. Por favor, entre em contato conosco.',
                'error_type' => 'invalid_token'
            ]);
            return;
        }

        // Buscar dados da solicitação com todos os campos, incluindo numero_solicitacao
        $sql = "
            SELECT s.*, 
                   st.nome as status_nome,
                   st.cor as status_cor
            FROM solicitacoes s
            LEFT JOIN status st ON s.status_id = st.id
            WHERE s.id = ?
        ";
        $solicitacao = \App\Core\Database::fetch($sql, [$tokenData['solicitacao_id']]);

        if (!$solicitacao) {
            $this->view('token.error', [
                'title' => 'Solicitação Não Encontrada',
                'message' => 'A solicitação associada a este token não foi encontrada.',
                'error_type' => 'solicitacao_not_found'
            ]);
            return;
        }

        // Debug: Log completo da solicitação ANTES de processar
        error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - Solicitação completa: " . json_encode([
            'id' => $solicitacao['id'] ?? null,
            'numero_solicitacao' => $solicitacao['numero_solicitacao'] ?? null,
            'horarios_indisponiveis' => $solicitacao['horarios_indisponiveis'] ?? null,
            'horarios_indisponiveis_type' => gettype($solicitacao['horarios_indisponiveis'] ?? null),
            'horarios_opcoes' => $solicitacao['horarios_opcoes'] ?? null,
            'horarios_opcoes_type' => gettype($solicitacao['horarios_opcoes'] ?? null),
            'confirmed_schedules' => $solicitacao['confirmed_schedules'] ?? null,
            'data_agendamento' => $solicitacao['data_agendamento'] ?? null,
            'horario_agendamento' => $solicitacao['horario_agendamento'] ?? null
        ]));

        // Se já foi processado (POST), processar confirmação
        if ($this->isPost()) {
            $this->processarConfirmacao($token, $tokenData, $solicitacao);
            return;
        }

        // Buscar horários disponíveis para seleção
        $horariosDisponiveis = [];
        
        // IMPORTANTE: Se horarios_indisponiveis = 1, horarios_opcoes contém os horários da seguradora
        // Esses são os horários que o locatário deve escolher
        $horariosIndisponiveis = $solicitacao['horarios_indisponiveis'] ?? 0;
        // Normalizar para inteiro (pode vir como string "1" ou "0", boolean true/false, ou NULL)
        if ($horariosIndisponiveis === true || $horariosIndisponiveis === 'true' || $horariosIndisponiveis === '1' || $horariosIndisponiveis === 1) {
            $horariosIndisponiveis = 1;
        } else {
            $horariosIndisponiveis = 0;
        }
        $horariosOpcoesRaw = $solicitacao['horarios_opcoes'] ?? null;
        
        error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - horariosIndisponiveis (original): " . var_export($solicitacao['horarios_indisponiveis'] ?? null, true));
        error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - horariosIndisponiveis (normalizado): " . var_export($horariosIndisponiveis, true));
        error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - horariosOpcoesRaw: " . var_export($horariosOpcoesRaw, true));
        error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - horariosOpcoesRaw empty?: " . var_export(empty($horariosOpcoesRaw), true));
        
        // PRIORIDADE 1: Verificar confirmed_schedules (horários marcados pelo admin)
        // IMPORTANTE: Se o admin marcou checkboxes, os horários estão em confirmed_schedules
        if (!empty($solicitacao['confirmed_schedules'])) {
            $confirmedSchedules = json_decode($solicitacao['confirmed_schedules'], true);
            if (is_array($confirmedSchedules) && !empty($confirmedSchedules)) {
                foreach ($confirmedSchedules as $schedule) {
                    if (!empty($schedule['raw'])) {
                        // Normalizar formato do raw se necessário
                        $raw = $schedule['raw'];
                        // Remover segundos se houver
                        if (strpos($raw, ':00:00') !== false) {
                            $raw = preg_replace('/(\d{2}:\d{2}):\d{2}-(\d{2}:\d{2}):\d{2}/', '$1-$2', $raw);
                        }
                        
                        $horariosDisponiveis[] = [
                            'raw' => $raw,
                            'date' => $schedule['date'] ?? '',
                            'time' => $schedule['time'] ?? ''
                        ];
                        error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - ✅ Horário de confirmed_schedules adicionado: {$raw}");
                    }
                }
            }
        }
        
        // PRIORIDADE 2: Se horarios_indisponiveis = 1, buscar horários da seguradora em horarios_opcoes
        if (empty($horariosDisponiveis) && $horariosIndisponiveis == 1 && !empty($horariosOpcoesRaw)) {
            $horariosSeguradora = json_decode($horariosOpcoesRaw, true);
            error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - horariosSeguradora parseado: " . var_export($horariosSeguradora, true));
            
            if (is_array($horariosSeguradora) && !empty($horariosSeguradora)) {
                foreach ($horariosSeguradora as $index => $horario) {
                    error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - Processando horário [{$index}]: " . var_export($horario, true));
                    
                    if (is_string($horario)) {
                        // Formato esperado: "dd/mm/yyyy - HH:MM:SS-HH:MM:SS" ou "dd/mm/yyyy - HH:MM-HH:MM"
                        // Exemplo: "11/11/2025 - 08:00:00-11:00:00" ou "11/11/2025 - 08:00-11:00"
                        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s*-\s*(\d{2}):(\d{2})(?::(\d{2}))?-(\d{2}):(\d{2})(?::(\d{2}))?/', $horario, $matches)) {
                            $dataFormatada = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                            $horaInicio = $matches[4] . ':' . $matches[5];
                            $horaFim = $matches[7] . ':' . $matches[8];
                            
                            // Normalizar raw para formato padrão sem segundos se necessário
                            $rawNormalizado = $horario;
                            if (strpos($horario, ':00:00') !== false) {
                                // Remover segundos do formato de exibição
                                $rawNormalizado = preg_replace('/(\d{2}:\d{2}):\d{2}-(\d{2}:\d{2}):\d{2}/', '$1-$2', $horario);
                            }
                            
                            $horariosDisponiveis[] = [
                                'raw' => $rawNormalizado,
                                'date' => $dataFormatada,
                                'time' => $horaInicio . '-' . $horaFim
                            ];
                            
                            error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - ✅ Horário adicionado: {$rawNormalizado}");
                        } elseif (preg_match('/(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}:\d{2})-(\d{2}:\d{2}:\d{2})/', $horario, $matches)) {
                            // Formato: "YYYY-MM-DD HH:MM:SS-HH:MM:SS"
                            $dataFormatada = $matches[1];
                            $horaInicio = substr($matches[2], 0, 5); // HH:MM
                            $horaFim = substr($matches[3], 0, 5); // HH:MM
                            $rawFormatado = date('d/m/Y', strtotime($dataFormatada)) . ' - ' . $horaInicio . '-' . $horaFim;
                            $horariosDisponiveis[] = [
                                'raw' => $rawFormatado,
                                'date' => $dataFormatada,
                                'time' => $horaInicio . '-' . $horaFim
                            ];
                            
                            error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - ✅ Horário adicionado (formato ISO): {$rawFormatado}");
                        } else {
                            error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - ⚠️ Horário não correspondeu a nenhum padrão: {$horario}");
                        }
                    } else {
                        error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - ⚠️ Horário não é string: " . gettype($horario));
                    }
                }
            } else {
                error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - ⚠️ horariosSeguradora não é array ou está vazio");
            }
        } else {
            if (empty($horariosDisponiveis)) {
                error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - ⚠️ Condição não atendida: horariosIndisponiveis={$horariosIndisponiveis}, horariosOpcoesRaw=" . ($horariosOpcoesRaw ? 'tem valor' : 'vazio'));
            }
        }
        
        // Se ainda não houver horários, verificar horarios_opcoes (horários originais do locatário)
        if (empty($horariosDisponiveis) && !empty($solicitacao['horarios_opcoes'])) {
            $horariosOpcoes = json_decode($solicitacao['horarios_opcoes'], true);
            if (is_array($horariosOpcoes) && !empty($horariosOpcoes)) {
                foreach ($horariosOpcoes as $horario) {
                    if (!is_string($horario)) {
                        continue;
                    }

                    // Formato: "dd/mm/yyyy - HH:MM-HH:MM" (segundos opcionais)
                    if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s*-\s*(\d{2}):(\d{2})(?::(\d{2}))?\s*-\s*(\d{2}):(\d{2})(?::(\d{2}))?/', $horario, $matches)) {
                        $dia = (int) $matches[1];
                        $mes = (int) $matches[2];
                        $ano = (int) $matches[3];
                        $horaInicio = (int) $matches[4];
                        $minInicio = (int) $matches[5];
                        $horaFim = (int) $matches[7];
                        $minFim = (int) $matches[8];

                        $dataFormatada = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                        $horaInicioFmt = sprintf('%02d:%02d', $horaInicio, $minInicio);
                        $horaFimFmt = sprintf('%02d:%02d', $horaFim, $minFim);
                        $rawNormalizado = sprintf('%02d/%02d/%04d - %s-%s', $dia, $mes, $ano, $horaInicioFmt, $horaFimFmt);

                        $horariosDisponiveis[] = [
                            'raw' => $rawNormalizado,
                            'date' => $dataFormatada,
                            'time' => $horaInicioFmt . '-' . $horaFimFmt
                        ];
                        error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - ✅ Horário adicionado (locatário): {$rawNormalizado}");
                    }
                }
            }
        }
        
        // Se ainda não houver horários, usar o horário do token
        if (empty($horariosDisponiveis) && !empty($tokenData['scheduled_date']) && !empty($tokenData['scheduled_time'])) {
            $dataFormatada = date('d/m/Y', strtotime($tokenData['scheduled_date']));
            $horariosDisponiveis[] = [
                'raw' => $dataFormatada . ' - ' . $tokenData['scheduled_time'],
                'date' => $tokenData['scheduled_date'],
                'time' => $tokenData['scheduled_time']
            ];
        }
        
        // Debug: Log final dos horários disponíveis
        error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - Total de horários disponíveis: " . count($horariosDisponiveis));
        error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - Horários: " . json_encode($horariosDisponiveis));
        
        // Se ainda não houver horários e houver data_agendamento e horario_agendamento, criar um horário a partir deles
        if (empty($horariosDisponiveis) && !empty($solicitacao['data_agendamento']) && !empty($solicitacao['horario_agendamento'])) {
            $dataFormatada = date('d/m/Y', strtotime($solicitacao['data_agendamento']));
            $horarioFormatado = date('H:i', strtotime($solicitacao['horario_agendamento']));
            $horariosDisponiveis[] = [
                'raw' => $dataFormatada . ' - ' . $horarioFormatado,
                'date' => $solicitacao['data_agendamento'],
                'time' => $horarioFormatado
            ];
            error_log("DEBUG confirmacaoHorario [ID:{$tokenData['solicitacao_id']}] - Criado horário a partir de data_agendamento e horario_agendamento");
        }

        // Exibir formulário de confirmação
        $this->view('token.confirmacao', [
            'token' => $token,
            'tokenData' => $tokenData,
            'solicitacao' => $solicitacao,
            'horariosDisponiveis' => $horariosDisponiveis,
            'title' => 'Confirmar Horário de Atendimento'
        ]);
    }

    /**
     * Processa confirmação de horário
     */
    private function processarConfirmacao(string $token, array $tokenData, array $solicitacao): void
    {
        try {
            // Buscar horário selecionado pelo usuário
            $horarioSelecionado = $this->input('horario_selecionado');
            
            // Se não foi selecionado, usar o primeiro disponível
            $dataAgendamento = null;
            $horarioAgendamento = null;
            $horarioRaw = null;
            
            if ($horarioSelecionado) {
                // Decodificar o horário selecionado (vem como JSON string)
                $horarioData = json_decode($horarioSelecionado, true);
                if ($horarioData && !empty($horarioData['raw'])) {
                    $horarioRaw = $horarioData['raw'];
                    
                    // Extrair data do raw (formato: "dd/mm/yyyy - HH:MM-HH:MM")
                    if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $horarioRaw, $dateMatches)) {
                        $dataAgendamento = $dateMatches[3] . '-' . $dateMatches[2] . '-' . $dateMatches[1];
                    } elseif (!empty($horarioData['date'])) {
                        $dataAgendamento = date('Y-m-d', strtotime($horarioData['date']));
                    }
                    
                    // Extrair apenas a hora inicial se for uma faixa (ex: "14:00-17:00")
                    if (preg_match('/(\d{2}:\d{2})-\d{2}:\d{2}/', $horarioRaw, $timeMatches)) {
                        $horarioAgendamento = $timeMatches[1];
                    } elseif (!empty($horarioData['time'])) {
                        $horarioAgendamento = preg_replace('/-.*$/', '', $horarioData['time']);
                    }
                }
            }
            
            // Se não conseguiu extrair, usar dados do token
            if (!$horarioRaw && !empty($tokenData['scheduled_date']) && !empty($tokenData['scheduled_time'])) {
                $dataAgendamento = date('Y-m-d', strtotime($tokenData['scheduled_date']));
                $horarioAgendamento = preg_replace('/-.*$/', '', $tokenData['scheduled_time']);
                $horarioRaw = date('d/m/Y', strtotime($tokenData['scheduled_date'])) . ' - ' . $tokenData['scheduled_time'];
            }

            // Marcar token como usado
            $this->tokenModel->markAsUsed($token, 'confirmed');

            // ✅ CICLO DE AGENDAMENTO: Quando locatário confirma, mudar DIRETAMENTE para "Serviço Agendado"
            $condicaoModel = new \App\Models\Condicao();
            $statusModel = new \App\Models\Status();
            
            // Buscar status "Serviço Agendado" DIRETAMENTE (não usar "Agendamento Confirmado")
            // Quando o locatário aceita o horário, deve ir direto para "Serviço Agendado"
            $statusAgendamentoConfirmado = $statusModel->findByNome('Serviço Agendado');
            if (!$statusAgendamentoConfirmado) {
                $statusAgendamentoConfirmado = $statusModel->findByNome('Serviço agendado');
            }
            if (!$statusAgendamentoConfirmado) {
                // Fallback: buscar por LIKE
                $sqlStatus = "SELECT * FROM status WHERE nome LIKE '%Serviço Agendado%' AND status = 'ATIVO' LIMIT 1";
                $statusAgendamentoConfirmado = \App\Core\Database::fetch($sqlStatus);
            }
            
            error_log("DEBUG processarConfirmacao [ID:{$solicitacao['id']}] - Status encontrado para confirmação: " . 
                ($statusAgendamentoConfirmado ? $statusAgendamentoConfirmado['nome'] . " (ID: {$statusAgendamentoConfirmado['id']})" : 'NÃO ENCONTRADO'));
            
            // Buscar condição "Data Aceita pelo Locatário"
            $condicaoAceita = $condicaoModel->findByNome('Data Aceita pelo Locatário');
            
            $dadosUpdate = [
                'horario_confirmado' => 1
            ];
            
            if ($condicaoAceita) {
                $dadosUpdate['condicao_id'] = $condicaoAceita['id'];
            }
            if ($statusAgendamentoConfirmado) {
                $dadosUpdate['status_id'] = $statusAgendamentoConfirmado['id'];
            }
            
            // Atualizar data e horário se foram selecionados
            if ($dataAgendamento) {
                $dadosUpdate['data_agendamento'] = $dataAgendamento;
            }
            if ($horarioAgendamento) {
                $dadosUpdate['horario_agendamento'] = $horarioAgendamento . ':00';
            }
            if ($horarioRaw) {
                $dadosUpdate['horario_confirmado_raw'] = $horarioRaw;
                
                // IMPORTANTE: Salvar também em confirmed_schedules para que o admin possa ver e confirmar
                // Extrair horário completo do raw (formato: "dd/mm/yyyy - HH:MM-HH:MM")
                $horarioCompleto = $horarioRaw;
                if (preg_match('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $horarioRaw, $timeMatches)) {
                    $horarioCompleto = $timeMatches[1] . '-' . $timeMatches[2];
                } elseif ($horarioAgendamento) {
                    // Se não encontrou faixa, usar apenas o horário inicial
                    $horarioCompleto = $horarioAgendamento;
                }
                
                $confirmedSchedule = [
                    'date' => $dataAgendamento,
                    'time' => $horarioCompleto,
                    'raw' => $horarioRaw,
                    'source' => 'tenant',
                    'confirmed_at' => date('c')
                ];
                
                // Buscar confirmed_schedules existentes e adicionar o novo
                $confirmedSchedules = [];
                if (!empty($solicitacao['confirmed_schedules'])) {
                    $existing = json_decode($solicitacao['confirmed_schedules'], true);
                    if (is_array($existing)) {
                        $confirmedSchedules = $existing;
                    }
                }
                
                // Adicionar o novo horário confirmado pelo locatário
                $confirmedSchedules[] = $confirmedSchedule;
                $dadosUpdate['confirmed_schedules'] = json_encode($confirmedSchedules);
            }
            
            // ✅ ATUALIZAR status para "Serviço Agendado" DIRETAMENTE quando locatário aceita
            // Não é necessário aguardar confirmação do admin - o locatário aceitou o horário proposto
            
            error_log("DEBUG processarConfirmacao [ID:{$solicitacao['id']}] - Dados de update: " . json_encode([
                'horario_confirmado' => $dadosUpdate['horario_confirmado'] ?? null,
                'status_id' => $dadosUpdate['status_id'] ?? null,
                'condicao_id' => $dadosUpdate['condicao_id'] ?? null,
                'data_agendamento' => $dadosUpdate['data_agendamento'] ?? null,
                'horario_agendamento' => $dadosUpdate['horario_agendamento'] ?? null,
                'horario_confirmado_raw' => $dadosUpdate['horario_confirmado_raw'] ?? null
            ]));
            
                $updateCallback = function() use ($dadosUpdate, $solicitacao) {
                    $this->solicitacaoModel->update($solicitacao['id'], $dadosUpdate);

                    // Registrar no histórico diretamente
                    $sql = "
                        INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ";
                    \App\Core\Database::query($sql, [
                        $solicitacao['id'],
                        $dadosUpdate['status_id'] ?? $solicitacao['status_id'],
                    null, // Usuário sistema (null = cliente/locatário)
                    'Horário aceito pelo locatário via link de confirmação. Status atualizado para "Serviço Agendado" e horário confirmado.'
                    ]);
                
                error_log("DEBUG processarConfirmacao [ID:{$solicitacao['id']}] - ✅ Solicitação atualizada com sucesso para 'Serviço Agendado'");
                };

                if ($this->isStatusCancelado($solicitacao['status_id'])) {
                    $this->logReagendamento([
                        'status' => 'CONFIRMACAO_BLOQUEADA',
                        'token' => $token,
                        'token_id' => $tokenData['id'] ?? null,
                        'solicitacao_id' => $solicitacao['id'],
                        'motivo' => 'Solicitação cancelada, confirmação ignorada'
                    ]);
                } else {
                    $updateCallback();
                }

            // Exibir página de sucesso
            $this->view('token.sucesso', [
                'title' => 'Horário Confirmado',
                'message' => 'Seu horário foi confirmado com sucesso!',
                'solicitacao' => $solicitacao,
                'tokenData' => $tokenData,
                'action' => 'confirmacao'
            ]);

        } catch (\Exception $e) {
            error_log('Erro ao processar confirmação de horário: ' . $e->getMessage());
            $this->view('token.error', [
                'title' => 'Erro ao Confirmar',
                'message' => 'Ocorreu um erro ao processar sua confirmação. Por favor, tente novamente ou entre em contato conosco.',
                'error_type' => 'processing_error'
            ]);
        }
    }

    /**
     * Exibe página de cancelamento de horário
     * GET /cancelamento-horario?token=xxx
     */
    public function cancelamentoHorario(): void
    {
        $token = $this->input('token');

        if (!$token) {
            $this->view('token.error', [
                'title' => 'Token Inválido',
                'message' => 'Token não fornecido. Por favor, use o link completo enviado no WhatsApp.',
                'error_type' => 'missing_token'
            ]);
            return;
        }

        // Validar token (permitir tokens já usados para cancelamento, mas verificar se não expirou)
        $tokenData = $this->tokenModel->validateToken($token);
        
        // Se o token não for válido (não usado e não expirado), tentar buscar mesmo se já foi usado
        if (!$tokenData) {
            $sql = "
                SELECT * FROM schedule_confirmation_tokens
                WHERE token = ?
                AND expires_at > NOW()
            ";
            $tokenData = \App\Core\Database::fetch($sql, [$token]);
        }

        if (!$tokenData) {
            error_log('Cancelamento - Token inválido ou expirado: ' . $token);
            $this->view('token.error', [
                'title' => 'Token Inválido ou Expirado',
                'message' => 'Este link de cancelamento é inválido ou expirou. Por favor, entre em contato conosco.',
                'error_type' => 'invalid_token'
            ]);
            return;
        }
        
        // Verificar se o token já foi usado para cancelamento
        if ($tokenData['used_at'] && $tokenData['action_type'] === 'cancelled') {
            error_log('Cancelamento - Token já foi usado para cancelamento: ' . $token);
            // Mesmo assim, permitir ver a solicitação cancelada
            $sql = "
                SELECT s.*, 
                       st.nome as status_nome,
                       st.cor as status_cor
                FROM solicitacoes s
                LEFT JOIN status st ON s.status_id = st.id
                WHERE s.id = ?
            ";
            $solicitacao = \App\Core\Database::fetch($sql, [$tokenData['solicitacao_id']]);
            
            if ($solicitacao) {
                $this->view('token.sucesso', [
                    'title' => 'Solicitação Já Cancelada',
                    'message' => 'Esta solicitação já foi cancelada anteriormente.',
                    'solicitacao' => $solicitacao,
                    'tokenData' => $tokenData,
                    'action' => 'cancelamento'
                ]);
                return;
            }
        }

        // Buscar dados da solicitação com todos os campos, incluindo numero_solicitacao
        $sql = "
            SELECT s.*, 
                   st.nome as status_nome,
                   st.cor as status_cor
            FROM solicitacoes s
            LEFT JOIN status st ON s.status_id = st.id
            WHERE s.id = ?
        ";
        $solicitacao = \App\Core\Database::fetch($sql, [$tokenData['solicitacao_id']]);

        if (!$solicitacao) {
            $this->view('token.error', [
                'title' => 'Solicitação Não Encontrada',
                'message' => 'A solicitação associada a este token não foi encontrada.',
                'error_type' => 'solicitacao_not_found'
            ]);
            return;
        }

        // Validar se ainda é possível cancelar (até 1 hora antes do horário confirmado)
        $podeCancelar = $this->validarPrazoCancelamento($solicitacao);
        if (!$podeCancelar['permitido']) {
            $this->view('token.error', [
                'title' => 'Prazo de Cancelamento Expirado',
                'message' => $podeCancelar['mensagem'] ?? 'Não é mais possível cancelar este agendamento. O prazo para cancelamento expirou (1 hora antes do horário confirmado). Por favor, entre em contato conosco.',
                'error_type' => 'prazo_expirado'
            ]);
            return;
        }

        // Se já foi processado (POST), processar cancelamento
        if ($this->isPost()) {
            $this->processarCancelamento($token, $tokenData, $solicitacao);
            return;
        }

        // Exibir formulário de cancelamento
        $this->view('token.cancelamento', [
            'token' => $token,
            'tokenData' => $tokenData,
            'solicitacao' => $solicitacao,
            'title' => 'Cancelar Horário de Atendimento'
        ]);
    }

    /**
     * Processa cancelamento de horário
     * Fecha a solicitação no sistema (status "Cancelado")
     */
    private function processarCancelamento(string $token, array $tokenData, array $solicitacao): void
    {
        try {
            $motivo = $this->input('motivo', 'Horário cancelado pelo cliente via link de cancelamento');

            // Validar se ainda é possível cancelar (até 1 hora antes do horário confirmado)
            $podeCancelar = $this->validarPrazoCancelamento($solicitacao);
            if (!$podeCancelar['permitido']) {
                $this->view('token.error', [
                    'title' => 'Prazo de Cancelamento Expirado',
                    'message' => $podeCancelar['mensagem'] ?? 'Não é mais possível cancelar este agendamento. O prazo para cancelamento expirou (1 hora antes do horário confirmado). Por favor, entre em contato conosco.',
                    'error_type' => 'prazo_expirado'
                ]);
                return;
            }

            // Validar token antes de processar (permitir tokens já usados, mas verificar se não expirou)
            $tokenValidado = $this->tokenModel->validateToken($token);
            
            if (!$tokenValidado) {
                // Tentar buscar token mesmo se já foi usado, mas verificar se não expirou
                $sql = "
                    SELECT * FROM schedule_confirmation_tokens
                    WHERE token = ?
                    AND expires_at > NOW()
                ";
                $tokenValidado = \App\Core\Database::fetch($sql, [$token]);
            }
            
            if (!$tokenValidado) {
                error_log('Cancelamento - Token inválido ou expirado: ' . $token);
                $this->view('token.error', [
                    'title' => 'Token Inválido',
                    'message' => 'Este link de cancelamento é inválido ou expirou. Por favor, entre em contato conosco.',
                    'error_type' => 'invalid_token'
                ]);
                return;
            }
            
            // Verificar se a solicitação já foi cancelada
            $sqlStatus = "
                SELECT s.status_id, st.nome as status_nome
                FROM solicitacoes s
                LEFT JOIN status st ON s.status_id = st.id
                WHERE s.id = ?
            ";
            $statusAtual = \App\Core\Database::fetch($sqlStatus, [$solicitacao['id']]);
            
            if ($statusAtual && (stripos($statusAtual['status_nome'], 'Cancelado') !== false || stripos($statusAtual['status_nome'], 'Cancel') !== false)) {
                error_log('Cancelamento - Solicitação já está cancelada: ' . $solicitacao['id']);
                $this->view('token.sucesso', [
                    'title' => 'Solicitação Já Cancelada',
                    'message' => 'Esta solicitação já foi cancelada anteriormente.',
                    'solicitacao' => $solicitacao,
                    'tokenData' => $tokenData,
                    'action' => 'cancelamento'
                ]);
                return;
            }

            // Marcar token como usado
            try {
                $this->tokenModel->markAsUsed($token, 'cancelled');
            } catch (\Exception $e) {
                error_log('Cancelamento - Aviso: Erro ao marcar token como usado (continuando): ' . $e->getMessage());
                // Continuar mesmo se falhar ao marcar token como usado
            }

            // Buscar status "Cancelado" para fechar a solicitação
            $statusModel = new \App\Models\Status();
            $statusCancelado = $statusModel->findByNome('Cancelado');

            // Log para debug
            error_log('Cancelamento - Buscando status "Cancelado"');
            error_log('Cancelamento - Solicitação ID: ' . $solicitacao['id']);

            if (!$statusCancelado) {
                // Se não encontrar "Cancelado", tentar buscar qualquer status que contenha "Cancelado"
                $sql = "SELECT * FROM status WHERE (nome LIKE '%Cancelado%' OR nome LIKE '%Cancel%') AND status = 'ATIVO' LIMIT 1";
                $statusCancelado = \App\Core\Database::fetch($sql);
                
                if (!$statusCancelado) {
                    // Se ainda não encontrar, buscar status inativo também
                    $sql = "SELECT * FROM status WHERE (nome LIKE '%Cancelado%' OR nome LIKE '%Cancel%') LIMIT 1";
                    $statusCancelado = \App\Core\Database::fetch($sql);
                }
            }

            if (!$statusCancelado) {
                error_log('Cancelamento - ERRO: Status "Cancelado" não encontrado no banco de dados');
                $this->view('token.error', [
                    'title' => 'Erro no Sistema',
                    'message' => 'Não foi possível processar o cancelamento. Por favor, entre em contato conosco.',
                    'error_type' => 'status_not_found'
                ]);
                return;
            }

            error_log('Cancelamento - Status encontrado: ' . $statusCancelado['nome'] . ' (ID: ' . $statusCancelado['id'] . ')');

            // Fechar a solicitação: atualizar status para "Cancelado" e limpar dados de agendamento
            $observacoesAtualizadas = ($solicitacao['observacoes'] ?? '');
            if (!empty($observacoesAtualizadas)) {
                $observacoesAtualizadas .= "\n\n";
            }
            $observacoesAtualizadas .= "CANCELADO VIA TOKEN: " . $motivo;

            $updateSql = "
                UPDATE solicitacoes 
                SET status_id = ?,
                    data_agendamento = NULL,
                    horario_agendamento = NULL,
                    horario_confirmado = 0,
                    horario_confirmado_raw = NULL,
                    data_confirmada = NULL,
                    confirmed_schedules = NULL,
                    observacoes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ";
            
            try {
                \App\Core\Database::query($updateSql, [
                    $statusCancelado['id'],
                    $observacoesAtualizadas,
                    $solicitacao['id']
                ]);

                error_log('Cancelamento - Solicitação fechada com sucesso. Status: ' . $statusCancelado['nome'] . ' (ID: ' . $statusCancelado['id'] . ')');

                // Registrar no histórico diretamente
                $sqlHistorico = "
                    INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ";
                \App\Core\Database::query($sqlHistorico, [
                    $solicitacao['id'],
                    $statusCancelado['id'],
                    null, // Usuário sistema (null = cliente)
                    'Solicitação cancelada pelo cliente via link de cancelamento. Motivo: ' . $motivo
                ]);

                // Buscar solicitação atualizada com todos os campos, incluindo numero_solicitacao
                $sqlBuscar = "
                    SELECT s.*, 
                           st.nome as status_nome,
                           st.cor as status_cor
                    FROM solicitacoes s
                    LEFT JOIN status st ON s.status_id = st.id
                    WHERE s.id = ?
                ";
                $solicitacaoAtualizada = \App\Core\Database::fetch($sqlBuscar, [$solicitacao['id']]);
                
                if ($solicitacaoAtualizada) {
                    // Mesclar dados atualizados com os dados originais para manter compatibilidade
                    $solicitacao = array_merge($solicitacao, $solicitacaoAtualizada);
                    error_log('Cancelamento - Solicitação atualizada. Protocolo: ' . ($solicitacao['numero_solicitacao'] ?? 'N/A'));
                } else {
                    error_log('Cancelamento - AVISO: Não foi possível buscar solicitação atualizada (ID: ' . $solicitacao['id'] . '), mas a atualização foi realizada');
                }

                // Exibir página de sucesso
                $this->view('token.sucesso', [
                    'title' => 'Solicitação Cancelada',
                    'message' => 'Sua solicitação foi cancelada com sucesso.',
                    'solicitacao' => $solicitacao,
                    'tokenData' => $tokenData,
                    'action' => 'cancelamento'
                ]);

            } catch (\Exception $e) {
                error_log('Cancelamento - ERRO ao atualizar solicitação: ' . $e->getMessage());
                error_log('Cancelamento - Stack trace: ' . $e->getTraceAsString());
                throw $e; // Re-lançar para ser capturado pelo catch externo
            }

        } catch (\Exception $e) {
            error_log('Erro ao processar cancelamento de horário: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->view('token.error', [
                'title' => 'Erro ao Cancelar',
                'message' => 'Ocorreu um erro ao processar seu cancelamento. Por favor, tente novamente ou entre em contato conosco.',
                'error_type' => 'processing_error'
            ]);
        }
    }

    /**
     * Exibe página de cancelamento de solicitação
     * GET /cancelar-solicitacao?token=xxx
     * Este método usa token permanente que não expira
     */
    public function cancelarSolicitacao(): void
    {
        $token = $this->input('token');

        if (!$token) {
            $this->view('token.error', [
                'title' => 'Token Inválido',
                'message' => 'Token não fornecido. Por favor, use o link completo enviado no WhatsApp.',
                'error_type' => 'missing_token'
            ]);
            return;
        }

        // Validar token de cancelamento permanente
        $solicitacaoId = $this->solicitacaoModel->validarTokenCancelamento($token);

        if (!$solicitacaoId) {
            $this->view('token.error', [
                'title' => 'Token Inválido',
                'message' => 'Este link de cancelamento é inválido. Por favor, entre em contato conosco.',
                'error_type' => 'invalid_token'
            ]);
            return;
        }

        // Buscar dados da solicitação
        $sql = "
            SELECT s.*, 
                   st.nome as status_nome,
                   st.cor as status_cor,
                   c.nome as categoria_nome
            FROM solicitacoes s
            LEFT JOIN status st ON s.status_id = st.id
            LEFT JOIN categorias c ON s.categoria_id = c.id
            WHERE s.id = ?
        ";
        $solicitacao = \App\Core\Database::fetch($sql, [$solicitacaoId]);

        if (!$solicitacao) {
            $this->view('token.error', [
                'title' => 'Solicitação Não Encontrada',
                'message' => 'A solicitação associada a este link não foi encontrada.',
                'error_type' => 'solicitacao_not_found'
            ]);
            return;
        }

        // Verificar se a solicitação já foi cancelada
        $statusAtual = $solicitacao['status_nome'] ?? '';
        if (stripos($statusAtual, 'Cancelado') !== false || stripos($statusAtual, 'Cancel') !== false) {
            $this->view('token.sucesso', [
                'title' => 'Solicitação Já Cancelada',
                'message' => 'Esta solicitação já foi cancelada anteriormente.',
                'solicitacao' => $solicitacao,
                'action' => 'cancelamento'
            ]);
            return;
        }

        // Se já foi processado (POST), processar cancelamento
        if ($this->isPost()) {
            $this->processarCancelamentoSolicitacao($token, $solicitacao);
            return;
        }

        // Exibir formulário de cancelamento
        $this->view('token.cancelamento-solicitacao', [
            'token' => $token,
            'solicitacao' => $solicitacao,
            'title' => 'Cancelar Solicitação'
        ]);
    }

    /**
     * Processa cancelamento de solicitação
     * Fecha a solicitação no sistema (status "Cancelado")
     */
    private function processarCancelamentoSolicitacao(string $token, array $solicitacao): void
    {
        try {
            $motivo = $this->input('motivo', 'Solicitação cancelada pelo cliente via link de cancelamento');

            // Validar token novamente
            $solicitacaoId = $this->solicitacaoModel->validarTokenCancelamento($token);
            
            if (!$solicitacaoId || $solicitacaoId != $solicitacao['id']) {
                error_log('Cancelamento Solicitação - Token inválido: ' . $token);
                $this->view('token.error', [
                    'title' => 'Token Inválido',
                    'message' => 'Este link de cancelamento é inválido. Por favor, entre em contato conosco.',
                    'error_type' => 'invalid_token'
                ]);
                return;
            }

            // Buscar status "Cancelado"
            $sqlStatus = "SELECT * FROM status WHERE nome = 'Cancelado' AND status = 'ATIVO' LIMIT 1";
            $statusCancelado = \App\Core\Database::fetch($sqlStatus);

            if (!$statusCancelado) {
                // Se não encontrar "Cancelado", tentar buscar qualquer status que contenha "Cancelado"
                $sql = "SELECT * FROM status WHERE (nome LIKE '%Cancelado%' OR nome LIKE '%Cancel%') AND status = 'ATIVO' LIMIT 1";
                $statusCancelado = \App\Core\Database::fetch($sql);
                
                if (!$statusCancelado) {
                    // Se ainda não encontrar, buscar status inativo também
                    $sql = "SELECT * FROM status WHERE (nome LIKE '%Cancelado%' OR nome LIKE '%Cancel%') LIMIT 1";
                    $statusCancelado = \App\Core\Database::fetch($sql);
                }
            }

            if (!$statusCancelado) {
                error_log('Cancelamento Solicitação - ERRO: Status "Cancelado" não encontrado no banco de dados');
                $this->view('token.error', [
                    'title' => 'Erro no Sistema',
                    'message' => 'Não foi possível processar o cancelamento. Por favor, entre em contato conosco.',
                    'error_type' => 'status_not_found'
                ]);
                return;
            }

            error_log('Cancelamento Solicitação - Status encontrado: ' . $statusCancelado['nome'] . ' (ID: ' . $statusCancelado['id'] . ')');

            // Buscar categoria "Cancelado"
            $sqlCategoria = "SELECT * FROM categorias WHERE nome = 'Cancelado' AND status = 'ATIVA' LIMIT 1";
            $categoriaCancelado = \App\Core\Database::fetch($sqlCategoria);
            
            // Se não encontrar, buscar qualquer categoria com "Cancelado" no nome
            if (!$categoriaCancelado) {
                $sqlCategoria = "SELECT * FROM categorias WHERE nome LIKE '%Cancelado%' AND status = 'ATIVA' LIMIT 1";
                $categoriaCancelado = \App\Core\Database::fetch($sqlCategoria);
            }
            
            // Buscar condição "Cancelado pelo Locatário"
            $condicaoModel = new \App\Models\Condicao();
            $condicaoCancelado = $condicaoModel->findByNome('Cancelado pelo Locatário');
            
            // Se não encontrar, buscar qualquer condição com "Cancelado" no nome
            if (!$condicaoCancelado) {
                $sqlCondicao = "SELECT * FROM condicoes WHERE nome LIKE '%Cancelado%' AND status = 'ATIVO' LIMIT 1";
                $condicaoCancelado = \App\Core\Database::fetch($sqlCondicao);
            }
            
            // Atualizar observações
            $observacoesAtualizadas = ($solicitacao['observacoes'] ?? '');
            if (!empty($observacoesAtualizadas)) {
                $observacoesAtualizadas .= "\n\n";
            }
            $observacoesAtualizadas .= "CANCELADO VIA LINK PERMANENTE: " . $motivo;

            // Fechar a solicitação: atualizar status, categoria e condição para "Cancelado"
            $updateFields = ['status_id = ?', 'observacoes = ?', 'motivo_cancelamento = ?', 'updated_at = NOW()'];
            $updateParams = [$statusCancelado['id'], $observacoesAtualizadas, $motivo];
            
            if ($categoriaCancelado) {
                $updateFields[] = 'categoria_id = ?';
                $updateParams[] = $categoriaCancelado['id'];
            }
            
            if ($condicaoCancelado) {
                $updateFields[] = 'condicao_id = ?';
                $updateParams[] = $condicaoCancelado['id'];
            }
            
            $updateParams[] = $solicitacao['id'];
            
            $updateSql = "
                UPDATE solicitacoes 
                SET " . implode(', ', $updateFields) . "
                WHERE id = ?
            ";
            
            try {
                \App\Core\Database::query($updateSql, $updateParams);

                error_log('Cancelamento Solicitação - Solicitação cancelada com sucesso. Status: ' . $statusCancelado['nome'] . ' (ID: ' . $statusCancelado['id'] . ')');

                // Registrar no histórico
                $sqlHistorico = "
                    INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ";
                \App\Core\Database::query($sqlHistorico, [
                    $solicitacao['id'],
                    $statusCancelado['id'],
                    null, // Usuário sistema (null = cliente)
                    'Solicitação cancelada pelo cliente via link permanente de cancelamento. Motivo: ' . $motivo
                ]);

                // Buscar solicitação atualizada
                $sqlBuscar = "
                    SELECT s.*, 
                           st.nome as status_nome,
                           st.cor as status_cor
                    FROM solicitacoes s
                    LEFT JOIN status st ON s.status_id = st.id
                    WHERE s.id = ?
                ";
                $solicitacaoAtualizada = \App\Core\Database::fetch($sqlBuscar, [$solicitacao['id']]);
                
                if ($solicitacaoAtualizada) {
                    $solicitacao = array_merge($solicitacao, $solicitacaoAtualizada);
                    error_log('Cancelamento Solicitação - Solicitação atualizada. Protocolo: ' . ($solicitacao['numero_solicitacao'] ?? 'N/A'));
                }

                // Exibir página de sucesso
                $this->view('token.sucesso', [
                    'title' => 'Solicitação Cancelada',
                    'message' => 'Sua solicitação foi cancelada com sucesso.',
                    'solicitacao' => $solicitacao,
                    'action' => 'cancelamento'
                ]);

            } catch (\Exception $e) {
                error_log('Cancelamento Solicitação - ERRO ao atualizar solicitação: ' . $e->getMessage());
                error_log('Cancelamento Solicitação - Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }

        } catch (\Exception $e) {
            error_log('Erro ao processar cancelamento de solicitação: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->view('token.error', [
                'title' => 'Erro ao Cancelar',
                'message' => 'Ocorreu um erro ao processar seu cancelamento. Por favor, tente novamente ou entre em contato conosco.',
                'error_type' => 'processing_error'
            ]);
        }
    }

    /**
     * Exibe página de reagendamento de horário
     * GET /reagendamento-horario?token=xxx
     */
    public function reagendamentoHorario(): void
    {
        $token = $this->input('token');

        if (!$token) {
            $this->view('token.error', [
                'title' => 'Token Inválido',
                'message' => 'Token não fornecido. Por favor, use o link completo enviado no WhatsApp.',
                'error_type' => 'missing_token'
            ]);
            return;
        }

        // Validar token (permitir tokens já usados, mas verificar se não expirou)
        $tokenData = $this->tokenModel->validateToken($token);
        
        if (!$tokenData) {
            // Tentar buscar token mesmo se já foi usado, mas verificar se não expirou
            $sql = "
                SELECT * FROM schedule_confirmation_tokens
                WHERE token = ?
                AND expires_at > NOW()
            ";
            $tokenData = \App\Core\Database::fetch($sql, [$token]);
        }

        if (!$tokenData) {
            $this->view('token.error', [
                'title' => 'Token Inválido ou Expirado',
                'message' => 'Este link de reagendamento é inválido ou expirou. Por favor, entre em contato conosco.',
                'error_type' => 'invalid_token'
            ]);
            return;
        }

        // Buscar dados da solicitação
        $sql = "
            SELECT s.*, 
                   st.nome as status_nome,
                   st.cor as status_cor
            FROM solicitacoes s
            LEFT JOIN status st ON s.status_id = st.id
            WHERE s.id = ?
        ";
        $solicitacao = \App\Core\Database::fetch($sql, [$tokenData['solicitacao_id']]);

        if (!$solicitacao) {
            $this->view('token.error', [
                'title' => 'Solicitação Não Encontrada',
                'message' => 'A solicitação associada a este token não foi encontrada.',
                'error_type' => 'solicitacao_not_found'
            ]);
            return;
        }

        // Se já foi processado (POST), processar reagendamento
        if ($this->isPost()) {
            $this->processarReagendamento($token, $tokenData, $solicitacao);
            return;
        }

        // Exibir formulário de reagendamento
        $this->view('token.reagendamento', [
            'token' => $token,
            'tokenData' => $tokenData,
            'solicitacao' => $solicitacao,
            'title' => 'Reagendar Horário de Atendimento'
        ]);
    }

    /**
     * Processa reagendamento de horário
     */
    private function processarReagendamento(string $token, array $tokenData, array $solicitacao): void
    {
        try {
            $novasDatasInput = $this->input('novas_datas', []);

            // Debug: Log do que está sendo recebido
            error_log("DEBUG processarReagendamento - novas_datas recebido (tipo: " . gettype($novasDatasInput) . "): " . var_export($novasDatasInput, true));

            // Se vier como string JSON, parsear
            if (is_string($novasDatasInput)) {
                $novasDatasInput = json_decode($novasDatasInput, true) ?? [];
                error_log("DEBUG processarReagendamento - novas_datas parseado: " . var_export($novasDatasInput, true));
            }

            // Se não for array, tentar converter
            if (!is_array($novasDatasInput)) {
                $novasDatasInput = [];
            }

            // Sanitizar valores recebidos
            $novasDatasInput = array_values(array_filter(array_map(
                fn($valor) => is_string($valor) || is_scalar($valor) ? trim((string)$valor) : '',
                $novasDatasInput
            ), fn($valor) => $valor !== ''));

            $this->logReagendamento([
                'status' => 'RECEBIDO',
                'fase' => 'dados_brutos',
                'token' => $token,
                'token_id' => $tokenData['id'] ?? null,
                'solicitacao_id' => $solicitacao['id'] ?? null,
                'payload' => $novasDatasInput,
            ]);

            if (empty($novasDatasInput)) {
                error_log("DEBUG processarReagendamento - novas_datas está vazio após processamento");
                $this->view('token.error', [
                    'title' => 'Dados Inválidos',
                    'message' => 'Por favor, selecione pelo menos uma nova data e horário.',
                    'error_type' => 'invalid_data'
                ]);
                return;
            }

            // Converter e padronizar datas do formato "dd/mm/yyyy - HH:MM-HH:MM" ou variantes com segundos
            $datasConvertidas = [];
            $novasDatasSanitizadas = [];

            foreach ($novasDatasInput as $index => $dataString) {
                error_log("DEBUG processarReagendamento - Processando data [{$index}]: " . var_export($dataString, true));

                $dataNormalizada = null;
                $sanitizada = null;

                // Formato esperado principal: "dd/mm/yyyy - HH:MM-HH:MM" com segundos opcionais
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s*-\s*(\d{2}):(\d{2})(?::(\d{2}))?\s*-\s*(\d{2}):(\d{2})(?::(\d{2}))?/', $dataString, $matches)) {
                    $dia = (int) $matches[1];
                    $mes = (int) $matches[2];
                    $ano = (int) $matches[3];
                    $horaInicio = (int) $matches[4];
                    $minInicio = (int) $matches[5];
                    $horaFim = (int) $matches[7];
                    $minFim = (int) $matches[8];

                    $dataNormalizada = sprintf('%04d-%02d-%02d %02d:%02d:00', $ano, $mes, $dia, $horaInicio, $minInicio);
                    $sanitizada = sprintf('%02d/%02d/%04d - %02d:%02d-%02d:%02d', $dia, $mes, $ano, $horaInicio, $minInicio, $horaFim, $minFim);
                    error_log("DEBUG processarReagendamento - ✅ Data convertida [{$index}] via padrão principal: {$dataNormalizada} | Sanitizada: {$sanitizada}");
                }
                // Formato alternativo: "YYYY-MM-DD HH:MM-HH:MM" (com T opcional e segundos opcionais)
                elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})[ T]?(\d{2}):(\d{2})(?::(\d{2}))?\s*-\s*(\d{2}):(\d{2})(?::(\d{2}))?/', $dataString, $matches)) {
                    $ano = (int) $matches[1];
                    $mes = (int) $matches[2];
                    $dia = (int) $matches[3];
                    $horaInicio = (int) $matches[4];
                    $minInicio = (int) $matches[5];
                    $horaFim = (int) $matches[7];
                    $minFim = (int) $matches[8];

                    $dataNormalizada = sprintf('%04d-%02d-%02d %02d:%02d:00', $ano, $mes, $dia, $horaInicio, $minInicio);
                    $sanitizada = sprintf('%02d/%02d/%04d - %02d:%02d-%02d:%02d', $dia, $mes, $ano, $horaInicio, $minInicio, $horaFim, $minFim);
                    error_log("DEBUG processarReagendamento - ✅ Data convertida [{$index}] via padrão ISO: {$dataNormalizada} | Sanitizada: {$sanitizada}");
                } else {
                    error_log("DEBUG processarReagendamento - ⚠️ Regex não correspondeu para: {$dataString}");
                    // Tentar parsear usando DateTime (assume início do período)
                    try {
                        $dt = new \DateTime($dataString);
                        $dataNormalizada = $dt->format('Y-m-d H:i:s');
                        $sanitizada = $dt->format('d/m/Y - H:i');
                        error_log("DEBUG processarReagendamento - ✅ Data parseada como DateTime [{$index}]: " . $dataNormalizada);
                    } catch (\Exception $e) {
                        error_log("DEBUG processarReagendamento - ⚠️ Erro ao converter data [{$index}]: {$dataString} - " . $e->getMessage());
                    }
                }

                if ($dataNormalizada && $sanitizada) {
                    $datasConvertidas[] = $dataNormalizada;
                    $novasDatasSanitizadas[] = $sanitizada;
                }
            }
            
            error_log("DEBUG processarReagendamento - Total de datas convertidas: " . count($datasConvertidas));
            
            $this->logReagendamento([
                'status' => 'SANITIZADO',
                'fase' => 'datas_convertidas',
                'token' => $token,
                'token_id' => $tokenData['id'] ?? null,
                'solicitacao_id' => $solicitacao['id'] ?? null,
                'novas_datas_sanitizadas' => $novasDatasSanitizadas,
                'datas_convertidas' => $datasConvertidas,
            ]);

            if (empty($datasConvertidas)) {
                error_log("DEBUG processarReagendamento - Nenhuma data foi convertida com sucesso");
                $this->view('token.error', [
                    'title' => 'Dados Inválidos',
                    'message' => 'Por favor, selecione pelo menos uma nova data e horário válidos.',
                    'error_type' => 'invalid_data'
                ]);
                return;
            }

            // Validar novas datas
            $datasErrors = $this->solicitacaoModel->validarDatasOpcoes($datasConvertidas);
            if (!empty($datasErrors)) {
                $this->view('token.error', [
                    'title' => 'Datas Inválidas',
                    'message' => 'As datas selecionadas não são válidas: ' . implode(', ', $datasErrors),
                    'error_type' => 'invalid_dates'
                ]);
                return;
            }

            // Validar token antes de processar
            $tokenValidado = $this->tokenModel->validateToken($token);
            
            if (!$tokenValidado) {
                $sql = "
                    SELECT * FROM schedule_confirmation_tokens
                    WHERE token = ?
                    AND expires_at > NOW()
                ";
                $tokenValidado = \App\Core\Database::fetch($sql, [$token]);
            }
            
            if (!$tokenValidado) {
                $this->view('token.error', [
                    'title' => 'Token Inválido',
                    'message' => 'Este link de reagendamento é inválido ou expirou.',
                    'error_type' => 'invalid_token'
                ]);
                return;
            }

            // IMPORTANTE: Limitar a 3 horários máximo (e manter arrays sincronizados)
            if (count($novasDatasSanitizadas) > 3) {
                $novasDatasSanitizadas = array_slice($novasDatasSanitizadas, 0, 3);
                $datasConvertidas = array_slice($datasConvertidas, 0, 3);
            }

            $this->logReagendamento([
                'status' => 'PROCESSANDO',
                'fase' => 'atualizacao',
                'token' => $token,
                'token_id' => $tokenData['id'] ?? null,
                'solicitacao_id' => $solicitacao['id'] ?? null,
                'novas_datas_sanitizadas' => $novasDatasSanitizadas,
                'condicao_definida' => $condicaoId ?? null,
            ]);
            
            // Atualizar solicitação com novas datas (SUBSTITUINDO os horários do admin)
            // Quando locatário reageenda, deve SUBSTITUIR os horários do admin em horarios_opcoes
            $condicaoModel = new \App\Models\Condicao();
            $condicaoRecusada = $condicaoModel->findByNome('Datas recusadas pelo locatário');
            if (!$condicaoRecusada) {
                $condicaoId = $condicaoModel->create([
                    'nome' => 'Datas recusadas pelo locatário',
                    'cor' => '#f97316',
                    'icone' => 'fa-calendar-times',
                    'status' => 'ATIVO'
                ]);
            } else {
                $condicaoId = $condicaoRecusada['id'];
            }

            // ✅ Quando locatário informa outras datas, mudar condição para "Aguardando Prestador"
            $condicaoAguardandoPrestador = $condicaoModel->findByNome('Aguardando Prestador');
            if (!$condicaoAguardandoPrestador) {
                $sqlCondicao = "SELECT * FROM condicoes WHERE (nome LIKE '%Aguardando%Prestador%' OR nome LIKE '%Aguardando Prestador%') AND status = 'ATIVO' LIMIT 1";
                $condicaoAguardandoPrestador = \App\Core\Database::fetch($sqlCondicao);
            }
            
            $updateData = [
                'horarios_opcoes' => json_encode($novasDatasSanitizadas, JSON_UNESCAPED_UNICODE), // SUBSTITUIR horários do admin pelos do locatário
                'datas_opcoes' => null, // Limpar datas_opcoes (não é mais necessário preservar)
                'data_agendamento' => null,
                'horario_agendamento' => null,
                'horario_confirmado' => 0,
                'horario_confirmado_raw' => null,
                'confirmed_schedules' => null,
                'horarios_indisponiveis' => 0, // Resetar flag de horários indisponíveis (locatário substituiu)
                'status_id' => $this->getStatusId('Buscando Prestador'),
                'condicao_id' => $condicaoAguardandoPrestador ? $condicaoAguardandoPrestador['id'] : $condicaoId, // Usar "Aguardando Prestador" se existir, senão usar "Datas recusadas"
                'observacoes' => ($solicitacao['observacoes'] ?? '') . "\n\nREAGENDADO VIA TOKEN: Cliente solicitou reagendamento com novas datas: " . implode(', ', $novasDatasSanitizadas),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->solicitacaoModel->colunaExisteBanco('data_confirmada')) {
                $updateData['data_confirmada'] = null;
            }

            $this->solicitacaoModel->update($solicitacao['id'], $updateData);

            // Registrar no histórico
            $sql = "
                INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ";
            $statusBuscandoPrestador = $this->getStatusId('Buscando Prestador');
            \App\Core\Database::query($sql, [
                $solicitacao['id'],
                $statusBuscandoPrestador,
                null, // Usuário sistema (null = cliente)
                'Solicitação reagendada pelo cliente via link de reagendamento. Novas datas: ' . implode(', ', $novasDatasSanitizadas)
            ]);

            // Buscar solicitação atualizada
            $sqlBuscar = "
                SELECT s.*, 
                       st.nome as status_nome,
                       st.cor as status_cor
                FROM solicitacoes s
                LEFT JOIN status st ON s.status_id = st.id
                WHERE s.id = ?
            ";
            $solicitacaoAtualizada = \App\Core\Database::fetch($sqlBuscar, [$solicitacao['id']]);
            
            if ($solicitacaoAtualizada) {
                $solicitacao = array_merge($solicitacao, $solicitacaoAtualizada);
            }

            try {
                $this->tokenModel->markAsUsed($token, 'rescheduled');
            } catch (\Exception $e) {
                error_log('Reagendamento - Aviso: Erro ao marcar token como usado (continuando): ' . $e->getMessage());
                $this->logReagendamento([
                    'status' => 'AVISO',
                    'fase' => 'marcar_token',
                    'token' => $token,
                    'token_id' => $tokenData['id'] ?? null,
                    'solicitacao_id' => $solicitacao['id'] ?? null,
                    'mensagem' => $e->getMessage(),
                ]);
            }

            $this->logReagendamento([
                'status' => 'SUCESSO',
                'fase' => 'concluido',
                'token' => $token,
                'token_id' => $tokenData['id'] ?? null,
                'solicitacao_id' => $solicitacao['id'] ?? null,
                'novas_datas_salvas' => $novasDatasSanitizadas,
            ]);

            // Exibir página de sucesso
            $this->view('token.sucesso', [
                'title' => 'Horário Reagendado',
                'message' => 'Sua solicitação de reagendamento foi enviada com sucesso! Entraremos em contato para confirmar o novo horário.',
                'solicitacao' => $solicitacao,
                'tokenData' => $tokenData,
                'action' => 'reagendamento'
            ]);

        } catch (\Exception $e) {
            error_log('Erro ao processar reagendamento de horário: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->logReagendamento([
                'status' => 'ERRO',
                'fase' => 'exception',
                'token' => $token,
                'token_id' => $tokenData['id'] ?? null,
                'solicitacao_id' => $solicitacao['id'] ?? null,
                'mensagem' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->view('token.error', [
                'title' => 'Erro ao Reagendar',
                'message' => 'Ocorreu um erro ao processar seu reagendamento. Por favor, tente novamente ou entre em contato conosco.',
                'error_type' => 'processing_error'
            ]);
        }
    }

    /**
     * Registra logs detalhados do fluxo de reagendamento
     */
    private function logReagendamento(array $data): void
    {
        try {
            $logDir = __DIR__ . '/../../storage/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logFile = $logDir . '/reagendamento.log';
            if (!file_exists($logFile)) {
                touch($logFile);
            }

            $data = array_merge([
                'timestamp' => date('Y-m-d H:i:s'),
            ], $data);

            $line = '[' . $data['timestamp'] . '] ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            error_log('Reagendamento Log - Falha ao escrever: ' . $e->getMessage());
        }
    }

    private function isStatusCancelado(mixed $statusId): bool
    {
        if ($statusId === null) {
            return false;
        }

        $statusModel = new \App\Models\Status();
        $status = $statusModel->find((int)$statusId);
        if (!$status) {
            return false;
        }

        $nome = strtoupper($status['nome'] ?? '');
        return str_contains($nome, 'CANCELAD');
    }

    /**
     * Valida se ainda é possível cancelar (até 1 hora antes do horário confirmado)
     * @param array $solicitacao
     * @return array ['permitido' => bool, 'mensagem' => string|null]
     */
    private function validarPrazoCancelamento(array $solicitacao): array
    {
        // Se não há horário confirmado, permitir cancelamento
        if (empty($solicitacao['horario_confirmado']) || $solicitacao['horario_confirmado'] == 0) {
            return ['permitido' => true, 'mensagem' => null];
        }

        $horarioConfirmado = null;
        $dataConfirmada = null;

        // Tentar buscar o primeiro horário confirmado de confirmed_schedules
        if (!empty($solicitacao['confirmed_schedules'])) {
            $confirmedSchedules = json_decode($solicitacao['confirmed_schedules'], true);
            if (is_array($confirmedSchedules) && !empty($confirmedSchedules)) {
                // Ordenar por data/hora e pegar o primeiro (mais próximo)
                usort($confirmedSchedules, function($a, $b) {
                    $dateA = ($a['date'] ?? '') . ' ' . ($a['time'] ?? '');
                    $dateB = ($b['date'] ?? '') . ' ' . ($b['time'] ?? '');
                    return strtotime($dateA) <=> strtotime($dateB);
                });
                
                $primeiroHorario = $confirmedSchedules[0];
                if (!empty($primeiroHorario['date']) && !empty($primeiroHorario['time'])) {
                    $dataConfirmada = $primeiroHorario['date'];
                    // Extrair apenas a hora inicial se for uma faixa (ex: "14:00-17:00")
                    $horarioConfirmado = preg_match('/^(\d{2}:\d{2})/', $primeiroHorario['time'], $matches) 
                        ? $matches[1] 
                        : $primeiroHorario['time'];
                }
            }
        }

        // Se não encontrou em confirmed_schedules, usar data_agendamento e horario_agendamento
        if (!$horarioConfirmado && !empty($solicitacao['data_agendamento']) && !empty($solicitacao['horario_agendamento'])) {
            $dataConfirmada = $solicitacao['data_agendamento'];
            $horarioConfirmado = preg_match('/^(\d{2}:\d{2})/', $solicitacao['horario_agendamento'], $matches) 
                ? $matches[1] 
                : $solicitacao['horario_agendamento'];
        }

        // Se ainda não encontrou horário, permitir cancelamento
        if (!$horarioConfirmado || !$dataConfirmada) {
            return ['permitido' => true, 'mensagem' => null];
        }

        // Montar DateTime do horário confirmado
        try {
            // Garantir formato correto da data (YYYY-MM-DD)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataConfirmada)) {
                // Já está no formato correto
                $dataFormatada = $dataConfirmada;
            } else {
                // Tentar converter de outros formatos
                $timestamp = strtotime($dataConfirmada);
                if ($timestamp === false) {
                    throw new \Exception("Data inválida: {$dataConfirmada}");
                }
                $dataFormatada = date('Y-m-d', $timestamp);
            }
            
            // Garantir formato correto do horário (HH:MM)
            if (!preg_match('/^\d{2}:\d{2}/', $horarioConfirmado)) {
                throw new \Exception("Horário inválido: {$horarioConfirmado}");
            }
            
            $dataHoraConfirmada = new \DateTime($dataFormatada . ' ' . $horarioConfirmado);
            $agora = new \DateTime();
            
            // Se o horário já passou, não permitir cancelamento
            if ($dataHoraConfirmada <= $agora) {
                $horarioFormatado = $dataHoraConfirmada->format('d/m/Y H:i');
                return [
                    'permitido' => false,
                    'mensagem' => "Não é mais possível cancelar este agendamento. O horário confirmado já passou ({$horarioFormatado}). Por favor, entre em contato conosco."
                ];
            }
            
            // Calcular diferença em horas (sempre positiva pois já verificamos que é futuro)
            $diferenca = $agora->diff($dataHoraConfirmada);
            $horasRestantes = ($diferenca->days * 24) + $diferenca->h + ($diferenca->i / 60);
            
            // Se faltam menos de 1 hora, não permitir cancelamento
            if ($horasRestantes < 1) {
                $horarioFormatado = $dataHoraConfirmada->format('d/m/Y H:i');
                return [
                    'permitido' => false,
                    'mensagem' => "Não é mais possível cancelar este agendamento. O prazo para cancelamento expirou (1 hora antes do horário confirmado). O horário confirmado é: {$horarioFormatado}. Por favor, entre em contato conosco."
                ];
            }
            
            return ['permitido' => true, 'mensagem' => null];
        } catch (\Exception $e) {
            error_log('Erro ao validar prazo de cancelamento: ' . $e->getMessage());
            // Em caso de erro, permitir cancelamento (fail-safe)
            return ['permitido' => true, 'mensagem' => null];
        }
    }

    /**
     * Retorna o ID do status pelo nome
     */
    private function getStatusId(string $statusNome): int
    {
        $sql = "SELECT id FROM status WHERE nome = ? LIMIT 1";
        $status = \App\Core\Database::fetch($sql, [$statusNome]);
        return $status['id'] ?? 2; // Default: Buscando Prestador (ID 2)
    }

    /**
     * Exibe status do serviço
     * GET /status-servico?token=xxx
     * Aceita tanto tokens de confirmação quanto tokens públicos permanentes
     */
    public function statusServico(): void
    {
        $token = $this->input('token');

        if (!$token) {
            $this->view('token.error', [
                'title' => 'Token Inválido',
                'message' => 'Token não fornecido. Por favor, use o link completo enviado no WhatsApp.',
                'error_type' => 'missing_token'
            ]);
            return;
        }

        $solicitacaoId = null;
        $tokenData = null;
        $isPublicToken = false;

        // Tentar validar como token de confirmação primeiro
        $tokenData = $this->tokenModel->validateToken($token);
        
        if ($tokenData) {
            // É um token de confirmação válido
            $solicitacaoId = $tokenData['solicitacao_id'];
        } else {
            // Tentar validar como token público permanente
            $solicitacaoId = $this->solicitacaoModel->validarTokenPublico($token);
            
            if ($solicitacaoId) {
                $isPublicToken = true;
            }
        }

        if (!$solicitacaoId) {
            $this->view('token.error', [
                'title' => 'Token Inválido ou Expirado',
                'message' => 'Este link de status é inválido ou expirou. Por favor, entre em contato conosco.',
                'error_type' => 'invalid_token'
            ]);
            return;
        }

        // Buscar dados da solicitação com relacionamentos
        $sql = "
            SELECT 
                s.*,
                st.nome as status_nome,
                st.cor as status_cor,
                c.nome as categoria_nome,
                i.nome as imobiliaria_nome,
                i.instancia as imobiliaria_instancia
            FROM solicitacoes s
            LEFT JOIN status st ON s.status_id = st.id
            LEFT JOIN categorias c ON s.categoria_id = c.id
            LEFT JOIN imobiliarias i ON s.imobiliaria_id = i.id
            WHERE s.id = ?
        ";
        
        $solicitacao = \App\Core\Database::fetch($sql, [$solicitacaoId]);

        if (!$solicitacao) {
            $this->view('token.error', [
                'title' => 'Solicitação Não Encontrada',
                'message' => 'A solicitação associada a este token não foi encontrada.',
                'error_type' => 'solicitacao_not_found'
            ]);
            return;
        }

        // Buscar histórico de status
        $historico = $this->solicitacaoModel->getHistoricoStatus($solicitacao['id']);

        // Exibir página de status
        $this->view('token.status', [
            'token' => $token,
            'tokenData' => $tokenData,
            'solicitacao' => $solicitacao,
            'historico' => $historico,
            'isPublicToken' => $isPublicToken,
            'title' => 'Status do Serviço'
        ]);
    }

    /**
     * Exibe página com opções de ações do serviço
     * GET /acoes-servico?token=xxx
     */
    public function acoesServico(): void
    {
        $token = $this->input('token');

        if (!$token) {
            $this->view('token.error', [
                'title' => 'Token Inválido',
                'message' => 'Token não fornecido. Por favor, use o link completo enviado no WhatsApp.',
                'error_type' => 'missing_token'
            ]);
            return;
        }

        // Validar token
        $tokenData = $this->tokenModel->validateToken($token);

        if (!$tokenData) {
            $this->view('token.error', [
                'title' => 'Token Inválido ou Expirado',
                'message' => 'Este link é inválido ou expirou. Por favor, entre em contato conosco.',
                'error_type' => 'invalid_token'
            ]);
            return;
        }

        // Buscar dados da solicitação
        $solicitacao = $this->solicitacaoModel->getDetalhes($tokenData['solicitacao_id']);

        if (!$solicitacao) {
            $this->view('token.error', [
                'title' => 'Solicitação Não Encontrada',
                'message' => 'A solicitação associada a este link não foi encontrada.',
                'error_type' => 'solicitacao_not_found'
            ]);
            return;
        }

        $this->view('token.acoes_servico', [
            'title' => 'Ações do Serviço',
            'token' => $token,
            'solicitacao' => $solicitacao
        ]);
    }

    /**
     * Processa a ação selecionada pelo usuário
     * POST /acoes-servico
     */
    public function processarAcaoServico(): void
    {
        $token = $this->input('token');
        $acao = $this->input('acao');
        $descricao = $this->input('descricao');

        if (!$token) {
            $this->json(['success' => false, 'message' => 'Token não fornecido'], 400);
            return;
        }

        // Validar token
        $tokenData = $this->tokenModel->validateToken($token);

        if (!$tokenData) {
            $this->json(['success' => false, 'message' => 'Token inválido ou expirado'], 400);
            return;
        }

        $solicitacaoId = $tokenData['solicitacao_id'];
        $solicitacao = $this->solicitacaoModel->getDetalhes($solicitacaoId);

        if (!$solicitacao) {
            $this->json(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
            return;
        }

        try {
            $condicaoModel = new \App\Models\Condicao();
            $statusModel = new \App\Models\Status();

            switch ($acao) {
                case 'servico_realizado':
                    // Mudar status para "Concluído"
                    $statusConcluido = $statusModel->findByNome('Concluído');
                    if (!$statusConcluido) {
                        // Tentar variações do nome
                        $statusConcluido = $statusModel->findByNome('Concluido');
                    }
                    if (!$statusConcluido) {
                        // Buscar qualquer status que contenha "Concluído"
                        $sql = "SELECT * FROM status WHERE (nome LIKE '%Concluído%' OR nome LIKE '%Concluido%') AND status = 'ATIVO' LIMIT 1";
                        $statusConcluido = \App\Core\Database::fetch($sql);
                    }
                    
                    if ($statusConcluido) {
                        $this->solicitacaoModel->update($solicitacaoId, [
                            'status_id' => $statusConcluido['id'],
                            'observacoes' => ($solicitacao['observacoes'] ?? '') . "\n\nServiço realizado com sucesso - confirmado pelo locatário."
                        ]);
                        
                        // Registrar no histórico que foi feito pelo LOCATÁRIO
                        $sql = "INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                                VALUES (?, ?, NULL, ?, NOW())";
                        \App\Core\Database::query($sql, [
                            $solicitacaoId, 
                            $statusConcluido['id'], 
                            'Status: Concluído - Por Locatário'
                        ]);
                        
                        // Atualizar contagem de CPF quando a solicitação é concluída
                        try {
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
                                error_log("Token [servico_realizado] - ✅ Contagem de CPF atualizada para: {$cpfLocatario}");
                            }
                        } catch (\Exception $e) {
                            error_log("Token [servico_realizado] - ⚠️ Erro ao atualizar contagem de CPF: " . $e->getMessage());
                        }
                    }
                    
                    // Marcar token como usado
                    $this->tokenModel->markAsUsed($token);
                    
                    // Redirecionar para avaliação pública (via token)
                    $this->json([
                        'success' => true,
                        'message' => 'Obrigado! Redirecionando para avaliação...',
                        'redirect' => url('avaliacao?token=' . $token)
                    ]);
                    break;

                case 'nao_compareceu':
                    // Mensagem de desculpa, volta para nova solicitação, condição: "Prestador não compareceu"
                    $condicao = $condicaoModel->findByNome('Prestador não compareceu');
                    if (!$condicao) {
                        // Criar condição se não existir
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'Prestador não compareceu',
                            'cor' => '#dc2626',
                            'icone' => 'fa-times-circle',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }

                    $statusNova = $statusModel->findByNome('Nova Solicitação');
                    if ($statusNova) {
                        $this->solicitacaoModel->update($solicitacaoId, [
                            'status_id' => $statusNova['id'],
                            'condicao_id' => $condicaoId,
                            'observacoes' => ($solicitacao['observacoes'] ?? '') . "\n\nPrestador não compareceu no serviço agendado."
                        ]);
                    }

                    // Marcar token como usado
                    $this->tokenModel->markAsUsed($token);

                    $this->json([
                        'success' => true,
                        'message' => 'Sentimos muito pelo inconveniente. Sua solicitação foi reaberta e entraremos em contato em breve.',
                        'redirect' => false
                    ]);
                    break;

                case 'precisa_pecas':
                    // Condição: "Comprar peças", status: pendente, 10 dias para comprar
                    $condicao = $condicaoModel->findByNome('Comprar peças');
                    if (!$condicao) {
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'Comprar peças',
                            'cor' => '#f59e0b',
                            'icone' => 'fa-shopping-cart',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }

                    $statusPendente = $statusModel->findByNome('Pendente');
                    if (!$statusPendente) {
                        $statusPendente = $statusModel->findByNome('Aguardando');
                    }

                    $dataLimite = date('Y-m-d', strtotime('+10 days'));

                    // Preparar dados de atualização com verificação de colunas existentes
                    $updateData = [
                        'status_id' => $statusPendente['id'] ?? $solicitacao['status_id'],
                        'condicao_id' => $condicaoId,
                        'observacoes' => ($solicitacao['observacoes'] ?? '') . "\n\nLocatário precisa comprar peças. Prazo: " . date('d/m/Y', strtotime($dataLimite))
                    ];

                    if ($this->solicitacaoModel->colunaExisteBanco('data_limite_peca')) {
                        $updateData['data_limite_peca'] = $dataLimite;
                    } else {
                        $updateData['observacoes'] .= " (Data limite: " . date('d/m/Y', strtotime($dataLimite)) . ")";
                    }

                    if ($this->solicitacaoModel->colunaExisteBanco('data_ultimo_lembrete')) {
                        $updateData['data_ultimo_lembrete'] = null;
                    }
                    if ($this->solicitacaoModel->colunaExisteBanco('lembretes_enviados')) {
                        $updateData['lembretes_enviados'] = 0;
                    }
                    if ($this->solicitacaoModel->colunaExisteBanco('data_limite_cancelamento')) {
                        $updateData['data_limite_cancelamento'] = $dataLimite;
                    }

                    try {
                        $this->solicitacaoModel->update($solicitacaoId, $updateData);
                    } catch (\Exception $e) {
                        $mensagem = $e->getMessage();
                        $optionalColumns = [
                            'data_limite_peca',
                            'data_ultimo_lembrete',
                            'lembretes_enviados',
                            'data_limite_cancelamento'
                        ];
                        $alterado = false;
                        foreach ($optionalColumns as $coluna) {
                            if (strpos($mensagem, $coluna) !== false && isset($updateData[$coluna])) {
                                unset($updateData[$coluna]);
                                $alterado = true;
                            }
                        }

                        if ($alterado) {
                            error_log('Aviso: removendo colunas opcionais inexistentes ao salvar "Precisa comprar peças".');
                            $this->solicitacaoModel->update($solicitacaoId, $updateData);
                        } else {
                            throw $e;
                        }
                    }

                    // Marcar token como usado
                    $this->tokenModel->markAsUsed($token);

                    $this->json([
                        'success' => true,
                        'message' => 'Você tem até ' . date('d/m/Y', strtotime($dataLimite)) . ' para comprar as peças. Enviaremos lembretes diários.',
                        'redirect' => false
                    ]);
                    break;

                case 'ausente':
                    // Condição: "Locatário se ausentou", status: Cancelado
                    $condicao = $condicaoModel->findByNome('Locatário se ausentou');
                    if (!$condicao) {
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'Locatário se ausentou',
                            'cor' => '#6366f1',
                            'icone' => 'fa-user-times',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }

                    // Buscar status "Cancelado"
                    $statusCancelado = null;
                    $statusCanceladoIdEnv = env('STATUS_CANCELADO_LOCATARIO_ID');
                    if ($statusCanceladoIdEnv) {
                        $statusCancelado = $statusModel->find((int)$statusCanceladoIdEnv);
                    }
                    if (!$statusCancelado) {
                        $statusCanceladoNomeEnv = env('STATUS_CANCELADO_LOCATARIO');
                        if (!empty($statusCanceladoNomeEnv)) {
                            $statusCancelado = $statusModel->findByNome($statusCanceladoNomeEnv);
                        }
                    }
                    if (!$statusCancelado) {
                        $statusCancelado = $statusModel->findByNome('Cancelado');
                    }
                    if (!$statusCancelado) {
                        $statusCancelado = $statusModel->findByNome('Cancelada');
                    }
                    if (!$statusCancelado) {
                        $statusCancelado = $statusModel->findByNome('Cancelado pelo Locatário');
                    }
                    if (!$statusCancelado) {
                        $statusCancelado = $statusModel->findByNome('Cancelada pelo Locatário');
                    }
                    if (!$statusCancelado) {
                        // Tentar buscar qualquer status que contenha "Cancelado"
                        $sql = "SELECT * FROM status WHERE (nome LIKE '%Cancelad%' OR nome LIKE '%Cancel%') AND status = 'ATIVO' LIMIT 1";
                        $statusCancelado = \App\Core\Database::fetch($sql);
                    }
                    
                    if ($statusCancelado) {
                        $this->solicitacaoModel->update($solicitacaoId, [
                            'status_id' => $statusCancelado['id'],
                            'condicao_id' => $condicaoId,
                            'horario_confirmado' => 0,
                            'horario_confirmado_raw' => null,
                            'observacoes' => ($solicitacao['observacoes'] ?? '') . "\n\nLocatário se ausentou no horário agendado."
                        ]);
                    }

                    // Marcar token como usado
                    $this->tokenModel->markAsUsed($token);

                    $this->logReagendamento([
                        'status' => 'AUSENTE',
                        'token' => $token,
                        'token_id' => $tokenData['id'] ?? null,
                        'solicitacao_id' => $solicitacaoId,
                        'status_cancelado_id' => $statusCancelado['id'] ?? null,
                        'status_cancelado_nome' => $statusCancelado['nome'] ?? null,
                        'condicao_id' => $condicaoId,
                    ]);

                    $this->json([
                        'success' => true,
                        'message' => 'Sua solicitação foi cancelada. Por favor, entre no sistema e faça uma nova solicitação quando estiver disponível.',
                        'redirect' => false
                    ]);
                    break;

                case 'outros':
                    // Campo descrição, condição: "outros", status: pendente
                    if (empty($descricao)) {
                        $this->json(['success' => false, 'message' => 'Por favor, descreva o motivo'], 400);
                        return;
                    }

                    $condicao = $condicaoModel->findByNome('outros');
                    if (!$condicao) {
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'outros',
                            'cor' => '#6b7280',
                            'icone' => 'fa-ellipsis-h',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }

                    $statusPendente = $statusModel->findByNome('Pendente');
                    if (!$statusPendente) {
                        $statusPendente = $statusModel->findByNome('Aguardando');
                    }

                    $this->solicitacaoModel->update($solicitacaoId, [
                        'status_id' => $statusPendente['id'] ?? $solicitacao['status_id'],
                        'condicao_id' => $condicaoId,
                        'observacoes' => ($solicitacao['observacoes'] ?? '') . "\n\nOutros: " . $descricao
                    ]);

                    // Marcar token como usado
                    $this->tokenModel->markAsUsed($token);

                    $this->json([
                        'success' => true,
                        'message' => 'Sua mensagem foi registrada. Entraremos em contato em breve.',
                        'redirect' => false
                    ]);
                    break;

                default:
                    $this->json(['success' => false, 'message' => 'Ação inválida'], 400);
                    return;
            }

        } catch (\Exception $e) {
            error_log('Erro ao processar ação do serviço: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao processar ação: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Exibe página para informar compra de peça e selecionar novos horários
     * GET /compra-peca?token=xxx
     */
    public function compraPeca(): void
    {
        $token = $this->input('token');

        if (!$token) {
            $this->view('token.error', [
                'title' => 'Token Inválido',
                'message' => 'Token não fornecido. Por favor, use o link completo enviado no WhatsApp.',
                'error_type' => 'missing_token'
            ]);
            return;
        }

        // Validar token (permitir tokens já usados, mas verificar se não expirou)
        $tokenData = $this->tokenModel->validateToken($token);
        
        if (!$tokenData) {
            // Tentar buscar token mesmo se já foi usado, mas verificar se não expirou
            $sql = "
                SELECT * FROM schedule_confirmation_tokens
                WHERE token = ?
                AND expires_at > NOW()
            ";
            $tokenData = \App\Core\Database::fetch($sql, [$token]);
        }

        if (!$tokenData) {
            $this->view('token.error', [
                'title' => 'Token Inválido ou Expirado',
                'message' => 'Este link é inválido ou expirou. Por favor, entre em contato conosco.',
                'error_type' => 'invalid_token'
            ]);
            return;
        }

        // Buscar dados da solicitação
        $sql = "
            SELECT s.*, 
                   st.nome as status_nome,
                   st.cor as status_cor,
                   i.instancia as imobiliaria_instancia,
                   c.nome as categoria_nome,
                   sc.nome as subcategoria_nome
            FROM solicitacoes s
            LEFT JOIN status st ON s.status_id = st.id
            LEFT JOIN imobiliarias i ON s.imobiliaria_id = i.id
            LEFT JOIN categorias c ON s.categoria_id = c.id
            LEFT JOIN subcategorias sc ON s.subcategoria_id = sc.id
            WHERE s.id = ?
        ";
        $solicitacao = \App\Core\Database::fetch($sql, [$tokenData['solicitacao_id']]);

        if (!$solicitacao) {
            $this->view('token.error', [
                'title' => 'Solicitação Não Encontrada',
                'message' => 'A solicitação associada a este token não foi encontrada.',
                'error_type' => 'solicitacao_not_found'
            ]);
            return;
        }

        // Se já foi processado (POST), processar compra de peça
        if ($this->isPost()) {
            $this->processarCompraPeca($token, $tokenData, $solicitacao);
            return;
        }

        // Exibir formulário para selecionar novos horários
        $this->view('token.compra-peca', [
            'token' => $token,
            'tokenData' => $tokenData,
            'solicitacao' => $solicitacao,
            'title' => 'Informar Compra de Peça e Selecionar Horários'
        ]);
    }

    /**
     * Processa compra de peça e seleção de novos horários
     */
    private function processarCompraPeca(string $token, array $tokenData, array $solicitacao): void
    {
        try {
            // Obter dados do formulário (múltiplas datas)
            $novasDatasInput = $this->input('novas_datas', []);

            // Se vier como string JSON, parsear
            if (is_string($novasDatasInput)) {
                $novasDatasInput = json_decode($novasDatasInput, true) ?? [];
            }

            // Se não for array, tentar converter
            if (!is_array($novasDatasInput)) {
                $novasDatasInput = [];
            }

            // Sanitizar valores recebidos
            $novasDatasInput = array_values(array_filter(array_map(
                fn($valor) => is_string($valor) || is_scalar($valor) ? trim((string)$valor) : '',
                $novasDatasInput
            ), fn($valor) => $valor !== ''));

            if (empty($novasDatasInput)) {
                $this->view('token.compra-peca', [
                    'token' => $token,
                    'tokenData' => $tokenData,
                    'solicitacao' => $solicitacao,
                    'title' => 'Informar Compra de Peça e Selecionar Horários',
                    'error' => 'Por favor, selecione pelo menos uma data e horário.'
                ]);
                return;
            }

            // Converter e padronizar datas (formato igual nova solicitação: "YYYY-MM-DD HH:MM:SS")
            $datasConvertidas = [];
            $novasDatasSanitizadas = [];

            foreach ($novasDatasInput as $index => $dataString) {
                $dataNormalizada = null;
                $sanitizada = null;

                // Formato da nova solicitação: "2025-10-29 08:00:00"
                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})$/', $dataString, $matches)) {
                    $ano = (int) $matches[1];
                    $mes = (int) $matches[2];
                    $dia = (int) $matches[3];
                    $horaInicio = (int) $matches[4];
                    $minInicio = (int) $matches[5];

                    $dataNormalizada = sprintf('%04d-%02d-%02d %02d:%02d:00', $ano, $mes, $dia, $horaInicio, $minInicio);
                    
                    // Determinar faixa de horário baseado na hora inicial (igual nova solicitação)
                    // 08:00 -> 08:00-11:00, 11:00 -> 11:00-14:00, 14:00 -> 14:00-17:00, 17:00 -> 17:00-20:00
                    $horaFim = 0;
                    if ($horaInicio == 8) {
                        $horaFim = 11;
                    } elseif ($horaInicio == 11) {
                        $horaFim = 14;
                    } elseif ($horaInicio == 14) {
                        $horaFim = 17;
                    } elseif ($horaInicio == 17) {
                        $horaFim = 20;
                    } else {
                        // Fallback: adicionar 3 horas
                        $horaFim = min($horaInicio + 3, 20);
                    }
                    
                    $sanitizada = sprintf('%02d/%02d/%04d - %02d:%02d-%02d:%02d', $dia, $mes, $ano, $horaInicio, $minInicio, $horaFim, $minInicio);
                }
                // Formato alternativo: "dd/mm/yyyy - HH:MM-HH:MM"
                elseif (preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s*-\s*(\d{2}):(\d{2})(?::(\d{2}))?\s*-\s*(\d{2}):(\d{2})(?::(\d{2}))?/', $dataString, $matches)) {
                    $dia = (int) $matches[1];
                    $mes = (int) $matches[2];
                    $ano = (int) $matches[3];
                    $horaInicio = (int) $matches[4];
                    $minInicio = (int) $matches[5];
                    $horaFim = (int) $matches[7];
                    $minFim = (int) $matches[8];

                    $dataNormalizada = sprintf('%04d-%02d-%02d %02d:%02d:00', $ano, $mes, $dia, $horaInicio, $minInicio);
                    $sanitizada = sprintf('%02d/%02d/%04d - %02d:%02d-%02d:%02d', $dia, $mes, $ano, $horaInicio, $minInicio, $horaFim, $minFim);
                }
                // Formato alternativo: "YYYY-MM-DD HH:MM-HH:MM"
                elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})[ T]?(\d{2}):(\d{2})(?::(\d{2}))?\s*-\s*(\d{2}):(\d{2})(?::(\d{2}))?/', $dataString, $matches)) {
                    $ano = (int) $matches[1];
                    $mes = (int) $matches[2];
                    $dia = (int) $matches[3];
                    $horaInicio = (int) $matches[4];
                    $minInicio = (int) $matches[5];
                    $horaFim = (int) $matches[7];
                    $minFim = (int) $matches[8];

                    $dataNormalizada = sprintf('%04d-%02d-%02d %02d:%02d:00', $ano, $mes, $dia, $horaInicio, $minInicio);
                    $sanitizada = sprintf('%02d/%02d/%04d - %02d:%02d-%02d:%02d', $dia, $mes, $ano, $horaInicio, $minInicio, $horaFim, $minFim);
                } else {
                    // Tentar parsear usando DateTime
                    try {
                        $dt = new \DateTime($dataString);
                        $dataNormalizada = $dt->format('Y-m-d H:i:s');
                        $horaInicio = (int) $dt->format('H');
                        $minInicio = (int) $dt->format('i');
                        $horaFim = min($horaInicio + 3, 20);
                        $sanitizada = sprintf('%02d/%02d/%04d - %02d:%02d-%02d:%02d', 
                            (int) $dt->format('d'), 
                            (int) $dt->format('m'), 
                            (int) $dt->format('Y'), 
                            $horaInicio, $minInicio, $horaFim, $minInicio);
                    } catch (\Exception $e) {
                        error_log("Erro ao converter data na compra de peça: {$dataString} - " . $e->getMessage());
                    }
                }

                if ($dataNormalizada && $sanitizada) {
                    $datasConvertidas[] = $dataNormalizada;
                    $novasDatasSanitizadas[] = $sanitizada;
                }
            }

            if (empty($datasConvertidas)) {
                error_log('Compra de peça: Nenhuma data convertida. Input recebido: ' . var_export($novasDatasInput, true));
                $this->view('token.compra-peca', [
                    'token' => $token,
                    'tokenData' => $tokenData,
                    'solicitacao' => $solicitacao,
                    'title' => 'Informar Compra de Peça e Selecionar Horários',
                    'error' => 'Por favor, selecione pelo menos uma data e horário válidos. Se o problema persistir, tente selecionar novamente.'
                ]);
                return;
            }

            // Validar novas datas
            try {
                $datasErrors = $this->solicitacaoModel->validarDatasOpcoes($datasConvertidas);
                if (!empty($datasErrors)) {
                    error_log('Compra de peça: Erros de validação: ' . implode(', ', $datasErrors));
                    $this->view('token.compra-peca', [
                        'token' => $token,
                        'tokenData' => $tokenData,
                        'solicitacao' => $solicitacao,
                        'title' => 'Informar Compra de Peça e Selecionar Horários',
                        'error' => 'As datas selecionadas não são válidas: ' . implode(', ', $datasErrors) . '. Por favor, selecione datas futuras em dias úteis.'
                    ]);
                    return;
                }
            } catch (\Exception $e) {
                error_log('Compra de peça: Erro ao validar datas: ' . $e->getMessage());
                $this->view('token.compra-peca', [
                    'token' => $token,
                    'tokenData' => $tokenData,
                    'solicitacao' => $solicitacao,
                    'title' => 'Informar Compra de Peça e Selecionar Horários',
                    'error' => 'Erro ao validar as datas selecionadas. Por favor, tente novamente.'
                ]);
                return;
            }

            // Marcar token como usado
            $this->tokenModel->markAsUsed($token, 'compra_peca');

            // Remover condição "Comprar peças" se existir
            $condicaoModel = new \App\Models\Condicao();
            $condicaoComprarPecas = $condicaoModel->findByNome('Comprar peças');
            
            // Atualizar status para "Buscando Prestador" ou "Nova Solicitação"
            $statusModel = new \App\Models\Status();
            $statusBuscando = $statusModel->findByNome('Buscando Prestador');
            if (!$statusBuscando) {
                $statusBuscando = $statusModel->findByNome('Nova Solicitação');
            }

            // Atualizar solicitação: marcar peça como comprada e definir novos horários
            $updateData = [
                'horarios_opcoes' => json_encode($novasDatasSanitizadas, JSON_UNESCAPED_UNICODE),
                'data_limite_peca' => null,
                'data_ultimo_lembrete' => null,
                'lembretes_enviados' => 0
            ];

            if ($condicaoComprarPecas) {
                $updateData['condicao_id'] = null;
            }

            if ($statusBuscando) {
                $updateData['status_id'] = $statusBuscando['id'];
            }

            // Adicionar observação sobre compra de peça
            $observacoes = $solicitacao['observacoes'] ?? '';
            $horariosTexto = implode(', ', $novasDatasSanitizadas);
            $observacoes .= "\n\n✅ Locatário informou que comprou a peça em " . date('d/m/Y H:i') . " e selecionou novos horários: " . $horariosTexto;
            $updateData['observacoes'] = $observacoes;

            // Atualizar solicitação
            $this->solicitacaoModel->update($solicitacao['id'], $updateData);

            // Adicionar ao histórico
            $historico = "✅ Locatário informou que comprou a peça e selecionou novos horários: " . $horariosTexto;
            $sqlHistorico = "
                INSERT INTO solicitacoes_historico (solicitacao_id, usuario_id, acao, descricao, created_at)
                VALUES (?, NULL, 'compra_peca', ?, NOW())
            ";
            \App\Core\Database::query($sqlHistorico, [$solicitacao['id'], $historico]);

            // Redirecionar para criar nova solicitação (ou dashboard do locatário)
            $instancia = $solicitacao['imobiliaria_instancia'] ?? '';
            $redirectUrl = url($instancia . '/nova-solicitacao?success=' . urlencode('Peça comprada informada com sucesso! Agora você pode criar uma nova solicitação.'));
            
            // Se não tiver instância, redirecionar para página de sucesso
            if (empty($instancia)) {
                $this->view('token.sucesso', [
                    'title' => 'Compra de Peça Informada',
                    'message' => 'Obrigado por informar que comprou a peça! Seus novos horários foram registrados e nossa equipe entrará em contato em breve.',
                    'solicitacao' => $solicitacao,
                    'action' => 'confirmacao'
                ]);
                return;
            }

            $this->redirect($redirectUrl);
            
        } catch (\Exception $e) {
            error_log('Erro ao processar compra de peça: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            error_log('Dados recebidos: ' . var_export($this->input('novas_datas'), true));
            
            $errorMessage = 'Erro ao processar. Por favor, tente novamente ou entre em contato conosco.';
            
            // Mensagens de erro mais específicas
            if (strpos($e->getMessage(), 'validarDatasOpcoes') !== false) {
                $errorMessage = 'As datas selecionadas não são válidas. Por favor, selecione datas futuras em dias úteis.';
            } elseif (strpos($e->getMessage(), 'update') !== false || strpos($e->getMessage(), 'solicitacao') !== false) {
                $errorMessage = 'Erro ao atualizar a solicitação. Por favor, tente novamente.';
            }
            
            $this->view('token.compra-peca', [
                'token' => $token,
                'tokenData' => $tokenData,
                'solicitacao' => $solicitacao,
                'title' => 'Informar Compra de Peça e Selecionar Horários',
                'error' => $errorMessage
            ]);
        }
    }

    /**
     * GET /avaliacao?token=xxx
     * Página de avaliação pública (via token)
     */
    public function avaliacao(): void
    {
        $token = $this->input('token');

        if (!$token) {
            $this->view('token.error', [
                'title' => 'Token Inválido',
                'message' => 'Token não fornecido. Por favor, use o link completo enviado.',
                'error_type' => 'missing_token'
            ]);
            return;
        }

        // Validar token
        $tokenData = $this->tokenModel->validateToken($token);

        if (!$tokenData) {
            $this->view('token.error', [
                'title' => 'Token Inválido ou Expirado',
                'message' => 'Este link é inválido ou expirou. Por favor, entre em contato conosco.',
                'error_type' => 'invalid_token'
            ]);
            return;
        }

        // Buscar dados da solicitação
        $solicitacao = $this->solicitacaoModel->getDetalhes($tokenData['solicitacao_id']);

        if (!$solicitacao) {
            $this->view('token.error', [
                'title' => 'Solicitação Não Encontrada',
                'message' => 'A solicitação associada a este link não foi encontrada.',
                'error_type' => 'solicitacao_not_found'
            ]);
            return;
        }

        // Verificar se já foi avaliada
        if (!empty($solicitacao['avaliacao_satisfacao'])) {
            $this->view('token.sucesso', [
                'title' => 'Avaliação Já Realizada',
                'message' => 'Obrigado! Você já avaliou este serviço.',
                'solicitacao' => $solicitacao,
                'action' => 'avaliacao'
            ]);
            return;
        }

        $this->view('token.avaliacao', [
            'title' => 'Avaliar Serviço',
            'token' => $token,
            'solicitacao' => $solicitacao
        ]);
    }

    /**
     * POST /avaliacao
     * Salvar avaliação via token
     */
    public function salvarAvaliacao(): void
    {
        $token = $this->input('token');
        $npsScore = $this->input('nps_score');
        $comentario = $this->input('comentario', '');

        if (!$token) {
            $this->json(['success' => false, 'message' => 'Token não fornecido'], 400);
            return;
        }

        // Validar token
        $tokenData = $this->tokenModel->validateToken($token);

        if (!$tokenData) {
            $this->json(['success' => false, 'message' => 'Token inválido ou expirado'], 400);
            return;
        }

        if (empty($npsScore) || !is_numeric($npsScore)) {
            $this->json(['success' => false, 'message' => 'Score NPS é obrigatório'], 400);
            return;
        }

        $solicitacaoId = $tokenData['solicitacao_id'];
        $solicitacao = $this->solicitacaoModel->find($solicitacaoId);

        if (!$solicitacao) {
            $this->json(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
            return;
        }

        try {
            // Atualizar avaliação
            $this->solicitacaoModel->update($solicitacaoId, [
                'avaliacao_satisfacao' => (int)$npsScore,
                'comentarios_avaliacao' => $comentario
            ]);

            // Marcar token como usado
            $this->tokenModel->markAsUsed($token);

            $this->json([
                'success' => true,
                'message' => 'Obrigado pela sua avaliação!'
            ]);
        } catch (\Exception $e) {
            error_log('Erro ao salvar avaliação: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao salvar avaliação. Tente novamente.'], 500);
        }
    }
    
    /**
     * POST /atualizar-observacoes
     * Atualizar observações do segurado via token
     */
    public function atualizarObservacoes(): void
    {
        // Tentar pegar token do body JSON primeiro, depois da query string
        $token = $this->input('token') ?? $_GET['token'] ?? null;
        $observacoes = $this->input('observacoes', '');

        if (!$token) {
            $this->json(['success' => false, 'message' => 'Token não fornecido'], 400);
            return;
        }

        $solicitacaoId = null;
        $tokenData = null;

        // Tentar validar como token de confirmação primeiro
        $tokenData = $this->tokenModel->validateToken($token);
        
        if ($tokenData) {
            // É um token de confirmação válido
            $solicitacaoId = $tokenData['solicitacao_id'];
        } else {
            // Tentar validar como token público permanente
            $solicitacaoId = $this->solicitacaoModel->validarTokenPublico($token);
        }

        if (!$solicitacaoId) {
            $this->json(['success' => false, 'message' => 'Token inválido ou expirado'], 400);
            return;
        }

        $solicitacao = $this->solicitacaoModel->find($solicitacaoId);

        if (!$solicitacao) {
            $this->json(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
            return;
        }

        // Atualizar observações
        $this->solicitacaoModel->update($solicitacaoId, [
            'observacoes' => $observacoes,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Registrar no histórico
        $this->solicitacaoModel->addHistoricoStatus($solicitacaoId, $solicitacao['status_id'], null, "Observações atualizadas pelo locatário via link público.");

        $this->json(['success' => true, 'message' => 'Observações salvas com sucesso']);
    }
}

