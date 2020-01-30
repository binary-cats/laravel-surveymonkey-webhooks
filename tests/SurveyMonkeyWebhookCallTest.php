<?php

namespace BinaryCats\SurveyMonkeyWebhooks\Tests;

use BinaryCats\SurveyMonkeyWebhooks\ProcessSurveyMonkeyWebhookJob;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookClient\Models\WebhookCall;

class SurveyMonkeyWebhookCallTest extends TestCase
{
    /** @var \BinaryCats\SurveyMonkeyWebhooks\ProcessSurveyMonkeyWebhookJob */
    public $processSurveyMonkeyWebhookJob;

    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['surveymonkey-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->webhookCall = WebhookCall::create([
            'name' => 'survey-monkey',
            'payload' => [
                'event_type' => 'my_type',
            ],
        ]);

        $this->processSurveyMonkeyWebhookJob = new ProcessSurveyMonkeyWebhookJob($this->webhookCall);
    }

    /** @test */
    public function it_will_fire_off_the_configured_job()
    {
        $this->processSurveyMonkeyWebhookJob->handle();

        $this->assertEquals($this->webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['surveymonkey-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processSurveyMonkeyWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['surveymonkey-webhooks.jobs' => []]);

        $this->processSurveyMonkeyWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured()
    {
        config(['surveymonkey-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processSurveyMonkeyWebhookJob->handle();

        $webhookCall = $this->webhookCall;

        Event::assertDispatched("surveymonkey-webhooks::{$webhookCall->payload['event_type']}", function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertNull(cache('dummyjob'));
    }
}
