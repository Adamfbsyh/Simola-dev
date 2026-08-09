@if($errors->any())
    <div class="mb-5 rounded-lg border border-red-200
                bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc space-y-1 ps-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('error'))
    <div class="mb-5 rounded-lg border border-red-200
                bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ session('error') }}
    </div>
@endif

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Kode SPBE
        </label>

        <input
            type="text"
            name="code"
            value="{{ old('code', $company->code) }}"
            class="w-full rounded-lg border-gray-300"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Nama {{ \App\Support\MasterFleet\FleetType::current(request()) === \App\Support\MasterFleet\FleetType::PERTASHOP ? 'SPBU / Perusahaan' : 'SPBE / Perusahaan' }}
        </label>

        <input
            type="text"
            name="name"
            required
            value="{{ old('name', $company->name) }}"
            class="w-full rounded-lg border-gray-300"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Pasangan TLPG
        </label>

        <select
            name="default_terminal_id"
            class="w-full rounded-lg border-gray-300"
        >
            <option value="">
                Pilih TLPG
            </option>

            @foreach($terminals as $terminal)
                <option
                    value="{{ $terminal->id }}"
                    @selected(
                        (string) old(
                            'default_terminal_id',
                            $company->default_terminal_id
                        )
                        ===
                        (string) $terminal->id
                    )
                >
                    {{ $terminal->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div></div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Latitude SPBE
        </label>

        <input
            type="number"
            step="0.0000001"
            name="latitude"
            value="{{ old('latitude', $company->latitude) }}"
            class="w-full rounded-lg border-gray-300"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Longitude SPBE
        </label>

        <input
            type="number"
            step="0.0000001"
            name="longitude"
            value="{{ old('longitude', $company->longitude) }}"
            class="w-full rounded-lg border-gray-300"
        >
    </div>
</div>

<hr class="my-7">

<h3 class="mb-4 text-lg font-bold text-gray-900">
    Profil Jarak SPBE ke TLPG
</h3>

<div class="grid gap-5 md:grid-cols-3">
    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Jarak (km)
        </label>

        <input
            type="number"
            step="0.01"
            min="0"
            name="distance_km"
            value="{{ old(
                'distance_km',
                $distanceProfile->distance_km
            ) }}"
            class="w-full rounded-lg border-gray-300"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Kategori
        </label>

        <select
            name="distance_category"
            class="w-full rounded-lg border-gray-300"
        >
            <option value="">
                Pilih Kategori
            </option>

            @foreach(
                config(
                    'master-fleet.distance_categories',
                    []
                )
                as $key => $category
            )
                <option
                    value="{{ $key }}"
                    @selected(
                        old(
                            'distance_category',
                            $distanceProfile
                                ->distance_category
                        )
                        === $key
                    )
                >
                    {{ $category['label'] }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Bobot
        </label>

        <input
            type="number"
            min="1"
            name="weight"
            value="{{ old(
                'weight',
                $distanceProfile->weight
            ) }}"
            class="w-full rounded-lg border-gray-300"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Sumber Jarak
        </label>

        <select
            name="distance_source"
            class="w-full rounded-lg border-gray-300"
        >
            @foreach([
                'manual' => 'Input Manual',
                'google_maps' => 'Google Maps',
                'validated' => 'Sudah Divalidasi',
            ] as $key => $label)
                <option
                    value="{{ $key }}"
                    @selected(
                        old(
                            'distance_source',
                            $distanceProfile
                                ->distance_source
                            ?? 'manual'
                        )
                        === $key
                    )
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Terakhir Diverifikasi
        </label>

        <input
            type="datetime-local"
            name="last_verified_at"
            value="{{ old(
                'last_verified_at',
                $distanceProfile->last_verified_at
                    ?->format('Y-m-d\TH:i')
            ) }}"
            class="w-full rounded-lg border-gray-300"
        >
    </div>

    <div></div>

    <div class="md:col-span-3">
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Catatan Rute
        </label>

        <textarea
            name="route_notes"
            rows="3"
            class="w-full rounded-lg border-gray-300"
        >{{ old(
            'route_notes',
            $distanceProfile->route_notes
        ) }}</textarea>
    </div>

    <div class="md:col-span-3">
        <label class="mb-2 block text-sm font-semibold text-gray-700">
            Catatan SPBE
        </label>

        <textarea
            name="notes"
            rows="3"
            class="w-full rounded-lg border-gray-300"
        >{{ old('notes', $company->notes) }}</textarea>
    </div>

    <div class="md:col-span-3">
        <input
            type="hidden"
            name="is_active"
            value="0"
        >

        <label class="inline-flex items-center gap-2">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                class="rounded border-gray-300"
                @checked(
                    (bool) old(
                        'is_active',
                        $company->is_active
                    )
                )
            >

            <span class="text-sm font-semibold text-gray-700">
                SPBE aktif
            </span>
        </label>
    </div>
</div>
