<?php

namespace Combodo\iTop\Test\UnitTest\Setup;

use CMDBSource;
use Combodo\iTop\Test\UnitTest\ItopTestCase;
use DBBackup;
use utils;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 */
class DBBackupTest extends ItopTestCase
{
	/**
	 * @throws \CoreException
	 * @throws \MySQLException
	 * @throws \ConfigException
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->RequireOnceItopFile('setup/backup.class.inc.php');

		// We need a connection to the DB, so let's open it !
		// We are using the default config file... as the server might not be configured for all the combination we are testing
		// For example dev env and ci env won't accept TLS connection
		$oConfigOnDisk = utils::GetConfig();
		CMDBSource::InitFromConfig($oConfigOnDisk);
	}

	/**
	 * No TLS connection = no additional CLI args !
	 *
	 * @throws \CoreException
	 * @throws \ConfigException
	 * @throws \MySQLException
	 */
	public function testGetMysqlCliTlsOptionsNoTls()
	{
		$oConfigToTest = utils::GetConfig();

		$oConfigToTest->Set('db_tls.enabled', false);
		$sCliArgsNoTls = DBBackup::GetMysqlCliTlsOptions($oConfigToTest);

		$this->assertEmpty($sCliArgsNoTls);
	}

	/**
	 * TLS connection configured = we need one CLI arg
	 *
	 * @return void
	 * @throws \ConfigException
	 * @throws \CoreException
	 */
	public function testGetMysqlCliTlsOptionsWithTlsNoCa()
	{
		$oConfigToTest = utils::GetConfig();
		$oConfigToTest->Set('db_tls.enabled', true);
		$sCliArgsMinCfg = DBBackup::GetMysqlCliTlsOptions($oConfigToTest);

		// depending on the MySQL vendor, we would have `--ssl` or `--ssl-mode=REQUIRED`
		if (CMDBSource::IsSslModeDBVersion())
		{
			$this->assertStringStartsWith(' --ssl-mode=REQUIRED', $sCliArgsMinCfg);
		}
		else
		{
			$this->assertStringStartsWith(' --ssl', $sCliArgsMinCfg);
			$this->assertStringNotContainsString('--ssl-mode', $sCliArgsMinCfg);
		}
	}

	/**
	 * TLS connection configured + CA option = we need multiple CLI args
	 *
	 * @return void
	 * @throws \ConfigException
	 * @throws \CoreException
	 */
	public function testGetMysqlCliTlsOptionsWithTlsAndCa()
	{
		$oConfigToTest = utils::GetConfig();
		$sTestCa = 'my_test_ca';

		$oConfigToTest->Set('db_tls.enabled', true);
		$oConfigToTest->Set('db_tls.ca', $sTestCa);
		$sCliArgsCapathCfg = DBBackup::GetMysqlCliTlsOptions($oConfigToTest);

		// depending on the MySQL vendor, we would have `--ssl` or `--ssl-mode=VERIFY_CA`
		if (CMDBSource::IsSslModeDBVersion())
		{
			$this->assertStringStartsWith(' --ssl-mode=VERIFY_CA', $sCliArgsCapathCfg);
		}
		else
		{
			$this->assertStringStartsWith(' --ssl', $sCliArgsCapathCfg);
			$this->assertStringNotContainsString('--ssl-mode', $sCliArgsCapathCfg);

		}
		$this->assertStringEndsWith('--ssl-ca='.DBBackup::EscapeShellArg($sTestCa), $sCliArgsCapathCfg);
	}
}
