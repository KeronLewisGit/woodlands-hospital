@if ($errors->any())
    <div class="callout callout-er mb-8" role="alert" tabindex="-1" id="form-errors">
        <p class="flex items-center gap-2 font-bold"><x-icon name="alert" class="h-5 w-5 text-er-text" />Please check {{ $errors->count() === 1 ? 'one thing' : $errors->count().' things' }} below</p>
        <ul class="mt-2 list-disc pl-7 text-[0.9375rem]">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
