<?php

namespace MCAG\Controller\Public;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ApiProxyController
{
    /**
     * Proxies USGS Earthquake requests using native cURL
     */
    public function usgs(Request $request, Response $response, array $args): Response
    {
        $path = $args['path'] ?? '';
        $url = 'https://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/' . $path;
        return $this->proxyRequest($url, $response);
    }

    /**
     * Proxies OpenSky Network requests using native cURL
     */
    public function opensky(Request $request, Response $response, array $args): Response
    {
        $path = $args['path'] ?? '';
        $url = 'https://opensky-network.org/api/' . $path;

        $auth = null;
        if (!empty($_ENV['VITE_OPENSKY_USERNAME']) && !empty($_ENV['VITE_OPENSKY_PASSWORD'])) {
            $auth = $_ENV['VITE_OPENSKY_USERNAME'] . ':' . $_ENV['VITE_OPENSKY_PASSWORD'];
        }

        return $this->proxyRequest($url, $response, $auth);
    }

    private function proxyRequest(string $url, Response $response, ?string $auth = null): Response
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For Ampps/Local environments
        curl_setopt($ch, CURLOPT_USERAGENT, 'MCAG-Intelligence-Proxy/1.0');

        if ($auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth);
        }

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($result === false) {
            $response->getBody()->write(json_encode(['error' => 'Proxy Connectivity Error']));
            return $response->withStatus(502)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write($result);
        return $response
            ->withHeader('Content-Type', $contentType ?: 'application/json')
            ->withStatus($httpCode);
    }
}
