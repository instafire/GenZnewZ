<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\TrackBehaviorEventJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrackingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if ($this->doNotTrack($request)) {
            return response()->json(['status' => 'ignored', 'reason' => 'do_not_track']);
        }

        $validator = Validator::make($request->all(), [
            'visitor_id' => 'required|string|max:64',
            'post_id' => 'required|integer|min:1',
            'action' => 'required|string|in:view,click',
            'source' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $userId = $this->resolveUserId();

        TrackBehaviorEventJob::dispatch(
            (string) $request->input('visitor_id'),
            $userId,
            (int) $request->input('post_id'),
            (string) $request->input('action'),
            $request->input('source')
        );

        return response()->json(['status' => 'tracked']);
    }

    /**
     * Respect Do Not Track and Global Privacy Control signals.
     */
    protected function doNotTrack(Request $request): bool
    {
        $dnt = $request->header('DNT');
        if ($dnt === '1') {
            return true;
        }

        $secGpc = $request->header('Sec-GPC');
        if ($secGpc === '1') {
            return true;
        }

        return false;
    }

    /**
     * Resolve the currently authenticated user ID across available guards.
     */
    protected function resolveUserId(): ?int
    {
        foreach (['member', 'web'] as $guard) {
            if (auth($guard)->check()) {
                return auth($guard)->id();
            }
        }

        return null;
    }
}
