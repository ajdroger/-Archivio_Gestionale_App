import { useRef, useEffect } from 'react';
import { Viewer, Cesium3DTileset, PostProcessStage } from 'resium';
import { Color, IonResource, Cartesian3 } from 'cesium';

import { SatelliteLayer } from './Layers/SatelliteLayer';
import { FlightLayer } from './Layers/FlightLayer';
import { VideoProjectionLayer } from './Layers/VideoProjectionLayer';
import type { LocationDest } from './UI/BottomToolbar';

import { useStore } from '../store/useStore';

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
    const { fxSettings } = useStore();

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
                {visualMode === 'NVG' && <PostProcessStage
                    fragmentShader={NVG_SHADER}
                    uniforms={{ noiseIntensity: fxSettings.noise, bloomFactor: fxSettings.bloom }}
                />}
                {visualMode === 'FLIR' && <PostProcessStage
                    fragmentShader={FLIR_SHADER}
                    uniforms={{ thermalIntensity: fxSettings.bloom }}
                />}
                {visualMode === 'THERMAL' && <PostProcessStage
                    fragmentShader={FLIR_SHADER}
                    uniforms={{ thermalIntensity: fxSettings.bloom }}
                />}
                {visualMode === 'CRT' && <PostProcessStage
                    fragmentShader={CRT_SHADER}
                    uniforms={{ distortionAmount: fxSettings.distortion, bloom: fxSettings.bloom }}
                />}
            </Viewer>

            {/* Reticolo di Puntamento HUD Centrale */}
            <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none z-10 opacity-60 mix-blend-screen scale-125 transition-transform duration-300">
                <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="60" cy="60" r="50" stroke="#00f0ff" strokeWidth="1" strokeDasharray="4 8" />
                    <circle cx="60" cy="60" r="30" stroke="#00ff41" strokeWidth="0.5" />
                    <path d="M60 0 L60 45" stroke="#ff3333" strokeWidth="1.5" />
                    <path d="M60 120 L60 75" stroke="#ff3333" strokeWidth="1.5" />
                    <path d="M0 60 L45 60" stroke="#ff3333" strokeWidth="1.5" />
                    <path d="M120 60 L75 60" stroke="#ff3333" strokeWidth="1.5" />
                    <circle cx="60" cy="60" r="2" fill="#ffb000" />
                </svg>
            </div>
        </div>
    );
}
