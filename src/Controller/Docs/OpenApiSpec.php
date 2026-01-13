<?php

namespace MCAG\Controller\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "2.4.0",
    title: "MCAG API - Militare Civile Archivio Gestionale",
    description: "API REST/GraphQL per la gestione dell'Archivio Militare e Civile. Include autenticazione 2FA, gestione soci, documenti e statistiche.",
    contact: new OA\Contact(
        email: "tech@MCAG.it"
    ),
    license: new OA\License(
        name: "Proprietary",
        url: "http://MCAG.it"
    )
)]
#[OA\Server(
    url: "/",
    description: "Main App Server"
)]
#[OA\SecurityScheme(
    securityScheme: "cookieAuth",
    type: "apiKey",
    in: "cookie",
    name: "PHPSESSID"
)]
#[OA\SecurityScheme(
    securityScheme: "apiKey",
    type: "apiKey",
    in: "header",
    name: "X-API-Key"
)]
class OpenApiSpec
{
    // This class is used purely for global OA annotations.
}


