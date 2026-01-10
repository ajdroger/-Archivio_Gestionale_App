<?php

namespace FratellanzaMilitare\Controller\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "2.3.0",
    title: "Fratellanza Militare - Archivio Digitale API",
    description: "API Documentation for the Fratellanza Militare management system. Authenticated endpoints require a valid session or token.",
    contact: new OA\Contact(
        email: "tech@fratellanzamilitare.it"
    ),
    license: new OA\License(
        name: "Proprietary",
        url: "http://fratellanzamilitare.it"
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
class OpenApiSpec
{
    // This class is used purely for global OA annotations.
}
