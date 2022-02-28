<?php
namespace TJM\ShellRunner\Tests;
use PHPUnit\Framework\TestCase;
use TJM\ShellRunner\Location\FileLocation;
use TJM\ShellRunner\Location\SSHLocation;
use TJM\ShellRunner\ShellRunner;

class ShellRunnerTest extends TestCase{
	public function testBuildingCommandStrings(){
		$shell = new ShellRunner();
		foreach(array(
			array(
				'command'=> array(
					'command'=> 'ls -l',
					'path'=> '/',
				),
				'expect'=> "bash -c 'cd '\''/'\'' && ls -l'",
			),
			array(
				'command'=> array(
					'command'=> 'ls -l',
					'sudo'=> true,
				),
				'expect'=> "sudo bash -c 'ls -l'",
			),
			array(
				'command'=> array(
					'command'=> 'ls -l',
					'sudo'=> 'fooser',
				),
				'expect'=> "sudo -u fooser bash -c 'ls -l'",
			),
			array(
				'command'=> array(
					'command'=> 'ls -l',
					'host'=> 'tobymackenzie.com',
					'path'=> '/',
				),
				'expect'=> "ssh tobymackenzie.com 'cd '\''/'\'' && ls -l'",
			),
			array(
				'command'=> array(
					'command'=> 'ls -l',
					'forwardAgent'=> true,
					'host'=> 'tobymackenzie.com',
				),
				'expect'=> "ssh tobymackenzie.com -o ForwardAgent=\"yes\" 'ls -l'",
			),
			array(
				'command'=> array(
					'command'=> 'ls -l',
					'forwardAgent'=> true,
					'host'=> 'tobymackenzie.com',
					'sudo'=> true,
				),
				'expect'=> "ssh tobymackenzie.com -o ForwardAgent=\"yes\" 'sudo --preserve-env=SSH_AUTH_SOCK ls -l'",
			),
			array(
				'command'=> array(
					'command'=> 'ls -l',
					'forwardAgent'=> true,
					'host'=> 'tobymackenzie.com',
					'sudo'=> 'fooser',
				),
				'expect'=> "ssh tobymackenzie.com -o ForwardAgent=\"yes\" 'sudo -u fooser --preserve-env=SSH_AUTH_SOCK ls -l'",
			),
			array(
				'command'=> array(
					'command'=> 'ls -l',
					'forwardAgent'=> true,
					'sudo'=> 'fooser',
				),
				'location'=> new SSHLocation(array(
					'host'=> 'tobymackenzie.com',
				)),
				'expect'=> "ssh tobymackenzie.com -o ForwardAgent=\"yes\" 'sudo -u fooser --preserve-env=SSH_AUTH_SOCK ls -l'",
			),
		) as $opts){
			$results = $shell->buildCommandString($opts['command'], $opts['location'] ?? null);
			$this->assertEquals($opts['expect'], trim($results));
		}
	}
	public function testListDirectory(){
		$shell = new ShellRunner();
		foreach(Array(
			'localOld'=> $shell(Array(
				'command'=> 'ls -l'
				,'interactive'=> false
				,'path'=> '/'
			)),
			'local'=> $shell(
				array(
					'command'=> 'ls -l'
					,'interactive'=> false
				),
				new FileLocation('/'),
			),
			'remoteOld'=> $shell(Array(
				'command'=> 'ls -l'
				,'host'=> 'tobymackenzie.com'
				,'interactive'=> false
				,'path'=> '/'
			)),
			'remote'=> $shell(
				array(
					'command'=> 'ls -l',
					'interactive'=> false,
				), new SSHLocation(array(
					'host'=> 'tobymackenzie.com',
					'path'=> '/',
				)
			)),
		) as $results){
			$this->assertMatchesRegularExpression('/\sroot\s/', $results, "Listing root directory should contain string 'root'");
			$this->assertMatchesRegularExpression('/\setc\s/', $results, "Listing root directory should contain string 'etc'");
		}
	}
	public function testMultiLocation(){
		$shell = new ShellRunner();
		$results = $shell(
			array(
				'command'=> 'ls -l',
				'interactive'=> false,
			), array(
				new FileLocation('/'),
				new SSHLocation(array(
					'host'=> 'tobymackenzie.com',
					'path'=> '/',
				)),
			)
		);
		$this->assertMatchesRegularExpression('/\setc\s/', $results, "Listing root directory should contain string 'etc'");
		$this->assertMatchesRegularExpression('/\s?file:\/\/\/\s?/', $results, "Multi-location run should list file location.");
		$this->assertMatchesRegularExpression('/\s?ssh:\/\/tobymackenzie.com\s?/', $results, "Multi-location run should list ssh location.");
	}
	public function testSudoWho(){
		$shell = new ShellRunner();
		// $me = trim(`whoami`);
		foreach(Array(
			// array(
			// 	'host'=> 'localhost',
			// 	'who'=> $me,
			// ),
			array(
				'host'=> 'tobymackenzie.com',
				'who'=> 'root',
			),
		) as $opts){
			$results = $shell(Array(
				'command'=> 'whoami',
				'forwardAgent'=> true,
				'host'=> $opts['host'],
				'interactive'=> false,
				'sudo'=> $opts['who'],
			));
			$this->assertEquals($opts['who'], trim($results), "Running as sudo should result in user being specified user");
		}
	}
}
