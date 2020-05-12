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
SOFTWARE.

*/

session_start();
require_once dirname(__FILE__) . "/config.php";
require_once dirname(__FILE__) . "/classes/users.php";

// No user logged in
if (empty($_SESSION['user']['address']) || empty($_SESSION['user']['info']))
{
	header ('location: index.php');
	exit;
}

// Set variables with user info
$address = $_SESSION['user']['address'];
$user_info = $_SESSION['user']['info'];
$user = new token_user($_SESSION['user']['address']);

// Change state of showing all
if (isset($_GET['show']))
	$_SESSION['show'] = $_GET['show'];
$show = $_SESSION['show'];

// Get current permission-levels, this needs to be done first before anything further
$permissions = $user->get_permissions();

// Admin also may authorize or reject
if ($permissions['ispermitted'] != REJECTED_USER /* not rejected */
	&& $permissions['isadmin'] /* admin */
	&& isset($_REQUEST['act'])) /* want action */
{
	switch ($_REQUEST['act']) {
		case 'change_user_state':
			// ?state=ispermitted[,isadmin]
			$new_state = explode(',', $_REQUEST['state']);
			// ispermitted
			if (isset($new_state[0]))
			    $user->grantaccess($_REQUEST['addr'], $new_state[0]);
			// isadmin
			if (isset($new_state[1]))
			    $user->grantadmin($_REQUEST['addr'], $new_state[1]);
			break;
	}
	header ('location: dashboard.php');
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">

    <title>COVID-19 Check-in</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Custom styles for this template -->
    <link rel="stylesheet" type="text/css" href="css/dashboard.css?27019">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

<?php if (DIGIID_GOOGLE_ANALYTICS_TAG != '') : ?><!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= DIGIID_GOOGLE_ANALYTICS_TAG ?>"></script>
    <script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date()); gtag('config', '<?= DIGIID_GOOGLE_ANALYTICS_TAG ?>');</script><?php endif ?>
  </head>

  <body>

    <div class="site-wrapper">

      <div class="site-wrapper-inner">

        <div class="cover-container">

          <div class="masthead clearfix">
<!--            <div class="inner">
              <h3 class="masthead-brand">Cover</h3>
              <nav>
                <ul class="nav masthead-nav">
                  <li class="active"><a href="#">Home</a></li>
                </ul>
              </nav>
            </div>-->
          </div>

          <div class="inner cover">
            <h1 class="cover-heading">Hello, <?= $user_info['fio']; ?>!</h1>
		<?php
		// We show the user their Digi-ID address, but clarify it's for this site
		// I don't have a real use for this at present but in-future we may need it so it's here for now
		// The theory being if we have two Johns who sign up at once we could always show the Admin the Digi-ID at the same time to avoid any ambiguity?
		// That said the user could just request that we "forget" their account, and sign up again using an additional identifier such as a surname if it was really an issue
		// It's fine for now though :)
		?>
            <p class="lead">Your Digi-ID for this site: <?= $address ?></p>

            <p class="lead" style="margin-top: 40px">
		<?php
		$action_allowed = false;
		if ($permissions['ispermitted'] == REJECTED_USER) {
			// Presume the user has been rejected because anything other than 1 or 2 is a no-go
			echo "Sorry your request for permission has been denied.";
			}
		else if ($permissions['ispermitted'] == PENDING_USER) {
			echo "Your access request is pending approval by an administrator. Please ask your admin to approve / deny your request";
			}
		else if ($permissions['ispermitted'] == AUTHORIZED_USER || $permissions['isadmin'] == ADMIN_USER) {
			// We only have the one "unlock" at present with unlock.php automatically firing the unlock mechanism, but this can easily be modified in future for further expansion / multiple doors
			// This could probably be done on this page itself, but I have a feeling that making it a new page will be better for extensibility in-future
			$action_allowed = true;
			}
		?>

            </p>

	<p class="lead" style="margin-top: 40px">
	<?php
	// Show the Authorized User stuff, like a button to open a door etc
		if ($action_allowed) {
		echo <<<HTML
		<p class="lead">Congratulations, you are authorized to access.<br />
		<a href='unlock.php' class='btn btn-lg btn-default'>Unlock the door</a></p><br />
HTML;
		}



	// Show the admin-only stuff
		if ($permissions['isadmin'] == ADMIN_USER) :
		$only_pending_btn_class	= $show == 'PENDING' ? 'primary':'';
		$not_rejected_btn_class = $show == 'NONREJECTED' ? 'primary':'';
		$show_all_btn_class	= $show == 'ALL' ? 'primary':'';
