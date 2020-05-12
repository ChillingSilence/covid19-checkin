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

require_once dirname(__FILE__) . "/config.php";
require_once dirname(__FILE__) . "/classes/users.php";

// Address must be defined for register
if (isset($_SESSION['user']['address']))
{
	// Save it
	$user = new token_user($_SESSION['user']['address']);

	// If there's no admin users, then make this user the initial admin
	$is_first = $user->in_empty_list();
	$user->insert ($_POST);
	if ($is_first) $user->initial_admin();
	//$dao = new DAO();
	//$dao->remove($nonce);
	$_SESSION['user']['info'] = $user->get_info ();
}

header ('location: ' . DIGIID_SERVER_URL);
