<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Service;

use BackgroundTask;
use CLIPage;
use CMDBObject;
use ContextTag;
use DateTime;
use DBObjectSearch;
use DBObjectSet;
use Exception;
use IssueLog;
use iTopMutex;
use MetaModel;
use MySQLHasGoneAwayException;
use Page;
use ProcessFatalException;
use ReflectionClass;
use utils;
use WebPage;

class Cron
{

	/**
	 * @param CLIPage|WebPage $oP
	 * @param boolean $bVerbose
	 *
	 * @param bool $bDebug
	 *
	 * @throws \ArchivedObjectException
	 * @throws \CoreCannotSaveObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \CoreWarning
	 * @throws \MissingQueryArgument
	 * @throws \MySQLException
	 * @throws \MySQLHasGoneAwayException
	 * @throws \OQLException
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function CronExec($oP, bool $bVerbose, bool $bDebug = false)
	{
		$iStarted = time();
		$iMaxDuration = MetaModel::GetConfig()->Get('cron_max_execution_time');
		$iTimeLimit = $iStarted + $iMaxDuration;
		$iCronSleep = MetaModel::GetConfig()->Get('cron_sleep');
		$iMaxCronProcess = MetaModel::GetConfig()->Get('cron.max_process');

		if ($bVerbose) {
			$oP->p("Planned duration = $iMaxDuration seconds");
			$oP->p("Loop pause = $iCronSleep seconds");
		}

		$this->ReSyncProcesses($oP, $bVerbose, $bDebug);

		while (time() < $iTimeLimit) {
			$this->CheckMaintenanceMode($oP);

			$oNow = new DateTime();
			$sNow = $oNow->format('Y-m-d H:i:s');
			$oSearch = new DBObjectSearch('BackgroundTask');
			$oSearch->AddCondition('next_run_date', $sNow, '<=');
			$oSearch->AddCondition('status', 'active');
			$oTasks = new DBObjectSet($oSearch, ['next_run_date' => true]);

			$aTasks = [];
			if ($oTasks->CountExceeds(0)) {
				if ($bVerbose) {
					$sCount = $oTasks->Count();
					$oP->p("$sCount Tasks planned to run now ($sNow):");
					$oP->p('+---------------------------+---------+---------------------+---------------------+');
					$oP->p('| Task Class                | Status  | Last Run            | Next Run            |');
					$oP->p('+---------------------------+---------+---------------------+---------------------+');
				}
				while ($oTask = $oTasks->Fetch()) {
					$sTaskName = $oTask->Get('class_name');
					if ($this->IsTaskRunning($sTaskName)) {
						// Already running, ignore
						continue;
					}
					$aTasks[] = $oTask;
					if ($bVerbose) {
						$sStatus = $oTask->Get('status');
						$sLastRunDate = $oTask->Get('latest_run_date');
						$sNextRunDate = $oTask->Get('next_run_date');
						$oP->p(sprintf('| %1$-25.25s | %2$-7s | %3$-19s | %4$-19s |', $sTaskName, $sStatus, $sLastRunDate, $sNextRunDate));
					}
				}
				if ($bVerbose) {
					$oP->p('+---------------------------+---------+---------------------+---------------------+');
				}
				$aRunTasks = [];
				while ($aTasks != []) {
					$oTask = array_shift($aTasks);
					$sTaskClass = $oTask->Get('class_name');

					// Check if the current task is running
					$oTaskMutex = $this->GetTaskMutex($sTaskClass);
					if (!$oTaskMutex->TryLock()) {
						// Task is already running, try next one
						continue;
					}

					$aRunTasks[] = $sTaskClass;

					// N°3219 for each process will use a specific CMDBChange object with a specific track info
					// Any BackgroundProcess can override this as needed
					CMDBObject::SetCurrentChangeFromParams("Background task ($sTaskClass)");

					// Run the task and record its next run time
					if ($bVerbose) {
						$oNow = new DateTime();
						$oP->p(">> === ".$oNow->format('Y-m-d H:i:s').sprintf(" Starting:%-'=49s", ' '.$sTaskClass.' '));
					}
					try {
						$sMessage = $this->RunTask($oTask, $iTimeLimit);
					}
					catch (MySQLHasGoneAwayException $e) {
						$oP->p("ERROR : 'MySQL has gone away' thrown when processing $sTaskClass  (error_code=".$e->getCode().")");
						exit(EXIT_CODE_FATAL);
					}
					catch (ProcessFatalException $e) {
						$oP->p("ERROR : an exception was thrown when processing '$sTaskClass' (".$e->getInfoLog().")");
						IssueLog::Error("Cron.php error : an exception was thrown when processing '$sTaskClass' (".$e->getInfoLog().')');
					}
					finally {
						$oTaskMutex->Unlock();
					}
					if ($bVerbose) {
						if (!empty($sMessage)) {
							$oP->p("$sTaskClass: $sMessage");
						}
						$oEnd = new DateTime();
						$sNextRunDate = $oTask->Get('next_run_date');
						$oP->p("<< === ".$oEnd->format('Y-m-d H:i:s').sprintf(" End of:  %-'=42s", ' '.$sTaskClass.' ')." Next: $sNextRunDate");
					}
					if (time() > $iTimeLimit) {
						break 2;
					}
					$this->CheckMaintenanceMode($oP);

					// Reindex tasks every time if multiple cron are running
					if ($iMaxCronProcess > 1) {
						break;
					}
				}

				// Tasks to run later
				if ($bVerbose && $aTasks == []) {
					$oP->p('--');
					$oSearch = new DBObjectSearch('BackgroundTask');
					$oSearch->AddCondition('next_run_date', $sNow, '>');
					$oSearch->AddCondition('status', 'active');
					$oTasks = new DBObjectSet($oSearch, ['next_run_date' => true]);
					while ($oTask = $oTasks->Fetch()) {
						if (!in_array($oTask->Get('class_name'), $aRunTasks)) {
							$oP->p(sprintf("-- Skipping task: %-'-40s", $oTask->Get('class_name').' ')." until: ".$oTask->Get('next_run_date'));
						}
					}
				}
			}
			if ($aTasks == []) {
				if ($bVerbose) {
					$oP->p("Sleeping...\n");
				}
				sleep($iCronSleep);
			}
		}
		if ($bVerbose) {
			$oP->p('');
			self::DisplayStatus($oP, ['next_run_date' => true]);
			$oP->p("Reached normal execution time limit (exceeded by ".(time() - $iTimeLimit)."s)");
		}
	}

	/**
	 * @param $oP
	 * @param $bVerbose
	 * @param $bDebug
	 *
	 * @throws \ArchivedObjectException
	 * @throws \CoreCannotSaveObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \CoreWarning
	 * @throws \MySQLException
	 * @throws \OQLException
	 * @throws \ReflectionException
	 */
	protected function ReSyncProcesses($oP, $bVerbose, $bDebug)
	{
		// Enumerate classes implementing BackgroundProcess
		//
		$oSearch = new DBObjectSearch('BackgroundTask');
		$oTasks = new DBObjectSet($oSearch);
		$aTasks = array();
		while ($oTask = $oTasks->Fetch()) {
			$aTasks[$oTask->Get('class_name')] = $oTask;
		}
		$oNow = new DateTime();

		$aProcesses = array();
		foreach (utils::GetClassesForInterface('iProcess', '', ['lib']) as $sTaskClass) {
			$oRefClass = new ReflectionClass($sTaskClass);
			if ($oRefClass->isAbstract()) {
				continue;
			}

			$oProcess = new $sTaskClass;
			$aProcesses[$sTaskClass] = $oProcess;

			// Create missing entry if needed
			if (!array_key_exists($sTaskClass, $aTasks)) {
				// New entry, let's create a new BackgroundTask record, and plan the first execution
				$oTask = new BackgroundTask();
				$oTask->SetDebug($bDebug);
				$oTask->Set('class_name', $sTaskClass);
				$oTask->Set('total_exec_count', 0);
				$oTask->Set('min_run_duration', 99999.999);
				$oTask->Set('max_run_duration', 0);
				$oTask->Set('average_run_duration', 0);
				$oRefClass = new ReflectionClass($sTaskClass);
				if ($oRefClass->implementsInterface('iScheduledProcess')) {
					$oNextOcc = $oProcess->GetNextOccurrence();
					$oTask->Set('next_run_date', $oNextOcc->format('Y-m-d H:i:s'));
				} else {
					// Background processes do start asap, i.e. "now"
					$oTask->Set('next_run_date', $oNow->format('Y-m-d H:i:s'));
				}
				if ($bVerbose) {
					$oP->p('Creating record for: '.$sTaskClass);
					$oP->p('First execution planned at: '.$oTask->Get('next_run_date'));
				}
				$oTask->DBInsert();
			} else {
				/** @var \BackgroundTask $oTask */
				$oTask = $aTasks[$sTaskClass];
				if ($oTask->Get('next_run_date') == '3000-01-01 00:00:00') {
					// check for rescheduled tasks
					$oRefClass = new ReflectionClass($sTaskClass);
					if ($oRefClass->implementsInterface('iScheduledProcess')) {
						$oNextOcc = $oProcess->GetNextOccurrence();
						$oTask->Set('next_run_date', $oNextOcc->format('Y-m-d H:i:s'));
						$oTask->DBUpdate();
					}
				}
				// Reactivate task if necessary
				if ($oTask->Get('status') == 'removed') {
					$oTask->Set('status', 'active');
					$oTask->DBUpdate();
				}
				// task having a real class to execute
				unset($aTasks[$sTaskClass]);
			}
		}

		// Remove all the tasks not having a valid class
		foreach ($aTasks as $oTask) {
			$sTaskClass = $oTask->Get('class_name');
			if (!class_exists($sTaskClass)) {
				$oTask->Set('status', 'removed');
				$oTask->DBUpdate();
			}
		}

		if ($bVerbose) {
			$aDisplayProcesses = array();
			foreach ($aProcesses as $oExecInstance) {
				$aDisplayProcesses[] = get_class($oExecInstance);
			}
			$sDisplayProcesses = implode(', ', $aDisplayProcesses);
			$oP->p("Background processes: ".$sDisplayProcesses);
		}
	}

