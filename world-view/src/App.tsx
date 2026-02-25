import { useState } from 'react';
import GlobeView from './components/GlobeView';
import { SidebarLeft } from './components/UI/SidebarLeft';
import { SidebarRight } from './components/UI/SidebarRight';
import { BottomToolbar } from './components/UI/BottomToolbar';
import type { LocationDest } from './components/UI/BottomToolbar';
import type { VisualMode } from './components/UI/BottomToolbar';
import { CCTVPanopticPanel } from './components/CCTVPanopticPanel';
import { Link as LinkIcon } from 'lucide-react';

function App() {
  const [layers, setLayers] = useState({
    earthquakes: true,
    flights: false,
    satellites: false,
    cctv: false,
  });

  const [activeMode, setActiveMode] = useState<VisualMode>('CRT');
  const [targetLocation, setTargetLocation] = useState<LocationDest | null>(null);

  const [fxSettings, setFxSettings] = useState({
    distortion: 0.15,
    bloom: 0.8,
    scanlines: 0.5,
    noise: 0.05
  });

  const handleSetLayer = (key: keyof typeof layers, value: boolean) => {
    setLayers(prev => ({ ...prev, [key]: value }));
  };

  const handleSetFxSetting = (key: keyof typeof fxSettings, value: number) => {
    setFxSettings(prev => ({ ...prev, [key]: value }));
  };

  const handleModeChange = (mode: VisualMode) => {
    setActiveMode(mode);
    switch (mode) {
      case 'NORMAL':
        setFxSettings({ distortion: 0, bloom: 0.2, scanlines: 0, noise: 0 });
        break;
      case 'CRT':
        setFxSettings({ distortion: 0.15, bloom: 0.8, scanlines: 0.5, noise: 0.05 });
        break;
      case 'NVG':
        setFxSettings({ distortion: 0.05, bloom: 1.2, scanlines: 0.2, noise: 0.15 });
        break;
      case 'FLIR':
        setFxSettings({ distortion: 0, bloom: 1.0, scanlines: 0.1, noise: 0.08 });
        break;
      case 'THERMAL':
        setFxSettings({ distortion: 0, bloom: 1.5, scanlines: 0, noise: 0.02 });
        break;
    }
  };

  const handleJump = (loc: LocationDest) => {
    setTargetLocation(loc);
  };

  return (
    <div className="w-screen h-screen bg-[#050b14] overflow-hidden font-mono fixed inset-0">

      {/* Top Header - Left */}
      <div className="absolute top-4 left-4 z-20 pointer-events-none flex flex-col gap-1">
        <div className="flex items-center gap-2">
          <h1 className="text-3xl font-sans font-bold text-gray-200 tracking-wider m-0 leading-none">WORLDVIEW</h1>
          <span className="text-[10px] text-gray-500 font-sans tracking-widest bg-gray-900/50 px-2 py-0.5 rounded border border-white/10 flex items-center gap-1 backdrop-blur-sm">
            NO PLACE LEFT BEHIND <LinkIcon size={10} />
          </span>
        </div>
        <div className="text-[10px] font-mono text-gray-400 mt-2">
          <div>TOP SECRET // SI-TK // NOFORN</div>
          <div>KH11-4166 GPS-4117</div>
        </div>
        <div className="mt-4 bg-gray-900/40 backdrop-blur-md border border-white/10 p-2 rounded max-w-sm">
          <h2 className="text-xs font-sans font-bold text-[#00f0ff] mb-1">CRT</h2>
          <p className="text-[10px] font-mono text-gray-300 m-0 leading-tight">
            SUMMARY: CRT GLOBAL NEAR PENNYBACKER BRIDGE (AUSTIN)...
          </p>
        </div>
      </div>

      {/* Top Header - Right (Telemetria) */}
      <div className="absolute top-4 right-4 z-20 pointer-events-none text-right flex flex-col items-end gap-1">
        <div className="flex items-center gap-2 bg-gray-900/60 backdrop-blur-md border border-white/10 px-3 py-1.5 rounded">
          <div className="w-2 h-2 rounded-full bg-red-600 animate-pulse"></div>
          <span className="text-xs font-mono text-red-500 font-bold tracking-widest">
            REC 2026-02-12 02:49:11Z
          </span>
        </div>
        <div className="text-[10px] font-mono text-gray-400 mt-1 mr-1">
          ORB: 47439 PASS: DESC-179
        </div>
      </div>

      {/* Bottom Header - Right (Telemetria) */}
      <div className="absolute bottom-6 right-6 z-20 pointer-events-none text-right">
        <div className="text-[10px] font-mono text-[#00f0ff] leading-relaxed drop-shadow-[0_0_2px_rgba(0,240,255,0.8)]">
          <div>GSD: 12255.14M NDIRS: 0.0</div>
          <div>ALT: 32680429M SUN: -34.8° EL</div>
        </div>
      </div>

      {/* Main 3D Globe */}
      <GlobeView layers={layers} visualMode={activeMode} targetLocation={targetLocation} />

      {/* UI Panels */}
      <div className="z-10 absolute inset-0 pointer-events-none">
        <div className="pointer-events-auto">
          <SidebarLeft layers={layers} setLayer={handleSetLayer} />
          <SidebarRight fxSettings={fxSettings} setFxSetting={handleSetFxSetting} />
          <BottomToolbar currentMode={activeMode} setMode={handleModeChange} onJump={handleJump} />

          {/* Visual CV Module */}
          {layers.cctv && (
            <CCTVPanopticPanel onClose={() => handleSetLayer('cctv', false)} />
          )}
        </div>
      </div>

      {/* CSS Overlay FX */}
      {fxSettings.scanlines > 0 && (
        <div className="crt-overlay" style={{ opacity: fxSettings.scanlines }}></div>
      )}

      {fxSettings.noise > 0 && (
        <div
          className="fixed inset-0 pointer-events-none z-[9998] opacity-20 mix-blend-overlay"
          style={{
            backgroundImage: `url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E")`,
            opacity: fxSettings.noise * 2
          }}
        ></div>
      )}

      {/* Vignette */}
      <div className="fixed inset-0 pointer-events-none z-[9990] shadow-[inset_0_0_150px_rgba(0,0,0,0.9)]"></div>
    </div>
  );
}

export default App;
