<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_returns_successfully(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('TNF Today', false);
    }
}
