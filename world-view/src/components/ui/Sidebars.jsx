import { Activity, Plane, Satellite, Video, Map, CloudRain, ChevronDown } from 'lucide-react';
import { useStore } from '../../store/useWorldViewStore';

export function SidebarLeft() {
    const { layers, toggleLayer } = useStore();

    const layerItems = [
        { key: 'flights', icon: Plane, label: 'Live Flights', sub: 'OpenSky', color: '#00f0ff', count: '8.2K' },
        { key: 'earthquakes', icon: Activity, label: 'Earthquakes (24h)', sub: 'USGS', color: '#ff3333' },
        { key: 'satellites', icon: Satellite, label: 'Satellites', sub: 'CelesTrak', color: '#00ff41', count: '180' },
        { key: 'cctv', icon: Video, label: 'CCTV Mesh', sub: 'Street View Polygons', color: '#ffb000' },
        { key: 'streetTraffic', icon: Map, label: 'Street Traffic', sub: 'OpenStreetMap', color: '#ff3333' },
        { key: 'weatherRadar', icon: CloudRain, label: 'Weather Radar', sub: 'NOAA', color: '#00f0ff' },
    ];

    return (
        <div className="absolute top-1/2 -translate-y-1/2 left-4 w-[320px] z-10 select-none">
            <div className="text-gray-300 font-sans font-bold text-sm tracking-widest mb-2 px-1">DATA LAYERS</div>

            <div className="flex flex-col gap-2">

                {/* Toggleable Layers */}
                {layerItems.map(({ key, icon: Icon, label, sub, color, count }) => {
                    const isOn = layers[key];
                    return (
                        <div
                            key={key}
                            onClick={() => toggleLayer(key)}
                            className="flex items-center justify-between cursor-pointer bg-gray-900/60 backdrop-blur-md border border-white/10 p-3 rounded"
                        >
                            <div className="flex gap-3 items-start">
                                <Icon size={16} className={isOn ? `text-[${color}]` : 'text-gray-500 mt-0.5'}
                                    style={isOn ? { color } : undefined} />
                                <div>
                                    <div className={isOn ? 'text-gray-200 font-bold text-xs' : 'text-gray-400 font-bold text-xs'}>{label}</div>
                                    <div className="flex gap-2">
                                        <span className="text-[10px] font-mono text-gray-500 mt-0.5">{sub}</span>
                                        {count && <span className="text-[10px] font-mono text-gray-500 mt-0.5">{count}</span>}
                                    </div>
                                </div>
                            </div>
                            <div
                                className={`px-2 py-0.5 rounded-full text-[10px] font-mono font-bold border transition-colors`}
                                style={isOn
                                    ? { backgroundColor: `${color}20`, color, borderColor: `${color}80` }
                                    : { backgroundColor: 'transparent', color: '#6b7280', borderColor: '#4b5563' }
                                }
                            >
                                {isOn ? 'ON' : 'OFF'}
                            </div>
                        </div>
                    );
                })}

            </div>
        </div>
    );
}

