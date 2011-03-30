<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: Noven INI Update
// SOFTWARE RELEASE: @@@VERSION@@@
// COPYRIGHT NOTICE: Copyright (C) @@@YEAR@@@ - Jean-Luc Nguyen, Jerome Vieilledent - Noven.
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//
/**
 * Cluster Update handler
 * @author Jerome Vieilledent
 * @package noveniniupdate
 */
class NovenClusterUpdater extends NovenConfigAbstractUpdater implements INovenFileUpdater
{
	const DEFAULT_INI_FILE = 'settings/override/file.ini.append.php',
		  DEFAULT_PHP_FILE = 'index_cluster.php',
		  DEFAULT_DB_HOST = 'localhost',
		  DEFAULT_DB_PORT = 3306,
		  DEFAULT_DB_CHUNK_SIZE = 65535,
		  DEFAULT_DB_CONNECT_RETRIES = 3,
		  DEFAULT_DB_EXECUTE_RETRIES = 20;
		  
	private $iniDirPath;
	
	private $phpFile;
		  
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see extension/noveniniupdate/classes/INovenFileUpdater#setEnv($env)
	 */
	public function setEnv($env, $backup)
	{
		// Check if <ClusterMode> is configured in XML file
		$clusterTag = $this->xmlDoc->ClusterMode;
		if(count($clusterTag))
		{
			// First check if environment is supported
			if(!$this->checkIsEnvSupported($env))
			{
				$errMsg = ezpI18n::tr('extension/noveniniupdate/error', 'Given environment "%envname" is not supported/declared in XML config file', null, array('%envname' => $env));
				throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::UNSUPPORTED_ENV);
			}
			
			$this->phpFile = $clusterTag['php'] ? $clusterTag['php'] : self::DEFAULT_PHP_FILE;
			$iniFile = $clusterTag['ini'] ? $clusterTag['ini'] : self::DEFAULT_INI_FILE;
			$this->iniDirPath = dirname($iniFile);

			if($backup) // Do a backup if necessary
			{
				$this->doBackup($this->phpFile);
				$this->doBackup($iniFile);
			}
			
			// Get cluster params for given environment
			$aClusterConf = $this->xmlDoc->xpath("//ClusterMode/cluster[@env='$env']");
			if(!$aClusterConf)
			{
				$errMsg = ezpI18n::tr('extension/noveniniupdate/error', 'Cluster Mode is not configured for environment "%envname" in XML config file !', null, array('%envname' => $env));
				throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::CLUSTER_NOT_CONFIGURED);
			}
			
