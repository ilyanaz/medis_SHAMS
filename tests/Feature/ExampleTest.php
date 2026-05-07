<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_panel_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/panel/login');
    }

    public function test_panel_login_page_loads(): void
    {
        $response = $this->get('/panel/login');

        $response->assertOk();
    }
}
