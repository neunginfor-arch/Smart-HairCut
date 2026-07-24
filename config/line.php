<?php

return [
    'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
    'channel_secret' => env('LINE_CHANNEL_SECRET'),
    'admin_user_ids' => env('LINE_ADMIN_USER_IDS') ?: env('LINE_OWNER_USER_ID'),
    'add_friend_url' => env('LINE_OA_ADD_FRIEND_URL'),
];
