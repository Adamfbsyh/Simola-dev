@php
    $coordinateValue = old(
        'coordinates',
        \App\Support\CoordinateParser::format(
            $terminal->latitude,
            $terminal->longitude
        )
    );
@endphp

@if($errors->any())
    <div
        class="mb-5 rounded-lg border border-red-200
               bg-red-50 px-4 py-3 text-sm text-red-800"
    >
        <div class="font-semibold">
            Data belum dapat disimpan.
        </div>

        <ul class="mt-2 list-disc space-y-1 ps-5">
            @foreach($errors->all() as $error)
                <li>
                    {{ $error }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('error'))
    <div
        class="mb-5 rounded-lg border border-red-200
               bg-red-50 px-4 py-3 text-sm text-red-800"
    >
        {{ session('error') }}
    </div>
@endif

<div class="grid gap-5 md:grid-cols-2">
    {{-- Kode TLPG --}}
    <div>
        <label
            for="terminal-code"
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            Kode TLPG
        </label>

        <input
            id="terminal-code"
            type="text"
            name="code"
            maxlength="50"
            value="{{ old(
                'code',
                $terminal->code
            ) }}"
            class="w-full rounded-lg border-gray-300
                   uppercase shadow-sm
                   focus:border-blue-500 focus:ring-blue-500"
            placeholder="Contoh: TLPG-TJ-PERAK"
            autocomplete="off"
        >

        <p class="mt-1 text-xs text-gray-500">
            Kode bersifat opsional dan akan disimpan menggunakan huruf kapital.
        </p>
    </div>

    {{-- Nama TLPG --}}
    <div>
        <label
            for="terminal-name"
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            Nama TLPG / Terminal
            <span class="text-red-600">*</span>
        </label>

        <input
            id="terminal-name"
            type="text"
            name="name"
            required
            maxlength="255"
            value="{{ old(
                'name',
                $terminal->name
            ) }}"
            class="w-full rounded-lg border-gray-300
                   shadow-sm
                   focus:border-blue-500 focus:ring-blue-500"
            placeholder="Contoh: TLPG TJ PERAK"
            autocomplete="off"
        >
    </div>

    {{-- Koordinat gabungan --}}
    <div class="md:col-span-2">
        <label
            for="terminal-coordinates"
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            Koordinat TLPG
        </label>

        <input
            id="terminal-coordinates"
            type="text"
            name="coordinates"
            maxlength="500"
            value="{{ $coordinateValue }}"
            class="w-full rounded-lg border-gray-300
                   shadow-sm
                   focus:border-blue-500 focus:ring-blue-500"
            placeholder="-7.203807110524606, 112.71950675497641"
            autocomplete="off"
            spellcheck="false"
        >

        <div class="mt-2 space-y-1 text-xs text-gray-500">
            <p>
                Tempel langsung koordinat dari Google Maps dengan urutan
                <strong>Latitude, Longitude</strong>.
            </p>

            <p>
                Contoh desimal:
                <code
                    class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700"
                >
                    -7.203807110524606, 112.71950675497641
                </code>
            </p>

            <p>
                Format derajat dari Google Maps juga dapat digunakan:
                <code
                    class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700"
                >
                    7°12'13.7"S 112°43'10.2"E
                </code>
            </p>
        </div>

        {{-- Preview koordinat --}}
        <div
            id="terminal-coordinate-preview-container"
            class="mt-4 hidden rounded-lg border border-blue-200
                   bg-blue-50 px-4 py-3"
        >
            <div class="flex flex-col justify-between gap-3 sm:flex-row">
                <div>
                    <p
                        class="text-xs font-semibold uppercase
                               tracking-wide text-blue-600"
                    >
                        Koordinat yang akan disimpan
                    </p>

                    <p
                        id="terminal-coordinate-preview"
                        class="mt-1 text-sm font-bold text-blue-900"
                    ></p>

                    <p class="mt-1 text-xs text-blue-700">
                        Koordinat otomatis dibulatkan menjadi tujuh angka desimal.
                    </p>
                </div>

                <div class="shrink-0">
                    <a
                        id="terminal-coordinate-map-link"
                        href="#"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center rounded-lg
                               border border-blue-300 bg-white
                               px-3 py-2 text-xs font-semibold
                               text-blue-700 shadow-sm
                               hover:bg-blue-100"
                    >
                        Periksa di Google Maps
                    </a>
                </div>
            </div>
        </div>

        <p
            id="terminal-coordinate-error"
            class="mt-2 hidden text-xs font-semibold text-red-600"
        ></p>
    </div>

    {{-- Catatan --}}
    <div class="md:col-span-2">
        <label
            for="terminal-notes"
            class="mb-2 block text-sm font-semibold text-gray-700"
        >
            Catatan
        </label>

        <textarea
            id="terminal-notes"
            name="notes"
            rows="4"
            maxlength="5000"
            class="w-full rounded-lg border-gray-300
                   shadow-sm
                   focus:border-blue-500 focus:ring-blue-500"
            placeholder="Catatan lokasi, nama terminal, atau informasi lainnya."
        >{{ old(
            'notes',
            $terminal->notes
        ) }}</textarea>
    </div>

    {{-- Status aktif --}}
    <div class="md:col-span-2">
        <input
            type="hidden"
            name="is_active"
            value="0"
        >

        <label
            class="inline-flex cursor-pointer items-center gap-3
                   rounded-lg border border-gray-200
                   bg-gray-50 px-4 py-3"
        >
            <input
                type="checkbox"
                name="is_active"
                value="1"
                class="rounded border-gray-300
                       text-blue-600 shadow-sm
                       focus:ring-blue-500"
                @checked(
                    (bool) old(
                        'is_active',
                        $terminal->is_active
                    )
                )
            >

            <span>
                <span class="block text-sm font-semibold text-gray-800">
                    TLPG aktif
                </span>

                <span class="block text-xs text-gray-500">
                    TLPG aktif dapat digunakan sebagai pasangan SPBE dan profil jarak.
                </span>
            </span>
        </label>
    </div>
