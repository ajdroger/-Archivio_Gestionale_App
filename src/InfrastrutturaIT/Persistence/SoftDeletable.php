<?php
declare(strict_types=1);

namespace FratellanzaMilitare\InfrastrutturaIT\Persistence;

trait SoftDeletable
{
    public function softDelete(string $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE " . $this->getTableName() . "
            SET deleted_at = NOW()
            WHERE " . $this->getPrimaryKey() . " = ?
        ");
        $stmt->execute([$id]);
    }

    public function restore(string $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE " . $this->getTableName() . "
            SET deleted_at = NULL
            WHERE " . $this->getPrimaryKey() . " = ?
        ");
        $stmt->execute([$id]);
    }

    public function findWithTrashed(string $id)
    {
        // Questo metodo dovrebbe essere sovrascritto nella classe che usa il trait
        // se si vuole permettere il recupero specifico di elementi cancellati.
        return null;
    }

    abstract protected function getTableName(): string;
    abstract protected function getPrimaryKey(): string;
}
