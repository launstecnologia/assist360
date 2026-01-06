<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Solicitacao;
use App\Models\Status;
use App\Models\Condicao;
use App\Models\Imobiliaria;
use App\Models\Categoria;
use App\Models\Subcategoria;

class RelatoriosController extends Controller
{
    private Solicitacao $solicitacaoModel;
    private Status $statusModel;
    private Condicao $condicaoModel;
    private Imobiliaria $imobiliariaModel;
    private Categoria $categoriaModel;
    private Subcategoria $subcategoriaModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->solicitacaoModel = new Solicitacao();
        $this->statusModel = new Status();
        $this->condicaoModel = new Condicao();
        $this->imobiliariaModel = new Imobiliaria();
        $this->categoriaModel = new Categoria();
        $this->subcategoriaModel = new Subcategoria();
    }

    public function index(): void
    {
        $user = $this->getUser();
        $suportaSla = $this->solicitacaoModel->hasDataLimiteCancelamento();

        $statusId = $this->input('status_id');
        $statusIds = [];
        if (!empty($statusId)) {
            $statusIds[] = $statusId;
        }

        $filtros = array_filter([
            'status_ids' => $statusIds,
            'status_id' => $statusId,
            'condicao_id' => $this->input('condicao_id'),
            'imobiliaria_id' => $this->input('imobiliaria_id'),
            'categoria_id' => $this->input('categoria_id'),
            'subcategoria_id' => $this->input('subcategoria_id'),
            'cpf' => trim((string) $this->input('cpf')),
            'numero_contrato' => trim((string) $this->input('numero_contrato')),
            'locatario_nome' => trim((string) $this->input('locatario_nome')),
            'data_inicio' => $this->input('data_inicio'),
            'data_fim' => $this->input('data_fim'),
            'agendamento_inicio' => $this->input('agendamento_inicio'),
            'agendamento_fim' => $this->input('agendamento_fim'),
        ], function ($valor) {
            if (is_array($valor)) {
                return !empty($valor);
            }
            return $valor !== null && $valor !== '';
        });

        if ($this->input('sla_atrasado') !== null) {
            if ($suportaSla) {
                $filtros['sla_atrasado'] = $this->input('sla_atrasado') === '1';
            }
        }

        if ($this->input('precisa_reembolso') !== null) {
            $filtros['precisa_reembolso'] = $this->input('precisa_reembolso') === '1';
        }

        if ($this->input('whatsapp_enviado') !== null) {
            $valorWhatsapp = $this->input('whatsapp_enviado');
            if ($valorWhatsapp === '1' || $valorWhatsapp === '0') {
                $filtros['whatsapp_enviado'] = $valorWhatsapp === '1';
            }
        }

        $limite = (int) ($this->input('limite') ?? 100);
        if ($limite <= 0) {
            $limite = 100;
        }
        if ($limite > 1000) {
            $limite = 1000;
        }

        $resumo = $this->solicitacaoModel->getRelatorioResumo($filtros);
        $solicitacoes = $this->solicitacaoModel->getRelatorioSolicitacoes($filtros, $limite);

        $this->view('relatorios.index', [
            'user' => $user,
            'pageTitle' => 'Relatórios',
            'statusLista' => $this->statusModel->getAll(),
            'condicoes' => $this->condicaoModel->getAll(),
            'imobiliarias' => $this->imobiliariaModel->getAtivas(),
            'categorias' => $this->categoriaModel->getAtivas(),
            'subcategorias' => $this->subcategoriaModel->getAtivasAgrupadas(),
            'suportaSla' => $suportaSla,
            'filtros' => $filtros,
            'resumo' => $resumo,
            'solicitacoes' => $solicitacoes,
            'limiteAtual' => $limite
        ]);
    }
}

