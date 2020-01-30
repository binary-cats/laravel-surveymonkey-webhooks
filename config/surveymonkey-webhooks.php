<?php

return [

    /*
     * Survey Monkey is an online survey development cloud-based software as a service company.
     * You can find the used secret after creating Survey Monkey App: https://developer.surveymonkey.com/
     */
    'signing_secret' => env('SURVEYMONKEY_API_SECRET'),

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Survey Monkey event type with the `.` replaced by a `_`.
     *
     * You can find a list of Survey Monkey webhook types here:
     * https://developer.surveymonkey.com/api/v3/#webhook-callbacks
     */
    'jobs' => [
        // Example:
        // 'response_completed' => \BinaryCats\SurveyMonkeyWebhooks\Jobs\HandleResponseCompleted::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Spatie\WebhookClient\Models\WebhookCall
     */
    'model' => \Spatie\WebhookClient\Models\WebhookCall::class,

    /*
     * The classname of the model to be used. The class should equal or extend
     * BinaryCats\SurveyMonkeyWebhooks\ProcessSurveyMonkeyWebhookJob
     */
    'process_webhook_job' => \BinaryCats\SurveyMonkeyWebhooks\ProcessSurveyMonkeyWebhookJob::class,
];
