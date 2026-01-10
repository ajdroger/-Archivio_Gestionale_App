<?php

declare(strict_types=1);

namespace FratellanzaMilitare\Middleware;

use HTMLPurifier;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class InputSanitizerMiddleware implements MiddlewareInterface
{
    private HTMLPurifier $purifier;

    // Define keys that should skip sanitation (e.g. password fields)
    private array $skipKeys = [
        'password',
        'password_confirmation',
        'current_password',
        '_csrf_token',
        'csrf_name',
        'csrf_value'
    ];

    public function __construct(HTMLPurifier $purifier)
    {
        $this->purifier = $purifier;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Sanitize Query Params
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams)) {
            $cleanedQueryParams = $this->sanitizeArray($queryParams);
            $request = $request->withQueryParams($cleanedQueryParams);
        }

        // Sanitize Parsed Body (Post Data)
        $parsedBody = $request->getParsedBody();
        if (!empty($parsedBody) && is_array($parsedBody)) {
            $cleanedParsedBody = $this->sanitizeArray($parsedBody);
            $request = $request->withParsedBody($cleanedParsedBody);
        }

        // Sanitize Arguments (Route Args - if any are mutable/injectable)
        // Usually handled by router, but strictly speaking route args are URL encoded.

        return $handler->handle($request);
    }

    private function sanitizeArray(array $input): array
    {
        foreach ($input as $key => $value) {
            // Skip sensitive fields or fields that shouldn't be altered
            if (in_array($key, $this->skipKeys, true)) {
                continue;
            }

            if (is_array($value)) {
                $input[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $input[$key] = $this->purifier->purify($value);
            }
        }

        return $input;
    }
}
