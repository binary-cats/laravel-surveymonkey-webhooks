<?php

namespace BinaryCats\SurveyMonkeyWebhooks\Tests;

use Spatie\WebhookClient\Models\WebhookCall;

class DummyJob
{
    /**
     * Bind the implementation.
     */
    public $webhookCall;

    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    /**
     * Handle the job.
     */
    public function handle(): void
    {
        cache()->put('dummyjob', $this->webhookCall);
    }
}
