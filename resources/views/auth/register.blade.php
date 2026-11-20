<x-guest-layout title="Daftar Anggota">
    <div class="mb-6 text-center">
        <h2 class="font-serif text-xl font-semibold text-slate-900">Daftar Anggota</h2>
        <p class="mt-1 text-xs text-slate-500">Buat akun untuk mulai meminjam buku.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4" novalidate>
        @csrf

        <div>
            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Nama Lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                   class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm shadow-sm {{ $errors->has('name') ? 'border-rose-500' : 'border-slate-300' }}">
            @error('name')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Alamat Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                   class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm shadow-sm {{ $errors->has('email') ? 'border-rose-500' : 'border-slate-300' }}">
            @error('email')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Kata Sandi</label>
            <div class="relative mt-1.5">
                <input id="password" name="password" type="password" minlength="15" maxlength="64" required autocomplete="new-password"
                       class="block w-full rounded-xl border bg-slate-50/50 pl-3.5 pr-10 py-2.5 text-sm shadow-sm {{ $errors->has('password') ? 'border-rose-500' : 'border-slate-300' }}">
                <x-password-toggle target="password" />
            </div>
            <p class="mt-1 text-xs text-slate-500">15 sampai 64 karakter.</p>
            @error('password')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Konfirmasi Kata Sandi</label>
            <div class="relative mt-1.5">
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="15" maxlength="64" required autocomplete="new-password"
                       class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 pl-3.5 pr-10 py-2.5 text-sm shadow-sm">
                <x-password-toggle target="password_confirmation" />
            </div>
        </div>

        <div class="pt-1">
            <button type="submit" class="w-full flex justify-center py-2.5 px-4 rounded-xl shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition">
                Daftar sebagai Anggota
            </button>
        </div>

        <p class="text-center text-sm text-slate-600 pt-2 border-t border-slate-200/70">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Masuk di sini</a>
        </p>
    </form>
</x-guest-layout>
