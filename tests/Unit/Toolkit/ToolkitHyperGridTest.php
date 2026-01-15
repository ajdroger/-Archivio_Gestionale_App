<?php

namespace Tests\Unit\Toolkit;

use PHPUnit\Framework\TestCase;

class ToolkitHyperGridTest extends TestCase
{
    private $scriptPath;

    protected function setUp(): void
    {
        $this->scriptPath = realpath(__DIR__ . '/../../../bin/debug_tools/test_dashboard.php');
    }

    public function testToolkitScriptExists()
    {
        $this->assertFileExists($this->scriptPath);
    }

    /**
     * Verify function definitions in the file content directly.
     * This avoids execution environment issues.
     */
    public function testToolkitContainsNewBackendFunctions()
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('function getGitInfo()', $content);
        $this->assertStringContainsString('function getLogTail', $content);
        $this->assertStringContainsString('function purgeCache()', $content);
        $this->assertStringContainsString('function scanTestsRecursive', $content);
    }

    public function testToolkitContainsHyperGridUI()
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('HYPER-GRID', $content);
        $this->assertStringContainsString('QUANTUM ENGINEERING DECK', $content);
        $this->assertStringContainsString('neon-blue', $content); // CSS Check
    }
}
