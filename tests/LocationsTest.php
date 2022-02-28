<?php
namespace TJM\ShellRunner\Tests;
use PHPUnit\Framework\TestCase;
use TJM\ShellRunner\Location;

class LocationsTest extends TestCase{
	public function testLocationAsString(){
		foreach(Array(
			Array(new Location\FileLocation(), 'file://.') //--this is improper format, but we want to support it, so ?
			,Array(new Location\FileLocation('.'), 'file://.') //--this is improper format, but we want to support it, so ?
			,Array(new Location\FileLocation('./foo/bar'), 'file://./foo/bar') //--this is improper format, but we want to support it, so ?
			,Array(new Location\FileLocation('/'), 'file:///')
			,Array(new Location\FileLocation('/etc'), 'file:///etc')
			,Array(new Location\SSHLocation(Array('host'=> 'tobymackenzie.com')), 'ssh://tobymackenzie.com')
			,Array(new Location\SSHLocation(Array('host'=> 'tobymackenzie.com', 'path'=> '/')), 'ssh://tobymackenzie.com/')
			,Array(new Location\SSHLocation(Array('port'=> 123)), 'ssh://127.0.0.1:123')
			,Array(new Location\SSHLocation(Array('path'=> '/etc', 'port'=> 123)), 'ssh://127.0.0.1:123/etc')
			,Array(new Location\SSHLocation(Array('user'=> 'toby')), 'ssh://toby@127.0.0.1')
		) as $location){
			$this->assertEquals($location[1], (string) $location[0]);
		}
	}
}
