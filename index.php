<?php
/*
Copyright (c) 2019 Josiah Spackman

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE
*/

session_start ();

// Require users operations
require_once dirname(__FILE__) . "/classes/users.php";

// Current stored value
$user_addr = $user_info = false;
// He is already specify QR
if (isset($_SESSION['user']['address'])) 
{
	// Load all we already know about user
	$user_addr = $_SESSION['user']['address'];
	if (!empty($_SESSION['user']['info']))
		$user_info = $_SESSION['user']['info'];

	// He is logged fully
	if ($user_addr && $user_info) {
		header ('location: dashboard.php');
		exit;
	}
}

// QR not activated yet?
// 1 - Scan QR first. 2 - Wait details for registration
$step = (!isset($_SESSION['user'])) ? 1 : 2;

// DigiID is required for login (do not modify)
// DAO could be replace by your CMS/FRAMEWORK database classes
require_once dirname(__FILE__) . "/classes/DigiID.php";
require_once dirname(__FILE__) . "/classes/DAO.php";
$digiid = new DigiID();
// generate a nonce
$nonce = $digiid->generateNonce();
// build uri with nonce, nonce is optional, but we pre-calculate it to avoid extracting it later
$digiid_uri = $digiid->buildURI(DIGIID_SERVER_URL . 'callback.php', $nonce);

// Insert nonce + IP in the database to avoid an attacker go and try several nonces
// This will only allow one nonce per IP, but it could be easily modified to allow severals per IP
// (this is deleted after an user successfully log in the system, so only will collide if two or more users try to log in at the same time)
$dao = new DAO();
$result = $dao->insert($nonce, @$_SERVER['REMOTE_ADDR']);
if ($dao->error) die ('');

if(!$result)
{
	echo "<pre>";
	echo "Database failer\n";
	var_dump($dao);
	die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Digi-ID demo site</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<link rel="stylesheet" type="text/css" href="css/main.css">
<?php if (DIGIID_GOOGLE_ANALYTICS_TAG != '') : ?><!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?= DIGIID_GOOGLE_ANALYTICS_TAG ?>"></script>
	<script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date()); gtag('config', '<?= DIGIID_GOOGLE_ANALYTICS_TAG ?>');</script><?php endif ?>
</head>
<body>
	
	<div class="limiter">
		<div class="container-login">
			<div class="wrap-login">
				<div id="step1" class="login-form hidden">
					<div class="bigscreen-padding hidden-xs"></div>
					<span class="login-form-title" style="padding-bottom: 20px">
						Login or Register:
					</span>
					<div class="center">
						<a href="<?= $digiid_uri ?>"><div><img id="qr" alt="Click on QRcode to activate compatible desktop wallet" border="0" /></div></a>
						<p class="comment">Scan it from your mobile phone. Requires DigiByte application:</p>
						<p class="applications">
							<a href="https://itunes.apple.com/us/app/digibyte/id1378061425" target="_blank"><img src="images/appstore.png" height="32px" /></a>
							<a href="https://play.google.com/store/apps/details?id=io.digibyte" target="_blank"><img src="images/android.png" height="32px" /></a>
						</p>
					</div>
				</div>
				<div id="step2" class="login-form hidden">
					<div class="bigscreen-padding hidden-xs"></div>
					<form id="regform" action="<?= DIGIID_SERVER_URL ?>register.php" method="post">
					<span class="login-form-title" style="padding-bottom: 42px;">
						Please enter your details:
					</span>
					<div class="wrap-input100">
						<input class="input100" type="text" name="fio" required="true">
						<span class="focus-input100"></span>
						<span class="label-input100">Cellphone number</span>
					</div>
					<div class="container-login-form-btn">
						<input type="submit" class="login-form-btn main" value="Register" />
					</div>
					</form>
					<form action="<?= DIGIID_SERVER_URL ?>logout.php" method="post">
					<div class="container-login-form-btn" style="margin-top:5px">
						<input type="submit" class="login-form-btn" value="Cancel" />
					</div>
					</form>
				</div>
				<div class="login-more">
				</div>
			</div>
		</div>
	</div>
	<div id="source-link"><a href="https://github.com/ChillingSilence/covid19-checkin" title="Download it in open source"><img src="images/open-source-code.png" /></a>
	</div>
	
	<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<script src="js/digiQR.min.js"></script>
	<script>var step=<?= $step ?>; var nonce='<?= $nonce ?>';</script>
	<script>$("#qr").attr("src", DigiQR.id("<?= $digiid_uri ?>",300,2,0.5));</script>
	<script src="js/main.js"></script>
</body>
</html>
