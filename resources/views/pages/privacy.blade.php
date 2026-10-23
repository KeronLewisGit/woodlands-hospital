<x-layouts.site title="Privacy" description="How Woodlands Hospital handles information sent through this website and the results portal.">
    <x-page-header title="Privacy" sign="Your information" code="§" lead="What this website collects, why, and how the results portal keeps reports private." />
    <div class="wrap mt-12 prose-copy measure">
        <p class="callout">Draft for review by Woodlands Hospital before launch.</p>
        <h2>What we collect</h2>
        <ul>
            <li><strong>Appointment requests:</strong> your name, contact details, optional date of birth, preferred dates and the reason for your visit, so the front desk can arrange it.</li>
            <li><strong>Refill requests:</strong> your name, contact details and the medicines you list, so the pharmacy can prepare them.</li>
            <li><strong>Messages and job applications:</strong> what you send us, including your CV, so the right team can reply.</li>
            <li><strong>Results portal:</strong> your name, email, optional date of birth and hospital number, and the reports the laboratory or radiology team publishes for you.</li>
        </ul>
        <h2>How the results portal protects your reports</h2>
        <ul>
            <li>Reports are stored outside the public website and are only ever sent to the signed-in patient they belong to, or to authorised laboratory and radiology staff.</li>
            <li>Accounts are created by hospital staff; there is no public sign-up. Your first password is set from a one-time link that expires after 72 hours.</li>
            <li>Emails only say that a report is ready. Test names and results are never put in email.</li>
            <li>Sign-in attempts are rate-limited, and portal pages are never stored in shared caches.</li>
        </ul>
        <h2>Who sees it</h2>
        <p>Only Woodlands Hospital staff whose role needs it: the front desk for appointments and messages, the pharmacy for refills, laboratory and radiology staff for reports, and management for job applications. We do not sell or share your information for marketing.</p>
        <h2>Cookies</h2>
        <p>The site uses one essential session cookie to keep forms secure and keep you signed in. Your light or dark theme choice is saved in your own browser. There are no advertising or tracking cookies.</p>
        <h2>Contact</h2>
        <p>Questions about your information: <a href="mailto:{{ setting('email_care') }}">{{ setting('email_care') }}</a> or {{ format_phone(setting('phone_main'), true) }}.</p>
    </div>
</x-layouts.site>
