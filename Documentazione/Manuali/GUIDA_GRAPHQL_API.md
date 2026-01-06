# Guida API GraphQL

## Endpoint
**URL**: `/api/graphql`
**Method**: `POST`
**Auth**: Richiede API Key (`X-API-Key`) se configurato, o accesso pubblico se whitelistato.

## Schema
Il sistema espone (attualmente) le seguenti risorse:
- `socio(codiceFiscale: String!)`: Recupera un singolo socio.
- `soci(page: Int, perPage: Int, stato: String)`: Recupera una lista paginata.

## Esempi di Query

### 1. Recupero Lista Soci
Recupera CF, Nome e Cognome dei primi 10 soci.
```graphql
query {
  soci(page: 1, perPage: 10) {
    codiceFiscale
    nome
    cognome
    statoIscrizione
  }
}
```

### 2. Dettaglio Socio
```graphql
query {
  socio(codiceFiscale: "RSSMRA80A01H501U") {
    nome
    cognome
    email
    telefono
    indirizzo
    dataIscrizione
  }
}
```

## Tools
È possibile testare le query usando:
1. **Postman/Insomnia**: Puntando all'endpoint `/api/graphql`.
2. **DevTools**: Usando lo script `bin/debug_tools/graphql_debug.php` per visualizzare lo schema completo.
