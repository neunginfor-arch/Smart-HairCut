<?php

return [
    'endpoint' => env('GHOSTX_SLIP_VERIFY_URL', 'https://externalauth.ghostxapi.xyz/qr/scan'),
    'timeout' => (int) env('GHOSTX_SLIP_VERIFY_TIMEOUT', 20),
];
