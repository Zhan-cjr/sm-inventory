<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook {action=info : Action to perform: set, info, or delete} {--url= : Custom webhook URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Telegram Bot Webhook (set, info, delete)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN is not defined in .env');
            return 1;
        }

        $action = $this->argument('action');

        if ($action === 'set') {
            $webhookUrl = $this->option('url') ?: rtrim(config('app.url'), '/') . '/api/v1/webhook/telegram';
            $this->info("Registering webhook to: {$webhookUrl}");

            $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl,
                'allowed_updates' => [
                    'message',
                    'edited_message',
                    'channel_post',
                    'edited_channel_post',
                    'callback_query'
                ],
                'drop_pending_updates' => false,
            ]);

            $this->line("Telegram Response: " . $response->body());
            return $response->successful() ? 0 : 1;
        }

        if ($action === 'delete') {
            $this->info("Deleting webhook for bot...");
            $response = Http::post("https://api.telegram.org/bot{$token}/deleteWebhook", [
                'drop_pending_updates' => false
            ]);
            $this->line("Telegram Response: " . $response->body());
            return $response->successful() ? 0 : 1;
        }

        // Default: info
        $this->info("Checking webhook info for bot...");
        $response = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
        $this->line("Telegram Response: " . $response->body());
        return 0;
    }
}
