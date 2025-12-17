<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeed extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Business Consultation',
                'icon' => 'fas fa-briefcase',
                'slug' => 'business-consultation',
                'description' => 'Professional business consulting services for startups and established businesses.',
                'features' => [
                    'Personalized business strategy',
                    'Market analysis and research',
                    'Financial planning assistance',
                    'Growth optimization techniques',
                    'Monthly progress reviews'
                ]
            ],
            [
                'name' => 'Logistics Support',
                'icon' => 'fas fa-shipping-fast',
                'slug' => 'logistics-support',
                'description' => 'Comprehensive logistics and supply chain management solutions.',
                'features' => [
                    'International shipping coordination',
                    'Customs clearance assistance',
                    'Real-time tracking system',
                    'Warehouse management',
                    'Insurance coverage options'
                ]
            ],
            [
                'name' => 'Digital Marketing',
                'icon' => 'fas fa-chart-line',
                'slug' => 'digital-marketing',
                'description' => 'Boost your online presence with our digital marketing services.',
                'features' => [
                    'SEO optimization',
                    'Social media management',
                    'Email marketing campaigns',
                    'Content creation',
                    'Analytics and reporting'
                ]
            ],
            [
                'name' => 'Payment Processing',
                'icon' => 'fas fa-credit-card',
                'slug' => 'payment-processing',
                'description' => 'Secure and efficient payment processing solutions for your business.',
                'features' => [
                    'Multi-currency support',
                    'Secure payment gateway',
                    'Mobile payment options',
                    'Fraud protection',
                    '24/7 transaction monitoring'
                ]
            ],
            [
                'name' => 'Technical Support',
                'icon' => 'fas fa-headset',
                'slug' => 'technical-support',
                'description' => 'Round-the-clock technical support for all your business needs.',
                'features' => [
                    '24/7 support availability',
                    'Multi-language support team',
                    'Remote assistance',
                    'On-site support options',
                    'Regular system maintenance'
                ]
            ]
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}