@csrf

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <label for="name" class="block text-sm font-semibold text-slate-700">Full Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $managedUser->name ?? '') }}" required autofocus
            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
        @error('name')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-semibold text-slate-700">Email Address</label>
        <input id="email" name="email" type="email" value="{{ old('email', $managedUser->email ?? '') }}" required
            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
        @error('email')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="role_id" class="block text-sm font-semibold text-slate-700">Role</label>
        <select id="role_id" name="role_id" required
            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700"
            @if (($managedUser ?? null)?->is(auth()->user())) disabled @endif>
            <option value="">Select role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected((string) old('role_id', $managedUser->role_id ?? '') === (string) $role->id)>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
        @if (($managedUser ?? null)?->is(auth()->user()))
            <input type="hidden" name="role_id" value="{{ $managedUser->role_id }}">
            <p class="mt-2 text-xs text-slate-500">Your own administrator role cannot be changed here.</p>
        @endif
        @error('role_id')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="account_status" class="block text-sm font-semibold text-slate-700">Account Status</label>
        <select id="account_status" name="account_status" required
            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700"
            @if (($managedUser ?? null)?->is(auth()->user())) disabled @endif>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('account_status', $managedUser->account_status ?? 'ACTIVE') === $status)>
                    {{ ucfirst(strtolower($status)) }}
                </option>
            @endforeach
        </select>
        @if (($managedUser ?? null)?->is(auth()->user()))
            <input type="hidden" name="account_status" value="ACTIVE">
            <p class="mt-2 text-xs text-slate-500">Your own administrator account must remain active.</p>
        @endif
        @error('account_status')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-password-input
            id="password"
            name="password"
            label="Password"
            :required="! isset($managedUser)"
            autocomplete="new-password"
            :showStrengthMeter="true"
        />
        @isset($managedUser)
            <p class="mt-2 text-xs text-slate-500">Leave blank to keep the current password.</p>
        @endisset
        @error('password')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-password-input
            id="password_confirmation"
            name="password_confirmation"
            label="Confirm Password"
            :required="! isset($managedUser)"
            autocomplete="new-password"
            :showStrengthMeter="false"
        />
    </div>
</div>

<div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('admin.users.index') }}" class="inline-flex justify-center rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
        {{ $submitLabel }}
    </button>
</div>
