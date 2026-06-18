<?php

namespace Meraki\Core\Tests;

use Meraki\Core\Testing\MerakiTestCase;

class DoctorCommandTest extends MerakiTestCase
{
    public function test_doctor_command_returns_success(): void
    {
        $this->artisan('meraki:doctor')->assertSuccessful();
    }

    public function test_doctor_command_outputs_install_status(): void
    {
        $this->artisan('meraki:doctor')
             ->expectsOutputToContain('installed')
             ->assertSuccessful();
    }
}
