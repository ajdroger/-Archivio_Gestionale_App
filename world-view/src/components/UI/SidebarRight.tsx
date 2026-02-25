import { Sliders, Maximize, Zap, Eye } from 'lucide-react';

interface SidebarRightProps {
    fxSettings: {
        distortion: number;
        bloom: number;
        scanlines: number;
        noise: number;
    };
    setFxSetting: (key: keyof SidebarRightProps['fxSettings'], value: number) => void;
}

export function SidebarRight({ fxSettings, setFxSetting }: SidebarRightProps) {
    return (
        <div className="absolute top-1/4 right-6 glass-panel rounded-lg w-64 p-4 text-xs z-10 select-none shadow-[0_0_20px_rgba(0,255,65,0.15)] border border-[rgba(0,255,65,0.3)] bg-gradient-to-b from-[#0a1428cc] to-[#050b14cc]">
            <div className="flex items-center gap-2 mb-4 text-[#ffb000] uppercase font-bold tracking-widest border-b border-[#ffb00040] pb-2">
                <Sliders size={16} /> FX Controls
            </div>

            <div className="space-y-4">
                {/* Bloom */}
                <div>
                    <div className="flex justify-between text-gray-400 mb-1">
                        <span className="flex items-center gap-1"><Zap size={12} /> BLOOM</span>
                        <span>{fxSettings.bloom.toFixed(1)}</span>
                    </div>
                    <input
                        type="range"
                        min="0" max="2" step="0.1"
                        value={fxSettings.bloom}
                        onChange={(e) => setFxSetting('bloom', parseFloat(e.target.value))}
                        className="w-full accent-[#ffb000] h-1 bg-gray-800 rounded-lg appearance-none cursor-pointer"
                    />
                </div>

                {/* Distortion */}
                <div>
                    <div className="flex justify-between text-gray-400 mb-1">
                        <span className="flex items-center gap-1"><Maximize size={12} /> DISTORTION</span>
                        <span>{fxSettings.distortion.toFixed(2)}</span>
                    </div>
                    <input
                        type="range"
                        min="0" max="0.5" step="0.01"
                        value={fxSettings.distortion}
                        onChange={(e) => setFxSetting('distortion', parseFloat(e.target.value))}
                        className="w-full accent-[#ffb000] h-1 bg-gray-800 rounded-lg appearance-none cursor-pointer"
                    />
                </div>

                {/* Scanlines */}
                <div>
                    <div className="flex justify-between text-gray-400 mb-1">
                        <span className="flex items-center gap-1"><Eye size={12} /> SCANLINES INTENSITY</span>
                        <span>{fxSettings.scanlines.toFixed(1)}</span>
                    </div>
                    <input
                        type="range"
                        min="0" max="1" step="0.1"
                        value={fxSettings.scanlines}
                        onChange={(e) => setFxSetting('scanlines', parseFloat(e.target.value))}
                        className="w-full accent-[#ffb000] h-1 bg-gray-800 rounded-lg appearance-none cursor-pointer"
                    />
                </div>

                {/* Noise */}
                <div>
                    <div className="flex justify-between text-gray-400 mb-1">
                        <span className="flex items-center gap-1"><Eye size={12} /> NOISE (GRAIN)</span>
                        <span>{fxSettings.noise.toFixed(2)}</span>
                    </div>
                    <input
                        type="range"
                        min="0" max="0.2" step="0.01"
                        value={fxSettings.noise}
                        onChange={(e) => setFxSetting('noise', parseFloat(e.target.value))}
                        className="w-full accent-[#ffb000] h-1 bg-gray-800 rounded-lg appearance-none cursor-pointer"
                    />
                </div>
            </div>
        </div>
    );
}
