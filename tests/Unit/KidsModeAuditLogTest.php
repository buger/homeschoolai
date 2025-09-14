<?php

namespace Tests\Unit;

use App\Models\KidsModeAuditLog;
use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KidsModeAuditLogTest extends TestCase
{
    private $userId;

    private $childId;

    private $ipAddress;

    private $userAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = 'test-user-'.uniqid();
        $this->childId = 123;
        $this->ipAddress = '192.168.1.100';
        $this->userAgent = 'Mozilla/5.0 (Test Browser)';
    }

    #[Test]
    public function it_can_log_security_events()
    {
        $insertedData = null;

        // Mock SupabaseClient with flexible return type
        $this->mock(SupabaseClient::class, function ($mock) use (&$insertedData) {
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturnSelf();
            $mock->shouldReceive('insert')
                ->once()
                ->andReturnUsing(function ($data) use (&$insertedData, $mock) {
                    $insertedData = $data;

                    return $mock;
                });
        });

        $metadata = [
            'session_duration_minutes' => 45,
            'previous_failed_attempts' => 2,
        ];

        KidsModeAuditLog::logEvent(
            'exit_success',
            $this->userId,
            $this->childId,
            $this->ipAddress,
            $this->userAgent,
            $metadata
        );

        // Verify the correct data was logged
        $this->assertNotNull($insertedData);
        $this->assertEquals('exit_success', $insertedData['action']);
        $this->assertEquals($this->userId, $insertedData['user_id']);
        $this->assertEquals($this->childId, $insertedData['child_id']);
        $this->assertEquals($this->ipAddress, $insertedData['ip_address']);
        $this->assertEquals($this->userAgent, $insertedData['user_agent']);
        $this->assertEquals(json_encode($metadata), $insertedData['metadata']);
        $this->assertNotNull($insertedData['created_at']);
        $this->assertNotNull($insertedData['updated_at']);
    }

    #[Test]
    public function it_handles_null_values_gracefully()
    {
        $insertedData = null;

        $this->mock(SupabaseClient::class, function ($mock) use (&$insertedData) {
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturnSelf();
            $mock->shouldReceive('insert')
                ->once()
                ->andReturnUsing(function ($data) use (&$insertedData, $mock) {
                    $insertedData = $data;

                    return $mock;
                });
        });

        KidsModeAuditLog::logEvent(
            'pin_failed',
            $this->userId,
            null, // No child ID
            null, // No IP address
            null, // No user agent
            []    // Empty metadata
        );

        $this->assertEquals('pin_failed', $insertedData['action']);
        $this->assertEquals($this->userId, $insertedData['user_id']);
        $this->assertNull($insertedData['child_id']);
        $this->assertNull($insertedData['ip_address']);
        $this->assertNull($insertedData['user_agent']);
        $this->assertNull($insertedData['metadata']);
    }

    #[Test]
    public function it_handles_database_errors_without_failing()
    {
        // Mock SupabaseClient to throw an exception
        $this->mock(SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturnSelf();
            $mock->shouldReceive('insert')->once()->andThrow(new \Exception('Database error'));
        });

        // Mock Log to verify error is logged
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to create kids mode audit log', \Mockery::type('array'));

        // This should not throw an exception
        KidsModeAuditLog::logEvent(
            'test_event',
            $this->userId,
            $this->childId,
            $this->ipAddress,
            $this->userAgent,
            ['test' => 'data']
        );

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_retrieve_user_audit_logs()
    {
        $mockLogs = [
            [
                'id' => 1,
                'user_id' => $this->userId,
                'child_id' => $this->childId,
                'action' => 'enter',
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'metadata' => '{"child_name":"Test Child"}',
                'created_at' => now()->toISOString(),
            ],
            [
                'id' => 2,
                'user_id' => $this->userId,
                'child_id' => $this->childId,
                'action' => 'exit_success',
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'metadata' => '{"session_duration_minutes":30}',
                'created_at' => now()->subHour()->toISOString(),
            ],
        ];

        $this->mock(SupabaseClient::class, function ($mock) use ($mockLogs) {
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturnSelf();
            $mock->shouldReceive('select')->with('*')->andReturnSelf();
            $mock->shouldReceive('eq')->with('user_id', $this->userId)->andReturnSelf();
            $mock->shouldReceive('order')->with('created_at', 'desc')->andReturnSelf();
            $mock->shouldReceive('limit')->with(50)->andReturnSelf();
            $mock->shouldReceive('get')->once()->andReturn(collect($mockLogs));
        });

        $logs = KidsModeAuditLog::forUser($this->userId);

        $this->assertCount(2, $logs);
        $this->assertEquals('enter', $logs[0]['action']);
        $this->assertEquals('exit_success', $logs[1]['action']);
    }

    #[Test]
    public function it_can_count_recent_failed_attempts()
    {
        $mockFailedLogs = [
            ['id' => 1], ['id' => 2], ['id' => 3], // 3 failed attempts
        ];

        $this->mock(SupabaseClient::class, function ($mock) use ($mockFailedLogs) {
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturnSelf();
            $mock->shouldReceive('select')->with('id')->andReturnSelf();
            $mock->shouldReceive('eq')->with('user_id', $this->userId)->andReturnSelf();
            $mock->shouldReceive('eq')->with('action', 'pin_failed')->andReturnSelf();
            $mock->shouldReceive('gte')->with('created_at', \Mockery::type('string'))->andReturnSelf();
            $mock->shouldReceive('get')->once()->andReturn(collect($mockFailedLogs));
        });

        $count = KidsModeAuditLog::getRecentFailedAttempts($this->userId, 60);

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function it_can_count_failed_attempts_by_ip()
    {
        $mockIpLogs = [
            ['id' => 1], ['id' => 2], // 2 failed attempts from IP
        ];

        $this->mock(SupabaseClient::class, function ($mock) use ($mockIpLogs) {
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturnSelf();
            $mock->shouldReceive('select')->with('id')->andReturnSelf();
            $mock->shouldReceive('eq')->with('ip_address', $this->ipAddress)->andReturnSelf();
            $mock->shouldReceive('eq')->with('action', 'pin_failed')->andReturnSelf();
            $mock->shouldReceive('gte')->with('created_at', \Mockery::type('string'))->andReturnSelf();
            $mock->shouldReceive('get')->once()->andReturn(collect($mockIpLogs));
        });

        $count = KidsModeAuditLog::getFailedAttemptsByIP($this->ipAddress, 60);

        $this->assertEquals(2, $count);
    }

    #[Test]
    public function it_handles_query_errors_gracefully()
    {
        // Mock SupabaseClient to throw an exception on query
        $this->mock(SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturnSelf();
            $mock->shouldReceive('select')->andReturnSelf();
            $mock->shouldReceive('eq')->andReturnSelf();
            $mock->shouldReceive('order')->andReturnSelf();
            $mock->shouldReceive('limit')->andReturnSelf();
            $mock->shouldReceive('get')->once()->andThrow(new \Exception('Query error'));
        });

        // Mock Log to verify error is logged
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to fetch kids mode audit logs', \Mockery::type('array'));

        $logs = KidsModeAuditLog::forUser($this->userId);

        // Should return empty array on error
        $this->assertEquals([], $logs);
    }

    #[Test]
    public function it_validates_time_ranges_for_failed_attempts()
    {
        $this->mock(SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturnSelf();
            $mock->shouldReceive('select')->with('id')->andReturnSelf();
            $mock->shouldReceive('eq')->with('user_id', $this->userId)->andReturnSelf();
            $mock->shouldReceive('eq')->with('action', 'pin_failed')->andReturnSelf();
            $mock->shouldReceive('gte')
                ->with('created_at', \Mockery::on(function ($timestamp) {
                    // Verify the timestamp is approximately 30 minutes ago
                    $expected = Carbon::now()->subMinutes(30);
                    $actual = Carbon::parse($timestamp);

                    return $actual->diffInMinutes($expected) <= 1;
                }))
                ->andReturnSelf();
            $mock->shouldReceive('get')->once()->andReturn(collect([]));
        });

        KidsModeAuditLog::getRecentFailedAttempts($this->userId, 30);
    }

    #[Test]
    public function it_properly_encodes_metadata()
    {
        $insertedData = null;

        $this->mock(SupabaseClient::class, function ($mock) use (&$insertedData) {
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturnSelf();
            $mock->shouldReceive('insert')
                ->once()
                ->andReturnUsing(function ($data) use (&$insertedData, $mock) {
                    $insertedData = $data;

                    return $mock;
                });
        });

        $complexMetadata = [
            'attempts' => 5,
            'locked' => true,
            'lockout_minutes' => 15,
            'user_failed_attempts_1h' => 3,
            'ip_failed_attempts_1h' => 8,
            'nested_data' => [
                'browser' => 'Chrome',
                'version' => '120.0.0.0',
            ],
        ];

        KidsModeAuditLog::logEvent(
            'pin_failed',
            $this->userId,
            $this->childId,
            $this->ipAddress,
            $this->userAgent,
            $complexMetadata
        );

        $this->assertEquals(json_encode($complexMetadata), $insertedData['metadata']);
        $this->assertTrue(is_string($insertedData['metadata']));
    }
}
