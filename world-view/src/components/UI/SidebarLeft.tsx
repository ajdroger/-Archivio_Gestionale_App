import { Activity, Plane, Satellite, Video, Map, CloudRain } from 'lucide-react';

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
        <div className="absolute top-1/2 -translate-y-1/2 left-4 w-[320px] z-10 select-none">
            <div className="text-gray-300 font-sans font-bold text-sm tracking-widest mb-2 px-1">DATA LAYERS</div>

            <div className="flex flex-col gap-2">

                {/* Live Flights */}
                <div
                    onClick={() => toggle('flights')}
                    className="flex items-center justify-between cursor-pointer bg-gray-900/60 backdrop-blur-md border border-white/10 p-3 rounded"
                >
                    <div className="flex gap-3 items-start">
                        <Plane size={16} className={layers.flights ? 'text-[#00f0ff]' : 'text-gray-500 mt-0.5'} />
                        <div>
                            <div className={layers.flights ? 'text-gray-200 font-bold text-xs' : 'text-gray-400 font-bold text-xs'}>Live Flights</div>
                            <div className="text-[10px] font-mono text-gray-500 mt-0.5">8.2K</div>
                        </div>
                    </div>
                    <div className={`px-2 py-0.5 rounded-full text-[10px] font-mono font-bold border transition-colors ${layers.flights ? 'bg-[#00f0ff]/20 text-[#00f0ff] border-[#00f0ff]/50' : 'bg-transparent text-gray-500 border-gray-600'}`}>
                        {layers.flights ? 'ON' : 'OFF'}
                    </div>
                </div>

                {/* Earthquakes */}
                <div
                    onClick={() => toggle('earthquakes')}
                    className="flex items-center justify-between cursor-pointer bg-gray-900/60 backdrop-blur-md border border-white/10 p-3 rounded"
                >
                    <div className="flex gap-3 items-start">
                        <Activity size={16} className={layers.earthquakes ? 'text-[#ff3333]' : 'text-gray-500 mt-0.5'} />
                        <div>
                            <div className={layers.earthquakes ? 'text-gray-200 font-bold text-xs' : 'text-gray-400 font-bold text-xs'}>Earthquakes (24h)</div>
                            <div className="text-[10px] font-mono text-gray-500 mt-0.5">USGS</div>
                        </div>
                    </div>
                    <div className={`px-2 py-0.5 rounded-full text-[10px] font-mono font-bold border transition-colors ${layers.earthquakes ? 'bg-[#ff3333]/20 text-[#ff3333] border-[#ff3333]/50' : 'bg-transparent text-gray-500 border-gray-600'}`}>
                        {layers.earthquakes ? 'ON' : 'OFF'}
                    </div>
                </div>

                {/* Satellites */}
                <div
                    onClick={() => toggle('satellites')}
                    className="flex items-center justify-between cursor-pointer bg-gray-900/60 backdrop-blur-md border border-white/10 p-3 rounded"
                >
                    <div className="flex gap-3 items-start">
                        <Satellite size={16} className={layers.satellites ? 'text-[#00ff41]' : 'text-gray-500 mt-0.5'} />
                        <div>
                            <div className={layers.satellites ? 'text-gray-200 font-bold text-xs' : 'text-gray-400 font-bold text-xs'}>Satellites</div>
                            <div className="flex gap-2">
                                <span className="text-[10px] font-mono text-gray-500 mt-0.5">CelesTrak</span>
                                <span className="text-[10px] font-mono text-gray-500 mt-0.5">180</span>
                            </div>
                        </div>
                    </div>
                    <div className={`px-2 py-0.5 rounded-full text-[10px] font-mono font-bold border transition-colors ${layers.satellites ? 'bg-[#00ff41]/20 text-[#00ff41] border-[#00ff41]/50' : 'bg-transparent text-gray-500 border-gray-600'}`}>
                        {layers.satellites ? 'ON' : 'OFF'}
                    </div>
                </div>

                {/* Street Traffic (mock) */}
                <div className="flex items-center justify-between cursor-pointer bg-gray-900/60 backdrop-blur-md border border-white/10 p-3 rounded">
                    <div className="flex gap-3 items-start">
                        <Map size={16} className="text-gray-500 mt-0.5" />
                        <div>
                            <div className="text-gray-400 font-bold text-xs">Street Traffic</div>
                            <div className="text-[10px] font-mono text-gray-500 mt-0.5">OpenStreetMap</div>
                        </div>
                    </div>
                    <div className="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold border bg-transparent text-gray-500 border-gray-600">OFF</div>
                </div>

                {/* Weather Radar (mock) */}
                <div className="flex items-center justify-between cursor-pointer bg-gray-900/60 backdrop-blur-md border border-white/10 p-3 rounded">
                    <div className="flex gap-3 items-start">
                        <CloudRain size={16} className="text-gray-500 mt-0.5" />
                        <div>
                            <div className="text-gray-400 font-bold text-xs">Weather Radar</div>
                            <div className="text-[10px] font-mono text-gray-500 mt-0.5">NOAA (requires overlay)</div>
                        </div>
                    </div>
                    <div className="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold border bg-transparent text-gray-500 border-gray-600">OFF</div>
                </div>

                {/* CCTV Mesh */}
                <div
                    onClick={() => toggle('cctv')}
                    className="flex items-center justify-between cursor-pointer bg-gray-900/60 backdrop-blur-md border border-white/10 p-3 rounded"
                >
                    <div className="flex gap-3 items-start">
                        <Video size={16} className={layers.cctv ? 'text-[#ffb000]' : 'text-gray-500 mt-0.5'} />
                        <div>
                            <div className={layers.cctv ? 'text-gray-200 font-bold text-xs' : 'text-gray-400 font-bold text-xs'}>CCTV Mesh</div>
                            <div className="text-[10px] font-mono text-gray-500 mt-0.5">Street View Polygons</div>
                        </div>
                    </div>
                    <div className={`px-2 py-0.5 rounded-full text-[10px] font-mono font-bold border transition-colors ${layers.cctv ? 'bg-[#ffb000]/20 text-[#ffb000] border-[#ffb000]/50' : 'bg-transparent text-gray-500 border-gray-600'}`}>
                        {layers.cctv ? 'ON' : 'OFF'}
                    </div>
                </div>

            </div>
        </div>
    );
}
