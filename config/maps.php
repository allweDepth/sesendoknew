<?php

return [
    'google_maps_api_key' => trim((string)(getenv('GOOGLE_MAPS_API_KEY') ?: '')),
];