?>
		<p class="lead">You're an admin! You can authorize additional users once they've performed an initial log-in.</p>

		<p>
			<a class="btn btn-<?php echo $only_pending_btn_class ?>" href="?show=PENDING" role="button">PENDING ONLY</a>
			<a class="btn btn-<?php echo $not_rejected_btn_class ?>" href="?show=NONREJECTED" role="button">NON REJECTED</a>
			<a class="btn btn-<?php echo $show_all_btn_class ?>" href="?show=ALL" role="button">ALL USERS</a>
		</p>

		<?php
		switch ($show) {
			case 'PENDING':		$requests = $user->get_pending_requests();	break;
			case 'NONREJECTED':	$requests = $user->get_nonrejected_list();	break;
			case 'ALL':		$requests = $user->get_users_list();		break;
			default:		$requests = array ();
		}

		$const = get_defined_constants();
		foreach ($requests as $line) {
			// There must be a user who wants access so we do them one at a time
			// We do it this way for two reasons:
			// #1 I'm lazy and can't be bothered making the code through all of them at once
			// #2 It looks better from a UI perspective at this point and again, I'm too lazy to fit it all in on one page
			$line['isadmin_text']	= ($line['isadmin'] == ADMIN_USER) ? 'admin':'';

			// Authorize the user, could probably be done on this page but filler link for now
			// Reject the user, again could probably be done on this page but filler for now coz I'm editing things in the database
			$remove_or_make_admin = $line['isadmin']>0 ? 0:1;
			$authorized_btn_class	= ($line['ispermitted'] == AUTHORIZED_USER) ? 'success':'default';
			$rejected_btn_class	= ($line['ispermitted'] == REJECTED_USER) ? 'danger':'default';
			$admin_btn_class	= ($line['isadmin'] == ADMIN_USER) ? 'warning':'default';

			// Alert if admin remove own access
			$alert = ($line['addr'] != $_SESSION['user']['address']) ? ''
				: " onclick='javascript:if (!confirm(\"You will lost access. Are you sure?\")) return false'";

			echo <<<HTML
			<table class="table" style="text-align:left">
			<tbody>
			<tr>
			<td width="100%"><span class="lead">{$line['fio']}</span></td>
			<td>
			    <span>{$line['addr']}</span>
			</td>
			<td nowrap>
				<a href='?act=change_user_state&addr={$line['addr']}&state={$const['AUTHORIZED_USER']}'
				    class='btn btn-$authorized_btn_class'>Authorized</a>
				<a href='?act=change_user_state&addr={$line['addr']}&state={$const['REJECTED_USER']}'
				    class='btn btn-$rejected_btn_class' $alert>Rejected</a>
				<a href='?act=change_user_state&addr={$line['addr']}&state={$const['ADMIN_USER']},{$remove_or_make_admin}'
				    class='btn btn-$admin_btn_class' $alert>Admin</a></td>
			</tr>
			</tbody>
			</table>
HTML;
		}
		?>
	<?php endif ?>
          </div>

            <p class="lead" style="margin-top: 20px">
              <a href="logout.php" class="btn btn-lg">Logout</a><?= (!$user->is_banned()) ? '|| <a href="forget.php" class="btn btn-lg">Forget my account</a>' : '' ?>
            </p>


          <div class="mastfoot">
            <div class="inner">
              <p>Need help? <a href="https://t.me/DigiByteDevelopers">Ask in Telegram</a></p>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

  </body>
</html>
