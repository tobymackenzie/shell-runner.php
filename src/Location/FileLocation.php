<?php
namespace TJM\ShellRunner\Location;

class FileLocation extends Location{
	static protected $defaultProtocol = 'file';
	protected $path = '.';
	public function __construct($path = null){
		if($path){
			$this->setPath($path);
		}
	}
}
