<x-filament-panels::page>
    <x-filament::section heading="Change Your Password">
        <p class="text-sm text-gray-500 mb-4">For security, choose a strong password. You will be asked to log in again.</p>
        {{ $this->form }}
        <div class="mt-4">
            <x-filament::button wire:click="save" color="primary">
                Update Password
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
