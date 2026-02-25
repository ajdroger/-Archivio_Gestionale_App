import { useEffect, useState } from 'react';
import { useStore } from '../../store/useWorldViewStore';

export function HUDInfo() {
    const mouseCoords = useStore(state => state.mouseCoords);
    const [sunEl, setSunEl] = useState(0);

    // Simulated Sun Elevation based on UTC time just for immersion
    useEffect(() => {
        const interval = setInterval(() => {
            const h = new Date().getUTCHours();
            // simple parabola: max at 12:00, min at 24:00
            const el = -45 + 90 * (1 - Math.abs(h - 12) / 12);
            setSunEl(parseFloat(el.toFixed(1)));
        }, 60000);
        return () => clearInterval(interval);
    }, []);

    // GSD is roughly Altitude / 1000 in meters per pixel based on focal length usually,
    // let's create a simulated value depending on altitude
    const gsd = Math.max(0.1, (mouseCoords.alt / 15000)).toFixed(2);
    const niirs = (9 - parseFloat(gsd)).toFixed(1); // Rough estimate

    return (
        <div className="absolute bottom-6 right-6 z-20 pointer-events-none text-right flex flex-col items-end gap-1">
            <div className="text-[10px] font-mono text-[#00f0ff] leading-relaxed drop-shadow-[0_0_2px_rgba(0,240,255,0.8)] bg-gray-900/60 backdrop-blur-sm border border-[#00f0ff]/30 p-2 rounded">
                <div>GSD: {gsd}M NIIRS: {niirs}</div>
                <div>ALT: {Math.floor(mouseCoords.alt)}M SUN: {sunEl}° EL</div>
            </div>
            <div className="text-[10px] font-mono text-gray-300 bg-gray-900/60 backdrop-blur-sm border border-white/10 px-2 py-1 rounded">
                MGRS: <span className="text-white font-bold">{mouseCoords.mgrs}</span>
            </div>
            <div className="text-[9px] font-mono text-gray-500">
                L/L: {mouseCoords.lat.toFixed(5)}, {mouseCoords.lng.toFixed(5)}
            </div>
        </div>
    );
}
