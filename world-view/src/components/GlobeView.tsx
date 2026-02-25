import { useRef, useEffect } from 'react';
import { Viewer, Cesium3DTileset, PostProcessStage } from 'resium';
import { Color, IonResource, Cartesian3 } from 'cesium';

import { EarthquakeLayer } from './Layers/EarthquakeLayer';
import { SatelliteLayer } from './Layers/SatelliteLayer';
import { FlightLayer } from './Layers/FlightLayer';
import { VideoProjectionLayer } from './Layers/VideoProjectionLayer';

import { useStore } from '../store/useStore';

// ═══════════════════════════════════════════════════════
// GLSL Fragment Shaders — Post-Processing Visual Modes
// ═══════════════════════════════════════════════════════

const NVG_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float luminance = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    float noise = fract(sin(dot(v_textureCoordinates * 500.0, vec2(12.9898, 78.233))) * 43758.5453);
    vec3 nvgColor = vec3(0.1, 0.9, 0.2) * (luminance + noise * 0.15);
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
    vec3 thermal = vec3(t, t * 0.4, t * 0.1);
    fragColor = vec4(thermal, 1.0);
}
`;

const THERMAL_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float lum = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    // White-hot palette: cold→blue, warm→red/yellow, hot→white
    vec3 cold  = vec3(0.0, 0.0, 0.5);
    vec3 mid   = vec3(1.0, 0.3, 0.0);
    vec3 hot   = vec3(1.0, 1.0, 0.8);
    vec3 result;
    if (lum < 0.5) {
        result = mix(cold, mid, lum * 2.0);
    } else {
        result = mix(mid, hot, (lum - 0.5) * 2.0);
    }
    fragColor = vec4(result, 1.0);
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
        fragColor = vec4(0.0, 0.0, 0.0, 1.0);
        return;
    }
    vec4 color = texture(colorTexture, uv);
    color.rgb *= (0.85 + 0.15 * sin(uv.y * 800.0));
    color.rgb *= vec3(0.9, 1.0, 0.95);
    fragColor = color;
}
`;

const ANIME_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec2 uv = v_textureCoordinates;
    vec4 color = texture(colorTexture, uv);
    // Cel-shading: quantize luminance to 4 bands
    float lum = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    float q = floor(lum * 4.0 + 0.5) / 4.0;
    vec3 cel = color.rgb * (q / max(lum, 0.001));
    // Sobel edge detection
    float tx = 1.0 / 1920.0;
    float ty = 1.0 / 1080.0;
    float tl = dot(texture(colorTexture, uv + vec2(-tx, -ty)).rgb, vec3(0.333));
    float t  = dot(texture(colorTexture, uv + vec2( 0., -ty)).rgb, vec3(0.333));
    float tr = dot(texture(colorTexture, uv + vec2( tx, -ty)).rgb, vec3(0.333));
    float ml = dot(texture(colorTexture, uv + vec2(-tx,  0.)).rgb, vec3(0.333));
    float mr = dot(texture(colorTexture, uv + vec2( tx,  0.)).rgb, vec3(0.333));
    float bl = dot(texture(colorTexture, uv + vec2(-tx,  ty)).rgb, vec3(0.333));
    float b  = dot(texture(colorTexture, uv + vec2( 0.,  ty)).rgb, vec3(0.333));
    float br = dot(texture(colorTexture, uv + vec2( tx,  ty)).rgb, vec3(0.333));
    float gx = -tl - 2.0*ml - bl + tr + 2.0*mr + br;
    float gy = -tl - 2.0*t  - tr + bl + 2.0*b  + br;
    float edge = sqrt(gx*gx + gy*gy);
    vec3 outline = mix(cel, vec3(0.0), smoothstep(0.08, 0.15, edge));
    // Saturate colors
    float sat = 1.4;
    float grayVal = dot(outline, vec3(0.299, 0.587, 0.114));
    vec3 saturated = mix(vec3(grayVal), outline, sat);
    fragColor = vec4(saturated, 1.0);
}
`;

const NOIR_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float lum = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    // High contrast B&W
    float contrast = 2.2;
    lum = clamp((lum - 0.5) * contrast + 0.5, 0.0, 1.0);
    // Film grain
    float noise = fract(sin(dot(v_textureCoordinates * 800.0, vec2(12.9898, 78.233))) * 43758.5453);
    lum += (noise - 0.5) * 0.12;
    // Slight sepia tint
    vec3 bw = vec3(lum * 1.0, lum * 0.95, lum * 0.85);
    fragColor = vec4(bw, 1.0);
}
`;

const SNOW_SHADER = `
uniform sampler2D colorTexture;
uniform float czm_frameNumber;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec2 uv = v_textureCoordinates;
    // Signal loss effect — random white-noise static
    float rnd = fract(sin(dot(uv * 400.0 + czm_frameNumber * 0.01, vec2(12.9898, 78.233))) * 43758.5453);
    float rnd2 = fract(sin(dot(uv * 200.0 - czm_frameNumber * 0.007, vec2(39.346, 11.135))) * 43758.5453);
    // Mix signal with noise based on vertical scanline pattern
    float signal = step(0.3, rnd2);
    vec4 color = texture(colorTexture, uv);
    vec3 noiseColor = vec3(rnd);
    vec3 result = mix(noiseColor, color.rgb, signal * 0.6);
    // Horizontal jitter bars
    float bar = step(0.97, fract(uv.y * 30.0 + czm_frameNumber * 0.002));
    result = mix(result, vec3(1.0), bar * 0.3);
    fragColor = vec4(result, 1.0);
}
`;

