<?php

namespace FratellanzaMilitare\Debug;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

class GlobalExceptionHandler
{
    private \Psr\Log\LoggerInterface $logger;
    private ?\Mustache_Engine $mustache;

    public function __construct(\Psr\Log\LoggerInterface $logger, ?\Mustache_Engine $mustache = null)
    {
        $this->logger = $logger;
        $this->mustache = $mustache;
    }

    // Per uso in contesti non-Slim (CLI, script)
    public function handleCli(Throwable $t): void
    {
        $this->logException($t, 'CLI_CRASH');
        echo "\n\033[1;31m[ERRORE CRITICO]\033[0m Si è verificato un errore imprevisto.\n";
        echo "Dettagli salvati nei log di sistema.\n";
        echo "Messaggio: " . $t->getMessage() . "\n";
        exit(1);
    }

    // Per uso come Error Handler personalizzato in Slim
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $this->logException($exception, 'HTTP_500', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri()
        ]);

        $acceptHeader = $request->getHeaderLine('Accept');
        $response = new \Slim\Psr7\Response();

        // 1. Render HTML if requested and Engine is available
        if ($this->mustache && str_contains($acceptHeader, 'text/html')) {
            $html = $this->mustache->render('error', [
                'title' => 'Errore del Sistema',
                'message' => 'Si è verificato un problema tecnico imprevisto.',
                'code' => 500,
                'debug_msg' => $displayErrorDetails ? $exception->getMessage() : null,
                'trace' => $displayErrorDetails ? $exception->getTraceAsString() : null,
                'year' => date('Y')
            ]);
            $response->getBody()->write($html);
            return $response->withHeader('Content-Type', 'text/html')->withStatus(500);
        }

        // 2. Fallback to JSON
        $response->getBody()->write(json_encode([
            'error' => 'Si è verificato un errore interno. Riprovare più tardi.',
            'debug_msg' => $displayErrorDetails ? $exception->getMessage() : null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    private function logException(Throwable $t, string $code, array $extraContext = []): void
    {
        $context = array_merge([
            'error_code' => $code,
            'file' => $t->getFile(),
            'line' => $t->getLine(),
            'trace' => $t->getTraceAsString()
        ], $extraContext);

        $this->logger->error($t->getMessage(), $context);
    }

    public static function registerGlobalHandlers(\Psr\Log\LoggerInterface $logger): void
    {
        $handler = new self($logger);

        // Exception Handler
        set_exception_handler([$handler, 'handleCli']);

        // Error Handler (converts PHP errors to Exceptions)
        set_error_handler(function ($severity, $message, $file, $line): bool {
            if (!(error_reporting() & $severity)) {
                return true;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }
}
