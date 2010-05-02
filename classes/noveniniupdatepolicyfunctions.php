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

class NovenINIUpdatePolicyFunctions
{
	/**
	 * Fetches limitation list for policies
	 */
	public static function fetchEnvironmentLimitationList()
	{
		$updater = new NovenINIUpdater();
		$aEnvs = $updater->getEnvs(); // Returns array of envs as SimpleXMLElement
		$aResult = array();

		foreach($aEnvs as $env)
		{
			$id = (string)$env['name'];
			$name = isset($env['comment']) ? (string)$env['comment'] : $id;
			$aResult[] = array(
				'id'	=> $id,
				'name'	=> $name
			);
		}

		return $aResult;
	}

	/**
	 * Shorthand method to check user access policy limitations for a given module/policy function.
	 * Returns the same array as eZUser::hasAccessTo(), with "simplifiedLimitations".
	 * 'simplifiedLimitations' array holds all the limitations names as defined in module.php.
	 * If your limitation name is not defined as a key, then your user has full access to this limitation
	 * @param string $module Name of the module
	 * @param string $function Name of the policy function ($FunctionList element in module.php)
	 * @return array
	 */
	public static function getSimplifiedUserAccess( $module, $function )
	{
		$user = eZUser::currentUser();
		$userAccess = $user->hasAccessTo( $module, $function );

		$userAccess['simplifiedLimitations'] = array();
		if( $userAccess['accessWord'] == 'limited' )
		{
			foreach( $userAccess['policies'] as $policy )
			{
				foreach( $policy as $limitationName => $limitationList )
				{
					foreach( $limitationList as $limitationValue )
					{
						$userAccess['simplifiedLimitations'][$limitationName][] = $limitationValue;
					}

					$userAccess['simplifiedLimitations'][$limitationName] = array_unique($userAccess['simplifiedLimitations'][$limitationName]);
				}
			}
		}
		return $userAccess;
	}
}