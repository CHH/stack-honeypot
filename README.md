# Stack\Honeypot

A port of [Rack::Honeypot](https://github.com/sunlightlabs/rack-honeypot) to Stack for PHP.

## Install

    composer require stack/honey-pot:~1.0

## Usage

Wrap your HttpKernelInterface app in an instance of `CHH\Stack\Honeypot` or add it to your middleware stack.

### From [Rack::Honeypot](https://github.com/sunlightlabs/rack-honeypot):

This middleware acts as a spam trap. It inserts, into every outputted `<form>`, a text field that a spambot will really want to fill in, but is actually not used by the app. The field is hidden to humans via CSS, and includes a warning label for screenreading software.

In the `<body>`:

    <form>
      <div class='phonetoy'>
        <label for='email'>Don't fill in this field</label>
        <input type='text' name='email' value=''/>
      </div>
    [...]

In the `<head>`:
  
    <style type='text/css' media='all'>
      div.phonetoy {
        display:none;
      }
    </style>
  
Then, for incoming requests, the middleware will check if the text field has been set to an unexpected value. If it has, that means a spambot has altered the field, and the spambot is booted to a dead end blank page.

There are a few options you can pass in:
  
  * `class_name` is the class assigned to the parent div of the honeypot. Defaults to "phonetoy", an anagram of honeypot.
  * `label` is the warning label displayed to those with CSS disabled. Defaults to "Don't fill in this field".
  * `input_name` is the name of the form field. Ensure that this is tempting to a spambot if you modify it. Defaults to "email".
  * `input_value` is the value of the form field that would only be modified by a spambot. Defaults to blank.

## License

See [LICENSE.txt](LICENSE.txt).
