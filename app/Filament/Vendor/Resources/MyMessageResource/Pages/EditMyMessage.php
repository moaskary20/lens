<?php

namespace App\Filament\Vendor\Resources\MyMessageResource\Pages;

use App\Filament\Vendor\Resources\MyMessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditMyMessage extends EditRecord
{
    protected static string $resource = MyMessageResource::class;

    public function getTitle(): string | Htmlable
    {
        $name = $this->getRecord()->client?->name;

        return $name ? 'Chat with '.$name : 'Client chat';
    }

    public function getHeading(): string | Htmlable
    {
        return $this->getTitle();
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        // Vendors reply through Send message. They cannot change participants.
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Send message')
                ->schema([
                    Textarea::make('body')->label('Message')->required()->rows(4),
                ])
                ->action(function (array $data): void {
                    /** @var Conversation $conversation */
                    $conversation = $this->getRecord();
                    abort_unless((int) $conversation->vendor_id === (int) auth()->user()?->vendor?->id, 403);

                    Message::query()->create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => auth()->id(),
                        'body' => $data['body'],
                        'type' => 'text',
                    ]);
                    $conversation->update(['last_message_at' => now()]);

                    Notification::make()->title('Message sent to the chat')->success()->send();
                    $this->redirect(static::getUrl(['record' => $conversation]));
                }),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [];
    }
}
