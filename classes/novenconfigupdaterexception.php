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

class NovenConfigUpdaterException extends Exception
{
	const XML_FILE_UNAVAILABLE = -1,
		  XML_FILE_UNREADABLE = -2,
		  XML_PARSE_ERROR = -3;
		  
	const UNSUPPORTED_ENV = -10,
		  UNSUPPORTED_DATATYPE = -11,
		  UNSUPPORTED_FILE_HANDLER = -12;
		  
	const FILE_IO_ERROR = -20;
	
	const CLUSTER_NOT_CONFIGURED = -30;
	
	public function __toString()
	{
		return __CLASS__ . " [$this->code] => $this->message\n";
	}
	
	/**
	 * SimpleXML errors handling
	 *
	 * @param int $errno PHP error number
	 * @param string $errstr error message
	 * @param string $errfile
	 * @param int $errline
	 * @return bool
	 * @throws NovenINIUpdaterException
	 */
	public static function HandleSimpleXMLError($errno, $errstr, $errfile, $errline)
	{
		if (substr_count($errstr,"SimpleXMLElement::__construct()")>0)
			throw new self($errstr, self::XML_PARSE_ERROR);
		else
			return false;
	}
}