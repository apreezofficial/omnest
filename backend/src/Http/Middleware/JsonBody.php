<?php

declare(strict_types=1);

namespace Omnest\Http\Middleware;

use JsonException;
use Omnest\Exceptions\HttpException;
use Omnest\Http\Request;
use Omnest\Http\Response;

/** Parses JSON request bodies; rejects malformed JSON and non-object payloads. */
final class JsonBody implements Middleware
{
    public function process(Request $request, callable $next): Response
    {
        if (trim($request->rawBody) === '' || in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $type = (string) $request->header('content-type', '');
        if (!str_contains(strtolower($type), 'application/json')) {
            throw new HttpException(415, 'unsupported_media_type', 'Send the request body as application/json.');
        }

        try {
            $data = json_decode($request->rawBody, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw HttpException::badRequest('Request body is not valid JSON.');
        }

        if (!is_array($data) || array_is_list($data) && $data !== []) {
            throw HttpException::badRequest('Request body must be a JSON object.');
        }

        return $next($request->withBody($data));
    }
}
