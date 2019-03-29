<?php

class Avatar extends UnlistedSpecialPage {

	const DEFAULT_PROFILE_OLD = '/skins/WikiHow/images/default_profile.png';
	const DEFAULT_PROFILE = '/skins/WikiHow/images/80x80_user.png';
	const ANON_AVATAR_DIR = '/skins/WikiHow/anon_avatars';

	function __construct() {
		parent::__construct( 'Avatar' );
	}

	// return the URL of the avatar
	public static function getAvatarRaw($name) {
		$u = User::newFromName($name);
		return self::getAvatarRawByUser($u);
	}

	private static function getAvatarRawByUser($u) {
		$default = array('type' => 'df', 'url' => '');

		if (!$u || $u->getID() === 0) {
			return $default;
		}

		$dbr = wfGetDB(DB_REPLICA);
		// check for facebook
		if ($u->isFacebookUser()) {
			$row = $dbr->selectRow('avatar', array('av_image','av_patrol'), array('av_user'=>$u->getID()), __METHOD__);
			if ($row->av_image && ($row->av_patrol == 0 || $row->av_patrol == 1)) {
				// make Facebook images load from HTTP or HTTPS depending on context
				$row->av_image = preg_replace('@^http:@', '', $row->av_image);
				return array('type' => 'fb', 'url' => $row->av_image);
			}
		}

		//check for Google+
		if ($u->isGPlusUser()) {
			$row = $dbr->selectRow('avatar', array('av_image','av_patrol'), array('av_user'=>$u->getID()), __METHOD__);
			if ($row->av_image && ($row->av_patrol == 0 || $row->av_patrol == 1)) {
				return array('type' => 'gp', 'url' => $row->av_image);
			}
		}

		//checks for redirects for users that go that route
		//rather than just changing the username
		$up = $u->getUserPage();
		$a = new Article($up, 0); //need to put 0 as the oldID b/c Article gets the old id out of the URL
		if ($a->isRedirect()) {
			$wp = new WikiPage( $a->getTitle() );
			$t = $wp->getRedirectTarget();
			$u = User::newFromName($t->getText());
			if (!$u) {
				return $default;
			}
		}

		$row = $dbr->selectRow('avatar', array('av_dateAdded'), array('av_user'=>$u->getID(), 'av_patrol'=>0), __METHOD__);
		$filename = $u->getID() .".jpg";
		if ($row && $row->av_dateAdded) {
			return array('type' => 'av', 'url' => "$filename?" . $row->av_dateAdded);
		}

		return $default;
	}

	public static function getAvatarURL($name) {
		$raw = self::getAvatarRaw($name);
		return self::getAvatarUrlFromRaw($raw);
	}

	private static function getAvatarUrlFromRaw($raw) {
		global $wgIsDevServer;
		if ($raw['type'] == 'df') {
			return self::getDefaultProfile();
		} elseif (($raw['type'] == 'fb') || ($raw['type'] == 'gp')) {
			return $raw['url'];
		} elseif ($raw['type'] == 'av') {
			$imgName = explode("?", $raw['url']);
			$imgPath = self::getAvatarOutPath($imgName[0]);
			// exception made for dev since there are no profile pics there
			if ($wgIsDevServer) {
				$imgPath = '//www.wikihow.com' . $imgPath;
			}
			return $imgPath . $raw['url'];
		}
	}

