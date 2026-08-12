<x-app-layout>
    <x-slot name="header">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    SIMOLA Help Center
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Kelola knowledge base yang digunakan Help Assistant.
                </p>
            </div>
            <a
                href="{{ route('dashboard') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700"
            >
                Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[360px_minmax(0,1fr)]">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900">Tambah Artikel Bantuan</h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Artikel yang aktif dapat digunakan sebagai jawaban lokal dan konteks AI.
                    </p>

                    <form
                        method="POST"
                        action="{{ route('simola-help.admin.articles.store') }}"
                        class="mt-5 space-y-4"
                    >
                        @csrf

                        <div>
                            <label class="text-xs font-semibold text-gray-700">Judul</label>
                            <input
                                name="title"
                                value="{{ old('title') }}"
                                required
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-700">Modul</label>
                            <input
                                name="module"
                                value="{{ old('module') }}"
                                required
                                placeholder="Contoh: Master Fleet"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-700">Kata kunci</label>
                            <textarea
                                name="keywords_text"
                                rows="3"
                                placeholder="Pisahkan dengan koma"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                            >{{ old('keywords_text') }}</textarea>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-700">Isi panduan</label>
                            <textarea
                                name="content"
                                rows="9"
                                required
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                            >{{ old('content') }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-gray-700">Urutan</label>
                                <input
                                    type="number"
                                    name="sort_order"
                                    value="{{ old('sort_order', 100) }}"
                                    min="0"
                                    max="9999"
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                                >
                            </div>
                            <label class="flex items-end gap-2 pb-2 text-xs font-semibold text-gray-700">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    checked
                                    class="rounded border-gray-300"
                                >
                                Aktif
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white"
                        >
                            Simpan Artikel
                        </button>
                    </form>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Knowledge Base</h3>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $articles->count() }} artikel tersedia.
                                </p>
                            </div>
                            <div class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                AI: {{ config('simola-help.ai.enabled') && filled(config('simola-help.ai.api_key')) ? 'Aktif' : 'FAQ lokal' }}
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @forelse ($articles as $article)
                            <details class="group p-5" @if ($loop->first) open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="truncate font-semibold text-gray-900">
                                                {{ $article->title }}
                                            </span>
                                            @if ($article->is_active)
                                                <span class="rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-bold text-green-700">
                                                    Aktif
                                                </span>
                                            @else
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500">
                                                    Nonaktif
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ $article->module }} · urutan {{ $article->sort_order }}
                                        </p>
                                    </div>
                                    <span class="text-gray-400">⌄</span>
                                </summary>

                                <form
                                    method="POST"
                                    action="{{ route('simola-help.admin.articles.update', $article) }}"
                                    class="mt-5 grid gap-4"
                                >
                                    @csrf
                                    @method('PUT')

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="text-xs font-semibold text-gray-700">Judul</label>
                                            <input
                                                name="title"
                                                value="{{ $article->title }}"
                                                required
                                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                                            >
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-700">Modul</label>
                                            <input
                                                name="module"
                                                value="{{ $article->module }}"
                                                required
                                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                                            >
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-xs font-semibold text-gray-700">Kata kunci</label>
                                        <textarea
                                            name="keywords_text"
                                            rows="2"
                                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                                        >{{ implode(', ', $article->keywords ?: []) }}</textarea>
                                    </div>

                                    <div>
                                        <label class="text-xs font-semibold text-gray-700">Isi panduan</label>
                                        <textarea
                                            name="content"
                                            rows="8"
                                            required
                                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                                        >{{ $article->content }}</textarea>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-4">
                                            <input
                                                type="number"
                                                name="sort_order"
                                                value="{{ $article->sort_order }}"
                                                min="0"
                                                max="9999"
                                                class="w-24 rounded-lg border-gray-300 text-sm"
                                            >
                                            <label class="flex items-center gap-2 text-xs font-semibold text-gray-700">
                                                <input
                                                    type="checkbox"
                                                    name="is_active"
                                                    value="1"
                                                    @checked($article->is_active)
                                                    class="rounded border-gray-300"
                                                >
                                                Aktif
                                            </label>
                                        </div>

                                        <button
                                            type="submit"
                                            class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white"
                                        >
                                            Simpan Perubahan
                                        </button>
                                    </div>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('simola-help.admin.articles.destroy', $article) }}"
                                    class="mt-3"
                                    onsubmit="return confirm('Hapus artikel bantuan ini?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="text-xs font-semibold text-red-600 hover:text-red-700"
                                    >
                                        Hapus artikel
                                    </button>
                                </form>
                            </details>
                        @empty
                            <div class="p-8 text-center text-sm text-gray-500">
                                Belum ada artikel.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
