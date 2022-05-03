<?php
namespace Combodo\iTop\Core\Authentication\Client\OAuth;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

abstract class OAuthClientProviderAbstract implements IOAuthClientProvider
{
	/** @var string */
	static protected $sVendorName = '';
	/** @var array */
	static protected $sVendorColors = ['', '', '', ''];
	/** @var string */
	static protected $sVendorIcon = '';
	static protected $sRedirectUri = 'http://localhost/iTop/pages/oauth.landing.php';
	/** @var \League\OAuth2\Client\Provider\GenericProvider */
	protected $oVendorProvider;
	/** @var \League\OAuth2\Client\Token\AccessToken */
	protected $oAccessToken;


	/**
	 * @return \League\OAuth2\Client\Provider\GenericProvider
	 */
	public function GetVendorProvider()
	{
		return $this->oVendorProvider;
	}

	/**
	 * @param \League\OAuth2\Client\Provider\GenericProvider $oVendorProvider
	 */
	public function SetVendorProvider(GenericProvider $oVendorProvider): void
	{
		$this->oVendorProvider = $oVendorProvider;
	}

	/**
	 * @return \League\OAuth2\Client\Token\AccessToken
	 */
	public function GetAccessToken(): AccessToken
	{
		return $this->oAccessToken;
	}

	/**
	 * @param \League\OAuth2\Client\Token\AccessToken $oAccessToken
	 */
	public function SetAccessToken(AccessToken $oAccessToken): void
	{
		$this->oAccessToken = $oAccessToken;
	}

	/**
	 * @return string
	 */
	public static  function GetVendorIcon(): string
	{
		return static::$sVendorIcon;
	}

	/**
	 * @return string
	 */
	public static function GetVendorName(): string
	{
		return static::$sVendorName;
	}


	public static function getConfFromAccessToken($oAccessToken, $sClientId, $sClientSecret): string{
		$sAccessToken = $oAccessToken->getToken();
		$sRefreshToken = $oAccessToken->getRefreshToken();
		$sVendor = static::GetVendorName();
		return <<<EOF
'email_transport' => 'SMTP_OAuth',
'email_transport_smtp.oauth.provider' => '$sVendor',
'email_transport_smtp.oauth.client_id' => '$sClientId',
'email_transport_smtp.oauth.client_secret' => '$sClientSecret',
'email_transport_smtp.oauth.access_token' => '$sAccessToken',
'email_transport_smtp.oauth.refresh_token' => '$sRefreshToken',
EOF;
	}

	/**
	 * @return array
	 */
	public static function GetVendorColors(): array
	{
		return static::$sVendorColors;
	}
	/**
	 * @return string
	 */
	public static function GetRedirectUri(): string
	{
		return static::$sRedirectUri;
	}
}