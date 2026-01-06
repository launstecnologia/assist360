<?php
/**
 * Script para executar correção da solicitação 249 via endpoint
 * Acesse: http://seu-dominio/admin/solicitacoes/249/corrigir-para-manual
 * Ou execute via POST
 */

// Simular requisição POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [];

// Incluir o sistema
require __DIR__ . '/../index.php';

