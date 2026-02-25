import { Layers, Activity, Plane, Satellite, Video } from 'lucide-react';

interface SidebarLeftProps {
    layers: {
        earthquakes: boolean;
        flights: boolean;
        satellites: boolean;
        cctv: boolean;
    };
    setLayer: (key: keyof SidebarLeftProps['layers'], value: boolean) => void;
}

export function SidebarLeft({ layers, setLayer }: SidebarLeftProps) {
    const toggle = (key: keyof typeof layers) => setLayer(key, !layers[key]);

    return (
        <div className="absolute top-1/4 left-6 glass-panel rounded-lg w-64 p-4 text-xs z-10 select-none shadow-[0_0_20px_rgba(0,255,65,0.15)] border border-[rgba(0,255,65,0.3)] bg-gradient-to-b from-[#0a1428cc] to-[#050b14cc]">
            <div className="flex items-center gap-2 mb-4 text-[#00f0ff] uppercase font-bold tracking-widest border-b border-[#00f0ff40] pb-2">
                <Layers size={16} /> Data Layers
            </div>

            <div className="space-y-4">
                {/* Satellites */}
                <div className="flex items-center justify-between cursor-pointer group" onClick={() => toggle('satellites')}>
                    <div className="flex items-center gap-3">
                        <Satellite size={16} className={layers.satellites ? 'text-[#00ff41]' : 'text-gray-500'} />
                        <span className={layers.satellites ? 'text-white' : 'text-gray-400'}>ORBITAL TELEMETRY</span>
                    </div>
                    <div className={`w-8 h-4 rounded-full border border-[rgba(0,255,65,0.5)] flex items-center p-[1px] transition-colors ${layers.satellites ? 'bg-[#00ff4120]' : 'bg-transparent'}`}>
                        <div className={`w-3 h-3 rounded-full bg-[#00ff41] transition-transform ${layers.satellites ? 'translate-x-4 shadow-[0_0_8px_#00ff41]' : 'bg-gray-600'}`}></div>
                    </div>
                </div>

                {/* Flights */}
                <div className="flex items-center justify-between cursor-pointer group" onClick={() => toggle('flights')}>
                    <div className="flex items-center gap-3">
                        <Plane size={16} className={layers.flights ? 'text-[#00f0ff]' : 'text-gray-500'} />
                        <span className={layers.flights ? 'text-white' : 'text-gray-400'}>CIVIL AVIATION (ADS-B)</span>
                    </div>
                    <div className={`w-8 h-4 rounded-full border border-[rgba(0,240,255,0.5)] flex items-center p-[1px] transition-colors ${layers.flights ? 'bg-[#00f0ff20]' : 'bg-transparent'}`}>
                        <div className={`w-3 h-3 rounded-full bg-[#00f0ff] transition-transform ${layers.flights ? 'translate-x-4 shadow-[0_0_8px_#00f0ff]' : 'bg-gray-600'}`}></div>
                    </div>
                </div>

                {/* Earthquakes */}
                <div className="flex items-center justify-between cursor-pointer group" onClick={() => toggle('earthquakes')}>
                    <div className="flex items-center gap-3">
                        <Activity size={16} className={layers.earthquakes ? 'text-[#ff3333]' : 'text-gray-500'} />
                        <span className={layers.earthquakes ? 'text-white' : 'text-gray-400'}>SEISMIC ACTIVITY</span>
                    </div>
                    <div className={`w-8 h-4 rounded-full border border-[rgba(255,51,51,0.5)] flex items-center p-[1px] transition-colors ${layers.earthquakes ? 'bg-[#ff333320]' : 'bg-transparent'}`}>
                        <div className={`w-3 h-3 rounded-full bg-[#ff3333] transition-transform ${layers.earthquakes ? 'translate-x-4 shadow-[0_0_8px_#ff3333]' : 'bg-gray-600'}`}></div>
                    </div>
                </div>

                {/* CCTV Mesh */}
                <div className="flex items-center justify-between cursor-pointer group" onClick={() => toggle('cctv')}>
                    <div className="flex items-center gap-3">
                        <Video size={16} className={layers.cctv ? 'text-[#ffb000]' : 'text-gray-500'} />
                        <span className={layers.cctv ? 'text-white' : 'text-gray-400'}>GLOBAL CCTV MESH</span>
                    </div>
                    <div className={`w-8 h-4 rounded-full border border-[rgba(255,176,0,0.5)] flex items-center p-[1px] transition-colors ${layers.cctv ? 'bg-[#ffb00020]' : 'bg-transparent'}`}>
                        <div className={`w-3 h-3 rounded-full bg-[#ffb000] transition-transform ${layers.cctv ? 'translate-x-4 shadow-[0_0_8px_#ffb000]' : 'bg-gray-600'}`}></div>
                    </div>
                </div>
            </div>
        </div>
    );
}
