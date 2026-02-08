<?php

namespace App\Traits;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Reusable sharing logic for API controllers that manage shareable resources.
 *
 * Requirements on the model:
 * - Must have a `sharedWith()` BelongsToMany relation.
 * - Must have a `title` attribute for notification text.
 */
trait HasApiSharing
{
    /**
     * Get the permission field name for the pivot table ('permission' or 'status').
     */
    abstract protected function sharingPivotField(): string;

    /**
     * Get the notification type string (e.g. 'note_shared').
     */
    abstract protected function sharingNotificationType(): string;

    /**
     * Get the notification title string (e.g. 'Notiz geteilt').
     */
    abstract protected function sharingNotificationTitle(): string;

    /**
     * Get the notification message for sharing.
     */
    abstract protected function sharingNotificationMessage(Model $resource): string;

    /**
     * Get the notification data payload.
     */
    abstract protected function sharingNotificationData(Model $resource): array;

    /**
     * Return the JSON resource for the given model (e.g. new ListResource($model)).
     */
    abstract protected function sharingResource(Model $resource): mixed;

    /**
     * Get the policy ability name for sharing authorization.
     */
    protected function sharingAbility(): string
    {
        return 'share';
    }

    /**
     * Get the default permission value for sharing.
     */
    protected function sharingDefaultPermission(): string
    {
        return 'view';
    }

    /**
     * Whether the share request should include a permission field.
     */
    protected function sharingRequiresPermission(): bool
    {
        return true;
    }

    /**
     * Share the resource with another user.
     */
    protected function performApiShare(Request $request, Model $resource): mixed
    {
        $this->authorize($this->sharingAbility(), $resource);

        $rules = [
            'user_id' => ['required', 'exists:users,id', 'not_in:'.auth()->id()],
        ];

        if ($this->sharingRequiresPermission()) {
            $rules['permission'] = ['required', 'in:view,edit'];
        }

        $request->validate($rules);

        $pivotData = $this->sharingRequiresPermission()
            ? [$this->sharingPivotField() => $request->permission]
            : [$this->sharingPivotField() => $this->sharingDefaultPermission()];

        $resource->sharedWith()->syncWithoutDetaching([
            $request->user_id => $pivotData,
        ]);

        app(NotificationService::class)->notify(
            User::findOrFail($request->user_id),
            auth()->user(),
            $this->sharingNotificationType(),
            $this->sharingNotificationTitle(),
            $this->sharingNotificationMessage($resource),
            $this->sharingNotificationData($resource),
        );

        $resource->load(['owner', 'sharedWith']);

        return $this->sharingResource($resource);
    }

    /**
     * Remove sharing of the resource with a user.
     */
    protected function performApiUnshare(Request $request, Model $resource): mixed
    {
        $this->authorize($this->sharingAbility(), $resource);

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $resource->sharedWith()->detach($request->user_id);

        $resource->load(['owner', 'sharedWith']);

        return $this->sharingResource($resource);
    }
}
