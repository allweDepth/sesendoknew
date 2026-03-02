<?php
require_once 'app/Console/ProfileGenerator.php';

$generator = new ProfileGenerator();
$generator->generate();
// Run:
// php generate_profiles.php