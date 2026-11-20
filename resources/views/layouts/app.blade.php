<!DOCTYPE html>
<html lang="id" class="min-h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Beranda' }} — {{ config('app.name', 'Ruang Temu Buku') }}</title>

    {{-- Google Fonts: Fraunces (Editorial Serif), Plus Jakarta Sans (Humanist UI), JetBrains Mono (Data/ISBN) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        (function () {
            try {
                const savedTheme = localStorage.getItem('rtb_theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        serif: ['"Fraunces"', 'Georgia', 'Cambria', 'serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
                    },
                    spacing: {
                        '4.5': '1.125rem',
                        '13': '3.25rem',
                        '18': '4.5rem',
                    },
                    boxShadow: {
                        '2xs': '0 1px 1px 0 rgba(28, 25, 23, 0.04)',
                        'xs': '0 1px 2px 0 rgba(28, 25, 23, 0.05)',
                    },
                    backdropBlur: {
                        'xs': '2px',
                    },
                    colors: {
                        /* Remap slate & indigo to Warm Literary Sanctuary palette for seamless consistency */
                        slate: {
                            50: '#FAF7F2',
                            100: '#F3EFE6',
                            200: '#E6E0D4',
                            300: '#D5CBB8',
                            400: '#A89F91',
                            500: '#78716C',
                            600: '#57534E',
                            700: '#44403C',
                            800: '#292524',
                            900: '#1C1917',
                            950: '#121311',
                        },
                        indigo: {
                            50: '#EBF3F0',
                            100: '#D4E6DF',
                            200: '#AED0C3',
                            300: '#7DB4A0',
                            400: '#52967E',
                            500: '#2E6B55',
                            600: '#1F4B3B',
                            700: '#183C2F',
                            800: '#143026',
                            900: '#10261E',
                            950: '#091712',
                        },
                        terracotta: {
                            50: '#FBF3F0',
                            100: '#F6E3DC',
                            200: '#ECC5B8',
                            300: '#DF9F8B',
                            400: '#D07B60',
                            500: '#C26343',
                            600: '#A84E31',
                            700: '#8C3F27',
                            800: '#733523',
                            900: '#5F2E20',
                        }
                    }
                }
            }
        };
    </script>
    <style>
        :root {
            --bg-canvas: #FAF7F2;
            --bg-surface: #FFFFFF;
            --bg-subtle: #F3EFE6;
            --bg-sidebar: #15201B;
            --border-subtle: #E6E0D4;
            --border-strong: #D5CBB8;
            --ink-primary: #1C1917;
            --ink-secondary: #57534E;
            --ink-muted: #8A8275;
            --accent-primary: #1F4B3B;
            --accent-primary-hover: #16382B;
            --accent-soft: #EBF3F0;
            --accent-warm: #C26343;
            --accent-warm-soft: #FBF3F0;
            --shadow-card: 0 1px 2px rgba(28, 25, 23, 0.03), 0 6px 16px -4px rgba(28, 25, 23, 0.05);
            --shadow-card-hover: 0 16px 32px -8px rgba(28, 25, 23, 0.10), 0 4px 12px -2px rgba(28, 25, 23, 0.05);
            --ease-out-expo: cubic-bezier(0.22, 1, 0.36, 1);
            --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        html.dark {
            --bg-canvas: #121311;
            --bg-surface: #1A1C19;
            --bg-subtle: #222521;
            --bg-sidebar: #0E100E;
            --border-subtle: #2A2E29;
            --border-strong: #3A3F38;
            --ink-primary: #F4F1EA;
            --ink-secondary: #A8A29E;
            --ink-muted: #78716C;
            --accent-primary: #4D9A7F;
            --accent-primary-hover: #62B094;
            --accent-soft: rgba(77, 154, 127, 0.14);
            --accent-warm: #E07A5F;
            --accent-warm-soft: rgba(224, 122, 95, 0.14);
            --shadow-card: 0 2px 10px rgba(0, 0, 0, 0.32), inset 0 1px 0 rgba(255, 255, 255, 0.03);
            --shadow-card-hover: 0 18px 36px -8px rgba(0, 0, 0, 0.52), inset 0 1px 0 rgba(255, 255, 255, 0.06);
        }

        @media screen {
            body {
                background-color: var(--bg-canvas);
                color: var(--ink-primary);
                transition: background-color 0.35s var(--ease-out-expo), color 0.35s var(--ease-out-expo);
            }

            /* Subtle paper grain & ambient radial warmth */
            body::before {
                content: "";
                position: fixed;
                inset: 0;
                pointer-events: none;
                z-index: 0;
                background:
                    radial-gradient(circle at 15% 0%, rgba(194, 99, 67, 0.045), transparent 42%),
                    radial-gradient(circle at 85% 10%, rgba(31, 75, 59, 0.05), transparent 45%);
            }

            html.dark body::before {
                background:
                    radial-gradient(circle at 15% 0%, rgba(224, 122, 95, 0.05), transparent 42%),
                    radial-gradient(circle at 85% 10%, rgba(77, 154, 127, 0.06), transparent 45%);
            }

            /* Smooth semantic dark mode adaptation for surfaces, text, and status badges */
            html.dark .bg-white { background-color: var(--bg-surface) !important; }
            html.dark .bg-slate-50, html.dark .bg-slate-50\/50, html.dark .bg-slate-50\/60 { background-color: var(--bg-subtle) !important; }
            html.dark .bg-slate-100 { background-color: #282C26 !important; }
            html.dark .border-slate-200, html.dark .border-slate-200\/80, html.dark .border-slate-200\/70, html.dark .border-slate-100 { border-color: var(--border-subtle) !important; }
            html.dark .border-slate-300 { border-color: var(--border-strong) !important; }
            html.dark .divide-slate-200 > :not([hidden]) ~ :not([hidden]),
            html.dark .divide-slate-100 > :not([hidden]) ~ :not([hidden]) { border-color: var(--border-subtle) !important; }
            html.dark .text-slate-900, html.dark .text-slate-800 { color: var(--ink-primary) !important; }
            html.dark .text-slate-700 { color: #D6D2C9 !important; }
            html.dark .text-slate-600, html.dark .text-slate-500 { color: var(--ink-secondary) !important; }

            /* Dark mode semantic status badges & icon boxes (prevents glaring white boxes in dark mode) */
            html.dark .text-indigo-600, html.dark .text-indigo-700 { color: #68B59A !important; }
            html.dark .bg-indigo-50, html.dark .bg-indigo-50\/70 { background-color: rgba(77, 154, 127, 0.16) !important; }
            html.dark .border-indigo-200, html.dark .border-indigo-200\/70, html.dark .border-indigo-200\/60 { border-color: rgba(77, 154, 127, 0.32) !important; }

            html.dark .bg-emerald-50, html.dark .bg-emerald-50\/60, html.dark .bg-emerald-100 { background-color: rgba(16, 185, 129, 0.15) !important; }
            html.dark .text-emerald-600, html.dark .text-emerald-700, html.dark .text-emerald-800 { color: #6EE7B7 !important; }
            html.dark .border-emerald-200 { border-color: rgba(16, 185, 129, 0.3) !important; }

            html.dark .bg-amber-50, html.dark .bg-amber-100 { background-color: rgba(245, 158, 11, 0.15) !important; }
            html.dark .text-amber-600, html.dark .text-amber-700, html.dark .text-amber-800, html.dark .text-amber-900 { color: #FCD34D !important; }
            html.dark .border-amber-200 { border-color: rgba(245, 158, 11, 0.3) !important; }

            html.dark .bg-rose-50, html.dark .bg-rose-50\/60, html.dark .bg-rose-100 { background-color: rgba(244, 63, 94, 0.15) !important; }
            html.dark .text-rose-600, html.dark .text-rose-700, html.dark .text-rose-800 { color: #FDA4AF !important; }
            html.dark .border-rose-200 { border-color: rgba(244, 63, 94, 0.3) !important; }

            html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
            html.dark select,
            html.dark textarea {
                background-color: #161815 !important;
                color: var(--ink-primary) !important;
                border-color: var(--border-strong) !important;
            }

            html.dark .bg-slate-800 { background-color: #2E342D !important; }
            html.dark .bg-slate-800:hover { background-color: #3B4239 !important; }
            html.dark nav[role="navigation"] a,
            html.dark nav[role="navigation"] span[aria-disabled="true"] > span,
            html.dark nav[role="navigation"] span[aria-current="page"] > span {
                background-color: var(--bg-surface) !important;
                border-color: var(--border-subtle) !important;
                color: var(--ink-primary) !important;
            }
            html.dark nav[role="navigation"] span[aria-current="page"] > span {
                background-color: var(--accent-soft) !important;
                border-color: rgba(77, 154, 127, 0.4) !important;
                color: #68B59A !important;
            }
            html.dark nav[role="navigation"] p { color: var(--ink-secondary) !important; }
        }

        /* Sticky Glass Topbar */
        .rtb-topbar {
            background-color: rgba(250, 247, 242, 0.88);
            border-bottom: 1px solid var(--border-subtle);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        html.dark .rtb-topbar {
            background-color: rgba(18, 19, 17, 0.88);
        }

        /* Smooth Entrance Animations */
        @keyframes rtbRevealUp {
            0% {
                opacity: 0;
                transform: translateY(14px) scale(0.992);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes rtbSlideDown {
            0% {
                opacity: 0;
                transform: translateY(-10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .rtb-reveal {
            animation: rtbRevealUp 0.65s var(--ease-out-expo) backwards;
            animation-delay: calc(var(--stagger, 0) * 45ms);
        }

        .rtb-alert-enter {
            animation: rtbSlideDown 0.45s var(--ease-out-expo) backwards;
        }

        /* Tactile Surface & Card System */
        .rtb-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-card);
            transition:
                transform 0.45s var(--ease-out-expo),
                box-shadow 0.45s var(--ease-out-expo),
                border-color 0.3s ease,
                background-color 0.3s ease;
        }

        .rtb-card-hover:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-card-hover);
            border-color: var(--border-strong);
        }

        /* Realistic 3D Book Cover Spine Effect */
        .book-cover-frame {
            position: relative;
            overflow: hidden;
            box-shadow:
                0 6px 16px -4px rgba(28, 25, 23, 0.14),
                0 2px 6px -1px rgba(28, 25, 23, 0.08);
        }

        .book-cover-frame::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 10;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0.28) 0%,
                rgba(0, 0, 0, 0.12) 3.5%,
                rgba(255, 255, 255, 0.15) 6%,
                transparent 12%
            );
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08);
        }

        /* Tactile Buttons & Inputs */
        .rtb-btn, button, a[class*="rounded-lg"], a[class*="rounded-xl"] {
            transition:
                transform 0.25s var(--ease-out-expo),
                background-color 0.25s ease,
                border-color 0.25s ease,
                color 0.25s ease,
                box-shadow 0.25s ease;
        }

        button:active:not(:disabled),
        .rtb-btn:active {
            transform: scale(0.975);
        }

        input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
        select,
        textarea {
            transition:
                border-color 0.25s ease,
                box-shadow 0.25s var(--ease-out-expo),
                background-color 0.25s ease;
        }

        input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3.5px rgba(31, 75, 59, 0.14) !important;
        }

        html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):focus,
        html.dark select:focus,
        html.dark textarea:focus {
            box-shadow: 0 0 0 3.5px rgba(77, 154, 127, 0.22) !important;
        }

        /* Table Row Smooth Highlight */
        tbody tr {
            transition: background-color 0.2s ease;
        }
        tbody tr:hover {
            background-color: rgba(243, 239, 230, 0.55);
        }
        html.dark tbody tr:hover {
            background-color: rgba(34, 37, 33, 0.65);
        }

        /* Sidebar Scrollbar */
        #app-sidebar nav::-webkit-scrollbar {
            width: 4px;
        }
        #app-sidebar nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 9999px;
        }

        /* Respect Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>
<body class="min-h-screen font-sans antialiased selection:bg-indigo-600/15 selection:text-indigo-700 dark:selection:bg-indigo-400/20 dark:selection:text-indigo-200">
    @php
        $authUser = auth()->user()?->loadMissing('role');
    @endphp

    <div class="relative z-10 min-h-screen flex flex-col lg:flex-row">
        {{-- Mobile Sidebar Backdrop (Smooth Opacity + Blur Transition) --}}
        <div id="sidebar-backdrop"
             class="fixed inset-0 bg-stone-950/60 backdrop-blur-sm z-30 opacity-0 pointer-events-none transition-opacity duration-300 ease-out lg:hidden"
             onclick="toggleSidebar()"></div>

        {{-- Sidebar Navigation (Warm Botanical Pine Sanctuary) --}}
        <aside id="app-sidebar" aria-label="Navigasi Utama"
               class="print:hidden fixed inset-y-0 left-0 z-40 w-[272px] bg-[#15201B] dark:bg-[#0E100E] text-stone-100 transform -translate-x-full transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen flex flex-col border-r border-white/10 shadow-2xl lg:shadow-none">

            {{-- Brand Header --}}
            <div class="h-20 flex items-center justify-between px-6 border-b border-white/[0.08]">
                <a href="{{ route('home') }}" class="group flex items-center gap-3 text-white focus:outline-none">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#2E6B55] to-[#1F4B3B] border border-white/15 flex items-center justify-center shadow-md group-hover:scale-105 group-hover:rotate-[-2deg] transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
                        <svg class="w-5 h-5 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <span class="block font-serif text-lg font-semibold tracking-tight text-stone-100 leading-none">
                            Ruang Temu Buku
                        </span>
                        <span class="block mt-1 text-[11px] font-medium tracking-widest uppercase text-stone-400">
                            Perpustakaan Digital
                        </span>
                    </div>
                </a>
                <button type="button" class="lg:hidden text-stone-400 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition" onclick="toggleSidebar()" aria-label="Tutup menu navigasi">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Navigation Links --}}
            <nav class="flex-1 px-3.5 py-5 space-y-1 overflow-y-auto">
                <div class="px-3 pb-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-400/80">
                    Menu Utama
                </div>

                @if ($authUser)
                    @php $isDashboard = request()->routeIs('dashboard'); @endphp
                    <a href="{{ route('dashboard') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isDashboard ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isDashboard)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isDashboard ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                        <span>Beranda</span>
                    </a>
                @endif

                @php $isCatalog = request()->routeIs('catalog.*'); @endphp
                <a href="{{ route('catalog.index') }}"
                   class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isCatalog ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                    @if ($isCatalog)
                        <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                    @endif
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isCatalog ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span>Katalog Buku</span>
                </a>

                @if ($authUser?->isAnggota())
                    <div class="pt-4 pb-1.5 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-400/80">
                        Peminjaman Saya
                    </div>
                    @php
                        $isMyBooks = request()->routeIs('member.loans.*') && request('status') !== 'returned';
                        $isMyHistory = request()->routeIs('member.loans.*') && request('status') === 'returned';
                    @endphp
                    <a href="{{ route('member.loans.index') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isMyBooks ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isMyBooks)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isMyBooks ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                        <span>Buku Saya</span>
                    </a>
                    <a href="{{ route('member.loans.index', ['status' => 'returned']) }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isMyHistory ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isMyHistory)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isMyHistory ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Riwayat</span>
                    </a>
                @endif

                @if ($authUser?->isAdmin() || $authUser?->isPetugas())
                    <div class="pt-5 pb-1.5 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-400/80">
                        Layanan
                    </div>
                    @php
                        $isLoanReq = request()->routeIs('staff.loans.*') && request('status') !== 'borrowed';
                        $isLoanRet = request()->routeIs('staff.loans.*') && request('status') === 'borrowed';
                    @endphp
                    <a href="{{ route('staff.loans.index') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isLoanReq ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isLoanReq)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isLoanReq ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                        </svg>
                        <span>Peminjaman</span>
                    </a>
                    <a href="{{ route('staff.loans.index', ['status' => 'borrowed']) }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isLoanRet ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isLoanRet)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isLoanRet ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                        </svg>
                        <span>Pengembalian</span>
                    </a>
                @endif

                @if ($authUser?->isAdmin())
                    <div class="pt-5 pb-1.5 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-400/80">
                        Koleksi
                    </div>
                    @php
                        $isBooks = request()->routeIs('admin.books.*');
                        $isCategories = request()->routeIs('admin.categories.*');
                        $isTags = request()->routeIs('admin.tags.*');
                    @endphp
                    <a href="{{ route('admin.books.index') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isBooks ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isBooks)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isBooks ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                        <span>Kelola Buku</span>
                    </a>
                    <a href="{{ route('admin.categories.index') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isCategories ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isCategories)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isCategories ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                        </svg>
                        <span>Kategori</span>
                    </a>
                    <a href="{{ route('admin.tags.index') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isTags ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isTags)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isTags ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 6h.008v.008H6V6z" />
                        </svg>
                        <span>Tag Buku</span>
                    </a>

                    <div class="pt-5 pb-1.5 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-400/80">
                        Administrasi
                    </div>
                    @php
                        $isUsers = request()->routeIs('admin.users.*');
                        $isReports = request()->routeIs('admin.reports.*');
                        $isLogs = request()->routeIs('admin.audit-logs.*');
                        $isBackups = request()->routeIs('admin.backups.*');
                    @endphp
                    <a href="{{ route('admin.users.index') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isUsers ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isUsers)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isUsers ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        <span>Pengguna</span>
                    </a>
                    <a href="{{ route('admin.reports.index') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isReports ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isReports)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isReports ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        <span>Laporan</span>
                    </a>
                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isLogs ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isLogs)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isLogs ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        <span>Catatan Aktivitas</span>
                    </a>
                    <a href="{{ route('admin.backups.index') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isBackups ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isBackups)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isBackups ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                        </svg>
                        <span>Cadangan Data</span>
                    </a>
                @endif

                @if ($authUser)
                    <div class="pt-5 pb-1.5 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-400/80">
                        Akun
                    </div>
                    @php $isProfile = request()->routeIs('profile.*'); @endphp
                    <a href="{{ route('profile.edit') }}"
                       class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $isProfile ? 'bg-white/[0.11] text-white shadow-sm' : 'text-stone-300/90 hover:bg-white/[0.06] hover:text-white' }}">
                        @if ($isProfile)
                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-amber-400"></span>
                        @endif
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5 {{ $isProfile ? 'text-amber-300' : 'text-stone-400 group-hover:text-stone-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Profil</span>
                    </a>
                @else
                    <div class="pt-5 pb-1.5 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-400/80">
                        Akun
                    </div>
                    <a href="{{ route('login') }}"
                       class="group flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-stone-300 hover:bg-white/[0.06] hover:text-white transition">
                        <svg class="w-4 h-4 text-stone-400 group-hover:text-stone-200 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                        <span>Masuk</span>
                    </a>
                    <a href="{{ route('register') }}"
                       class="group flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-amber-300/90 hover:bg-white/[0.06] hover:text-amber-200 transition">
                        <svg class="w-4 h-4 text-amber-400/80 group-hover:text-amber-300 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                        </svg>
                        <span>Daftar Anggota</span>
                    </a>
                @endif
            </nav>

            {{-- User Footer Card --}}
            @if ($authUser)
                <div class="p-4 border-t border-white/[0.08] bg-black/20">
                    <div class="flex items-center justify-between gap-2.5">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-white/10 border border-white/15 flex items-center justify-center text-xs font-serif font-semibold text-amber-200 shrink-0">
                                {{ strtoupper(substr($authUser->name, 0, 1)) }}
                            </div>
                            <div class="truncate">
                                <p class="text-sm font-medium text-stone-100 truncate">{{ $authUser->name }}</p>
                                <p class="text-[11px] text-amber-300/90 uppercase tracking-wider font-semibold">{{ $authUser->role?->name }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Keluar dari sesi"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-rose-500/15 text-rose-200 border border-rose-400/20 hover:bg-rose-600 hover:text-white hover:border-rose-500 transition">
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </aside>

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Sticky Glass Topbar --}}
            <header class="print:hidden sticky top-0 z-20 h-16 rtb-topbar flex items-center justify-between px-4 sm:px-6 lg:px-8 transition-colors duration-300">
                <div class="flex items-center gap-3.5">
                    <button type="button" class="lg:hidden p-2 rounded-xl text-slate-700 hover:bg-slate-200/60 transition" onclick="toggleSidebar()" aria-label="Buka menu navigasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h12" />
                        </svg>
                    </button>
                    <div>
                        <h1 class="font-serif text-lg sm:text-xl font-semibold tracking-tight text-slate-900">
                            {{ $header ?? ($title ?? 'Beranda') }}
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Tombol Toggle Mode Gelap / Terang dengan Animasi Putar --}}
                    <button type="button" onclick="toggleTheme()" id="theme-toggle-btn"
                            aria-label="Ubah mode tampilan gelap atau terang"
                            title="Mode Gelap / Terang"
                            class="group p-2.5 rounded-xl bg-white dark:bg-slate-900 text-slate-600 hover:text-slate-900 border border-slate-200 shadow-sm transition-all duration-300">
                        <svg id="theme-icon-sun" class="w-4 h-4 hidden dark:block text-amber-400 transition-transform duration-500 group-hover:rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg id="theme-icon-moon" class="w-4 h-4 block dark:hidden text-slate-700 transition-transform duration-500 group-hover:-rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    @if ($authUser)
                        <div class="hidden sm:flex items-center gap-2.5 pl-2 border-l border-slate-200">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase">
                                {{ $authUser->role?->name }}
                            </span>
                            <span class="text-xs font-medium text-slate-600 hidden md:inline">{{ $authUser->email }}</span>
                        </div>
                    @else
                        <div class="flex items-center gap-2">
                            <a href="{{ route('login') }}" class="px-3.5 py-2 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-200/60 transition">
                                Masuk
                            </a>
                            <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                                Daftar
                            </a>
                        </div>
                    @endif
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                @if (session('success'))
                    <div role="alert" class="rtb-alert-enter mb-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-start justify-between gap-3 shadow-sm">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                        <button type="button" onclick="dismissAlert(this)" class="text-emerald-600/70 hover:text-emerald-900 p-1 rounded-lg transition" aria-label="Tutup pesan">
                            &times;
                        </button>
                    </div>
                @endif

                @if (session('status'))
                    <div role="alert" class="rtb-alert-enter mb-6 rounded-2xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800 flex items-start justify-between gap-3 shadow-sm">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium">{{ session('status') }}</span>
                        </div>
                        <button type="button" onclick="dismissAlert(this)" class="text-amber-600/70 hover:text-amber-900 p-1 rounded-lg transition" aria-label="Tutup pesan">
                            &times;
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div role="alert" class="rtb-alert-enter mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 flex items-start justify-between gap-3 shadow-sm">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                        <button type="button" onclick="dismissAlert(this)" class="text-rose-600/70 hover:text-rose-900 p-1 rounded-lg transition" aria-label="Tutup pesan">
                            &times;
                        </button>
                    </div>
                @endif

                @if ($errors->has('loan'))
                    <div role="alert" class="rtb-alert-enter mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 flex items-start justify-between gap-3 shadow-sm">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <span class="font-medium">{{ $errors->first('loan') }}</span>
                        </div>
                        <button type="button" onclick="dismissAlert(this)" class="text-rose-600/70 hover:text-rose-900 p-1 rounded-lg transition" aria-label="Tutup pesan">
                            &times;
                        </button>
                    </div>
                @endif

                <div class="rtb-reveal">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    {{-- Custom Smooth Confirmation Modal (Menggantikan window.confirm bawaan browser) --}}
    <div id="rtb-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="rtb-confirm-title">
        <div id="rtb-confirm-backdrop" class="fixed inset-0 bg-stone-950/55 backdrop-blur-sm opacity-0 transition-opacity duration-300 ease-out" onclick="closeConfirmModal()"></div>
        <div id="rtb-confirm-panel" class="rtb-card relative z-10 w-full max-w-md rounded-2xl p-6 shadow-2xl transform scale-95 opacity-0 transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 id="rtb-confirm-title" class="font-serif text-lg font-semibold text-slate-900">Konfirmasi Tindakan</h3>
                    <p id="rtb-confirm-message" class="mt-1.5 text-sm text-slate-600 leading-relaxed"></p>
                </div>
            </div>
            <div class="mt-6 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeConfirmModal()"
                        class="px-4 py-2 rounded-xl text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    Batal
                </button>
                <button type="button" id="rtb-confirm-ok"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                    Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('app-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const isHidden = sidebar.classList.toggle('-translate-x-full');

            if (!isHidden) {
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                backdrop.classList.add('opacity-100', 'pointer-events-auto');
            } else {
                backdrop.classList.remove('opacity-100', 'pointer-events-auto');
                backdrop.classList.add('opacity-0', 'pointer-events-none');
            }
        }

        function toggleTheme() {
            const root = document.documentElement;
            const isDark = root.classList.toggle('dark');
            try {
                localStorage.setItem('rtb_theme', isDark ? 'dark' : 'light');
            } catch (e) {}
        }

        function dismissAlert(btn) {
            const alertBox = btn.closest('[role="alert"]');
            if (!alertBox) return;
            alertBox.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            alertBox.style.opacity = '0';
            alertBox.style.transform = 'translateY(-6px)';
            setTimeout(() => alertBox.remove(), 250);
        }

        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const eyeIcon = btn.querySelector('.eye-icon');
            const eyeSlashIcon = btn.querySelector('.eye-slash-icon');
            if (eyeIcon && eyeSlashIcon) {
                eyeIcon.classList.toggle('hidden', isPassword);
                eyeSlashIcon.classList.toggle('hidden', !isPassword);
            }
            const label = isPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
        }

        /* Progressive Enhancement: Smooth Confirmation Modal for forms with onsubmit="return confirm(...)" or data-confirm */
        let pendingConfirmForm = null;

        function openConfirmModal(message, form) {
            pendingConfirmForm = form;
            const modal = document.getElementById('rtb-confirm-modal');
            const backdrop = document.getElementById('rtb-confirm-backdrop');
            const panel = document.getElementById('rtb-confirm-panel');
            const msgEl = document.getElementById('rtb-confirm-message');

            msgEl.textContent = message;
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                panel.classList.remove('scale-95', 'opacity-0');
                panel.classList.add('scale-100', 'opacity-100');
            });

            document.getElementById('rtb-confirm-ok').focus();
        }

        function closeConfirmModal() {
            const modal = document.getElementById('rtb-confirm-modal');
            const backdrop = document.getElementById('rtb-confirm-backdrop');
            const panel = document.getElementById('rtb-confirm-panel');

            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            panel.classList.remove('scale-100', 'opacity-100');
            panel.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                pendingConfirmForm = null;
            }, 260);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const okBtn = document.getElementById('rtb-confirm-ok');
            if (okBtn) {
                okBtn.addEventListener('click', () => {
                    if (pendingConfirmForm) {
                        const formToSubmit = pendingConfirmForm;
                        formToSubmit.dataset.confirmed = 'true';
                        closeConfirmModal();
                        formToSubmit.requestSubmit ? formToSubmit.requestSubmit() : formToSubmit.submit();
                    }
                });
            }

            document.querySelectorAll('form[onsubmit*="confirm("], form[data-confirm]').forEach((form) => {
                const inlineAttr = form.getAttribute('onsubmit') || '';
                const match = inlineAttr.match(/confirm\(['"]([\s\S]+?)['"]\)/);
                if (match && match[1]) {
                    form.dataset.confirm = match[1].replace(/\\'/g, "'");
                    form.removeAttribute('onsubmit');
                }

                form.addEventListener('submit', (e) => {
                    if (form.dataset.confirm && form.dataset.confirmed !== 'true') {
                        e.preventDefault();
                        openConfirmModal(form.dataset.confirm, form);
                    }
                });
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && pendingConfirmForm) {
                    closeConfirmModal();
                }
            });
        });
    </script>
</body>
</html>
