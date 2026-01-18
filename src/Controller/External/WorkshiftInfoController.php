<?php

namespace MCAG\Controller\External;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MCAG\InfrastrutturaIT\Persistence\PDOWorkshiftRepository;

class WorkshiftInfoController
{
    private $view;
    private $repository;

    public function __construct($view, PDOWorkshiftRepository $repository)
    {
        $this->view = $view;
        $this->repository = $repository;
    }

    private function render(Response $response, string $template, string $title): Response
    {
        // Fetch minimal data for layout (user status, etc.) if needed
        // For static pages, we just need basic identity info usually handled by middleware or session
        // Here we pass a default title and basic user mock if session is missing

        // Calculate dynamic base URL to ensure assets are loaded correctly
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $baseUrl = $scriptDir === '/' ? '' : $scriptDir;

        $viewData = [
            'title' => $title,
            'base_url' => $baseUrl,
            'year' => date('Y'),
            'username' => $_SESSION['user_id'] ?? 'Ospite',
            'user_role' => $_SESSION['role'] ?? 'ospite'
        ];

        $html = $this->view->render("workshift/info/{$template}", $viewData);
        $response->getBody()->write($html);
        return $response;
    }

    public function support(Request $request, Response $response): Response
    {
        return $this->render($response, 'support', 'Support Line - WorkShift');
    }

    public function hrPolicy(Request $request, Response $response): Response
    {
        return $this->render($response, 'hr-policy', 'HR Policy - WorkShift');
    }

    public function systemStatus(Request $request, Response $response): Response
    {
        return $this->render($response, 'status', 'System Status - WorkShift');
    }

    public function laborLaws(Request $request, Response $response): Response
    {
        return $this->render($response, 'labor-laws', 'Labor Laws - WorkShift');
    }

    public function privacy(Request $request, Response $response): Response
    {
        return $this->render($response, 'privacy', 'Privacy Policy - WorkShift');
    }

    public function terms(Request $request, Response $response): Response
    {
        return $this->render($response, 'terms', 'Terms of Service - WorkShift');
    }
}
