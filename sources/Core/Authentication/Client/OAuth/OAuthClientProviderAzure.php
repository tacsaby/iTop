<?php
namespace Combodo\iTop\Core\Authentication\Client\OAuth;

use League\OAuth2\Client\Token\AccessToken;
use TheNetworg\OAuth2\Client\Provider\Azure;

class OAuthClientProviderAzure extends OAuthClientProviderAbstract{
	/** @var string */
	static protected $sVendorName = 'Azure';
	/** @var array */
	static protected $sVendorColors = ['#0766b7', '#0d396b', '#2893df', '#3ccbf4'];
	/** @var string */
	static	protected $sVendorIcon = '../images/icons/icons8-azure.svg';
	/** @var \League\OAuth2\Client\Provider\GenericProvider */
	protected $oVendorProvider;
    /** @var \League\OAuth2\Client\Token\AccessToken */
	protected $oAccessToken;

	public function __construct($aVendorProvider, $aAccessTokenParams = []){
		$this->oVendorProvider = new Azure($aVendorProvider);
		
		if(!empty($aAccessTokenParams)){
			$this->oAccessToken = new AccessToken(["access_token" =>  $aAccessTokenParams["access_token"],
			                                                      "expires_in" => 1,
			                                                      "refresh_token" => $aAccessTokenParams["refresh_token"],
			                                                      "token_type" => "Bearer"
			]);			
		}
	}
}