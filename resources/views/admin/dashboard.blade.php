<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Administrator Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-semibold text-green-700">RHUConnect administration access confirmed.</p>
                    <p class="mt-2 text-sm text-gray-600">Manage staff accounts for Doctor, Nurse, Midwife, and Data Encoder roles.</p>
                    <div class="mt-6">
                        <a href="{{ route('admin.users.index') }}" class="inline-flex rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                            Open User Management
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
