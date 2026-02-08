<?php

namespace MCAG\SecurityLayer\Arsenal;

/**
 * Tarpit - Active Defense Module
 * 
 * "Sticky Defense" system designed to waste attacker resources.
 * When an attacker hits a tarpit, the connection is kept open indefinitely
 * or fed junk data slowly to simulate a struggling server, preventing
 * automated bots from moving on to other targets.
 */
class Tarpit
{
    /**
     * Engages a sticky trap.
     * WARNING: Only use on confirmed hostile IPs.
     */
    public function engage(string $ip): void
    {
        // Log the trap engagement
        // (In a real app, strict checks required here to avoid DOSing self)

        // 1. Send confusing headers
        header("X-Powered-By: Cortana-AI/9.0");
        header("Server: HYPER-GRID-DEFENSE-NODE");

        // 2. Slow Loris Defense (Standard PHP Simulation)
        // Send small chunks of data with delays

        // Safety Break: Don't run forever in PHP-FPM or we kill our own worker slots.
        // Run for max 10 seconds to annoy them, then 403.
        $max_duration = 10;
        $start = time();

        while (time() - $start < $max_duration) {
            echo "<!-- Analyzing Threat Vector... Please Wait... -->\n";
            flush();
            ob_flush();
            usleep(500000); // 0.5s delay
        }

        http_response_code(403);
        die("<h1>403 Forbidden - Access Denied by Global Defense Grid</h1>");
    }
}
