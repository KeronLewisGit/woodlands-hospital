@if (\App\Support\Notify::recipients() === [])
    <div class="mb-5 flex gap-3 rounded-xl border border-line bg-surface px-5 py-4 shadow-sm" role="alert">
        <x-icon name="alert" class="mt-0.5 h-5 w-5 flex-none text-[color:var(--attention)]" />
        <p><span class="font-bold">Nobody is emailed about new requests.</span> @if(auth()->user()->role === \App\Enums\Role::Admin) Add an address under <a class="text-link underline" href="{{ route('admin.settings.edit') }}#notify_email">Settings → Notification email</a>. @else Ask an administrator to set a notification email. @endif</p>
    </div>
@elseif (\App\Support\Notify::invalid() !== [])
    <div class="mb-5 flex gap-3 rounded-xl border border-line bg-surface px-5 py-4 shadow-sm" role="alert">
        <x-icon name="alert" class="mt-0.5 h-5 w-5 flex-none text-[color:var(--attention)]" />
        <p><span class="font-bold">Some notification addresses are skipped</span> because they are not email addresses: {{ implode(', ', \App\Support\Notify::invalid()) }}.</p>
    </div>
@endif
