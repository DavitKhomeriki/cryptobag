<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'CryptoBag API',
    description: 'API documentation for CryptoBag.'
)]
#[OA\Server(
    url: '/',
    description: 'Application'
)]
#[OA\Tag(
    name: 'Internal - BitGo Webhooks',
    description: 'Provider callbacks used by BitGo. These endpoints are documented for developers only and are not merchant-facing APIs.'
)]
class ApiDocumentation {}
