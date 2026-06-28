<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dot.Tutor</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { corePlugins: { preflight: false } }</script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; background: #0b1326; color: #dae2fd; font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; line-height: 1; }
        [x-cloak] { display: none !important; }
        .sl { display:flex;align-items:center;gap:0.75rem;padding:0.65rem 1rem;border-radius:0.5rem;font-family:'Manrope',sans-serif;font-size:0.875rem;font-weight:600;text-decoration:none;color:#b7c8e1;opacity:0.75;transition:all 0.2s; }
        .sl:hover { background:rgba(26,36,56,0.9);opacity:1;color:#b6c4ff; }
        .sl.active { border-left:4px solid #6366f1;background:rgba(99,102,241,0.1);color:#a5b4fc;opacity:1; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    </style>
    @livewireStyles
    <script defer src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js"></script>
</head>
<body>
    <x-banner />
    <aside style="position:fixed;left:0;top:0;height:100vh;width:272px;background:#131b2e;border-right:1px solid rgba(67,70,86,0.2);z-index:50;overflow-y:auto;padding:1.75rem 1rem;display:flex;flex-direction:column;">
        <div style="margin-bottom:1.75rem;padding:0 0.5rem;">
            <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:0.75rem;text-decoration:none;">
                <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:18px;color:#fff;">school</span>
                </div>
                <div>
                    <div style="font-family:'Manrope',sans-serif;font-size:1rem;font-weight:800;color:#b6c4ff;">Dot.Tutor</div>
                    <div style="font-size:0.58rem;font-weight:600;color:#8d90a2;letter-spacing:0.15em;text-transform:uppercase;">Online Tutoring</div>
                </div>
            </a>
        </div>
        <div style="margin:0 0.25rem 1.5rem;">
            <a href="#" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;border-radius:9999px;background:linear-gradient(135deg,#6366f1,#4f46e5);padding:0.7rem 1.25rem;font-family:'Manrope',sans-serif;font-size:0.78rem;font-weight:700;color:#fff;text-decoration:none;box-shadow:0 8px 20px rgba(99,102,241,0.3);">
                <span class="material-symbols-outlined" style="font-size:18px;">add_circle</span>
                Book Session
            </a>
        </div>
        <nav style="flex:1;display:flex;flex-direction:column;gap:0.15rem;">
            <a href="{{ route('dashboard') }}" class="sl {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="material-symbols-outlined" style="font-size:20px;">dashboard</span><span>Dashboard</span>
            </a>
            <a href="#" class="sl"><span class="material-symbols-outlined" style="font-size:20px;">calendar_month</span><span>Sessions</span></a>
            <a href="#" class="sl"><span class="material-symbols-outlined" style="font-size:20px;">person_search</span><span>Find Tutors</span></a>
            <a href="#" class="sl"><span class="material-symbols-outlined" style="font-size:20px;">menu_book</span><span>Subjects</span></a>
            <a href="#" class="sl"><span class="material-symbols-outlined" style="font-size:20px;">folder_open</span><span>Resources</span></a>
            <a href="#" class="sl"><span class="material-symbols-outlined" style="font-size:20px;">star_rate</span><span>Reviews</span></a>
        </nav>
        @auth
        <div style="margin-top:auto;padding-top:1.25rem;border-top:1px solid rgba(67,70,86,0.2);">
            <div style="display:flex;align-items:center;gap:0.75rem;padding:0 0.5rem;">
                <div style="width:36px;height:36px;border-radius:9999px;background:#6366f1;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#fff;flex-shrink:0;">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div style="min-width:0;">
                    <div style="font-size:0.75rem;font-weight:700;color:#dae2fd;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Auth::user()->name }}</div>
                    <div style="font-size:0.6rem;color:#8d90a2;text-transform:uppercase;letter-spacing:0.08em;">Student / Tutor</div>
                </div>
            </div>
        </div>
        @endauth
    </aside>
    @livewire('navigation-menu')
    <div style="margin-left:272px;padding-top:72px;min-height:100vh;background:#0b1326;">
        @if(isset($header))<div style="padding:1.75rem 2.5rem 0;">{{ $header }}</div>@endif
        <main>{{ $slot }}</main>
    </div>
    <div style="position:fixed;bottom:1.5rem;right:1.5rem;display:flex;align-items:center;gap:0.5rem;padding:0.4rem 0.85rem;background:rgba(49,57,77,0.7);backdrop-filter:blur(16px);border-radius:9999px;border:1px solid rgba(67,70,86,0.2);z-index:50;">
        <div style="width:6px;height:6px;border-radius:9999px;background:#22c55e;animation:pulse 2s infinite;"></div>
        <span style="font-size:0.58rem;font-weight:700;color:rgba(218,226,253,0.5);text-transform:uppercase;letter-spacing:0.18em;">Dot.Tutor Online</span>
    </div>
    @stack('modals')
    @livewireScripts
</body>
</html>
