<?php
namespace TJM\ShellRunner\Location;

class Location implements LocationInterface{
	static protected $defaultProtocol;
	protected $destination;
	protected $host = 'localhost';
	protected $path = '';
	protected $protocol;
	public function __construct($protocolOrArray, $path = null){
		if(is_array($protocolOrArray)){
			$this->set($protocolOrArray);
		}elseif($protocolOrArray){
			$this->setProtocol($protocolOrArray);
		}
		if(!$this->getProtocol()){
			$this->setProtocol(static::$defaultProtocol);
		}
		if($path){
			$this->setPath($path);
		}
	}
	public function __toString(){
		return $this->getProtocol() . '://' . $this->getPath();
	}
	public function set($keyOrArray, $value = null){
		if(is_array($keyOrArray)){
			foreach($keyOrArray as $key=> $val){
				$this->set($key, $val);
			}
		}else{
			$this->{"set" . ucfirst($keyOrArray)}($value);
		}
	}
	public function getDestination(){
		return $this->destination;
	}
	public function setDestination($value = null){
		$this->destination = null;
	}
	public function getHost(){
		return $this->host;
	}
	public function setHost($value = null){
		$this->host = $value;
	}
	public function getPath(){
		return $this->path;
	}
	public function setPath($value = null){
		$this->path = $value;
	}
	public function getProtocol(){
		return $this->protocol ?: static::$defaultProtocol;
	}
	public function setProtocol($value){
		$this->protocol = $value;
	}
}
