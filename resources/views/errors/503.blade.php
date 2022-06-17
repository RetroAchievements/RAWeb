@extends('errors.error', [
    'pageTitle' => __('Service Unavailable'),
    'title' => __($exception->getMessage() ?: 'Service Unavailable'),
])
