<x-filament-panels::page>
    <div class="space-y-6">
        @if($hasMfa)
            <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
                <strong>MFA is ENABLED</strong> for {{ $user->email }}. Use your authenticator app or a recovery code to sign in.
            </div>
        @else
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-amber-800">
                <strong>MFA is NOT enabled.</strong> Generate a secret, scan the QR in Google Authenticator / Authy, then verify.
            </div>
        @endif

        @if($secret)
            <div class="rounded-lg border p-6 bg-white space-y-4">
                <h3 class="font-bold">Your TOTP Secret</h3>
                <p class="text-sm text-gray-600">Scan this QR or enter the secret manually. Keep recovery codes offline.</p>
                <div class="flex flex-col md:flex-row gap-6 items-start">
                    <div>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrUrl) }}" alt="MFA QR" class="border rounded" width="200" height="200" />
                    </div>
                    <div class="space-y-2">
                        <div>
                            <label class="text-xs font-semibold text-gray-500">SECRET</label>
                            <div class="font-mono bg-gray-50 p-2 rounded border text-sm break-all">{{ $secret }}</div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500">QR URL</label>
                            <div class="text-xs break-all text-gray-500">{{ $qrUrl }}</div>
                        </div>
                        @if(!empty($recoveryPlain))
                            <div>
                                <label class="text-xs font-semibold text-gray-500">RECOVERY CODES (single-use, store securely)</label>
                                <ul class="font-mono text-sm bg-gray-50 p-2 rounded border space-y-1">
                                    @foreach($recoveryPlain as $c)<li>{{ $c }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{ $this->form }}

        <div class="text-xs text-gray-500">
            Need help? Run <code>php artisan mfa:setup {{ $user->email }}</code> or <code>make:break-glass</code> for recovery. MFA challenge will be required after password if enabled.
        </div>
    </div>
</x-filament-panels::page>
