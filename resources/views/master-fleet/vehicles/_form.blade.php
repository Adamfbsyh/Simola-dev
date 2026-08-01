@php
    $editing = $vehicle->exists;
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label
            for="plate-number"
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            Nomor Polisi
        </label>

        <input
            id="plate-number"
            type="text"
            name="plate_number"
            required
            maxlength="30"
            value="{{ old('plate_number', $vehicle->plate_number) }}"
            placeholder="AE 8518 UJ"
            class="w-full rounded-lg border-gray-300 uppercase
                   shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >

        <p class="mt-1 text-xs text-gray-500">
            Penulisan akan otomatis dirapikan oleh sistem.
        </p>
    </div>

    <div>
        <label
            for="company-search"
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            SPBE / Perusahaan
        </label>

        <div
            class="rounded-lg border border-gray-300 bg-white
                focus-within:border-blue-500
                focus-within:ring-1 focus-within:ring-blue-500"
            data-company-filter-wrapper
        >
            {{-- Search perusahaan --}}
            <div
                class="flex items-center border-b border-gray-200 px-3"
            >
                <svg
                    class="h-4 w-4 shrink-0 text-gray-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>

                <input
                    id="company-search"
                    type="search"
                    placeholder="Cari nama perusahaan..."
                    autocomplete="off"
                    class="w-full border-0 px-3 py-2.5 text-sm
                        shadow-none outline-none
                        focus:border-0 focus:ring-0"
                    data-company-search
                >

                <button
                    type="button"
                    class="hidden rounded-md px-2 py-1
                        text-sm text-gray-400
                        hover:bg-gray-100 hover:text-gray-700"
                    title="Hapus pencarian"
                    data-company-search-clear
                >
                    ✕
                </button>
            </div>

            {{-- Dropdown perusahaan --}}
            <select
                id="company"
                name="company_id"
                class="w-full border-0 px-3 py-2.5
                    text-sm shadow-none
                    focus:border-0 focus:ring-0"
                data-company-select
            >
                <option
                    value=""
                    data-company-name="belum tersedia"
                >
                    Belum tersedia
                </option>

                @foreach($companies as $company)
                    <option
                        value="{{ $company->id }}"
                        data-company-name="{{
                            mb_strtolower(
                                $company->name,
                                'UTF-8'
                            )
                        }}"
                        @selected(
                            (int) old(
                                'company_id',
                                $vehicle->company_id
                            ) === $company->id
                        )
                    >
                        {{ $company->name }}
                        {{ !$company->is_active ? '— Nonaktif' : '' }}
                    </option>
                @endforeach
            </select>

            {{-- Informasi hasil --}}
            <div
                class="hidden border-t border-gray-100
                    px-3 py-2 text-xs text-gray-500"
                data-company-filter-info
            ></div>
        </div>

        @if($editing)
            <p class="mt-1 text-xs text-orange-600">
                Perubahan perusahaan akan mengosongkan profil
                jarak pada PC Set aktif dan draft agar dihitung ulang.
            </p>
        @endif
    </div>

    <div>
        <label
            for="unit-code"
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            Kode Unit
        </label>

        <input
            id="unit-code"
            type="text"
            name="unit_code"
            maxlength="100"
            value="{{ old('unit_code', $vehicle->unit_code) }}"
            placeholder="Opsional"
            class="w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            Status Kendaraan
        </label>

        <input
            type="hidden"
            name="is_active"
            value="0"
        >

        <label
            class="flex min-h-11 items-center gap-3 rounded-lg
                   border border-gray-300 bg-white px-4"
        >
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(
                    old(
                        'is_active',
                        $vehicle->is_active
                    )
                )
                class="rounded border-gray-300 text-blue-600"
            >

            <span class="text-sm font-semibold text-gray-700">
                Kendaraan aktif
            </span>
        </label>
    </div>

    <div>
        <label
            for="effective-from"
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            Mulai Berlaku
        </label>

        <input
            id="effective-from"
            type="date"
            name="effective_from"
            value="{{ old(
                'effective_from',
                optional($vehicle->effective_from)->format('Y-m-d')
            ) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label
            for="effective-until"
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            Berlaku Sampai
        </label>

        <input
            id="effective-until"
            type="date"
            name="effective_until"
            value="{{ old(
                'effective_until',
                optional($vehicle->effective_until)->format('Y-m-d')
            ) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm"
        >

        <p class="mt-1 text-xs text-gray-500">
            Kosongkan apabila kendaraan masih aktif.
        </p>
    </div>

    <div class="md:col-span-2">
        <label
            for="notes"
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            Catatan Kendaraan
        </label>

        <textarea
            id="notes"
            name="notes"
            rows="4"
            maxlength="2000"
            class="w-full rounded-lg border-gray-300 shadow-sm"
            placeholder="Catatan tambahan kendaraan"
        >{{ old('notes', $vehicle->notes) }}</textarea>
    </div>

    @if($editing)
        <div
            class="md:col-span-2 rounded-xl border border-blue-200
                   bg-blue-50 p-5"
        >
            <h3 class="font-bold text-blue-900">
                Data perubahan nopol
            </h3>

            <p class="mt-1 text-sm text-blue-800">
                Bagian ini hanya wajib diisi ketika nomor polisi
                di atas benar-benar diubah.
            </p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        for="plate-change-date"
                        class="mb-2 block text-sm font-semibold
                               text-blue-900"
                    >
                        Tanggal Perubahan
                    </label>

                    <input
                        id="plate-change-date"
                        type="date"
                        name="plate_change_effective_date"
                        value="{{ old(
                            'plate_change_effective_date',
                            now()->toDateString()
                        ) }}"
                        class="w-full rounded-lg border-blue-300"
                    >
                </div>

                <div>
                    <label
                        for="plate-change-reason"
                        class="mb-2 block text-sm font-semibold
                               text-blue-900"
                    >
                        Alasan Perubahan
                    </label>

                    <input
                        id="plate-change-reason"
                        type="text"
                        name="plate_change_reason"
                        maxlength="1000"
                        value="{{ old('plate_change_reason') }}"
                        placeholder="Contoh: pergantian nopol kendaraan"
                        class="w-full rounded-lg border-blue-300"
                    >
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const wrappers =
            document.querySelectorAll(
                '[data-company-filter-wrapper]'
            );

        wrappers.forEach(function (wrapper) {
            const searchInput =
                wrapper.querySelector(
                    '[data-company-search]'
                );

            const companySelect =
                wrapper.querySelector(
                    '[data-company-select]'
                );

            const clearButton =
                wrapper.querySelector(
                    '[data-company-search-clear]'
                );

            const infoElement =
                wrapper.querySelector(
                    '[data-company-filter-info]'
                );

            if (
                !searchInput
                || !companySelect
            ) {
                return;
            }

            const options =
                Array.from(
                    companySelect.options
                );

            const normalizeText =
                function (value) {
                    return String(value || '')
                        .normalize('NFD')
                        .replace(
                            /[\u0300-\u036f]/g,
                            ''
                        )
                        .toLowerCase()
                        .replace(
                            /[^a-z0-9]+/g,
                            ' '
                        )
                        .trim();
                };

            const filterCompanies =
                function () {
                    const keyword =
                        normalizeText(
                            searchInput.value
                        );

                    let visibleCount = 0;

                    options.forEach(
                        function (option, index) {
                            /*
                             * Pilihan "Belum tersedia"
                             * selalu ditampilkan.
                             */
                            if (index === 0) {
                                option.hidden = false;

                                return;
                            }

                            const companyName =
                                normalizeText(
                                    option.dataset
                                        .companyName
                                    || option.textContent
                                );

                            const isMatch =
                                keyword === ''
                                || companyName.includes(
                                    keyword
                                );

                            option.hidden =
                                !isMatch;

                            if (isMatch) {
                                visibleCount++;
                            }
                        }
                    );

                    if (clearButton) {
                        clearButton.classList.toggle(
                            'hidden',
                            keyword === ''
                        );
                    }

                    if (infoElement) {
                        if (keyword === '') {
                            infoElement.classList.add(
                                'hidden'
                            );

                            infoElement.textContent = '';

                            return;
                        }

                        infoElement.classList.remove(
                            'hidden'
                        );

                        infoElement.textContent =
                            visibleCount > 0
                                ? visibleCount
                                    + ' perusahaan ditemukan.'
                                : 'Perusahaan tidak ditemukan.';
                    }

                    /*
                     * Apabila perusahaan yang sedang
                     * terpilih tidak cocok dengan pencarian,
                     * pilihan tidak langsung dihapus.
                     */
                };

            searchInput.addEventListener(
                'input',
                filterCompanies
            );

            searchInput.addEventListener(
                'keydown',
                function (event) {
                    if (
                        event.key === 'ArrowDown'
                    ) {
                        event.preventDefault();
                        companySelect.focus();
                    }

                    if (
                        event.key === 'Escape'
                    ) {
                        searchInput.value = '';
                        filterCompanies();
                    }
                }
            );

            if (clearButton) {
                clearButton.addEventListener(
                    'click',
                    function () {
                        searchInput.value = '';
                        filterCompanies();
                        searchInput.focus();
                    }
                );
            }

            /*
             * Setelah perusahaan dipilih,
             * kolom pencarian dikosongkan agar
             * seluruh pilihan kembali tersedia.
             */
            companySelect.addEventListener(
                'change',
                function () {
                    searchInput.value = '';
                    filterCompanies();
                }
            );
        });
    });
</script>