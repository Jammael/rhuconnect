<section>
    <header>
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">
            {{ __('Update Password') }}
        </p>

        <h3 class="mt-2 text-xl font-semibold tracking-tight text-slate-900">
            {{ __('Strengthen account access') }}
        </h3>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <x-password-input
                id="update_password_current_password"
                name="current_password"
                :label="__('Current Password')"
                autocomplete="current-password"
                :showStrengthMeter="false"
            />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-password-input
                id="update_password_password"
                name="password"
                :label="__('New Password')"
                autocomplete="new-password"
                :showStrengthMeter="true"
            />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-password-input
                id="update_password_password_confirmation"
                name="password_confirmation"
                :label="__('Confirm Password')"
                autocomplete="new-password"
                :showStrengthMeter="false"
            />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-1">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm shadow-emerald-600/10 transition-colors hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:ring-offset-2">
                {{ __('Save') }}
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-medium text-emerald-700"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
