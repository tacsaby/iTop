<?php
/**
 * Copyright (C) 2013-2020 Combodo SARL
 * This file is part of iTop.
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 */

namespace Combodo\iTop\Test\UnitTest\Integration;

use Combodo\iTop\Test\UnitTest\ItopTestCase;


class DictionariesConsistencyTest extends ItopTestCase
{
	/**
	 * Verify that language declarations match the file names (same language codes)
	 *
	 * @dataProvider DictionaryFileProvider
	 *
	 * @param $sDictFile
	 */
	public function testDictionariesLanguage($sDictFile)
	{
		$aPrefixToLanguageData = array(
			'cs'    => array('CS CZ', 'Czech', 'Čeština'),
			'da'    => array('DA DA', 'Danish', 'Dansk'),
			'de'    => array('DE DE', 'German', 'Deutsch'),
			'en'    => array('EN US', 'English', 'English'),
			'es_cr' => array('ES CR', 'Spanish', array(
				'Español, Castellaño', // old value
				'Español, Castellano', // new value since N°3635
			)),
			'fr'    => array('FR FR', 'French', 'Français'),
			'hu'    => array('HU HU', 'Hungarian', 'Magyar'),
			'it'    => array('IT IT', 'Italian', 'Italiano'),
			'ja'    => array('JA JP', 'Japanese', '日本語'),
			'nl'    => array('NL NL', 'Dutch', 'Nederlands'),
			'pt_br' => array('PT BR', 'Brazilian', 'Brazilian'),
			'ru'    => array('RU RU', 'Russian', 'Русский'),
			'sk'    => array('SK SK', 'Slovak', 'Slovenčina'),
			'tr'    => array('TR TR', 'Turkish', 'Türkçe'),
			'zh_cn' => array('ZH CN', 'Chinese', '简体中文'),
		);

		if (!preg_match('/^(.*)\\.dict/', basename($sDictFile), $aMatches))
		{
			static::fail("Dictionary file '$sDictFile' not matching the naming convention");
		}

		$sLangPrefix = $aMatches[1];
		if (!array_key_exists($sLangPrefix, $aPrefixToLanguageData))
		{
			static::fail("Unknown prefix '$sLangPrefix' for dictionary file '$sDictFile'");
		}

		$sExpectedLanguageCode = $aPrefixToLanguageData[$sLangPrefix][0];
		$sExpectedEnglishLanguageDesc = $aPrefixToLanguageData[$sLangPrefix][1];
		$aExpectedLocalizedLanguageDesc = $aPrefixToLanguageData[$sLangPrefix][2];

		$sDictPHP = file_get_contents($sDictFile);
		if ($iCount = (preg_match_all("@Dict::Add\('(.*)'\s*,\s*'(.*)'\s*,\s*'(.*)'@", $sDictPHP, $aMatches) === false))
		{
			static::fail("Pattern not working");
		}
		if ($iCount == 0)
		{
			// Empty dictionary, that's fine!
			static::assertTrue(true);
		}
		foreach ($aMatches[1] as $sLanguageCode)
		{
			static::assertSame($sExpectedLanguageCode, $sLanguageCode,
				"Unexpected language code for Dict::Add in dictionary $sDictFile");
		}
		foreach ($aMatches[2] as $sEnglishLanguageDesc)
		{
			static::assertSame($sExpectedEnglishLanguageDesc, $sEnglishLanguageDesc,
				"Unexpected language description (english) for Dict::Add in dictionary $sDictFile");
		}
		foreach ($aMatches[3] as $sLocalizedLanguageDesc)
		{
			if (false === is_array($aExpectedLocalizedLanguageDesc)) {
				$aExpectedLocalizedLanguageDesc = array($aExpectedLocalizedLanguageDesc);
			}
			static::assertContains($sLocalizedLanguageDesc,$aExpectedLocalizedLanguageDesc,
				"Unexpected language description for Dict::Add in dictionary $sDictFile");
		}
	}

