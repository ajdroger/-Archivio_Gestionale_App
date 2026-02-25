import { Camera, Eye, Radio, Thermometer, Monitor } from 'lucide-react';

export type VisualMode = 'NORMAL' | 'CRT' | 'NVG' | 'FLIR' | 'THERMAL';

interface BottomToolbarProps {
    currentMode: VisualMode;
    setMode: (mode: VisualMode) => void;
}

export function BottomToolbar({ currentMode, setMode }: BottomToolbarProps) {
    const modes: { id: VisualMode; icon: any; label: string; color: string }[] = [
        { id: 'NORMAL', icon: Monitor, label: 'STANDARD OP', color: '#00f0ff' },
        { id: 'CRT', icon: Radio, label: 'CRT LEGACY', color: '#00ff41' },
        { id: 'NVG', icon: Eye, label: 'NIGHT VISION', color: '#00ff41' },
        { id: 'FLIR', icon: Camera, label: 'FLIR BW', color: '#ffffff' },
        { id: 'THERMAL', icon: Thermometer, label: 'THERMAL FLIR', color: '#ff3333' },
    ];

    return (
        <div className="absolute bottom-6 left-1/2 -translate-x-1/2 glass-panel rounded-full px-6 py-3 flex gap-4 z-10 shadow-[0_0_30px_rgba(0,0,0,0.8)] border border-gray-700">
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
    );
}
