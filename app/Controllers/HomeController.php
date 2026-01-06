<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $this->view('home.index');
    }

    public function condicoesGerais(): void
    {
        $this->view('home.condicoes-gerais');
    }
}
