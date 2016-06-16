<?php
//� 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Remote\Host;
use MTS\Common\Devices\Actions\Remote\Base;

class OperatingSystem extends Base
{
	public function getOsObj($shellObj)
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['shellObj']		= $shellObj;
		return $this->execute();
	}
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		$shellObj			= $this->_classStore['shellObj']->getActiveShell();

		if ($requestType == 'getOsObj') {

			if ($shellObj instanceof \MTS\Common\Devices\Shells\Bash) {
				$osObj				= null;
				
				//we need 4 things to determine the correct class of OS
				$osArch				= null;
				$osType				= null;
				$osName				= null;
				$osMajorVersion		= null;
				
				$cmdString		= 'cat /etc/os-release';
				$cReturn		= $shellObj->exeCmd($cmdString);

				if (strlen($cReturn) == 0) {
					$cmdString		= 'cat /etc/redhat-release';
					$cReturn		= $shellObj->exeCmd($cmdString);
				}
				
				if (preg_match("/Linux/", $cReturn)) {
					$osType			= 'linux';

					$cmdString		= 'uname -a';
					$uReturn		= $shellObj->exeCmd($cmdString);

					preg_match("/(x86_64|i386|i686)/i", $uReturn, $rawArch);
					if (isset($rawArch[1])) {
						$rawArch	= strtolower($rawArch[1]);
						if ($rawArch == "x86_64") {
							$osArch	= 64;
						} elseif ($rawArch == "i386" || $rawArch == "i686") {
							$osArch	= 32;
						}
					}
											
					if ($cReturn !== null) {
						preg_match("/NAME=\"(CentOS Linux|Debian GNU\/Linux|Ubuntu|Arch Linux)\"/", $cReturn, $rawName);
				
						if (isset($rawName[1]) === true) {
							$osName				= strtolower($rawName[1]);
						}
				
						if ($osName == 'arch linux') {
							$cmdString		= 'cat /proc/version';
							$c2Return		= $shellObj->exeCmd($cmdString);
				
							preg_match("/([0-9]{8})/", $c2Return, $rawMajorVersion);
				
							if (isset($rawMajorVersion[1]) === true) {
								$osMajorVersion		= $rawMajorVersion[1];
							}
				
						} else {
							preg_match("/VERSION_ID=\"([0-9]+)/", $cReturn, $rawMajorVersion);
				
							if (isset($rawMajorVersion[1]) === true) {
								$osMajorVersion		= $rawMajorVersion[1];
							}
						}
					}
				}
				
				if (
				$osType !== null
				&& $osName !== null
				&& $osMajorVersion !== null
				&& $osArch !== null
				) {
					$osObj	= null;
					if ($osType == 'linux') {
						if ($osName == 'centos linux') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\CentOSBase();
						} elseif ($osName == 'red hat enterprise linux') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\RHELBase();
						} elseif ($osName == 'debian gnu/linux') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\DebianBase();
						} elseif ($osName == 'ubuntu') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\UbuntuBase();
						} elseif ($osName == 'arch linux') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\ArchBase();
						}
					}
				
					if ($osObj !== null) {
						$osObj->setMajorVersion($osMajorVersion);
						$osObj->setArchitecture($osArch);
						return $osObj;
					}
				}
			} elseif ($shellObj instanceof \MTS\Common\Devices\Shells\RouterOS) {
				
			}
		}
		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}