<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Delete all existing testimonials (including soft-deleted ones)
        Testimonial::withTrashed()->forceDelete();

        // 2. Define the new real IELTS testimonials scraped from https://ktmeducational.edu.np/student-testimonials/
        $testimonials = [
            [
                'name' => 'Amardeep Upadhyaya',
                'initials' => 'AU',
                'meta' => 'IELTS Student',
                'tag' => 'IELTS',
                'country' => 'Nepal',
                'quote' => 'It was a very good time for me to be here at KTM Educational Consultancy for a couple of months. Luckily I went to this institution where I found cooperative members and versatile instructors. The technology here made our learning environment worth it. Honestly speaking, this institution had been the best platform for my further day education as it gave me the best guidelines.',
                'cats' => ['ielts'],
                'is_featured' => true,
                'sort_order' => 1,
                'photo_url' => 'https://ktmeducational.edu.np/media/ktm_edu/other_dir/images/amardeep_dhital1.original.jpg',
            ],
            [
                'name' => 'Anamika Gurung',
                'initials' => 'AG',
                'meta' => 'IELTS Student',
                'tag' => 'IELTS',
                'country' => 'Nepal',
                'quote' => 'KTM Educational Consultancy is the consultancy where I have been studying IELTS. I found this consultancy cheaper and better also. It has also a good environment. I also recommend other people to take IELTS or other classes.',
                'cats' => ['ielts'],
                'is_featured' => true,
                'sort_order' => 2,
                'photo_url' => 'https://ktmeducational.edu.np/media/ktm_edu/other_dir/images/anamika_gurung1.original.jpg',
            ],
            [
                'name' => 'Anisha Bala',
                'initials' => 'AB',
                'meta' => 'IELTS Student',
                'tag' => 'IELTS',
                'country' => 'Nepal',
                'quote' => 'I am glad with my decision to join the class in KTM Educational Consultancy it’s been a wonderful experience and very fruitful for my career in abroad. Weekly test of IELTS class helps me a lot to be prepared for the IELTS test. The teacher and environment here are very friendly, and we all students get a homely environment to learn.',
                'cats' => ['ielts'],
                'is_featured' => true,
                'sort_order' => 3,
                'photo_url' => 'https://ktmeducational.edu.np/media/ktm_edu/other_dir/images/Anisha_Bala1.original.jpg',
            ],
            [
                'name' => 'Asmita K.C',
                'initials' => 'AK',
                'meta' => 'IELTS Student',
                'tag' => 'IELTS',
                'country' => 'Nepal',
                'quote' => 'Choosing the best IELTS preparation center is crucial because, in my opinion, the band score is vital for studying abroad. One of the best is KTM Educational Consultancy, which provides flexible class timings and a friendly environment that encourages interaction between instructors and students.',
                'cats' => ['ielts'],
                'is_featured' => true,
                'sort_order' => 4,
                'photo_url' => 'https://ktmeducational.edu.np/media/ktm_edu/other_dir/images/asmita_kc1.original.jpg',
            ],
            [
                'name' => 'Asmita thapa magar',
                'initials' => 'AT',
                'meta' => 'IELTS Student',
                'tag' => 'IELTS',
                'country' => 'Nepal',
                'quote' => 'After my Nursing Licensing Examination, I sought a reliable consultancy for IELTS preparation and was recommended to KTM Educational Consultancy by a friend. The peaceful study environment, friendly staff, and weekly mock tests with updated materials significantly boosted my scores. In my opinion, it\'s one of the best consultancies in Nepal for IELTS classes.',
                'cats' => ['ielts'],
                'is_featured' => true,
                'sort_order' => 5,
                'photo_url' => 'https://ktmeducational.edu.np/media/ktm_edu/other_dir/images/asmita_thapa_magar1.original.jpg',
            ],
            [
                'name' => 'Karuna Aryal',
                'initials' => 'KA',
                'meta' => 'IELTS Student',
                'tag' => 'IELTS',
                'country' => 'Nepal',
                'quote' => 'KTM Educational Consultancy, located a few meters ahead of Putalisadak Chowk, provided an excellent environment for my IELTS preparation. The peaceful atmosphere, cooperative teachers, and weekly mock tests with ample learning materials made my classes very fruitful. I am glad to be a part of KTM Educational Consultancy.',
                'cats' => ['ielts'],
                'is_featured' => true,
                'sort_order' => 6,
                'photo_url' => 'https://ktmeducational.edu.np/media/ktm_edu/other_dir/images/karuna1.original.jpg',
            ],
            [
                'name' => 'Manish Bhujel',
                'initials' => 'MB',
                'meta' => 'IELTS Student',
                'tag' => 'IELTS',
                'country' => 'Nepal',
                'quote' => 'Clearly, this is one of the best consultancy in Kathmandu. The reason for being such a better consultancy is good environment and is cheaper then other consultancy which is also giving better knowledge and information. I recommend people to go KTM Educational consultancy and take IELTS or other classes for better future.',
                'cats' => ['ielts'],
                'is_featured' => true,
                'sort_order' => 7,
                'photo_url' => 'https://ktmeducational.edu.np/media/ktm_edu/other_dir/images/manish_bhujel1.original.jpg',
            ],
        ];

        foreach ($testimonials as $data) {
            $photoUrl = $data['photo_url'];
            unset($data['photo_url']);

            // Create record
            $t = Testimonial::create($data);

            // Fetch and save photo
            try {
                $filename = basename($photoUrl);
                $storagePath = "testimonial_photos/{$t->id}/{$filename}";

                // Fetch image content securely via file_get_contents
                $imageContent = @file_get_contents($photoUrl);

                if ($imageContent !== false) {
                    Storage::disk('public')->put($storagePath, $imageContent);
                    $t->update(['photo' => $storagePath]);
                }
            } catch (\Exception $e) {
                // If fetching fails, we keep the testimonial but skip the photo
            }
        }
    }
}
