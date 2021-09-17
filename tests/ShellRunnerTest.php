<?php
namespace TJM\ShellRunner\Tests;
use PHPUnit\Framework\TestCase;
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
					'host'=> 'tobymackenzie.com',
					'path'=> '/',
				),
				'expect'=> "ssh tobymackenzie.com 'cd '\''/'\'' && ls -l'",
			),
		) as $opts){
			$results = $shell->buildCommandString($opts['command']);
			$this->assertEquals($opts['expect'], trim($results));
		}
	}
	public function testListDirectory(){
		$shell = new ShellRunner();
		foreach(Array(
			'local'=> $shell(Array(
				'command'=> 'ls -l'
				,'interactive'=> false
				,'path'=> '/'
			))
			,'remote'=> $shell(Array(
				'command'=> 'ls -l'
				,'host'=> 'tobymackenzie.com'
				,'interactive'=> false
				,'path'=> '/'
			))
		) as $results){
			$this->assertMatchesRegularExpression('/\sroot\s/', $results, "Listing root directory should contain string 'root'");
			$this->assertMatchesRegularExpression('/\setc\s/', $results, "Listing root directory should contain string 'etc'");
		}
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
