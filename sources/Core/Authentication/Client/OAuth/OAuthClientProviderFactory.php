<?php

namespace Combodo\iTop\Core\Authentication\Client\OAuth;

use MetaModel;

class OAuthClientProviderFactory {
	public static function getProviderForSMTP()
	{
		$sProviderVendor = MetaModel::GetConfig()->Get('email_transport_smtp.oauth.provider'); // email_transport_smtp.oauth.provider
		$aProviderVendorParams = [
			'clientId'     => MetaModel::GetConfig()->Get('email_transport_smtp.oauth.client_id'),  // email_transport_smtp.oauth.client_id
			'clientSecret' => MetaModel::GetConfig()->Get('email_transport_smtp.oauth.client_secret'),// email_transport_smtp.oauth.client_secret
			'redirectUri'  => OAuthClientProviderGoogle::GetRedirectUri(), // ?
		];
		$aAccessTokenParams = [
			"access_token"  => MetaModel::GetConfig()->Get('email_transport_smtp.oauth.access_token'), // email_transport_smtp.oauth.access_token
			"refresh_token" => MetaModel::GetConfig()->Get('email_transport_smtp.oauth.refresh_token'), // email_transport_smtp.oauth.refresh_token
		];

		$sProviderClass = "\Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProvider".$sProviderVendor;

		return new $sProviderClass($aProviderVendorParams, $aAccessTokenParams);
	}

	public static function getVendorProviderForAccessUrl($sProviderVendor, $sClientId, $sClientSecret, $sScope){
		$sRedirectUrl = 'http://localhost/iTop/pages/oauth.landing.php';
		$sProviderClass = "\Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProvider".$sProviderVendor;
		
		$oProvider = new $sProviderClass(['clientId' => $sClientId, 'clientSecret' => $sClientSecret, 'redirectUri' => $sRedirectUrl, 'scope' => $sScope]);
		return $oProvider->GetVendorProvider()->getAuthorizationUrl([
			'scope' => [
				$sScope
			],
		]);
	}

	public static function getConfFromRedirectUrl($sProviderVendor, $sClientId, $sClientSecret, $sRedirectUrlQuery)
	{
		$sRedirectUrl = 'http://localhost/iTop/pages/oauth.landing.php';
		$sProviderClass = "\Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProvider".$sProviderVendor;
		$aQuery = [];
		parse_str($sRedirectUrlQuery, $aQuery);
		$sCode = $aQuery['code'];
		$oProvider = new $sProviderClass(['clientId' => $sClientId, 'clientSecret' => $sClientSecret, 'redirectUri' => $sRedirectUrl]);
		return $sProviderClass::getConfFromAccessToken($oProvider->GetVendorProvider()->getAccessToken('authorization_code', ['code' => $sCode]), $sClientId, $sClientSecret);
	}

}