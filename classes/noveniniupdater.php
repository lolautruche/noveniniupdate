<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: Noven INI Update
// SOFTWARE RELEASE: $$$VERSION$$$
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
 * INI update handler
 * Can be used to switch INI params depending on environments (dev, staging, production, ...)
 * @author Jerome Vieilledent
 * @package noveniniupdate
 */
class NovenINIUpdater extends NovenConfigAbstractUpdater implements INovenFileUpdater
{
	const INI_TYPE_ARRAY = 'array',
		  INI_TYPE_STRING = 'string';

	/**
	 * @var array
	 */
	protected $aAcceptedINITypes;

	/**
	 * Constructor
	 * @return NovenINIUpdater
	 * @throws NovenConfigUpdaterException
	 */
	public function __construct()
	{
		parent::__construct();
		$this->aAcceptedINITypes = $this->updateINI->variable('XmlSettings', 'XmlType');
	}

	/**
	 * Returns an multidimensionnal array containing the params for a given environment.
	 * At first level, each entry represent a INI file (<file> tag in XML config file), containing :
	 * 	- 'path'		=> Path of the INI file
	 * 	- 'comment'		=> Comment for the INI file
	 * 	- 'lines'		=> Array containing the params (<line> tag in XML config file) :
	 * 		- 'block'		=> INI Block
	 * 		- 'type'		=> INI Datatype
	 * 		- 'name'		=> Name of the INI param
	 * 		- 'value'		=> Value for the param
	 * 		- 'comment'		=> Comment for the param
	 * @param $env
	 * @return array
	 * @throws NovenConfigUpdaterException
	 */
	public function getParamsByEnv($env)
	{
		// First check if environment is supported
		if(!$this->checkIsEnvSupported($env))
		{
			$errMsg = ezi18n('extension/noveniniupdate/error', 'Given environment "%envname" is not supported/declared in XML config file', null, array('%envname' => $env));
			throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::UNSUPPORTED_ENV);
		}
			
		$aResult = array();
		$i = 0;
		foreach($this->xmlDoc->files->file as $file)
		{
			$aResult[$i] = array(
				'path'		=> (string)$file['path'],
				'comment'	=> (string)$file['comment'],
				'lines'		=> array()
			);

			$j = 0;
			foreach($file->line as $line)
			{
				if((string)$line['env'] == $env)
				{
					if(array_key_exists((string)$line['type'], $this->aAcceptedINITypes))
					{
						// Value is different in the XML file if type is an array or not
						switch($line['type'])
						{
							case self::INI_TYPE_ARRAY:
								$value = (string)$line;
								break;
							default:
								$value = (string)$line['value'];
								break;
						}
	
						$aResult[$i]['lines'][] = array(
							'block'		=> (string)$line['block'],
							'type'		=> (string)$line['type'],
							'name'		=> (string)$line['name'],
							'value'		=> $value,
							'comment'	=> (string)$line['comment']
						);
	
					}
					else
					{
						$errMsg = ezi18n('extension/noveniniupdate/error', 'Invalid INI datatype "%datatype"', null, array('%datatype' => (string)$line['type']));
						throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::UNSUPPORTED_DATATYPE);
					}
					$j++;
				}
			}
			$i++;
		}

		return $aResult;
	}

	/**
	 * Sets the environment
	 * @param $env
	 * @return void
	 * @throws NovenConfigUpdaterException
	 */
	public function setEnv($env)
	{
		// First check if environment is supported
		if(!$this->checkIsEnvSupported($env))
		{
			$errMsg = ezi18n('extension/noveniniupdate/error', 'Given environment "%envname" is not supported/declared in XML config file', null, array('%envname' => $env));
			throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::UNSUPPORTED_ENV);
		}

		$aFiles = $this->getParamsByEnv($env);
		foreach($aFiles as $file)
		{
			$filePath = $file['path'];
			$path = dirname( $filePath );
			$iniFile = str_replace( '.append.php', '', basename($filePath) );
			$iniFile = str_replace( '.append', '', $iniFile );
			$iniFile = $iniFile . '.append';

			// Write the params for each INI file
			$ini = eZINI::instance( $iniFile, $path, null, null, null, true, true );
			foreach($file['lines'] as $line)
			{
				$settingName    = $line['name'];
				$settingType    = $line['type'];
				$block          = $line['block'];
				$valueToWrite	= trim($line['value']);

				if ( $settingType == self::INI_TYPE_ARRAY )
				{
					$valueArray = explode( "\n", $valueToWrite );
					$valuesToWriteArray = array();

					$settingCount = 0;
					foreach( $valueArray as $value )
					{
						if ( preg_match( "/^\[(.+)\]\=(.+)$/", $value, $matches ) )
						{
							$valuesToWriteArray[$matches[1]] = trim( $matches[2], "\r\n" );
						}
						else
						{
							$value = substr( strchr( $value, '=' ), 1 );
							if ( $value == "" )
							{
								if ( $settingCount == 0 )
									$valuesToWriteArray[] = null;
							}
							else
							{
								$valuesToWriteArray[] = trim( $value, "\r\n" );
							}
						}
						++$settingCount;
					}

					$ini->setVariable( $block, $settingName, $valuesToWriteArray );
				}
				else
				{
					$ini->setVariable( $block, $settingName, $valueToWrite );
				}
				$writeOk = $ini->save(); // Save the INI file
				
				if(!$writeOk)
				{
					$errMsg = ezi18n('extension/noveniniupdate/error', 'Write error on file %inifile', null, array('%inifile' => "$path/$iniFile.append.php"));
					throw new NovenConfigUpdaterException($errMsg, NovenConfigUpdaterException::FILE_IO_ERROR);
				}
			}
		}
	}
}