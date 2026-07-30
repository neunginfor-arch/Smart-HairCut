<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PromotionController extends Controller
{
    public function index(): View
    {
        return view('admin.promotions', [
            'promotions' => Promotion::orderBy('display_order')->latest('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('promotion-banners');
        }

        Promotion::create($data);

        return back()->with('success', 'เพิ่มโปรโมชั่นเรียบร้อยแล้ว');
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $data = $this->validatedData($request);
        if ($request->hasFile('image')) {
            $oldPath = $promotion->image_path;
            $data['image_path'] = $request->file('image')->store('promotion-banners');
            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
            }
        } elseif ($request->boolean('remove_image')) {
            if ($promotion->image_path) {
                Storage::disk('local')->delete($promotion->image_path);
            }
            $data['image_path'] = null;
        }

        $promotion->update($data);

        return back()->with('success', 'อัปเดตโปรโมชั่นเรียบร้อยแล้ว');
    }

    public function toggle(Promotion $promotion): RedirectResponse
    {
        $promotion->update(['is_active' => !$promotion->is_active]);

        return back()->with(
            'success',
            $promotion->is_active ? 'เปิดแสดงโปรโมชั่นแล้ว' : 'ปิดแสดงโปรโมชั่นแล้ว'
        );
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        if ($promotion->image_path) {
            Storage::disk('local')->delete($promotion->image_path);
        }
        $promotion->delete();

        return back()->with('success', 'ลบโปรโมชั่นเรียบร้อยแล้ว');
    }

    public function image(Promotion $promotion): BinaryFileResponse
    {
        abort_unless(
            $promotion->image_path && Storage::disk('local')->exists($promotion->image_path),
            404
        );

        return response()->file(Storage::disk('local')->path($promotion->image_path), [
            'Cache-Control' => 'private, max-age=600',
            'Content-Type' => Storage::disk('local')->mimeType($promotion->image_path) ?: 'image/jpeg',
        ]);
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'button_text' => 'nullable|string|max:50',
            'button_url' => 'nullable|url|max:2048',
            'display_order' => 'nullable|integer|min:0|max:999',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'remove_image' => 'nullable|boolean',
        ]);

        unset($data['image'], $data['remove_image']);
        $data['display_order'] = (int) ($data['display_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
