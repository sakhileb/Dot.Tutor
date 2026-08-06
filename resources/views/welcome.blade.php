<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dot.Tutor — Book a tutor who actually teaches your subject</title>
        <meta name="description" content="A subject-matched tutoring marketplace. Filter approved tutors by subject and level, see their real hourly rate, and book a session that locks in the price the moment you confirm it.">

        <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Figtree:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script defer src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js"></script>

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
                --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
            }
            html { background: var(--paper); }
            body { font-family: var(--font-body); background: var(--paper); color: var(--ink); }
            .font-display { font-family: var(--font-display); }
            .font-mono { font-family: var(--font-mono); }

            .press { transition: transform 160ms var(--ease-out); }
            .press:active { transform: scale(0.97); }

            @media (prefers-reduced-motion: no-preference) {
                .reveal {
                    opacity: 0;
                    transform: translateY(14px);
                    transition: opacity 600ms var(--ease-out), transform 600ms var(--ease-out);
                }
                .reveal.is-visible { opacity: 1; transform: translateY(0); }
            }
            @media (prefers-reduced-motion: reduce) {
                .reveal { opacity: 1; transform: none; }
            }

            @media (hover: hover) and (pointer: fine) {
                .row-hover:hover { background: rgba(30, 42, 27, 0.03); }
                .row-hover-ink:hover { background: rgba(245, 242, 230, 0.03); }
                .link-underline { background-size: 0% 1px; }
                .link-underline:hover { background-size: 100% 1px; }
            }
            .link-underline {
                background-image: linear-gradient(currentColor, currentColor);
                background-position: 0 100%;
                background-repeat: no-repeat;
                transition: background-size 220ms var(--ease-out);
            }
        </style>
    </head>
    <body class="antialiased">

        <!-- Nav -->
        <header
            x-data="{ scrolled: false, mobileMenuOpen: false }"
            @scroll.window="scrolled = window.pageYOffset > 24"
            :class="scrolled ? 'bg-[#1e2a1b]/95 backdrop-blur-md border-b border-[var(--line-ink)]' : 'border-b border-transparent'"
            class="fixed top-0 left-0 right-0 z-50 transition-colors duration-300"
        >
            <nav class="max-w-[1400px] mx-auto px-5 sm:px-8 py-3 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5 press">
                    <img src="{{ asset('images/logo.png') }}" alt="Dot.Tutor" class="h-16 sm:h-20 w-auto">
                </a>

                <div class="hidden md:flex items-center gap-8 font-mono text-[13px] tracking-wide uppercase text-[var(--sage)]">
                    <a href="#features" class="link-underline hover:text-[var(--chalk)] pb-0.5">Features</a>
                    <a href="#capabilities" class="link-underline hover:text-[var(--chalk)] pb-0.5">Architecture</a>
                </div>

                @if (Route::has('login'))
                    <div class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="press flex items-center gap-2 px-5 py-2.5 bg-[var(--gold)] hover:bg-[var(--gold-bright)] text-[#1e2a1b] text-sm font-display font-semibold rounded-lg transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden sm:block text-sm font-medium text-[var(--sage)] hover:text-[var(--chalk)] transition-colors">
                                Sign in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="press px-5 py-2.5 bg-[var(--gold)] hover:bg-[var(--gold-bright)] text-[#1e2a1b] text-sm font-display font-semibold rounded-lg transition-colors">
                                    Create account
                                </a>
                            @endif
                        @endauth

                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden press p-2 -mr-2 text-[var(--chalk)]" aria-label="Toggle menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7h16M4 12h16M4 17h16"></path>
                                <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endif
            </nav>

            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="md:hidden border-t border-[var(--line-ink)] bg-[#1e2a1b]"
                 style="display: none;">
                <div class="flex flex-col px-5 py-4 gap-1 font-mono text-sm uppercase tracking-wide">
                    <a href="#features" class="px-3 py-2.5 text-[var(--sage)] hover:text-[var(--chalk)]">Features</a>
                    <a href="#capabilities" class="px-3 py-2.5 text-[var(--sage)] hover:text-[var(--chalk)]">Architecture</a>
                    @guest
                        <a href="{{ route('login') }}" class="px-3 py-2.5 text-[var(--sage)] hover:text-[var(--chalk)]">Sign in</a>
                    @endguest
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative min-h-[100dvh] flex items-end overflow-hidden bg-[#1e2a1b]">
            {{-- Photo: two people at a laptop, one showing the other something, by Centre for Ageing Better (@ageing_better), unsplash.com/photos/a-woman-showing-a-woman-something-on-the-laptop-ukDFRP2RNA0 --}}
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1664382953647-5c6c76dd63b9?q=80&w=2400&auto=format&fit=crop');"></div>
            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(30,42,27,0.55) 0%, rgba(30,42,27,0.75) 45%, #1e2a1b 92%);"></div>
            <div class="absolute inset-0" style="background: linear-gradient(90deg, #1e2a1b 0%, rgba(30,42,27,0.55) 38%, transparent 68%);"></div>

            <!-- Line-art silhouette — echoes the teacher-at-chalkboard icon in the real Dot.Tutor mark -->
            <svg class="hidden lg:block absolute right-[6%] bottom-0 h-[72%] w-auto opacity-[0.15] pointer-events-none" viewBox="0 0 240 320" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="130" y="40" width="90" height="62" rx="2" stroke="#f5f2e6" stroke-width="3"/>
                <path d="M144 58H196M144 70H184" stroke="#f5f2e6" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M148 92L206 56" stroke="#f5f2e6" stroke-width="3" stroke-linecap="round"/>
                <circle cx="55" cy="90" r="22" stroke="#f5f2e6" stroke-width="3"/>
                <path d="M15 260V165Q15 128 55 128Q95 128 95 165V220" stroke="#f5f2e6" stroke-width="3" stroke-linecap="round"/>
                <path d="M88 150L138 100L152 86" stroke="#f5f2e6" stroke-width="3" stroke-linecap="round"/>
            </svg>

            <div class="relative z-10 max-w-[1400px] mx-auto px-5 sm:px-8 pt-32 pb-16 sm:pb-20 w-full">
                <div class="max-w-2xl reveal" data-reveal>
                    <p class="font-mono text-xs tracking-[0.18em] uppercase text-[var(--gold-bright)] mb-6">
                        Tutoring session marketplace
                    </p>

                    <h1 class="font-display font-semibold text-4xl sm:text-5xl lg:text-6xl leading-[1.08] tracking-tight text-[var(--chalk)] mb-6">
                        The tutor you book actually teaches your subject.
                    </h1>

                    <p class="text-lg text-[var(--sage)] leading-relaxed max-w-xl mb-10">
                        A subject-matched booking marketplace, not a contact form. Browse approved tutors by subject and level, see their real hourly rate, and book a session — the price locks in the moment you confirm it, and the form simply won't let you pick a subject they don't teach.
                    </p>

                    @guest
                        <div class="flex flex-wrap items-center gap-4">
                            <a href="{{ route('register') }}" class="press px-7 py-3.5 bg-[var(--gold)] hover:bg-[var(--gold-bright)] text-[#1e2a1b] font-display font-semibold rounded-lg transition-colors">
                                Create account
                            </a>
                            <a href="#features" class="press flex items-center gap-2 px-7 py-3.5 text-[var(--chalk)] font-medium rounded-lg border border-[var(--line-ink)] hover:border-[var(--sage)] transition-colors">
                                See how booking works
                            </a>
                        </div>
                    @endguest
                </div>
            </div>

            <!-- Live data strip — real domain facts, not a fabricated metric -->
            <div class="relative z-10 w-full border-t border-[var(--line-ink)] bg-[#1e2a1b]/60 backdrop-blur-sm">
                <div class="max-w-[1400px] mx-auto px-5 sm:px-8 py-4 flex flex-wrap gap-x-8 gap-y-2 font-mono text-[11px] tracking-[0.14em] uppercase text-[var(--sage)]">
                    <span>Subjects &amp; levels</span>
                    <span class="text-[var(--gold-bright)]">&gt;</span>
                    <span>Approved tutor profiles</span>
                    <span class="text-[var(--gold-bright)]">&gt;</span>
                    <span>Session booking</span>
                    <span class="text-[var(--gold-bright)]">&gt;</span>
                    <span>Policy-gated cancellation</span>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="py-24 sm:py-28 px-5 sm:px-8 bg-[var(--paper)]">
            <div class="max-w-[1400px] mx-auto">
                <div class="max-w-xl mb-16 reveal" data-reveal>
                    <p class="font-mono text-xs tracking-[0.18em] uppercase text-[var(--leaf)] mb-4">What it does</p>
                    <h2 class="font-display font-semibold text-3xl sm:text-4xl text-[var(--ink)] leading-tight">
                        Everything the booking flow actually does
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 border-t border-[var(--line)]">
                    @php
                        $features = [
                            ['tag' => 'Browse', 'title' => 'Filter by subject and level', 'body' => 'Every approved tutor for a subject, with their hourly rate and rating visible before you open a profile.'],
                            ['tag' => 'Profile', 'title' => 'Only approved profiles are browsable', 'body' => 'A tutor listing isn\'t public until it\'s approved — a pending or suspended profile won\'t show up by browsing or by a shared link.'],
                            ['tag' => 'Booking', 'title' => 'The subject has to match', 'body' => 'A session only books for a subject the tutor actually teaches, at a time that hasn\'t already passed. The form enforces both, not just the copy.'],
                            ['tag' => 'Price', 'title' => 'The rate locks in at booking', 'body' => 'Your session snapshots the tutor\'s hourly rate the moment you book it, so a later rate change never rewrites what you agreed to pay.'],
                            ['tag' => 'Sessions', 'title' => 'One page for that session', 'body' => 'Delivery mode, timing, status, and any notes either side left — scoped to the student and tutor actually on it, no one else\'s sessions mixed in.'],
                            ['tag' => 'Cancel', 'title' => 'Cancel it yourself', 'body' => 'Either the student or the tutor can cancel a session — checked against who\'s actually on it, not just who\'s signed in.'],
                        ];
                    @endphp
                    @foreach ($features as $i => $f)
                        <div class="row-hover border-b border-[var(--line)] {{ $i % 2 === 0 ? 'md:border-r' : '' }} px-1 py-8 sm:py-10 transition-colors reveal" data-reveal>
                            <p class="font-mono text-[11px] tracking-[0.14em] uppercase text-[var(--leaf)] mb-3">{{ $f['tag'] }}</p>
                            <h3 class="font-display font-semibold text-xl text-[var(--ink)] mb-2.5">{{ $f['title'] }}</h3>
                            <p class="text-[var(--moss)] leading-relaxed max-w-md">{{ $f['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Capabilities -->
        <section id="capabilities" class="py-24 sm:py-28 px-5 sm:px-8 bg-[var(--ink)] border-y border-[var(--line-ink)]">
            <div class="max-w-[1400px] mx-auto">
                <div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] gap-12 lg:gap-20">
                    <div class="reveal" data-reveal>
                        <p class="font-mono text-xs tracking-[0.18em] uppercase text-[var(--gold-bright)] mb-4">Built on</p>
                        <h2 class="font-display font-semibold text-3xl sm:text-4xl text-[var(--chalk)] leading-tight mb-5">
                            A shared marketplace, not a walled garden
                        </h2>
                        <p class="text-[var(--sage)] leading-relaxed max-w-sm">
                            Dot.Tutor runs on the same shared InfoDot database as the rest of the ecosystem. There's no team or org gate on a tutor listing — sign in with your one InfoDot account, and the whole marketplace is already there.
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-x-10">
                        @php
                            $capabilities = [
                                ['title' => 'Laravel &amp; Livewire', 'body' => 'Server-rendered pages with Alpine.js interactions — no separate API to keep in sync with the UI.'],
                                ['title' => 'One ecosystem account', 'body' => 'Signs in through the same one-time SSO token exchange every Dot platform uses — no separate Tutor password.'],
                                ['title' => 'Shared PostgreSQL', 'body' => 'Runs on the same infodot database as the rest of the ecosystem, not a siloed copy of it.'],
                                ['title' => 'Policy-gated by session', 'body' => 'Viewing or cancelling a session checks you\'re the actual student or tutor on it — not just anyone signed in.'],
                                ['title' => 'No team, no gate', 'body' => 'Every approved tutor is publicly browsable. No invite or team membership stands between you and a listing.'],
                                ['title' => 'Rate history, not overwritten', 'body' => 'A booked session keeps the rate it was booked at, even if the tutor\'s listed rate changes later.'],
                            ];
                        @endphp
                        @foreach ($capabilities as $c)
                            <div class="row-hover-ink py-6 border-t border-[var(--line-ink)] reveal transition-colors" data-reveal>
                                <h3 class="font-display font-medium text-base text-[var(--chalk)] mb-1.5">{!! $c['title'] !!}</h3>
                                <p class="text-sm text-[var(--sage)] leading-relaxed">{{ $c['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="relative py-28 sm:py-36 px-5 sm:px-8 overflow-hidden bg-[#1e2a1b]">
            <!-- Large quiet chevron — the arrow element from the real Dot.Tutor mark -->
            <svg class="absolute -right-[8%] top-1/2 -translate-y-1/2 h-[130%] w-auto opacity-[0.06] pointer-events-none" viewBox="0 0 200 320" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M20 20L160 160L20 300" stroke="#40a535" stroke-width="34" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>

            <div class="relative z-10 max-w-2xl mx-auto text-center reveal" data-reveal>
                <h2 class="font-display font-semibold text-3xl sm:text-4xl text-[var(--chalk)] leading-tight mb-5">
                    Ready to find someone who teaches your subject?
                </h2>
                <p class="text-[var(--sage)] leading-relaxed mb-10 max-w-lg mx-auto">
                    Create an account to browse approved tutors, or list yourself as one — sign-in is the same InfoDot account used across the ecosystem.
                </p>

                @guest
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="{{ route('register') }}" class="press px-8 py-3.5 bg-[var(--gold)] hover:bg-[var(--gold-bright)] text-[#1e2a1b] font-display font-semibold rounded-lg transition-colors">
                            Create account
                        </a>
                        <a href="{{ route('login') }}" class="press px-8 py-3.5 text-[var(--chalk)] font-medium rounded-lg border border-[var(--line-ink)] hover:border-[var(--sage)] transition-colors">
                            Sign in
                        </a>
                    </div>
                @endguest
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-14 px-5 sm:px-8 border-t border-[var(--line-ink)] bg-[#1e2a1b]">
            <div class="max-w-[1400px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-6">
                <a href="/" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo.png') }}" alt="Dot.Tutor" class="h-11 w-auto opacity-90">
                </a>
                <p class="font-mono text-xs tracking-wide text-[var(--sage)]">
                    &copy; {{ date('Y') }} Dot.Tutor. Tutoring session marketplace.
                </p>
            </div>
        </footer>

        <script>
            if (window.matchMedia('(prefers-reduced-motion: no-preference)').matches && 'IntersectionObserver' in window) {
                const io = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
                document.querySelectorAll('[data-reveal]').forEach((el) => io.observe(el));
            } else {
                document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-visible'));
            }
        </script>
    </body>
</html>
