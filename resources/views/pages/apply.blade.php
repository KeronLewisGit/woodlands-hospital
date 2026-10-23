<x-layouts.site title="Apply" description="Apply for a job at Woodlands Hospital, Georgetown.">
    <x-page-header title="{{ $selected ? 'Apply: '.$selected->title : 'Send your application' }}" sign="Careers" code="HR"
        lead="It takes about five minutes. Have your CV ready as a PDF or Word file." />
    <div class="wrap mt-12">
        <form method="POST" action="{{ route('apply.store') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6" novalidate>
            @csrf
            <x-form-errors />
            <div>
                <label for="job_posting_id" class="label">Role</label>
                <select id="job_posting_id" name="job_posting_id" class="input">
                    <option value="">General application (no specific role)</option>
                    @foreach ($jobs as $job)<option value="{{ $job->id }}" @selected((string) old('job_posting_id', $selected?->id) === (string) $job->id)>{{ $job->title }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="category" class="label">Area of work</label>
                <select id="category" name="category" class="input sm:max-w-md" @error('category') aria-invalid="true" @enderror>
                    <option value="">Choose one</option>
                    @foreach (\App\Enums\JobCategory::cases() as $c)<option value="{{ $c->value }}" @selected(old('category', $selected?->category->value) === $c->value)>{{ $c->label() }}</option>@endforeach
                </select>
                <span class="hint">If you picked a role above, we use that role’s area.</span>
                <x-error for="category" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="first_name" label="First name" autocomplete="given-name" />
                <x-field name="last_name" label="Last name" autocomplete="family-name" />
                <x-field name="phone" type="tel" label="Phone" autocomplete="tel" inputmode="tel" />
                <x-field name="email" type="email" label="Email" autocomplete="email" />
                <x-field name="registration" label="Professional registration number" :optional="true" hint="For example, your Nursing Council or Medical Council number." />
                <x-field name="years_experience" type="number" label="Years of experience" :optional="true" min="0" max="60" inputmode="numeric" />
            </div>
            <x-field name="cv" type="file" label="CV" accept=".pdf,.doc,.docx" hint="PDF or Word, up to 5 MB." />
            <x-field name="message" type="textarea" label="Anything else?" :optional="true" maxlength="3000" />
            <div class="hidden" aria-hidden="true"><label for="website">Leave this empty</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
            <label class="choice"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>Woodlands Hospital may keep my application on file and contact me about roles. <a href="{{ route('privacy') }}" class="text-link">Privacy</a></span></label>
            <x-error for="consent" />
            <button class="btn btn-clay">Send application<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
        </form>
    </div>
</x-layouts.site>
