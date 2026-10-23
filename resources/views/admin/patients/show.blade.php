<x-layouts.admin :title="$patient->full_name">
    <x-slot:actions><a href="{{ route('admin.patients.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />All patients</a></x-slot:actions>
    <div class="grid gap-5 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-5">
            @if ($canSeeResults)
                <section class="a-card p-5" aria-labelledby="upload-title">
                    <h2 id="upload-title" class="font-bold">Publish a report</h2>
                    <p class="text-sm text-ink-3">PDF only. It appears in {{ $patient->first_name }}’s portal straight away.</p>
                    <form method="POST" action="{{ route('admin.results.store', $patient) }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div><label class="a-label" for="kind">Department</label><select id="kind" name="kind" class="a-input w-full">@foreach (\App\Enums\ResultKind::cases() as $k)<option value="{{ $k->value }}" @selected(old('kind') === $k->value)>{{ $k->label() }}</option>@endforeach</select></div>
                        <div><label class="a-label" for="collected_on">Sample / scan date</label><input id="collected_on" name="collected_on" type="date" class="a-input w-full" value="{{ old('collected_on', today()->toDateString()) }}" max="{{ today()->toDateString() }}"></div>
                        <div class="sm:col-span-2"><label class="a-label" for="title">Report title</label><input id="title" name="title" class="a-input w-full" value="{{ old('title') }}" required placeholder="e.g. Full blood count"><p class="mt-1 text-xs text-ink-3">Shown to the patient in the portal only, never in email.</p></div>
                        <div class="sm:col-span-2"><label class="a-label" for="file">PDF file</label><input id="file" name="file" type="file" accept="application/pdf,.pdf" class="a-input w-full" required></div>
                        <label class="flex min-h-11 items-center gap-3 sm:col-span-2"><input type="hidden" name="notify" value="0"><input type="checkbox" name="notify" value="1" checked class="h-4 w-4 accent-[var(--primary)]">Email {{ $patient->first_name }} that a report is ready {{ $patient->isActivated() ? '' : '(sends a portal invitation, as the account is not active yet)' }}</label>
                        <div class="sm:col-span-2"><button class="a-btn a-btn-primary"><x-icon name="upload" class="h-4 w-4" />Publish to portal</button></div>
                    </form>
                </section>
                <section class="a-card overflow-hidden" aria-labelledby="reports-title">
                    <h2 id="reports-title" class="p-5 pb-3 font-bold">Reports in the portal</h2>
                    @if ($results->isEmpty())
                        <p class="px-5 pb-5 text-ink-2">None yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="data-table">
                                <thead><tr><th>Report</th><th>Published</th><th>Opened by patient</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($results as $r)
                                        <tr>
                                            <td><span class="font-semibold">{{ $r->title }}</span><span class="block text-sm text-ink-3">{{ $r->kind->label() }} · {{ $r->sizeLabel() }} · by {{ $r->uploader->name ?? '—' }}</span></td>
                                            <td class="whitespace-nowrap">{{ $r->created_at->format('j M Y, g:i a') }}</td>
                                            <td>@if($r->first_viewed_at)<x-chip tone="good" icon="check">{{ $r->first_viewed_at->format('j M') }}</x-chip>@else<x-chip tone="neutral" icon="dot">Not yet</x-chip>@endif</td>
                                            <td class="r whitespace-nowrap">
                                                <a href="{{ route('admin.results.show', $r) }}" target="_blank" class="a-btn a-btn-ghost">View</a>
                                                <form method="POST" action="{{ route('admin.results.destroy', $r) }}" class="inline" onsubmit="return confirm('Delete this report from the portal? The patient will no longer see it.')">@csrf @method('DELETE')<button class="a-btn text-[color:var(--critical-text)] hover:bg-surface-2" aria-label="Delete {{ $r->title }}"><x-icon name="trash" class="h-4 w-4" /></button></form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @else
                <section class="a-card p-5">
                    <h2 class="font-bold">Reports</h2>
                    <p class="mt-1 text-ink-2">{{ $resultCount }} {{ \Illuminate\Support\Str::plural('report', $resultCount) }} published. Report contents are visible to laboratory, radiology and admin staff only.</p>
                </section>
            @endif
        </div>

        <aside class="space-y-5">
            <section class="a-card p-5">
                <h2 class="font-bold">Portal access</h2>
                <div class="mt-3">
                    @if ($patient->isActivated())
                        <x-chip tone="good" icon="check">Active since {{ $patient->activated_at?->format('j M Y') }}</x-chip>
                        <p class="mt-2 text-sm text-ink-2">Last sign-in {{ $patient->last_login_at?->diffForHumans() ?? 'never' }}. Forgotten passwords are reset by the patient from the sign-in page.</p>
                    @else
                        @if ($patient->invited_at)<x-chip tone="attention" icon="mail">Invited {{ $patient->invited_at->diffForHumans() }}</x-chip>@else<x-chip tone="neutral" icon="dot">Not invited yet</x-chip>@endif
                        <p class="mt-2 text-sm text-ink-2">Invitation links work for {{ \App\Notifications\PortalInvitation::HOURS }} hours. Sending a new one cancels older links.</p>
                        <form method="POST" action="{{ route('admin.patients.invite', $patient) }}" class="mt-3">@csrf<button class="a-btn a-btn-primary w-full"><x-icon name="mail" class="h-4 w-4" />{{ $patient->invited_at ? 'Send a new invitation' : 'Send invitation' }}</button></form>
                    @endif
                </div>
            </section>
            <section class="a-card p-5">
                <h2 class="font-bold">Details</h2>
                <form method="POST" action="{{ route('admin.patients.update', $patient) }}" class="mt-3 space-y-4">
                    @csrf @method('PUT')
                    @include('admin.patients._fields', ['cols' => 'sm:grid-cols-2 xl:grid-cols-1'])
                    <button class="a-btn a-btn-ghost">Save details</button>
                </form>
            </section>
        </aside>
    </div>
</x-layouts.admin>
