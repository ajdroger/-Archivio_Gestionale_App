<?php

namespace FratellanzaMilitare\Controller\Docs;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Generator;
use Mustache_Engine;

class DocumentationController
{
    private $renderer;
    private $projectRoot;

    public function __construct(Mustache_Engine $renderer)
    {
        $this->renderer = $renderer;
        $this->projectRoot = dirname(__DIR__, 3); // Leads to project root from src/Controller/Docs
    }

    /**
     * Serves the Swagger UI HTML page.
     */
    public function ui(Request $request, Response $response): Response
    {
        $template = $this->renderer->loadTemplate('docs/swagger');
        $html = $template->render([
            'specUrl' => '/api/docs/json'
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Generates and returns the OpenAPI JSON specification.
     */
    public function spec(Request $request, Response $response): Response
    {
        $paths = [
            realpath($this->projectRoot . '/src/Controller'),
            realpath($this->projectRoot . '/src/DTO'),
        ];

        $paths = array_filter($paths);
        $openapi = Generator::scan($paths);

        $response->getBody()->write($openapi->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }
}
