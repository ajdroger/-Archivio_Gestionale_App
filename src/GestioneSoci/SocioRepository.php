<?php

namespace FratellanzaMilitare\GestioneSoci;

interface SocioRepository
{
    public function save(Socio $socio): void;
    public function findByCodiceFiscale(string $cf): ?Socio;
    /**
     * @return Socio[]
     */
    public function findAll(): array;
    public function delete(string $cf): void;
    /**
     * @return array<string, mixed>
     */
    public function getStatistics(): array;
    /**
     * @param array $filters ['stato' => string, 'moroso' => bool]
     * @return Socio[]
     */
    public function findByFilters(array $filters): array;

    /**
     * @return Socio[]
     */
    public function search(string $query): array;
}
