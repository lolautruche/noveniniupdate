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
 * CLI Formater used to display information returned by NovenINIUpdate
 * @author Jerome Vieilledent
 */
class NovenINIUpdateCLIFormater
{
	/**
	 * @var ezcConsoleOutput
	 */
	protected $output;
	
	/**
	 * @var ezcConsoleTable
	 */
	protected $table;
	
	public function __construct()
	{
		$this->output = new ezcConsoleOutput();
		
		// Output formats
		$this->output->formats->headContent->style = array( 'bold' );
		$this->output->formats->title->style = array('bold');
	}
	
	/**
	 * Formats the final output with a table
	 * @param array $aData
	 * @param $title
	 * @return unknown_type
	 */
	public function formatTable(array $aData, $title=null)
	{
		$this->table = new ezcConsoleTable( $this->output, 300 );
		$this->table[0]->borderFormat = 'headBorder';
		$this->table[0]->format = 'headContent';
		$this->table[0]->align = ezcConsoleTable::ALIGN_CENTER;
		
		foreach($aData as $row => $cells)
		{
			foreach($cells as $cell)
			{
				$this->table[$row][]->content = $cell;
			}
		}
		
		if($title)
			$this->output->outputLine($title, 'title');
		$this->table->outputTable();
		$this->output->outputLine();
		$this->output->outputLine();
	}
	
	/**
	 * Formats the environment list for the CLI
	 * @param $aEnvList
	 * @return void
	 * @static
	 */
	public static function formatEnvList(array $aEnvList)
	{
		$aData = array(
			array( // Table header
				ezi18n('extension/noveniniupdate/script', 'Environment Name'), 
				ezi18n('extension/noveniniupdate/script', 'Comment')
			)
		);
		
		// First format the array
		foreach ($aEnvList as $env)
		{
			$aData[] = array((string)$env['name'], (string)$env['comment']);
		}
		
		$formater = new self();
		$formater->formatTable($aData, ezi18n('extension/noveniniupdate/script', 'Environments list :'));
	}
	
	/**
	 * Formats the params list for the CLI
	 * @param array $aFiles
	 * @return void
	 * @static
	 */
	public static function formatParamsList(array $aFiles)
	{
		$formater = new self();
		foreach($aFiles as $file)
		{
			$title = $file['path'].' => '.$file['comment'];
			$aData = array(
				array( // Table header
					'Block', 
					'Param',
					'Value'
				)
			);
			
			foreach($file['lines'] as $line)
			{
				$aRow = array($line['block'], $line['name'], $line['value']);
				$aData[] = $aRow;
			}
			
			// Now ouput params for the current file
			$formater->formatTable($aData, $title);
		}
	}
	
	/**
	 * Formats cluster params list for the CLI
	 * @param array $aParams
	 * @return void
	 * @static
	 */
	public static function formatClusterParamsList(array $aParams, $title='Cluster Params')
	{
		if($aParams)
		{
			$formater = new self();
			$aData = array(
				array(
					'Name',
					'Value'
				)
			);
			
			foreach($aParams as $param)
			{
				$aData[] = array($param['name'], $param['value']);
			}
			
			// Now ouput params for the current file
			$formater->formatTable($aData, $title);
		}
	}
	
	/**
	 * Formats params diff between current INI values and those to be applied for requested environment
	 * @param array $aCurrentParamsFiles
	 * @return void
	 * @static
	 */
	public static function formatParamsDiff(array $aEnvParamsFiles)
	{
		$formater = new self();
		
		foreach($aEnvParamsFiles as $file)
		{
			$iniFile = basename((string)$file['path']);
			$iniFile = str_replace('.append.php', '', $iniFile);
			$ini = eZINI::instance($iniFile);
			
			$title = $file['path'].' => '.$file['comment'];
			$aData = array(
				array( // Table header
					'Block', 
					'Param',
					'Current Value',
					'Modified Value'
				)
			);
			
			foreach($file['lines'] as $line)
			{
				$currentValue = $ini->variable((string)$line['block'], (string)$line['name']);
				$aRow = array($line['block'], $line['name'], $currentValue, $line['value']);
				$aData[] = $aRow;
			}
			
			// Now ouput params for the current file
			$formater->formatTable($aData, $title);
		}
	}
	
	public static function formatClusterParamsDiff(array $aClusterDiffParams)
	{
		$formater = new self();
		
		// First handle "current" cluster params
		if($aClusterDiffParams['current'])
		{
			self::formatClusterParamsList($aClusterDiffParams['current'], 'Current Cluster Params');
		}
		else
		{
			$formater->output->outputText('No current cluster params found.');
			$formater->output->outputLine();
		}
		
		// Handling new cluster params
		if($aClusterDiffParams['new'])
		{
			self::formatClusterParamsList($aClusterDiffParams['new'], 'Modified Cluster Params');
		}
		else
		{
			$formater->output->outputText('No cluster params found for requested environment.');
			$formater->output->outputLine();
		}
	}
}