<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(
        public User $user,
        public string $type,
        public string $titleKey,
        public string $bodyKey,
        public ?array $parameters = null,
        public ?array $metadata = null
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $notificationService->send(
            user: $this->user,
            type: $this->type,
            titleKey: $this->titleKey,
            bodyKey: $this->bodyKey,
            parameters: $this->parameters,
            metadata: $this->metadata
        );
    }
}
