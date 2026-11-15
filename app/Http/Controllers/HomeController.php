<?php

namespace App\Http\Controllers;

use App\Models\HomeCard;
use App\Models\HomeDoctor;
use App\Models\HomeTestimony;
use App\Models\HomeText;
use App\Services\ImageUploader;
use App\Support\HomeContentCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct(
        protected ImageUploader $imageUploader,
    ) {}

    public function index(HomeContentCache $contentCache)
    {
        return view('index', $contentCache->get());
    }

    public function update(Request $request)
    {
        $request->validate([
            'doctors.*.image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096|dimensions:max_width=6000,max_height=6000',
            'new_doctors.*.image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096|dimensions:max_width=6000,max_height=6000',
            'testimonials.*.image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096|dimensions:max_width=6000,max_height=6000',
            'new_testimonials.*.image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096|dimensions:max_width=6000,max_height=6000',
        ]);

        DB::transaction(function () use ($request) {
            if ($request->has('texts')) {
                foreach ($request->input('texts') as $id => $value) {
                    HomeText::updateOrCreate(
                        ['id' => $id],
                        ['value' => $value]
                    );
                }
            }

            // Hapus Card (About / Mission / Core Values / Service)
            if ($request->has('delete_cards')) {
                HomeCard::destroy($request->input('delete_cards'));
            }

            // Hapus Dokter (soft delete: file gambar dibiarkan di tempat)
            if ($request->has('delete_doctors')) {
                HomeDoctor::destroy($request->input('delete_doctors'));
            }

            // Hapus Testimoni (soft delete: file gambar dibiarkan di tempat)
            if ($request->has('delete_testimonials')) {
                HomeTestimony::destroy($request->input('delete_testimonials'));
            }

            // Update Card Lama (icon/title/text/sort_order)
            if ($request->has('cards')) {
                foreach ($request->input('cards') as $id => $data) {
                    $card = HomeCard::find($id);
                    if ($card) {
                        $card->update([
                            'icon' => $data['icon'] ?? '',
                            'title' => $data['title'] ?? '',
                            'text' => $data['text'] ?? null,
                            'sort_order' => $data['sort_order'] ?? 0,
                        ]);
                    }
                }
            }

            // Update Dokter Lama
            if ($request->has('doctors')) {
                foreach ($request->input('doctors') as $id => $data) {
                    $doctor = HomeDoctor::find($id);
                    if (! $doctor) {
                        continue;
                    }

                    $imageValue = $data['image'] ?? $doctor->image;

                    if ($request->hasFile("doctors.$id.image_file")) {
                        $this->imageUploader->archive($doctor->image, 'profile');
                        $imageValue = $this->imageUploader->store(
                            $request->file('doctors')[$id]['image_file'],
                            'profile',
                            $data['name'] ?? 'doctor'
                        );
                    }

                    $doctor->update([
                        'image' => $imageValue,
                        'name' => $data['name'] ?? '',
                        'title' => $data['title'] ?? '',
                        'text' => $data['text'] ?? '',
                        'twitter' => $data['twitter'] ?? null,
                        'fb' => $data['fb'] ?? null,
                        'linkedin' => $data['linkedin'] ?? null,
                        'sort_order' => $data['sort_order'] ?? $doctor->sort_order,
                    ]);
                }
            }

            // Update Testimoni Lama
            if ($request->has('testimonials')) {
                foreach ($request->input('testimonials') as $id => $data) {
                    $testimony = HomeTestimony::find($id);
                    if (! $testimony) {
                        continue;
                    }

                    $imageValue = $data['image'] ?? $testimony->image;

                    if ($request->hasFile("testimonials.$id.image_file")) {
                        $this->imageUploader->archive($testimony->image, 'testimonials');
                        $imageValue = $this->imageUploader->store(
                            $request->file('testimonials')[$id]['image_file'],
                            'testimonials',
                            $data['name'] ?? 'testimony'
                        );
                    }

                    $testimony->update([
                        'image' => $imageValue,
                        'name' => $data['name'] ?? '',
                        'title' => $data['title'] ?? '',
                        'text' => $data['text'] ?? '',
                    ]);
                }
            }

            // Insert Card Baru
            if ($request->has('new_cards')) {
                foreach ($request->input('new_cards') as $data) {
                    HomeCard::create([
                        'section' => $data['section'],
                        'icon' => $data['icon'] ?? 'fas fa-heartbeat',
                        'title' => $data['title'] ?? '',
                        'text' => $data['text'] ?? null,
                        'sort_order' => $data['sort_order'] ?? 0,
                    ]);
                }
            }

            // Insert Dokter Baru
            if ($request->has('new_doctors')) {
                foreach ($request->input('new_doctors') as $key => $data) {
                    $imageValue = $data['image'] ?? '';

                    if ($request->hasFile("new_doctors.$key.image_file")) {
                        $imageValue = $this->imageUploader->store(
                            $request->file('new_doctors')[$key]['image_file'],
                            'profile',
                            $data['name'] ?? 'doctor'
                        );
                    }

                    HomeDoctor::create([
                        'image' => $imageValue,
                        'name' => $data['name'] ?? '',
                        'title' => $data['title'] ?? '',
                        'text' => $data['text'] ?? '',
                        'twitter' => $data['twitter'] ?? null,
                        'fb' => $data['fb'] ?? null,
                        'linkedin' => $data['linkedin'] ?? null,
                        'sort_order' => $data['sort_order'] ?? 0,
                    ]);
                }
            }

            // Insert Testimoni Baru
            if ($request->has('new_testimonials')) {
                foreach ($request->input('new_testimonials') as $key => $data) {
                    $imageValue = $data['image'] ?? null;

                    if ($request->hasFile("new_testimonials.$key.image_file")) {
                        $imageValue = $this->imageUploader->store(
                            $request->file('new_testimonials')[$key]['image_file'],
                            'testimonials',
                            $data['name'] ?? 'testimony'
                        );
                    }

                    HomeTestimony::create([
                        'image' => $imageValue,
                        'name' => $data['name'] ?? '',
                        'title' => $data['title'] ?? '',
                        'text' => $data['text'] ?? '',
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Semua perubahan berhasil diselaraskan!');
    }
}
