<?php

namespace Meraki\Core\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Meraki\Core\Installer\EnvironmentChecker;
use Meraki\Core\Installer\MerakiInstaller;
use Meraki\Core\Installer\WizardSession;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Plugins\PluginManager;
use Illuminate\Support\Facades\Artisan;

final class InstallerController extends Controller
{
    public function __construct(
        private readonly MerakiInstaller $installer,
        private readonly PluginManager $pluginManager,
        private readonly PackageRegistry $packageRegistry,
        private readonly WizardSession $wizard,
        private readonly EnvironmentChecker $checker,
    ) {}

    public function welcome(): View
    {
        // @phpstan-ignore argument.type
        return view('meraki::installer.welcome');
    }

    public function environment(): View
    {
        $checks = $this->checker->run();

        // @phpstan-ignore argument.type
        return view('meraki::installer.environment', compact('checks'));
    }

    public function postEnvironment(Request $request): RedirectResponse
    {
        $this->wizard->set('environment_passed', true);
        $this->wizard->markStep('environment');

        return redirect()->route('meraki.install.database');
    }

    public function database(): View
    {
        $dbCheck = $this->checker->dbConnectionPass();

        // @phpstan-ignore argument.type
        return view('meraki::installer.database', compact('dbCheck'));
    }

    public function postDatabase(Request $request): View|RedirectResponse
    {
        if (!$this->checker->dbConnectionPass()) {
            // @phpstan-ignore argument.type
            return view('meraki::installer.database', [
                'dbCheck' => false,
                'error'   => 'Không thể kết nối database. Vui lòng kiểm tra cấu hình .env và thử lại.',
            ]);
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            // @phpstan-ignore argument.type
            return view('meraki::installer.database', [
                'dbCheck' => true,
                'error'   => 'Migration thất bại: ' . $e->getMessage(),
            ]);
        }

        $this->wizard->set('database_migrated', true);
        $this->wizard->markStep('database');

        if ($this->shouldShowAdminStep()) {
            return redirect()->route('meraki.install.admin');
        }

        return redirect()->route('meraki.install.plugins');
    }

    public function admin(): View|RedirectResponse
    {
        if (!$this->shouldShowAdminStep()) {
            return redirect()->route('meraki.install.plugins');
        }

        // @phpstan-ignore argument.type
        return view('meraki::installer.admin');
    }

    public function postAdmin(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->wizard->set('admin', [
            'name'          => $request->input('name'),
            'email'         => $request->input('email'),
            'password_hash' => bcrypt($request->input('password')),
        ]);
        $this->wizard->markStep('admin');

        return redirect()->route('meraki.install.plugins');
    }

    public function plugins(): View
    {
        $plugins = $this->pluginManager->discover();

        // @phpstan-ignore argument.type
        return view('meraki::installer.plugins', compact('plugins'));
    }

    public function postPlugins(Request $request): RedirectResponse
    {
        $selected = $request->input('plugins', []);
        $this->wizard->set('plugins', $selected);
        $this->wizard->markStep('plugins');

        return redirect()->route('meraki.install.complete');
    }

    public function complete(Request $request): RedirectResponse
    {
        $context = $this->installer->install();

        $adminData = $this->wizard->get('admin');
        if ($adminData) {
            $context->set('admin', $adminData);
        }

        $selectedPlugins = $this->wizard->get('plugins', []);
        $context->set('selected_plugins', $selectedPlugins);

        $this->wizard->clear();

        $redirect = config('meraki.installer.redirect', '/');

        return redirect($redirect);
    }

    private function shouldShowAdminStep(): bool
    {
        return $this->packageRegistry->has('meraki-auth')
            && class_exists(\Meraki\Core\Installer\Steps\CreateAdminStep::class);
    }
}
