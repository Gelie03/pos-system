<?php
/**
 * logout.php  -  ends the session (forgets the user and the cart) and goes to the login page.
 */
require_once 'includes/db_connect.php';

$_SESSION = [];          // empty everything stored in the session
session_destroy();       // end the session

header('Location: login.php');
exit;
