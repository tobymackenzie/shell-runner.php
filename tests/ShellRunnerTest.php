<?php
namespace TJM\ShellRunner\Tests;
use PHPUnit\Framework\TestCase;
use TJM\ShellRunner\ShellRunner;

class ShellRunnerTest extends TestCase{
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
			$this->assertRegExp('/\sroot\s/', $results, "Listing root directory should contain string 'root'");
			$this->assertRegExp('/\setc\s/', $results, "Listing root directory should contain string 'etc'");
		}
	}
}
