<?php
/**
 * Class SAMLConfiguration
 *
 * This object's job is to convert configuration from SilverStripe config system
 * into an array that can be consumed by the Onelogin SAML implementation.
 *
 * The configuration tells the IdP and SP how to establish the circle of trust - i.e.
 * how to exchange certificates and which endpoints to use (e.g. see SAMLConfiguration::metadata).
 *
 * https://syncplicity.zendesk.com/hc/en-us/articles/202392814-Single-sign-on-with-ADFS
 */
class SAMLConfiguration extends Object {

	/**
	 * @var bool
	 */
	private static $strict;

	/**
	 * @var bool
	 */
	private static $debug;

	/**
	 * @var array
	 */
	private static $SP;

	/**
	 * @var array
	 */
	private static $IdP;

	/**
	 * @return array
	 */
	public function asArray() {
		$conf = array();

		$conf['strict'] = $this->config()->get('strict');
		$conf['debug'] = $this->config()->get('debug');

		// SERVICE PROVIDER SECTION
		$sp = $this->config()->get('SP');
		$conf['sp']['entityId'] = $sp['entityId'];
		$conf['sp']['assertionConsumerService'] = array(
			'url' => $sp['entityId'] . '/saml/acs',
			'binding' => OneLogin_Saml2_Constants::BINDING_HTTP_POST
		);
		$conf['sp']['NameIDFormat'] = OneLogin_Saml2_Constants::NAMEID_TRANSIENT;
		$conf['sp']['x509cert'] = file_get_contents(BASE_PATH.'/'.$sp['x509cert']);
		$conf['sp']['privateKey'] = file_get_contents(BASE_PATH.'/'.$sp['privateKey']);

		// IDENTITY PROVIDER SECTION
		$idp = $this->config()->get('IdP');
		$conf['idp']['entityId'] = $idp['entityId'];
		$conf['idp']['singleSignOnService'] = array(
			'url' => $idp['singleSignOnService'],
			'binding' => OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT,
		);
		if(isset($idp['singleLogoutService'])) {
			$conf['idp']['singleLogoutService'] = array(
				'url' => $idp['singleLogoutService'],
				'binding' => OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT,
			);
		}

		$conf['idp']['x509cert'] = file_get_contents(BASE_PATH.'/'.$idp['x509cert']);

		// SECURITY SECTION
		$conf['security'] = array (
			/** signatures and encryptions offered */
			// Indicates that the nameID of the <samlp:logoutRequest> sent by this SP will be encrypted.
			'nameIdEncrypted' => true,
			// Indicates whether the <samlp:AuthnRequest> messages sent by this SP will be signed. [Metadata of the SP will offer this info]
			'authnRequestsSigned' => true,
			// Indicates whether the <samlp:logoutRequest> messages sent by this SP will be signed.
			'logoutRequestSigned' => true,
			// Indicates whether the <samlp:logoutResponse> messages sent by this SP will be signed.
			'logoutResponseSigned' => true,
			'signMetadata' => false,
			/** signatures and encryptions required **/
			// Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest>
			// and <samlp:LogoutResponse> elements received by this SP to be signed.
			'wantMessagesSigned' => false,
			// Indicates a requirement for the <saml:Assertion> elements received by
			// this SP to be signed. [Metadata of the SP will offer this info]
			'wantAssertionsSigned' => true,
			// Indicates a requirement for the NameID received by
			// this SP to be encrypted.
			'wantNameIdEncrypted' => false,
			// Authentication context.
			// Set to false and no AuthContext will be sent in the AuthNRequest,
			// Set true or don't present thi parameter and you will get an AuthContext 'exact' 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport'
			// Set an array with the possible auth context values: array ('urn:oasis:names:tc:SAML:2.0:ac:classes:Password', 'urn:oasis:names:tc:SAML:2.0:ac:classes:X509'),
			'requestedAuthnContext' => array(
				'urn:federation:authentication:windows',
				'urn:oasis:names:tc:SAML:2.0:ac:classes:Password',
				'urn:oasis:names:tc:SAML:2.0:ac:classes:X509',
			),
			// Indicates if the SP will validate all received xmls.
			// (In order to validate the xml, 'strict' and 'wantXMLValidation' must be true).
			'wantXMLValidation' => true,
		);

		return $conf;
	}

}
