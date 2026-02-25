import { useStore } from '../../store/useWorldViewStore';

export function TacticalInfoBox() {
    const selectedInfo = useStore(state => state.selectedInfo);

    if (!selectedInfo) return null;

    return (
        <div
            className="absolute z-50 p-4 bg-gray-900/90 backdrop-blur-md border border-[#00f0ff] rounded max-w-xs shadow-[0_0_20px_rgba(0,240,255,0.4)] pointer-events-none transition-opacity duration-200"
            style={{ left: selectedInfo.x + 20, top: selectedInfo.y - 20 }}
        >
            <div className="flex items-center gap-2 mb-2 border-b border-[#00f0ff]/30 pb-2">
                <div className="w-2 h-2 rounded-full bg-[#00f0ff] animate-pulse"></div>
                <h3 className="text-[#00f0ff] font-bold text-xs tracking-widest uppercase">{selectedInfo.name}</h3>
            </div>
            <pre className="text-gray-300 text-[10px] font-mono whitespace-pre-wrap leading-relaxed">
                {selectedInfo.description}
            </pre>
        </div>
    );
}
