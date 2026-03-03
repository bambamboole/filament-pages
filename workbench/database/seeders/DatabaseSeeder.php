<?php declare(strict_types=1);
namespace Workbench\Database\Seeders;

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
        ]);

        // ===========================
        // English pages (locale: en)
        // ===========================

        $home = Page::create([
            'title' => 'Home',
            'slug' => '/',
            'locale' => 'en',
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Welcome\n\nWelcome to our website."]]],
            'published_at' => now(),
        ]);

        $about = Page::create([
            'title' => 'About',
            'slug' => 'about',
            'locale' => 'en',
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# About Us\n\nWe build great software."]]],
            'published_at' => now(),
        ]);

        $team = Page::create([
            'title' => 'Team',
            'slug' => 'team',
            'locale' => 'en',
            'parent_id' => $about->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Our Team\n\nMeet the people behind the product."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Leadership',
            'slug' => 'leadership',
            'locale' => 'en',
            'parent_id' => $team->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Leadership\n\nOur executive team."]]],
        ]);

        Page::create([
            'title' => 'Engineering',
            'slug' => 'engineering',
            'locale' => 'en',
            'parent_id' => $team->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Engineering\n\nOur engineering team."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Careers',
            'slug' => 'careers',
            'locale' => 'en',
            'parent_id' => $about->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Careers\n\nJoin our growing team."]]],
        ]);

        $services = Page::create([
            'title' => 'Services',
            'slug' => 'services',
            'locale' => 'en',
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Services\n\nWhat we offer."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Consulting',
            'slug' => 'consulting',
            'locale' => 'en',
            'parent_id' => $services->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Consulting\n\nExpert advice for your projects."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Development',
            'slug' => 'development',
            'locale' => 'en',
            'parent_id' => $services->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Development\n\nCustom software development."]]],
        ]);

        Page::create([
            'title' => 'Contact',
            'slug' => 'contact',
            'locale' => 'en',
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Contact\n\nGet in touch with us."]]],
            'published_at' => now(),
        ]);

        $legal = Page::create([
            'title' => 'Legal',
            'slug' => 'legal',
            'locale' => 'en',
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Legal\n\nLegal information."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy',
            'locale' => 'en',
            'parent_id' => $legal->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Privacy Policy\n\nYour privacy matters to us."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Terms of Service',
            'slug' => 'terms',
            'locale' => 'en',
            'parent_id' => $legal->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Terms of Service\n\nRules and regulations."]]],
            'published_at' => now(),
        ]);

        // ===========================
        // German pages (locale: de)
        // ===========================

        Page::create([
            'title' => 'Startseite',
            'slug' => '/',
            'locale' => 'de',
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Willkommen\n\nWillkommen auf unserer Webseite."]]],
            'published_at' => now(),
        ]);

        $ueberUns = Page::create([
            'title' => 'Über uns',
            'slug' => 'ueber-uns',
            'locale' => 'de',
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Über uns\n\nWir entwickeln großartige Software."]]],
            'published_at' => now(),
        ]);

        $teamDe = Page::create([
            'title' => 'Team',
            'slug' => 'team',
            'locale' => 'de',
            'parent_id' => $ueberUns->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Unser Team\n\nLernen Sie die Menschen hinter dem Produkt kennen."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Führungsteam',
            'slug' => 'fuehrungsteam',
            'locale' => 'de',
            'parent_id' => $teamDe->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Führungsteam\n\nUnser Führungsteam."]]],
        ]);

        Page::create([
            'title' => 'Karriere',
            'slug' => 'karriere',
            'locale' => 'de',
            'parent_id' => $ueberUns->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Karriere\n\nWerden Sie Teil unseres Teams."]]],
        ]);

        $leistungen = Page::create([
            'title' => 'Leistungen',
            'slug' => 'leistungen',
            'locale' => 'de',
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Leistungen\n\nWas wir anbieten."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Beratung',
            'slug' => 'beratung',
            'locale' => 'de',
            'parent_id' => $leistungen->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Beratung\n\nKompetente Beratung für Ihre Projekte."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Entwicklung',
            'slug' => 'entwicklung',
            'locale' => 'de',
            'parent_id' => $leistungen->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Entwicklung\n\nIndividuelle Softwareentwicklung."]]],
        ]);

        Page::create([
            'title' => 'Kontakt',
            'slug' => 'kontakt',
            'locale' => 'de',
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Kontakt\n\nNehmen Sie Kontakt mit uns auf."]]],
            'published_at' => now(),
        ]);

        $rechtliches = Page::create([
            'title' => 'Rechtliches',
            'slug' => 'rechtliches',
            'locale' => 'de',
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Rechtliches\n\nRechtliche Informationen."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Datenschutz',
            'slug' => 'datenschutz',
            'locale' => 'de',
            'parent_id' => $rechtliches->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Datenschutz\n\nIhre Privatsphäre ist uns wichtig."]]],
            'published_at' => now(),
        ]);

        Page::create([
            'title' => 'Impressum',
            'slug' => 'impressum',
            'locale' => 'de',
            'parent_id' => $rechtliches->id,
            'blocks' => [['type' => 'markdown', 'data' => ['content' => "# Impressum\n\nAngaben gemäß § 5 TMG."]]],
            'published_at' => now(),
        ]);
    }
}