			$clusterConf = $aClusterConf[0];
			$fileHandler = (string)$clusterConf->FileHandler;
			// Now configure depending on the chosen FileHandler
			switch($fileHandler)
			{
				case 'ezfs':
				case 'eZFSFileHandler':
				case 'eZFS2FileHandler':
					$this->updateFSFileHandler($fileHandler);
					break;
					
				case 'ezdb':
				case 'eZDBFileHandler':
					$this->updateDBFileHandler($clusterConf, $fileHandler);
					break;
				
				case 'eZDFSFileHandler':
				    $this->updateDFSFileHandler($clusterConf);
				    break;
					
				default:
					$errMsg = ezpI18n::tr('extension/noveniniupdate/error', 'FileHandler "%filehandler" is not supported by NovenINIUpdate !', null, array('%filehandler' => $fileHandler));
					throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::UNSUPPORTED_FILE_HANDLER);
					break;
			}
		}
	}
	
	/**
	 * Updates only file.ini to set the FSFileHandler
	 * @param $fileHandler
	 * @return void
	 */
	private function updateFSFileHandler($fileHandler)
	{
		$ini = eZINI::instance( 'file.ini.append.php', $this->iniDirPath, null, null, null, true, true );
		$ini->setVariable('ClusteringSettings', 'FileHandler', $fileHandler);
		$writeOk = $ini->save(); // Save the INI file
		
		if(!$writeOk)
		{
			$errMsg = ezpI18n::tr('extension/noveniniupdate/error', 'Write error on file %inifile', null, array('%inifile' => "$path/$iniFile.append.php"));
			throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::FILE_IO_ERROR);
		}
	}
	
	/**
	 * Updates both file.ini config file and PHP file (index_cluster.php)
	 * @param SimpleXMLElement $clusterConf
	 * @param $fileHandler
	 * @return void
	 */
	private function updateDBFileHandler(SimpleXMLElement $clusterConf, $fileHandler)
	{
		$host = (string)$clusterConf->DBHost ? (string)$clusterConf->DBHost : self::DEFAULT_DB_HOST;
		$port = (string)$clusterConf->DBPort ? (string)$clusterConf->DBPort : self::DEFAULT_DB_PORT;
		$chunkSize = (string)$clusterConf->DBChunkSize ? (string)$clusterConf->DBChunkSize : self::DEFAULT_DB_CHUNK_SIZE;
		$connectRetries = (string)$clusterConf->DBConnectRetries ? (string)$clusterConf->DBConnectRetries : self::DEFAULT_DB_CONNECT_RETRIES;
		$executeRetries = (string)$clusterConf->DBExecuteRetries ? (string)$clusterConf->DBExecuteRetries : self::DEFAULT_DB_EXECUTE_RETRIES;
		
		// Update the INI File
		$ini = eZINI::instance( 'file.ini.append.php', $this->iniDirPath, null, null, null, true, true );
		$ini->setVariable('ClusteringSettings', 'FileHandler', $fileHandler);
		$ini->setVariable('ClusteringSettings', 'DBBackend', (string)$clusterConf->DBBackend);
		$ini->setVariable('ClusteringSettings', 'DBHost', $host);
		$ini->setVariable('ClusteringSettings', 'DBPort', $port);
		$ini->setVariable('ClusteringSettings', 'DBSocket', (string)$clusterConf->DBSocket);
		$ini->setVariable('ClusteringSettings', 'DBName', (string)$clusterConf->DBName);
		$ini->setVariable('ClusteringSettings', 'DBUser', (string)$clusterConf->DBUser);
		$ini->setVariable('ClusteringSettings', 'DBPassword', (string)$clusterConf->DBPassword);
		$ini->setVariable('ClusteringSettings', 'DBChunkSize', $chunkSize);
		$ini->setVariable('ClusteringSettings', 'DBConnectRetries', $connectRetries);
		$ini->setVariable('ClusteringSettings', 'DBExecuteRetries', $executeRetries);
		$writeOk = $ini->save(); // Save the INI file
		
		if(!$writeOk)
		{
			$errMsg = ezpI18n::tr('extension/noveniniupdate/error', 'Write error on file %inifile', null, array('%inifile' => "$this->iniDirPath/file.ini.append.php"));
			throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::FILE_IO_ERROR);
		}
		
		// Update the PHP File
		try
		{
			$phpGenerator = new ezcPhpGenerator($this->phpFile);
			$storageBackend = ((string)$clusterConf->DBBackend == 'eZDBFileHandlerOracleBackend') ? 'oracle' : 'mysql';
			$phpGenerator->appendComment('Generated by NovenINIUpdate. '.date('Y-m-d H:i'));
			$phpGenerator->appendDefine('STORAGE_BACKEND', $storageBackend);
			$phpGenerator->appendDefine('STORAGE_HOST', $host);
			$phpGenerator->appendDefine('STORAGE_PORT', $port);
			$phpGenerator->appendDefine('STORAGE_SOCKET', (string)$clusterConf->DBSocket);
			$phpGenerator->appendDefine('STORAGE_USER', (string)$clusterConf->DBUser);
			$phpGenerator->appendDefine('STORAGE_PASS', (string)$clusterConf->DBPassword);
			$phpGenerator->appendDefine('STORAGE_DB', (string)$clusterConf->DBName);
			$phpGenerator->appendDefine('STORAGE_CHUNK_SIZE', $chunkSize);
			$phpGenerator->appendEmptyLines(1);
			$phpGenerator->appendCustomCode("include('index_image.php');");
			$phpGenerator->finish();
		}
		catch(ezcPhpGeneratorException $e)
		{
			$errMsg = ezpI18n::tr('extension/noveniniupdate/error', 'Write error on file %inifile', null, array('%inifile' => $phpFile));
			throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::FILE_IO_ERROR);
		}
	}
	
	/**
	 * Updates both file.ini config file and PHP file (index_cluster.php) for DFS cluster
	 * @param SimpleXMLElement $clusterConf
	 * @throws NovenConfigUpdaterException
	 * @throws ezcPhpGeneratorException
	 */
	private function updateDFSFileHandler(SimpleXMLElement $clusterConf)
	{
        $host = (string)$clusterConf->DBHost ? (string)$clusterConf->DBHost : self::DEFAULT_DB_HOST;
        $port = (string)$clusterConf->DBPort ? (string)$clusterConf->DBPort : self::DEFAULT_DB_PORT;
        $connectRetries = (string)$clusterConf->DBConnectRetries ? (string)$clusterConf->DBConnectRetries : self::DEFAULT_DB_CONNECT_RETRIES;
        $executeRetries = (string)$clusterConf->DBExecuteRetries ? (string)$clusterConf->DBExecuteRetries : self::DEFAULT_DB_EXECUTE_RETRIES;
        
        // Update the INI File
        $ini = eZINI::instance( 'file.ini.append.php', $this->iniDirPath, null, null, null, true, true );
        $ini->setVariable('ClusteringSettings', 'FileHandler', 'eZDFSFileHandler');
        $ini->setVariable('ClusteringSettings', 'DBBackend', (string)$clusterConf->DBBackend);
        $ini->setVariable('eZDFSClusteringSettings', 'DBBackend', (string)$clusterConf->DBBackend);
        $ini->setVariable('eZDFSClusteringSettings', 'MountPointPath', (string)$clusterConf->MountPointPath);
        $ini->setVariable('eZDFSClusteringSettings', 'DBHost', $host);
        $ini->setVariable('eZDFSClusteringSettings', 'DBPort', $port);
        $ini->setVariable('eZDFSClusteringSettings', 'DBSocket', (string)$clusterConf->DBSocket);
        $ini->setVariable('eZDFSClusteringSettings', 'DBName', (string)$clusterConf->DBName);
        $ini->setVariable('eZDFSClusteringSettings', 'DBUser', (string)$clusterConf->DBUser);
        $ini->setVariable('eZDFSClusteringSettings', 'DBPassword', (string)$clusterConf->DBPassword);
        $ini->setVariable('eZDFSClusteringSettings', 'DBConnectRetries', $connectRetries);
        $ini->setVariable('eZDFSClusteringSettings', 'DBExecuteRetries', $executeRetries);
        $writeOk = $ini->save(); // Save the INI file
        
        if(!$writeOk)
        {
            $errMsg = ezpI18n::tr('extension/noveniniupdate/error', 'Write error on file %inifile', null, array('%inifile' => "$this->iniDirPath/file.ini.append.php"));
            throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::FILE_IO_ERROR);
        }
        
        // Update the PHP File
        try
        {
            $phpGenerator = new ezcPhpGenerator($this->phpFile);
            $storageBackend = ((string)$clusterConf->DBBackend == 'eZDFSFileHandlerMySQLBackend') ? 'dfsmysql' : 'dfsoracle';
            $phpGenerator->appendComment('Generated by NovenINIUpdate. '.date('Y-m-d H:i'));
            $phpGenerator->appendDefine('STORAGE_BACKEND', $storageBackend);
            $phpGenerator->appendDefine('STORAGE_HOST', $host);
            $phpGenerator->appendDefine('STORAGE_PORT', $port);
            $phpGenerator->appendDefine('STORAGE_SOCKET', (string)$clusterConf->DBSocket);
            $phpGenerator->appendDefine('STORAGE_USER', (string)$clusterConf->DBUser);
            $phpGenerator->appendDefine('STORAGE_PASS', (string)$clusterConf->DBPassword);
            $phpGenerator->appendDefine('STORAGE_DB', (string)$clusterConf->DBName);
            $phpGenerator->appendDefine('MOUNT_POINT_PATH', (string)$clusterConf->MountPointPath);
            $phpGenerator->appendEmptyLines(1);
            $phpGenerator->appendCustomCode("include('index_image.php');");
            $phpGenerator->finish();
        }
        catch(ezcPhpGeneratorException $e)
        {
            $errMsg = ezpI18n::tr('extension/noveniniupdate/error', 'Write error on file %inifile', null, array('%inifile' => $phpFile));
            throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::FILE_IO_ERROR);
        }
	}
	
	/**
	 * (non-PHPdoc)
	 * @see extension/noveniniupdate/classes/INovenFileUpdater#getParamsByEnv($env)
	 */
	public function getParamsByEnv($env)
	{
		$aParams = array();
		
		// Check if <ClusterMode> is configured in XML file
		$clusterTag = $this->xmlDoc->ClusterMode;
		if(count($clusterTag))
		{
			// First check if environment is supported
			if(!$this->checkIsEnvSupported($env))
			{
				$errMsg = ezpI18n::tr('extension/noveniniupdate/error', 'Given environment "%envname" is not supported/declared in XML config file', null, array('%envname' => $env));
				throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::UNSUPPORTED_ENV);
			}
			
			// Get cluster params for given environment
			$aClusterConf = $this->xmlDoc->xpath("//ClusterMode/cluster[@env='$env']");
			if(!$aClusterConf)
			{
				$errMsg = ezpI18n::tr('extension/noveniniupdate/error', 'Cluster Mode is not configured for environment "%envname" in XML config file !', null, array('%envname' => $env));
				throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::CLUSTER_NOT_CONFIGURED);
			}
			
			$clusterConf = $aClusterConf[0];
			foreach($clusterConf as $name => $value)
			{
				$aParams[] = array(
					'name'		=> $name,
					'value'		=> (string)$value
				);
			}
		}
		
		return $aParams;
	}
	
	public function getDiffParamsByEnv($env)
	{
		$currentEnv = $this->getCurrentEnvironment();
		$aResult = array(
			'current'	=> $this->getParamsByEnv($currentEnv),
			'new'		=> $this->getParamsByEnv($env)
		);
		
		return $aResult;
	}
}