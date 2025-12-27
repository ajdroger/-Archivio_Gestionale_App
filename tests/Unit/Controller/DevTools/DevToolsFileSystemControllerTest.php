<?php

namespace Tests\Unit\Controller\DevTools;

use FratellanzaMilitare\Controller\DevTools\DevToolsFileSystemController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class DevToolsFileSystemControllerTest extends TestCase
{
    private $testDir;
    private $controller;

    protected function setUp(): void
    {
        // Create temporary test directory
        $this->testDir = __DIR__ . '/../../tmp_fs_test_' . uniqid();
        mkdir($this->testDir, 0777, true);

        // Populate with dummy files
        file_put_contents($this->testDir . '/test.txt', 'Hello World');
        mkdir($this->testDir . '/subdir');
        file_put_contents($this->testDir . '/subdir/inner.log', 'Log Data');

        $this->controller = new DevToolsFileSystemController($this->testDir);
    }

    protected function tearDown(): void
    {
        // Cleanup test dir
        $this->recursiveRemove($this->testDir);
    }

    private function recursiveRemove($dir)
    {
        if (!is_dir($dir))
            return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveRemove("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testFsListReturnsCorrectStructure()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getParsedBody')->willReturn(['path' => '/']);

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $responseMock->method('getBody')->willReturn($streamMock);
        $responseMock->method('withHeader')->willReturnSelf();

        // Capture JSON output
        $streamMock->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                $valid = isset($data['items'])
                    && count($data['items']) >= 2
                    && $data['items'][0]['name'] === 'subdir' // Dirs first
                    && $data['items'][1]['name'] === 'test.txt';

                if (!$valid) {
                    echo "\nDebug Failure:\n";
                    var_dump($data);
                }
                return $valid;
            }));

        $this->controller->fsList($requestMock, $responseMock);
    }

    public function testFsReadReadsContent()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getParsedBody')->willReturn(['path' => '/test.txt']);

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $responseMock->method('getBody')->willReturn($streamMock);
        $responseMock->method('withHeader')->willReturnSelf();

        $streamMock->expects($this->once())
            ->method('write')
            ->with(json_encode(['content' => 'Hello World']));

        $this->controller->fsRead($requestMock, $responseMock);
    }

    public function testFsSaveUpdatesContent()
    {
        // Read existing first
        $this->assertEquals('Hello World', file_get_contents($this->testDir . '/test.txt'));

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getParsedBody')->willReturn([
            'path' => '/test.txt',
            'content' => 'New Content'
        ]);

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $responseMock->method('getBody')->willReturn($streamMock);
        $responseMock->method('withHeader')->willReturnSelf();

        $streamMock->expects($this->once())
            ->method('write')
            ->with(json_encode(['success' => true]));

        $this->controller->fsSave($requestMock, $responseMock);

        // Verify file change
        $this->assertEquals('New Content', file_get_contents($this->testDir . '/test.txt'));
    }

    public function testSecurityCantTraverseUp()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getParsedBody')->willReturn(['path' => '/../secret.txt']); // Attempt to go up

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $responseMock->method('getBody')->willReturn($streamMock);
        $responseMock->method('withHeader')->willReturnSelf();

        // Should return root listing or handle safely
        // In fsList context, it defaults to basePath.
        // Let's test checking the output is root content, not error, but staying in root.

        $streamMock->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['current'] === '/' || $data['current'] === '';
            }));

        $this->controller->fsList($requestMock, $responseMock);
    }
}
