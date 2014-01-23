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

    protected $defaultOptions = [
        'class_name' => 'phonetoy',
        'input_name' => 'email',
        'input_value' => '',
        'label' => "Don't fill in this field",
        'always_enabled' => true
    ];

    function __construct(HttpKernelInterface $app, array $options = [])
    {
        $this->app = $app;

        $options = array_merge($this->defaultOptions, $options);

        $this->className = $options['class_name'];
        $this->inputName = $options['input_name'];
        $this->inputValue = $options['input_value'];
        $this->label = $options['label'];
        $this->alwaysEnabled = $options['always_enabled'];
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
div.{$this->className} {
    display: none;
}
</style>
HTML
        , $body);

        return $body;
    }
}
