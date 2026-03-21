<?php

namespace BinaryCats\SurveyMonkeyWebhooks\Tests;

use BinaryCats\SurveyMonkeyWebhooks\ProcessSurveyMonkeyWebhookJob;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\WebhookClient\Models\WebhookCall;

class SurveyMonkeyWebhookCallTest extends TestCase
{
    private ProcessSurveyMonkeyWebhookJob $processSurveyMonkeyWebhookJob;

    private WebhookCall $webhookCall;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['surveymonkey-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->webhookCall = WebhookCall::create([
            'name' => 'survey-monkey',
            'payload' => [
                'event_type' => 'my_type',
            ],
            'url' => '/webhooks/survey-monkey',
        ]);

        $this->processSurveyMonkeyWebhookJob = new ProcessSurveyMonkeyWebhookJob($this->webhookCall);
    }

    #[Test]
    public function it_will_fire_off_the_configured_job(): void
    {
        $this->processSurveyMonkeyWebhookJob->handle();

        $this->assertEquals($this->webhookCall->id, cache('dummyjob')->id);
    }

    #[Test]
    public function it_will_not_dispatch_a_job_for_another_type(): void
    {
        config(['surveymonkey-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processSurveyMonkeyWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    #[Test]
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured(): void
    {
        config(['surveymonkey-webhooks.jobs' => []]);

        $this->processSurveyMonkeyWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    #[Test]
    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured(): void
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
