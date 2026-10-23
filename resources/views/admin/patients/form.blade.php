<x-layouts.admin title="New portal record">
    <form method="POST" action="{{ route('admin.patients.store') }}" class="a-card max-w-3xl space-y-5 p-5">
        @csrf
        @include('admin.patients._fields')
        <label class="flex min-h-11 items-center gap-3"><input type="hidden" name="send_invite" value="0"><input type="checkbox" name="send_invite" value="1" checked class="h-4 w-4 accent-[var(--primary)]">Email the portal invitation now</label>
        <div class="flex gap-2"><button class="a-btn a-btn-primary">Create record</button><a href="{{ route('admin.patients.index') }}" class="a-btn">Cancel</a></div>
    </form>
</x-layouts.admin>
