<x-guest-layout title="Masuk">
    <div class="mb-6 text-center">
        <h2 class="font-serif text-xl font-semibold text-slate-900">Masuk ke Akun Anda</h2>
        <p class="mt-1 text-xs text-slate-500">Selamat datang kembali di Ruang Temu Buku</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Alamat Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="nama@email.com"
                   class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm shadow-sm {{ $errors->has('email') ? 'border-rose-500' : 'border-slate-300' }}">
            @error('email')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Kata Sandi</label>
                <a href="{{ route('password.request') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                    Lupa kata sandi?
                </a>
            </div>
            <div class="relative mt-1.5">
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="block w-full rounded-xl border bg-slate-50/50 pl-3.5 pr-10 py-2.5 text-sm shadow-sm {{ $errors->has('password') ? 'border-rose-500' : 'border-slate-300' }}">
                <x-password-toggle target="password" />
            </div>
            @error('password')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center">
            <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <label for="remember" class="ml-2 block text-sm text-slate-600">Ingat saya</label>
        </div>

        <button type="submit" class="w-full flex justify-center py-2.5 px-4 rounded-xl shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition">
            Masuk
        </button>

        <p class="text-center text-sm text-slate-600 pt-2 border-t border-slate-200/70">
            Belum menjadi anggota?
            <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Daftar sekarang</a>
        </p>
    </form>
</x-guest-layout>