	/**
	 * @param \WebPage $oP
	 */
	protected function CheckMaintenanceMode(Page $oP)
	{
		// Verify files instead of reloading the full config each time
		if (file_exists(MAINTENANCE_MODE_FILE) || file_exists(READONLY_MODE_FILE)) {
			$oP->p("Maintenance detected, exiting");
			exit(EXIT_CODE_ERROR);
		}
	}

	/**
	 * @param \BackgroundTask $oTask
	 * @param int $iTimeLimit
	 *
	 * @return string
	 * @throws \ArchivedObjectException
	 * @throws \CoreCannotSaveObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \MySQLHasGoneAwayException
	 * @throws \ProcessFatalException
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function RunTask(BackgroundTask $oTask, int $iTimeLimit): string
	{
		$TaskClass = $oTask->Get('class_name');
		$oProcess = new $TaskClass;
		$oRefClass = new ReflectionClass(get_class($oProcess));
		$oDateStarted = new DateTime();
		$fStart = microtime(true);
		$oCtx = new ContextTag('CRON:Task:'.$TaskClass);

		$sMessage = '';
		$oExceptionToThrow = null;
		try {
			// Record (when starting) that this task was started, just in case it crashes during the execution
			if ($oTask->Get('total_exec_count') == 0) {
				// First execution
				$oTask->Set('first_run_date', $oDateStarted->format('Y-m-d H:i:s'));
			}
			$oTask->Set('latest_run_date', $oDateStarted->format('Y-m-d H:i:s'));
			// Record the current user running the cron
			$oTask->Set('system_user', utils::GetCurrentUserName());
			$oTask->Set('running', 1);
			// Compute the next run date
			if ($oRefClass->implementsInterface('iScheduledProcess')) {
				// Schedules process do repeat at specific moments
				$oPlannedStart = $oProcess->GetNextOccurrence();
			} else {
				// Background processes do repeat periodically
				$oDatePlanned = new DateTime($oTask->Get('next_run_date'));
				$oPlannedStart = clone $oDatePlanned;
				// Let's schedule from the previous planned date of execution to avoid shift
				$oPlannedStart->modify('+'.$oProcess->GetPeriodicity().' seconds');
				$oNow = new DateTime();
				while ($oPlannedStart->format('U') <= $oNow->format('U')) {
					// Next planned start is already in the past, increase it again by a period
					$oPlannedStart = $oPlannedStart->modify('+'.$oProcess->GetPeriodicity().' seconds');
				}
			}
			$oTask->Set('next_run_date', $oPlannedStart->format('Y-m-d H:i:s'));
			$oTask->DBUpdate();

			$sMessage = $oProcess->Process($iTimeLimit);
		}
		catch (MySQLHasGoneAwayException $e) {
			throw $e;
		}
		catch (ProcessFatalException $e) {
			$oExceptionToThrow = $e;
		}
		catch (Exception $e) // we shouldn't get so many exceptions... but we need to handle legacy code, and cron.php has to keep running
		{
			if ($oTask->IsDebug()) {
				$sMessage = 'Processing failed with message: '.$e->getMessage().'. '.$e->getTraceAsString();
			} else {
				$sMessage = 'Processing failed with message: '.$e->getMessage();
			}
		}
		finally {
			$oTask->Set('running', 0);
			$fDuration = microtime(true) - $fStart;
			$oTask->ComputeDurations($fDuration); // does increment the counter and compute statistics
			$oTask->DBUpdate();
		}

		if ($oExceptionToThrow) {
			throw $oExceptionToThrow;
		}

		unset($oCtx);

		return $sMessage;
	}

	/**
	 * @param CLIPage|WebPage $oP
	 * @param array $aTaskOrderBy
	 *
	 * @throws \ArchivedObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \MySQLException
	 * @throws \OQLException
	 */
	public static function DisplayStatus($oP, array $aTaskOrderBy = [])
	{
		$oSearch = new DBObjectSearch('BackgroundTask');
		$oTasks = new DBObjectSet($oSearch, $aTaskOrderBy);
		$oP->p('+---------------------------+---------+---------------------+---------------------+--------+-----------+');
		$oP->p('| Task Class                | Status  | Last Run            | Next Run            | Nb Run | Avg. Dur. |');
		$oP->p('+---------------------------+---------+---------------------+---------------------+--------+-----------+');
		while ($oTask = $oTasks->Fetch()) {
			$sTaskName = $oTask->Get('class_name');
			$sStatus = $oTask->Get('status');
			$sLastRunDate = $oTask->Get('latest_run_date');
			$sNextRunDate = $oTask->Get('next_run_date');
			$iNbRun = (int)$oTask->Get('total_exec_count');
			$sAverageRunTime = $oTask->Get('average_run_duration');
			$oP->p(sprintf('| %1$-25.25s | %2$-7s | %3$-19s | %4$-19s | %5$6d | %6$7s s |', $sTaskName, $sStatus,
				$sLastRunDate, $sNextRunDate, $iNbRun, $sAverageRunTime));
		}
		$oP->p('+---------------------------+---------+---------------------+---------------------+--------+-----------+');
	}

	/**
	 * @param $sTaskClass
	 *
	 * @return \iTopMutex
	 */
	protected function GetTaskMutex($sTaskClass): iTopMutex
	{
		return new iTopMutex("cron_$sTaskClass");
	}

	/**
	 * @param $sTaskName
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function IsTaskRunning($sTaskName): bool
	{
		$oTaskMutex = $this->GetTaskMutex($sTaskName);
		return $oTaskMutex->IsLocked();
	}
}