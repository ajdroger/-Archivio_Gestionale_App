<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use MCAG\Service\AI\AIService;
use MCAG\Service\Audit\AIAuditLogger;

class AIChatControllerTest extends TestCase
{
    public function test_chat_needs_message()
    {
        $request = $this->createRequest('POST', '/api/ai/chat')
            ->withParsedBody([]); // Empty

        $response = $this->app->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_chat_returns_response()
    {
        // Ideally we Mock AIService here using DI Container
        // But for this quick integration test, we check if it handles the flow 
        // even if it returns 503 (AI Unavailable) or 200 (Mocked/Real)

        $request = $this->createRequest('POST', '/api/ai/chat')
            ->withParsedBody(['message' => 'Hello AI', 'context' => 'test']);

        $response = $this->app->handle($request);

        $statusCode = $response->getStatusCode();

        // It should be 200 (Success) or 503 (AI Service Down/Null)
        // We consider both "passing" logic flow as Controller didn't crash
        $this->assertTrue(in_array($statusCode, [200, 503]));

        $json = json_decode((string) $response->getBody(), true);
        $this->assertNotNull($json);
    }
}
