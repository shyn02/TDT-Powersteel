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

        {{-- Only show QR when MFA is NOT yet enabled and a secret has been generated (pending verification) --}}
        @if(!$hasMfa && $secret)
            <div class="rounded-lg border p-6 bg-white space-y-4">
                <h3 class="font-bold">Scan QR to Enroll</h3>
                <p class="text-sm text-gray-600">Open Google Authenticator or Authy → Scan QR → Enter the 6-digit code via <strong>Verify & Enable MFA</strong> (top right).</p>
                <div class="flex flex-col md:flex-row gap-6 items-center">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrUrl) }}" alt="MFA QR" class="border rounded bg-white p-2" width="200" height="200" onerror="this.style.display='none';document.getElementById('qr-fallback').style.display='block';" />
                    <div id="qr-fallback" style="display:none;" class="text-sm text-amber-700 bg-amber-50 p-3 rounded border">QR failed to load (offline/CSP). Enter secret manually: <span class="font-mono break-all">{{ $secret }}</span></div>
                    <div class="text-sm text-gray-600 space-y-3">
                        <p>Can't scan? <details class="inline"><summary class="cursor-pointer text-orange-600 underline">Show secret</summary><span class="font-mono bg-gray-50 p-1 rounded border text-xs break-all">{{ $secret }}</span></details></p>
                        @if(!empty($recoveryPlain))
                            <details open class="rounded border bg-amber-50 p-3">
                                <summary class="cursor-pointer text-sm font-semibold">Show recovery codes (single-use) — SAVE NOW</summary>
                                <ul class="font-mono text-xs mt-2 space-y-1">
                                    @foreach($recoveryPlain as $c)<li>{{ $c }}</li>@endforeach
                                </ul>
                                <p class="text-xs text-amber-700 mt-2">Copy these now — each can be used once if you lose your phone.</p>
                            </details>
                        @endif
                    </div>
                </div>
            </div>
        @elseif(!$hasMfa)
            <div class="rounded-lg border border-dashed p-6 bg-white text-center text-sm text-gray-500">
                Click <strong>Generate Secret & QR</strong> above to start enrollment.
            </div>
        @else
            <div class="rounded-lg border p-4 bg-green-50 text-sm text-green-800">
                MFA is active. You can <strong>Regenerate Secret</strong> (will require re-verify) or <strong>Disable MFA</strong> if needed. Recovery codes were shown only once at generation — store them securely.
            </div>
        @endif
    </div>
</x-filament-panels::page>
