#!/usr/bin/env php
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
require 'autoload.php';

$cli = eZCLI::instance();
$cli->setUseStyles(true);

$script = eZScript::instance( array( 'description' => ( "\nSwitches INI params depending on a given environment.\n" ),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true ) );

$script->startup();
$script->initialize();

$output = new ezcConsoleOutput();
$output->formats->error->style = array('bold');
$output->formats->error->color = 'red';

// Options handling
$options = $script->getOptions(
	'[env:][list-envs][list-params]',
	'',
	array( 'env'			=> ezi18n('extension/noveniniupdate/script', 'Environment identifier for switching INI params. Use --list-envs switch to list available environments.'),
		   'list-envs'		=> ezi18n('extension/noveniniupdate/script', 'Lists available environmnents.'),
		   'list-params'	=> ezi18n('extension/noveniniupdate/script', 'Lists configured params for given environment.')
	)
);

try
{
	$iniUpdater = new NovenINIUpdater();
	$clusterUpdater = new NovenClusterUpdater();
	
	if($options['list-envs']) // Just lists available envs
	{
		$aEnv = $iniUpdater->getEnvs();
		NovenINIUpdateCLIFormater::formatEnvList($aEnv);
	}
	else if($options['list-params']) // Lists params for given env
	{
		if(!$options['env'])
			throw new Exception(ezi18n('extension/noveniniupdate/script', 'Environment not set ! Please set it with --env=VALUE'));
			
		$aParams = $iniUpdater->getParamsByEnv($options['env']);
		NovenINIUpdateCLIFormater::formatParamsList($aParams);
		
		$aClusterParams = $clusterUpdater->getParamsByEnv($options['env']);
		NovenINIUpdateCLIFormater::formatClusterParamsList($aClusterParams);
	}
	else // Sets the environment
	{
		if(!$options['env'])
			throw new Exception(ezi18n('extension/noveniniupdate/script', 'Environment not set ! Please set it with --env=VALUE'));
			
		$cli->notice(ezi18n('extension/noveniniupdate/script', 'Starting environment switching...'));
		$iniUpdater->setEnv($options['env']);
		$clusterUpdater->setEnv($options['env']);
		$cli->notice(ezi18n('extension/noveniniupdate/script', 'Environment switching complete !'));
	}
	
	$script->shutdown();
}
catch(Exception $e)
{
	$output->outputText($e->getMessage(), 'error');
	$output->outputLine();
	$script->shutdown($e->getCode());
}