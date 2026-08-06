<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dot.Tutor') }}</title>

        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Figtree:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles

        <style>
            :root {
                --paper: #fbf8f0;
                --paper-soft: #f1ebda;
                --ink: #1e2a1b;
                --ink-soft: #253524;
                --gold: #c9971e;
                --gold-bright: #f1c62e;
                --leaf: #2a6e2f;
                --leaf-bright: #40a535;
                --chalk: #f5f2e6;
                --moss: #55634f;
                --sage: #b9c2ae;
                --line: rgba(30, 42, 27, 0.12);
                --line-ink: rgba(245, 242, 230, 0.14);
                --font-display: 'Fraunces', Georgia, serif;
                --font-body: 'Figtree', system-ui, sans-serif;
                --font-mono: 'Space Mono', ui-monospace, monospace;
            }
            html { background: var(--paper); }
            body { background: var(--paper); }
            .font-display { font-family: var(--font-display); }
            .font-mono { font-family: var(--font-mono); }
        </style>
    </head>
    <body>
        <div class="font-sans text-[var(--ink)] antialiased" style="font-family: var(--font-body);">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
