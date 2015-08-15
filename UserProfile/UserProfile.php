<?php
// Global profile namespace reference
define( 'NS_USER_PROFILE', 202 );
define( 'NS_USER_WIKI', 200 );

// Default setup for displaying sections
$wgUserPageChoice = true;

$wgUserProfileDisplay['friends'] = false;
$wgUserProfileDisplay['foes'] = false;
$wgUserProfileDisplay['gifts'] = true;
$wgUserProfileDisplay['awards'] = true;
$wgUserProfileDisplay['profile'] = true;
$wgUserProfileDisplay['board'] = false;
$wgUserProfileDisplay['stats'] = false; // Display statistics on user profile pages?
$wgUserProfileDisplay['interests'] = true;
$wgUserProfileDisplay['custom'] = true;
$wgUserProfileDisplay['personal'] = true;
$wgUserProfileDisplay['activity'] = false; // Display recent social activity?
$wgUserProfileDisplay['userboxes'] = false; // If FanBoxes extension is installed, setting this to true will display the user's fanboxes on their profile page
$wgUserProfileDisplay['games'] = false; // Display casual games created by the user on their profile? This requires three separate social extensions: PictureGame, PollNY and QuizGame

$wgUpdateProfileInRecentChanges = false; // Show a log entry in recent changes whenever a user updates their profile?
$wgUploadAvatarInRecentChanges = false; // Same as above, but for avatar uploading

$wgAvailableRights[] = 'avatarremove';
$wgAvailableRights[] = 'editothersprofiles';
$wgGroupPermissions['sysop']['avatarremove'] = true;
$wgGroupPermissions['staff']['editothersprofiles'] = true;

// ResourceLoader support for MediaWiki 1.17+
// Modules for Special:EditProfile/Special:UpdateProfile
$wgResourceModules['ext.userProfile.updateProfile'] = array(
	'styles' => 'UserProfile.css',
	'scripts' => 'UpdateProfile.js',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'SocialProfile/UserProfile',
	'position' => 'top'
);

# Add new log types for profile edits and avatar uploads
global $wgLogTypes, $wgLogNames, $wgLogHeaders, $wgLogActions;
$wgLogTypes[]                    = 'profile';
$wgLogNames['profile']           = 'profilelogpage';
$wgLogHeaders['profile']         = 'profilelogpagetext';
$wgLogActions['profile/profile'] = 'profilelogentry';

$wgLogTypes[]                    = 'avatar';
$wgLogNames['avatar']            = 'avatarlogpage';
$wgLogHeaders['avatar']          = 'avatarlogpagetext';
$wgLogActions['avatar/avatar'] = 'avatarlogentry';

$wgHooks['ArticleFromTitle'][] = 'wfUserProfileFromTitle';

/**
 * Called by ArticleFromTitle hook
 * Calls UserProfilePage instead of standard article
 *
 * @param &$title Title object
 * @param &$article Article object
 * @return true
 */
function wfUserProfileFromTitle( &$title, &$article ) {
	global $wgRequest, $wgOut, $wgHooks, $wgUserPageChoice, $wgUserProfileScripts;

	if ( strpos( $title->getText(), '/' ) === false &&
		( NS_USER == $title->getNamespace() || NS_USER_PROFILE == $title->getNamespace() )
	) {
		$show_user_page = false;
		if ( $wgUserPageChoice ) {
			$profile = new UserProfile( $title->getText() );
			$profile_data = $profile->getProfile();

			// If they want regular page, ignore this hook
			if ( isset( $profile_data['user_id'] ) && $profile_data['user_id'] && $profile_data['user_page_type'] == 0 ) {
				$show_user_page = true;
			}
		}
		//항상 유저페이지만 보여주도록 함 (by 페넷)
		$show_user_page = false;

		if ( !$show_user_page ) {
			// Prevents editing of userpage
			if ( $wgRequest->getVal( 'action' ) == 'edit' ) {
				$wgOut->redirect( $title->getFullURL() );
			}
		} else {
			$wgOut->enableClientCache( false );
			$wgHooks['ParserLimitReport'][] = 'wfUserProfileMarkUncacheable';
		}

		$wgOut->addExtensionStyle( $wgUserProfileScripts . '/UserProfile.css' );

		$article = new UserProfilePage( $title );
	}
	#NS_USER의 하위페이지 접근 시에도 유저페이지만 보여줌 (by 페네트)
	#별로 좋지 못한 해결책임...mw 다음 버전에서 특수 함수를 사용해서 해결할 수 있을듯.
	elseif ( strpos( $title->getText(), '/' ) > 0 &&
		( NS_USER == $title->getNamespace() || NS_USER_PROFILE == $title->getNamespace() )
	) {
		$parts = explode( '/', $title->getText() );
		if ( count( $parts ) > 1 )
			unset( $parts[count( $parts ) - 1] );

		$article = new UserProfilePage($title);
		$title = Title::newFromText(implode($parts), $title->getNamespace());
		$article->getContext()->getOutput()->redirect($title->getFullURL());
	}
	return true;
}

/**
 * Mark page as uncacheable
 *
 * @param $parser Parser object
 * @param &$limitReport String: unused
 * @return true
 */
function wfUserProfileMarkUncacheable( $parser, &$limitReport ) {
	$parser->disableCache();
	return true;
}