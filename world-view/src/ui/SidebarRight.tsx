import { ChevronDown } from 'lucide-react';
import { useStore } from '../core/store';

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