</div>

<script>
    (() => {
        const input =
            document.getElementById(
                'terminal-coordinates'
            );

        const previewContainer =
            document.getElementById(
                'terminal-coordinate-preview-container'
            );

        const preview =
            document.getElementById(
                'terminal-coordinate-preview'
            );

        const mapLink =
            document.getElementById(
                'terminal-coordinate-map-link'
            );

        const errorElement =
            document.getElementById(
                'terminal-coordinate-error'
            );

        if (
            !input
            ||
            !previewContainer
            ||
            !preview
            ||
            !mapLink
            ||
            !errorElement
        ) {
            return;
        }

        /**
         * Mengubah DMS menjadi koordinat desimal.
         */
        function dmsToDecimal(
            degrees,
            minutes,
            seconds,
            direction
        ) {
            let result =
                Number(degrees)
                +
                (
                    Number(minutes)
                    /
                    60
                )
                +
                (
                    Number(seconds)
                    /
                    3600
                );

            if (
                direction === 'S'
                ||
                direction === 'W'
            ) {
                result *= -1;
            }

            return result;
        }

        /**
         * Memeriksa rentang latitude dan longitude.
         */
        function validateRange(
            latitude,
            longitude
        ) {
            if (
                !Number.isFinite(latitude)
                ||
                !Number.isFinite(longitude)
            ) {
                return false;
            }

            if (
                latitude < -90
                ||
                latitude > 90
            ) {
                return false;
            }

            if (
                longitude < -180
                ||
                longitude > 180
            ) {
                return false;
            }

            return {
                latitude:
                    latitude.toFixed(7),

                longitude:
                    longitude.toFixed(7),
            };
        }

        /**
         * Membaca koordinat dari beberapa format.
         */
        function parseCoordinates(
            rawValue
        ) {
            let value =
                String(
                    rawValue || ''
                ).trim();

            if (value === '') {
                return null;
            }

            value = value.replace(
                /[−–—]/g,
                '-'
            );

            /*
             * Format DMS:
             * 7°12'13.7"S 112°43'10.2"E
             */
            const dmsPattern =
                /(\d{1,2})\s*[°º]\s*(\d{1,2})\s*['’′]\s*(\d+(?:\.\d+)?)\s*["”″]?\s*([NS])\s*[,\s;]+\s*(\d{1,3})\s*[°º]\s*(\d{1,2})\s*['’′]\s*(\d+(?:\.\d+)?)\s*["”″]?\s*([EW])/i;

            const dmsMatch =
                value.match(
                    dmsPattern
                );

            if (dmsMatch) {
                const latitude =
                    dmsToDecimal(
                        dmsMatch[1],
                        dmsMatch[2],
                        dmsMatch[3],
                        dmsMatch[4].toUpperCase()
                    );

                const longitude =
                    dmsToDecimal(
                        dmsMatch[5],
                        dmsMatch[6],
                        dmsMatch[7],
                        dmsMatch[8].toUpperCase()
                    );

                return validateRange(
                    latitude,
                    longitude
                );
            }

            /*
             * Format koma desimal:
             * -7,2038071; 112,7195068
             */
            if (
                value.includes(';')
            ) {
                const parts =
                    value.split(';');

                if (
                    parts.length === 2
                ) {
                    const latitude =
                        Number(
                            parts[0]
                                .trim()
                                .replace(',', '.')
                        );

                    const longitude =
                        Number(
                            parts[1]
                                .trim()
                                .replace(',', '.')
                        );

                    return validateRange(
                        latitude,
                        longitude
                    );
                }
            }

            /*
             * Format desimal atau URL Google Maps.
             */
            const decimalMatch =
                value.match(
                    /(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)/
                );

            if (decimalMatch) {
                return validateRange(
                    Number(decimalMatch[1]),
                    Number(decimalMatch[2])
                );
            }

            /*
             * Format dipisahkan spasi.
             */
            const spaceMatch =
                value.match(
                    /^(-?\d{1,2}(?:\.\d+)?)\s+(-?\d{1,3}(?:\.\d+)?)$/
                );

            if (spaceMatch) {
                return validateRange(
                    Number(spaceMatch[1]),
                    Number(spaceMatch[2])
                );
            }

            return false;
        }

        function hideMessages() {
            previewContainer
                .classList
                .add('hidden');

            errorElement
                .classList
                .add('hidden');

            errorElement.textContent = '';
        }

        function showError(
            message
        ) {
            previewContainer
                .classList
                .add('hidden');

            errorElement.textContent =
                message;

            errorElement
                .classList
                .remove('hidden');
        }

        function showPreview(
            result
        ) {
            const normalized =
                result.latitude
                +
                ', '
                +
                result.longitude;

            preview.textContent =
                normalized;

            mapLink.href =
                'https://www.google.com/maps?q='
                +
                encodeURIComponent(
                    normalized
                );

            errorElement
                .classList
                .add('hidden');

            previewContainer
                .classList
                .remove('hidden');
        }

        function updatePreview(
            normalizeInput = false
        ) {
            const result =
                parseCoordinates(
                    input.value
                );

            hideMessages();

            if (result === null) {
                return;
            }

            if (result === false) {
                showError(
                    'Format koordinat belum valid. Gunakan contoh: -7.2038071, 112.7195068'
                );

                return;
            }

            if (normalizeInput) {
                input.value =
                    result.latitude
                    +
                    ', '
                    +
                    result.longitude;
            }

            showPreview(
                result
            );
        }

        input.addEventListener(
            'input',
            () => {
                updatePreview(false);
            }
        );

        input.addEventListener(
            'paste',
            () => {
                window.setTimeout(
                    () => {
                        updatePreview(false);
                    },
                    0
                );
            }
        );

        input.addEventListener(
            'blur',
            () => {
                updatePreview(true);
            }
        );

        updatePreview(false);
    })();
</script>