	public static function getPicture($name, $raw = false, $fromCDN = false) {
		global $wgUser, $wgTitle;

		$u = User::newFromName($name);
		if (!$u) return;

		// not sure what's going on here, User Designer-WG.de ::newFromName does not work, mId==0
		if ($u->getID() == 0) {
			$dbr = wfGetDB(DB_REPLICA);
			$id = $dbr->selectField('user', array('user_id'), array('user_name'=> $name), __METHOD__);
			$u = User::newFromID($id);
		}

		$filename = $u->getID() . ".jpg";
		$crop_out = self::getAvatarOutFilePath($filename) . $filename;

		$ret = "";
		if (!$raw) {
			$ret = "<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/avatar/avatar.css?') . WH_SITEREV . "' type='text/css' />
			<script type='text/javascript' src='".wfGetPad('/extensions/wikihow/common/jquery.md5.js?') . WH_SITEREV ."'></script>
			<script language='javascript' src='" . wfGetPad('/extensions/wikihow/avatar/avatar.js?') . WH_SITEREV . "'></script>\n";
		}

		// handle facebook and google+ users
		$isFacebook = $u->isFacebookUser();
		$isGPlus = $u->isGPlusUser();
		if ($isFacebook || $isGPlus) {
			$raw = self::getAvatarRawByUser($u);
			if ($raw['type'] != 'df') {
				$cssClass = $isFacebook ? 'avatar_fb' : 'avatar_gp';
				$imgUrl = self::getAvatarUrlFromRaw($raw);
				$ret .= "<div id='avatarID' class='$cssClass'><img id='avatarULimg' src='{$imgUrl}'  height='50px' width='50px' alt='' /><br/>";
				if ($u->getID() == $wgUser->getID() && $wgTitle->inNamespace(NS_USER)) {
					$ret .="<a href='#' onclick='removeButton();return false;'>remove</a>";
				}
				$ret .= "</div>";
				return $ret;
			}
		}

		// check if displaying user's own avatar, on their User: page
		if ($wgUser->getID() == $u->getID() && !$wgUser->isAnon() && $wgTitle->inNamespace(NS_USER)) {
			$ret .= "<div class='avatar' id='avatarID'>";
			$url = self::getAvatarURL($name);
			if (stristr($url, basename(self::getDefaultProfile())) !== false) {
				$ret .= "
				<div id='avatarULaction'><div class='avatarULtextBox'><a class='avatar_upload' onclick='uploadImageLink();return false;' id='gatUploadImageLink' href='#'></a></div></div>";
			} else {
				$ret .= Html::element( 'img', array('id'=>'avatarULimg', 'src' => $url, 'height' => 80, 'width'=> 80, 'alt'=>$name ) );
				$ret .= "<a href onclick='removeButton();return false;' onhover='background-color: #222;' >remove</a> | <a href onclick='editButton();return false;' onhover='background-color: #222;'>edit</a>";
			}
			$ret .= "</div>";
		} else {
			$dbr = wfGetDB(DB_REPLICA);
		    $row = $dbr->selectRow('avatar', array('av_dateAdded'), array('av_user'=>$u->getID(), 'av_patrol'=>0), __METHOD__);

			if ($row && $row->av_dateAdded) {
				if ($raw) {
					$imgUrl = self::getAvatarURL($name);
					$ret .= Html::element( 'img', array( 'src'=>$imgUrl, 'alt'=>$name ) );
				} else {
					$ret .= "<div id='avatarID' class='avatar'>";
					$ret .= Html::element( 'img', array( 'id'=>'avatarULimg', 'src'=>self::getAvatarURL($name), 'alt'=>$name, 'height'=>80, 'width'=>80) );
					$ret .= "</div>";
				}
			} else {
				// NOTE: We could return the default image here. But not until
				// we force profile images.
				$ret = "";
			}
		}

		return $ret;
	}

	private static function getAnonName($src) {
		if (preg_match('/\_(.*?)\./', $src, $matches)) {
			return "Anonymous " . ucfirst($matches[1]);
		}
	}

	// returns an anon avatar picture and its name
	// hashes on the id if it is non null
	private static function getAnonImageFileName($id = null) {
		global $IP;
		$images	= glob($IP . self::ANON_AVATAR_DIR . '/80x80*');
		if ($id === null) {
			$i = array_rand($images);
		} else {
			$i = abs( crc32($id) ) % (count($images) - 1);
			MWDebug::log("id: $id, i: $i");
		}
		return basename($images[$i]);
	}

	// gets an avatar from the pool of anonymous avatars
	// uses the id as a hash so you can keep getting the same one if you want
	// passing in a null just gives a completely random avatar
	public static function getAnonAvatar($id = null) {
		$fileName = self::getAnonImageFileName($id);
		$img = "<img src='" . wfGetPad( self::ANON_AVATAR_DIR . "/" . $fileName ) . "' alt='anon'>";
		$name = self::getAnonName($fileName);
		$ret = array("name"=>$name, "image"=>$img);
		return $ret;
	}

	public static function getDefaultPicture() {
		$ret = "<img src='" . self::getDefaultProfile() . "' alt='default picture'>";
		return $ret;
	}

	public static function getDefaultProfile() {
		return self::DEFAULT_PROFILE;
	}

