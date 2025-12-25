<?php

namespace FratellanzaMilitare\SecurityLayer;

class Amministratore extends UtenteSistema
{
    /**
     * Crea un nuovo utente nel sistema
     * @param string $username Nome utente
     * @param string $password Password in chiaro (sarà hashata)
     * @param string $tipo Tipo di utente ('Operatore' o 'Amministratore')
     * @return int ID del nuovo utente
     */
    public function creaUtente(string $username, string $password, string $tipo = 'Operatore'): int
    {
        // Hash della password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $db = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, role, created_at) VALUES (:username, :password, :role, NOW())");
        $stmt->execute([
            ':username' => $username,
            ':password' => $passwordHash,
            ':role' => $tipo
        ]);
        $newId = (int) $db->lastInsertId();

        AuditTrail::getInstance()->logEvento(
            $this,
            'CREATE_USER',
            "new_user_{$newId}_{$username}"
        );

        return $newId;
    }

    /**
     * Revoca i permessi di un utente
     * @param int $userId ID dell'utente
     * @param string $permesso Permesso da revocare
     */
    public function revocaPermessi(int $userId, string $permesso): void
    {
        // In produzione: rimuovere dal database
        AuditTrail::getInstance()->logEvento(
            $this,
            'REVOKE_PERMISSION',
            "user_{$userId}_permission_{$permesso}"
        );

        // Nota: la revoca effettiva richiederebbe modifica dell'ACL nel database
    }

    /**
     * Visualizza il log di audit
     * @param array $filters Filtri opzionali
     * @return array Eventi del log
     */
    public function visualizzaAuditLog(array $filters = []): array
    {
        $auditTrail = AuditTrail::getInstance();
        return $auditTrail->ricercaAzioni($filters);
    }

    /**
     * Genera un report di audit
     * @param string $periodo Periodo ('today', 'week', 'month')
     * @return array Report statistico
     */
    public function generaReportAudit(string $periodo = 'today'): array
    {
        $auditTrail = AuditTrail::getInstance();
        return $auditTrail->generaReport($periodo);
    }
}
