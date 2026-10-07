<?php

declare(strict_types=1);

namespace Omnest\Controllers;

use Omnest\Exceptions\HttpException;
use Omnest\Http\Request;
use Omnest\Http\Response;
use Omnest\Repositories\ChildRepository;
use Omnest\Services\UsageService;

final class UsageController
{
    public function __construct(
        private readonly UsageService $usage,
        private readonly ChildRepository $children,
    ) {
    }

    /** Phone: POST /device/usage */
    public function ingest(Request $request): Response
    {
        return Response::success($this->usage->ingest($request->attribute('device'), $request->all()));
    }

    /** Phone: PUT /device/apps */
    public function apps(Request $request): Response
    {
        return Response::success($this->usage->syncApps($request->attribute('device'), $request->all()));
    }

    /** Parent: GET /children/{id}/usage/day?date=YYYY-MM-DD */
    public function day(Request $request): Response
    {
        $date = $request->query('date');

        return Response::success($this->usage->dayReport($this->childId($request), is_string($date) && $date !== '' ? $date : null));
    }

    /** Parent: GET /children/{id}/usage/range?days=7|14|30 */
    public function range(Request $request): Response
    {
        $days = (int) ($request->query('days') ?? 7);

        return Response::success($this->usage->rangeReport($this->childId($request), $days));
    }

    private function childId(Request $request): int
    {
        $id = (int) $request->param('id');
        if ($id <= 0 || $this->children->findForUser($id, (int) $request->attribute('user')['id']) === null) {
            throw HttpException::notFound('Child not found.');
        }

        return $id;
    }
}
