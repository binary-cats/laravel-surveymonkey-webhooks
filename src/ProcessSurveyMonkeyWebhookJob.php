<?php

namespace BinaryCats\SurveyMonkeyWebhooks;

use BinaryCats\SurveyMonkeyWebhooks\Exceptions\WebhookFailed;
use Illuminate\Support\Arr;
use Spatie\WebhookClient\ProcessWebhookJob;

class ProcessSurveyMonkeyWebhookJob extends ProcessWebhookJob
{
    /**
     * Name of the payload key to contain the type of event.
     *
     * @var string
     */
    protected $key = 'event_type';

    /**
     * Handle the process.
     *
     * @return void
     */
    public function handle()
    {
        $type = Arr::get($this->webhookCall, "payload.{$this->key}");

        if (! $type) {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        event("surveymonkey-webhooks::{$type}", $this->webhookCall);

        $jobClass = $this->determineJobClass($type);

        if ($jobClass === '') {
            return;
        }

        if (! class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this->webhookCall);
        }

        dispatch(new $jobClass($this->webhookCall));
    }

    /**
     * Calculate the name of the Job class
     *
     * @param  string $eventType
     * @return string
     */
    protected function determineJobClass(string $eventType): string
    {
        $jobConfigKey = str_replace('.', '_', $eventType);

        return config("surveymonkey-webhooks.jobs.{$jobConfigKey}", '');
    }
}
