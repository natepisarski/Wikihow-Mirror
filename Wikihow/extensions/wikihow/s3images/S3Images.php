<?

if (!defined('MEDIAWIKI')) die();
    
/**#@+
 * wikiHow stores its images on S3. This class contains a bunch of the glue for
 * those operations.
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:CommunityDashboard-Extension Documentation
 *
 * @author Reuben Smith <reuben@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'S3Images',
	'author' => 'Reuben Smith',
	'description' => 'Glue to allow wikiHow store its uploaded images on S3',
	'url' => 'http://www.wikihow.com/WikiHow:S3Images-Extension',
);

$wgAutoloadClasses['S3Images'] = dirname( __FILE__ ) . '/S3Images.body.php';
$wgAutoloadClasses['AwsFiles'] = dirname( __FILE__ ) . '/AwsFiles.body.php';
$wgAutoloadClasses['UploadS3FileJob'] = dirname( __FILE__ ) . '/S3Job.body.php';
$wgAutoloadClasses['DeleteS3FileJob'] = dirname( __FILE__ ) . '/S3Job.body.php';

$wgJobClasses['UploadS3FileJob'] = 'UploadS3FileJob';
$wgJobClasses['DeleteS3FileJob'] = 'DeleteS3FileJob';

#$wgHooks['FileTransformed'][] = array('S3Images::onFileTransformed');
#$wgHooks['LocalFilePurgeThumbnails'][] = array('S3Images::onLocalFilePurgeThumbnails');
#$wgHooks['NewRevisionFromEditComplete'][] = array('S3Images::onNewRevisionFromEditComplete');
$wgHooks['FileUpload'][] = array('S3Images::onFileUpload');

