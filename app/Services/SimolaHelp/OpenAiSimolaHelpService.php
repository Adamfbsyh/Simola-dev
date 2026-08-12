<?php

namespace App\Services\SimolaHelp;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiSimolaHelpService
{
    public function enabled(): bool
    {
        return (bool) config('simola-help.ai.enabled')
            && filled((string) config('simola-help.ai.api_key'))
            && filled((string) config('simola-help.ai.base_url'));
    }

    /**
     * @param array<int, array<string, mixed>> $articles
     * @param array<int, array{role:string,content:string}> $history
     */
    public function answer(
        string $question,
        array $articles,
        array $history,
        ?string $pageTitle = null,
        ?string $pageUrl = null
    ): string {
        if (!$this->enabled()) {
            throw new RuntimeException(
                'AI provider belum dikonfigurasi.'
            );
        }

        $messages = $this->messages(
            $question,
            $articles,
            $history,
            $pageTitle,
            $pageUrl
        );

        $endpoint = mb_strtolower(
            trim(
                (string) config(
                    'simola-help.ai.endpoint',
                    'auto'
                )
            )
        );

        if (
            in_array(
                $endpoint,
                ['chat', 'chat/completions', '/chat/completions'],
                true
            )
        ) {
            return $this->chatCompletions($messages);
        }

        if (
            in_array(
                $endpoint,
                ['responses', '/responses'],
                true
            )
        ) {
            return $this->responses($messages);
        }

        try {
            return $this->chatCompletions($messages);
        } catch (ProviderEndpointNotFound $e) {
            return $this->responses($messages);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $articles
     * @param array<int, array{role:string,content:string}> $history
     * @return array<int, array{role:string,content:string}>
     */
    private function messages(
        string $question,
        array $articles,
        array $history,
        ?string $pageTitle,
        ?string $pageUrl
    ): array {
        $knowledge = collect($articles)
            ->map(function (array $article): string {
                return sprintf(
                    "### %s [%s]\n%s",
                    $article['title'] ?? 'Panduan',
                    $article['module'] ?? 'SIMOLA',
                    $article['content'] ?? ''
                );
            })
            ->implode("\n\n");

        $system = <<<'TEXT'
Anda adalah SIMOLA Help Assistant. Bersikaplah seperti asisten percakapan yang natural, kontekstual, dan enak diajak diskusi.

PRINSIP UTAMA:
1. Selalu pahami dan jawab pertanyaan terbaru pengguna terlebih dahulu. Jangan memaksakan topik FAQ jika tidak relevan.
2. Gunakan riwayat percakapan untuk memahami follow-up. Contoh: jika sebelumnya membahas jumlah PC lalu pengguna berkata "kalau saya tambah 2 gimana?", pahami bahwa "2" merujuk pada PC.
3. Jika Knowledge Base SIMOLA diberikan, gunakan sebagai sumber fakta untuk fitur/prosedur SIMOLA. Ambil hanya bagian yang relevan.
4. Jika tidak ada Knowledge Base yang relevan, tetap jawab secara natural menggunakan kemampuan model. Jangan mengatakan "panduan tidak tersedia" hanya karena FAQ tidak cocok.
5. Pengguna boleh berdiskusi di luar FAQ, termasuk meminta penjelasan, ide, analisis, penyusunan kalimat, atau pertanyaan umum. Jawab sesuai pertanyaannya.
6. Untuk fakta spesifik tentang SIMOLA yang tidak tersedia dalam Knowledge Base atau konteks percakapan, jangan mengarang. Katakan bagian yang belum diketahui dan tanyakan satu klarifikasi bila perlu.
7. Jawaban umumnya ringkas dan langsung. Kalau pengguna meminta penjelasan detail, baru jelaskan lebih lengkap.
8. Gunakan Bahasa Indonesia natural. Ikuti gaya bahasa pengguna tanpa menjadi terlalu formal atau terlalu kaku.
9. Jangan mengulang pertanyaan pengguna kecuali membantu memperjelas jawaban.

KEAMANAN SIMOLA:
- Anda read-only. Jangan mengklaim telah mengubah, menghapus, upload, sinkron, publish, grouping, atau menjalankan aksi di SIMOLA.
- Anda boleh menjelaskan langkah yang dapat dilakukan pengguna.
- Jangan meminta atau menampilkan password, API key, access token, refresh token, cookie, client secret, OTP, atau kredensial.
- Jika tindakan bergantung pada role/permission, jelaskan bahwa hal itu tergantung hak akses akun.

CONTOH PERILAKU:
Pengguna: "hai bisa bantu saya?"
Jawab natural seperti: "Tentu 😊 Mau dibantu soal apa?"

Pengguna: "saya mau edit data di simola"
Jawab inti pertanyaannya dan minta konteks data/modul bila belum jelas.

Pengguna: "menurut kamu pembagian 333 kendaraan ke 12 PC gimana?"
Boleh menganalisis pertanyaannya. Jika ada Knowledge Base Draft Grouping, gunakan sebagai konteks tambahan, bukan menyalinnya mentah.

Pengguna: "kalau ditambah 2 pc?"
Gunakan percakapan sebelumnya dan pahami bahwa pengguna sedang melanjutkan diskusi pembagian PC.

Pengguna: "buatkan kalimat untuk saya laporkan ke atasan"
Bantu menulis kalimat tersebut walaupun bukan FAQ.

Pengguna: "kenapa langit biru?"
Jawab pertanyaan umum tersebut secara normal. Jangan memaksakan pembahasan SIMOLA.
TEXT;

        $messages = [
            [
                'role' => 'system',
                'content' => $system,
            ],
        ];

        if ($knowledge !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => "Knowledge Base SIMOLA yang relevan untuk percakapan ini:\n\n"
                    . $knowledge,
            ];
        }

        if (filled($pageTitle) || filled($pageUrl)) {
            $messages[] = [
                'role' => 'system',
                'content' => "Konteks halaman yang secara eksplisit dirujuk pengguna:\n"
                    . 'Judul: ' . ($pageTitle ?: '-')
                    . "\nPath: " . ($pageUrl ?: '-')
                    . "\nGunakan hanya bila memang membantu menjawab pertanyaan.",
            ];
        }

        foreach (
            collect($history)
                ->take(-16)
                ->all()
            as $message
        ) {
            $role = $message['role'] ?? null;
            $content = trim(
                (string) ($message['content'] ?? '')
            );

            if (
                !in_array(
                    $role,
                    ['user', 'assistant'],
                    true
                )
                || $content === ''
            ) {
                continue;
            }

            $messages[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        return $messages;
    }

    /**
     * @param array<int, array{role:string,content:string}> $messages
     */
    private function chatCompletions(array $messages): string
    {
        $response = $this->request()->post(
            $this->url('chat/completions'),
            [
                'model' => $this->model(),
                'messages' => $messages,
                'max_tokens' => $this->maxTokens(),
                'temperature' => 0.55,
                'stream' => false,
            ]
        );

        $this->ensureSuccessful(
            $response,
            'chat/completions'
        );

        $content = $response->json(
            'choices.0.message.content'
        );

        if (
            is_string($content)
            && trim($content) !== ''
        ) {
            return trim($content);
        }

        if (is_array($content)) {
            $answer = collect($content)
                ->map(function ($part): string {
                    if (is_string($part)) {
                        return trim($part);
                    }

                    if (is_array($part)) {
                        return trim(
                            (string) (
                                $part['text']
                                ?? $part['content']
                                ?? ''
                            )
                        );
                    }

                    return '';
                })
                ->filter()
                ->implode("\n");

            if ($answer !== '') {
                return $answer;
            }
        }

        throw new RuntimeException(
            'Provider chat/completions tidak mengembalikan jawaban teks.'
        );
    }

    /**
     * @param array<int, array{role:string,content:string}> $messages
     */
    private function responses(array $messages): string
    {
        $instructions = collect($messages)
            ->where('role', 'system')
            ->pluck('content')
            ->implode("\n\n");

        $input = collect($messages)
            ->reject(
                fn (array $message) =>
                    ($message['role'] ?? null) === 'system'
            )
            ->values()
            ->all();

        $response = $this->request()->post(
            $this->url('responses'),
            [
                'model' => $this->model(),
                'instructions' => $instructions,
                'input' => $input,
                'max_output_tokens' => $this->maxTokens(),
                'store' => false,
            ]
        );

        $this->ensureSuccessful(
            $response,
            'responses'
        );

        $parts = [];

        foreach (
            (array) ($response->json('output') ?? [])
            as $item
        ) {
            if (
                ($item['type'] ?? null)
                !== 'message'
            ) {
                continue;
            }

            foreach (
                (array) ($item['content'] ?? [])
                as $content
            ) {
                if (
                    ($content['type'] ?? null)
                    === 'output_text'
                    && filled(
                        $content['text'] ?? null
                    )
                ) {
                    $parts[] = trim(
                        (string) $content['text']
                    );
                }
            }
        }

        $answer = trim(
            implode("\n", $parts)
        );

        if ($answer === '') {
            throw new RuntimeException(
                'Provider responses tidak mengembalikan jawaban teks.'
            );
        }

        return $answer;
    }

    private function request()
    {
        return Http::withToken(
            (string) config(
                'simola-help.ai.api_key'
            )
        )
            ->acceptJson()
            ->asJson()
            ->timeout(
                max(
                    10,
                    (int) config(
                        'simola-help.ai.timeout',
                        35
                    )
                )
            );
    }

    private function url(string $endpoint): string
    {
        return rtrim(
            (string) config(
                'simola-help.ai.base_url'
            ),
            '/'
        )
            . '/'
            . ltrim($endpoint, '/');
    }

    private function model(): string
    {
        return trim(
            (string) config(
                'simola-help.ai.model'
            )
        );
    }

    private function maxTokens(): int
    {
        return max(
            256,
            (int) config(
                'simola-help.ai.max_output_tokens',
                650
            )
        );
    }

    private function ensureSuccessful(
        Response $response,
        string $endpoint
    ): void {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();

        $message = trim(
            (string) data_get(
                $response->json(),
                'error.message',
                data_get(
                    $response->json(),
                    'message',
                    $response->body()
                )
            )
        );

        if (
            in_array(
                $status,
                [404, 405],
                true
            )
            ||
            (
                $status === 400
                &&
                preg_match(
                    '/(route|endpoint|path).*(not found|unknown|unsupported)|not found.*(route|endpoint|path)/i',
                    $message
                ) === 1
            )
        ) {
            throw new ProviderEndpointNotFound(
                $endpoint . ': '
                . (
                    $message !== ''
                        ? $message
                        : "HTTP {$status}"
                )
            );
        }

        throw new RuntimeException(
            sprintf(
                'AI provider gagal [%s HTTP %d]: %s',
                $endpoint,
                $status,
                $message !== ''
                    ? $message
                    : 'Tidak ada pesan error.'
            )
        );
    }
}

class ProviderEndpointNotFound extends RuntimeException
{
}
