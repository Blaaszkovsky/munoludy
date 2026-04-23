<x-layouts.app title="Smoke">
    <x-header />
    <main class="flex-1 px-8 py-12 md:px-16">
        <div class="max-w-4xl mx-auto">
            <x-form-card>
                <h2 class="text-3xl mb-4 font-heading">Smoke test</h2>
                <x-text-input name="email" label="Email" placeholder="test@example.com" />
                <x-ranked-input :questionId="1" />
                <div class="mt-6 flex gap-4">
                    <x-btn variant="outline">Back</x-btn>
                    <x-btn>Submit</x-btn>
                </div>
            </x-form-card>
        </div>
    </main>
    <x-footer />
</x-layouts.app>
