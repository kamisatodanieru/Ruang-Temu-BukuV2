<x-app-layout title="Profil" header="Profil Saya">
    <div class="max-w-2xl">
        <div class="rtb-card rounded-2xl p-6 sm:p-7">
            <h2 class="font-serif text-lg font-semibold text-slate-900 mb-1">Informasi Akun</h2>
            <p class="text-xs text-slate-500 mb-6">Perbarui nama, email, atau kata sandi akun Anda.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5" novalidate>
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                           class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm shadow-sm {{ $errors->has('name') ? 'border-rose-500' : 'border-slate-300' }}">
                    @error('name')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Alamat Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                           class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm shadow-sm {{ $errors->has('email') ? 'border-rose-500' : 'border-slate-300' }}">
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-5 border-t border-slate-200">
                    <h3 class="font-serif text-base font-semibold text-slate-800 mb-1">Ubah Kata Sandi</h3>
                    <p class="text-xs text-slate-500 mb-4">Isi kata sandi saat ini jika Anda mengubah email atau kata sandi.</p>

                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-slate-700">Kata Sandi Saat Ini</label>
                            <div class="relative mt-1.5">
                                <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                                       class="block w-full rounded-xl border bg-slate-50/50 pl-3.5 pr-10 py-2.5 text-sm shadow-sm {{ $errors->has('current_password') ? 'border-rose-500' : 'border-slate-300' }}">
                                <x-password-toggle target="current_password" />
                            </div>
                            @error('current_password')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi Baru</label>
                            <div class="relative mt-1.5">
                                <input id="password" name="password" type="password" minlength="15" maxlength="64" autocomplete="new-password"
                                       class="block w-full rounded-xl border bg-slate-50/50 pl-3.5 pr-10 py-2.5 text-sm shadow-sm {{ $errors->has('password') ? 'border-rose-500' : 'border-slate-300' }}">
                                <x-password-toggle target="password" />
                            </div>
                            <p class="mt-1 text-xs text-slate-500">15 sampai 64 karakter.</p>
                            @error('password')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi Baru</label>
                            <div class="relative mt-1.5">
                                <input id="password_confirmation" name="password_confirmation" type="password" minlength="15" maxlength="64" autocomplete="new-password"
                                       class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 pl-3.5 pr-10 py-2.5 text-sm shadow-sm">
                                <x-password-toggle target="password_confirmation" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="inline-flex justify-center py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
