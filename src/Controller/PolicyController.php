<?php

namespace MCAG\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Mustache_Engine;

class PolicyController
{
    private Mustache_Engine $engine;

    public function __construct(Mustache_Engine $engine)
    {
        $this->engine = $engine;
    }

    public function privacy(Request $request, Response $response): Response
    {
        // In un caso reale, il contenuto verrebbe caricato da un file MD o DB
        $html = $this->engine->render('layout/base', [
            'content' => '<h1>Privacy Policy</h1><p>Informativa sul trattamento dei dati personali (GDPR Art. 13-14)... [BOZZA]</p>',
            'title' => 'Privacy Policy'
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function cookie(Request $request, Response $response): Response
    {
        $html = $this->engine->render('layout/base', [
            'content' => '<h1>Cookie Policy</h1><p>Informativa sull\'uso dei cookie... [BOZZA]</p>',
            'title' => 'Cookie Policy'
        ]);
        $response->getBody()->write($html);
        return $response;
    }
}


