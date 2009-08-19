<?php

//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: Noven INI Advanced Update
// SOFTWARE RELEASE: 1.0.0
// COPYRIGHT NOTICE: Copyright (C) 2009 - Jean-Luc Nguyen, Noven.
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

$Module =& $Params["Module"];
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

// Find environments
$envs = $dom->getElementsByTagName('env');

$environments = array();
foreach ( $envs as $env )
{
	$environments[$env->getAttribute('name')] = $env->getAttribute('comment');
}
$tpl->setVariable( 'envs', $environments );

// Selected environment
if ( $http->hasPostVariable( "selectedEnvironment" ) )
{
	$selectedEnvironment = $http->postVariable( "selectedEnvironment" );
	$tpl->setVariable( 'selected_env', $selectedEnvironment );
		
	// Find variables by environment
	$files = $dom->getElementsByTagName('file');

    $tabs = array();
    $i = 0;
	foreach ( $files as $file )
	{			
        // File	
        $tabs[$i]['path'] = $file->getAttribute('path')	;
        // Comment file
        $tabs[$i]['comment'] = $file->getAttribute('comment');
        // Find lines
        $lines = $file->getElementsByTagName('line');

        $j = 0;
	    foreach ( $lines as $line )
	    {
            // Current environment
            if ( $line->getAttribute('env') == $selectedEnvironment )
            {
                if ( in_array( $line->getAttribute('type'), array_keys( $acceptedTypes ) ) )
                {
                    $tabs[$i][$j]['block']   = $line->getAttribute('block');
                    $tabs[$i][$j]['type']    = $line->getAttribute('type');
                    $tabs[$i][$j]['name']    = $line->getAttribute('name');                    
                    $tabs[$i][$j]['comment'] = $line->getAttribute('comment');

                    switch ($line->getAttribute('type')) {
                        case 'array':
                            $tabs[$i][$j]['value'] = $line->nodeValue;
                            break;
                        default:
                            $tabs[$i][$j]['value'] = $line->getAttribute('value');
                    }
                    $j++;
                }
                else
                {
                    $errors[] = ezi18n( 'extension/noveniniupdate/error', 'Invalid INI datatype:'.' '.$line->getAttribute('type'));
                }
            }
        }
        $i++;
	}
    $tpl->setVariable( 'tabs', $tabs );
}

// Update current environment with whole XML content
if ( $Module->isCurrentAction( 'UpdateEnvButton' ) )
{
    if ( $http->hasPostVariable( "selectedEnvironment" ) )
    {
    	$selectedEnvironment = $http->postVariable( "selectedEnvironment" );

        // Let's update
        $hasError = false;
        foreach ( $tabs as $tab )
        {
            $filePath = $tab['path'];
            $path = dirname( $filePath );
            $iniFile = str_replace( '.append.php', '', basename($filePath) );
            $iniFile = str_replace( '.append', '', $iniFile );

            $ini = eZINI::instance( $iniFile . '.append', $path, null, null, null, true, true );

            foreach ( $tab as $t )
            {
                if ( is_array( $t ) )
                {
                    /*
			         * Dirty Hack to avoid the "empty value" validation bug in the validate function (kernel/settings/validation.php)
			         * If the value is empty, we replace it by a space, and then trim it AFTER the validation
			         */
                    $valueToWrite   = !empty($t['value']) ? $t['value'] : ' ';
                    $settingName    = $t['name'];
                    $settingType    = $t['type'];
                    $block          = $t['block'];
                    $hasValidationError = false;
                    require_once( 'kernel/settings/validation.php' );

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
                            $hasError = true;
                            $errorType = 'write_error';
                            $errorPath = $path;
                            $errorFilename = $iniFile . '.append.php';
                            
                            $errMsg = "Write error on file $iniFile.append.php";
                            eZLog::write($errMsg, 'noveniniupdate-error.log');
                            eZDebug::writeError($errMsg, "NovenINIUpdate");
                        }
                    }
                    else // found validation errors...
                    {
                        $hasError = true;
                        $errorType = $validationResult['type'];
                        $errorMessage = $validationResult['message'];
                        
                        $errMsg = 'Validation Error. Error Type : '.$validationResult['type'].' / Error Message : '.$validationResult['message'];
                        eZLog::write($errMsg, 'noveniniupdate-error.log');
                        eZDebug::writeError($errMsg, "NovenINIUpdate");
                    }
                }
            }            
        }

        if ( $hasError )
        {
            $tpl->setVariable( 'validation_error', true );
            $tpl->setVariable( 'validation_error_type', $errorType );
            if(isset($errorPath))
            	$tpl->setVariable( 'path', $errorPath );
            $tpl->setVariable( 'filename', $errorFilename );
            $tpl->setVariable( 'validation_error_message', $errorMessage );
        }
        else
        {
            return $Module->redirectTo( '/noveniniupdate/view/(update)/1' );
        }        
    }
}

if ( isset( $Params['Update'] ) && $Params['Update'] == 1 )
{
    $tpl->setVariable( 'confirm_label', ezi18n( 'extension/noveniniupdate/view', 'The INI parameter(s) have been updated for the selected environment' ) );
}
if ($errors)
    $tpl->setVariable( 'errors', $errors );

$Result['content'] = $tpl->fetch( "design:noveniniupdate/view.tpl" );
$Result['path'] = array(
                        array(  'url'   => false,
                                'text'  => ezi18n( 'extension/noveniniupdate', 'Noven advanced INI parameters' ) ) );

