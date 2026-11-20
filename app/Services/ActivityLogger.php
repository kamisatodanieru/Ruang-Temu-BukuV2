<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogger
{
    public static function record(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        ?User $actor = null,
        ?Request $request = null,
    ): ActivityLog {
        $request ??= request();
        $actorId = $actor?->id ?? Auth::id();

        $userAgent = $request?->userAgent();
        if ($userAgent !== null) {
            $userAgent = Str::limit($userAgent, 495, '...');
        }

        return ActivityLog::query()->create([
            'user_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject !== null ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}
