<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'CryptoBag API',
    description: 'API documentation for CryptoBag.'
)]
#[OA\Server(
    url: '/api',
    description: 'Application API'
)]
class ApiDocumentation {}
