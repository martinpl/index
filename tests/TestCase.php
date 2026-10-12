<?php

namespace Tests;

use App\Models\Site;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Site::factory()->create(['domain' => 'localhost']);
    }

    /**
     * The app boots once per test, so make the requested site current like a real boot would.
     */
    protected function createTestRequest($symfonyRequest): Request
    {
        $request = parent::createTestRequest($symfonyRequest);

        Site::findForRequest($request)?->makeCurrent();

        return $request;
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
