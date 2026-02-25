import { useEffect, useRef, useState } from 'react';
import { Video, Target, X } from 'lucide-react';

interface CCTVPanelProps {
    onClose: () => void;
}

import { useStore } from '../core/store';

export function CCTVPanopticPanel({ onClose }: CCTVPanelProps) {
    const { cctvParams, setCctvParam } = useStore();
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const videoRef = useRef<HTMLVideoElement>(null);
    const [isReady, setIsReady] = useState(false);

    // Create video element for the CCTV feed
    useEffect(() => {
        const vid = videoRef.current;
        if (!vid) return;
        vid.src = 'https://cesium.com/public/SandcastleSampleData/big-buck-bunny_trailer.mp4';
        vid.crossOrigin = 'anonymous';
        vid.loop = true;
        vid.muted = true;

        const handleReady = () => {
            if (vid.readyState >= 2) {
                setIsReady(true);
            }
        };

        vid.addEventListener('canplay', handleReady);
        vid.play().catch(e => console.error('CCTV autoplay denied:', e));

        return () => {
            vid.removeEventListener('canplay', handleReady);
            vid.pause();
            vid.removeAttribute('src');
            vid.load();
        };
    }, []);

    // Simulated YOLO Panoptic Bounding Boxes
    useEffect(() => {
        if (!isReady) return;

        let animationFrameId: number;
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        canvas.width = 320;
        canvas.height = 200;

        // Tracked objects
        const boxes = [
            { x: 50, y: 100, w: 30, h: 40, label: 'PERSON 98%', color: '#00ff41', vx: 0.5, vy: 0.2 },
            { x: 150, y: 120, w: 80, h: 50, label: 'VEHICLE 91%', color: '#ffb000', vx: -1.0, vy: 0 },
            { x: 220, y: 80, w: 25, h: 55, label: 'CYCLIST 87%', color: '#00f0ff', vx: 0.7, vy: -0.1 },
            { x: 30, y: 140, w: 18, h: 20, label: 'DOG 76%', color: '#ff3333', vx: 1.2, vy: 0.3 },
            { x: 260, y: 130, w: 15, h: 18, label: 'BACKPACK 82%', color: '#a855f7', vx: -0.3, vy: 0.15 },
        ];

        const render = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Grid overlay
            ctx.strokeStyle = '#00ff4110';
            ctx.lineWidth = 1;
            for (let i = 0; i < canvas.width; i += 20) {
                ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, canvas.height); ctx.stroke();
            }
            for (let i = 0; i < canvas.height; i += 20) {
                ctx.beginPath(); ctx.moveTo(0, i); ctx.lineTo(canvas.width, i); ctx.stroke();
            }

            // Update & draw each tracked object
            boxes.forEach(b => {
                b.x += b.vx;
                b.y += b.vy;
                if (b.x > canvas.width - b.w || b.x < 0) b.vx *= -1;
                if (b.y > canvas.height - b.h || b.y < 20) b.vy *= -1;

                // Corner brackets (crosshair style)
                ctx.strokeStyle = b.color;
                ctx.lineWidth = 1.5;
                const cs = 8;
                ctx.beginPath();
                // TL
                ctx.moveTo(b.x, b.y + cs); ctx.lineTo(b.x, b.y); ctx.lineTo(b.x + cs, b.y);
                // TR
                ctx.moveTo(b.x + b.w - cs, b.y); ctx.lineTo(b.x + b.w, b.y); ctx.lineTo(b.x + b.w, b.y + cs);
                // BL
                ctx.moveTo(b.x, b.y + b.h - cs); ctx.lineTo(b.x, b.y + b.h); ctx.lineTo(b.x + cs, b.y + b.h);
                // BR
                ctx.moveTo(b.x + b.w - cs, b.y + b.h); ctx.lineTo(b.x + b.w, b.y + b.h); ctx.lineTo(b.x + b.w, b.y + b.h - cs);
                ctx.stroke();

                // Label
                ctx.fillStyle = b.color;
                const tw = ctx.measureText(b.label).width + 8;
                ctx.fillRect(b.x, b.y - 12, tw, 12);
                ctx.fillStyle = '#000';
                ctx.font = '9px Consolas';
                ctx.fillText(b.label, b.x + 4, b.y - 3);

                // Center crosshair
                ctx.strokeStyle = b.color;
                ctx.lineWidth = 1;
                ctx.beginPath();
                const cx = b.x + b.w / 2;
                const cy = b.y + b.h / 2;
                ctx.moveTo(cx - 5, cy); ctx.lineTo(cx + 5, cy);
                ctx.moveTo(cx, cy - 5); ctx.lineTo(cx, cy + 5);
                ctx.stroke();
            });

            animationFrameId = requestAnimationFrame(render);
        };

        render();
        return () => cancelAnimationFrame(animationFrameId);
    }, [isReady]);

    return (
        <div className="absolute top-24 right-[19rem] w-80 glass-panel rounded-md overflow-hidden shadow-[0_0_30px_#ffb00040] border border-[#ffb000] z-50 cursor-move">
            {/* HUD Header */}
            <div className="bg-[#ffb00020] border-b border-[#ffb000] p-2 flex justify-between items-center text-[#ffb000]">
                <div className="flex items-center gap-2 text-[10px] font-bold tracking-widest uppercase">
                    <Video size={14} className="animate-pulse" />
                    CCTV MESH - AUSTIN 6TH ST
                </div>
                <button onClick={onClose} className="hover:text-white transition-colors"><X size={14} /></button>
            </div>

            {/* Video Feed + Canvas Overlay */}
            <div className="relative h-48 bg-[#001100] border-b border-[#ffb00040] overflow-hidden">
                {!isReady && (
                    <div className="absolute inset-0 flex items-center justify-center bg-black z-10">
                        <div className="text-[#ffb000] text-[10px] animate-pulse">CONNECTING TO MESH...</div>
                    </div>
                )}
                {/* Real video element */}
                <video
                    ref={videoRef}
                    className="absolute inset-0 w-full h-full object-cover opacity-60"
                    style={{ visibility: isReady ? 'visible' : 'hidden' }}
                    playsInline
                    muted
                    loop
                />
                {/* HTML5 Canvas for YOLO Detection Overlay */}
                <canvas ref={canvasRef} className="absolute inset-0 w-full h-full mix-blend-screen"></canvas>

                {/* Radial Vignette */}
                <div className="absolute top-0 w-full h-full pointer-events-none" style={{ background: 'radial-gradient(circle, transparent 60%, rgba(0,0,0,0.8) 100%)' }}></div>

                {/* Tactical Info */}
                <div className="absolute bottom-2 left-2 text-[#ffb000] text-[9px] space-y-1 font-bold pointer-events-none">
                    <div><span className="text-gray-400">MODEL</span> YoloV8-Panoptic</div>
                    <div><span className="text-gray-400">CONF.</span> &gt;0.85</div>
                    <div><span className="text-gray-400">LATENCY</span> 14ms</div>
                    <div><span className="text-gray-400">OBJECTS</span> 5</div>
                </div>
                <div className="absolute top-2 right-2 flex items-center gap-1 text-[#ff3333] text-[9px] animate-pulse font-bold pointer-events-none">
                    <Target size={12} /> REC
                </div>
            </div>

            {/* Status Bar */}
            <div className="p-2 text-[9px] font-bold text-gray-500 flex justify-between tracking-wider">
                <span>IP: 192.168.10.44:8080</span>
                <span className="text-[#00ff41]">{isReady ? 'UPLINK STABLE' : 'UPLINK SEARCHING'}</span>
            </div>

            {/* Calibration Controls */}
            <div className="p-2 border-t border-[#ffb00040] bg-gray-900/80 backdrop-blur-md pb-4 cursor-default">
                <div className="grid grid-cols-2 gap-x-4 gap-y-3 text-[9px] text-[#ffb000] font-bold">
                    <label className="flex flex-col gap-1">
                        <span>HEADING: {cctvParams.heading}°</span>
                        <input type="range" min="0" max="360" value={cctvParams.heading}
                            onChange={e => setCctvParam('heading', parseInt(e.target.value))}
                            className="w-full accent-[#ffb000] h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer" />
                    </label>
                    <label className="flex flex-col gap-1">
                        <span>PITCH: {cctvParams.pitch}°</span>
                        <input type="range" min="-90" max="0" value={cctvParams.pitch}
                            onChange={e => setCctvParam('pitch', parseInt(e.target.value))}
                            className="w-full accent-[#ffb000] h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer" />
                    </label>
                    <label className="flex flex-col gap-1">
                        <span>FOV: {cctvParams.fov}°</span>
                        <input type="range" min="20" max="140" value={cctvParams.fov}
                            onChange={e => setCctvParam('fov', parseInt(e.target.value))}
                            className="w-full accent-[#ffb000] h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer" />
                    </label>
                    <label className="flex flex-col gap-1">
                        <span>RANGE: {cctvParams.range}M</span>
                        <input type="range" min="50" max="1500" value={cctvParams.range}
                            onChange={e => setCctvParam('range', parseInt(e.target.value))}
                            className="w-full accent-[#ffb000] h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer" />
                    </label>
                </div>
            </div>
        </div>
    );
}
