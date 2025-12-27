<?php

namespace FratellanzaMilitare\GestioneSoci;

/**
 * Rappresenta un modulo di iscrizione annuale.
 * 
 * Estende il documento base aggiungendo campi specifici per la gestione quote:
 * Anno di riferimento, Importo versato e Metodo di pagamento.
 */
class ModuloIscrizione extends Documento
{
    public int $AnnoSolare = 0;
    public float $QuotaVersata = 0.0;
    public string $MetodoPagamento = '';
}
