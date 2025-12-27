<?php

namespace FratellanzaMilitare\GestioneSoci;

/**
 * Interfaccia principale per il repository dei Soci.
 * 
 * Implementa le operazioni CRUD e di ricerca avanzata (filtraggio, statistiche).
 * Astrae la persistenza (DB, File, etc.) dal dominio.
 */
interface SocioRepository
{
    /**
     * Salva o aggiorna un socio.
     */
    public function save(Socio $socio): void;

    /**
     * Trova un socio per Codice Fiscale.
     */
    public function findByCodiceFiscale(string $cf): ?Socio;

    /**
     * Recupera l'elenco completo dei soci.
     * @return Socio[]
     */
    public function findAll(): array;

    /**
     * Elimina un socio (o lo marca come cancellato).
     */
    public function delete(string $cf): void;

    /**
     * Calcola statistiche globali (totali, attivi, morosi, distribuzione età).
     * @return array<string, mixed>
     */
    public function getStatistics(): array;

    /**
     * Cerca soci in base a filtri specifici.
     * 
     * @param array $filters ['stato' => string, 'moroso' => bool]
     * @return Socio[]
     */
    public function findByFilters(array $filters): array;

    /**
     * Esegue una ricerca full-text (nome, cognome, cf, matricola).
     * @return Socio[]
     */
    public function search(string $query): array;
}
