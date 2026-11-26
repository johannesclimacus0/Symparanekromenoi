<?php

declare(strict_types=1);

namespace App\Modules\Workspaces\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class WorkspaceInvitationNotification extends Notification
{
    public function __construct(private readonly string $token, private readonly string $workspaceName) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Workspace invitation')
            ->line('You have been invited to '.$this->workspaceName.'.')
            ->action('Accept invitation', rtrim((string) config('app.url'), '/').'/invitations/accept#token='.urlencode($this->token));
    }
}
