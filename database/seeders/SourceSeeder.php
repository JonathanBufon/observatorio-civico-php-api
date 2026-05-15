<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    // URLs validadas em: 2026-05-15
    public function run(): void
    {
        $sources = [
            ['name' => 'Folha de S.Paulo - Poder', 'url' => 'https://feeds.folha.uol.com.br/poder/rss091.xml', 'editorial_leaning' => 'centro-esquerda'],
            ['name' => 'Estadao - Politica', 'url' => 'https://www.estadao.com.br/arc/outboundfeeds/feeds/rss/sections/politica/', 'editorial_leaning' => 'centro-direita'],
            ['name' => 'G1 - Politica', 'url' => 'https://g1.globo.com/rss/g1/politica/', 'editorial_leaning' => 'centro'],
            ['name' => 'UOL Noticias', 'url' => 'https://rss.uol.com.br/feed/noticias.xml', 'editorial_leaning' => 'centro'],
            ['name' => 'Carta Capital', 'url' => 'https://www.cartacapital.com.br/feed/', 'editorial_leaning' => 'esquerda'],
            ['name' => 'Gazeta do Povo', 'url' => 'https://www.gazetadopovo.com.br/feed/rss/republica.xml', 'editorial_leaning' => 'direita'],
            ['name' => 'Congresso em Foco', 'url' => 'https://congressoemfoco.uol.com.br/feed/', 'editorial_leaning' => 'independente'],
            ['name' => 'Poder360', 'url' => 'https://www.poder360.com.br/feed/', 'editorial_leaning' => 'centro'],
            ['name' => 'Nexo Jornal', 'url' => 'https://www.nexojornal.com.br/rss.xml', 'editorial_leaning' => 'centro-esquerda'],
            ['name' => 'BBC Brasil', 'url' => 'https://feeds.bbci.co.uk/portuguese/rss.xml', 'editorial_leaning' => 'centro-independente'],
        ];

        foreach ($sources as $source) {
            Source::firstOrCreate(['url' => $source['url']], $source);
        }
    }
}
