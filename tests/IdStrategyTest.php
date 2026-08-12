<?php

namespace Volcy\Translator\Tests;

use PHPUnit\Framework\TestCase;
use Volcy\Translator\IdStrategies\ExplicitIdStrategy;
use Volcy\Translator\IdStrategies\HashIdStrategy;
use Volcy\Translator\IdStrategies\TagPathIdStrategy;

class IdStrategyTest extends TestCase
{
    public function testHashIdStrategy()
    {
        $strategy = new HashIdStrategy();
        
        $item = [
            'type' => 'text',
            'text' => 'Hello World',
            'path' => 'views/home.blade.php',
            'tag_path' => 'div > h1',
            'attribute' => null,
        ];
        
        $id = $strategy->generateId($item);
        
        // Hash IDs should be 40 character SHA1 hashes
        $this->assertEquals(40, strlen($id));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $id);
        
        // Same content should generate same hash
        $id2 = $strategy->generateId($item);
        $this->assertEquals($id, $id2);
    }
    
    public function testTagPathIdStrategy()
    {
        $strategy = new TagPathIdStrategy();
        
        $item1 = [
            'type' => 'text',
            'text' => 'Hello World',
            'path' => 'views/home.blade.php',
            'tag_path' => 'div > h1',
            'attribute' => null,
        ];
        
        $item2 = [
            'type' => 'text',
            'text' => 'Hello World',
            'path' => 'views/home.blade.php',
            'tag_path' => 'div > p',
            'attribute' => null,
        ];
        
        $id1 = $strategy->generateId($item1);
        $id2 = $strategy->generateId($item2);
        
        // Same text in different tag paths should generate different IDs
        $this->assertNotEquals($id1, $id2);
        
        // Both should be valid SHA1 hashes
        $this->assertEquals(40, strlen($id1));
        $this->assertEquals(40, strlen($id2));
    }
    
    public function testExplicitIdStrategy()
    {
        $strategy = new ExplicitIdStrategy();
        
        $itemWithExplicitId = [
            'type' => 'text',
            'text' => 'Hello World',
            'path' => 'views/home.blade.php',
            'tag_path' => 'div > h1',
            'attribute' => null,
            'translation_id' => 'home.title',
        ];
        
        $itemWithoutExplicitId = [
            'type' => 'text',
            'text' => 'Hello World',
            'path' => 'views/home.blade.php',
            'tag_path' => 'div > h1',
            'attribute' => null,
        ];
        
        $id1 = $strategy->generateId($itemWithExplicitId);
        $id2 = $strategy->generateId($itemWithoutExplicitId);
        
        // Should use explicit ID when available
        $this->assertEquals('home.title', $id1);
        
        // Should fall back to hash when no explicit ID
        $this->assertEquals(40, strlen($id2));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $id2);
    }
    
    public function testExplicitIdStrategyNames()
    {
        $this->assertEquals('hash', (new HashIdStrategy())->getName());
        $this->assertEquals('tag_path', (new TagPathIdStrategy())->getName());
        $this->assertEquals('explicit', (new ExplicitIdStrategy())->getName());
    }
}