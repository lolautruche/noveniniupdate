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

$updateINI = eZINI::instance('noveniniupdate.ini');

// XML relative path
$xmlContentPath = $updateINI->variable('XmlSettings', 'XmlContent');
// Accepted INI datatypes
$acceptedTypes = $updateINI->variable('XmlSettings', 'XmlType');

// Parsing the XML string
if ( !isset( $xmlContentPath ) )
{
	$errors[] = ezi18n( 'extension/noveniniupdate/error', 'XML content does not exist');
}

// XML readable?
if ( !$xmlContent = file_get_contents( $xmlContentPath ) )
{
	$errors[] = ezi18n( 'extension/noveniniupdate/error', 'XML content is not readable');
}

// DOM
$dom = new DOMDocument();
$dom->loadXML( $xmlContent );

// Coming from view.php
if ( isset( $Params['Env'] ) && $Params['Env'] && isset( $Params['Line'] ) && $Params['Line'] && isset( $Params['Path'] ) && $Params['Path'] )
{
	$selectedEnvironment = $Params['Env'];
    $filePath = $Params['Path'];
    $curLine = $Params['Line'];

    // Find environments
    $envs = $dom->getElementsByTagName('env');

    $environments = array();
    foreach ( $envs as $env )
    {
        if ( $env->getAttribute('name') == $selectedEnvironment )
            $selectedCommentEnv = $env->getAttribute('comment');           
    }
		
	// Find variables by environment
	$files = $dom->getElementsByTagName('file');

    $tab = array();
    $i = 1;
	foreach ( $files as $file )
	{	
        if ( $file->getAttribute('path') == $filePath )
        {
            // Environment
            $tab['file']['label_env'] = $selectedCommentEnv;
            $tab['file']['env'] = $selectedEnvironment;
            // File	
            $tab['file']['path'] = $filePath;
            // Comment file
            $tab['file']['comment'] = $file->getAttribute('comment');
            // Find lines
            $lines = $file->getElementsByTagName('line');
            $i = 1;

	        foreach ( $lines as $line )
	        {
                // Current environment
                if ( $line->getAttribute('env') == $selectedEnvironment )
                {                    
                    if ( in_array( $line->getAttribute('type'), array_keys( $acceptedTypes ) ) && $i == $curLine )
                    {
                        $tab['line']['block']   = $line->getAttribute('block');
                        $tab['line']['type']    = $line->getAttribute('type');
                        $tab['line']['name']    = $line->getAttribute('name');                    
                        $tab['line']['comment'] = $line->getAttribute('comment');

                        switch ($line->getAttribute('type')) {
                            case 'array':
                                $tab['line']['value'] = $line->nodeValue;
                                break;
                            default:
                                $tab['line']['value'] = $line->getAttribute('value');
                        }                        
                    }
                    else
                    {
                        $errors[] = ezi18n( 'extension/noveniniupdate/error', 'Invalid INI datatype:'.' '.$line->getAttribute('type'));
                    }
                    $i++;
                }
            }
        }
	}
}

// Cancel action
if ( $Module->isCurrentAction( 'CancelSetting' ) )
{
    return $Module->redirectTo( '/noveniniupdate/view' );
}

// Save INI setting
if ( $Module->isCurrentAction( 'WriteSetting' ) )
{

    if ( $http->hasPostVariable( 'FilePath' ) )
        $filePath = trim( $http->postVariable( 'FilePath' ) );
    if ( $http->hasPostVariable( 'Block' ) )
        $block = trim( $http->postVariable( 'Block' ) );
    if ( $http->hasPostVariable( 'SettingType' ) )
        $settingType = trim( $http->postVariable( 'SettingType' ) );
    if ( $http->hasPostVariable( 'SettingName' ) )
        $settingName = trim( $http->postVariable( 'SettingName' ) );
    if ( $http->hasPostVariable( 'Value' ) )
    {
        /*
         * Dirty Hack to avoid the "empty value" validation bug in the validate function (kernel/settings/validation.php)
         * If the value is empty, we replace it by a space, and then trim it AFTER the validation
         */
    	$tmpValue = $http->postVariable( 'Value' );
    	$valueToWrite = !empty($tmpValue) ? $tmpValue : ' ';
    	unset($tmpValue);
    }

    if ( $http->hasPostVariable( 'LabelEnv' ) )
        $labelEnv = trim( $http->postVariable( 'LabelEnv' ) );
    if ( $http->hasPostVariable( 'Env' ) )
        $env = trim( $http->postVariable( 'Env' ) );
    if ( $http->hasPostVariable( 'FileComment' ) )
        $fileComment = trim( $http->postVariable( 'FileComment' ) );
    if ( $http->hasPostVariable( 'LineComment' ) )
        $lineComment = trim( $http->postVariable( 'LineComment' ) );

    $path = dirname( $filePath );
    $iniFile = str_replace( '.append.php', '', basename($filePath) );
    $iniFile = str_replace( '.append', '', $iniFile );

    $ini = eZINI::instance( $iniFile . '.append', $path, null, null, null, true, true );

    $hasValidationError = false;
    require 'kernel/settings/validation.php';
    $validationResult = validate( array( 'Name' => $settingName,
                                         'Value' => $valueToWrite ),
                                  array( 'name', $settingType ), true );
    if ( $validationResult['hasValidationError'] )
    {
        $tpl->setVariable( 'validation_field', $validationResult['fieldContainingError'] );
        $hasValidationError = true;
    }

    if ( !$hasValidationError )
    {
        $valueToWrite = trim($valueToWrite);
    	if ( $settingType == 'array' )
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
                            $valuesToWriteArray[] = NULL;
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
        $writeOk = $ini->save(); // false, false, false, false, true, true );

        if ( !$writeOk )
        {
            $tab = array();
            $tab['file']['label_env']   = $labelEnv;
            $tab['file']['env']         = $env;
            $tab['file']['comment']     = $fileComment;
            $tab['file']['path']        = $filePath;

            $tab['line']['block']       = $block;
            $tab['line']['type']        = $settingType;
            $tab['line']['name']        = $settingName;                    
            $tab['line']['comment']     = $lineComment;
            $tab['line']['value']       = $valueToWrite;

            $tpl->setVariable( 'validation_error', true );
            $tpl->setVariable( 'validation_error_type', 'write_error' );
            $tpl->setVariable( 'path', $path );
            $tpl->setVariable( 'filename',  $iniFile . '.append.php' );
        }
        else
        {
            return $Module->redirectTo( '/noveniniupdate/view/(update)/1' );
        }
    }
    else // found validation errors...
    {
        $tab = array();
        $tab['file']['label_env']   = $labelEnv;
        $tab['file']['env']         = $env;
        $tab['file']['comment']     = $fileComment;
        $tab['file']['path']        = $filePath;

        $tab['line']['block']       = $block;
        $tab['line']['type']        = $settingType;
        $tab['line']['name']        = $settingName;                    
        $tab['line']['comment']     = $lineComment;
        $tab['line']['value']       = $valueToWrite;

        $tpl->setVariable( 'validation_error', true );
        $tpl->setVariable( 'validation_error_type', $validationResult['type'] );
        $tpl->setVariable( 'validation_error_message', $validationResult['message'] );
    }
}

$tpl->setVariable( 'tab', $tab );

if ($errors)
    $tpl->setVariable( 'errors', $errors );

$Result['content'] = $tpl->fetch( "design:noveniniupdate/edit.tpl" );
$Result['path'] = array(
                        array(  'url'   => false,
                                'text'  => ezi18n( 'extension/noveniniupdate', 'Noven advanced INI parameters' ) ) );

