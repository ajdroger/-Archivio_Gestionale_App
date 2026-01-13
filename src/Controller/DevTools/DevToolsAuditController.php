<?php

namespace MCAG\Controller\DevTools;

use Mustache_Engine;
use MCAG\SecurityLayer\AuditTrail;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controller per la consultazione del Registro di Audit (Audit Log).
 * 
 * Permette di filtrare visualizzare le azioni tracciate nel sistema
 * (login, modifiche DB, accessi sensibili) per scopi di sicurezza e compliance.
 */
class DevToolsAuditController
{
    private Mustache_Engine $mustache;
    private \PDO $pdo;

    public function __construct(Mustache_Engine $mustache, \PDO $pdo)
    {
        $this->mustache = $mustache;
        $this->pdo = $pdo;
    }

    /**
     * Recupera e filtra i log di audit.
     * 
     * Accetta parametri di filtro via GET o POST (date, utente, risorsa).
     * Utilizza il servizio AuditTrail per eseguire la query.
     * 
     * @param Request $request
     * @return array Dati paginati dei log
     */
    public function getLogs(Request $request): array
    {
        $params = array_merge($request->getQueryParams(), (array) $request->getParsedBody());

        $auditFilters = [];
        if (!empty($params['start_date'])) {
            $auditFilters['start_date'] = $params['start_date'];
        }
        if (!empty($params['end_date'])) {
            $auditFilters['end_date'] = $params['end_date'];
        }
        if (!empty($params['audit_user'])) {
            $auditFilters['username'] = $params['audit_user'];
        }
        if (!empty($params['resource_id'])) {
            $auditFilters['resource_id'] = $params['resource_id'];
        }

        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = 20;

        $db = $this->pdo;
        $auditTrail = AuditTrail::getInstance();
        $auditTrail->setPdo($db);

        return $auditTrail->ricercaAzioni($auditFilters, $page, $perPage);
    }
}


