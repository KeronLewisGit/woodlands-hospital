<x-layouts.admin title="Staff accounts">
    <div class="grid gap-5 xl:grid-cols-[1fr_24rem]">
        <section class="a-card overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Role</th><th></th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td class="font-bold">{{ $u->name }}<span class="block text-sm font-normal text-ink-3">{{ $u->email }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('admin.staff.update', $u) }}" class="flex gap-2">@csrf @method('PATCH')
                                    <label class="sr-only" for="role-{{ $u->id }}">Role for {{ $u->name }}</label>
                                    <select id="role-{{ $u->id }}" name="role" class="a-input">@foreach (\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}" @selected($u->role === $r)>{{ $r->label() }}</option>@endforeach</select>
                                    <button class="a-btn a-btn-ghost">Save</button>
                                </form>
                            </td>
                            <td class="r">@unless ($u->is(auth()->user()))<form method="POST" action="{{ route('admin.staff.destroy', $u) }}" onsubmit="return confirm('Remove {{ $u->name }}’s account?')">@csrf @method('DELETE')<button class="a-btn text-[color:var(--critical-text)] hover:bg-surface-2">Remove</button></form>@endunless</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <section class="a-card p-5">
            <h2 class="font-bold">Add a staff member</h2>
            <form method="POST" action="{{ route('admin.staff.store') }}" class="mt-4 space-y-3">
                @csrf
                <div><label class="a-label" for="name">Name</label><input id="name" name="name" class="a-input w-full" value="{{ old('name') }}" required></div>
                <div><label class="a-label" for="email">Work email</label><input id="email" name="email" type="email" class="a-input w-full" value="{{ old('email') }}" required></div>
                <div><label class="a-label" for="role">Role</label><select id="role" name="role" class="a-input w-full">@foreach (\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}">{{ $r->label() }}</option>@endforeach</select></div>
                <ul class="space-y-1 text-xs text-ink-3">@foreach (\App\Enums\Role::cases() as $r)<li><strong>{{ $r->label() }}:</strong> {{ $r->description() }}</li>@endforeach</ul>
                <div><label class="a-label" for="password">Temporary password</label><input id="password" name="password" type="text" minlength="10" class="a-input w-full" required autocomplete="off"></div>
                <button class="a-btn a-btn-primary w-full">Create account</button>
            </form>
        </section>
    </div>
</x-layouts.admin>
