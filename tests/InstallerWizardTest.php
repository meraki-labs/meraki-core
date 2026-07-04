<?php

namespace Meraki\Core\Tests;

use Meraki\Core\Installer\EnvironmentChecker;
use Meraki\Core\Installer\WizardSession;
use Meraki\Core\Installer\State\MerakiState;
use Meraki\Core\Testing\MerakiTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class InstallerWizardTest extends MerakiTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('session.driver', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
    }
    public function test_welcome_route_returns_200(): void
    {
        $response = $this->get('/meraki/install');
        $response->assertStatus(200);
    }

    public function test_environment_route_returns_200(): void
    {
        $response = $this->get('/meraki/install/environment');
        $response->assertStatus(200);
    }

    public function test_environment_checker_returns_required_keys(): void
    {
        $checker = app(EnvironmentChecker::class);
        $checks  = $checker->run();

        $this->assertNotEmpty($checks);
        foreach ($checks as $check) {
            $this->assertArrayHasKey('key', $check);
            $this->assertArrayHasKey('label', $check);
            $this->assertArrayHasKey('pass', $check);
            $this->assertArrayHasKey('detail', $check);
            $this->assertIsBool($check['pass']);
        }
    }

    public function test_post_environment_marks_step_and_redirects(): void
    {
        $response = $this->post('/meraki/install/environment');
        $response->assertRedirect('/meraki/install/database');
    }

    public function test_database_route_returns_200(): void
    {
        $response = $this->get('/meraki/install/database');
        $response->assertStatus(200);
    }

    public function test_admin_route_skips_when_meraki_auth_not_registered(): void
    {
        $response = $this->get('/meraki/install/admin');
        $response->assertRedirect('/meraki/install/plugins');
    }

    public function test_plugins_route_returns_200(): void
    {
        $response = $this->get('/meraki/install/plugins');
        $response->assertStatus(200);
    }

    public function test_post_plugins_marks_step_and_redirects(): void
    {
        $response = $this->post('/meraki/install/plugins', ['plugins' => []]);
        $response->assertRedirect('/meraki/install/complete');
    }

    public function test_wizard_session_set_get(): void
    {
        $session = app(WizardSession::class);

        $session->set('test_key', 'test_value');
        $this->assertSame('test_value', $session->get('test_key'));
    }

    public function test_wizard_session_mark_and_check_step(): void
    {
        $session = app(WizardSession::class);

        $session->markStep('environment');
        $this->assertTrue($session->stepCompleted('environment'));
        $this->assertFalse($session->stepCompleted('database'));
    }

    public function test_wizard_session_clear(): void
    {
        $session = app(WizardSession::class);

        $session->set('key', 'value');
        $session->clear();

        $this->assertNull($session->get('key'));
    }

    public function test_complete_post_redirects_after_install(): void
    {
        $response = $this->post('/meraki/install/complete');
        $response->assertRedirect();
    }

    public function test_installer_routes_are_registered(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter()
            ->values()
            ->toArray();

        $this->assertContains('meraki.install.welcome', $routes);
        $this->assertContains('meraki.install.environment', $routes);
        $this->assertContains('meraki.install.database', $routes);
        $this->assertContains('meraki.install.admin', $routes);
        $this->assertContains('meraki.install.plugins', $routes);
        $this->assertContains('meraki.install.complete', $routes);
    }

    public function test_redirect_if_installed_middleware_is_aliased(): void
    {
        $router     = $this->app->make(\Illuminate\Routing\Router::class);
        $middleware = $router->getMiddleware();

        $this->assertArrayHasKey('meraki.installed', $middleware);
        $this->assertSame(
            \Meraki\Core\Http\Middleware\RedirectIfInstalled::class,
            $middleware['meraki.installed']
        );
    }
}
