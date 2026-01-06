<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model para gerenciar tokens de confirmação de horário
 * 
 * Tokens têm validade de 48 horas e são de uso único (single use)
 */
class ScheduleConfirmationToken
{
    /**
     * Cria um novo token de confirmação de horário
     * 
     * @param int $solicitacaoId ID da solicitação
     * @param string $protocol Número de protocolo
     * @param string|null $scheduledDate Data agendada (Y-m-d)
     * @param string|null $scheduledTime Horário agendado
     * @param string|null $actionType Tipo de ação (confirm, cancel, reschedule)
     * @return string Token gerado (hash)
     */
    public function createToken(
        int $solicitacaoId, 
        string $protocol, 
        ?string $scheduledDate = null, 
        ?string $scheduledTime = null,
        ?string $actionType = null
    ): string
    {
        // Gerar token único (hash SHA-256)
        $token = bin2hex(random_bytes(32)); // 64 caracteres hexadecimais
        
        // Calcular data de expiração (48 horas)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+48 hours'));
        
        $sql = "
            INSERT INTO schedule_confirmation_tokens (
                token, 
                solicitacao_id, 
                protocol, 
                scheduled_date, 
                scheduled_time, 
                expires_at,
                action_type,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        Database::query($sql, [
            $token,
            $solicitacaoId,
            $protocol,
            $scheduledDate,
            $scheduledTime,
            $expiresAt,
            $actionType
        ]);
        
        return $token;
    }
    
    /**
     * Valida um token
     * 
     * @param string $token Token a validar
     * @return array|null Dados do token ou null se inválido
     */
    public function validateToken(string $token): ?array
    {
        $sql = "
            SELECT * FROM schedule_confirmation_tokens
            WHERE token = ?
            AND expires_at > NOW()
            AND used_at IS NULL
        ";
        
        return Database::fetch($sql, [$token]);
    }
    
    /**
     * Marca token como usado
     * 
     * @param string $token Token a marcar
     * @param string|null $actionType Tipo de ação realizada
     * @return bool Sucesso
     */
    public function markAsUsed(string $token, ?string $actionType = null): bool
    {
        $sql = "
            UPDATE schedule_confirmation_tokens
            SET used_at = NOW(),
                action_type = COALESCE(?, action_type)
            WHERE token = ?
        ";
        
        Database::query($sql, [$actionType, $token]);
        return true;
    }
    
    /**
     * Busca token por ID da solicitação (último criado e válido)
     * 
     * @param int $solicitacaoId ID da solicitação
     * @return array|null Dados do token
     */
    public function getTokenBySolicitacao(int $solicitacaoId): ?array
    {
        $sql = "
            SELECT * FROM schedule_confirmation_tokens
            WHERE solicitacao_id = ?
            AND expires_at > NOW()
            AND used_at IS NULL
            ORDER BY created_at DESC
            LIMIT 1
        ";
        
        return Database::fetch($sql, [$solicitacaoId]);
    }
    
    /**
     * Invalida todos os tokens de uma solicitação
     * (útil quando o agendamento é alterado)
     * 
     * @param int $solicitacaoId ID da solicitação
     * @return bool Sucesso
     */
    public function invalidateTokensBySolicitacao(int $solicitacaoId): bool
    {
        $sql = "
            UPDATE schedule_confirmation_tokens
            SET used_at = NOW(),
                action_type = 'invalidated'
            WHERE solicitacao_id = ?
            AND used_at IS NULL
        ";
        
        Database::query($sql, [$solicitacaoId]);
        return true;
    }
    
    /**
     * Busca token do pré-serviço para uma solicitação
     * 
     * @param int $solicitacaoId ID da solicitação
     * @return array|null Dados do token do pré-serviço
     */
    public function getTokenPreServico(int $solicitacaoId): ?array
    {
        $sql = "
            SELECT * FROM schedule_confirmation_tokens
            WHERE solicitacao_id = ?
            AND action_type = 'pre_servico'
            ORDER BY created_at DESC
            LIMIT 1
        ";
        
        return Database::fetch($sql, [$solicitacaoId]);
    }
}

