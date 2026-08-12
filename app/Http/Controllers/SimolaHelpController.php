<?php

namespace App\Http\Controllers;

use App\Models\SimolaHelpArticle;
use App\Services\SimolaHelp\SimolaHelpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimolaHelpController extends Controller
{
    public function ask(
        Request $request,
        SimolaHelpService $service
    ): JsonResponse {
        abort_unless(
            (bool) config('simola-help.enabled', true),
            404
        );

        $validated = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:1200'],
            'page_title' => ['nullable', 'string', 'max:200'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'history' => ['nullable', 'array', 'max:20'],
            'history.*.role' => [
                'required_with:history',
                'string',
                'in:user,assistant',
            ],
            'history.*.content' => [
                'required_with:history',
                'string',
                'max:1800',
            ],
        ]);

        $question = trim($validated['question']);
        $pageTitle = trim((string) ($validated['page_title'] ?? ''));
        $pageUrl = trim((string) ($validated['page_url'] ?? ''));

        $history = collect($validated['history'] ?? [])
            ->take(-16)
            ->map(fn (array $item) => [
                'role' => $item['role'],
                'content' => trim($item['content']),
            ])
            ->filter(
                fn (array $item) =>
                    $item['content'] !== ''
            )
            ->values()
            ->all();

        return response()->json(
            $service->answer(
                $this->normalizeQuestion($question),
                $history,
                $pageTitle !== '' ? $pageTitle : null,
                $pageUrl !== '' ? $pageUrl : null,
                $this->questionUsesPageContext($question)
            )
        );
    }

    public function adminIndex(): View
    {
        return view(
            'developer.simola-help.index',
            [
                'articles' => SimolaHelpArticle::query()
                    ->orderBy('sort_order')
                    ->orderBy('title')
                    ->get(),
            ]
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateArticle($request);
        $validated['created_by'] = $request->user()?->id;
        $validated['updated_by'] = $request->user()?->id;

        SimolaHelpArticle::query()->create($validated);

        return back()->with(
            'status',
            'Artikel Help Center berhasil ditambahkan.'
        );
    }

    public function update(
        Request $request,
        SimolaHelpArticle $article
    ): RedirectResponse {
        $validated = $this->validateArticle($request);
        $validated['updated_by'] = $request->user()?->id;

        $article->update($validated);

        return back()->with(
            'status',
            'Artikel Help Center berhasil diperbarui.'
        );
    }

    public function destroy(
        SimolaHelpArticle $article
    ): RedirectResponse {
        $article->delete();

        return back()->with(
            'status',
            'Artikel Help Center dihapus.'
        );
    }

    private function questionUsesPageContext(string $question): bool
    {
        $query = mb_strtolower(
            trim(
                preg_replace('/\s+/u', ' ', $question) ?? $question
            )
        );

        return preg_match(
            '/\b(ini|halaman ini|fitur ini|yang ini|disini|di sini|bagian ini|menu ini|halaman sekarang|yang saya buka)\b/u',
            $query
        ) === 1;
    }

    private function normalizeQuestion(string $question): string
    {
        $result = $question;

        foreach ([
            '/\bcroscheck\b/iu' => 'crosscheck',
            '/\berorlog\b/iu' => 'errorlog',
            '/\berror log\b/iu' => 'errorlog',
            '/\bk32\b/iu' => 'k3.2',
            '/\bunggah\b/iu' => 'upload',
        ] as $pattern => $replacement) {
            $result = preg_replace(
                $pattern,
                $replacement,
                $result
            ) ?? $result;
        }

        return trim($result);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateArticle(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'module' => ['required', 'string', 'max:100'],
            'keywords_text' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string', 'max:12000'],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $keywords = collect(
            preg_split(
                '/[,;\n]+/u',
                (string) ($validated['keywords_text'] ?? '')
            ) ?: []
        )
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        unset($validated['keywords_text']);

        $validated['keywords'] = $keywords;
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 100);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