	public function DictionaryFileProvider()
	{
		static::setUp();

		$aDictFiles = array_merge(
			glob(APPROOT.'datamodels/2.x/*/*.dict*.php'), // legacy form in modules
			glob(APPROOT.'datamodels/2.x/*/dictionaries/*.dict*.php'), // modern form in modules
			glob(APPROOT.'dictionaries/*.dict*.php') // framework
		);
		$aTestCases = array();
		foreach ($aDictFiles as $sDictFile) {
			$aTestCases[$sDictFile] = array('sDictFile' => $sDictFile);
		}

		return $aTestCases;
	}


	/**
	 * return a map linked to *.dict.php files that are generated after setup
	 * each entry key is lang code (example 'en')
	 * each value is an array with lang code (again) and dict file path
	 *
	 * @return array
	 */
	private function GetDictFiles(): array
	{
		$aDictFiles = [];

		foreach (glob(APPROOT.'env-'.\utils::GetCurrentEnvironment().'/dictionaries/*.dict.php') as $sDictFile) {
			if (preg_match('/.*\\/(.*).dict.php/', $sDictFile, $aMatches)) {
				$sLangCode = $aMatches[1];
				$aDictFiles[$sLangCode] = [
					'lang' => $sLangCode,
					'file' => $sDictFile,
				];
			}
		}

		return $aDictFiles;
	}

	/**
	 * return a map generated with all *dict.php files content
	 * each entry key is the lang code (example 'en)
	 * each value is an array with localization code (ex. 'EN US') and a map of label key/values
	 * map is sorted by keys: en is first, then fr and then other lang code
	 *
	 * @return array
	 */
	private function ReadAllDictKeys(): array
	{
		clearstatcache();
		$aDictFiles = $this->GetDictFiles();
		$aDictEntries = [];
		$aTmpValue = [];
		foreach ($aDictFiles as $sCode => $aData) {
			$sContent = file_get_contents($aData['file']);
			$sReplacedContent = str_replace('Dict::SetEntries(', "\$aTmpValue['$sCode'] = array(", $sContent);
			$sTempFilePath = tempnam(sys_get_temp_dir(), 'tmp_dict').'.php';
			file_put_contents($sTempFilePath, $sReplacedContent);
			require_once($sTempFilePath);
			unlink($sTempFilePath);

			$aDictEntries[$sCode] = $aTmpValue[$sCode];
		}

		uksort($aDictEntries, function (string $sLangCode1, string $sLangCode2) {
			$sEnUsCode = "en-us";
			$sFrCode = "fr-fr";

			if ($sLangCode1 === $sEnUsCode) {
				return -1;
			}
			if ($sLangCode2 === $sEnUsCode) {
				return 1;
			}
			if ($sLangCode1 === $sFrCode) {
				return -1;
			}
			if ($sLangCode2 === $sFrCode) {
				return 1;
			}

			return ($sLangCode1 < $sLangCode2) ? 1 : -1;
		});

		return $aDictEntries;
	}

	/**
	 * this test checks that there are the exact same count of labels between 'en'
	 * it checks also that label keys are the same as well (we could have the same count with 'toto' label key on 'en' side and 'titi' on another dict side)
	 */
	public function testDictEntryKeys()
	{
		$aDictEntries = $this->ReadAllDictKeys();
		$this->assertNotEquals([], $aDictEntries, "No entries found from *.dict.php");

		$sPreviousCode = null;
		$sPreviousSize = null;
		$sPreviousKeys = null;
		foreach ($aDictEntries as $sCode => $aData) {
			$aLabelEntries = $aData[1];
			$iCurrentSize = sizeof($aLabelEntries);
			$aCurrentKeys = array_keys($aLabelEntries);
			sort($aCurrentKeys);

			if ($sPreviousCode === null) {
				$sPreviousCode = $sCode;
				$sPreviousSize = $iCurrentSize;
				$aPreviousKeys = $aCurrentKeys;
			} else {
				$this->assertEquals($sPreviousSize, $iCurrentSize, "$sPreviousCode and $sCode  dictionnaries dont have the same amount of labels ($iCurrentSize vs $sPreviousSize)");
				$this->assertEquals($aPreviousKeys, $aCurrentKeys, "$sPreviousCode and $sCode dictionnaries dont have the same label keys");
			}
		}
	}

