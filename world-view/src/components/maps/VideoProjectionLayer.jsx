import { useEffect, useState } from 'react';
import { Entity, PolygonGraphics } from 'resium';
import { Cartesian3, Color, ImageMaterialProperty } from 'cesium';

// Il bbox dinamico rimpiazza l'approccio statico
import { useStore } from '../../store/useWorldViewStore';

export function VideoProjectionLayer() {
    const { cctvParams } = useStore();
    const [videoElement, setVideoElement] = useState(null);
    const [isReady, setIsReady] = useState(false);

    useEffect(() => {
        const vid = document.createElement('video');
        vid.src = 'https://cesium.com/public/SandcastleSampleData/big-buck-bunny_trailer.mp4';
        vid.crossOrigin = 'anonymous';
        vid.loop = true;
        vid.muted = true;
        vid.volume = 0; // Double fail-safe
        vid.autoplay = true;
        vid.playsInline = true;

        const handleReady = () => setIsReady(true);

        vid.addEventListener('canplay', handleReady);
        vid.addEventListener('loadeddata', handleReady);
        vid.addEventListener('playing', handleReady);

        if (vid.readyState >= 2) setIsReady(true);

        vid.play().catch(e => console.error("Auto-play CCTV projection denied:", e));

        // Fallback per mostrare un frame in caso di ritardi CORS/Network
        setTimeout(() => setIsReady(true), 2000);

        setVideoElement(vid);

        return () => {
            vid.removeEventListener('canplay', handleReady);
            vid.pause();
            vid.removeAttribute('src');
            vid.load();
        };
    }, []);

    // Calcolo Trapezio Proiezione CCTV in base ai parametri
    const pivotLat = 30.2665;
    const pivotLng = -97.7405;
    const mToDeg = 1 / 111111;

    // Simulate Trapezoid Drape from Camera
    const headingRad = (cctvParams.heading * Math.PI) / 180;
    const fovRad = (cctvParams.fov * Math.PI) / 180;
    const rangeDeg = cctvParams.range * mToDeg;

    // Distanze base e offset
    const dNear = rangeDeg * 0.1;
    const wNear = dNear * Math.tan(fovRad / 2);
    const pitchFactor = Math.max(0.1, Math.cos((cctvParams.pitch * Math.PI) / 180) * 1.5);
    const dFar = rangeDeg * pitchFactor;
    const wFar = Math.max(dFar * Math.tan(fovRad / 2), wNear * 1.5);

    // Funzione rotazione 2D matematica e mappa coordinate (x=lng, y=lat)
    const rotate = (dx, dy) => {
        const rx = dx * Math.cos(-headingRad) - dy * Math.sin(-headingRad);
        const ry = dx * Math.sin(-headingRad) + dy * Math.cos(-headingRad);
        // compensazione equatore/paralleli approx
        return [pivotLng + rx / Math.cos((pivotLat * Math.PI) / 180), pivotLat + ry];
    };

    const p1 = rotate(-wNear, dNear); // Near Left
    const p2 = rotate(wNear, dNear);  // Near Right
    const p3 = rotate(wFar, dFar);    // Far Right
    const p4 = rotate(-wFar, dFar);   // Far Left

    const boxCoords = [...p1, ...p2, ...p3, ...p4];

    return (
        <>
            {videoElement && isReady && (
                <Entity
                    name="CCTV Projection Austin 6th St"
                    description="Live Video-to-Ground projection from Mesh Node 4"
                >
                    <PolygonGraphics
                        hierarchy={Cartesian3.fromDegreesArray(boxCoords)}
                        material={new ImageMaterialProperty({
                            image: videoElement,
                            color: Color.WHITE.withAlpha(0.85),
                            transparent: true
                        })}
                        height={5}
                        extrudedHeight={0}
                        outline={false}
                    />
                </Entity>
            )}
        </>
    );
}
