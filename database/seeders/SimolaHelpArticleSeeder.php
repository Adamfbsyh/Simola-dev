<?php

namespace Database\Seeders;

use App\Support\SimolaHelpDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimolaHelpArticleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SimolaHelpDefaults::articles() as $article) {
            DB::table('simola_help_articles')->updateOrInsert(
                [
                    'title' => $article['title'],
                ],
                [
                    'module' => $article['module'],
                    'keywords' => json_encode(
                        $article['keywords'],
                        JSON_UNESCAPED_UNICODE
                    ),
                    'content' => $article['content'],
                    'is_active' => true,
                    'sort_order' => $article['sort_order'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
