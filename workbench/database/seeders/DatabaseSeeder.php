<?php

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

        // --- Home ---
        Page::create([
            'title' => 'Home',
            'slug' => 'home',
            'content' => "# Welcome\n\nWelcome to our website.",
        ]);

        // --- About (with children & grandchildren) ---
        $about = Page::create([
            'title' => 'About',
            'slug' => 'about',
            'content' => "# About Us\n\nWe build great software.",
        ]);

        $team = Page::create([
            'title' => 'Team',
            'slug' => 'team',
            'parent_id' => $about->id,
            'content' => "# Our Team\n\nMeet the people behind the product.",
        ]);

        Page::create([
            'title' => 'Leadership',
            'slug' => 'leadership',
            'parent_id' => $team->id,
            'content' => "# Leadership\n\nOur executive team.",
        ]);

        Page::create([
            'title' => 'Engineering',
            'slug' => 'engineering',
            'parent_id' => $team->id,
            'content' => "# Engineering\n\nOur engineering team.",
        ]);

        Page::create([
            'title' => 'Careers',
            'slug' => 'careers',
            'parent_id' => $about->id,
            'content' => "# Careers\n\nJoin our growing team.",
        ]);

        Page::create([
            'title' => 'History',
            'slug' => 'history',
            'parent_id' => $about->id,
            'content' => "# Our History\n\nFounded in 2020.",
        ]);

        Page::create([
            'title' => 'Mission & Values',
            'slug' => 'mission-values',
            'parent_id' => $about->id,
            'content' => "# Mission & Values\n\nWhat drives us forward.",
        ]);

        // --- Services (with children) ---
        $services = Page::create([
            'title' => 'Services',
            'slug' => 'services',
            'content' => "# Services\n\nWhat we offer.",
        ]);

        Page::create([
            'title' => 'Consulting',
            'slug' => 'consulting',
            'parent_id' => $services->id,
            'content' => "# Consulting\n\nExpert advice for your projects.",
        ]);

        Page::create([
            'title' => 'Development',
            'slug' => 'development',
            'parent_id' => $services->id,
            'content' => "# Development\n\nCustom software development.",
        ]);

        Page::create([
            'title' => 'Design',
            'slug' => 'design',
            'parent_id' => $services->id,
            'content' => "# Design\n\nUI/UX and brand design.",
        ]);

        Page::create([
            'title' => 'Hosting & Infrastructure',
            'slug' => 'hosting',
            'parent_id' => $services->id,
            'content' => "# Hosting\n\nReliable cloud hosting.",
        ]);

        // --- Products (with children & grandchildren) ---
        $products = Page::create([
            'title' => 'Products',
            'slug' => 'products',
            'content' => "# Products\n\nOur product lineup.",
        ]);

        $openSource = Page::create([
            'title' => 'Open Source',
            'slug' => 'open-source',
            'parent_id' => $products->id,
            'content' => "# Open Source\n\nOur contributions to the community.",
        ]);

        Page::create([
            'title' => 'Filament Pages',
            'slug' => 'filament-pages',
            'parent_id' => $openSource->id,
            'content' => "# Filament Pages\n\nA page tree plugin for Filament.",
        ]);

        Page::create([
            'title' => 'Filament Menu',
            'slug' => 'filament-menu',
            'parent_id' => $openSource->id,
            'content' => "# Filament Menu\n\nA menu builder for Filament.",
        ]);

        Page::create([
            'title' => 'Enterprise',
            'slug' => 'enterprise',
            'parent_id' => $products->id,
            'content' => "# Enterprise\n\nSolutions for large organizations.",
        ]);

        Page::create([
            'title' => 'Pricing',
            'slug' => 'pricing',
            'parent_id' => $products->id,
            'content' => "# Pricing\n\nTransparent pricing for every plan.",
        ]);

        // --- Resources (with children) ---
        $resources = Page::create([
            'title' => 'Resources',
            'slug' => 'resources',
            'content' => "# Resources\n\nLearn and explore.",
        ]);

        Page::create([
            'title' => 'Blog',
            'slug' => 'blog',
            'parent_id' => $resources->id,
            'content' => "# Blog\n\nNews and insights.",
        ]);

        Page::create([
            'title' => 'Documentation',
            'slug' => 'docs',
            'parent_id' => $resources->id,
            'content' => "# Documentation\n\nGuides and API reference.",
        ]);

        Page::create([
            'title' => 'Tutorials',
            'slug' => 'tutorials',
            'parent_id' => $resources->id,
            'content' => "# Tutorials\n\nStep-by-step learning.",
        ]);

        Page::create([
            'title' => 'FAQ',
            'slug' => 'faq',
            'parent_id' => $resources->id,
            'content' => "# FAQ\n\nFrequently asked questions.",
        ]);

        // --- Contact ---
        Page::create([
            'title' => 'Contact',
            'slug' => 'contact',
            'content' => "# Contact\n\nGet in touch with us.",
        ]);

        // --- Legal (with children) ---
        $legal = Page::create([
            'title' => 'Legal',
            'slug' => 'legal',
            'content' => "# Legal\n\nLegal information.",
        ]);

        Page::create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy',
            'parent_id' => $legal->id,
            'content' => "# Privacy Policy\n\nYour privacy matters to us.",
        ]);

        Page::create([
            'title' => 'Terms of Service',
            'slug' => 'terms',
            'parent_id' => $legal->id,
            'content' => "# Terms of Service\n\nRules and regulations.",
        ]);

        Page::create([
            'title' => 'Imprint',
            'slug' => 'imprint',
            'parent_id' => $legal->id,
            'content' => "# Imprint\n\nLegal disclosure.",
        ]);

        Page::create([
            'title' => 'Cookie Policy',
            'slug' => 'cookies',
            'parent_id' => $legal->id,
            'content' => "# Cookie Policy\n\nHow we use cookies.",
        ]);
    }
}
