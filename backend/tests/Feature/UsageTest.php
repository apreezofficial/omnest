<?php

declare(strict_types=1);

namespace Omnest\Tests\Feature;

use Omnest\Http\Response;
use Omnest\Services\Maintenance;
use Omnest\Support\Clock;

final class UsageTest extends ApiTestCase
{
    private string $parent;
    private int $child;

    protected function setUp(): void
    {
        parent::setUp();
        // 21:00 UTC = 22:00 in Lagos, still the same local day.
        Clock::freeze('2026-06-10 21:00:00');
        $this->parent = $this->registerParent();
        $this->child = $this->createChild($this->parent);
    }

    private function pairPhone(string $ip = '10.0.0.9'): string
    {
        $code = $this->json('POST', "/api/v1/children/{$this->child}/pairing-codes", null, $this->parent)->decoded()['data']['code'];

        return $this->json('POST', '/api/v1/device/pair', ['code' => $code, 'name' => 'Phone'], null, $ip)->decoded()['data']['token'];
    }

    /** @param array<string, array<string, int>> $days date => [package => seconds] */
    private function upload(string $device, array $days, string $tz = 'Africa/Lagos'): Response
    {
        $payload = ['timezone' => $tz, 'days' => []];
        foreach ($days as $date => $apps) {
            $payload['days'][] = [
                'date' => $date,
                'apps' => array_map(static fn ($p, $s) => ['package' => $p, 'seconds' => $s], array_keys($apps), $apps),
            ];
        }

        return $this->json('POST', '/api/v1/device/usage', $payload, $device);
    }

    /** @return array<string, mixed> */
    private function day(?string $date = null): array
    {
        $q = $date ? "?date=$date" : '';
        $res = $this->json('GET', "/api/v1/children/{$this->child}/usage/day$q", null, $this->parent);
        self::assertSame(200, $res->status, $res->body);

        return $res->decoded()['data'];
    }

    public function testIngestAndDayReportWithLabels(): void
    {
        $device = $this->pairPhone();
        $apps = $this->json('PUT', '/api/v1/device/apps', ['apps' => [
            ['package' => 'com.whatsapp', 'label' => 'WhatsApp'],
            ['package' => 'com.zhiliaoapp.musically', 'label' => 'TikTok'],
        ]], $device);
        self::assertSame(['added' => 2, 'removed' => 0], $apps->decoded()['data']);

        $res = $this->upload($device, ['2026-06-10' => ['com.zhiliaoapp.musically' => 3600, 'com.whatsapp' => 1200, 'org.example.game' => 60]]);
        self::assertSame(200, $res->status, $res->body);
        self::assertSame(['2026-06-10'], $res->decoded()['data']['accepted']);

        $day = $this->day();
        self::assertSame('2026-06-10', $day['date']);
        self::assertTrue($day['is_today']);
        self::assertSame('Africa/Lagos', $day['timezone']);
        self::assertSame(4860, $day['total_seconds']);
        self::assertSame(['TikTok', 'WhatsApp', 'org.example.game'], array_column($day['apps'], 'label'));
        self::assertSame('2026-06-10T21:00:00Z', $day['last_synced_at']);
    }

    public function testResendingIsIdempotentAndNeverLowersTotals(): void
    {
        $device = $this->pairPhone();
        $this->upload($device, ['2026-06-10' => ['com.whatsapp' => 1000]]);
        $this->upload($device, ['2026-06-10' => ['com.whatsapp' => 1000]]);
        self::assertSame(1000, $this->day()['total_seconds']);

        $this->upload($device, ['2026-06-10' => ['com.whatsapp' => 400]]); // stale or reset phone
        self::assertSame(1000, $this->day()['total_seconds']);

        $this->upload($device, ['2026-06-10' => ['com.whatsapp' => 1500, 'com.android.chrome' => 300]]);
        self::assertSame(1800, $this->day()['total_seconds']);
    }

    public function testTotalsAddUpAcrossTwoPhones(): void
    {
        $a = $this->pairPhone('10.0.0.9');
        $b = $this->pairPhone('10.0.0.10');
        $this->upload($a, ['2026-06-10' => ['com.whatsapp' => 600]]);
        $this->upload($b, ['2026-06-10' => ['com.whatsapp' => 300, 'com.google.android.youtube' => 900]]);

        $day = $this->day();
        self::assertSame(1800, $day['total_seconds']);
        self::assertSame([['package' => 'com.google.android.youtube', 'label' => 'com.google.android.youtube', 'seconds' => 900], ['package' => 'com.whatsapp', 'label' => 'com.whatsapp', 'seconds' => 900]], $day['apps']);
    }

