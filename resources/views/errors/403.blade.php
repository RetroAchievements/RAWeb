@extends('errors.error', [
    'image' => asset('assets/images/cheevo/angry.webp'),
    'pageTitle' => __('Forbidden'),
    'title' => __($exception->getMessage() ?: 'Forbidden'),
])
