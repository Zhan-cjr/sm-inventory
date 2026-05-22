<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsappGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure there is always a clean organization
        Organization::truncate();
    }

    public function test_whatsapp_sends_via_mock_when_no_token()
    {
        Log::shouldReceive('info')
            ->once()
            ->with("=== MOCK WHATSAPP SEND (No Token/Config) ===");
        Log::shouldReceive('info')
            ->once()
            ->with("Gateway Type: fonnte");
        Log::shouldReceive('info')
            ->once()
            ->with("Target: 628123456789");
        Log::shouldReceive('info')
            ->once()
            ->with("Message: Halo Test");
        Log::shouldReceive('info')
            ->once()
            ->with("==========================================");

        // Put placeholder in env if it's set in real env, override it
        putenv('FONNTE_TOKEN=your_fonnte_token_here');

        $result = WhatsappService::sendMessage('08123456789', 'Halo Test');
        $this->assertTrue($result);
    }

    public function test_whatsapp_sends_via_fonnte_gateway()
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        Organization::create([
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Toserba Selamat Test',
            'wa_gateway_type' => 'fonnte',
            'wa_gateway_token' => 'fonnte-secret-token',
        ]);

        $result = WhatsappService::sendMessage('08123456789', 'Halo Fonnte');
        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send' &&
                $request->hasHeader('Authorization', 'fonnte-secret-token') &&
                $request['target'] === '628123456789' &&
                $request['message'] === 'Halo Fonnte';
        });
    }

    public function test_whatsapp_sends_via_local_gateway()
    {
        $localEndpoint = 'http://localhost:9999/send-message';
        Http::fake([
            $localEndpoint => Http::response(['status' => 'success'], 200),
        ]);

        Organization::create([
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Toserba Selamat Test',
            'wa_gateway_type' => 'local',
            'wa_gateway_token' => 'local-api-key',
            'wa_gateway_domain' => $localEndpoint,
        ]);

        $result = WhatsappService::sendMessage('08123456789', 'Halo Local Gateway');
        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($localEndpoint) {
            return $request->url() === $localEndpoint &&
                $request->hasHeader('Authorization', 'Bearer local-api-key') &&
                $request->hasHeader('x-api-key', 'local-api-key') &&
                $request['target'] === '628123456789' &&
                $request['number'] === '628123456789' &&
                $request['to'] === '628123456789' &&
                $request['phone'] === '628123456789' &&
                $request['message'] === 'Halo Local Gateway' &&
                $request['text'] === 'Halo Local Gateway' &&
                $request['api_key'] === 'local-api-key' &&
                $request['token'] === 'local-api-key';
        });
    }
}
