<?php

namespace BinaryCats\SurveyMonkeyWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;
use Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile;

class SurveyMonkeyWebhooksController
{
    /**
     * Invoke controller method.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string|null $configKey
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, string $configKey = null)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'survey-monkey',
            'signing_secret' => ($configKey) ?
                config('surveymonkey-webhooks.signing_secret_'.$configKey) :
                config('surveymonkey-webhooks.signing_secret'),
            'signature_header_name' => 'sm-signature',
            'signature_validator' => SurveyMonkeySignatureValidator::class,
            'webhook_profile' => ProcessEverythingWebhookProfile::class,
            'webhook_model' => config('surveymonkey-webhooks.model'),
            'process_webhook_job' => config('surveymonkey-webhooks.process_webhook_job'),
        ]);

        (new WebhookProcessor($request, $webhookConfig))->process();

        return response()->json(['message' => 'ok']);
    }
}
