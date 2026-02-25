import { useEffect, useRef } from 'react';
import { Video, Target, X } from 'lucide-react';

interface CCTVPanelProps {
    onClose: () => void;
}

export function CCTVPanopticPanel({ onClose }: CCTVPanelProps) {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    // Simulated YOLO Bounding Boxes su HTML5 Canvas
    useEffect(() => {
        let animationFrameId: number;
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        // Configura proporzioni canvas
        canvas.width = 300;
        canvas.height = 200;

        // Placeholder objects tracked
        const boxes = [
            { x: 50, y: 100, w: 30, h: 40, label: 'PERSON 98%', color: '#00ff41', speed: 0.5 },
            { x: 150, y: 120, w: 80, h: 50, label: 'VEHICLE 91%', color: '#ffb000', speed: -1 }
        ];

        const render = () => {
            // Clear frame
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw grid a matrice
            ctx.strokeStyle = '#00ff4110';
            ctx.lineWidth = 1;
            for (let i = 0; i < 300; i += 20) {
                ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, 200); ctx.stroke();
            }
            for (let i = 0; i < 200; i += 20) {
                ctx.beginPath(); ctx.moveTo(0, i); ctx.lineTo(300, i); ctx.stroke();
            }

            // Update AI Boxes
            boxes.forEach(b => {
                b.x += b.speed;
                if (b.x > 300 - b.w || b.x < 0) b.speed *= -1; // Bounce

                ctx.strokeStyle = b.color;
                ctx.lineWidth = 1.5;
                // Animazione crosshair corner
                const cornerSize = 10;
                ctx.beginPath();
                // Top Left
                ctx.moveTo(b.x, b.y + cornerSize); ctx.lineTo(b.x, b.y); ctx.lineTo(b.x + cornerSize, b.y);
                // Top Right
                ctx.moveTo(b.x + b.w - cornerSize, b.y); ctx.lineTo(b.x + b.w, b.y); ctx.lineTo(b.x + b.w, b.y + cornerSize);
                // Bottom Left
                ctx.moveTo(b.x, b.y + b.h - cornerSize); ctx.lineTo(b.x, b.y + b.h); ctx.lineTo(b.x + cornerSize, b.y + b.h);
                // Bottom Right
                ctx.moveTo(b.x + b.w - cornerSize, b.y + b.h); ctx.lineTo(b.x + b.w, b.y + b.h); ctx.lineTo(b.x + b.w, b.y + b.h - cornerSize);
                ctx.stroke();

                // Label Box
                ctx.fillStyle = b.color;
                ctx.fillRect(b.x, b.y - 12, ctx.measureText(b.label).width + 8, 12);
                ctx.fillStyle = '#000';
                ctx.font = '9px Consolas';
                ctx.fillText(b.label, b.x + 4, b.y - 3);

                // Draw crosshair center
                ctx.strokeStyle = b.color;
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
    }, []);

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

            {/* Video Feed Simulation */}
            <div className="relative h-48 bg-[#001100] border-b border-[#ffb00040] overflow-hidden">
                {/* Placeholder image from Austin */}
                <div className="absolute inset-0 opacity-40 mix-blend-screen scale-105" style={{ backgroundImage: 'url("https://images.unsplash.com/photo-1514395617265-276e053a6977?auto=format&fit=crop&q=80&w=600")', backgroundSize: 'cover', backgroundPosition: 'center', filter: 'grayscale(100%) contrast(150%)' }}></div>

                {/* HTML5 Canvas overlay per Panoptic Detection */}
                <canvas ref={canvasRef} className="absolute inset-0 w-full h-full mix-blend-screen"></canvas>

                {/* Tactical Info Overlay */}
                <div className="absolute top-0 w-full h-full pointer-events-none" style={{ background: 'radial-gradient(circle, transparent 60%, rgba(0,0,0,0.8) 100%)' }}></div>

                <div className="absolute bottom-2 left-2 text-[#ffb000] text-[9px] space-y-1 font-bold pointer-events-none">
                    <div><span className="text-gray-400">MODEL</span> YoloV8-Panoptic</div>
                    <div><span className="text-gray-400">CONF.</span> &gt;0.85</div>
                    <div><span className="text-gray-400">LATENCY</span> 14ms</div>
                </div>
                <div className="absolute top-2 right-2 flex items-center gap-1 text-[#ff3333] text-[9px] animate-pulse font-bold pointer-events-none">
                    <Target size={12} /> REC
                </div>
            </div>

            {/* Status Bar */}
            <div className="p-2 text-[9px] font-bold text-gray-500 flex justify-between tracking-wider">
                <span>IP: 192.168.10.44:8080</span>
                <span className="text-[#00ff41]">UPLINK STABLE</span>
            </div>
        </div>
    );
}
