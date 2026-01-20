<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceThresholdReached extends Notification
{
    use Queueable;

    public $itemAlert;
    public $triggeredPrice;

    public function __construct(\App\Models\ItemAlert $itemAlert, int $triggeredPrice)
    {
        $this->itemAlert = $itemAlert;
        $this->triggeredPrice = $triggeredPrice;
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];
        if ($this->itemAlert->webhook_url) {
            $channels[] = \App\Channels\WebhookChannel::class;
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $itemName = $this->itemAlert->item->name;
        $type = $this->itemAlert->type;
        $direction = $this->itemAlert->direction;
        $triggered = $this->triggeredPrice;

        $subject = "Alert: {$itemName}";
        $message = "";

        switch ($type) {
            case 'price':
                $threshold = number_format((float) $this->itemAlert->threshold_price);
                $subject = "Price Alert: {$itemName} is {$direction} {$threshold} gp";
                $message = "The price of **{$itemName}** has reached **" . number_format((float) $triggered) . " gp**, which is **{$direction}** your threshold of **{$threshold} gp**.";
                break;
            case 'percentage':
                $target = $this->itemAlert->target_value;
                $subject = "Price Move: {$itemName} shifted {$triggered}%";
                $move = $triggered >= 0 ? 'risen' : 'dropped';
                $message = "The price of **{$itemName}** has **{$move}** by **" . abs($triggered) . "%**, hitting your **{$target}%** target.";
                break;
            case 'margin':
                $target = number_format((float) $this->itemAlert->target_value);
                $subject = "Margin Alert: {$itemName} margin is {$direction} {$target} gp";
                $message = "The flip margin for **{$itemName}** is now **" . number_format((float) $triggered) . " gp**, which is **{$direction}** your target of **{$target} gp**.";
                break;
            case 'volume':
                $target = number_format((float) $this->itemAlert->target_value);
                $subject = "Volume Alert: {$itemName} 1h volume is {$direction} {$target}";
                $message = "The 1-hour trade volume for **{$itemName}** has reached **" . number_format((float) $triggered) . "**, which is **{$direction}** your threshold of **{$target}**.";
                break;
        }

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->action('View Item', url("/dashboard"))
            ->line('Thank you for using OSRS GE Item Tracker!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'item_id' => $this->itemAlert->item_id,
            'item_name' => $this->itemAlert->item->name,
            'threshold_price' => $this->itemAlert->threshold_price,
            'triggered_price' => $this->triggeredPrice,
            'direction' => $this->itemAlert->direction,
        ];
    }

    public function toWebhook($notifiable)
    {
        $itemName = $this->itemAlert->item->name;
        $type = $this->itemAlert->type;
        $direction = $this->itemAlert->direction;
        $triggered = $this->triggeredPrice;

        $title = "Alert: {$itemName}";
        $description = "";

        switch ($type) {
            case 'price':
                $threshold = number_format((float) $this->itemAlert->threshold_price);
                $title = "Price Alert: {$itemName}";
                $description = "Price is now **" . number_format((float) $triggered) . " gp**, which is **{$direction}** your **{$threshold} gp** threshold.";
                break;
            case 'percentage':
                $move = $triggered >= 0 ? "risen" : "dropped";
                $title = "Price Shift: {$itemName}";
                $description = "Price has **{$move}** by **" . abs($triggered) . "%**, hitting your **" . $this->itemAlert->target_value . "%** target.";
                break;
            case 'margin':
                $target = number_format((float) $this->itemAlert->target_value);
                $title = "Margin Alert: {$itemName}";
                $description = "Flip margin is now **" . number_format((float) $triggered) . " gp**, which is **{$direction}** your **{$target} gp** target.";
                break;
            case 'volume':
                $title = "Volume Alert: {$itemName}";
                $description = "1h Volume has hit **" . number_format((float) $triggered) . "**, which is **{$direction}** your **" . number_format((float) $this->itemAlert->target_value) . "** threshold.";
                break;
        }

        return \Illuminate\Support\Facades\Http::post($this->itemAlert->webhook_url, [
            'content' => "🔔 **{$title}**: {$description}\n\nView your alerts: " . url('/dashboard'),
            'embeds' => [
                [
                    'title' => "{$itemName} on OSRS Wiki",
                    'url' => "https://oldschool.runescape.wiki/w/" . str_replace(' ', '_', $itemName),
                    'description' => $description,
                    'color' => ($type === 'price' && $direction === 'above') || ($type === 'percentage' && $triggered > 0) ? 3066993 : 15105570,
                    'thumbnail' => [
                        'url' => $this->itemAlert->item->icon,
                    ],
                    'fields' => [
                        ['name' => 'Current Value', 'value' => number_format((float) $triggered) . ($type === 'percentage' ? '%' : ''), 'inline' => true],
                        ['name' => 'Threshold', 'value' => ($type === 'price' ? number_format((float) $this->itemAlert->threshold_price) . ' gp' : ($type === 'percentage' ? $this->itemAlert->target_value . '%' : number_format((float) $this->itemAlert->target_value))), 'inline' => true],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ]
            ],
        ]);
    }
}
