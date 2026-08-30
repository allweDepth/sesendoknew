<?php
require_once 'app/Console/ProfileValidator.php';

$validator = new ProfileValidator();
$errors = $validator->run();

exit($errors === 0 ? 0 : 1);
