<?php

declare(strict_types=1);

namespace Omnest\Controllers;

use Omnest\Exceptions\HttpException;
use Omnest\Http\Request;
use Omnest\Http\Response;
use Omnest\Repositories\ChildRepository;
use Omnest\Repositories\DeviceRepository;
use Omnest\Services\PairingService;
use Omnest\Support\Validator;

/**
 * Two audiences:
 *   - child phone (auth.device): pair, me, fcm-token
 *   - parent (auth.parent): unpair a device
 */
final class DeviceController
{
    public function __construct(
        private readonly PairingService $pairing,
        private readonly DeviceRepository $devices,
    ) {
    }

    public function pair(Request $request): Response
    {
        $in = Validator::validate($request->all(), [
            'code' => 'required|string|max:12',
            'name' => 'required|string|max:80',
            'model' => 'nullable|string|max:80',
            'os_version' => 'nullable|string|max:20',
            'app_version' => 'nullable|string|max:20',
            'fcm_token' => 'nullable|string|max:255',
        ]);
        $code = $in['code'];
        unset($in['code']);

        $result = $this->pairing->pair($code, $in);

        return Response::created([
            'token' => $result['token'],
            'device' => DeviceRepository::present($result['device']),
            'child' => self::presentChild($result['child']),
        ]);
    }

    public function me(Request $request): Response
    {
        $device = $request->attribute('device');

        return Response::success([
            'device' => DeviceRepository::present($device),
            'child' => [
                'id' => (int) $device['child_id'],
                'name' => $device['child_name'],
                'age_tier' => $device['age_tier'],
            ],
        ]);
    }

    public function updateFcmToken(Request $request): Response
    {
        $in = Validator::validate($request->all(), ['fcm_token' => 'required|string|max:255']);
        $this->devices->updateFcmToken((int) $request->attribute('device')['id'], $in['fcm_token']);

        return Response::noContent();
    }

    /** Parent unpairs a phone: its token stops working immediately. */
    public function revoke(Request $request): Response
    {
        $device = $this->devices->findForUser((int) $request->param('id'), (int) $request->attribute('user')['id']);
        if ($device === null) {
            throw HttpException::notFound('Device not found.');
        }
        $this->devices->revoke((int) $device['id']);

        return Response::noContent();
    }

    /**
     * @param array<string, mixed> $child
     * @return array<string, mixed>
     */
    private static function presentChild(array $child): array
    {
        $full = ChildRepository::present($child);

        return ['id' => $full['id'], 'name' => $full['name'], 'age_tier' => $full['age_tier']];
    }
}
