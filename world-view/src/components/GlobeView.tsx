import { useRef, useEffect } from 'react';
import { Viewer, Cesium3DTileset, PostProcessStage } from 'resium';
import { Color, IonResource, Cartesian3 } from 'cesium';

import { SatelliteLayer } from './Layers/SatelliteLayer';
import { FlightLayer } from './Layers/FlightLayer';
import { VideoProjectionLayer } from './Layers/VideoProjectionLayer';
import type { LocationDest } from './UI/BottomToolbar';

interface GlobeViewProps {
    layers: {
        earthquakes: boolean;
        flights: boolean;
        satellites: boolean;
        cctv: boolean;
    };
    visualMode: string;
    targetLocation?: LocationDest | null;
}

// GLSL Fragment Shaders
const NVG_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float luminance = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    float noise = fract(sin(dot(v_textureCoordinates, vec2(12.9898, 78.233))) * 43758.5453);
    vec3 nvgColor = vec3(0.1, 0.9, 0.2) * (luminance + noise * 0.2);
    fragColor = vec4(nvgColor, 1.0);
}
`;

const FLIR_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float luminance = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    float t = luminance * 1.5;
    vec3 thermal = vec3(t, t*0.4, t*0.1);
    fragColor = vec4(thermal, 1.0);
}
`;

const CRT_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec2 uv = v_textureCoordinates;
    vec2 cc = uv - 0.5;
    float dist = dot(cc, cc);
    uv = uv + cc * (dist * 0.2);
    
    if(uv.x < 0.0 || uv.x > 1.0 || uv.y < 0.0 || uv.y > 1.0) {
        fragColor = vec4(0.0,0.0,0.0,1.0);
        return;
    }
    
    vec4 color = texture(colorTexture, uv);
    color.rgb *= (0.85 + 0.15 * sin(uv.y * 800.0));
    color.rgb *= vec3(0.9, 1.0, 0.95);
    fragColor = color;
}
`;

export default function GlobeView({ layers, visualMode, targetLocation }: GlobeViewProps) {
    const viewerRef = useRef<any>(null);

    // Styling Iniziale
    useEffect(() => {
        if (viewerRef.current?.cesiumElement) {
            const viewer = viewerRef.current.cesiumElement;
            viewer.scene.globe.baseColor = Color.fromCssColorString('#050b14');
            viewer.scene.backgroundColor = Color.fromCssColorString('#050b14');
            viewer.scene.skyAtmosphere.show = true;
            viewer.scene.fog.enabled = true;

            if (viewer.animation) viewer.animation.container.style.display = 'none';
            if (viewer.timeline) viewer.timeline.container.style.display = 'none';
            if (viewer.fullscreenButton) viewer.fullscreenButton.container.style.display = 'none';
        }
    }, []);

    // Handler Jump Camera
    useEffect(() => {
        if (targetLocation && viewerRef.current?.cesiumElement) {
            const viewer = viewerRef.current.cesiumElement;
            viewer.camera.flyTo({
                destination: Cartesian3.fromDegrees(targetLocation.lng, targetLocation.lat, targetLocation.alt),
                duration: 2.5
            });
        }
    }, [targetLocation]);

    return (
        <div className="absolute inset-0 z-0">
            <Viewer
                ref={viewerRef}
                full
                animation={false}
                timeline={false}
                baseLayerPicker={false}
                geocoder={false}
                homeButton={false}
                infoBox={false}
                sceneModePicker={false}
                navigationHelpButton={false}
                requestRenderMode={true}
                maximumRenderTimeChange={Infinity}
            >
                {/* Layer 3D Buildings (Standard OSM Ion) */}
                <Cesium3DTileset
                    url={IonResource.fromAssetId(96188)}
                />

                {/* --- Layers Dinamici --- */}
                {layers.satellites && <SatelliteLayer />}
                {layers.flights && <FlightLayer />}
                {layers.cctv && <VideoProjectionLayer />}

                {/* --- Shaders FX via Resium --- */}
                {visualMode === 'NVG' && <PostProcessStage fragmentShader={NVG_SHADER} />}
                {visualMode === 'FLIR' && <PostProcessStage fragmentShader={FLIR_SHADER} />}
                {visualMode === 'THERMAL' && <PostProcessStage fragmentShader={FLIR_SHADER} />}
                {visualMode === 'CRT' && <PostProcessStage fragmentShader={CRT_SHADER} />}
            </Viewer>
        </div>
    );
}
