@php
    use App\Models\DocumentationMaterial;
    use App\Models\Faq;
    use App\Models\SupportContact;
    use Illuminate\Support\Facades\Storage;

    $materials = DocumentationMaterial::query()
        ->where('is_published', true)
        ->orderBy('sort_order')
        ->latest('id')
        ->get();

    $supportContacts = SupportContact::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->latest('id')
        ->get();

    $faqs = Faq::query()
        ->where('is_published', true)
        ->orderBy('sort_order')
        ->latest('id')
        ->get();

    $materialCount = $materials->count();
    $videoCount = $materials->where('material_type', 'video')->count();
    $pdfCount = $materials->where('material_type', 'pdf')->count();
    $checklistCount = $materials->where('material_type', 'checklist')->count();

    $emailContacts = $supportContacts->where('type', 'email');
    $hotlineContacts = $supportContacts->where('type', 'hotline');
    $groupContacts = $supportContacts->where('type', 'internal_group');
@endphp

<x-layouts::app :title="__('Dokumentasi')">
    <div class="space-y-6" x-data="{ tab: 'training' }">
        <header class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h1 class="text-2xl font-semibold">Documentation Center</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Pusat panduan training dan support untuk operasional aplikasi IMSI Catcher.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('documentation.materials') }}" wire:navigate class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Kelola Materi</a>
                <a href="{{ route('documentation.support-contacts') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">Kelola Support Contacts</a>
                <a href="{{ route('documentation.faqs') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">Kelola FAQ</a>
            </div>
        </header>

        <section class="rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="tab = 'training'" :class="tab === 'training' ? 'bg-blue-600 text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'" class="rounded-lg px-4 py-2 text-sm font-medium transition">
                    Training
                </button>
                <button type="button" @click="tab = 'support'" :class="tab === 'support' ? 'bg-blue-600 text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'" class="rounded-lg px-4 py-2 text-sm font-medium transition">
                    Support
                </button>
            </div>
        </section>

        <div x-show="tab === 'training'" x-transition.opacity.duration.200ms class="space-y-6">
            <section class="grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs uppercase text-zinc-500">Total Materi</div>
                    <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($materialCount) }}</div>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs uppercase text-zinc-500">Video</div>
                    <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($videoCount) }}</div>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs uppercase text-zinc-500">PDF</div>
                    <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($pdfCount) }}</div>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs uppercase text-zinc-500">Checklist</div>
                    <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($checklistCount) }}</div>
                </div>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Library Materi</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    @forelse ($materials as $material)
                        <article class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="text-xs uppercase text-zinc-500">{{ $material->material_type }}</div>
                            <h3 class="mt-1 font-medium">{{ $material->title }}</h3>
                            <p class="mt-1 text-xs text-zinc-500">{{ $material->description ?: '-' }}</p>
                            <div class="mt-2 text-xs text-zinc-500">
                                @if ($material->duration_minutes)
                                    <span>Durasi: {{ $material->duration_minutes }} menit</span>
                                @endif
                                @if ($material->version)
                                    <span class="ml-2">Versi: {{ $material->version }}</span>
                                @endif
                            </div>
                            @if ($material->content_url)
                                <a href="{{ $material->content_url }}" target="_blank" class="mt-3 inline-block text-sm text-blue-600 hover:underline">Buka Materi</a>
                            @elseif ($material->file_path)
                                <a href="{{ Storage::url($material->file_path) }}" target="_blank" class="mt-3 inline-block text-sm text-blue-600 hover:underline">Buka File PDF</a>
                            @endif
                        </article>
                    @empty
                        <div class="col-span-full rounded-xl border border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-700">
                            Belum ada materi yang dipublikasikan.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div x-show="tab === 'support'" x-transition.opacity.duration.200ms class="space-y-6">
            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs uppercase text-zinc-500">Email Support</div>
                    <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($emailContacts->count()) }}</div>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs uppercase text-zinc-500">Hotline</div>
                    <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($hotlineContacts->count()) }}</div>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs uppercase text-zinc-500">Internal Group</div>
                    <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($groupContacts->count()) }}</div>
                </div>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Channel Support</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    @forelse ($supportContacts as $contact)
                        <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="text-xs uppercase text-zinc-500">{{ str_replace('_', ' ', $contact->type) }}</div>
                            <div class="mt-1 font-medium">{{ $contact->label }}</div>
                            @if ($contact->contact_url)
                                <a href="{{ $contact->contact_url }}" target="_blank" class="mt-1 block text-sm text-blue-600 hover:underline">{{ $contact->contact_value }}</a>
                            @else
                                <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $contact->contact_value }}</div>
                            @endif
                            @if ($contact->notes)
                                <p class="mt-2 text-xs text-zinc-500">{{ $contact->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full rounded-xl border border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-700">
                            Belum ada kontak support aktif.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">FAQ</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($faqs as $faq)
                        <details class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <summary class="cursor-pointer text-sm font-medium">{{ $faq->question }}</summary>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $faq->answer }}</p>
                            @if ($faq->category)
                                <p class="mt-2 text-xs text-zinc-500">Kategori: {{ $faq->category }}</p>
                            @endif
                        </details>
                    @empty
                        <div class="rounded-xl border border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-700">
                            Belum ada FAQ yang dipublikasikan.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
