<?php

namespace Tests\Feature;

use App\Models\Paper;
use App\Models\User;
use App\Services\AnalysisHttpClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalysisHttpBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_sends_contract_and_returns_validated_data_boundary(): void
    {
        config()->set('services.ai', ['url' => 'http://ai.test', 'token' => 'internal-token', 'timeout' => 15]);
        Http::fake(['http://ai.test/api/v1/analyze' => Http::response(['success' => true, 'data' => ['scores' => []]])]);
        $user = User::factory()->create();
        $paper = Paper::query()->create(['title' => 'Paper', 'file_path' => 'p.pdf', 'status' => 'UPLOADED', 'uploaded_by' => $user->id]);

        Storage::fake('paper-files');
        Storage::disk('paper-files')->put('p.pdf', '%PDF-private-content');
        $stream = Storage::disk('paper-files')->readStream('p.pdf');
        $data = app(AnalysisHttpClient::class)->analyze($paper, '123e4567-e89b-12d3-a456-426614174000', $stream);
        $this->assertSame(['scores' => []], $data);
        Http::assertSent(fn ($request) => $request->url() === 'http://ai.test/api/v1/analyze'
            && $request->hasHeader('Authorization', 'Bearer internal-token')
            && $request->hasHeader('X-Request-ID', '123e4567-e89b-12d3-a456-426614174000')
            && str_contains($request->body(), '123e4567-e89b-12d3-a456-426614174000')
            && str_contains($request->body(), (string) $paper->id)
            && str_contains($request->body(), '%PDF-private-content'));
    }
}
