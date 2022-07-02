<?php
/**
 * Copyright (C) 2013-2021 Combodo SARL
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 */

use Combodo\iTop\Service\Cron;

if (!defined('__DIR__')) define('__DIR__', dirname(__FILE__));
require_once(__DIR__.'/../approot.inc.php');

const EXIT_CODE_ERROR = -1;
const EXIT_CODE_FATAL = -2;
// early exit
if (file_exists(READONLY_MODE_FILE))
{
	echo "iTop is read-only. Exiting...\n";
	exit(EXIT_CODE_ERROR);
}

require_once(APPROOT.'/application/application.inc.php');
require_once(APPROOT.'/core/background.inc.php');

$sConfigFile = APPCONF.ITOP_DEFAULT_ENV.'/'.ITOP_CONFIG_FILE;
if (!file_exists($sConfigFile))
{
	echo "iTop is not yet installed. Exiting...\n";
	exit(EXIT_CODE_ERROR);
}

require_once(APPROOT.'/application/startup.inc.php');

$oCtx = new ContextTag(ContextTag::TAG_CRON);

function ReadMandatoryParam($oP, $sParam, $sSanitizationFilter = 'parameter')
{
	$sValue = utils::ReadParam($sParam, null, true, $sSanitizationFilter);
	if (is_null($sValue))
	{
		$oP->p("ERROR: Missing argument '$sParam'\n");
		UsageAndExit($oP);
	}

	return trim($sValue);
}

function UsageAndExit($oP)
{
	$bModeCLI = ($oP instanceof CLIPage);

	if ($bModeCLI)
	{
		$oP->p("USAGE:\n");
		$oP->p("php cron.php --auth_user=<login> --auth_pwd=<password> [--param_file=<file>] [--verbose=1] [--debug=1] [--status_only=1]\n");
	}
	else
	{
		$oP->p("Optional parameters: verbose, param_file, status_only\n");
	}
	$oP->output();
	exit(EXIT_CODE_FATAL);
}

////////////////////////////////////////////////////////////////////////////////
//
// Main
//

set_time_limit(0); // Some background actions may really take long to finish (like backup)

$bIsModeCLI = utils::IsModeCLI();
if ($bIsModeCLI)
{
	$oP = new CLIPage("iTop - cron");

	SetupUtils::CheckPhpAndExtensionsForCli($oP, EXIT_CODE_FATAL);
}
else
{
	$oP = new WebPage("iTop - cron");
}

try
{
	utils::UseParamFile();

	$bVerbose = utils::ReadParam('verbose', false, true /* Allow CLI */);
	$bDebug = utils::ReadParam('debug', false, true /* Allow CLI */);

	if ($bIsModeCLI)
	{
		// Next steps:
		//   specific arguments: 'csv file'
		//
		$sAuthUser = ReadMandatoryParam($oP, 'auth_user', 'raw_data');
		$sAuthPwd = ReadMandatoryParam($oP, 'auth_pwd', 'raw_data');
		if (UserRights::CheckCredentials($sAuthUser, $sAuthPwd))
		{
			UserRights::Login($sAuthUser); // Login & set the user's language
		}
		else
		{
			$oP->p("Access wrong credentials ('$sAuthUser')");
			$oP->output();
			exit(EXIT_CODE_ERROR);
		}
	}
	else
	{
		require_once(APPROOT.'/application/loginwebpage.class.inc.php');
		LoginWebPage::DoLogin(); // Check user rights and prompt if needed
	}

	if (!UserRights::IsAdministrator())
	{
		$oP->p("Access restricted to administrators");
		$oP->Output();
		exit(EXIT_CODE_ERROR);
	}


	if (utils::ReadParam('status_only', false, true /* Allow CLI */))
	{
		// Display status and exit
		Cron::DisplayStatus($oP);
		exit(0);
	}

	require_once(APPROOT.'core/mutex.class.inc.php');
	$oP->p("Starting: ".time().' ('.date('Y-m-d H:i:s').')');
}
catch (Exception $e)
{
	$oP->p("Error: ".$e->GetMessage());
	$oP->output();
	exit(EXIT_CODE_FATAL);
}

try
{
	if (!MetaModel::DBHasAccess(ACCESS_ADMIN_WRITE))
	{
		$oP->p("A maintenance is ongoing");
	}
	else
	{
		// Limit the number of cron process to run in parallel
		$iMaxCronProcess = MetaModel::GetConfig()->Get('cron.max_process');
		$bCanRun = false;
		for ($i = 0; $i < $iMaxCronProcess; $i++) {
			$oMutex = new iTopMutex("cron#$i");
			if ($oMutex->TryLock()) {
				$bCanRun = true;
				break;
			}
		}
		if ($bCanRun) {
			$oCron = new Cron();
			$oCron->CronExec($oP, $bVerbose, $bDebug);
		} else {
			$oP->p("Already $iMaxCronProcess are running...");
		}
	}
}
catch (Exception $e)
{
	$oP->p("ERROR: '".$e->getMessage()."'");
	if ($bDebug)
	{
		// Might contain verb parameters such a password...
		$oP->p($e->getTraceAsString());
	}
}

$oP->p("Exiting: ".time().' ('.date('Y-m-d H:i:s').')');
$oP->Output();
