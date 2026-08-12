<?php

namespace App\Services\SimolaHelp;

use App\Models\SimolaHelpArticle;
use App\Support\SimolaHelpDefaults;
use Illuminate\Support\Collection;
use Throwable;

class SimolaHelpService
{
    private const KB_MIN_SCORE = 8;

    public function __construct(
        private OpenAiSimolaHelpService $ai
    ) {
    }

    public function aiAvailable(): bool
    {
        return $this->ai->enabled();
    }

    /**
     * @param array<int, array{role:string,content:string}> $history
     * @return array<string, mixed>
     */
    public function answer(
        string $question,
        array $history = [],
        ?string $pageTitle = null,
        ?string $pageUrl = null,
        bool $usePageContext = false
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Retrieval hanya menjadi referensi tambahan
        |--------------------------------------------------------------------------
        |
        | Jangan memaksa artikel FAQ ke setiap pertanyaan. Hanya artikel dengan
        | skor relevansi kuat yang dikirim ke AI.
        |
        */
        $ranked = $this->rankArticles(
            $question,
            $usePageContext ? $pageTitle : null,
            $usePageContext ? $pageUrl : null
        );

        $relevant = $ranked
            ->filter(
                fn (array $article) =>
                    ((int) ($article['_score'] ?? 0)) >= self::KB_MIN_SCORE
            )
            ->take((int) config('simola-help.max_context_articles', 4))
            ->values();

        /*
        |--------------------------------------------------------------------------
        | AI selalu menjadi conversational brain jika tersedia
        |--------------------------------------------------------------------------
        */
        if ($this->ai->enabled()) {
            try {
                $answer = $this->ai->answer(
                    $question,
                    $relevant->all(),
                    $history,
                    $usePageContext ? $pageTitle : null,
                    $usePageContext ? $pageUrl : null
                );

                return [
                    'answer' => $answer,
                    'source' => 'ai',
                    'source_label' => $relevant->isNotEmpty()
                        ? 'AI + Panduan SIMOLA'
                        : 'AI SIMOLA',
                    'articles' => $relevant
                        ->pluck('title')
                        ->values()
                        ->all(),
                    'ai_available' => true,
                ];
            } catch (Throwable $e) {
                report($e);

                /*
                 * Jangan sembunyikan total bahwa AI gagal. Tetap beri fallback
                 * lokal bila ada artikel kuat, lalu tandai AI tidak tersedia.
                 */
                if ($relevant->isNotEmpty()) {
                    $best = $relevant->first();

                    return [
                        'answer' => trim(
                            (string) ($best['content'] ?? '')
                        ),
                        'source' => 'local_after_ai_error',
                        'source_label' => 'Panduan SIMOLA · AI sedang tidak tersedia',
                        'articles' => [$best['title']],
                        'ai_available' => false,
                    ];
                }

                return [
                    'answer' => 'AI Help Assistant sedang tidak dapat terhubung ke provider. Coba lagi sebentar. Jika pertanyaan Anda terkait fitur SIMOLA, Anda juga bisa menyebut nama modulnya agar panduan lokal dapat membantu.',
                    'source' => 'ai_error',
                    'source_label' => 'AI tidak tersambung',
                    'articles' => [],
                    'ai_available' => false,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Offline FAQ fallback
        |--------------------------------------------------------------------------
        */
        if ($relevant->isNotEmpty()) {
            $best = $relevant->first();

            return [
                'answer' => trim(
                    (string) ($best['content'] ?? '')
                ),
                'source' => 'local',
                'source_label' => 'Panduan SIMOLA',
                'articles' => [$best['title']],
                'ai_available' => false,
            ];
        }

        return [
            'answer' => 'AI Help Assistant belum aktif. Untuk sementara, sebutkan nama fitur SIMOLA seperti Upload Terpadu, Crosscheck K3.2, Errorlog, Master Fleet, atau Draft Grouping agar saya dapat mencari panduan lokal.',
            'source' => 'offline',
            'source_label' => 'FAQ Lokal',
            'articles' => [],
            'ai_available' => false,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rankArticles(
        string $question,
        ?string $pageTitle,
        ?string $pageUrl
    ): Collection {
        $query = $this->normalize($question);
        $searchText = $question;

        if (filled($pageTitle) || filled($pageUrl)) {
            $searchText .= ' ' . (string) $pageTitle . ' ' . (string) $pageUrl;
        }

        $tokens = $this->tokens($searchText);

        return collect($this->articles())
            ->map(function (array $article) use ($query, $tokens): array {
                $title = $this->normalize(
                    (string) ($article['title'] ?? '')
                );

                $module = $this->normalize(
                    (string) ($article['module'] ?? '')
                );

                $content = $this->normalize(
                    (string) ($article['content'] ?? '')
                );

                $keywords = collect(
                    (array) ($article['keywords'] ?? [])
                )
                    ->map(
                        fn ($value) =>
                            $this->normalize((string) $value)
                    )
                    ->filter()
                    ->values()
                    ->all();

                $score = 0;

                foreach ($tokens as $token) {
                    if (str_contains($title, $token)) {
                        $score += 5;
                    }

                    if (str_contains($module, $token)) {
                        $score += 4;
                    }

                    foreach ($keywords as $keyword) {
                        if (
                            $keyword === $token
                            || str_contains($keyword, $token)
                            || str_contains($token, $keyword)
                        ) {
                            $score += 5;
                        }
                    }

                    if (str_contains($content, $token)) {
                        $score += 1;
                    }
                }

                foreach ($keywords as $keyword) {
                    if (
                        mb_strlen($keyword) >= 5
                        && str_contains($query, $keyword)
                    ) {
                        $score += 7;
                    }
                }

                if (
                    $title !== ''
                    && str_contains($query, $title)
                ) {
                    $score += 10;
                }

                $article['_score'] = $score;

                return $article;
            })
            ->sortByDesc('_score')
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function articles(): array
    {
        try {
            $rows = SimolaHelpArticle::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();

            if ($rows->isNotEmpty()) {
                return $rows
                    ->map(
                        fn (SimolaHelpArticle $article) => [
                            'id' => $article->id,
                            'title' => $article->title,
                            'module' => $article->module,
                            'keywords' => $article->keywords ?: [],
                            'content' => $article->content,
                            'sort_order' => $article->sort_order,
                        ]
                    )
                    ->all();
            }
        } catch (Throwable $e) {
            report($e);
        }

        return SimolaHelpDefaults::articles();
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $value): array
    {
        $stop = [
            'yang', 'dan', 'atau', 'untuk', 'dari', 'dengan', 'pada',
            'saya', 'cara', 'bagaimana', 'gimana', 'kenapa', 'mengapa',
            'bisa', 'tolong', 'mohon', 'apa', 'dimana', 'mana', 'di',
            'ke', 'ada', 'ini', 'itu', 'apakah', 'melakukan', 'mau',
            'ingin', 'dong', 'boleh', 'bantu', 'halo', 'hai', 'hi',
            'hello', 'permisi', 'kamu', 'anda', 'aku', 'sih', 'ya',
            'yaa', 'nih', 'min', 'dong',
        ];

        $parts = preg_split(
            '/[^a-z0-9\.\-]+/u',
            $this->normalize($value)
        ) ?: [];

        return collect($parts)
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => mb_strlen($token) >= 2)
            ->reject(
                fn ($token) =>
                    in_array($token, $stop, true)
            )
            ->unique()
            ->values()
            ->all();
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(
            trim(
                preg_replace('/\s+/u', ' ', $value) ?? $value
            )
        );
    }
}
