<!DOCTYPE html>
<html lang="id" class="min-h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Autentikasi' }} — {{ config('app.name', 'Ruang Temu Buku') }}</title>

    {{-- Google Fonts: Fraunces (Editorial Serif), Plus Jakarta Sans (Humanist UI), JetBrains Mono --}}
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
                    colors: {
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
            --border-subtle: #E6E0D4;
            --border-strong: #D5CBB8;
            --ink-primary: #1C1917;
            --ink-secondary: #57534E;
            --accent-primary: #1F4B3B;
            --shadow-card: 0 2px 6px rgba(28, 25, 23, 0.04), 0 18px 40px -10px rgba(28, 25, 23, 0.08);
            --ease-out-expo: cubic-bezier(0.22, 1, 0.36, 1);
        }

        html.dark {
            --bg-canvas: #121311;
            --bg-surface: #1A1C19;
            --bg-subtle: #222521;
            --border-subtle: #2A2E29;
            --border-strong: #3A3F38;
            --ink-primary: #F4F1EA;
            --ink-secondary: #A8A29E;
            --accent-primary: #4D9A7F;
            --shadow-card: 0 20px 50px -12px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        @media screen {
            body {
                background-color: var(--bg-canvas);
                color: var(--ink-primary);
                transition: background-color 0.35s var(--ease-out-expo), color 0.35s var(--ease-out-expo);
            }

            body::before {
                content: "";
                position: fixed;
                inset: 0;
                pointer-events: none;
                z-index: 0;
                background:
                    radial-gradient(circle at 20% 15%, rgba(194, 99, 67, 0.06), transparent 45%),
                    radial-gradient(circle at 80% 85%, rgba(31, 75, 59, 0.07), transparent 45%);
            }

            html.dark .bg-white { background-color: var(--bg-surface) !important; }
            html.dark .bg-slate-50, html.dark .bg-slate-50\/50 { background-color: var(--bg-canvas) !important; }
            html.dark .bg-slate-100 { background-color: var(--bg-subtle) !important; }
            html.dark .border-slate-200, html.dark .border-slate-200\/70, html.dark .border-slate-100 { border-color: var(--border-subtle) !important; }
            html.dark .border-slate-300 { border-color: var(--border-strong) !important; }
            html.dark .text-slate-900, html.dark .text-slate-800 { color: var(--ink-primary) !important; }
            html.dark .text-slate-700 { color: #D6D2C9 !important; }
            html.dark .text-slate-600, html.dark .text-slate-500 { color: var(--ink-secondary) !important; }
            html.dark .text-indigo-600, html.dark .text-indigo-700 { color: #68B59A !important; }
            html.dark input:not([type="checkbox"]):not([type="radio"]),
            html.dark select,
            html.dark textarea {
                background-color: #151714 !important;
                color: var(--ink-primary) !important;
                border-color: var(--border-strong) !important;
            }
        }

        @keyframes rtbRevealCard {
            0% {
                opacity: 0;
                transform: translateY(18px) scale(0.985);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .rtb-guest-reveal {
            animation: rtbRevealCard 0.7s var(--ease-out-expo) backwards;
        }

        .rtb-guest-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-card);
        }

        button, a {
            transition: all 0.25s var(--ease-out-expo);
        }

        button:active:not(:disabled) {
            transform: scale(0.98);
        }

        input:not([type="checkbox"]):not([type="radio"]):focus {
            outline: none;
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3.5px rgba(31, 75, 59, 0.14) !important;
        }
    </style>
</head>
<body class="min-h-screen font-sans antialiased">
    {{-- Floating Theme Toggle --}}
    <div class="fixed top-5 right-5 z-50">
        <button type="button" onclick="toggleTheme()" id="theme-toggle-btn"
                aria-label="Ubah mode tampilan gelap atau terang"
                class="group p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 text-slate-600 hover:text-slate-900 shadow-sm hover:shadow-md transition">
            <svg id="theme-icon-sun" class="w-5 h-5 hidden dark:block text-amber-400 transition-transform duration-500 group-hover:rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg id="theme-icon-moon" class="w-5 h-5 block dark:hidden text-slate-700 transition-transform duration-500 group-hover:-rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center rtb-guest-reveal">
            <a href="{{ route('home') }}" class="group inline-flex flex-col items-center gap-3 focus:outline-none">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#2E6B55] to-[#1F4B3B] text-amber-200 flex items-center justify-center shadow-md group-hover:scale-105 group-hover:rotate-[-3deg] transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <span class="block font-serif text-2xl font-semibold tracking-tight text-slate-900">
                        {{ config('app.name', 'Ruang Temu Buku') }}
                    </span>
                    <span class="block mt-1 text-xs font-medium tracking-widest uppercase text-slate-500">
                        Perpustakaan Digital
                    </span>
                </div>
            </a>
        </div>

        <div class="mt-7 sm:mx-auto sm:w-full sm:max-w-md px-4 rtb-guest-reveal" style="animation-delay: 80ms">
            <div class="rtb-guest-card py-8 px-6 rounded-2xl sm:px-10">
                @if (session('status'))
                    <div role="alert" class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('success'))
                    <div role="alert" class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div role="alert" class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-indigo-600 transition">
                    <span>&larr; Lihat Katalog Buku</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleTheme() {
            const root = document.documentElement;
            const isDark = root.classList.toggle('dark');
            try {
                localStorage.setItem('rtb_theme', isDark ? 'dark' : 'light');
            } catch (e) {}
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
    </script>
</body>
</html>
