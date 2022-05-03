<?php
namespace Combodo\iTop\Core\Authentication\Client\OAuth;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Token\AccessToken;

class OAuthClientProviderGoogle extends OAuthClientProviderAbstract {
	/** @var string */
	static protected $sVendorName = 'Google';
	/** @var array */
	static protected $sVendorColors = ['#DB4437', '#F4B400', '#0F9D58', '#4285F4'];
	/** @var string */
	static	protected $sVendorIcon = 'http://localhost/iTop/images/icons/icons8-google.svg';
	/** @var \League\OAuth2\Client\Provider\GenericProvider */
	protected $oVendorProvider;
    /** @var \League\OAuth2\Client\Token\AccessToken */
	protected $oAccessToken;

	public function __construct($aVendorProvider, $aAccessTokenParams = []){
		$this->oVendorProvider = new Google(array_merge(['prompt'=>'consent', 'accessType' => 'offline'], $aVendorProvider));
		
		if(!empty($aAccessTokenParams)){
			$this->oAccessToken = new AccessToken(["access_token" =>  $aAccessTokenParams["access_token"],
			                                                      "expires_in" => 1,
			                                                      "refresh_token" => $aAccessTokenParams["refresh_token"],
			                                                      "token_type" => "Bearer"
			]);			
		}
	}
}