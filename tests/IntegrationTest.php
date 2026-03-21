<?php

namespace BinaryCats\SurveyMonkeyWebhooks\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Spatie\WebhookClient\Models\WebhookCall;

class IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        Route::surveyMonkeyWebhooks('webhooks/survey-monkey');
        Route::surveyMonkeyWebhooks('webhooks/survey-monkey/{configKey}');

        config(['surveymonkey-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        cache()->clear();
    }

    #[Test]
    public function it_can_handle_a_valid_request(): void
    {
        $payload = [
            'event_type' => 'my.type',
        ];

        $headers = [
            'sm-apikey' => $apiKey = 'api-key',
            'sm-signature' => $this->determineSurveyMonkeySignature($payload, $apiKey),
        ];

        $this
            ->postJson('webhooks/survey-monkey', $payload, $headers)
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertEquals('my.type', $webhookCall->payload['event_type']);
        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('surveymonkey-webhooks::my.type', function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
    }

    #[Test]
    public function a_request_with_an_invalid_signature_wont_be_logged(): void
    {
        $payload = [
            'event_type' => 'my.type',
        ];

        $headers = [
            'sm-apikey' => 'api-key',
            'sm-signature' => 'incorrect_signature',
        ];

        $this
            ->postJson('webhooks/survey-monkey', $payload, $headers)
            ->assertStatus(500);

        $this->assertCount(0, WebhookCall::get());

        Event::assertNotDispatched('surveymonkey-webhooks::my.type');

        $this->assertNull(cache('dummyjob'));
    }

    #[Test]
    public function a_request_with_an_invalid_payload_will_be_logged_but_events_and_jobs_will_not_be_dispatched(): void
    {
        $payload = ['invalid_payload'];

        $headers = [
            'sm-apikey' => $apiKey = 'api-key',
            'sm-signature' => $this->determineSurveyMonkeySignature($payload, $apiKey),
        ];

        $this
            ->postJson('webhooks/survey-monkey', $payload, $headers)
            ->assertStatus(400);

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertFalse(isset($webhookCall->payload['event_type']['id']));

        $this->assertEquals(['invalid_payload'], $webhookCall->payload);

        $this->assertEquals('Webhook call id `1` did not contain a type. Valid Survey Monkey webhook calls should always contain a type.', $webhookCall->exception['message']);

        Event::assertNotDispatched('surveymonkey-webhooks::my.type');

        $this->assertNull(cache('dummyjob'));
    }

    #[Test]
    public function a_request_with_a_config_key_will_use_the_correct_signing_secret(): void
    {
        config()->set('surveymonkey-webhooks.signing_secret', 'secret1');
        config()->set('surveymonkey-webhooks.signing_secret_somekey', 'secret2');

        $payload = [
            'event_type' => 'my.type',
        ];

        $headers = [
            'sm-apikey' => $apiKey = 'api-key',
            'sm-signature' => $this->determineSurveyMonkeySignature($payload, $apiKey, 'somekey'),
        ];

        $this
            ->postJson('webhooks/survey-monkey/somekey', $payload, $headers)
            ->assertSuccessful();
    }
}
