<?php

namespace App\Notifications\Businesses;

use App\Models\BusinessInvitation as BusinessInvitationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public BusinessInvitationModel $invitation)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $business = $this->invitation->business;
        $inviter = $this->invitation->inviter;

        return (new MailMessage)
            ->subject(__("You've been invited to join :businessName", ['businessName' => $business->name]))
            ->line(__(':inviterName has invited you to join the :businessName business.', [
                'inviterName' => $inviter->name,
                'businessName' => $business->name,
            ]))
            ->line(__('Log in and visit your dashboard to accept or decline this invitation.'))
            ->action(
                __('Log in'),
                route('login', ['invitation' => $this->invitation->code]),
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'business_id' => $this->invitation->business_id,
            'business_name' => $this->invitation->business->name,
            'role' => $this->invitation->role->value,
        ];
    }
}
