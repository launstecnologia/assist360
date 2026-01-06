<?php

namespace App\Core;

/**
 * Exceção especial para indicar que o router deve continuar
 * para a próxima rota sem retornar erro
 */
class RouteContinueException extends \Exception
{
}

