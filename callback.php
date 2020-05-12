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

require_once dirname(__FILE__) . "/classes/DigiID.php";
require_once dirname(__FILE__) . "/classes/DAO.php";
require_once dirname(__FILE__) . "/classes/users.php";

$digiid = new DigiID();
$dao = new DAO();

$input = $_POST;
$post_data = json_decode(file_get_contents('php://input'), true);
// SIGNED VIA PHONE WALLET (data is send as payload)
if($post_data!==null) {
    $input = $post_data;
}

// ALL THOSE VARIABLES HAVE TO BE SANITIZED !
$signValid = $digiid->isMessageSignatureValidSafe(@$input['address'], @$input['signature'], @$input['uri']);
$nonce = $digiid->extractNonce($input['uri']);
if($signValid && $dao->checkNonce($nonce) && ($digiid->buildURI(DIGIID_SERVER_URL . 'callback.php', $nonce) === $input['uri'])) {
    $dao->update($nonce, $input['address']);

    session_start();
    $user = new token_user ($input['address']);
    $_SESSION['user'] = array (
	'address' => $input['address'],
	'info' => $user->get_info()
	);

    // SIGNED VIA PHONE WALLET (data is send as payload)
    if($post_data!==null) {
        //DO NOTHING

    } else {
        // SIGNED MANUALLY (data is stored in $_POST+$_REQUEST vs payload)
        // SHOW SOMETHING PRETTY TO THE USER

        header("location: index.php");
    }


    $data = array ('address'=>$input['address'], 'nonce'=>$nonce);
    header('Content-Type: application/json');
    echo json_encode($data);
}