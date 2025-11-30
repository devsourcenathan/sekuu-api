<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\Http\Request;

class LegalPageController extends Controller
{
    /**
     * Get a published legal page by slug (public)
     * GET /api/legal/{slug}
     */
    public function show(string $slug)
    {
        $page = LegalPage::where('slug', $slug)
            ->published()
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'page' => $page,
        ]);
    }

    /**
     * List all legal pages (admin only)
     * GET /api/admin/legal
     */
    public function index()
    {
        $this->authorize('manage-settings');

        $pages = LegalPage::orderBy('slug')->get();

        return response()->json([
            'success' => true,
            'pages' => $pages,
        ]);
    }

    /**
     * Create or update a legal page (admin only)
     * PUT /api/admin/legal/{slug}
     */
    public function upsert(Request $request, string $slug)
    {
        $this->authorize('manage-settings');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'version' => 'required|string|max:50',
            'is_published' => 'boolean',
        ]);

        $page = LegalPage::updateOrCreate(
            ['slug' => $slug],
            $validated
        );

        return response()->json([
            'success' => true,
            'page' => $page,
            'message' => 'Legal page saved successfully',
        ]);
    }
}