    public function testTodayFollowsTheChildsTimezone(): void
    {
        // 23:30 UTC on the 10th is 00:30 on the 11th in Lagos.
        Clock::freeze('2026-06-10 23:30:00');
        $device = $this->pairPhone();
        $res = $this->upload($device, ['2026-06-11' => ['com.whatsapp' => 120]]);
        self::assertSame(200, $res->status, $res->body);

        $day = $this->day();
        self::assertSame('2026-06-11', $day['date']);
        self::assertSame(120, $day['total_seconds']);
    }

    public function testRangeReportFillsGapsAndAveragesDaysWithData(): void
    {
        $device = $this->pairPhone();
        $this->upload($device, [
            '2026-06-08' => ['com.whatsapp' => 3600],
            '2026-06-10' => ['com.whatsapp' => 1800, 'com.zhiliaoapp.musically' => 600],
        ]);

        $res = $this->json('GET', "/api/v1/children/{$this->child}/usage/range?days=7", null, $this->parent);
        self::assertSame(200, $res->status, $res->body);
        $range = $res->decoded()['data'];

        self::assertSame('2026-06-04', $range['from']);
        self::assertSame('2026-06-10', $range['to']);
        self::assertCount(7, $range['days']);
        self::assertSame([0, 0, 0, 0, 3600, 0, 2400], array_column($range['days'], 'total_seconds'));
        self::assertSame(6000, $range['total_seconds']);
        self::assertSame(3000, $range['average_seconds']);
        self::assertSame(['com.whatsapp', 'com.zhiliaoapp.musically'], array_column($range['top_apps'], 'package'));

        self::assertSame(422, $this->json('GET', "/api/v1/children/{$this->child}/usage/range?days=9", null, $this->parent)->status);
    }

    public function testValidation(): void
    {
        $device = $this->pairPhone();

        $future = $this->upload($device, ['2026-06-12' => ['com.whatsapp' => 10]]);
        self::assertSame(422, $future->status);
        self::assertArrayHasKey('days.0.date', $future->decoded()['error']['details']['fields']);

        $bad = $this->upload($device, ['2026-06-10' => ['not a package' => 10, 'com.ok' => 90000]]);
        self::assertSame(422, $bad->status);
        self::assertSame(['days.0.apps.0.package', 'days.0.apps.1.seconds'], array_keys($bad->decoded()['error']['details']['fields']));

        self::assertSame(422, $this->upload($device, ['2026-06-10' => ['com.ok' => 1]], 'Mars/Olympus')->status);
        self::assertSame(422, $this->json('POST', '/api/v1/device/usage', ['timezone' => 'Africa/Lagos', 'days' => []], $device)->status);
        self::assertSame(0, $this->day()['total_seconds']);
    }

    public function testAppListMarksRemovedAndReinstalledApps(): void
    {
        $device = $this->pairPhone();
        $this->json('PUT', '/api/v1/device/apps', ['apps' => [['package' => 'com.a.one', 'label' => 'One'], ['package' => 'com.b.two', 'label' => 'Two']]], $device);

        $res = $this->json('PUT', '/api/v1/device/apps', ['apps' => [['package' => 'com.a.one', 'label' => 'One'], ['package' => 'com.c.three', 'label' => 'Three']]], $device);
        self::assertSame(['added' => 1, 'removed' => 1], $res->decoded()['data']);
        self::assertNotNull($this->db()->scalar("SELECT removed_at FROM installed_apps WHERE package = 'com.b.two'"));

        $back = $this->json('PUT', '/api/v1/device/apps', ['apps' => [['package' => 'com.b.two', 'label' => 'Two']]], $device);
        self::assertSame(['added' => 1, 'removed' => 2], $back->decoded()['data']);
        self::assertNull($this->db()->scalar("SELECT removed_at FROM installed_apps WHERE package = 'com.b.two'"));
    }

    public function testAccessRules(): void
    {
        $device = $this->pairPhone();
        $other = $this->registerParent('bayo@example.com');

        self::assertSame(404, $this->json('GET', "/api/v1/children/{$this->child}/usage/day", null, $other)->status);
        self::assertSame(401, $this->json('POST', '/api/v1/device/usage', ['timezone' => 'Africa/Lagos', 'days' => []], $this->parent)->status);
        self::assertSame(401, $this->json('GET', "/api/v1/children/{$this->child}/usage/day", null, $device)->status);
    }

    public function testRetentionDropsUsageOlderThan90Days(): void
    {
        $device = $this->pairPhone();
        $this->upload($device, ['2026-06-10' => ['com.whatsapp' => 100]]);

        Clock::freeze('2026-09-08 21:00:00'); // 90 days later: still kept
        $this->app->container->get(Maintenance::class)->run();
        self::assertSame(1, (int) $this->db()->scalar('SELECT COUNT(*) FROM usage_daily'));

        Clock::freeze('2026-09-09 21:00:00');
        $deleted = $this->app->container->get(Maintenance::class)->run();
        self::assertSame(2, $deleted['usage']);
        self::assertSame(0, (int) $this->db()->scalar('SELECT COUNT(*) FROM usage_app_daily'));
    }
}