const AI_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec2 uv = v_textureCoordinates;
    float tx = 1.0 / 1920.0;
    float ty = 1.0 / 1080.0;
    // Sobel edge detection for wireframe look
    float tl = dot(texture(colorTexture, uv + vec2(-tx, -ty)).rgb, vec3(0.333));
    float t  = dot(texture(colorTexture, uv + vec2( 0., -ty)).rgb, vec3(0.333));
    float tr = dot(texture(colorTexture, uv + vec2( tx, -ty)).rgb, vec3(0.333));
    float ml = dot(texture(colorTexture, uv + vec2(-tx,  0.)).rgb, vec3(0.333));
    float mr = dot(texture(colorTexture, uv + vec2( tx,  0.)).rgb, vec3(0.333));
    float bl = dot(texture(colorTexture, uv + vec2(-tx,  ty)).rgb, vec3(0.333));
    float b  = dot(texture(colorTexture, uv + vec2( 0.,  ty)).rgb, vec3(0.333));
    float br = dot(texture(colorTexture, uv + vec2( tx,  ty)).rgb, vec3(0.333));
    float gx = -tl - 2.0*ml - bl + tr + 2.0*mr + br;
    float gy = -tl - 2.0*t  - tr + bl + 2.0*b  + br;
    float edge = sqrt(gx*gx + gy*gy);
    // Cyan wireframe on dark background
    vec3 dark = vec3(0.02, 0.03, 0.05);
    vec3 wire = vec3(0.0, 0.94, 1.0); // #00f0ff
    float edgeFactor = smoothstep(0.03, 0.1, edge);
    vec3 result = mix(dark, wire, edgeFactor);
    // Add faint original color in bright areas
    vec4 orig = texture(colorTexture, uv);
    float origLum = dot(orig.rgb, vec3(0.299, 0.587, 0.114));
    result += orig.rgb * 0.08 * origLum;
    fragColor = vec4(result, 1.0);
}
`;

const PIXELATION_SHADER = `
uniform sampler2D colorTexture;
uniform float pixelSize;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec2 uv = v_textureCoordinates;
    if (pixelSize > 0.001) {
        float ps = mix(1.0, 128.0, pixelSize);
        vec2 d = vec2(ps / 1920.0, ps / 1080.0);
        uv = d * floor(uv / d);
    }
    fragColor = texture(colorTexture, uv);
}
`;

// ═══════════════════════════════════════════════════════
// GlobeView Component
// ═══════════════════════════════════════════════════════

export default function GlobeView() {
    const viewerRef = useRef<any>(null);
    const { layers, visualMode, targetLocation, fxSettings } = useStore();

    // Cesium Viewer Init
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

    // Camera Jump
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
                {/* 3D Buildings (OSM Ion) */}
                <Cesium3DTileset
                    url={IonResource.fromAssetId(96188)}
                />

                {/* ─── Dynamic Layers ─── */}
                {layers.earthquakes && <EarthquakeLayer />}
                {layers.satellites && <SatelliteLayer />}
                {layers.flights && <FlightLayer />}
                {layers.cctv && <VideoProjectionLayer />}

                {/* ─── Visual Mode Shaders ─── */}
                {visualMode === 'NVG' && <PostProcessStage
                    fragmentShader={NVG_SHADER}
                    uniforms={{ noiseIntensity: fxSettings.noise, bloomFactor: fxSettings.bloom }}
                />}
                {visualMode === 'FLIR' && <PostProcessStage
                    fragmentShader={FLIR_SHADER}
                    uniforms={{ thermalIntensity: fxSettings.bloom }}
                />}
                {visualMode === 'THERMAL' && <PostProcessStage
                    fragmentShader={THERMAL_SHADER}
                    uniforms={{}}
                />}
                {visualMode === 'CRT' && <PostProcessStage
                    fragmentShader={CRT_SHADER}
                    uniforms={{ distortionAmount: fxSettings.distortion, bloom: fxSettings.bloom }}
                />}
                {visualMode === 'ANIME' && <PostProcessStage
                    fragmentShader={ANIME_SHADER}
                    uniforms={{}}
                />}
                {visualMode === 'NOIR' && <PostProcessStage
                    fragmentShader={NOIR_SHADER}
                    uniforms={{}}
                />}
                {visualMode === 'SNOW' && <PostProcessStage
                    fragmentShader={SNOW_SHADER}
                    uniforms={{}}
                />}
                {visualMode === 'AI' && <PostProcessStage
                    fragmentShader={AI_SHADER}
                    uniforms={{}}
                />}

                {/* ─── Pixelation (always-on when > 0) ─── */}
                {fxSettings.pixelation > 0.01 && <PostProcessStage
                    fragmentShader={PIXELATION_SHADER}
                    uniforms={{ pixelSize: fxSettings.pixelation }}
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
