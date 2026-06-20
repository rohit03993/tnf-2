<x-site.layout title="Page Not Found — TNF Today">
    <div class="tnf-page-content">
        <div class="mx-auto max-w-lg rounded-tnf-lg bg-white p-10 text-center shadow-card">
            <p class="text-tnf-3xl font-black text-tnf-red">404</p>
            <h1 class="mt-2 text-tnf-xl font-bold text-tnf-navy">Page not found</h1>
            <p class="mt-3 text-tnf-sm text-tnf-muted">
                The page you are looking for may have been moved or no longer exists.
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="tnf-btn-primary">Go to Home</a>
                <a href="{{ route('search') }}" class="tnf-btn-outline">Search</a>
            </div>
        </div>
    </div>
</x-site.layout>
