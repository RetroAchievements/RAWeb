@extends('errors.error', [
    'pageTitle' => __('Not Found'),
    'title' => isset($isBannedUser) && $isBannedUser ? __('This user has been banned.') : __('Not Found'),
    'image' => isset($isBannedUser) && $isBannedUser ? false : null, // null uses the default 404 image
])
