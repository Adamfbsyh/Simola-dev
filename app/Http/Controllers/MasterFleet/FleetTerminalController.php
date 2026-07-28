<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\FleetTerminal;
use App\Support\CoordinateParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class FleetTerminalController extends Controller
{
    /**
     * Menampilkan daftar TLPG/Terminal.
     */
    public function index(
        Request $request
    ): View {
        $search = trim(
            (string) $request->input(
                'search',
                ''
            )
        );

        $status = trim(
            (string) $request->input(
                'status',
                ''
            )
        );

        $terminals = FleetTerminal::query()
            ->withCount([
                'companies',
                'distanceProfiles',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $query->where(
                        function ($subQuery) use ($search): void {
                            $subQuery
                                ->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'code',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
                }
            )
            ->when(
                $status === 'active',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
            )
            ->when(
                $status === 'inactive',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        false
                    )
            )
            ->orderByDesc(
                'is_active'
            )
            ->orderBy(
                'name'
            )
            ->paginate(20)
            ->withQueryString();

        return view(
            'master-fleet.terminals.index',
            compact(
                'terminals',
                'search',
                'status'
            )
        );
    }

    /**
     * Menampilkan form tambah TLPG.
     */
    public function create(): View
    {
        return view(
            'master-fleet.terminals.create',
            [
                'terminal' =>
                    new FleetTerminal([
                        'is_active' => true,
                    ]),
            ]
        );
    }

    /**
     * Menyimpan TLPG baru.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $validated =
            $this->validatedData(
                $request
            );

        try {
            $terminal = DB::transaction(
                fn () =>
                    FleetTerminal::query()
                        ->create(
                            $validated
                        )
            );

            return redirect()
                ->route(
                    'master-fleet.terminals.edit',
                    $terminal
                )
                ->with(
                    'success',
                    'TLPG/Terminal berhasil ditambahkan.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'TLPG/Terminal gagal ditambahkan: '
                    . $e->getMessage()
                );
        }
    }

    /**
     * Menampilkan form edit TLPG.
     */
    public function edit(
        FleetTerminal $terminal
    ): View {
        return view(
            'master-fleet.terminals.edit',
            compact(
                'terminal'
            )
        );
    }

    /**
     * Memperbarui TLPG.
     */
    public function update(
        Request $request,
        FleetTerminal $terminal
    ): RedirectResponse {
        $validated =
            $this->validatedData(
                $request,
                $terminal
            );

        try {
            DB::transaction(
                fn () =>
                    $terminal->update(
                        $validated
                    )
            );

            return redirect()
                ->route(
                    'master-fleet.terminals.edit',
                    $terminal
                )
                ->with(
                    'success',
                    'TLPG/Terminal berhasil diperbarui.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'TLPG/Terminal gagal diperbarui: '
                    . $e->getMessage()
                );
        }
    }

    /**
     * Mengaktifkan atau menonaktifkan TLPG.
     */
    public function toggleActive(
        FleetTerminal $terminal
    ): RedirectResponse {
        if (
            $terminal->is_active
            &&
            (
                $terminal
                    ->companies()
                    ->where(
                        'is_active',
                        true
                    )
                    ->exists()
                ||
                $terminal
                    ->distanceProfiles()
                    ->where(
                        'is_active',
                        true
                    )
                    ->exists()
            )
        ) {
            return back()->with(
                'error',
                'TLPG tidak dapat dinonaktifkan karena masih digunakan SPBE atau profil jarak aktif.'
            );
        }

        $terminal->forceFill([
            'is_active' =>
                !$terminal->is_active,
        ])->save();

        return back()->with(
            'success',
            $terminal->is_active
                ? 'TLPG berhasil diaktifkan.'
                : 'TLPG berhasil dinonaktifkan.'
        );
    }

    /**
     * Validasi dan normalisasi data TLPG.
     */
    private function validatedData(
        Request $request,
        ?FleetTerminal $terminal = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Parsing koordinat
        |--------------------------------------------------------------------------
        |
        | Nilai koordinat berasal dari satu kolom:
        |
        | -7.203807110524606, 112.71950675497641
        |
        | Hasilnya akan dibulatkan menjadi tujuh angka desimal.
        |
        */

        $coordinates =
            CoordinateParser::parse(
                $request->input(
                    'coordinates'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Normalisasi kode dan koordinat
        |--------------------------------------------------------------------------
        */

        $request->merge([
            'code' =>
                $request->filled(
                    'code'
                )
                    ? mb_strtoupper(
                        trim(
                            (string) $request->input(
                                'code'
                            )
                        ),
                        'UTF-8'
                    )
                    : null,

            'latitude' =>
                $coordinates['latitude'],

            'longitude' =>
                $coordinates['longitude'],
        ]);

        $codeUniqueRule =
            Rule::unique(
                'fleet_terminals',
                'code'
            );

        if ($terminal !== null) {
            $codeUniqueRule->ignore(
                $terminal->getKey()
            );
        }

        $validated = $request->validate(
            [
                'code' => [
                    'nullable',
                    'string',
                    'max:50',
                    $codeUniqueRule,
                ],

                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'coordinates' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'latitude' => [
                    'nullable',
                    'numeric',
                    'between:-90,90',
                ],

                'longitude' => [
                    'nullable',
                    'numeric',
                    'between:-180,180',
                ],

                'is_active' => [
                    'required',
                    'boolean',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ],
            [
                'name.required' =>
                    'Nama TLPG/Terminal wajib diisi.',

                'name.max' =>
                    'Nama TLPG/Terminal maksimal 255 karakter.',

                'code.max' =>
                    'Kode TLPG maksimal 50 karakter.',

                'code.unique' =>
                    'Kode TLPG sudah digunakan.',

                'coordinates.max' =>
                    'Nilai koordinat terlalu panjang.',

                'latitude.between' =>
                    'Latitude harus berada antara -90 sampai 90.',

                'longitude.between' =>
                    'Longitude harus berada antara -180 sampai 180.',

                'notes.max' =>
                    'Catatan maksimal 5.000 karakter.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Normalisasi nama TLPG
        |--------------------------------------------------------------------------
        */

        $name = trim(
            (string) preg_replace(
                '/\s+/',
                ' ',
                (string) $validated['name']
            )
        );

        $normalizedName =
            FleetTerminal::normalizeName(
                $name
            );

        $duplicateQuery =
            FleetTerminal::query()
                ->where(
                    'normalized_name',
                    $normalizedName
                );

        if ($terminal !== null) {
            $duplicateQuery->where(
                'id',
                '!=',
                $terminal->getKey()
            );
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'name' =>
                    'Nama TLPG/Terminal sudah digunakan.',
            ]);
        }

        return [
            'code' =>
                $validated['code']
                ?? null,

            'name' =>
                $name,

            'normalized_name' =>
                $normalizedName,

            'latitude' =>
                $coordinates['latitude'],

            'longitude' =>
                $coordinates['longitude'],

            'is_active' =>
                (bool) $validated[
                    'is_active'
                ],

            'notes' =>
                isset(
                    $validated['notes']
                )
                    ? trim(
                        (string) $validated[
                            'notes'
                        ]
                    )
                    : null,
        ];
    }
}