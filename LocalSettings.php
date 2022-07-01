<?php
# This file was automatically generated by the MediaWiki 1.38.1
# installer. If you make manual changes, please keep track in case you
# need to recreate them later.
#
# See docs/Configuration.md for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# https://www.mediawiki.org/wiki/Manual:Configuration_settings

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}


## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename = "wizzypedia";
$wgMetaNamespace = "Wizzypedia";

$wgShowExceptionDetails = true;

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs
## (like /w/index.php/Page_title to /wiki/Page_title) please see:
## https://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath = "";

## The protocol and server name to use in fully-qualified URLs
$wgServer = "https://wizzypedia.forgottenrunes.com";

## The URL path to static resources (images, scripts, etc.)
$wgResourceBasePath = $wgScriptPath;

## The URL paths to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
$wgLogos = [
	'1x' => "$wgResourceBasePath/resources/assets/resize-logo.png",
	
	
	'icon' => "$wgResourceBasePath/resources/assets/change-your-logo-icon.svg",
];

## UPO means: this is also a user preference option

$wgEnableEmail = true;
$wgEnableUserEmail = true; # UPO

$wgEmergencyContact = "apache@🌻.invalid";
$wgPasswordSender = "apache@🌻.invalid";

$wgEnotifUserTalk = false; # UPO
$wgEnotifWatchlist = false; # UPO
$wgEmailAuthentication = true;

## Database settings
$wgDBtype = "mysql";
$wgDBserver = "cxmgkzhk95kfgbq4.cbetxkdyhwsb.us-east-1.rds.amazonaws.com";
$wgDBname = "plb8m3w63z6muu8r";
$wgDBuser = "lc78v6nzc1vc69r0";
$wgDBpassword = "zt691468loxnp01r";

# MySQL specific settings
$wgDBprefix = "";

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

# Shared database table
# This has no effect unless $wgSharedDB is also set.
$wgSharedTables[] = "actor";

## Shared memory settings
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = [];

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads = true;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";

$wgFileExtensions = array( 'png', 'gif', 'jpg', 'jpeg');

# InstantCommons allows wiki to use images from https://commons.wikimedia.org
$wgUseInstantCommons = false;

# Periodically send a pingback to https://www.mediawiki.org/ with basic data
# about this MediaWiki instance. The Wikimedia Foundation shares this data
# with MediaWiki developers to help guide future development efforts.
$wgPingback = false;

# Site language code, should be one of the list in ./languages/data/Names.php
$wgLanguageCode = "en";

# Time zone
$wgLocaltimezone = "UTC";

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publicly accessible from the web.
#$wgCacheDirectory = "$IP/cache";

$wgSecretKey = getenv("SECRET_KEY");

# Changing this will log out all existing sessions.
$wgAuthenticationTokenVersion = "1";

# Site upgrade key. Must be set to a string (default provided) to turn on the
# web installer while LocalSettings.php is in place
$wgUpgradeKey = getenv("UPGRADE_KEY");

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

wfLoadSkin( 'Vector' );
$wgDefaultSkin = 'vector';




$wgFavicon = $wgScriptPath . "/resources/assets/favicon.png";

$wgAllowExternalImages = true;

## https://www.mediawiki.org/wiki/Manual:$wgMaxAnimatedGifArea
$wgMaxAnimatedGifArea = 1000000000;
    
wfLoadExtension( 'AWS' );

// Configure AWS credentials.
// THIS IS NOT NEEDED if your EC2 instance has an IAM instance profile.
$wgAWSCredentials = [
	'key' => getenv("S3_KEY"),
	'secret' => getenv("S3_SECRET"),
	'token' => false
];

$wgAWSRegion = 'us-east-1'; # Northern Virginia

// Replace <something> with the name of your S3 bucket, e.g. wonderfulbali234.
$wgAWSBucketName = getenv("S3_NAME");

// If you anticipate using several hundred buckets, one per wiki, then it's probably better to use one bucket
// with the top level subdirectory as the wiki's name, and permissions properly configured of course.
// While there are no more performance losses by using such a scheme, it might make things messy. Hence, it's
// still a good idea to use one bucket per wiki unless you are approaching your 1,000 bucket per account limit.
// $wgAWSBucketTopSubdirectory = "/$wgDBname"; # leading slash is required
$wgAWSBucketTopSubdirectory = "/ttqfd9eooxmdfdav"; # leading slash is required

//Extensions
wfLoadExtension( 'VisualEditor' );
wfLoadExtension( 'TemplateStyles' );
wfLoadExtension( 'UploadWizard' );
wfLoadExtension( 'Scribunto' );
wfLoadExtension( 'Cargo' );
wfLoadExtension( 'DataTransfer' );

$wgScribuntoDefaultEngine = 'luastandalone';

# End of automatically generated settings.
# Add more configuration options below.


$wgUploadWizardConfig = [
	'tutorial' => [
	 	'skip' => true
	], // Skip the tutorial
	'defaults' => [
		// Initial value for the description field.
		'description' => 'Uploaded by UploadWizard'
	]
];

// Group permissions
// Anonymous users can't create pages or edit pages
$wgGroupPermissions['*']['createpage'] = false;
$wgGroupPermissions['*']['edit'] = false;