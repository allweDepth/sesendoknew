<?php
require_once 'app/Console/ProfileValidator.php';

$validator = new ProfileValidator();
$validator->run();
// php validate.php