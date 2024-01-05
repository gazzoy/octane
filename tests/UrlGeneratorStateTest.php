<?php

namespace Laravel\Octane\Tests;

use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\URL;

class UrlGeneratorStateTest extends TestCase
{
    public function test_url_generator_creates_correct_urls_across_subsequent_with_bind()
    {
        [$app, $worker, $client] = $this->createOctaneContext([
            Request::create('foo/bar', 'GET'),
            Request::create('foo/wibble', 'GET'),
        ]);

        $app['router']->bind('param', function (string $value)
        {
            URL::defaults(['param' => strtoupper($value)]);

            return $value;
        });

        $app['router']->get('foo/{param}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function () {
                return URL::getDefaultParameters()['param'];
            },
        ]);

        $worker->run();

        $this->assertEquals('BAR', $client->responses[0]->getContent());
        $this->assertEquals('WIBBLE', $client->responses[1]->getContent());
    }
}
