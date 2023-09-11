<?php
namespace TJM\ShellRunner;
use Exception;
use Symfony\Component\Process\Process;
use TJM\ShellRunner\Location\LocationInterface;
use TJM\ShellRunner\Location\SSHLocation;

class ShellRunner{
	protected $hosts = array();

	public function __invoke($opts = Array(), $locations = null){
		return $this->run($opts, $locations);
	}

	//==hosts
	public function addHost($alias, $value){
		$this->hosts[$alias] = $value;
		return $this;
	}
	public function getHost($alias){
		return $this->hosts[(string) $alias];
	}
	public function hasHost($alias){
		return isset($this->hosts[(string) $alias]);
	}

	public function buildCommandString($opts = array(), $location = 'localhost'){
		if(is_string($opts)){
			$opts = Array('command'=> $opts);
		}

		$shell = !empty($opts['shell']) ? $opts['shell'] : 'bash';

		//--determine command to run, if any
		$runCommand = isset($opts['command']) ? $opts['command'] : null;
		if(is_array($runCommand)){
			$runCommand = $this->convertCommandsArrayToString($runCommand);
		}

		//--determine location to run command
		if($location instanceof LocationInterface){
			$host = $location->getHost();
			$path = $location->getPath();
			$protocol = $location->getProtocol();
		}else{
			$host = isset($opts['host']) ? $opts['host'] : null;
			$path = isset($opts['path']) && $opts['path'] ? $opts['path'] : null;
			if(!$path && !$host){
				if($location && is_dir($location)){
					$path = $location;
				}else{
					$host = $location;
				}
			}
			if($host === 'localhost' || !$host){
				$protocol = 'file';
			}else{
				$protocol = 'ssh';
			}
		}
		if($this->hasHost($host)){
			$host = $this->getHost($host);
		}
		if(!$host){
			$host = 'localhost';
		}
		if(!isset($path)){
			$path = null;
		}

		//--interactive means we can interact with the shell process, but can't capture it
		$interactive = isset($opts['interactive']) ? $opts['interactive'] : false;

		if($path && $path !== '.'){
			$runCommand = "cd " . escapeshellarg($path) . ($runCommand ? " && {$runCommand}" : ' && ' . $shell . ' --login');
		}
		$shellOptions = isset($opts['shellOpts']) ? $opts['shellOpts'] : array();
		if($protocol === 'file'){
			if($interactive && !in_array('-i', $shellOptions)){
				$shellOptions[] = '-i';
			}
			if($runCommand && !in_array('-c', $shellOptions)){
				$shellOptions[] = '-c';
			}
			$command = $shell;
			if(!empty($opts['sudo'])){
				$command = 'sudo ' . ($opts['sudo'] === true ? '' : "-u {$opts['sudo']} ") . $command;
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
				$runCommand = 'sudo ' . ($opts['sudo'] === true ? '' : "-u {$opts['sudo']} ");
				if(!empty($opts['forwardAgent'])){
					$runCommand .= '--preserve-env=SSH_AUTH_SOCK ';
				}
				$runCommand .= $tmp;
				unset($tmp);
			}
			if($host instanceof SSHLocation){
				$host = $host->getDestination();
			}
			$command = "ssh {$host}";
		}
		if($runCommand){
			$command .= ' ';
			if($shellOptions){
				$command .= implode(' ', $shellOptions) . ' ';
			}
			$command .= escapeshellarg($runCommand);
		}
		return $command;
	}

	//==run
	public function run($opts = Array(), $locations = null){
		if(is_string($opts)){
			$opts = Array('command'=> $opts);
		}
		if(!is_array($locations)){
			$locations = array($locations);
		}
		$results = array();
		foreach($locations as $location){
			$command = $this->buildCommandString($opts, $location);
			$interactive = isset($opts['interactive']) ? $opts['interactive'] : false;
			if($interactive){
				//-# process better handles some interactive commands like `less`
				if(class_exists(Process::class) && (is_string($location) || (is_object($location) && $location->getProtocol() === 'file'))){
					$p = Process::fromShellCommandline($command);
					$p->setTty(true);
					if(strpos('/(nano|vi) /i', $command) === false){
						$p->mustRun();
					}else{
						$p->mustRun(function($t, $buffer){
							echo $buffer;
						});
					}
					$exitCode = $p->getExitCode();
				}else{
					passthru($command, $exitCode);
				}
			}else{
				exec($command, $result, $exitCode);
				$results[(string) $location] = $result;
			}
			if($exitCode){
				$message = "Error {$exitCode} running command\n\ncommand:\n\n```\n{$command}\n```";
				if(isset($result)){
					$message .= "\n\nresult:\n\n```\n" . implode("\n", $result) . "\n```";
				}
				throw new Exception($message, $exitCode);
			}
		}
		if($results){
			$return = '';
			if(count($results) > 1){
				foreach($results as $key=> $result){
					$return .= "{$key}\n-----\n" . implode("\n", $result) . "\n";
				}
			}else{
				$return = implode("\n", array_pop($results));
			}
			return $return;
		}else{
			return $exitCode;
		}
	}

	//-! this is just a temporary solution. eventually this functionality will be merged into `::run()` with a better interface and we won't have a need for this function anymore
	public function runAll($opts = Array()){
		trigger_error('`' . get_class($this) . '::runAll()` is deprecated and will be removed soon.  Try using `run()` with array for second argument.', E_USER_DEPRECATED);
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
