<?php

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditConversation extends EditRecord
{
    protected static string $resource = ConversationResource::class;

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
            DeleteAction::make(),
        ];
    }
}
