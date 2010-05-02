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

$Module = array('name' => 'noveniniupdate');

$ViewList = array();

/*
 * Environment selection / list
 */
$ViewList['view'] = array(
	'script'					=>	'view.php',
	'params'					=> 	array(),
	'unordered_params'			=> 	array( 'update' => 'Update' ),	
	'single_post_actions'		=> 	array(  'updateenvbutton' => 'UpdateEnvButton' ),
	'post_action_parameters'	=> 	array(),
	'default_navigation_part'	=> 'noveniniupdatenavigationpart',
	'functions'					=> 'configupdate'
);

// Environment limitation for policy
$Environment = array(
    'name'			=> 'NovenINIUpdate_Environment',
    'values'		=> array(),
	'extension'		=> 'noveniniupdate',
    'path'			=> 'classes/',
    'file'			=> 'noveniniupdatepolicyfunctions.php',
    'class'			=> 'NovenINIUpdatePolicyFunctions',
    'function'		=> 'fetchEnvironmentLimitationList',
    'parameter'		=> array()
);

$FunctionList['configupdate'] = array('NovenINIUpdate_Environment' => $Environment);
