<?php

namespace Tests;

use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;
use FratellanzaMilitare\SecurityLayer\AccessControlList;
use FratellanzaMilitare\SecurityLayer\Amministratore;
use FratellanzaMilitare\SecurityLayer\Operatore;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;

abstract class TestCase extends BaseTestCase
{
    public ?PDO $db = null;
    public ?PDOSocioRepository $repo = null;
    public ?AccessControlList $acl = null;
    public ?Amministratore $admin = null;
    public ?Operatore $operatore = null;
    public ?string $logFile = null;
    protected $app;

    /** @var \PDO */
    public $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Bootstrap Slim App
        $containerBuilder = new \DI\ContainerBuilder();
        $root = dirname(__DIR__);

        // Load definitions
        $definitions = require $root . '/config/container.php';
        foreach ($definitions as $def) {
            $containerBuilder->addDefinitions($def);
        }

        $container = $containerBuilder->build();

        // Database Connection for Tests
        if ($this->db === null) {
            // Use in-memory SQLite or test DB if configured, otherwise use real connection (careful!)
            // For now, assuming environment setup in Pest.php handles DB_DATABASE
            $this->db = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        }

        // Setup AuditTrail
        $audit = \FratellanzaMilitare\SecurityLayer\AuditTrail::getInstance();
        $audit->setPdo($this->db);

        // CREATE APP
        \Slim\Factory\AppFactory::setContainer($container);
        $this->app = \Slim\Factory\AppFactory::create();

        // Register Middleware
        $middleware = require $root . '/config/middleware.php';
        $middleware($this->app);

        // Register Routes
        $routes = require $root . '/config/routes.php';
        $routes($this->app);
    }

    protected function tearDown(): void
    {
        if ($this->db) {
            // Delete all users except admin to ensure tests start fresh
            $this->db->exec("DELETE FROM users WHERE username != 'admin'");
            $this->db->exec("DELETE FROM audit_logs");
        }
        parent::tearDown();
    }

    protected function withRouting(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ServerRequestInterface
    {
        $routeParser = $this->createMock(\Slim\Interfaces\RouteParserInterface::class);
        $routeParser->method('urlFor')->willReturnCallback(function ($name, $data = []) {
            $path = "/public/" . str_replace('_', '/', $name);
            foreach ($data as $k => $v) {
                $path = str_replace(":$k", $v, $path);
            }
            return $path;
        });

        $routingResults = $this->createMock(\Slim\Routing\RoutingResults::class);

        // Check if request is a PHPUnit Mock Object
        if ($request instanceof \PHPUnit\Framework\MockObject\MockObject) {
            $request->method('getAttribute')->willReturnCallback(function ($name) use ($routeParser, $routingResults) {
                switch ($name) {
                    case \Slim\Routing\RouteContext::ROUTE_PARSER:
                        return $routeParser;
                    case \Slim\Routing\RouteContext::ROUTING_RESULTS:
                        return $routingResults;
                    case \Slim\Routing\RouteContext::ROUTE:
                        return null;
                    case 'csrf_name':
                    case 'csrf_value':
                        return 'csrf_mock';
                    default:
                        return null;
                }
            });
            return $request;
        }

        // Fallback for real objects
        return $request
            ->withAttribute(\Slim\Routing\RouteContext::ROUTE_PARSER, $routeParser)
            ->withAttribute(\Slim\Routing\RouteContext::ROUTING_RESULTS, $routingResults)
            ->withAttribute(\Slim\Routing\RouteContext::ROUTE, null);
    }
}
