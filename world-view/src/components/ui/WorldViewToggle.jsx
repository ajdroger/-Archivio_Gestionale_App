import React from 'react';

/**
 * WorldViewToggle Component
 * Da posizionare a destra del tasto "PURGE" nell'interfaccia principale di MCAG.
 * Stile: bordo cyan neon, testo monospace, effetto hover "Tactical Glow".
 */
export function WorldViewToggle({ onClick }) {
    return (
        <button
            onClick={onClick}
            className="group relative px-4 py-2 bg-transparent border border-[#00f0ff] overflow-hidden ml-4 cursor-pointer"
            style={{
                boxShadow: '0 0 5px rgba(0, 240, 255, 0.4), inset 0 0 5px rgba(0, 240, 255, 0.1)',
                transition: 'all 0.3s ease-in-out',
            }}
            onMouseEnter={(e) => {
                e.currentTarget.style.boxShadow = '0 0 15px rgba(0, 240, 255, 0.8), inset 0 0 10px rgba(0, 240, 255, 0.3)';
                e.currentTarget.style.backgroundColor = 'rgba(0, 240, 255, 0.1)';
            }}
            onMouseLeave={(e) => {
                e.currentTarget.style.boxShadow = '0 0 5px rgba(0, 240, 255, 0.4), inset 0 0 5px rgba(0, 240, 255, 0.1)';
                e.currentTarget.style.backgroundColor = 'transparent';
            }}
        >
            <div className="absolute inset-0 w-full h-full bg-[#00f0ff] opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>

            {/* Scanline Effect on Hover */}
            <div className="absolute top-0 left-0 w-full h-[2px] bg-[#00f0ff] shadow-[0_0_8px_#00f0ff] opacity-0 group-hover:animate-[scanline_2s_linear_infinite]"></div>

            <span className="relative z-10 font-mono text-[11px] font-bold text-[#00f0ff] tracking-[0.2em] group-hover:text-white transition-colors duration-300 drop-shadow-[0_0_2px_rgba(0,240,255,0.8)]">
                [ WORLDVIEW ]
            </span>

            {/* Tactical Corners */}
            <div className="absolute top-0 left-0 w-2 h-2 border-t-2 border-l-2 border-[#00f0ff]"></div>
            <div className="absolute top-0 right-0 w-2 h-2 border-t-2 border-r-2 border-[#00f0ff]"></div>
            <div className="absolute bottom-0 left-0 w-2 h-2 border-b-2 border-l-2 border-[#00f0ff]"></div>
            <div className="absolute bottom-0 right-0 w-2 h-2 border-b-2 border-r-2 border-[#00f0ff]"></div>
        </button>
    );
}
