import { Camera, Eye, Radio, Monitor, Crosshair, Sparkles, CloudSnow, Moon } from 'lucide-react';
import { useStore } from '../../store/useWorldViewStore';

export function BottomToolbar() {
    const { visualMode, setVisualMode, setTargetLocation } = useStore();

    const locations = [
        { name: 'Austin', lat: 30.2672, lng: -97.7431, alt: 5000 },
        { name: 'San Francisco', lat: 37.7749, lng: -122.4194, alt: 5000 },
        { name: 'New York', lat: 40.7128, lng: -74.0060, alt: 7500 },
        { name: 'Tokyo', lat: 35.6762, lng: 139.6503, alt: 8000 },
        { name: 'London', lat: 51.5074, lng: -0.1278, alt: 6000 },
        { name: 'Paris', lat: 48.8566, lng: 2.3522, alt: 5500 },
        { name: 'Dubai', lat: 25.2048, lng: 55.2708, alt: 7000 },
        { name: 'Washington DC', lat: 38.8951, lng: -77.0364, alt: 5000 }
    ];

    const modes = [
        { id: 'NORMAL', icon: Monitor, label: 'Normal' },
        { id: 'CRT', icon: Radio, label: 'CRT' },
        { id: 'NVG', icon: Eye, label: 'NVG' },
        { id: 'FLIR', icon: Camera, label: 'FLIR' },
        { id: 'ANIME', icon: Sparkles, label: 'Anime' },
        { id: 'NOIR', icon: Moon, label: 'Noir' },
        { id: 'SNOW', icon: CloudSnow, label: 'Snow' },
        { id: 'AI', icon: Crosshair, label: 'AI' }
    ];

    return (
        <div className="absolute bottom-6 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 w-full max-w-5xl z-10 select-none pointer-events-none">

            {/* Locations Row */}
            <div className="flex bg-gray-900/40 backdrop-blur-md rounded border border-white/10 p-1 pointer-events-auto">
                {locations.map((loc) => (
                    <button
                        key={loc.name}
                        type="button"
                        onClick={(e) => {
                            e.preventDefault();
                            setTargetLocation({ ...loc, timestamp: Date.now() });
                        }}
                        className="px-4 py-1 flex items-center gap-2 text-gray-400 font-sans text-xs hover:text-white hover:bg-white/10 rounded transition-colors"
                    >
                        {loc.name}
                    </button>
                ))}
            </div>

            {/* Style Presets Row */}
            <div className="flex bg-gray-900/60 backdrop-blur-md rounded-lg border border-white/10 p-2 pointer-events-auto gap-1">
                {modes.map((mode) => {
                    const Icon = mode.icon;
                    const isActive = visualMode === mode.id;
                    return (
                        <button
                            key={mode.id}
                            type="button"
                            onClick={(e) => {
                                e.preventDefault();
                                setVisualMode(mode.id);
                            }}
                            className={`flex flex-col items-center justify-center gap-1 w-[70px] py-1.5 rounded transition-colors
                                ${isActive ? 'bg-[#00f0ff]/20 border border-[#00f0ff]/50 text-[#00f0ff]' : 'bg-transparent text-gray-400 border border-transparent hover:bg-white/10 hover:text-white'}
                            `}
                        >
                            <Icon size={16} />
                            <span className="text-[10px] font-sans font-bold tracking-wider">{mode.label}</span>
                        </button>
                    );
                })}
            </div>

        </div>
    );
}
