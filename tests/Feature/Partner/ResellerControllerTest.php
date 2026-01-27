<?php

namespace Tests\Feature\Partner;

use Tests\TestCase;

class ResellerControllerTest extends TestCase
{
    public function test_access_denied_for_unauthorized()
    {
        // Without authentication cookie
        $request = $this->createRequest('GET', '/partner');
        $response = $this->app->handle($request);

        // Expect redirect to login or 403
        // Assuming AdminMiddleware redirects to /code
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_partner_dashboard_loads()
    {
        // Mock Session/Auth
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin'; // Or 'partner' if granular

        $request = $this->createRequest('GET', '/partner');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Partner', (string) $response->getBody());
        $this->assertStringContainsString('Hub', (string) $response->getBody());
    }

    public function test_create_client_endpoint()
    {
        $_SESSION['user_id'] = 1;

        $request = $this->createRequest('POST', '/partner/client/create')
            ->withParsedBody(['client_name' => 'New Corp']);

        $response = $this->app->handle($request);

        // Expect redirect back to dashboard
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/partner/dashboard', $response->getHeaderLine('Location'));
    }
}
