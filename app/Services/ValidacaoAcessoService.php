<?php

namespace App\Services;

use App\Core\Database;

/**
 * Serviço para validação de acesso do locatário
 * 
 * REGRA 1: Verificar se CPF está no Bolsão (listagem locatarios_contratos) -> libera
 * REGRA 2: Verificar CtrDtaIni (data início contrato) - se > 45 dias, bloqueia
 */
class ValidacaoAcessoService
{
    /**
     * Validar regras de acesso do locatário
     * 
     * @param string $cpf CPF do locatário
     * @param int $imobiliariaId ID da imobiliária
     * @param array $imoveis Array de imóveis retornados pela API KSI
     * @return array ['permitido' => bool, 'bolsao' => bool, 'regra_2_passa' => bool, 'motivo_bloqueio' => string|null]
     */
    public static function validarRegrasAcesso(string $cpf, int $imobiliariaId, array $imoveis): array
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $resultado = [
            'permitido' => false,
            'bolsao' => false,
            'regra_2_passa' => false,
            'motivo_bloqueio' => null
        ];
        
        // REGRA 1: Verificar se CPF está no Bolsão (listagem locatarios_contratos)
        if (!empty($cpfLimpo)) {
            $sql = "SELECT * FROM locatarios_contratos 
                    WHERE imobiliaria_id = ? 
                    AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?";
            $cpfEncontrado = Database::fetch($sql, [$imobiliariaId, $cpfLimpo]);
            
            if ($cpfEncontrado) {
                // REGRA 1: CPF encontrado no Bolsão -> LIBERA ACESSO
                $resultado['permitido'] = true;
                $resultado['bolsao'] = true;
                error_log("DEBUG [ValidacaoAcessoService] - CPF {$cpfLimpo} encontrado no Bolsão - ACESSO LIBERADO");
                return $resultado;
            }
        }
        
        // REGRA 2: Verificar data de início do contrato (CtrDtaIni)
        // Se não está no Bolsão, verificar se o contrato está dentro de 45 dias
        $dataAtual = new \DateTime();
        $contratoValido = false;
        
        foreach ($imoveis as $imovel) {
            // Os contratos podem vir em diferentes formatos dependendo de onde vêm os dados
            $contratos = [];
            
            // Formato 1: Já processado pelo KsiApiService (array com chave 'contratos')
            if (isset($imovel['contratos']) && is_array($imovel['contratos'])) {
                $contratos = $imovel['contratos'];
            }
            // Formato 2: Dados raw da API (chave 'Ctr')
            elseif (isset($imovel['Ctr']) && is_array($imovel['Ctr'])) {
                $contratos = $imovel['Ctr'];
            }
            
            if (!empty($contratos)) {
                foreach ($contratos as $contrato) {
                    // Buscar data de início do contrato (CtrDtaIni)
                    $dataInicioContrato = null;
                    
                    // Tentar diferentes nomes de campo possíveis
                    if (isset($contrato['CtrDtaIni'])) {
                        $dataInicioContrato = $contrato['CtrDtaIni'];
                    } elseif (isset($contrato['ctr_dta_ini'])) {
                        $dataInicioContrato = $contrato['ctr_dta_ini'];
                    } elseif (isset($contrato['data_inicio'])) {
                        $dataInicioContrato = $contrato['data_inicio'];
                    }
                    
                    if (!empty($dataInicioContrato)) {
                        // Converter data para formato DateTime
                        // A data pode vir em formato "08/09/2025" (DD/MM/YYYY)
                        try {
                            if (strpos($dataInicioContrato, '/') !== false) {
                                // Formato DD/MM/YYYY
                                $dataObj = \DateTime::createFromFormat('d/m/Y', $dataInicioContrato);
                            } else {
                                // Tentar outros formatos
                                $dataObj = new \DateTime($dataInicioContrato);
                            }
                            
                            if ($dataObj) {
                                // Calcular diferença em dias
                                $diferenca = $dataAtual->diff($dataObj);
                                $diasDesdeInicio = (int)$diferenca->days;
                                
                                // Se a data de início está no futuro ou foi há menos de 45 dias, contrato válido
                                if ($dataObj >= $dataAtual || $diasDesdeInicio <= 45) {
                                    $contratoValido = true;
                                    error_log("DEBUG [ValidacaoAcessoService] - Contrato válido. Data início: {$dataInicioContrato}, Dias desde início: {$diasDesdeInicio}");
                                    break 2; // Sair dos dois loops
                                } else {
                                    error_log("DEBUG [ValidacaoAcessoService] - Contrato expirado. Data início: {$dataInicioContrato}, Dias desde início: {$diasDesdeInicio} (> 45 dias)");
                                }
                            }
                        } catch (\Exception $e) {
                            error_log("DEBUG [ValidacaoAcessoService] - Erro ao processar data do contrato: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        if ($contratoValido) {
            // REGRA 2: Contrato dentro de 45 dias -> LIBERA ACESSO
            $resultado['permitido'] = true;
            $resultado['bolsao'] = false;
            $resultado['regra_2_passa'] = true; // Passou na Regra 2
            error_log("DEBUG [ValidacaoAcessoService] - REGRA 2: Contrato dentro de 45 dias - ACESSO LIBERADO");
        } else {
            // REGRA 2: Contrato há mais de 45 dias -> BLOQUEIA ACESSO
            $resultado['permitido'] = false;
            $resultado['regra_2_passa'] = false;
            $resultado['motivo_bloqueio'] = 'O contrato está há mais de 45 dias da data de início. Acesso bloqueado.';
            error_log("DEBUG [ValidacaoAcessoService] - REGRA 2: ACESSO BLOQUEADO - Contrato há mais de 45 dias");
        }
        
        return $resultado;
    }
}

