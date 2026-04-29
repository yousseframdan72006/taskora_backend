<?php

namespace App\Providers;

use App\Events\CommentAdded;
use App\Events\InviteResponded;
use App\Events\InviteSent;
use App\Events\MemberRemovedFromProject;
use App\Events\MemberRemovedFromWorkspace;
use App\Events\ProjectCreated;
use App\Events\ProjectDeleted;
use App\Events\ProjectUpdated;
use App\Events\TaskAssigned;
use App\Events\TaskChanged;
use App\Events\TaskDeleted;
use App\Events\TaskStatusChanged;
use App\Events\TaskUpdated;
use App\Listeners\LogTaskActivity;
use App\Listeners\SendCommentNotification;
use App\Listeners\SendInviteNotification;
use App\Listeners\SendInviteResponseNotification;
use App\Listeners\SendMemberRemovedFromProjectNotification;
use App\Listeners\SendMemberRemovedFromWorkspaceNotification;
use App\Listeners\SendProjectCreatedNotification;
use App\Listeners\SendProjectDeletedNotification;
use App\Listeners\SendProjectUpdatedNotification;
use App\Listeners\SendTaskAssignedNotification;
use App\Listeners\SendTaskDeletedNotification;
use App\Listeners\SendTaskStatusNotification;
use App\Listeners\SendTaskUpdatedNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ---------------------------------------------------------------
        // Task Assigned → Audit Trail + Push Notification
        // ---------------------------------------------------------------
        Event::listen(TaskAssigned::class, [LogTaskActivity::class, 'handleTaskAssigned']);
        Event::listen(TaskAssigned::class, [SendTaskAssignedNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Task Status Changed → Audit Trail + Push Notification
        // ---------------------------------------------------------------
        Event::listen(TaskStatusChanged::class, [LogTaskActivity::class, 'handleTaskStatusChanged']);
        Event::listen(TaskStatusChanged::class, [SendTaskStatusNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Task Updated (title, description, priority, due_date, report)
        // Audit trail (via TaskChanged) + Push Notification to all participants
        // ---------------------------------------------------------------
        Event::listen(TaskChanged::class, [LogTaskActivity::class, 'handleTaskChanged']);
        Event::listen(TaskUpdated::class, [SendTaskUpdatedNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Task Deleted → Push Notification to all former participants
        // ---------------------------------------------------------------
        Event::listen(TaskDeleted::class, [SendTaskDeletedNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Comment Added → Push Notification
        // ---------------------------------------------------------------
        Event::listen(CommentAdded::class, [SendCommentNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Invite Sent → Push Notification (only if user exists in system)
        // ---------------------------------------------------------------
        Event::listen(InviteSent::class, [SendInviteNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Invite Responded (accept/decline) → Push Notification to Admin
        // ---------------------------------------------------------------
        Event::listen(InviteResponded::class, [SendInviteResponseNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Project Created → Push Notification to all assigned members
        // ---------------------------------------------------------------
        Event::listen(ProjectCreated::class, [SendProjectCreatedNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Project Updated → Push Notification to all project members
        // ---------------------------------------------------------------
        Event::listen(ProjectUpdated::class, [SendProjectUpdatedNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Project Deleted → Push Notification to all former members
        // ---------------------------------------------------------------
        Event::listen(ProjectDeleted::class, [SendProjectDeletedNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Member Removed from Project → Push Notification to removed user
        // ---------------------------------------------------------------
        Event::listen(MemberRemovedFromProject::class, [SendMemberRemovedFromProjectNotification::class, 'handle']);

        // ---------------------------------------------------------------
        // Member Removed from Workspace → Push Notification to removed user
        // ---------------------------------------------------------------
        Event::listen(MemberRemovedFromWorkspace::class, [SendMemberRemovedFromWorkspaceNotification::class, 'handle']);
    }
}
