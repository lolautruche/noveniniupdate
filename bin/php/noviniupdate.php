#!/usr/bin/env php
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
	'[env:][list-envs][list-params][diff][backup]',
	'',
	array( 'env'			=> 'Environment identifier for switching INI params. Use --list-envs switch to list available environments.',
		   'list-envs'		=> 'Lists available environmnents.',
		   'list-params'	=>  'Lists configured params for given environment.',
		   'diff'			=>  'Displays the difference between current params and those to be applied',
		   'backup'			=>  'Does a backup of old INI files',
	)
);

try
{
	$iniUpdater = new NovenINIUpdater();
	$clusterUpdater = new NovenClusterUpdater();
	$configUpdater = new NovenConfigUpdater();
	
	if($options['list-envs']) // Just lists available envs
	{
		$aEnv = $iniUpdater->getEnvs();
		NovenINIUpdateCLIFormater::formatEnvList($aEnv);
	}
	else if($options['list-params']) // Lists params for given env
	{
		if(!$options['env'])
			throw new Exception( 'Environment not set ! Please set it with --env=VALUE');
			
		$aParams = $iniUpdater->getParamsByEnv($options['env']);
		NovenINIUpdateCLIFormater::formatParamsList($aParams);
		
		$aClusterParams = $clusterUpdater->getParamsByEnv($options['env']);
		NovenINIUpdateCLIFormater::formatClusterParamsList($aClusterParams);
		
		$aConfigParams = $configUpdater->getParamsByEnv($options['env']);
		NovenINIUpdateCLIFormater::formatClusterParamsList($aConfigParams);
	}
	else if($options['diff']) // Shows a diff between current params and params for given env
	{
		if(!$options['env'])
			throw new Exception( 'Environment not set ! Please set it with --env=VALUE');
		
		$aParams = $iniUpdater->getParamsByEnv($options['env']);
		NovenINIUpdateCLIFormater::formatParamsDiff($aParams);
		
		$aClusterDiffParams = $clusterUpdater->getDiffParamsByEnv($options['env']);
		NovenINIUpdateCLIFormater::formatClusterParamsDiff($aClusterDiffParams);
		
		$aConfigDiffParams = $configUpdater->getDiffParamsByEnv($options['env']);
		NovenINIUpdateCLIFormater::formatConfigParamsDiff($aConfigDiffParams);
	}
	else // Sets the environment
	{
		if(!$options['env'])
			throw new Exception( 'Environment not set ! Please set it with --env=VALUE');
		
		$backup = (bool)$options['backup'];
			
		$cli->notice( 'Starting environment switching...');
		$iniUpdater->setEnv($options['env'], $backup);
		$clusterUpdater->setEnv($options['env'], $backup);
		$configUpdater->setEnv($options['env'], $backup);
		//clear local cache
		$dircache = str_replace("\\","/",eZSys::rootDir().eZSys::fileSeparator()."var/cache/ini".eZSys::fileSeparator());
		$cli->output( 'clearcache local');
		rrmdir($dircache) ;		
		$iniUpdater->storeEnvironment($options['env']); // Stores chosen environment in DB
		$cli->notice( 'Environment switching complete !');
	}
	
	$script->shutdown();
}
catch(Exception $e)
{
	$output->outputText($e->getMessage(), 'error');
	$output->outputLine();
	$script->shutdown($e->getCode());
}

function rrmdir($dir) {
	if(is_dir($dir)){
    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file))
            rrmdir($file);
        else
            unlink($file);
    }
    rmdir($dir);
	}
}
