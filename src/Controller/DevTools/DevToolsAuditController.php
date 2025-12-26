<?php

namespace FratellanzaMilitare\Controller\DevTools;

use Mustache_Engine;
use FratellanzaMilitare\SecurityLayer\AuditTrail;
use Psr\Http\Message\ServerRequestInterface as Request;

class DevToolsAuditController
{
    private Mustache_Engine $mustache;
    private \PDO $pdo;

    public function __construct(Mustache_Engine $mustache, \PDO $pdo)
    {
        $this->mustache = $mustache;
        $this->pdo = $pdo;
    }

    public function getLogs(Request $request): array
    {
        $params = $request->getQueryParams();

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
