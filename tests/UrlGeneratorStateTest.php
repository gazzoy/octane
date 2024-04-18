<?php

namespace Laravel\Octane\Tests;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Orchestra\Testbench\Http\Middleware\RedirectIfAuthenticated;

class UrlGeneratorStateTest extends TestCase
{
    public function test_url_generator_creates_correct_urls_across_subsequent_with_bind()
    {
        [$app, $worker, $client] = $this->createOctaneContext([
            Request::create('foo/bar/home', 'GET'),
            Request::create('foo/baz/home', 'GET'),
        ]);

        $app['router']->bind('param', function (string $value) use ($app)
        {
            $app['url']->defaults(['param' => strtoupper($value)]);

            return $value;
        });

        $app['router']->get('/foo/{param}/login', function (Application $app)
        {
            return $app['url']->getDefaultParameters()['param'];
        })->middleware([SubstituteBindings::class, RedirectIfAuthenticated::class])->name('login');

        $app['router']->get('/foo/{param}/home', function (Application $app)
        {
            return $app['url']->getDefaultParameters()['param'];
        })->middleware([SubstituteBindings::class, Authenticate::class])->name('home');

        $worker->run();

        $this->assertEquals('BAR', $client->responses[0]->getContent());
        $this->assertEquals('BAZ', $client->responses[1]->getContent());
    }
}
