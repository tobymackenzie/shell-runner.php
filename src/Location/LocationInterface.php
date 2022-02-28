<?php
namespace TJM\ShellRunner\Location;

interface LocationInterface{
	public function __toString();
	public function set($keyOrArray, $value = null);
	public function getDestination();
	public function getHost();
	public function setHost($value = null);
	public function getPath();
	public function setPath($value = null);
	public function getProtocol();
	public function setProtocol($value);
}
