<?php

return [
    'google_maps_api_key' => trim((string)(getenv('GOOGLE_MAPS_API_KEY') ?: '')),
    'search_endpoint' => trim((string)(getenv('MAPS_SEARCH_ENDPOINT') ?: 'https://photon.komoot.io/api/')),
];
