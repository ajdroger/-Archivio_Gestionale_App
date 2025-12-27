<?php

namespace FratellanzaMilitare\Controller\Anagrafica\Soci;

use FratellanzaMilitare\Service\FiscalCodeCalculator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller dedicato ad operazioni speciali e azioni di utilità sui soci.
 */
/**
 * Controller per azioni specifiche sui Soci.
 * 
 * Gestisce operazioni di utilità come il calcolo automatico del Codice Fiscale.
 */
class ActionController
{
    private LoggerInterface $auditLogger;

    public function __construct(LoggerInterface $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Calcola il Codice Fiscale tramite il servizio dedicato.
     * 
     * Accetta dati anagrafici in POST e restituisce il CF calcolato.
     * Gestisce e logga eventuali errori di calcolo.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface JSON
     */
    public function calculateFiscalCode(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        try {
            $calculator = new FiscalCodeCalculator();
            $cf = $calculator->calculate($data['nome'], $data['cognome'], $data['data_nascita'], $data['sesso'], $data['luogo']);
            $response->getBody()->write(json_encode(['cf' => $cf]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->auditLogger->error("Errore calcolo CF: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}
