import { useEffect, useState } from 'react';
import { Entity, PolygonGraphics } from 'resium';
import { Cartesian3, Color, ImageMaterialProperty } from 'cesium';

// Coordinate Bounding Box approssimative incrocio 6th St Austin, TX
// Basso-sinistra (Ovest, Sud), Alto-destra (Est, Nord)
const AUSTIN_CCTV_BBOX = [
    -97.740, 30.266, // P1 (SW)
    -97.739, 30.266, // P2 (SE)
    -97.739, 30.267, // P3 (NE)
    -97.740, 30.267  // P4 (NW)
];

export function VideoProjectionLayer() {
    const [videoElement, setVideoElement] = useState<HTMLVideoElement | null>(null);

    useEffect(() => {
        // Creazione dinamica di un elemento video fittizio (Video Feed simulato Austin)
        // Sostituire con l'RTSP stream o MJPEG reale in fase di prod
        const vid = document.createElement('video');
        vid.src = 'https://cesium.com/public/SandcastleSampleData/big-buck-bunny_trailer.mp4'; // Placeholder video pubblico accessibile via CORS
        vid.crossOrigin = 'anonymous';
        vid.loop = true;
        vid.muted = true;
        vid.play().catch(e => console.error("Auto-play CCTV projection denied:", e));
        setVideoElement(vid);

        return () => {
            vid.pause();
            vid.removeAttribute('src');
            vid.load();
        };
    }, []);

    return (
        <>
            {videoElement && (
                <Entity
                    name="CCTV Projection Austin 6th St"
                    description="Live Video-to-Ground projection from Mesh Node 4"
                >
                    <PolygonGraphics
                        hierarchy={Cartesian3.fromDegreesArray(AUSTIN_CCTV_BBOX)}
                        material={new ImageMaterialProperty({
                            image: videoElement,
                            color: Color.WHITE.withAlpha(0.85) // Overlay semitrasparente sul 3D Terrain
                        })}
                        height={5} // Alzato di 5 metri sopra il terreno per evitare z-fighting col ground
                        extrudedHeight={0}
                    />
                </Entity>
            )}
        </>
    );
}
