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
set_time_limit (5);

// Result by default
$result = array ('address' => false);

// If required param is not passed
if (!isset($_POST['nonce'])) {
    echo json_encode($result);
    exit;
}

require_once dirname(__FILE__) . "/classes/DAO.php";
require_once dirname(__FILE__) . "/classes/users.php";
$dao = new DAO();

// Check if this nonce is logged or not
$address = $dao->address($_POST['nonce'], @$_SERVER['REMOTE_ADDR']);
// Logged
if ($address !== false) {
    // Get info about user from db and store into session
    $user = new token_user($address);
    session_start();
    $result = $_SESSION['user'] = array (
	'address' => $address, 
	'info' => $user->get_info()
	);
}

//return address/false to tell the VIEW it could log in now or not
echo json_encode($result);
