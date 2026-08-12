<?php

namespace Volcy\Translator\Tests;

use PHPUnit\Framework\TestCase;
use Volcy\Translator\Drivers\BladeDriver;
use Volcy\Translator\IdStrategies\HashIdStrategy;
use Volcy\Translator\IdStrategies\ExplicitIdStrategy;

class BladeDriverTest extends TestCase
{
    public function testBladeDriverWithHashStrategy()
    {
        $driver = new BladeDriver(new HashIdStrategy());
        
        $content = '<h1>Hello World</h1>';
        $result = $driver->index('views/home.blade.php', $content);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('driver', $result);
        $this->assertArrayHasKey('items', $result);
        $this->assertEquals('blade', $result['driver']);
        $this->assertIsArray($result['items']);
        $this->assertNotEmpty($result['items']);
        
        // Check that items have expected structure
        $item = $result['items'][0];
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('text', $item);
        $this->assertArrayHasKey('type', $item);
        $this->assertEquals('Hello World', $item['text']);
    }
    
    public function testBladeDriverWithExplicitStrategy()
    {
        $driver = new BladeDriver(new ExplicitIdStrategy());
        
        $content = '<h1 data-i18n="home.title">Hello World</h1>';
        $result = $driver->index('views/home.blade.php', $content);
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result['items']);
        
        $item = $result['items'][0];
        $this->assertArrayHasKey('translation_id', $item);
        $this->assertEquals('home.title', $item['translation_id']);
        $this->assertEquals('home.title', $item['id']); // Explicit ID should be used as the ID
    }
    
    public function testBladeDriverExtractsPhpHelpers()
    {
        $driver = new BladeDriver(new HashIdStrategy());
        
        $content = "{{ __('Welcome back') }}";
        $result = $driver->index('views/home.blade.php', $content);
        
        $this->assertNotEmpty($result['items']);
        
        $phpItem = $result['items'][0];
        $this->assertEquals('php', $phpItem['type']);
        $this->assertEquals('Welcome back', $phpItem['text']);
    }
    
    public function testBladeDriverExtractsAttributes()
    {
        $driver = new BladeDriver(new HashIdStrategy());
        
        $content = '<input type="text" placeholder="Enter your name">';
        $result = $driver->index('views/form.blade.php', $content);
        
        $this->assertNotEmpty($result['items']);
        
        $attributeItem = $result['items'][0];
        $this->assertEquals('attribute', $attributeItem['type']);
        $this->assertEquals('placeholder', $attributeItem['attribute']);
        $this->assertEquals('Enter your name', $attributeItem['text']);
    }
}