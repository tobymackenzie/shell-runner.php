<?php
namespace TJM\ShellRunner;
use Exception;

class ShellRunner{
	protected $hosts = array();

	public function __invoke($opts = Array()){
		return $this->run($opts);
	}

	//==hosts
	public function addHost($alias, $value){
		$this->hosts[$alias] = $value;
		return $this;
	}
	public function getHost($alias){
		return $this->hosts[$alias];
	}
	public function hasHost($alias){
		return isset($this->hosts[$alias]);
	}

	//==run
	public function run($opts = Array()){
		if(is_string($opts)){
			$opts = Array('command'=> $opts);
		}

		//--determine command to run, if any
		$runCommand = isset($opts['command']) ? $opts['command'] : null;
		if(is_array($runCommand)){
			$runCommand = $this->convertCommandsArrayToString($runCommand);
		}

		//--determine host to run command on
		$host = isset($opts['host']) ? $opts['host'] : null;
		if($this->hasHost($host)){
			$host = $this->getHost($host);
		}
		if(!$host){
			$host = 'localhost';
		}

		//--interactive means we can interact with the shell process, but can't capture it
		$interactive = isset($opts['interactive']) ? $opts['interactive'] : false;

		if(isset($opts['path']) && $opts['path']){
			$runCommand = "cd " . escapeshellarg($opts['path']) . ($runCommand ? " && {$runCommand}" : ' && $SHELL --login');
		}
		$shellOptions = isset($opts['shellOpts']) ? $opts['shellOpts'] : array();
		if($host === 'localhost'){
			if($interactive && !in_array('-i', $shellOptions)){
				$shellOptions[] = '-i';
			}
			if($runCommand && !in_array('-c', $shellOptions)){
				$shellOptions[] = '-c';
			}
			$command = '$SHELL';
		}else{
			if($runCommand && $interactive && !in_array('-t', $shellOptions)){
				$shellOptions[] = '-t';
				$runCommand = '$SHELL -i -c ' . escapeshellarg($runCommand);
			}
			if(isset($opts['forwardAgent']) && $opts['forwardAgent'] && !in_array('-o ForwardAgent="yes"', $shellOptions)){
				$shellOptions[] = '-o ForwardAgent="yes"';
			}
			$command = "ssh {$host}";
		}
		if($runCommand){
			$command .= ' ' . implode(' ', $shellOptions) . ' ' . escapeshellarg($runCommand);
		}
		if($interactive){
			passthru($command, $exitCode);
		}else{
			exec($command, $result, $exitCode);
		}
		if($exitCode){
			throw new Exception("Error {$exitCode} running command `{$command}`", $exitCode);
		}
		return isset($result) && $result ? implode("\n", $result) : $exitCode;
	}
	protected function convertCommandsArrayToString($commands){
		return implode(' && ', $commands);
	}
}
