import { useState } from 'react';
import GlobeView from './components/GlobeView';
import { SidebarLeft } from './components/UI/SidebarLeft';
import { SidebarRight } from './components/UI/SidebarRight';
import { BottomToolbar } from './components/UI/BottomToolbar';
import type { LocationDest } from './components/UI/BottomToolbar';
import type { VisualMode } from './components/UI/BottomToolbar';
import { CCTVPanopticPanel } from './components/CCTVPanopticPanel';
import { ShieldAlert } from 'lucide-react';

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

      {/* Top Header */}
      <div className="absolute top-0 left-0 w-full p-4 z-20 flex justify-between items-start pointer-events-none">
        <div className="flex items-center gap-3 glass-panel px-4 py-2 rounded-md pointer-events-auto border-l-4 border-l-[#ff3333]">
          <ShieldAlert className="text-[#ff3333] animate-pulse" size={24} />
          <div>
            <div className="text-[#ff3333] font-bold tracking-widest text-sm">GLOBAL THREAT VECTOR</div>
            <div className="text-gray-400 text-xs">AUSTIN COMMAND CENTER - SECURE UPLINK</div>
          </div>
        </div>

        <div className="glass-panel px-4 py-2 rounded-md text-right pointer-events-auto border-r-4 border-r-[#00ff41]">
          <div className="text-[#00ff41] font-bold text-sm tracking-widest">{new Date().toISOString().split('T')[0]} - LIVE</div>
          <div className="text-gray-400 text-xs">DEFCON 5 ACTIVE</div>
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
