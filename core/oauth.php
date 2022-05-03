<?php
namespace Laminas\Mail\Protocol\Smtp\Auth;

use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderAbstract as OAuthClientProviderAbstractAlias;
use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderGoogle;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class Oauth extends Login
{
	/**
	 * LOGIN username
	 *
	 * @var OAuthClientProviderAbstractAlias
	 */
	protected static $oProvider;

	/**
	 * Constructor.
	 *
	 * @param string $host (Default: 127.0.0.1)
	 * @param null $port (Default: null)
	 * @param null $config Auth-specific parameters
	 */
	public function __construct($host = '127.0.0.1', $port = null, $config = null)
	{
		// Did we receive a configuration array?
		$origConfig = $config;
		if (is_array($host)) {
			// Merge config array with principal array, if provided
			if (is_array($config)) {
				$config = array_replace_recursive($host, $config);
			} else {
				$config = $host;
			}
		}

		if (is_array($config)) {
			if (isset($config['username'])) {
				$this->setUsername($config['username']);
			}
		}
		
		// Call parent with original arguments
		parent::__construct($host, $port, $origConfig);
	}

	/**
	 * @param OAuthClientProviderAbstractAlias $oProvider
	 *
	 * @return void
	 */
	public static function setProvider(OAuthClientProviderAbstractAlias $oProvider): void
	{
		self::$oProvider = $oProvider;
	}

	/**
	 * Perform LOGIN authentication with supplied credentials
	 *
	 */
	public function auth()
	{
		try {
			if (empty(self::$oProvider->GetAccessToken())) {
				throw new IdentityProviderException('Not prior authentication to OAuth', 255, []);
			} 
			elseif (self::$oProvider->GetAccessToken()->hasExpired()) {
				self::$oProvider->SetAccessToken(self::$oProvider->GetVendorProvider()->getAccessToken('refresh_token', [
					'refresh_token' => self::$oProvider->GetAccessToken()->getRefreshToken()
				]));
			}
		}
		catch (IdentityProviderException $e) {
			\IssueLog::Error('Failed to get oAuth credentials for outgoing mails');
			return false;
		}
		$sAccessToken = self::$oProvider->GetAccessToken()->getToken();

		if (empty($sAccessToken)) {
			return false;
		}

		$this->_send('AUTH XOAUTH2 '.base64_encode("user={$this->username}\1auth=Bearer {$sAccessToken}\1\1"));

		while (true) {
			$response = $this->_receive();

			$isPlus = $response === '+';
			if ($isPlus) {
				// Send empty client response.
				$this->_send('');
			} else {
				if (
					preg_match('/Unauthorized/i', $response) ||
					preg_match('/Rejected/i', $response)||
					preg_match('/535/i', $response)
				) {
					return false;
				}
				if (preg_match("/OK /i", $response)||
					preg_match('/Accepted/i', $response)|| 
					preg_match('/235/i', $response)) {
					$this->auth = true;
					return true;
				}
			}
		}
		return false;
	}
}
