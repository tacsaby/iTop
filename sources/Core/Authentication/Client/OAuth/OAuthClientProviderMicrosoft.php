<?php
namespace Combodo\iTop\Core\Authentication\Client\OAuth;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;

class OAuthClientProviderMicrosoft extends OAuthClientProviderAbstract{
	/** @var string */
	static protected $sVendorName = 'Microsoft';
	/** @var array */
	static protected $sVendorColors = ['#0766b7', '#0d396b', '#2893df', '#3ccbf4'];
	/** @var string */
	static	protected $sVendorIcon = 'http://localhost/iTop/images/icons/icons8-azure.svg';
	/** @var \League\OAuth2\Client\Provider\GenericProvider */
	protected $oVendorProvider;
    /** @var \League\OAuth2\Client\Token\AccessToken */
	protected $oAccessToken;

	public function __construct($aVendorProvider, $aAccessTokenParams = []){
		$this->oVendorProvider = new Microsoft($aVendorProvider);
		
		if(!empty($aAccessTokenParams)){
			$this->oAccessToken = new AccessToken(["access_token" =>  $aAccessTokenParams["access_token"],
			                                                      "expires_in" => 1,
			                                                      "refresh_token" => $aAccessTokenParams["refresh_token"],
			                                                      "token_type" => "Bearer"
			]);			
		}
	}
}