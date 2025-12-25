<?php

namespace FratellanzaMilitare\GestioneSoci;

interface DocumentoRepository
{
    public function save(Documento $documento, string $socioCf): void;
    public function findById(string $uuid): ?Documento;
    /**
     * @return Documento[]
     */
    public function findBySocio(string $socioCf): array;
}
