<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Imobiliaria;
use App\Models\HistoricoUpload;

class UploadController extends Controller
{
    private Imobiliaria $imobiliariaModel;
    private HistoricoUpload $historicoUploadModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->requireAdmin();
        $this->imobiliariaModel = new Imobiliaria();
        $this->historicoUploadModel = new HistoricoUpload();
    }

    public function index(): void
    {
        // Garantir que as tabelas necessárias existem
        $this->garantirTabelaLocatariosContratos();
        $this->garantirTabelaHistoricoUploads();
        
        $imobiliarias = $this->imobiliariaModel->getAtivas();
        
        $this->view('admin/upload/index', [
            'title' => 'Upload de CSV',
            'currentPage' => 'upload',
            'pageTitle' => 'Upload de CSV',
            'imobiliarias' => $imobiliarias
        ]);
    }

    /**
     * Processar upload de CSV (múltiplos arquivos)
     */
    public function processar(): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }

        // Validar imobiliária selecionada
        $imobiliariaId = (int) ($this->input('imobiliaria_id') ?? 0);
        if (empty($imobiliariaId)) {
            $this->json(['success' => false, 'error' => 'Imobiliária não selecionada'], 400);
            return;
        }

        // Verificar se a imobiliária existe
        $imobiliaria = $this->imobiliariaModel->find($imobiliariaId);
        if (!$imobiliaria) {
            $this->json(['success' => false, 'error' => 'Imobiliária não encontrada'], 404);
            return;
        }

        // Verificar se arquivos foram enviados
        if (empty($_FILES['csv_file']['name'])) {
            $this->json(['success' => false, 'error' => 'Nenhum arquivo foi enviado'], 400);
            return;
        }

        // Verificar se as tabelas necessárias existem
        $this->garantirTabelaLocatariosContratos();
        $this->garantirTabelaHistoricoUploads();

        // Obter usuário logado
        $user = $this->getUser();
        if (!$user) {
            $this->json(['success' => false, 'error' => 'Usuário não autenticado'], 401);
            return;
        }
        $usuarioId = $user['id'];

        // Processar múltiplos arquivos
        $files = $_FILES['csv_file'];
        $totalArquivos = is_array($files['name']) ? count($files['name']) : 1;
        
        // Normalizar para array se for apenas um arquivo
        if (!is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'tmp_name' => [$files['tmp_name']],
                'size' => [$files['size']],
                'error' => [$files['error']]
            ];
        }

        $totalSucessos = 0;
        $totalErros = 0;
        $detalhesErrosGeral = [];
        $resultadosArquivos = [];

        // Validar e processar cada arquivo
        for ($i = 0; $i < $totalArquivos; $i++) {
            $fileName = $files['name'][$i];
            $fileTmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileError = $files['error'][$i];

            // Validar erro de upload
            if ($fileError !== UPLOAD_ERR_OK) {
                $totalErros++;
                $detalhesErrosGeral[] = "Arquivo '{$fileName}': Erro no upload (código {$fileError})";
                $resultadosArquivos[] = [
                    'nome' => $fileName,
                    'sucessos' => 0,
                    'erros' => 1
                ];
                continue;
            }

            // Validar tamanho (máximo 10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($fileSize > $maxSize) {
                $totalErros++;
                $detalhesErrosGeral[] = "Arquivo '{$fileName}': Arquivo muito grande. Tamanho máximo: 10MB";
                $resultadosArquivos[] = [
                    'nome' => $fileName,
                    'sucessos' => 0,
                    'erros' => 1
                ];
                continue;
            }

            // Validar extensão
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if ($extension !== 'csv') {
                $totalErros++;
                $detalhesErrosGeral[] = "Arquivo '{$fileName}': Formato não permitido. Use .csv";
                $resultadosArquivos[] = [
                    'nome' => $fileName,
                    'sucessos' => 0,
                    'erros' => 1
                ];
                continue;
            }

            // Processar arquivo
            try {
                // Fazer uma cópia do arquivo ORIGINAL antes de processar (para salvar depois)
                // Isso é necessário porque processarArquivoCSV pode criar arquivos temporários
                $copiaParaSalvar = null;
                if (is_uploaded_file($fileTmpName)) {
                    // Se for um arquivo enviado via upload, fazer cópia
                    $copiaParaSalvar = tempnam(sys_get_temp_dir(), 'csv_backup_');
                    if (!copy($fileTmpName, $copiaParaSalvar)) {
                        throw new \Exception("Erro ao criar cópia do arquivo para salvamento");
                    }
                } else {
                    // Se já for um arquivo temporário, fazer cópia também
                    $copiaParaSalvar = tempnam(sys_get_temp_dir(), 'csv_backup_');
                    if (!copy($fileTmpName, $copiaParaSalvar)) {
                        throw new \Exception("Erro ao criar cópia do arquivo para salvamento");
                    }
                }
                
                // Processar primeiro (usando o arquivo temporário original)
                $resultado = $this->processarArquivoCSV($fileTmpName, $fileName, $imobiliariaId);
                
                // Salvar arquivo CSV após processar (usar a cópia do arquivo original)
                $caminhoArquivo = $this->salvarArquivoCSV($copiaParaSalvar, $fileName, $imobiliariaId);
                $totalSucessos += $resultado['sucessos'];
                $totalErros += $resultado['erros'];
                $detalhesErrosGeral = array_merge($detalhesErrosGeral, $resultado['detalhes_erros']);
                
                // Salvar histórico do upload
                $this->historicoUploadModel->create([
                    'imobiliaria_id' => $imobiliariaId,
                    'nome_arquivo' => $fileName,
                    'caminho_arquivo' => $caminhoArquivo,
                    'tamanho_arquivo' => $fileSize,
                    'usuario_id' => $usuarioId,
                    'total_registros' => $resultado['sucessos'] + $resultado['erros'],
                    'registros_sucesso' => $resultado['sucessos'],
                    'registros_erro' => $resultado['erros'],
                    'detalhes_erros' => $resultado['detalhes_erros']
                ]);
                
                $resultadosArquivos[] = [
                    'nome' => $fileName,
                    'sucessos' => $resultado['sucessos'],
                    'erros' => $resultado['erros']
                ];
            } catch (\Exception $e) {
                $totalErros++;
                $detalhesErrosGeral[] = "Arquivo '{$fileName}': " . $e->getMessage();
                
                // Tentar salvar arquivo mesmo em caso de erro
                $caminhoArquivo = null;
                try {
                    $caminhoArquivo = $this->salvarArquivoCSV($fileTmpName, $fileName, $imobiliariaId);
                } catch (\Exception $exFile) {
                    error_log("Erro ao salvar arquivo CSV: " . $exFile->getMessage());
                }
                
                // Salvar histórico mesmo em caso de erro
                try {
                    $this->historicoUploadModel->create([
                        'imobiliaria_id' => $imobiliariaId,
                        'nome_arquivo' => $fileName,
                        'caminho_arquivo' => $caminhoArquivo,
                        'tamanho_arquivo' => $fileSize,
                        'usuario_id' => $usuarioId,
                        'total_registros' => 0,
                        'registros_sucesso' => 0,
                        'registros_erro' => 1,
                        'detalhes_erros' => [$e->getMessage()]
                    ]);
                } catch (\Exception $ex) {
                    error_log("Erro ao salvar histórico: " . $ex->getMessage());
                }
                
                $resultadosArquivos[] = [
                    'nome' => $fileName,
                    'sucessos' => 0,
                    'erros' => 1
                ];
                error_log("Erro ao processar arquivo {$fileName}: " . $e->getMessage());
            }
        }

        $mensagem = "Processamento concluído: {$totalSucessos} registro(s) processado(s) com sucesso em {$totalArquivos} arquivo(s)";
        if ($totalErros > 0) {
            $mensagem .= ", {$totalErros} erro(s) encontrado(s)";
        }

        // Limitar e sanitizar detalhes de erros
        $detalhesErrosLimitados = array_slice($detalhesErrosGeral, 0, 200);
        $detalhesErrosSanitizados = array_map(function($erro) {
            return mb_convert_encoding($erro, 'UTF-8', 'UTF-8');
        }, $detalhesErrosLimitados);

        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_clean();
        }
        
        $this->json([
            'success' => true,
            'message' => $mensagem,
            'sucessos' => $totalSucessos,
            'erros' => $totalErros,
            'detalhes_erros' => $detalhesErrosSanitizados,
            'arquivos' => $resultadosArquivos,
            'imobiliaria_id' => $imobiliariaId
        ]);
    }

    /**
     * Processar um único arquivo CSV
     */
    private function processarArquivoCSV(string $fileTmpName, string $fileName, int $imobiliariaId): array
    {

        // Detectar encoding do arquivo
        $conteudo = file_get_contents($fileTmpName);
        $encoding = mb_detect_encoding($conteudo, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        
        // Se não for UTF-8, converter
        if ($encoding && $encoding !== 'UTF-8') {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', $encoding);
            // Salvar temporariamente o conteúdo convertido
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_utf8_');
            file_put_contents($tempFile, $conteudo);
            $fileTmpName = $tempFile;
        }

        // Processar CSV
        $handle = fopen($fileTmpName, 'r');
        if ($handle === false) {
            throw new \Exception('Não foi possível abrir o arquivo CSV');
        }

        // Detectar separador (vírgula ou ponto e vírgula)
        $primeiraLinha = fgets($handle);
        rewind($handle);
        
        $separador = ',';
        if (strpos($primeiraLinha, ';') !== false && substr_count($primeiraLinha, ';') >= substr_count($primeiraLinha, ',')) {
            $separador = ';';
        }

        // Ler cabeçalho
        $header = fgetcsv($handle, 0, $separador);
        if (!$header) {
            fclose($handle);
            throw new \Exception('Não foi possível ler o cabeçalho do arquivo');
        }

        // Normalizar nomes das colunas (remover espaços, converter para minúsculas) e garantir UTF-8
        $headerNormalizado = array_map(function($col) {
            $col = trim($col);
            // Garantir que está em UTF-8
            if (!mb_check_encoding($col, 'UTF-8')) {
                $col = mb_convert_encoding($col, 'UTF-8', 'auto');
            }
            return mb_strtolower($col, 'UTF-8');
        }, $header);

            // Mapear índices das colunas
            $indices = [
                'cpf' => array_search('inquilino_doc', $headerNormalizado),
                'nome' => array_search('inquilino_nome', $headerNormalizado),
                'tipo_imovel' => array_search('imofinalidade', $headerNormalizado),
                'cidade' => array_search('cidade', $headerNormalizado),
                'estado' => array_search('estado', $headerNormalizado),
                'bairro' => array_search('bairro', $headerNormalizado),
                'cep' => array_search('cep', $headerNormalizado),
                'endereco' => array_search('endereco', $headerNormalizado),
                'numero' => array_search('numero', $headerNormalizado),
                'complemento' => array_search('complemento', $headerNormalizado),
                'unidade' => array_search('unidade', $headerNormalizado),
                'contrato' => array_search('contrato', $headerNormalizado)
            ];

            // Verificar se todas as colunas obrigatórias foram encontradas
            $colunasFaltando = [];
            foreach (['cpf', 'contrato'] as $col) {
                if ($indices[$col] === false) {
                    $colunasFaltando[] = $col;
                }
            }

            if (!empty($colunasFaltando)) {
                fclose($handle);
                throw new \Exception('Colunas obrigatórias não encontradas: ' . implode(', ', $colunasFaltando));
            }

            $sucessos = 0;
            $erros = 0;
            $detalhesErros = [];
            $linha = 1;

            // Processar linhas
            while (($row = fgetcsv($handle, 0, $separador)) !== false) {
                $linha++;
                
                try {
                    // Função auxiliar para garantir UTF-8
                    $converterParaUTF8 = function($valor) {
                        if (empty($valor)) return $valor;
                        $valor = trim($valor);
                        if (!mb_check_encoding($valor, 'UTF-8')) {
                            $valor = mb_convert_encoding($valor, 'UTF-8', 'auto');
                        }
                        return $valor;
                    };
                    
                    // Extrair dados e garantir UTF-8
                    $cpf = isset($row[$indices['cpf']]) ? $converterParaUTF8($row[$indices['cpf']]) : '';
                    $contrato = isset($row[$indices['contrato']]) ? $converterParaUTF8($row[$indices['contrato']]) : '';

                    // Validar dados obrigatórios
                    if (empty($cpf)) {
                        $erros++;
                        $detalhesErros[] = "Linha {$linha}: CPF/CNPJ não informado";
                        continue;
                    }

                    if (empty($contrato)) {
                        $erros++;
                        $detalhesErros[] = "Linha {$linha}: Número do contrato não informado";
                        continue;
                    }

                    // Limpar CPF/CNPJ
                    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
                    
                    // Validar CPF (11 dígitos) ou CNPJ (14 dígitos)
                    if (strlen($cpfLimpo) !== 11 && strlen($cpfLimpo) !== 14) {
                        $erros++;
                        $detalhesErros[] = "Linha {$linha}: CPF/CNPJ inválido (deve ter 11 ou 14 dígitos)";
                        continue;
                    }

                    // Extrair dados opcionais e garantir UTF-8
                    $nome = isset($row[$indices['nome']]) ? $converterParaUTF8($row[$indices['nome']]) : null;
                    $tipoImovel = isset($row[$indices['tipo_imovel']]) ? $converterParaUTF8($row[$indices['tipo_imovel']]) : null;
                    $cidade = isset($row[$indices['cidade']]) ? $converterParaUTF8($row[$indices['cidade']]) : null;
                    $estado = isset($row[$indices['estado']]) ? $converterParaUTF8($row[$indices['estado']]) : null;
                    $bairro = isset($row[$indices['bairro']]) ? $converterParaUTF8($row[$indices['bairro']]) : null;
                    $cep = isset($row[$indices['cep']]) ? $converterParaUTF8($row[$indices['cep']]) : null;
                    $endereco = isset($row[$indices['endereco']]) ? $converterParaUTF8($row[$indices['endereco']]) : null;
                    $numero = isset($row[$indices['numero']]) ? $converterParaUTF8($row[$indices['numero']]) : null;
                    $complemento = isset($row[$indices['complemento']]) ? $converterParaUTF8($row[$indices['complemento']]) : null;
                    $unidade = isset($row[$indices['unidade']]) ? $converterParaUTF8($row[$indices['unidade']]) : null;
                    
                    // Verificar se já existe
                    $sql = "SELECT * FROM locatarios_contratos 
                            WHERE imobiliaria_id = ? AND cpf = ? AND numero_contrato = ?";
                    $existente = \App\Core\Database::fetch($sql, [$imobiliariaId, $cpfLimpo, $contrato]);

                    if ($existente) {
                        // Atualizar registro existente com todos os dados
                        $updateSql = "UPDATE locatarios_contratos 
                                     SET inquilino_nome = ?,
                                         tipo_imovel = ?,
                                         cidade = ?,
                                         estado = ?,
                                         bairro = ?,
                                         cep = ?,
                                         endereco = ?,
                                         numero = ?,
                                         complemento = ?,
                                         unidade = ?,
                                         updated_at = NOW() 
                                     WHERE id = ?";
                        \App\Core\Database::query($updateSql, [
                            $nome, $tipoImovel, $cidade, $estado, $bairro, $cep,
                            $endereco, $numero, $complemento, $unidade,
                            $existente['id']
                        ]);
                        $sucessos++;
                    } else {
                        // Criar novo registro com todos os dados
                        $insertSql = "INSERT INTO locatarios_contratos 
                                     (imobiliaria_id, cpf, inquilino_nome, numero_contrato, tipo_imovel,
                                      cidade, estado, bairro, cep, endereco, numero, complemento, unidade,
                                      created_at, updated_at) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                        \App\Core\Database::query($insertSql, [
                            $imobiliariaId, $cpfLimpo, $nome, $contrato, $tipoImovel,
                            $cidade, $estado, $bairro, $cep, $endereco, $numero, $complemento, $unidade
                        ]);
                        $sucessos++;
                    }
                } catch (\Exception $e) {
                    $erros++;
                    $mensagemErro = mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8');
                    $detalhesErros[] = "Linha {$linha}: Erro ao processar - " . $mensagemErro;
                    error_log("Erro ao processar linha {$linha} do CSV: " . $e->getMessage());
                }
            }

        fclose($handle);

        // Adicionar prefixo do nome do arquivo aos erros
        $detalhesErrosComArquivo = array_map(function($erro) use ($fileName) {
            return "[{$fileName}] {$erro}";
        }, $detalhesErros);

        return [
            'sucessos' => $sucessos,
            'erros' => $erros,
            'detalhes_erros' => $detalhesErrosComArquivo
        ];
    }

    /**
     * Buscar histórico de uploads por imobiliária
     */
    public function getHistorico(int $imobiliariaId): void
    {
        if (empty($imobiliariaId)) {
            $this->json(['success' => false, 'error' => 'Imobiliária não informada'], 400);
            return;
        }

        $historico = $this->historicoUploadModel->getByImobiliaria($imobiliariaId, 50);
        
        $this->json([
            'success' => true,
            'historico' => $historico
        ]);
    }

    /**
     * Buscar todas as imobiliárias com status de lançamento
     */
    public function getFiltros(): void
    {
        $buscaNome = trim($this->input('busca_nome') ?? '');
        $mes = $this->input('mes') ?? null;
        $ano = $this->input('ano') ?? null;
        $statusLancado = $this->input('status_lancado') ?? null; // 'sim', 'nao', ou null para todos

        // Query para buscar todas as imobiliárias com informações do último upload
        // Quando há filtros de mês/ano, aplicar na junção; senão, mostrar último lançamento geral
        $dataCondicoes = [];
        $dataParams = [];
        $nomeCondicoes = [];
        $whereParams = [];
        
        $sql = "SELECT 
                    i.id,
                    i.nome,
                    i.nome_fantasia,
                    i.status,
                    MAX(hu.created_at) as ultimo_lancamento,
                    COUNT(hu.id) as total_uploads
                FROM imobiliarias i
                LEFT JOIN historico_uploads hu ON i.id = hu.imobiliaria_id";
        
        // Aplicar filtros de mês/ano na junção se especificados
        if ($ano) {
            if ($mes) {
                // Filtro por mês e ano específicos
                $dataCondicoes[] = "YEAR(hu.created_at) = ? AND MONTH(hu.created_at) = ?";
                $dataParams[] = $ano;
                $dataParams[] = $mes;
            } else {
                // Filtro apenas por ano (todos os meses do ano)
                $dataCondicoes[] = "YEAR(hu.created_at) = ?";
                $dataParams[] = $ano;
        }
        } elseif ($mes) {
            // Se só tiver mês sem ano, usar o ano atual
            $anoAtual = (int)date('Y');
            $dataCondicoes[] = "YEAR(hu.created_at) = ? AND MONTH(hu.created_at) = ?";
            $dataParams[] = $anoAtual;
            $dataParams[] = $mes;
        }
        
        // Aplicar condições de data na junção
        if (!empty($dataCondicoes)) {
            $sql .= " AND (" . implode(" AND ", $dataCondicoes) . ")";
        }
        
        // Aplicar filtro de busca por nome no WHERE
        if (!empty($buscaNome)) {
            $nomeCondicoes[] = "(i.nome LIKE ? OR i.nome_fantasia LIKE ?)";
            $nomeParam = '%' . $buscaNome . '%';
            $whereParams[] = $nomeParam;
            $whereParams[] = $nomeParam;
        }
        
        // Aplicar condições de nome no WHERE
        if (!empty($nomeCondicoes)) {
            $sql .= " WHERE " . implode(" OR ", $nomeCondicoes);
        }

        $sql .= " GROUP BY i.id, i.nome, i.nome_fantasia, i.status";
        
        // Combinar parâmetros: primeiro os do JOIN (data), depois os do WHERE (nome)
        $params = array_merge($dataParams, $whereParams);

        // Executar query
        $resultados = \App\Core\Database::fetchAll($sql, $params);
        
        $imobiliarias = [];
        foreach ($resultados as $row) {
            $temLancamento = !empty($row['ultimo_lancamento']);
            
            // Aplicar filtro de status de lançamento
            if ($statusLancado === 'sim' && !$temLancamento) {
                continue;
            }
            if ($statusLancado === 'nao' && $temLancamento) {
                continue;
            }

            // Verificar se a data do último lançamento está dentro do filtro de mês/ano (se especificado)
            if ($temLancamento && ($mes || $ano)) {
                $ultimoLancamentoTimestamp = strtotime($row['ultimo_lancamento']);
                $ultimoLancamentoAno = (int)date('Y', $ultimoLancamentoTimestamp);
                $ultimoLancamentoMes = (int)date('m', $ultimoLancamentoTimestamp);
                
                if ($ano && $ultimoLancamentoAno != (int)$ano) {
                    continue;
                }
                
                if ($mes && $ultimoLancamentoMes != (int)$mes) {
                    continue;
                }
            }

            $imobiliarias[] = [
                'id' => $row['id'],
                'nome' => $row['nome'],
                'nome_fantasia' => $row['nome_fantasia'],
                'status' => $row['status'],
                'tem_lancamento' => $temLancamento,
                'ultimo_lancamento' => $row['ultimo_lancamento'] ? date('d/m/Y H:i', strtotime($row['ultimo_lancamento'])) : null,
                'ultimo_lancamento_raw' => $row['ultimo_lancamento'],
                'total_uploads' => (int)$row['total_uploads']
            ];
        }

        // Ordenar por último lançamento (mais recente primeiro) e depois por nome
        usort($imobiliarias, function($a, $b) {
            if ($a['tem_lancamento'] && $b['tem_lancamento']) {
                return strtotime($b['ultimo_lancamento_raw']) - strtotime($a['ultimo_lancamento_raw']);
            }
            if ($a['tem_lancamento']) return -1;
            if ($b['tem_lancamento']) return 1;
            return strcmp($a['nome_fantasia'] ?? $a['nome'], $b['nome_fantasia'] ?? $b['nome']);
        });

        $this->json([
            'success' => true,
            'imobiliarias' => $imobiliarias
        ]);
    }

    /**
     * Remover registro do histórico de uploads
     */
    public function remover(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }

        if (empty($id)) {
            $this->json(['success' => false, 'error' => 'ID não informado'], 400);
            return;
        }

        try {
            // Verificar se o registro existe
            $registro = $this->historicoUploadModel->find($id);
            if (!$registro) {
                $this->json(['success' => false, 'error' => 'Registro não encontrado'], 404);
                return;
            }

            // Salvar dados antes de deletar
            $imobiliariaId = $registro['imobiliaria_id'];
            $dataUpload = $registro['created_at']; // Data/hora do upload

            // ⚠️ IMPORTANTE: Antes de remover o histórico, remover os registros de locatarios_contratos
            // que foram importados por este upload específico.
            // Como não há uma coluna historico_upload_id na tabela locatarios_contratos,
            // vamos remover os registros que foram criados/atualizados no mesmo período do upload
            // (com margem de alguns minutos para garantir que pegamos todos os registros processados).
            
            try {
                // Calcular janela de tempo: 5 minutos antes e 10 minutos depois do upload
                // (para garantir que pegamos todos os registros processados naquele upload)
                $dataInicio = date('Y-m-d H:i:s', strtotime($dataUpload . ' -5 minutes'));
                $dataFim = date('Y-m-d H:i:s', strtotime($dataUpload . ' +10 minutes'));
                
                // Remover registros de locatarios_contratos que foram criados ou atualizados
                // no período deste upload
                $sqlDeleteContratos = "DELETE FROM locatarios_contratos 
                                      WHERE imobiliaria_id = ? 
                                      AND (
                                          (created_at >= ? AND created_at <= ?) 
                                          OR 
                                          (updated_at >= ? AND updated_at <= ?)
                                      )";
                
                $stmt = \App\Core\Database::query($sqlDeleteContratos, [
                    $imobiliariaId,
                    $dataInicio, $dataFim,
                    $dataInicio, $dataFim
                ]);
                
                $registrosRemovidos = $stmt->rowCount();
                error_log("IMPORTAÇÃO REMOÇÃO: Removidos {$registrosRemovidos} registro(s) de locatarios_contratos para imobiliaria_id={$imobiliariaId} no período do upload (ID histórico: {$id})");
                
            } catch (\Exception $e) {
                error_log("Erro ao remover registros de locatarios_contratos para histórico_uploads ID {$id}: " . $e->getMessage());
                // Não bloqueia a remoção do histórico, mas registra o erro
            }

            // Remover registro do histórico
            $deletado = $this->historicoUploadModel->delete($id);
            
            if (!$deletado) {
                error_log("Aviso: delete() retornou false para histórico_uploads ID: {$id}");
                $this->json([
                    'success' => false, 
                    'error' => 'Nenhum registro foi removido. O registro pode não existir ou já ter sido removido.'
                ], 400);
                return;
            }

            // Verificar se foi realmente deletado
            $verificar = $this->historicoUploadModel->find($id);
            if ($verificar !== null) {
                error_log("Erro: Registro ainda existe após delete() - histórico_uploads ID: {$id}");
                $this->json([
                    'success' => false, 
                    'error' => 'Erro ao remover registro do banco de dados'
                ], 500);
                return;
            }

            error_log("Registro removido com sucesso - histórico_uploads ID: {$id} (e registros relacionados em locatarios_contratos)");

            $this->json([
                'success' => true,
                'message' => 'Registro do histórico e registros relacionados em locatarios_contratos removidos com sucesso',
                'imobiliaria_id' => $imobiliariaId
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao remover histórico de upload ID {$id}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->json([
                'success' => false, 
                'error' => 'Erro ao remover registro: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Salvar arquivo CSV no servidor
     */
    private function salvarArquivoCSV(string $tmpPath, string $fileName, int $imobiliariaId): string
    {
        // Criar diretório para uploads de CSV se não existir
        $uploadDir = __DIR__ . '/../../storage/uploads/csv/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Criar subdiretório por imobiliária
        $imobiliariaDir = $uploadDir . $imobiliariaId . '/';
        if (!is_dir($imobiliariaDir)) {
            mkdir($imobiliariaDir, 0755, true);
        }
        
        // Gerar nome único para o arquivo (timestamp + nome original)
        $timestamp = date('YmdHis');
        $nomeUnico = $timestamp . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        $caminhoCompleto = $imobiliariaDir . $nomeUnico;
        
        // Copiar arquivo (não mover, pois pode ser um arquivo temporário já processado)
        // Se for um arquivo enviado via upload, usar move_uploaded_file
        // Se for um arquivo temporário, usar copy
        if (is_uploaded_file($tmpPath)) {
            if (!move_uploaded_file($tmpPath, $caminhoCompleto)) {
                throw new \Exception("Erro ao salvar arquivo no servidor");
            }
        } else {
            // É um arquivo temporário, usar copy
            if (!copy($tmpPath, $caminhoCompleto)) {
                throw new \Exception("Erro ao copiar arquivo no servidor");
            }
        }
        
        // Retornar caminho relativo (sem o caminho absoluto)
        return 'storage/uploads/csv/' . $imobiliariaId . '/' . $nomeUnico;
    }
    
    /**
     * Download de arquivo CSV
     */
    public function download(int $id): void
    {
        $historico = $this->historicoUploadModel->find($id);
        
        if (!$historico) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Registro não encontrado']);
            exit;
        }
        
        if (empty($historico['caminho_arquivo'])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Arquivo não encontrado no servidor']);
            exit;
        }
        
        $caminhoCompleto = __DIR__ . '/../../' . $historico['caminho_arquivo'];
        
        if (!file_exists($caminhoCompleto)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Arquivo não existe no servidor']);
            exit;
        }
        
        // Definir headers para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . addslashes($historico['nome_arquivo']) . '"');
        header('Content-Length: ' . filesize($caminhoCompleto));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Limpar buffer de saída
        if (ob_get_level()) {
            ob_clean();
        }
        flush();
        
        // Enviar arquivo
        readfile($caminhoCompleto);
        exit;
    }
    
    /**
     * Garantir que a tabela locatarios_contratos existe
     */
    private function garantirTabelaLocatariosContratos(): void
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM information_schema.tables 
                    WHERE table_schema = DATABASE() 
                    AND table_name = 'locatarios_contratos'";
            $result = \App\Core\Database::fetch($sql);
            
            if (empty($result) || ($result['count'] ?? 0) == 0) {
                $createTableSql = "CREATE TABLE locatarios_contratos (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    imobiliaria_id INT NOT NULL,
                    cpf VARCHAR(14) NOT NULL,
                    inquilino_nome VARCHAR(255) NULL,
                    numero_contrato VARCHAR(50) NOT NULL,
                    tipo_imovel VARCHAR(50) NULL,
                    cidade VARCHAR(100) NULL,
                    estado VARCHAR(2) NULL,
                    bairro VARCHAR(100) NULL,
                    cep VARCHAR(10) NULL,
                    endereco VARCHAR(255) NULL,
                    numero VARCHAR(20) NULL,
                    complemento VARCHAR(100) NULL,
                    unidade VARCHAR(50) NULL,
                    empresa_fiscal VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (imobiliaria_id) REFERENCES imobiliarias(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_cpf_contrato_imobiliaria (imobiliaria_id, cpf, numero_contrato),
                    INDEX idx_cpf_imobiliaria (imobiliaria_id, cpf)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                \App\Core\Database::query($createTableSql);
                error_log("Tabela locatarios_contratos criada automaticamente");
            }
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate table') === false) {
                error_log("Erro ao verificar/criar tabela locatarios_contratos: " . $e->getMessage());
            }
        }
    }

    /**
     * Garantir que a tabela historico_uploads existe
     */
    private function garantirTabelaHistoricoUploads(): void
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM information_schema.tables 
                    WHERE table_schema = DATABASE() 
                    AND table_name = 'historico_uploads'";
            $result = \App\Core\Database::fetch($sql);
            
            if (empty($result) || ($result['count'] ?? 0) == 0) {
                $createTableSql = "CREATE TABLE historico_uploads (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    imobiliaria_id INT NOT NULL,
                    nome_arquivo VARCHAR(255) NOT NULL,
                    tamanho_arquivo INT NOT NULL COMMENT 'Tamanho do arquivo em bytes',
                    usuario_id INT NOT NULL,
                    total_registros INT NOT NULL DEFAULT 0 COMMENT 'Total de registros processados',
                    registros_sucesso INT NOT NULL DEFAULT 0 COMMENT 'Registros processados com sucesso',
                    registros_erro INT NOT NULL DEFAULT 0 COMMENT 'Registros com erro',
                    detalhes_erros TEXT NULL COMMENT 'JSON com detalhes dos erros encontrados',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (imobiliaria_id) REFERENCES imobiliarias(id) ON DELETE CASCADE,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    INDEX idx_imobiliaria (imobiliaria_id),
                    INDEX idx_usuario (usuario_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='Histórico de uploads de planilhas CSV por imobiliária'";
                
                \App\Core\Database::query($createTableSql);
                error_log("Tabela historico_uploads criada automaticamente");
            }
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate table') === false) {
                error_log("Erro ao verificar/criar tabela historico_uploads: " . $e->getMessage());
            }
        }
    }
}

