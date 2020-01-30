<?php

namespace BinaryCats\SurveyMonkeyWebhooks\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Spatie\WebhookClient\Models\WebhookCall;

class IntegrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        Route::surveyMonkeyWebhooks('webwooks/survey-monkey');
        Route::surveyMonkeyWebhooks('webwooks/survey-monkey/{configKey}');

        config(['surveymonkey-webhooks.jobs' => ['my_type' => DummyJob::class]]);
        cache()->clear();
    }

    /** @test */
    public function it_can_handle_a_valid_request()
    {
        $payload = [
            'event_type' => 'my.type',
        ];

        $headers = [
            'sm-apikey' => $apiKey = 'api-key',
            'sm-signature' => $this->determineSurveyMonkeySignature($payload, $apiKey),
        ];

        $this
            ->postJson('webwooks/survey-monkey', $payload, $headers)
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

    /** @test */
    public function a_request_with_an_invalid_signature_wont_be_logged()
    {
        $payload = [
            'event_type' => 'my.type',
        ];

        $headers = [
            'sm-apikey' => 'api-key',
            'sm-signature' => 'incorrect_signature',
        ];

        $this
            ->postJson('webwooks/survey-monkey', $payload, $headers)
            ->assertStatus(500);

        $this->assertCount(0, WebhookCall::get());

        Event::assertNotDispatched('surveymonkey-webhooks::my.type');

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function a_request_with_an_invalid_payload_will_be_logged_but_events_and_jobs_will_not_be_dispatched()
    {
        $payload = ['invalid_payload'];

        $headers = [
            'sm-apikey' => $apiKey = 'api-key',
            'sm-signature' => $this->determineSurveyMonkeySignature($payload, $apiKey),
        ];

        $this
            ->postJson('webwooks/survey-monkey', $payload, $headers)
            ->assertStatus(400);

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertFalse(isset($webhookCall->payload['event_type']['id']));

        $this->assertEquals(['invalid_payload'], $webhookCall->payload);

        $this->assertEquals('Webhook call id `1` did not contain a type. Valid Survey Monkey webhook calls should always contain a type.', $webhookCall->exception['message']);

        Event::assertNotDispatched('surveymonkey-webhooks::my.type');

        $this->assertNull(cache('dummyjob'));
    }

    /** @test * */
    public function a_request_with_a_config_key_will_use_the_correct_signing_secret()
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
            ->postJson('webwooks/survey-monkey/somekey', $payload, $headers)
            ->assertSuccessful();
    }
}
