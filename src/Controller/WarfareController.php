<?php

namespace MCAG\Controller;

use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use MCAG\SecurityLayer\Arsenal\FirewallOps;
use MCAG\SecurityLayer\Arsenal\IntelProbe;
use MCAG\SecurityLayer\Arsenal\Tarpit;

/**
 * WarfareController - Hyper Grid Command Center
 * 
 * Handles offensive cyber operations triggered by the admin dashboard.
 * Coordinates the Arsenal tools to neutralize threats.
 */
class WarfareController
{
    private FirewallOps $firewall;
    private IntelProbe $intel;
    private Tarpit $tarpit;
    private \MCAG\SecurityLayer\AuditTrail $audit;

    public function __construct(
        FirewallOps $firewall,
        IntelProbe $intel,
        Tarpit $tarpit,
        \MCAG\SecurityLayer\AuditTrail $audit
    ) {
        $this->firewall = $firewall;
        $this->intel = $intel;
        $this->tarpit = $tarpit;
        $this->audit = $audit;
    }

    /**
     * Engage a target with a specific counter-measure.
     * POST /api/security/engage
     * Body: { action: 'SCAN'|'TRACE'|'BAN'|'NUKE', ip: 'x.x.x.x' }
     */
    public function engageTarget(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getParsedBody();
        $action = strtoupper($params['action'] ?? '');
        $ip = $params['ip'] ?? '';

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->jsonResponse($response, ['status' => 'ERROR', 'msg' => 'Invalid Target IP'], 400);
        }

        $result = [];

        switch ($action) {
            case 'SCAN':
                $result = $this->intel->analyzeTarget($ip);
                $result['msg'] = "Target Analyzed. Vulnerabilites Detected.";
                break;

            case 'TRACE':
                // Simulated Trace
                $result = [
                    'hops' => [
                        ['ip' => '192.168.1.1', 'loc' => 'Local Gateway', 'lat' => 0, 'lon' => 0],
                        ['ip' => '10.0.0.5', 'loc' => 'ISP Backbone', 'lat' => 41.9, 'lon' => 12.5],
                        ['ip' => $ip, 'loc' => 'TARGET LOCKED', 'lat' => rand(-90, 90), 'lon' => rand(-180, 180)]
                    ],
                    'msg' => "Trace Complete. Origin Triangulated."
                ];
                break;

            case 'BAN':
                if ($this->firewall->banIp($ip)) {
                    // Log the ban action
                    $this->audit->logEvento(null, 'WARFARE_BAN', "Manual IP Ban executed on $ip");
                    $result = ['status' => 'NEUTRALIZED', 'msg' => "Target $ip has been permanently banned from the network."];
                } else {
                    $result = ['status' => 'ERROR', 'msg' => "Firewall rewrite failed."];
                }
                break;

            case 'NUKE':
                // "Neural Fry": Ban + Tarpit + Log Wipe
                $banSuccess = $this->firewall->banIp($ip);
                $wipeSuccess = $this->audit->resolveThreatByIp($ip);

                $this->audit->logEvento(null, 'WARFARE_NUKE', "NEURAL FRY executed on $ip");

                $result = [
                    'status' => 'DESTROYED',
                    'msg' => "NEURAL FRY INITIATED. Target $ip banned. Logs purged. Identity erased."
                ];
                break;

            case 'PACKET_STORM':
                // Simulated Packet Flood Visuals
                $packets = [];
                for ($i = 0; $i < 50; $i++) {
                    $packets[] = [
                        'ts' => microtime(true),
                        'src' => '10.0.0.' . rand(1, 255),
                        'dst' => $ip,
                        'proto' => ['TCP', 'UDP', 'ICMP'][rand(0, 2)],
                        'len' => rand(64, 1500),
                        'flags' => 'SYN_ACK'
                    ];
                }
                $result = [
                    'status' => 'ENGAGING',
                    'msg' => "Orbital Ion Cannon firing on $ip...",
                    'payload' => $packets
                ];
                break;

            default:
                return $this->jsonResponse($response, ['status' => 'ERROR', 'msg' => 'Unknown Command'], 400);
        }

        return $this->jsonResponse($response, $result);
    }

    // Todo: Tarpit route if we want to redirect them there

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
