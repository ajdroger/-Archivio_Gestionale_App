import { Activity, Plane, Satellite, Video, Map, CloudRain } from 'lucide-react';
import { useStore } from '../../store/useStore';
import type { LayersState } from '../../store/useStore';

export function SidebarLeft() {
    const { layers, toggleLayer } = useStore();

    const layerItems: { key: keyof LayersState; icon: any; label: string; sub: string; color: string; count?: string }[] = [
        { key: 'flights', icon: Plane, label: 'Live Flights', sub: 'OpenSky', color: '#00f0ff', count: '8.2K' },
        { key: 'earthquakes', icon: Activity, label: 'Earthquakes (24h)', sub: 'USGS', color: '#ff3333' },
        { key: 'satellites', icon: Satellite, label: 'Satellites', sub: 'CelesTrak', color: '#00ff41', count: '180' },
        { key: 'cctv', icon: Video, label: 'CCTV Mesh', sub: 'Street View Polygons', color: '#ffb000' },
    ];

    const mockItems = [
        { icon: Map, label: 'Street Traffic', sub: 'OpenStreetMap' },
        { icon: CloudRain, label: 'Weather Radar', sub: 'NOAA (requires overlay)' },
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

                {/* Mock (inactive) Layers */}
                {mockItems.map(({ icon: Icon, label, sub }) => (
                    <div key={label} className="flex items-center justify-between cursor-pointer bg-gray-900/60 backdrop-blur-md border border-white/10 p-3 rounded">
                        <div className="flex gap-3 items-start">
                            <Icon size={16} className="text-gray-500 mt-0.5" />
                            <div>
                                <div className="text-gray-400 font-bold text-xs">{label}</div>
                                <div className="text-[10px] font-mono text-gray-500 mt-0.5">{sub}</div>
                            </div>
                        </div>
                        <div className="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold border bg-transparent text-gray-500 border-gray-600">OFF</div>
                    </div>
                ))}
            </div>
        </div>
    );
}
