<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cms\HubCard;

class HubCardSeeder extends Seeder
{

    public function run(): void
    {
        $cards = [
            [
                'label' => 'Research Hub',
                'subtitle' => 'Academic & Scientific',
                'description' => 'Publish, explore, and collaborate on groundbreaking academic research with peers worldwide.',
                'tag' => 'RESEARCH',
                'image' => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=800&q=80',
                'route' => '/researchhub',
                'order_index' => 1,
            ],
            [
                'label' => 'Innovation Hub',
                'subtitle' => 'Ideas & Invention',
                'description' => 'Turn bold ideas into real-world inventions. Connect with mentors and patent your innovations.',
                'tag' => 'INNOVATION',
                'image' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&q=80',
                'route' => '/innovationhub',
                'order_index' => 2,
            ],
            [
                'label' => 'Investor Zone',
                'subtitle' => 'Funding & Growth',
                'description' => 'Discover high-potential startups and innovations ready for strategic investment and growth.',
                'tag' => 'INVEST',
                'image' => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800&q=80',
                'route' => '/investorzone',
                'order_index' => 3,
            ],
            [
                'label' => 'Community',
                'subtitle' => 'Network & Connect',
                'description' => 'Join a thriving network of students, researchers, and industry professionals driving change.',
                'tag' => 'COMMUNITY',
                'image' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=800&q=80',
                'route' => '/community',
                'order_index' => 4,
            ],
            [
                'label' => 'Careers',
                'subtitle' => 'Jobs & Opportunities',
                'description' => 'Find opportunities that align with your skills and ambitions across top innovative companies.',
                'tag' => 'CAREERS',
                'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=800&q=80',
                'route' => '/careers',
                'order_index' => 5,
            ],
        ];

        foreach ($cards as $card) {
            HubCard::updateOrCreate(['label' => $card['label']], $card);
        }
    }
}
