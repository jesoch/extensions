<?php
/**
 * IntegratePerson extension - Integrates Person records into user preferences and account creation forms
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) )           die( 'Not an entry point.' );
if ( !defined( 'RECORDADMIN_VERSION' ) ) die( 'This extension depends on the RecordAdmin extension' );

define( 'INTEGRATEPERSON_VERSION', '0.2.0, 2009-11-10' );

$wgAutoConfirmCount = 10^10;

$wgIPPersonalUrls = array();
$wgIPDefaultImage = '';
$wgIPMaxImageSize = 100000;
$wgIPPersonType   = 'Person';

$wgExtensionFunctions[] = 'wfSetupIntegratePerson';
$wgExtensionCredits['other'][] = $wgExtensionCredits['specialpage'][] = array(
	'name'        => 'IntegratePerson',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad User:Nad]',
	'description' => 'Integrates Person records (see RecordAdmin extension) into user preferences and account creation forms',
	'url'         => 'http://www.organicdesign.co.nz/Extension:IntegratePerson',
	'version'     => INTEGRATEPERSON_VERSION
);

# Process posted contact details
if ( isset( $_POST['wpFirstName'] ) ) $_POST['wpRealName'] = $_POST['wpFirstName'] . ' ' . $_POST['wpLastName'];

class IntegratePerson {

	function __construct() {
		global $wgHooks, $wgMessageCache, $wgParser;

		# Modify login form messages to say email and name compulsory
#		$wgMessageCache->addMessages(array('prefs-help-email' => '<div class="error">Required</div>'));
#		$wgMessageCache->addMessages(array('prefs-help-realname' => '<div class="error">Required</div>'));

		$wgHooks['PersonalUrls'][] = $this;
		$wgHooks['OutputPageBeforeHTML'][] = $this;

		# Process an uploaded profile image if one was posted
		if ( array_key_exists( 'wpProfileImage', $_FILES ) && $_FILES['wpProfileImage']['size'] > 0 )
			$this->processUploadedImage( $_FILES['wpProfileImage'] );



	}

	/**
	 * Add items in $wgIPPersonalUrls to personal URL's
	 */
	function onPersonalUrls( &$personal_urls, &$title ) {
		global $wgIPPersonalUrls;
		return true;
	}

	/**
	 * Determine which JS and page modifications should be added
	 */
	function onOutputPageBeforeHTML( &$out, &$text ) {
		global $wgHooks, $wgTitle, $wgReuqest;

		# Preferences
		if ( $wgTitle->getPrefixedText() == 'Special:Preferences' ) {
			$this->jsPreferences( $out );
			$wgHooks['BeforePageDisplay'][] = array( $this, 'modPreferences' );
		}

		# Account-creation
		if ( $wgTitle->getPrefixedText() == 'Special:UserLogin' && $wgRequest->getText( 'type' ) == 'signup' ) {
			$this->jsAccountCreate( $out );
			$wgHooks['BeforePageDisplay'][] = array( $this, 'modAccountCreate' );
		}

	}

	# Add JS to prefs page
	function jsPreferences( &$out ) {
		print "prefs";
		$out->addScript( "<script type='$wgJsMimeType'>
			function wikidAdminShowTypeForm() {
				var type = document.getElementById('wpType').value;
				var forms = [$forms];
				for( i in forms ) document.getElementById('form-'+forms[i]).style.display = forms[i] == type ? '' : 'none';
			}</script>"
		);
		return true;
	}

	# Modify the account-create page before rendering
	function modPreferences( &$out, $skin = false ) {
		return true;
	}

	# Add JS to account-create page
	function jsAccountCreate( &$out ) {
		print "create";
		$out->addScript( "<script type='$wgJsMimeType'>
			function wikidAdminShowTypeForm() {
				var type = document.getElementById('wpType').value;
				var forms = [$forms];
				for( i in forms ) document.getElementById('form-'+forms[i]).style.display = forms[i] == type ? '' : 'none';
			}</script>"
		);
		return true;
	}

	# Modify the account-create page before rendering
	function modAccountCreate( &$out, $skin = false ) {
		return true;
	}

	/**
	 * Set fields in Person Record from posted form
	 */
	function setOptions( &$user ) {
		global $wgSpecialRecordAdmin;
		$posted = array();
		foreach ( $_REQUEST as $k => $v ) if ( preg_match( '|^ra_(\\w+)|', $k, $m ) ) $posted[$m[1]] = is_array( $v ) ? join( "\n", $v ) : $v;
		$wgSpecialRecordAdmin->filter = $posted;
	}
	
	/**
	 * Process uploaded image file
	 */
	function processUploadedImage( $file ) {
		global $wgUser, $wgDBname, $wgSiteNotice, $wgUploadDirectory, $wgIPMaxImageSize;
		$error = false;
		if ( !ereg( '^image/(jpeg|png|gif)$', $file['type'] ) ) $error = 'Uploaded file was not of a valid type!';
		if ( $file['size'] > $wgIPMaxImageSize )                $error = 'Profile images are restricted to a maximum of 100KBytes';
		if ( $file['error'] > 0 )                               $error = 'Uploaded error number ' . $file['error'] . ' occurred';
		if ( $error ) $wgSiteNotice = "<div class='errorbox'>$error</div>";
		else {
			$name = preg_replace( '%.+(\..+?)$%', "user-{$wgDBname}-{$wgUser->getId()}$1", $file['name'] );
			move_uploaded_file( $file['tmp_name'], "$wgUploadDirectory/$name" );
		}
	}

}

function wfSetupIntegratePerson() {
	global $wgIntegratePerson, $wgLanguageCode, $wgMessageCache;

	# Add the messages used by the specialpage
	if ( $wgLanguageCode == 'en' ) {
		$wgMessageCache->addMessages( array(
			'ip-preftab'   => "Person Record",
			'ip-prefmsg'   => "<br><b>Fill in your Personal details here...</b><br>"
		) );
	}

	# Instantiate the IntegratePerson singleton now that the environment is prepared
	$wgIntegratePerson = new IntegratePerson();

}
