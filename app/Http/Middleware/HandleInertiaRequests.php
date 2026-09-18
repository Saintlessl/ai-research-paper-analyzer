<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        $user?->loadMissing('roles');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'role' => $user->primaryRole()?->value,
                    'roles' => array_map(
                        static fn (RoleName $role): string => $role->value,
                        $user->roleNames(),
                    ),
                ],
                'capabilities' => $user?->capabilities() ?? [
                    'upload_papers' => false,
                    'review_papers' => false,
                    'manage_system' => false,
                ],
            ],
        ];
    }
}