	public static function removePicture($uid = '') {
		global $wgUser;

		if ($uid == '') {
			$u = $wgUser->getID();
		} else {
			$u = $uid;
		}

		self::purgePath(array("/User:" . $wgUser->getName()));

		$fileext = array('jpg','png','gif','jpeg');

		$filename = $u . ".jpg";
		$crop_out = self::getAvatarOutFilePath($filename) . $filename;
		self::purgeS3(self::getAvatarOutPath($filename) . $filename);
		$cropOutExists = file_exists($crop_out);
		@unlink($crop_out);

		foreach ($fileext as $ext) {
			$filename = "$u.$ext";
			$crop_in = self::getAvatarInFilePath($filename) . $filename;
			self::purgeS3(self::getAvatarInPath($filename) . $filename);
			@unlink($crop_in);

			$filename = "tmp_$u.$ext";
			$crop_in2 = self::getAvatarInFilePath($filename) . $filename;
			self::purgeS3(self::getAvatarInPath($filename) . $filename);
			@unlink($crop_in2);
		}

		if ($cropOutExists) {

			$dbw = wfGetDB(DB_MASTER);
			$sql = "UPDATE avatar SET av_patrol=2, av_patrolledBy=" . $dbw->addQuotes($wgUser->getId()) . ", av_patrolledDate='" . wfTimestampNow() . "' WHERE av_user=" . $dbw->addQuotes($u);
			$res = $dbw->query($sql, __METHOD__);

			return "SUCCESS: files removed $crop_out and $crop_in";
		} else {

			// files don't have to exist if we use av_image
			$dbw = wfGetDB(DB_MASTER);
			$sql = "UPDATE avatar SET av_patrol=2, av_patrolledBy=" . $dbw->addQuotes($wgUser->getId()) . ", av_patrolledDate='" . wfTimestampNow() . "' WHERE av_user=" . $dbw->addQuotes($u);
			$res = $dbw->query($sql, __METHOD__);

			// Remove avatar url (av_image) for FB users
			$user = User::newFromID($u);
			if ($user && $user->isFacebookUser()) {
				$sql = "UPDATE avatar set av_image='' where av_user=" . $dbw->addQuotes($u);
				$res = $dbw->query($sql, __METHOD__);
				return "SUCCESS: Facebook avatar removed";
			}
			// Remove avatar url (av_image) for G+ users
			if ($user && $user->isGPlusUser()) {
				$sql = "UPDATE avatar set av_image='' where av_user=" . $dbw->addQuotes($u);
				$res = $dbw->query($sql, __METHOD__);
				return "SUCCESS: Google+ avatar removed";
			}

			return "FAILED: files do not exist. $crop_out";
		}
	}

