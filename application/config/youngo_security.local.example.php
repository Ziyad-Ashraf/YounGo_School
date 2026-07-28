<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| YounGo local/server security override example
|--------------------------------------------------------------------------
|
| Copy this file to application/config/youngo_security.local.php only on a
| local/server environment. The copied file is ignored by Git.
|
| Do not commit a real encryption key. Do not paste Paymob credentials here.
| Generate the key outside Git/reports/chat and store it only in the ignored
| local/server file.
|
*/

$config['youngo_security'] = array(
    // Replace only in the ignored local/server file. Leave empty here.
    'encryption_key' => '',
);
