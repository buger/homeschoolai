<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use App\Models\User;
use App\Services\TopicMaterialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TopicMaterialsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Subject $subject;

    protected Unit $unit;

    protected Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test subject
        $this->subject = Subject::create([
            'user_id' => $this->user->id,
            'name' => 'Test Subject',
            'color' => '#3B82F6',
        ]);

        // Create test unit
        $this->unit = Unit::create([
            'subject_id' => $this->subject->id,
            'name' => 'Test Unit',
            'description' => 'A test unit for learning materials',
        ]);

        // Create test topic
        $this->topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Test Topic',
            'description' => 'A test topic for learning materials',
            'estimated_minutes' => 45,
            'required' => true,
        ]);
    }

    public function test_topic_can_add_video_material()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('topics.materials.video', $this->topic->id), [
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'video_title' => 'Test Video',
            'video_description' => 'A test video for learning',
        ], [
            'HX-Request' => 'true',
        ]);

        $response->assertStatus(200);

        $this->topic->refresh();
        $this->assertTrue($this->topic->hasLearningMaterials());
        $this->assertEquals(1, $this->topic->getLearningMaterialsCount());

        $videos = $this->topic->getVideos();
        $this->assertCount(1, $videos);
        $this->assertEquals('Test Video', $videos[0]['title']);
        $this->assertEquals('youtube', $videos[0]['type']);
    }

    public function test_topic_can_add_link_material()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('topics.materials.link', $this->topic->id), [
            'link_url' => 'https://example.com/article',
            'link_title' => 'Test Article',
            'link_description' => 'A test article for learning',
        ], [
            'HX-Request' => 'true',
        ]);

        $response->assertStatus(200);

        $this->topic->refresh();
        $this->assertTrue($this->topic->hasLearningMaterials());

        $links = $this->topic->getLinks();
        $this->assertCount(1, $links);
        $this->assertEquals('Test Article', $links[0]['title']);
        $this->assertEquals('https://example.com/article', $links[0]['url']);
    }

    public function test_topic_can_upload_file_material()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this->post(route('topics.materials.file', $this->topic->id), [
            'file' => $file,
            'file_title' => 'Test Document',
        ], [
            'HX-Request' => 'true',
        ]);

        $response->assertStatus(200);

        $this->topic->refresh();
        $this->assertTrue($this->topic->hasLearningMaterials());

        $files = $this->topic->getFiles();
        $this->assertCount(1, $files);
        $this->assertEquals('Test Document', $files[0]['title']);
        $this->assertEquals('pdf', $files[0]['type']);

        // Verify file was stored
        Storage::disk('public')->assertExists($files[0]['path']);
    }

    public function test_topic_can_remove_materials()
    {
        $this->actingAs($this->user);

        // Add a video material
        $this->topic->addMaterial('videos', [
            'title' => 'Test Video',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'type' => 'youtube',
            'video_id' => 'dQw4w9WgXcQ',
        ]);

        $this->assertEquals(1, $this->topic->getLearningMaterialsCount());

        // Remove the material
        $response = $this->delete(route('topics.materials.remove', [$this->topic->id, 'videos', 0]), [], [
            'HX-Request' => 'true',
        ]);
        $response->assertStatus(200);

        $this->topic->refresh();
        $this->assertFalse($this->topic->hasLearningMaterials());
    }

    public function test_video_url_validation()
    {
        $service = new TopicMaterialService;

        // Test YouTube URL
        $videoData = $service->processVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Test');
        $this->assertEquals('youtube', $videoData['type']);
        $this->assertEquals('dQw4w9WgXcQ', $videoData['video_id']);

        // Test Vimeo URL
        $videoData = $service->processVideoUrl('https://vimeo.com/123456789', 'Test');
        $this->assertEquals('vimeo', $videoData['type']);
        $this->assertEquals('123456789', $videoData['video_id']);

        // Test invalid URL
        $this->expectException(\InvalidArgumentException::class);
        $service->processVideoUrl('https://badsite.com/video', 'Test');
    }

    public function test_file_validation()
    {
        $service = new TopicMaterialService;

        // Test valid file
        $validFile = UploadedFile::fake()->create('test.pdf', 100);
        $this->assertNull($service->validateFile($validFile));

        // Test oversized file
        $oversizedFile = UploadedFile::fake()->create('huge.pdf', 11 * 1024); // 11MB
        $this->expectException(\InvalidArgumentException::class);
        $service->validateFile($oversizedFile);
    }

    public function test_unauthorized_access_blocked()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->post(route('topics.materials.video', $this->topic->id), [
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'video_title' => 'Test Video',
        ], [
            'HX-Request' => 'true',
        ]);

        $response->assertStatus(403);
    }

    protected function tearDown(): void
    {
        Storage::fake('public');
        parent::tearDown();
    }
}
