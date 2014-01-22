<?php

namespace CHH\Stack;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Honeypot implements HttpKernelInterface
{
    protected $app;
    protected $className;
    protected $inputName;
    protected $inputValue;

    function __construct(HttpKernelInterface $app, array $options = [])
    {
        $this->app = $app;

        $this->className = isset($options['class_name']) ? $options['class_name'] : 'phonetoy';
        $this->inputName = isset($options['input_name']) ? $options['input_name'] : 'email';
        $this->inputValue = isset($options['input_value']) ? $options['input_value'] : '';
        $this->label = isset($options['label']) ? $options['label'] : "Don't fill in this field";
        $this->alwaysEnabled = isset($options['always_enabled']) ? $options['always_enabled'] : true;
    }

    function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if ($this->isSpamBotRequest($request)) {
            return new Response("");
        }

        $response = $this->app->handle($request, $type, $catch);

        if ($this->alwaysEnabled or $response->headers->get('X-Honeypot') === 'enabled') {
            $response->setContent($this->insertHoneypot($response->getContent()));
        }

        return $response;
    }

    function isSpamBotRequest(Request $request)
    {
        return
            $request->request->count() > 0
            && $request->request->get($this->inputName) !== $this->inputValue;
    }

    function insertHoneypot($body)
    {
        $body = preg_replace('/(<form.*>)+/', <<<HTML
\$1
<div class="{$this->className}">
    <label>
        {$this->label}
        <input type="text" name="{$this->inputName}" value="{$this->inputValue}" />
    </label>
</div>
HTML
        , $body);

        $body = preg_replace('/(<head.*>)/', <<<HTML
\$1
<style type="text/css">
.{$this->className} {
    display: none;
}
</style>
HTML
        , $body);

        return $body;
    }
}
