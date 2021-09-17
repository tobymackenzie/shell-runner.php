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

		$shell = !empty($opts['shell']) ? $opts['shell'] : 'bash';

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

		if(isset($opts['path']) && $opts['path'] && $opts['path'] !== '.'){
			$runCommand = "cd " . escapeshellarg($opts['path']) . ($runCommand ? " && {$runCommand}" : ' && ' . $shell . ' --login');
		}
		$shellOptions = isset($opts['shellOpts']) ? $opts['shellOpts'] : array();
		if($host === 'localhost'){
			if($interactive && !in_array('-i', $shellOptions)){
				$shellOptions[] = '-i';
			}
			if($runCommand && !in_array('-c', $shellOptions)){
				$shellOptions[] = '-c';
			}
			$command = $shell;
			if(!empty($opts['sudo'])){
				$command = 'sudo ' . ($opts['sudo'] === true ? '' : " -u {$opts['sudo']}") . ' ' . escapeshellarg($command);
			}
		}else{
			if($runCommand && $interactive && !in_array('-t', $shellOptions)){
				$shellOptions[] = '-t';
				$runCommand = $shell . ' -i -c ' . escapeshellarg($runCommand);
			}
			if(isset($opts['forwardAgent']) && $opts['forwardAgent'] && !in_array('-o ForwardAgent="yes"', $shellOptions)){
				$shellOptions[] = '-o ForwardAgent="yes"';
			}
			if(!empty($opts['sudo'])){
				$tmp = $runCommand;
				$runCommand = 'sudo ' . ($opts['sudo'] === true ? '' : " -u {$opts['sudo']}") . ' ';
				if(!empty($opts['forwardAgent'])){
					$runCommand .= '--preserve-env=SSH_AUTH_SOCK ';
				}
				$runCommand .= $tmp;
				unset($tmp);
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

	//-! this is just a temporary solution. eventually this functionality will be merged into `::run()` with a better interface and we won't have a need for this function anymore
	public function runAll($opts = Array()){
		//--determine locations to run command at
		if(isset($opts['locations'])){
			$locations = $opts['locations'];
		}elseif(isset($opts['host']) || isset($opts['path'])){
			$locations = Array(
				Array(
					'host'=> (isset($opts['host']) ? $opts['host'] : null)
					,'path'=> (isset($opts['path']) ? $opts['path'] : null)
				)
			);
		}

		$results = Array();
		foreach($locations as $key=> $location){
			if(is_array($location)){
				$host = (isset($location['host']) ? $location['host'] : null);
				$path = (isset($location['path']) ? $location['path'] : null);
			}else{
				$host = is_numeric($key) ? 'localhost' : $key;
				$path = $location;
			}
			if($this->hasHost($host)){
				$host = $this->getHost($host);
			}
			if(!$host){
				$host = 'localhost';
			}
			if(!$path){
				$path = '.';
			}
			$opts['host'] = $host;
			$opts['path'] = $path;
			if($host === 'localhost'){
				$pathKey = "file://{$path}";
			}else{
				$pathKey = "ssh://{$host}{$path}";
			}
			$results[$pathKey] = $this->run($opts);
		}
		return $results;
	}
	protected function convertCommandsArrayToString($commands){
		return implode(' && ', $commands);
	}
}
