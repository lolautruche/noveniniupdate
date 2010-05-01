==============================
 NovenINIUpdate documentation 
==============================

--------------------------------------------------
 2009 Jean-Luc Nguyen, Jerome Vieilledent - Noven
--------------------------------------------------

Introduction
============

This extension allows to update **INI files** from an XML source. It uses the INI API available in eZ Publish kernel. It is mostly useful for update INI files depending on the website environment.
It provides a module to use in the backoffice and a CLI script.
Cluster Mode is supported so that the *index_cluster.php* file will also be updated. Settings for cluster mode are separated in the XML file


Installation
============

  - Download the compressed file under *extension/* directory and uncompress it.
  - Activate the extension.
  - Clear the caches
  - Re-build the class autoload array :

*Shell*
::
  php bin/php/ezpgenerateautoloads.php -e


Configuration
=============

Edit the XML file with your needs, which path is defined in *extension/noveniniupdate/setting/noveniniupdate.ini*, variable: **XmlContent** (please make an override). 
The XML sample is here: *extension/noveniniupdate/source/sample.xml*.
Be sure to get the same XML structure, only values that are different depending on the environment and check all values.
If you aren't using the Cluster Mode, you can ignore the *<ClusterMode>* tag in the XML file.

Warning
-------
Some INI settings can be marked as read only in site.ini.
Check site.ini **[eZINISettings]** / *ReadonlySettingList[]*.

Default values are :

*site.ini*
::

  [eZINISettings]
  ReadonlySettingList[]
  ReadonlySettingList[]=template.ini/PHP/PHPOperatorList
  ReadonlySettingList[]=image.ini/ImageMagick/ExecutablePath
  ReadonlySettingList[]=image.ini/ImageMagick/Executable


How to Use in the Backoffice
============================

  - You should have a new *INI Config* tab on the backoffice.
  - Select the environment where to update the INI files defined on the XML file.
  - You may check the new values on the setup tab, under INI parameters.
  - If everything's OK, click the **Udpdate Environment** button, under all listed INI parameters


How to use using the CLI script
===============================

The CLI script is located in *extension/noveniniupdate/bin/php/noviniupdate.php*
It uses *eZComponents Console Tools*

Several options are available :
  * --list-envs : Lists all available environments
  * --env=VALUE --list-params : Lists all params configured for a given environment
  * --env=VALUE --diff : Shows differences between what is currently configured and what will be configured
  * --env=VALUE : Updates the INI files for the given environment
  * --backup : If used with *--env* option, creates a backup of your config files in your backup directory, defined in noveniniupdate.ini
