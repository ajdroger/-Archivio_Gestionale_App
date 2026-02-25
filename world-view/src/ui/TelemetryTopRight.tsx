import { useEffect, useState } from 'react';

export function TelemetryTopRight() {
    const [timestamp, setTimestamp] = useState('');

    useEffect(() => {
        const tick = () => {
            const now = new Date();
            setTimestamp(now.toISOString().replace('T', ' ').substring(0, 19) + 'Z');
        };
        tick();
        const id = setInterval(tick, 1000);
        return () => clearInterval(id);
    }, []);

    return (
        <div className="absolute top-4 right-4 z-20 pointer-events-none text-right flex flex-col items-end gap-1">
            <div className="flex items-center gap-2 bg-gray-900/60 backdrop-blur-md border border-white/10 px-3 py-1.5 rounded shadow-lg shadow-black/50">
                <div className="w-2 h-2 rounded-full bg-red-600 animate-pulse"></div>
                <span className="text-xs font-mono text-red-500 font-bold tracking-widest leading-none mt-0.5">
                    REC {timestamp}
                </span>
            </div>
            <div className="text-[10px] font-mono text-gray-400 mt-1 mr-1 bg-gray-900/40 backdrop-blur-sm px-2 py-0.5 rounded border border-white/5">
                ORB: 47439 PASS: DESC-179
            </div>
        </div>
    );
}