	public function DictEntryValuesProvider()
	{
		//first entry should be linked to 'en' dictionnary
		//it is linked to sorting order used on ReadAllDictKeys
		$aFirstDictEntry = [];
		$sFirstEntryCode = null;

		$aUseCases = [];

		foreach ($this->ReadAllDictKeys() as $sCode => $aDictEntry) {
			if (null === $sFirstEntryCode) {
				$sFirstEntryCode = $sCode;
				$aFirstDictEntry = $aDictEntry;
			} else {
				$aUseCases[$sCode] = [
					'firstDict'   => $aFirstDictEntry,
					'firstCode'   => $sFirstEntryCode,
					'currentCode' => $sCode,
					'currentDict' => $aDictEntry,
				];
			}
		}

		return $aUseCases;
	}

	/**
	 * foreach dictionnary label map (key/value) it counts the number argument that should be passed to use Dict::Format
	 * examples:
	 *  for "gabu zomeu" label there are no args
	 *  for "shadok %1 %2 %3" there are 3 args
	 *
	 * limitation: there is no validation check for "%3 itop %2 combodo" which seems unconsistent
	 *
	 * @param $aDictEntry
	 *
	 * @return array
	 */
	private function GetKeyArgCountMap($aDictEntry): array
	{
		$aKeyArgsCount = [];
		$aLabelEntries = $aDictEntry[1];
		foreach ($aLabelEntries as $sKey => $sValue) {
			$iMaxIndex = 0;
			if (preg_match_all("/%(\d*)/", $sValue, $aMatches)) {
				$aSubMatches = $aMatches[1];
				if (is_array($aSubMatches)) {
					foreach ($aSubMatches as $aCurrentMatch) {
						$iIndex = $aCurrentMatch;
						$iMaxIndex = ($iMaxIndex < $iIndex) ? $iIndex : $iMaxIndex;
					}
				}
			}

			$aKeyArgsCount[$sKey] = $iMaxIndex;
		}

		return $aKeyArgsCount;
	}

	/**
	 * compare en and other dictionnaries and check that for all labels there it the same number of arguments
	 * if not Dict::Format could raise an exception for some languages. translation should be done again...
	 *
	 * @dataProvider DictEntryValuesProvider
	 */
	public function testDictEntryValues($aFirstDictEntry, $sFirstEntryCode, $sCode, $aDictEntry)
	{
		$sErrorMsg = "$sFirstEntryCode and $sCode dictionnaries dont have the same args provided to Dict::Format method!";

		$aKeyArgsCountMap = [];
		$aKeyArgsCountMap[$sFirstEntryCode] = $this->GetKeyArgCountMap($aFirstDictEntry);
		$aKeyArgsCountMap[$sCode] = $this->GetKeyArgCountMap($aDictEntry);

		$aMismatchedKeys = [];
		//$aMismatchedValues = [];
		foreach ($aKeyArgsCountMap[$sFirstEntryCode] as $sKey => $iCount) {
			if (array_key_exists($sKey, $aKeyArgsCountMap[$sCode])) {
				//if not array_key_exists => the previous test testDictEntryKeys will break
				//we dont care here
				if ($iCount != $aKeyArgsCountMap[$sCode][$sKey]) {
					$aMismatchedKeys[] = $sKey;
				}
			}
		}

		$iNbMismatchedKeys = count($aMismatchedKeys);
		if ($iNbMismatchedKeys > 0) {
			echo "Language $sCode : $iNbMismatchedKeys mismatched keys\n\n";
		}
		foreach ($aMismatchedKeys as $sKey) {
			/** @noinspection ForgottenDebugOutputInspection */
			var_dump([
				$sKey,
				$sFirstEntryCode => $aFirstDictEntry[1][$sKey],
				$sCode           => $aDictEntry[1][$sKey],
			]);
		}
		$this->assertEquals([], $aMismatchedKeys, $sErrorMsg);
	}
}
