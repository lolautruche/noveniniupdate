<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: Noven INI Update
// SOFTWARE RELEASE: @@@VERSION@@@
// COPYRIGHT NOTICE: Copyright (C) 2009 - Jean-Luc Nguyen, Jerome Vieilledent - Noven.
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
 * Update Abstract Handler
 * @author Jerome Vieilledent
 * @package noveniniupdate
 */
abstract class NovenConfigAbstractUpdater
{
	const NOVENINIUPDATE_ENV_KEY = 'noveniniupdate_env';
	
	/**
	 * @var eZINI
	 */
	protected $updateINI;

	/**
	 * Path to XML config file, relative to eZPublish instance
	 * @var string
	 */
	protected $xmlPath;
	
	/**
	 * SimpleXMLElement representation of the XML config file
	 * @var SimpleXMLElement
	 */
	protected $xmlDoc;
	
	/**
	 * Environments configured in the XML file
	 * @var array
	 */
	protected $aEnvs = array();
	
	/**
	 * Constructor
	 * @return NovenConfigAbstractUpdater
	 * @throws NovenConfigUpdaterException
	 */
	public function __construct()
	{
		$this->updateINI = eZINI::instance('noveniniupdate.ini');
		$this->xmlPath = $this->updateINI->variable('XmlSettings', 'XmlContent');
		
		// Check if XML file exists
		if(!file_exists($this->xmlPath))
			throw new NovenConfigUpdaterException(ezi18n('extension/noveniniupdate/error', 'XML content does not exist'), NovenConfigUpdaterException::XML_FILE_UNAVAILABLE);
			
		$this->xmlDoc = $this->parseXML($this->xmlPath);
		foreach($this->xmlDoc->envs->env as $env)
		{
			$this->aEnvs[] = $env;
		}
	}
	
	/**
	 * Returns available environments as configured in the source XML file
	 * @return array of SimpleXMLElements
	 */
	public function getEnvs()
	{
		return $this->aEnvs;
	}
	
	/**
	 * Parses an XML file from its file path. Returns a SimpleXMLElement object
	 * @param $xmlFilePath
	 * @return SimpleXMLElement
	 * @throws NovenConfigUpdaterException
	 */
	protected function parseXML($xmlFilePath)
	{
		set_error_handler(array('NovenConfigUpdaterException', 'HandleSimpleXMLError')); // SimpleXML error handling by NovenConfigUpdaterException
		$xmlDoc = new SimpleXMLElement($this->xmlPath, null, true);
		restore_error_handler();

		return $xmlDoc;
	}
	
	/**
	 * Checks if a given environment is supported (declared) in the XML file
	 * @param $env
	 * @return bool
	 */
	protected function checkIsEnvSupported($requestedEnv)
	{
		$isSupported = false;
		foreach($this->aEnvs as $env)
		{
			$envName = (string)$env['name'];
			if($envName == $requestedEnv)
			{
				$isSupported = true;
				break;
			}
		}

		return $isSupported;
	}
	
	/**
	 * Stores environment information in ezsite_data table for information
	 * @param string $env
	 * @return void
	 */
	public function storeEnvironment($env)
	{
		// First check if environment is supported
		if(!$this->checkIsEnvSupported($env))
		{
			$errMsg = ezi18n('extension/noveniniupdate/error', 'Given environment "%envname" is not supported/declared in XML config file', null, array('%envname' => $env));
			throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::UNSUPPORTED_ENV);
		}
		
		$db = eZDB::instance();
		
		/*
		 * Check if entry already exists in database.
		 * If so, update it.
		 * If no, create a new entry
		 */
		$resultSelect = $db->arrayQuery('SELECT COUNT(*) AS count FROM `ezsite_data` WHERE `name`="'.self::NOVENINIUPDATE_ENV_KEY.'"');
		if($resultSelect[0]['count'] == 0)
		{
			$db->query('INSERT INTO `ezsite_data` (`name`, `value` ) VALUES("'.self::NOVENINIUPDATE_ENV_KEY.'", "'.$env.'")');
		}
		else
		{
			$db->query('UPDATE `ezsite_data` SET `value`="'.$env.'" WHERE `name`="'.self::NOVENINIUPDATE_ENV_KEY.'"');
		}
	}
	
	/**
	 * Fetches current environment stored in the database
	 * @return string
	 */
	public function getCurrentEnvironment()
	{
		$currentEnv = null;
		$db = eZDB::instance();
		
		$result = $db->arrayQuery('SELECT * FROM `ezsite_data` WHERE `name`="'.self::NOVENINIUPDATE_ENV_KEY.'"');
		if($result)
		{
			$currentEnv = $result[0]['value'];
		}
		
		return $currentEnv;
	}
}