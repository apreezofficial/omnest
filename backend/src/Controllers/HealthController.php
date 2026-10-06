<?php

declare(strict_types=1);

namespace Omnest\Controllers;

use Omnest\Http\Request;
use Omnest\Http\Response;

final class HealthController
{
    public function show(Request $request): Response
    {
        return Response::success(['status' => 'ok', 'time' => gmdate('c')]);
    }
}
