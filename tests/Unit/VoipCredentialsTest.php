<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\VoipService;
use App\Support\VoipCredentials;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoipCredentialsTest extends TestCase
{
    private string $credentialsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentialsPath = sys_get_temp_dir().'/crm-v3-voip-credentials-'.bin2hex(random_bytes(6)).'.json';
        config(['voip.credentials_path' => $this->credentialsPath]);
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsPath);

        parent::tearDown();
    }

    public function test_credentials_are_saved_privately_and_loaded_by_the_service(): void
    {
        VoipCredentials::update([
            'api_url' => 'http://voip.test/api',
            'client_id' => 'crm-client',
            'client_secret' => 'secret-value',
        ]);

        $this->assertSame([
            'api_url' => 'http://voip.test/api',
            'client_id' => 'crm-client',
            'client_secret' => 'secret-value',
        ], VoipCredentials::all());
        $this->assertSame(0600, fileperms($this->credentialsPath) & 0777);
        $this->assertTrue((new VoipService)->isConfigured());
    }

    public function test_credentials_can_be_disconnected_without_losing_the_server_url(): void
    {
        VoipCredentials::update([
            'api_url' => 'http://voip.test/api',
            'client_id' => 'crm-client',
            'client_secret' => 'secret-value',
        ]);
        VoipCredentials::update([
            'client_id' => '',
            'client_secret' => '',
        ]);

        $this->assertSame('http://voip.test/api', VoipCredentials::all()['api_url']);
        $this->assertFalse((new VoipService)->isConfigured());
    }

    public function test_customer_history_translates_limit_to_the_server_pagination_parameter(): void
    {
        Http::fake([
            'http://voip.test/api/calls*' => Http::response(['data' => [], 'meta' => ['per_page' => 2]]),
        ]);

        (new VoipService('http://voip.test/api', 'secret-value'))
            ->getCustomerCallHistory('01000000000', ['limit' => 2]);

        Http::assertSent(static fn ($request): bool => $request['phone'] === '01000000000'
            && $request['per_page'] === 2
            && ! isset($request['limit']));
    }
}
