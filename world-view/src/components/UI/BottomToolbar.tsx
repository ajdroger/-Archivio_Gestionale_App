import { Camera, Eye, Radio, Thermometer, Monitor, Crosshair } from 'lucide-react';

export type VisualMode = 'NORMAL' | 'CRT' | 'NVG' | 'FLIR' | 'THERMAL';
export interface LocationDest { lat: number; lng: number; alt: number; name: string; }

interface BottomToolbarProps {
    currentMode: VisualMode;
    setMode: (mode: VisualMode) => void;
    onJump: (loc: LocationDest) => void;
}

export function BottomToolbar({ currentMode, setMode, onJump }: BottomToolbarProps) {
    const modes: { id: VisualMode; icon: any; label: string; color: string }[] = [
        { id: 'NORMAL', icon: Monitor, label: 'STANDARD OP', color: '#00f0ff' },
        { id: 'CRT', icon: Radio, label: 'CRT LEGACY', color: '#00ff41' },
        { id: 'NVG', icon: Eye, label: 'NIGHT VISION', color: '#00ff41' },
        { id: 'FLIR', icon: Camera, label: 'FLIR', color: '#ffffff' },
        { id: 'THERMAL', icon: Thermometer, label: 'THERMAL', color: '#ff3333' },
    ];

    const locations: LocationDest[] = [
        { name: 'AUSTIN', lat: 30.2672, lng: -97.7431, alt: 800 },
        { name: 'D.C.', lat: 38.8951, lng: -77.0364, alt: 1500 },
        { name: 'LONDON', lat: 51.5074, lng: -0.1278, alt: 2500 }
    ];

    return (
        <div className="absolute bottom-6 left-1/2 -translate-x-1/2 flex flex-col items-center gap-3 z-10 w-full max-w-4xl px-4 pointer-events-none">

            {/* Quick Navigation Toolbar */}
            <div className="flex gap-2 pointer-events-auto">
                {locations.map((loc) => (
                    <button
                        key={loc.name}
                        onClick={() => onJump(loc)}
                        className="glass-panel px-4 py-1.5 flex items-center gap-2 text-gray-300 hover:text-white hover:border-[#ffb000] border border-gray-700 bg-[#00000080] transition-colors"
                        style={{ fontSize: '11px', letterSpacing: '2px', fontFamily: 'monospace' }}
                    >
                        <Crosshair size={14} className="text-[#ffb000]" /> {loc.name}
                    </button>
                ))}
            </div>

            {/* Visual Presets Toolbar */}
            <div className="glass-panel rounded-full px-6 py-3 flex gap-4 shadow-[0_0_30px_rgba(0,0,0,0.8)] border border-gray-700 pointer-events-auto">
                {modes.map((mode) => {
                    const Icon = mode.icon;
                    const isActive = currentMode === mode.id;
                    return (
                        <button
                            key={mode.id}
                            onClick={() => setMode(mode.id)}
                            className={`flex flex-col items-center gap-1 transition-all px-4 py-2 rounded-lg 
                  ${isActive ? 'bg-[#112233] scale-105' : 'hover:bg-[#ffffff10] opacity-50 hover:opacity-100'}
                `}
                            style={{
                                color: isActive ? mode.color : '#888',
                                boxShadow: isActive ? `0 0 15px ${mode.color}40` : 'none',
                                border: isActive ? `1px solid ${mode.color}60` : '1px solid transparent'
                            }}
                        >
                            <Icon size={18} />
                            <span className="text-[10px] font-bold tracking-wider">{mode.label}</span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
