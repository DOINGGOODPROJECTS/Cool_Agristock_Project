<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;

class MailConfigServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Listen for mail sending events and override the from address
        Event::listen(MessageSending::class, function (MessageSending $event) {
            // Use the correct method for setting from address
            $event->message->getHeaders()->addMailboxHeader('From', 'noreply@agristock.fraiszo.com', 'Cool AgriStock');
            $event->message->getHeaders()->addMailboxHeader('Reply-To', 'noreply@agristock.fraiszo.com', 'Cool AgriStock');
        });
    }

    public function register()
    {
        //
    }
}