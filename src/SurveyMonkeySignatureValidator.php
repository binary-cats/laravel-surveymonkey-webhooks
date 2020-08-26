<?php

namespace BinaryCats\SurveyMonkeyWebhooks;

use Exception;
use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class SurveyMonkeySignatureValidator implements SignatureValidator
{
    /**
     * Bind the implemetation.
     *
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     * Inject the config.
     *
     * @var Spatie\WebhookClient\WebhookConfig
     */
    protected $config;

    /**
     * True if the signature has been valiates.
     *
     * @param  Illuminate\Http\Request       $request
     * @param  Spatie\WebhookClient\WebhookConfig $config
     *
     * @return bool
     */
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signatureArray = [
            'payload'   => $request->input(),
            'apiKey'    => $request->header('sm-apikey'),
            'signature' => $request->header('sm-signature'),
        ];

        $secret = $config->signingSecret;

        try {
            Webhook::constructEvent($request->all(), $signatureArray, $secret);
        } catch (Exception $exception) {
            report($exception);

            return false;
        }

        return true;
    }
}
