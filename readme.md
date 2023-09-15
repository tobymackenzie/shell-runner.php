Shell Runner
============

Run shell commands locally or via SSH.  Primarily for simplifying running commands on remote machines, either interactively or capturing the output.

Usage
-----

Install via composer.  Example usage:

``` php
<?php
use TJM\ShellRunner\ShellRunner;

$shell = new ShellRunner();

//--run `ls` locally, capturing output
$output = $shell->run(Array(
	'command'=> 'ls'
));

//--run `ls` remotely, capturing output
$output = $shell->run(Array(
	'command'=> 'ls'
	,'host'=> 'tobymackenzie.com'
));

//--SSH into remote machine interactively.  Will not capture output.  Interaction will require running PHP on command line, not in browser.
$shell->run(Array(
	'host'=> 'tobymackenzie.com'
	,'interactive'=> true
));
```

Other Options
-------------

- PHP built-ins:
	- [`shell_exec()`](https://www.php.net/manual/en/function.shell-exec.php)
	- [`passthru()`](https://secure.php.net/manual/en/function.passthru.php)
	- [`exec()`](https://www.php.net/manual/en/function.exec.php)
	- [`system()`](https://www.php.net/manual/en/function.system.php)
	- [`popen()`](https://www.php.net/manual/en/function.popen.php)
	- [`procopen()`](https://www.php.net/manual/en/function.proc-open.php)
- [Symfony Process Component](https://symfony.com/doc/current/components/process.html) (which this project uses in some cases)
- [PHP Secure Communications Library](https://github.com/phpseclib/phpseclib)
- [PHP Secure Shell2 extension](https://www.php.net/manual/en/book.ssh2.php)
