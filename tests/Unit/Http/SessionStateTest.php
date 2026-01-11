<?php
declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\SessionState;
use PHPUnit\Framework\TestCase;

final class SessionStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear session before each test
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        $_SESSION = [];
        
        parent::tearDown();
    }

    /** @test */
    public function it_sets_back_url_in_session(): void
    {
        $url = 'https://example.com/back';
        
        SessionState::setBackUrl($url);
        
        $this->assertArrayHasKey('st_back_url', $_SESSION);
        $this->assertEquals($url, $_SESSION['st_back_url']);
    }

    /** @test */
    public function it_sets_paysite_title_in_session(): void
    {
        $title = 'My Payment Site';
        
        SessionState::setPaysiteTitle($title);
        
        $this->assertArrayHasKey('st_paysite_title', $_SESSION);
        $this->assertEquals($title, $_SESSION['st_paysite_title']);
    }

    /** @test */
    public function it_overwrites_existing_back_url(): void
    {
        $_SESSION['st_back_url'] = 'https://old-url.com';
        
        $newUrl = 'https://new-url.com';
        SessionState::setBackUrl($newUrl);
        
        $this->assertEquals($newUrl, $_SESSION['st_back_url']);
    }

    /** @test */
    public function it_overwrites_existing_paysite_title(): void
    {
        $_SESSION['st_paysite_title'] = 'Old Title';
        
        $newTitle = 'New Title';
        SessionState::setPaysiteTitle($newTitle);
        
        $this->assertEquals($newTitle, $_SESSION['st_paysite_title']);
    }

    /** @test */
    public function it_handles_empty_string_for_back_url(): void
    {
        SessionState::setBackUrl('');
        
        $this->assertArrayHasKey('st_back_url', $_SESSION);
        $this->assertEquals('', $_SESSION['st_back_url']);
    }

    /** @test */
    public function it_handles_empty_string_for_paysite_title(): void
    {
        SessionState::setPaysiteTitle('');
        
        $this->assertArrayHasKey('st_paysite_title', $_SESSION);
        $this->assertEquals('', $_SESSION['st_paysite_title']);
    }

    /** @test */
    public function it_handles_url_with_query_parameters(): void
    {
        $url = 'https://example.com/back?param1=value1&param2=value2';
        
        SessionState::setBackUrl($url);
        
        $this->assertEquals($url, $_SESSION['st_back_url']);
    }

    /** @test */
    public function it_handles_url_with_fragment(): void
    {
        $url = 'https://example.com/back#section';
        
        SessionState::setBackUrl($url);
        
        $this->assertEquals($url, $_SESSION['st_back_url']);
    }

    /** @test */
    public function it_handles_relative_url(): void
    {
        $url = '/relative/path';
        
        SessionState::setBackUrl($url);
        
        $this->assertEquals($url, $_SESSION['st_back_url']);
    }

    /** @test */
    public function it_handles_unicode_in_title(): void
    {
        $title = 'Szálláshely fizetés - Apartman foglalás';
        
        SessionState::setPaysiteTitle($title);
        
        $this->assertEquals($title, $_SESSION['st_paysite_title']);
    }

    /** @test */
    public function it_handles_special_characters_in_title(): void
    {
        $title = 'Payment & Booking <> "Site"';
        
        SessionState::setPaysiteTitle($title);
        
        $this->assertEquals($title, $_SESSION['st_paysite_title']);
    }

    /** @test */
    public function it_handles_long_url(): void
    {
        $url = 'https://example.com/' . str_repeat('path/', 100) . '?query=' . str_repeat('x', 500);
        
        SessionState::setBackUrl($url);
        
        $this->assertEquals($url, $_SESSION['st_back_url']);
    }

    /** @test */
    public function it_handles_long_title(): void
    {
        $title = str_repeat('Very Long Title ', 50);
        
        SessionState::setPaysiteTitle($title);
        
        $this->assertEquals($title, $_SESSION['st_paysite_title']);
    }

    /** @test */
    public function it_does_not_affect_other_session_variables(): void
    {
        $_SESSION['other_var'] = 'other_value';
        $_SESSION['another_var'] = 123;
        
        SessionState::setBackUrl('https://example.com');
        SessionState::setPaysiteTitle('Title');
        
        $this->assertEquals('other_value', $_SESSION['other_var']);
        $this->assertEquals(123, $_SESSION['another_var']);
    }

    /** @test */
    public function setBackUrl_is_static(): void
    {
        $reflection = new \ReflectionMethod(SessionState::class, 'setBackUrl');
        $this->assertTrue($reflection->isStatic());
    }

    /** @test */
    public function setPaysiteTitle_is_static(): void
    {
        $reflection = new \ReflectionMethod(SessionState::class, 'setPaysiteTitle');
        $this->assertTrue($reflection->isStatic());
    }

    /** @test */
    public function class_is_final(): void
    {
        $reflection = new \ReflectionClass(SessionState::class);
        $this->assertTrue($reflection->isFinal());
    }

    /** @test */
    public function methods_return_void(): void
    {
        $backUrlMethod = new \ReflectionMethod(SessionState::class, 'setBackUrl');
        $titleMethod = new \ReflectionMethod(SessionState::class, 'setPaysiteTitle');
        
        $this->assertEquals('void', $backUrlMethod->getReturnType()->getName());
        $this->assertEquals('void', $titleMethod->getReturnType()->getName());
    }

    /** @test */
    public function methods_accept_string_parameters(): void
    {
        $backUrlMethod = new \ReflectionMethod(SessionState::class, 'setBackUrl');
        $titleMethod = new \ReflectionMethod(SessionState::class, 'setPaysiteTitle');
        
        $backUrlParams = $backUrlMethod->getParameters();
        $titleParams = $titleMethod->getParameters();
        
        $this->assertCount(1, $backUrlParams);
        $this->assertCount(1, $titleParams);
        
        $this->assertEquals('string', $backUrlParams[0]->getType()->getName());
        $this->assertEquals('string', $titleParams[0]->getType()->getName());
    }

    /** @test */
    public function it_uses_st_prefix_for_session_keys(): void
    {
        SessionState::setBackUrl('url');
        SessionState::setPaysiteTitle('title');
        
        // Verify keys start with 'st_' prefix
        $this->assertArrayHasKey('st_back_url', $_SESSION);
        $this->assertArrayHasKey('st_paysite_title', $_SESSION);
        
        $this->assertStringStartsWith('st_', 'st_back_url');
        $this->assertStringStartsWith('st_', 'st_paysite_title');
    }
}