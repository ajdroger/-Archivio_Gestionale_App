import { useEffect, useState } from 'react';
import GlobeView from './components/GlobeView';
import { SidebarLeft } from './components/UI/SidebarLeft';
import { SidebarRight } from './components/UI/SidebarRight';
import { BottomToolbar } from './components/UI/BottomToolbar';
import { CCTVPanopticPanel } from './components/CCTVPanopticPanel';
import { Link as LinkIcon } from 'lucide-react';
import { useStore } from './store/useStore';

function App() {
  const { layers, toggleLayer, visualMode, fxSettings } = useStore();

  // ── Live REC Timestamp ──────────────────────────────
  const [timestamp, setTimestamp] = useState('');
  useEffect(() => {
    const tick = () => {
      const now = new Date();
      setTimestamp(now.toISOString().replace('T', ' ').substring(0, 19) + 'Z');
    };
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, []);

  return (
    <div className="w-screen h-screen bg-[#050b14] overflow-hidden font-mono fixed inset-0">

      {/* ═══ Top Header - Left ═══ */}
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
          <h2 className="text-xs font-sans font-bold text-[#00f0ff] mb-1">{visualMode}</h2>
          <p className="text-[10px] font-mono text-gray-300 m-0 leading-tight">
            SUMMARY: {visualMode} GLOBAL NEAR PENNYBACKER BRIDGE (AUSTIN)...
          </p>
        </div>
      </div>

      {/* ═══ Top Header - Right (Telemetria Live) ═══ */}
      <div className="absolute top-4 right-4 z-20 pointer-events-none text-right flex flex-col items-end gap-1">
        <div className="flex items-center gap-2 bg-gray-900/60 backdrop-blur-md border border-white/10 px-3 py-1.5 rounded">
          <div className="w-2 h-2 rounded-full bg-red-600 animate-pulse"></div>
          <span className="text-xs font-mono text-red-500 font-bold tracking-widest">
            REC {timestamp}
          </span>
        </div>
        <div className="text-[10px] font-mono text-gray-400 mt-1 mr-1">
          ORB: 47439 PASS: DESC-179
        </div>
      </div>

      {/* ═══ Bottom Header - Right (GSD/ALT) ═══ */}
      <div className="absolute bottom-6 right-6 z-20 pointer-events-none text-right">
        <div className="text-[10px] font-mono text-[#00f0ff] leading-relaxed drop-shadow-[0_0_2px_rgba(0,240,255,0.8)]">
          <div>GSD: 12255.14M NDIRS: 0.0</div>
          <div>ALT: 32680429M SUN: -34.8° EL</div>
        </div>
      </div>

      {/* ═══ Main 3D Globe ═══ */}
      <GlobeView />

      {/* ═══ UI Panels ═══ */}
      <div className="z-10 absolute inset-0 pointer-events-none">
        <div className="pointer-events-auto">
          <SidebarLeft />
          <SidebarRight />
          <BottomToolbar />

          {/* CCTV Panoptic Module */}
          {layers.cctv && (
            <CCTVPanopticPanel onClose={() => toggleLayer('cctv')} />
          )}
        </div>
      </div>

      {/* ═══ CSS Overlay FX ═══ */}
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
