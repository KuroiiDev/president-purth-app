<?php

namespace Database\Seeders;

use App\Models\HomeCard;
use App\Models\HomeDoctor;
use App\Models\HomeText;
use App\Support\RevisionContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomeSeeder extends Seeder
{
    public function run(): void
    {
        app(RevisionContext::class)->withoutRecording(function () {
            $texts = [
                'hero_badge' => 'PUH',
                'hero_title' => 'PRESIDENT UNIVERSITY HOSPITAL',
                'hero_desc' => '❝ For Better Health Services and Regional Competitiveness ❞',
                'hero_btn_1_text' => 'About PUH',
                'hero_btn_2_text' => 'Contact Us',

                'about_badge' => 'About PUH',
                'about_title' => 'Welcome to PUH',
                'about_desc' => "Welcome to President University Hospital (PUH), a modern general hospital dedicated to delivering professional, high-quality healthcare while serving as a center for medical education, research, and innovation.\nAs an integral part of President University, PUH is committed to advancing an integrated healthcare ecosystem through the Academic Health System (AHS), fostering collaboration among clinical care, medical education, research, and community engagement.\nSupported by experienced healthcare professionals, state-of-the-art facilities, and a culture that prioritizes patient safety and compassionate care, PUH is dedicated to providing safe, high-quality, and patient-centered healthcare while contributing to the advancement of medical science and the development of future healthcare professionals.",

                'academic_badge' => 'Education & Research',
                'academic_title' => 'Advancing Healthcare Through Education & Innovation',
                'academic_desc' => 'President University Hospital (PUH) is a teaching hospital that integrates healthcare services, medical education, research, and innovation within a comprehensive Academic Health System. As an integral part of President University, PUH is committed to delivering high-quality, patient-centered healthcare while advancing medical education, developing future healthcare professionals, and strengthening the competitiveness of both the industrial community and the wider society through excellence in clinical care, education, and research.',
                'vision_text' => 'To become an internationally recognized teaching hospital, distinguished for excellence in healthcare services, medical education, research, and innovation, improving the quality of life of the community while strengthening regional competitiveness.',

                'services_badge' => 'Medical Services',
                'services_title' => 'Our Comprehensive Medical Offerings',

                'doctors_badge' => 'Hospital Leadership',
                'doctors_title' => 'Board of Directors',

                'testimony_badge' => 'Testimonial',
                'testimony_title' => 'Patients Say About Our Services',

                'contact_badge' => 'Contact Us',
                'contact_title' => 'Please Feel Free To Contact Us',

                'address' => 'Jababeka Industrial Township, Cikarang, Bekasi, Indonesia',
                'email' => 'purth@president.ac.id ',
                'phone' => '+62 21-8984-0530',
                'facebook' => '',
                'twitter' => '',
                'linkedin' => '',
                'instagram' => 'https://www.instagram.com/presuniv_hospital/',
                'youtube' => '',
                'map_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.7442281324247!2d107.1483669!3d-6.2973038!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e699b1e17175793%3A0x4f3c3244b22dbb10!2sPresident%20University%20Hospital!5e0!3m2!1sid!2sid!4v1783326808446!5m2!1sid!2sid',
            ];

            foreach ($texts as $id => $value) {
                HomeText::updateOrCreate(['id' => $id], ['value' => $value]);
            }

            // Clean tables
            DB::table('home_cards')->truncate();
            DB::table('home_doctors')->truncate();
            DB::table('home_testimonies')->truncate();

            $aboutCards = [
                ['icon' => 'fas fa-university', 'title' => 'Academic Teaching Hospital', 'text' => null, 'sort_order' => 1],
                ['icon' => 'fas fa-hospital-alt', 'title' => 'Modern Medical Facilities', 'text' => null, 'sort_order' => 2],
                ['icon' => 'fas fa-user-md', 'title' => 'Experienced Medical Professionals', 'text' => null, 'sort_order' => 3],
                ['icon' => 'fas fa-microscope', 'title' => 'Evidence-Based Healthcare', 'text' => null, 'sort_order' => 4],
            ];

            foreach ($aboutCards as $card) {
                HomeCard::create(array_merge($card, ['section' => 'about']));
            }

            $missionCards = [
                ['icon' => 'fas fa-check-circle', 'title' => 'Quality Healthcare', 'text' => 'To deliver professional, safe, high-quality, and patient-centered healthcare services.', 'sort_order' => 1],
                ['icon' => 'fas fa-check-circle', 'title' => 'Clinical Education', 'text' => 'To serve as the primary teaching hospital for the Faculty of Medicine, President University, by providing excellence in clinical education and training.', 'sort_order' => 2],
                ['icon' => 'fas fa-check-circle', 'title' => 'Research & Innovation', 'text' => 'To advance healthcare research and innovation that contribute to the well-being of society and the development of the healthcare industry.', 'sort_order' => 3],
                ['icon' => 'fas fa-check-circle', 'title' => 'Strategic Partnerships', 'text' => 'To establish strategic partnerships with academic institutions, healthcare organizations, government agencies, industry, and international partners.', 'sort_order' => 4],
                ['icon' => 'fas fa-check-circle', 'title' => 'Professional Development', 'text' => 'To develop healthcare professionals who demonstrate integrity, professionalism, adaptability to emerging healthcare technologies, and a global perspective.', 'sort_order' => 5],
                ['icon' => 'fas fa-check-circle', 'title' => 'Hospital Governance', 'text' => 'To implement effective, transparent, accountable, and sustainable hospital governance.', 'sort_order' => 6],
            ];

            foreach ($missionCards as $card) {
                HomeCard::create(array_merge($card, ['section' => 'mission']));
            }

            $coreValuesCards = [
                ['title' => 'Professionalism', 'text' => 'Delivering healthcare services in accordance with the highest professional standards and ethical principles.', 'sort_order' => 1],
                ['title' => 'Respect', 'text' => 'Treating every patient, family member, and healthcare professional with dignity, respect, and compassion.', 'sort_order' => 2],
                ['title' => 'Excellence', 'text' => 'Striving for excellence through continuous quality improvement, innovation, and outstanding performance.', 'sort_order' => 3],
                ['title' => 'Service', 'text' => 'Providing compassionate, responsive, and patient-centered care with sincerity and dedication.', 'sort_order' => 4],
                ['title' => 'Integrity', 'text' => 'Upholding honesty, accountability, transparency, and ethical conduct in every aspect of our work.', 'sort_order' => 5],
                ['title' => 'Development', 'text' => 'Fostering continuous growth through education, research, innovation, and lifelong learning.', 'sort_order' => 6],
                ['title' => 'Empathy', 'text' => 'Understanding and responding to the needs of patients and their families with compassion and kindness.', 'sort_order' => 7],
                ['title' => 'Networking', 'text' => 'Building strong partnerships and collaborations with national and international institutions to advance healthcare, education, and research.', 'sort_order' => 8],
                ['title' => 'Trust', 'text' => 'Earning and maintaining the trust of our patients, partners, and communities through excellence, reliability, and integrity.', 'sort_order' => 9],
            ];

            foreach ($coreValuesCards as $card) {
                HomeCard::create(array_merge($card, ['section' => 'core_values']));
            }

            $serviceCards = [
                ['icon' => 'fas fa-briefcase-medical', 'title' => 'General Services', 'text' => 'General Practitioner, Medical Check Up, Emergency Services, Laboratory, Pharmacy, Radiology', 'sort_order' => 1],
                ['icon' => 'fas fa-stethoscope', 'title' => 'Specialist Services', 'text' => 'Pediatrics, Internal Medicine, General Surgery, Obstetrics & Gynecology', 'sort_order' => 2],
            ];

            foreach ($serviceCards as $card) {
                HomeCard::create(array_merge($card, ['section' => 'service']));
            }

            $doctors = [
                [
                    'image' => 'smile.jpg',
                    'name' => 'Leader A',
                    'title' => 'President Director',
                    'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
                    'sort_order' => 1,
                ],
                [
                    'image' => 'smile.jpg',
                    'name' => 'Leader B',
                    'title' => 'Director',
                    'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
                    'sort_order' => 2,
                ],
                [
                    'image' => 'smile.jpg',
                    'name' => 'Leader C',
                    'title' => 'Legal and Compliance Trustee',
                    'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur pretium tincidunt lacus. Nulla gravida orci a odio. Nullam varius, turpis et commodo pharetra, est eros bibendum elit, nec luctus magna felis sollicitudin mauris.',
                    'sort_order' => 3,
                ],
                [
                    'image' => 'smile.jpg',
                    'name' => 'Leader D',
                    'title' => 'Hospital Director',
                    'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus hendrerit. Pellentesque aliquet nibh nec urna. In nisi neque, aliquet vel, dapibus id, mattis vel, nisi. Sed pretium, ligula sollicitudin laoreet viverra, tortor libero sodales leo, eget blandit nunc tortor eu nibh.',
                    'sort_order' => 4,
                ],
            ];

            foreach ($doctors as $doctor) {
                HomeDoctor::create($doctor);
            }
        });
    }
}
