<?php

namespace functional;

use Stack\CallableHttpKernel;
use CHH\Stack\Honeypot;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;

function handler(callable $fn)
{
    return new CallableHttpKernel($fn);
}

class HoneypotTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $template;

    function setup()
    {
        $this->template = <<<EOF
<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <form method="post">
        </form>
    </body>
</html>
EOF;

        $this->app = handler(function (Request $request) {
            return new Response($this->template, 200);
        });
    }

    function testSpambotRequest()
    {
        $request = Request::create('/foo', 'POST', [
            'email' => 'test',
        ]);

        $response = (new Honeypot($this->app))->handle($request);

        $this->assertTrue($response->isOk());
        $this->assertEquals("", $response->getContent());
    }

    function testInsert()
    {
        $client = new Client(new Honeypot($this->app));
        $dom = $client->request('GET', '/foo');

        $this->assertCount(1, $dom->filter('div.phonetoy'));
        $this->assertCount(1, $dom->filter('input[name=email]'));
    }

    function testOnlyInsertWhenHeaderIsSet()
    {
        $doNotInsert = handler(function() {
            return new Response($this->template, 200);
        });

        $client = new Client(new Honeypot($doNotInsert, ['always_enabled' => false]));
        $dom = $client->request('GET', '/foo');

        $this->assertCount(0, $dom->filter('div.phonetoy'));
        $this->assertCount(0, $dom->filter('input[name=email]'));

        $insert = handler(function() {
            return new Response($this->template, 200, ['X-Honeypot' => 'enabled']);
        });

        $client = new Client(new Honeypot($insert, ['always_enabled' => false]));
        $dom = $client->request('GET', '/foo');

        $this->assertCount(1, $dom->filter('div.phonetoy'));
        $this->assertCount(1, $dom->filter('input[name=email]'));
    }

    function testConfigureClassName()
    {
        $client = new Client(new Honeypot($this->app, ['class_name' => 'honeypot']));
        $dom = $client->request('GET', '/foo');

        $this->assertCount(1, $dom->filter('div.honeypot'));
    }

    function testConfigureInputName()
    {
        $client = new Client(new Honeypot($this->app, ['input_name' => 'foobar']));
        $dom = $client->request('GET', '/foo');

        $this->assertCount(1, $dom->filter('input[name=foobar]'));
    }
}
