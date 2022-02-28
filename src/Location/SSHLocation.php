<?php
namespace TJM\ShellRunner\Location;
use Exception;

class SSHLocation extends Location{
	static protected $defaultProtocol = 'ssh';
	protected $host = '127.0.0.1';
	protected $port = 22;
	protected $user;
	public function __construct($stringOrOpts){
		if(is_string($stringOrOpts)){
			throw new Exception("SSHLocation::__construct: string argument not yet supported.");
		}elseif(is_array($stringOrOpts)){
			foreach($stringOrOpts as $key=> $value){
				$this->set($key, $value);
			}
		}
	}
	public function __toString(){
		$string = $this->getProtocol() . '://';
		if($this->getUser()){
			$string .= "{$this->getUser()}@";
		}
		$string .= $this->getHost();
		if($this->getPort() && $this->getPort() !== 22){
			$string .= ":{$this->getPort()}";
		}
		$path = $this->getPath();
		if(preg_match('/^\./', $path)){
			$path = '/' . $path;
		}
		$string .= $path;
		return $string;
	}
	public function getDestination(){
		if($this->destination){
			return $this->destination;
		}else{
			$string = '';
			if($this->getUser()){
				$string .= "{$this->getUser()}@";
			}
			$string .= $this->getHost();
			if($this->getPort() && $this->getPort() !== 22){
				$string .= ":{$this->getPort()}";
			}
			return $string;
		}
	}
	public function getPort(){
		return $this->port;
	}
	public function setPort($value){
		$this->port = $value;
	}
	public function getUser(){
		return $this->user;
	}
	public function setUser($value){
		$this->user = $value;
	}
}
