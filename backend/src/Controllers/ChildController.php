<?php

declare(strict_types=1);

namespace Omnest\Controllers;

use Omnest\Exceptions\HttpException;
use Omnest\Exceptions\ValidationException;
use Omnest\Http\Request;
use Omnest\Http\Response;
use Omnest\Repositories\ChildRepository;
use Omnest\Repositories\DeviceRepository;
use Omnest\Services\PairingService;
use Omnest\Support\Clock;
use Omnest\Support\Validator;

final class ChildController
{
    private const MAX_CHILDREN = 10;

    public function __construct(
        private readonly ChildRepository $children,
        private readonly DeviceRepository $devices,
        private readonly PairingService $pairing,
    ) {
    }

    public function index(Request $request): Response
    {
        $rows = $this->children->allForUser(self::userId($request));

        return Response::success(array_map(ChildRepository::present(...), $rows));
    }

    public function store(Request $request): Response
    {
        $userId = self::userId($request);
        $in = $this->validate($request, creating: true);

        if (count($this->children->allForUser($userId)) >= self::MAX_CHILDREN) {
            throw new HttpException(422, 'limit_reached', 'You can add up to ' . self::MAX_CHILDREN . ' children.');
        }

        $id = $this->children->create($userId, $in);

        return Response::created(ChildRepository::present($this->children->findForUser($id, $userId)));
    }

    public function show(Request $request): Response
    {
        return Response::success(ChildRepository::present($this->ownedChild($request)));
    }

    public function update(Request $request): Response
    {
        $child = $this->ownedChild($request);
        $userId = self::userId($request);
        $this->children->update((int) $child['id'], $userId, $this->validate($request, creating: false));

        return Response::success(ChildRepository::present($this->children->findForUser((int) $child['id'], $userId)));
    }

    public function destroy(Request $request): Response
    {
        $child = $this->ownedChild($request);
        $this->children->delete((int) $child['id'], self::userId($request));

        return Response::noContent();
    }

    public function devices(Request $request): Response
    {
        $child = $this->ownedChild($request);

        return Response::success(array_map(DeviceRepository::present(...), $this->devices->forChild((int) $child['id'])));
    }

    public function createPairingCode(Request $request): Response
    {
        $child = $this->ownedChild($request);

        return Response::created($this->pairing->createCode((int) $child['id']));
    }

    /** @return array<string, mixed> */
    private function validate(Request $request, bool $creating): array
    {
        $req = $creating ? 'required|' : '';
        $in = Validator::validate($request->all(), [
            'name' => $req . 'string|max:60',
            'age_tier' => $req . 'in:' . implode(',', ChildRepository::AGE_TIERS),
            'birth_date' => 'nullable|date',
            'avatar' => 'nullable|string|max:32',
        ]);

        if (isset($in['birth_date']) && $in['birth_date'] > Clock::now()->format('Y-m-d')) {
            throw new ValidationException(['birth_date' => ['Birth date can\'t be in the future.']]);
        }

        return $in;
    }

    /** @return array<string, mixed> */
    private function ownedChild(Request $request): array
    {
        $id = (int) $request->param('id');
        $child = $id > 0 ? $this->children->findForUser($id, self::userId($request)) : null;
        if ($child === null) {
            // 404 (not 403) so ids of other families' children aren't confirmed.
            throw HttpException::notFound('Child not found.');
        }

        return $child;
    }

    private static function userId(Request $request): int
    {
        return (int) $request->attribute('user')['id'];
    }
}
