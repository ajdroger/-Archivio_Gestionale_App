import { Link as LinkIcon } from 'lucide-react';
import { useStore } from '../../store/useWorldViewStore';

export function Header() {
    const { visualMode, targetLocation } = useStore();

    const summaryText = targetLocation
        ? `${targetLocation.name.toUpperCase()} DATALINK ESTABLISHED`
        : `GLOBAL WIDE-AREA SURVEILLANCE`;

    return (
        <div className="absolute top-4 left-4 z-20 pointer-events-none flex flex-col gap-1">
            <div className="flex items-center gap-2">
                <h1 className="text-3xl font-sans font-bold text-gray-200 tracking-wider m-0 leading-none drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">WORLDVIEW</h1>
                <span className="text-[10px] text-gray-500 font-sans tracking-widest bg-gray-900/50 px-2 py-0.5 rounded border border-white/10 flex items-center gap-1 backdrop-blur-sm mt-1">
                    NO PLACE LEFT BEHIND <LinkIcon size={10} />
                </span>
            </div>
            <div className="text-[10px] font-mono text-gray-400 mt-2 bg-gray-900/40 backdrop-blur-sm w-fit px-2 py-1 rounded border border-white/5">
                <div>TOP SECRET // SI-TK // NOFORN</div>
                <div>KH11-4166 GPS-4117</div>
            </div>
            <div className="mt-4 bg-gray-900/60 backdrop-blur-md border border-white/10 p-2.5 rounded max-w-sm shadow-lg shadow-black/50 border-l border-l-[#00f0ff]/50">
                <h2 className="text-xs font-sans font-bold text-[#00f0ff] mb-1">{visualMode} MODE</h2>
                <p className="text-[10px] font-mono text-gray-200 m-0 leading-tight bg-black/30 p-1.5 rounded">
                    SUMMARY: {summaryText}...
                </p>
            </div>
        </div>
    );
}
