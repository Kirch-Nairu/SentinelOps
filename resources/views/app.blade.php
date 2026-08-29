<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="sentinelops-actor" content="{{ auth()->id() ?? '' }}">
    <meta name="sentinelops-organization" content="{{ session('organization_id') ?? '' }}">
    <meta name="theme-color" content="#0b1114">
    <title inertia>{{ config('app.name', 'SentinelOps') }}</title>
    @viteReactRefresh
    @vite(['resources/js/app.tsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