	private static function displayNonModal() {
		global $wgOut, $wgTitle, $wgUser, $wgRequest;

		$wgOut->setHTMLTitle('Change Your Profile Picture - wikiHow');

		$imgname = '';
		$avatarReload = '';
		$imgname = "tmp_".$wgUser->getID().".jpg";
		$imgPath = self::getAvatarInPath($imgname);
		if ($wgRequest->getVal('reload')) {
			$avatarReload = "var avatarReload = true;";
		} else {
//			$imgname = $wgUser->getID().".jpg";
//			$imgPath = self::getAvatarInPath($imgname);
			$avatarReload = "var avatarReload = false;";
		}

//		self::purgeS3($imgPath . $imgname);

		$avatarCrop = '';
		$avatarNew = "var avatarNew = false;";
		if ($wgRequest->getVal('new')) {
			$avatarCrop = "style='display:none;'";
			$avatarNew = "var avatarNew = true;";
		}

		$wgOut->addHTML("\n<!-- AVATAR CODE START -->\n<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/avatar/avatar.css?') . WH_SITEREV . "' type='text/css' />\n");

		$wgOut->addHTML( "
	<script>jQuery.noConflict();</script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/common/cropper/lib/prototype.js?') . WH_SITEREV . "'></script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/common/cropper/lib/scriptaculous.js?load=builder,dragdrop&') . WH_SITEREV . "'></script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/common/cropper/cropper.js?') . WH_SITEREV . "'></script>
	<script type='text/javascript' src='".wfGetPad('/extensions/wikihow/common/jquery.md5.js?') . WH_SITEREV ."'></script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/avatar/avatar.js?') . WH_SITEREV . "'></script>
	<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/common/cropper/cropper.css?') . WH_SITEREV . "' type='text/css' />


<script type='text/javascript'>
		var wgUserID = '".$wgUser->getID()."';
		var nonModal = true;
		var userpage = '".$wgUser->getUserPage()."';
		$avatarReload\n
		$avatarNew\n
</script>

	  <div class='avatarModalBody minor_section'>
	  <div>". wfMessage('avatar-instructions',$wgUser->getName())."</div>
		 <div id='avatarUpload' >
				<form name='avatarFileSelectForm' action='/Special:Avatar?type=upload&reload=1' method='post' enctype='multipart/form-data' >
					File: <input type='file' id='uploadedfile' name='uploadedfile' size='40' /> <input type='submit' id='gatAvatarImageSubmit' disabled='disabled' value='SUBMIT' class='button primary disabled' />
				</form>
				<div id='avatarResponse'></div>
		 </div>

		 <div id='avatarCrop' $avatarCrop >
			<div id='avatarCropBorder' >
					<div id='avatarImgBlock'>
						<div id='avatarJS'>
							<img src='" . $imgPath . $imgname . "?" . rand() . "' id='avatarIn' />
						</div>
						<div id='avatarPreview'>
						Cropped Preview:<br />
						<div id='avatarPreview2'>
						</div>
						</div>
					</div>
				<div style='clear: both;'> </div>
				</div>

				<div>".wfMessage('avatar-copyright-notice')->text()."</div>

				<div id='cropSubmit' >
				<form name='crop' method='post' >
					<a onclick=\"closeButton();\" class='button'>Cancel</a>
					<input type='button' class='button primary' value='Crop and Save' id='gatAvatarCropAndSave' onclick='ajaxCropit();' />
					<!-- <a onclick=\"alert($('avatarPreview2').innerHTML);\">vutest</a> -->
					<input type='hidden' name='cropflag' value='false' />
					<input type='hidden' name='image' value='".$imgname."' />
					<input type='hidden' name='type' value='crop' />
					<input type='hidden' name='x1' id='x1' />
					<input type='hidden' name='y1' id='y1' />
					<input type='hidden' name='x2' id='x2' />
					<input type='hidden' name='y2' id='y2' />
					<input type='hidden' name='width' id='width' />
					<input type='hidden' name='height' id='height' />
				</form>
				</div>

		 </div>
	  </div>
<script type='text/javascript'>
Event.observe(window, 'load', initNonModal);
</script>");

		$wgOut->addHTML("<!-- AVATAR CODE ENDS -->\n");
	}

	private static function purgePath($arr) {
		global $wgUseSquid, $wgCanonicalServer;
		if ($wgUseSquid) {
			$urls = array();
			foreach ($arr as $elem) $urls[] = $wgCanonicalServer . $elem;
			$u = new SquidUpdate($urls);
			$u->doUpdate();
			wfDebugLog('avatar', "Avatar: Purging path of " . print_r($urls, true) . "\n");
		}
		return true;
	}

	// Aggressive caching is causing bugs. Remove the S3 backup image
	// so people can change their avatars.
	private static function purgeS3($s3ImagePath) {
		global $wgIsDevServer;
		if (!$wgIsDevServer) {
			$s3ImagePath = trim($s3ImagePath);
			if (empty($s3ImagePath)) return;

			// remove the S3 file so that front end caching systems don't refetch from there
#wfDebugLog('avatar', 'Avatar: attempting purge on file S3: ' . $s3ImagePath);
#$fe = AwsFiles::fileExists($s3ImagePath);
#wfDebugLog('avatar', 'Avatar: file_exists=' . (int)$fe . ' pre-purge: ' . $s3ImagePath);
			if (AwsFiles::fileExists($s3ImagePath)) {
				AwsFiles::deleteFile($s3ImagePath);
#$fe = AwsFiles::fileExists($s3ImagePath);
#wfDebugLog('avatar', 'Avatar: file_exists=' . (int)$fe . ' post-purge: ' . $s3ImagePath);
			}
		}
	}

	private static function uploadS3($localFile, $s3ImagePath) {
		global $wgIsDevServer;
		if (!$wgIsDevServer) {
			$s3ImagePath = trim($s3ImagePath);
			if (empty($s3ImagePath) || preg_match('@/$@', $s3ImagePath)) return;

			$mimeType = 'image/jpeg';
			self::purgePath(array($s3ImagePath));
			AwsFiles::uploadFile($localFile, $s3ImagePath, $mimeType);
		}
	}

	private static function downloadS3($s3ImagePath, $localFile) {
		global $wgIsDevServer;
		if (!$wgIsDevServer) {
			$s3ImagePath = trim($s3ImagePath);
			if (empty($s3ImagePath) || preg_match('@/$@', $s3ImagePath)) return;

			$mimeType = 'image/jpeg';
			AwsFiles::getFile($s3ImagePath, $localFile);
		}
	}

/*	private static function logExists($name, $file) {
		global $wgUser;
		wfDebugLog('avatar', $name . ' e:' . intval(AwsFiles::fileExists($file)) . ' f:' . $file);
	} */

	private static function crop() {
		global $wgUser, $wgOut, $wgTitle, $wgRequest, $wgImageMagickConvertCommand;

		$imagesize = 80;
		if ($wgRequest->getVal('cropflag') == 'false') {return false;}

		$image = $wgRequest->getVal('image');
		$x1 = $wgRequest->getInt('x1');
		$y1 = $wgRequest->getInt('y1');
		$x2 = $wgRequest->getInt('x2');
		$y2 = $wgRequest->getInt('y2');
		$width = $wgRequest->getInt('width');
		$height = $wgRequest->getInt('height');

		$crop_in = self::getAvatarInFilePath($image) . $image;
#self::logExists('crop_in', $crop_in);
		$filename = $wgUser->getID() . ".jpg";
//		$crop_in2 = self::getAvatarInFilePath($filename) . $filename;
//self::logExists('crop_in2', $crop_in2);
		$crop_out = self::getAvatarOutFilePath($filename) . $filename;
#self::logExists('crop_out', $crop_out);

//		if ($crop_in != $crop_in2 && !copy($crop_in, $crop_in2)) {
//			wfDebugLog('avatar', "Avatar: failed copy $crop_in to $crop_in2\n");
//		}

		self::downloadS3(self::getAvatarInPath($image) . $image, $crop_in);
#wfDebugLog('avatar', "crop, post-download: $crop_in " . " exists=" . (int)file_exists($crop_in));
		$cmd = "$wgImageMagickConvertCommand -crop " . wfEscapeShellArg("{$width}x{$height}+$x1+$y1") . " " . wfEscapeShellArg($crop_in) . " +repage -strip " . wfEscapeShellArg($crop_out);
		$result = wfShellExec($cmd, $ret);
		wfDebugLog('avatar', "Avatar: ran command $cmd got result $result and code $ret\n");
#wfDebugLog('avatar', "crop, post-process: $crop_out " . " exists=" . (int)file_exists($crop_out));
		if (!$ret) {
			if ($width > $imagesize) {
				$cmd = "$wgImageMagickConvertCommand " . wfEscapeShellArg($crop_out) . " -resize " . wfEscapeShellArg("{$imagesize}x{$imagesize}") . " " . wfEscapeShellArg($crop_out);
				$result = wfShellExec($cmd, $ret);
				wfDebugLog('avatar', "Avatar: ran command $cmd got result $result and code $ret\n");
			}
		} else {
			wfDebugLog('avatar', "trace 2: $ret from: $cmd");
			return false;
		}

		$paths = array(
			self::getAvatarOutPath($filename) . $filename,
			"/User:" . $wgUser->getName(),
		);
		self::purgePath($paths);
		self::uploadS3($crop_out, self::getAvatarOutPath($filename) . $filename);
#self::logExists('crop_out-uploaded', $crop_out);

		$dbw = wfGetDB(DB_MASTER);
		$sql = "INSERT INTO avatar (av_user, av_patrol, av_dateAdded) " .
			"VALUES ('".$wgUser->getID()."',0,'".wfTimestampNow()."') " .
			"ON DUPLICATE KEY UPDATE av_patrol=0, av_dateAdded='".wfTimestampNow()."'";
		$ret = $dbw->query($sql, __METHOD__);

		Hooks::run("AvatarUpdated", array($wgUser));

		return true;
	}

	public function execute($par) {
		global $wgUser, $wgOut, $wgTitle, $wgRequest, $wgImageMagickConvertCommand;
		$dbw = wfGetDB(DB_MASTER);

		if (!$wgUser || $wgUser->isAnon()) {
			$wgOut->addHtml('You must <a href="/Special:UserLogin">sign in</a> to use this page.');
			return;
		}

		$type = $wgRequest->getVal('type');
		if ($type == 'upload') {
			$wgOut->setArticleBodyOnly(true);

			//GET EXT
			$fileext = array('jpg','png','gif','jpeg');
			$f = basename( $_FILES['uploadedfile']['name']);
			$basename = "";
			$extensions = "";

			wfDebugLog('avatar', "Avatar: Working with file $f\n");
			$pos = strrpos($f, '.');
			if ($pos === false) { // dot is not found in the filename
				$msg = "Invalid filename extension not recognized filename: $f\n";
				$response['status'] = 'ERROR';
				$response['msg'] = $msg;

				wfDebugLog('avatar', "Avatar: Invalid extension no period $f\n");
				echo json_encode($response);
				return;
			} else {
				$basename = substr($f, 0, $pos);
				$extension = substr($f, $pos+1);
				if ( !in_array(strtolower($extension), array_map('strtolower', $fileext)) ) {
					$msg = "Invalid filename extension not recognized filename: $f\n";
					$response['status'] = 'ERROR';
					$response['msg'] = $msg;
					wfDebugLog('avatar', "Avatar: $msg");
					echo json_encode($response);
					return;
				}
			}
			wfDebugLog('avatar', "Avatar: filename accepted $f\n");

			$filename = "tmp2_" . $wgUser->getID() . "." . strtolower($extension);
			$target_path = self::getAvatarInFilePath($filename) . $filename;
			$filename = "tmp_" . $wgUser->getID() . ".jpg";
			$target_path2 = self::getAvatarInFilePath($filename) . $filename;

			wfDebugLog('avatar', "Avatar: moving files {$_FILES['uploadedfile']['tmp_name']} to {$target_path}\n");
			if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
				wfDebugLog('avatar', "Avatar: Moved uploaded file from {$_FILES['uploadedfile']['tmp_name']} to {$target_path}\n");

#self::logExists('execute1', $target_path);
				//converting filetype
				$count = 0;
				while ($count < 3) {
					$cmd = "$wgImageMagickConvertCommand " . wfEscapeShellArg($target_path) . " " . wfEscapeShellArg($target_path2);
					$result = wfShellExec($cmd, $ret);
					wfDebugLog('avatar', "Avatar: Converting, $cmd result $result code: $ret\n");

					if ($ret != 127) {
						break;
					} else {
						$count++;
					}
				}

				$ratio = 1;
				$maxw = 350;
				$maxh = 225;
				$size = getimagesize($target_path2);
				$width = $size[0];
				$height = $size[1];

				if ($width < $maxw && $height < $maxh) {
					$ratio = 1;
				} else {
					if ($maxh/$height > $maxw/$width) {
						$ratio = $maxw/$width;
					} else {
						$ratio = $maxh/$height;
					}
				}

				$msg = "The file ".  basename( $_FILES['uploadedfile']['name']).  " has been uploaded. ";
				if ($ratio != 1) {
					$newwidth = number_format(($width * $ratio), 0, '.', '');
					$newheight = number_format(($height * $ratio), 0, '.', '');
					$cmd = "$wgImageMagickConvertCommand " . wfEscapeShellArg($target_path2) . " -resize " . wfEscapeShellArg("{$newwidth}x{$newheight}") . " " . wfEscapeShellArg($target_path2);
					$result = wfShellExec($cmd, $ret);
					wfDebugLog('avatar', "Avatar: Converting, $cmd result $result code: $ret\n");
				}
#wfDebugLog('avatar', "Avatar: upload to $target_path2, " . self::getAvatarInPath($filename) . $filename . "\n");
#self::logExists('execute2', $target_path2);
				self::uploadS3($target_path2, self::getAvatarInPath($filename) . $filename);
#self::logExists('execute3', $target_path2);
				if ($wgRequest->getVal('reload')) {
					wfDebugLog('avatar', "Avatar: Got a reload, returning\n");
					$location = wfExpandUrl('/Special:Avatar?type=nonmodal&reload=1');
					header( 'Location: ' . $location );
					return;
				}

				$response['status'] = 'SUCCESS';
				$response['msg'] = $msg;
				$response['basename'] = $basename;
				$response['extension'] = "jpg";
				wfDebugLog('avatar', "Avatar: Success, " . print_r($response, true) . "\n");
				$res =  json_encode($response);
				echo $res;
				return;
			} else {
				if ($wgRequest->getVal('reload')) {
					$location = wfExpandUrl('/Special:Avatar?type=nonmodal');
					header( 'Location: ' . $location );
					return;
				}
				wfDebugLog('avatar', "Avatar: Unable to move uploaded file from {$_FILES['uploadedfile']['tmp_name']} to {$target_path}\n");
				$msg = "There was an error uploading the file, please try again!";
				$response['status'] = 'ERROR';
				$response['msg'] = $msg;
				echo json_encode($response);
				return;
			}
		} elseif ($type == 'crop') {
			$wgOut->setArticleBodyOnly(true);
			if (self::crop()) {
				$wgOut->addHTML('SUCCESS');
			} else {
				$wgOut->addHTML('FAILED');
			}
		} elseif ($type == 'unlink') {
			$wgOut->setArticleBodyOnly(true);
			$ret = self::removePicture();
			if (preg_match('/SUCCESS/', $ret)) {
				$wgOut->addHTML('SUCCESS:'.$ret);
			} else {
				$wgOut->addHTML('FAILED:'.$ret);
			}
		} elseif ($type == 'nonmodal') {
			self::displayNonModal();
		} else {
			//no longer want to show this page
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
		}

	}

	public static function getAvatarInPath($name) {
		global $wgUploadPath;
		// hash level is 2 deep
		$path = "$wgUploadPath/avatarIn/" . self::getHashPath($name);
		return $path;
	}

	public static function getAvatarOutPath($name) {
		global $wgUploadPath;
		// hash level is 2 deep
		$path = "$wgUploadPath/avatarOut/" . self::getHashPath($name);
		return $path;
	}

	public static function getAvatarInFilePath($name) {
		global $wgUploadDirectory;
		$path = "$wgUploadDirectory/avatarIn/" . self::getHashPath($name);
		return $path;
	}

	public static function getAvatarOutFilePath($name) {
		global $wgUploadDirectory;
		// hash level is 2 deep
		$path = "$wgUploadDirectory/avatarOut/" . self::getHashPath($name);
		return $path;
	}

	public static function getHashPath($name) {
		return FileRepo::getHashPathForLevel($name, 2);
	}

	public static function insertAvatarIntoDiscussion($discussionText) {
		$text = "";
		$parts = preg_split('@(<p class="de_user".*</p>)@im', $discussionText, 0, PREG_SPLIT_DELIM_CAPTURE);
		for ($i = 0; $i < sizeof($parts); $i++) {
			if (preg_match('@(<p class="de_user".*</p>)@im', $parts[$i])) {
				$needle = 'href="/User:';
				$pos = strpos($parts[$i], $needle);

				// if the user has a redlink for their user link
				// then the format we are searching for is a bit different
				if (!$pos && strpos($parts[$i], 'redlink')) {
					$needle = 'index.php?title=User:';
					$pos = strpos($parts[$i], $needle);
				}

				$length = strlen($needle);
				$endpos = strpos($parts[$i], '"', $pos + $length);

				// to get the username, find the substring offset by the length of the '$needle' search value
				$username = substr($parts[$i], $pos + $length, $endpos - $pos - $length);
				$arr = explode("&", $username, 2);
				$username = $arr[0];

				$len = strlen('<p class="de_user">');
				$img = Html::element( 'img', array( 'src' => wfGetPad( self::getAvatarURL( $username ) ), 'alt' => $username, 'style' => 'width:40px' ) );
				$text .= substr($parts[$i], 0, $len) . $img . substr($parts[$i], $len);
			}
			else {
				$text .= $parts[$i];
			}
		}
		return $text;
	}

	public static function updateAvatar(int $whUserId, string $avatarUrl) {
		global $wgLanguageCode;

		if ($wgLanguageCode != 'en') {
			return;
		}

		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select('avatar', ['av_image', 'av_patrol'], ["av_user" => $whUserId]);
		$row = $dbw->fetchObject($res);
		if (!$row) {
			$dbw->upsert(
				$table = 'avatar',
				$rows = ['av_user' => $whUserId, 'av_image' => $avatarUrl, 'av_patrol' => 0],
				$uniqueIndexes = ['av_user'],
				$set = ['av_image' => $avatarUrl, 'av_patrol' => 0]
			);
		} elseif ($row->av_image && ($row->av_patrol == 0 || $row->av_patrol == 1)) {
			$dbw->upsert(
				$table = 'avatar',
				$rows = ['av_user' => $whUserId, 'av_image' => $avatarUrl],
				$uniqueIndexes = ['av_user'],
				$set = ['av_image' => $avatarUrl]
			);
		}
	}

}

