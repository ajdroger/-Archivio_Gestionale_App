import GlobeView from './core/GlobeView';
import { SidebarLeft } from './ui/SidebarLeft';
import { SidebarRight } from './ui/SidebarRight';
import { BottomToolbar } from './ui/BottomToolbar';
import { CCTVPanopticPanel } from './ui/CCTVPanopticPanel';
import { HUDInfoBottomRight } from './ui/HUDInfoBottomRight';
import { HeaderTopLeft } from './ui/HeaderTopLeft';
import { TelemetryTopRight } from './ui/TelemetryTopRight';
import { useStore } from './core/store';

function App() {
  const { layers, toggleLayer, fxSettings } = useStore();



  return (
    <div className="w-screen h-screen bg-[#050b14] overflow-hidden font-mono fixed inset-0">

      {/* ═══ Top Header - Left ═══ */}
      <HeaderTopLeft />

      {/* ═══ Top Header - Right (Telemetria Live) ═══ */}
      <TelemetryTopRight />

      {/* ═══ Bottom Header - Right (GSD/ALT/MGRS) ═══ */}
      <HUDInfoBottomRight />

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
