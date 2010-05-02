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

include_once( "kernel/common/template.php" );

$Module = $Params["Module"];
$Result = array();
$tpl = templateInit();
$http = eZHTTPTool::instance();
$errors = array();

try
{
	$iniUpdater = new NovenINIUpdater();
	$clusterUpdater = new NovenClusterUpdater();
	$configUpdater = new NovenConfigUpdater();
	$envs = $iniUpdater->getEnvs();
	$userLimitations = NovenINIUpdatePolicyFunctions::getSimplifiedUserAccess('noveniniupdate', 'configupdate');
	$simplifiedLimitations = $userLimitations['simplifiedLimitations'];
	$currentEnv = $iniUpdater->getCurrentEnvironment();
	$tpl->setVariable('current_env', $currentEnv);

	$environments = array();
	foreach ( $envs as $env )
	{
		$envName = (string)$env['name'];
		$envExpandedName = (string)$env['comment'];
		
		// Policy limitations check
		if((isset($simplifiedLimitations['NovenINIUpdate_Environment']) && in_array($envName, $simplifiedLimitations['NovenINIUpdate_Environment'])) 
		    || !isset($simplifiedLimitations['NovenINIUpdate_Environment']))
			$environments[$envName] = $envExpandedName;
	}
	$tpl->setVariable( 'envs', $environments );

	// Selected environment
	if ( $http->hasPostVariable( "selectedEnvironment" ) )
	{
		$selectedEnvironment = $http->postVariable( "selectedEnvironment" );
		$tpl->setVariable( 'selected_env', $selectedEnvironment );

		// Find variables by environment
		$tabs = $iniUpdater->getParamsByEnv($selectedEnvironment);
		$clusterParams = $clusterUpdater->getParamsByEnv($selectedEnvironment);
		$configParams = $configUpdater->getParamsByEnv($selectedEnvironment);
		$tpl->setVariable( 'tabs', $tabs );
		$tpl->setVariable( 'cluster_params', $clusterParams );
		$tpl->setVariable( 'config_params', $configParams );
	}

	// Update current environment with XML content
	if ( $Module->isCurrentAction( 'UpdateEnvButton' ) )
	{
		if ( $http->hasPostVariable( "selectedEnvironment" ) )
		{
			if(!ezjscAccessTemplateFunctions::hasAccessToLimitation('noveniniupdate', 'configupdate', array('NovenINIUpdate_Environment' => $http->postVariable('selectedEnvironment'))))
				throw new NovenConfigUpdaterException(ezi18n('extension/noveniniupdate/error', 
															 'Your policy limitations does not allow you to update config for "%env" environment', 
															 null, array('%env' => $http->postVariable('selectedEnvironment'))
													  ));
			
			$selectedEnvironment = $http->postVariable( "selectedEnvironment" );
			$iniUpdater->setEnv($selectedEnvironment);
			$clusterUpdater->setEnv($selectedEnvironment);
			$configUpdater->setEnv($selectedEnvironment);
			$iniUpdater->storeEnvironment($selectedEnvironment); // Stores selected environment in DB
			$Module->redirectTo( '/noveniniupdate/view/(update)/1' );
		}
	}

	if ( isset( $Params['Update'] ) && $Params['Update'] == 1 )
	{
		$tpl->setVariable( 'confirm_label', ezi18n( 'extension/noveniniupdate/view', 'The INI parameter(s) have been updated for the selected environment' ) );
	}
	
}
catch(NovenConfigUpdaterException $e)
{
	$errMsg = (string)$e;
	eZLog::write($errMsg, 'noveniniupdate-error.log');
	eZDebug::writeError($errMsg, "NovenINIUpdate");
	$tpl->setVariable( 'error_message', $errMsg );
}

$Result['path'] = array(
	array(
		'url'		=> false,
		'text'		=> ezi18n( 'extension/noveniniupdate', 'Noven advanced INI parameters' )
	)
);
$Result['content'] = $tpl->fetch( "design:noveniniupdate/view.tpl" );

