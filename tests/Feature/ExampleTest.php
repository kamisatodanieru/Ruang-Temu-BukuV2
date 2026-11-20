<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_guest_to_catalog(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('catalog.index'));
    }
}
