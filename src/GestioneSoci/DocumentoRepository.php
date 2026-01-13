<?php

namespace MCAG\GestioneSoci;

/**
 * Interfaccia per la persistenza dei documenti.
 * 
 * Implementa il pattern Repository per gestire il ciclo di vita dei documenti.
 */
interface DocumentoRepository
{
    /**
     * Salva un documento e lo associa a un socio.
     */
    public function save(Documento $documento, string $socioCf): void;

    /**
     * Recupera un documento per ID Univoco.
     */
    public function findById(string $uuid): ?Documento;

    /**
     * Trova tutti i documenti associati a un socio.
     * 
     * @return Documento[]
     */
    public function findBySocio(string $socioCf): array;
}


