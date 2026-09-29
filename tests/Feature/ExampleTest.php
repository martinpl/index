<?php

use App\Models\Site;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

pest()->use(LazilyRefreshDatabase::class);

test('the application returns a successful response', function () {
    Site::factory()->create(['domain' => 'localhost']);

    $response = $this->get('/');

    $response->assertStatus(200);
});