export function SidebarRight() {
    const { fxSettings, setFxSetting } = useStore();

    return (
        <div className="absolute top-1/2 -translate-y-1/2 right-4 w-[300px] z-10 select-none flex flex-col gap-4">

            {/* HUD Header */}
            <div className="text-gray-300 font-sans font-bold text-sm tracking-widest px-1">PARAMETERS / HUD</div>

            {/* Bloom & Sharpen */}
            <div className="bg-gray-900/60 backdrop-blur-md border border-white/10 p-4 rounded flex flex-col gap-5">
                <div>
                    <div className="flex justify-between text-gray-400 mb-2 font-bold text-xs">
                        <span>BLOOM</span>
                        <span className="font-mono text-[10px]">{Math.round(fxSettings.bloom * 50)}%</span>
                    </div>
                    <input
                        type="range"
                        min="0" max="2" step="0.1"
                        value={fxSettings.bloom}
                        onChange={(e) => setFxSetting('bloom', parseFloat(e.target.value))}
                        className="w-full accent-gray-400 h-0.5 bg-gray-700 rounded-lg appearance-none cursor-pointer"
                    />
                </div>
                <div>
                    <div className="flex justify-between text-gray-400 mb-2 font-bold text-xs">
                        <span>SHARPEN</span>
                        <span className="font-mono text-[10px]">{Math.round(fxSettings.sharpen * 100)}%</span>
                    </div>
                    <input
                        type="range"
                        min="0" max="1" step="0.01"
                        value={fxSettings.sharpen}
                        onChange={(e) => setFxSetting('sharpen', parseFloat(e.target.value))}
                        className="w-full accent-gray-400 h-0.5 bg-gray-700 rounded-lg appearance-none cursor-pointer"
                    />
                </div>
            </div>

            {/* HUD Dropdown (Mock) */}
            <div className="bg-gray-900/60 backdrop-blur-md border border-white/10 p-3 rounded flex items-center justify-between cursor-pointer">
                <span className="text-gray-400 font-bold text-xs flex items-center gap-2">HUD</span>
                <div className="flex items-center gap-2 text-gray-300 text-[10px] font-mono border border-gray-600 px-2 py-1 rounded">
                    Layer: Tactical <ChevronDown size={14} className="text-gray-500" />
                </div>
            </div>

            {/* PANOPTIC Section */}
            <div className="bg-gray-900/60 backdrop-blur-md border border-white/10 rounded overflow-hidden">
                <div className="bg-[#00ff41]/20 border-b border-[#00ff41]/30 p-3 flex justify-between items-center cursor-pointer hover:bg-[#00ff41]/30 transition-colors">
                    <span className="text-[#00ff41] font-bold text-xs">PANOPTIC</span>
                    <div className="w-8 h-4 rounded-full border border-[#00ff41]/50 flex items-center p-[1px] bg-[#00ff41]/20">
                        <div className="w-3 h-3 rounded-full bg-[#00ff41] translate-x-4 shadow-[0_0_8px_#00ff41]"></div>
                    </div>
                </div>
                <div className="p-4">
                    <div className="flex justify-between text-gray-400 mb-2 font-bold text-xs">
                        <span>Opacity</span>
                        <span className="font-mono text-[10px]">{Math.round(fxSettings.panopticOpacity * 100)}%</span>
                    </div>
                    <input
                        type="range"
                        min="0" max="1" step="0.01"
                        value={fxSettings.panopticOpacity}
                        onChange={(e) => setFxSetting('panopticOpacity', parseFloat(e.target.value))}
                        className="w-full accent-gray-400 h-0.5 bg-gray-700 rounded-lg appearance-none cursor-pointer"
                    />
                </div>
            </div>

            {/* Clear UI Button */}
            <button className="w-full bg-gray-900/60 backdrop-blur-md border border-white/10 hover:border-gray-500 p-3 rounded text-center text-gray-400 font-bold text-xs transition-colors">
                CLEAR UI
            </button>

            {/* PARAMETERS (CRT-Specific FX) */}
            <div className="bg-gray-900/60 backdrop-blur-md border border-white/10 p-4 rounded flex flex-col gap-4">
                <div className="text-[#00f0ff] font-bold text-xs tracking-widest mb-2 border-b border-white/10 pb-2">PARAMETERS</div>

                <div>
                    <div className="flex justify-between text-gray-400 mb-2 font-bold text-xs">
                        <span>Pixelation</span>
                        <span className="font-mono text-[10px]">{Math.round(fxSettings.pixelation * 100)}%</span>
                    </div>
                    <input
                        type="range"
                        min="0" max="1" step="0.1"
                        value={fxSettings.pixelation}
                        onChange={(e) => setFxSetting('pixelation', parseFloat(e.target.value))}
                        className="w-full accent-gray-400 h-0.5 bg-gray-700 rounded-lg appearance-none cursor-pointer"
                    />
                </div>

                <div>
                    <div className="flex justify-between text-gray-400 mb-2 font-bold text-xs">
                        <span>Distortion</span>
                        <span className="font-mono text-[10px]">{Math.round(fxSettings.distortion * 200)}%</span>
                    </div>
                    <input
                        type="range"
                        min="0" max="0.5" step="0.01"
                        value={fxSettings.distortion}
                        onChange={(e) => setFxSetting('distortion', parseFloat(e.target.value))}
                        className="w-full accent-gray-400 h-0.5 bg-gray-700 rounded-lg appearance-none cursor-pointer"
                    />
                </div>

                <div>
                    <div className="flex justify-between text-gray-400 mb-2 font-bold text-xs">
                        <span>Instability</span>
                        <span className="font-mono text-[10px]">{Math.round(fxSettings.noise * 500)}%</span>
                    </div>
                    <input
                        type="range"
                        min="0" max="0.2" step="0.01"
                        value={fxSettings.noise}
                        onChange={(e) => setFxSetting('noise', parseFloat(e.target.value))}
                        className="w-full accent-gray-400 h-0.5 bg-gray-700 rounded-lg appearance-none cursor-pointer"
                    />
                </div>
            </div>

        </div>
    );
}
