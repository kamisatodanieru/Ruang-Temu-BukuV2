<x-guest-layout title="Lupa Kata Sandi">
    <div class="mb-6 text-center">
        <h2 class="font-serif text-xl font-semibold text-slate-900">Lupa Kata Sandi</h2>
        <p class="mt-1.5 text-sm text-slate-600">
            Masukkan email Anda untuk menerima tautan atur ulang kata sandi.
        </p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" novalidate>
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Alamat Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm shadow-sm {{ $errors->has('email') ? 'border-rose-500' : 'border-slate-300' }}">
            @error('email')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full flex justify-center py-2.5 px-4 rounded-xl shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition">
            Kirim Tautan
        </button>

        <p class="text-center text-sm text-slate-600 pt-2 border-t border-slate-200/70">
            <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">&larr; Kembali ke halaman Masuk</a>
        </p>
    </form>
</x-guest-layout>
