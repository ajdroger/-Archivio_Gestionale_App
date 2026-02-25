import { useRef, useEffect, useCallback, useMemo } from 'react';
import throttle from 'lodash/throttle';
import { Viewer, Cesium3DTileset, PostProcessStage, ScreenSpaceEventHandler, ScreenSpaceEvent } from 'resium';
import { Color, IonResource, Cartesian3, ScreenSpaceEventType, Math as CesiumMath, Cartographic } from 'cesium';

import { EarthquakeLayer } from './EarthquakeLayer';
import { SatelliteLayer } from './SatelliteLayer';
import { FlightLayer } from './FlightLayer';
import { VideoProjectionLayer } from './VideoProjectionLayer';

import { useStore } from '../../store/useWorldViewStore';
import { latLonToMGRS } from '../../utils/mgrs';

// ═══════════════════════════════════════════════════════
// GLSL Fragment Shaders — Post-Processing Visual Modes
// ═══════════════════════════════════════════════════════

import NVG_SHADER from '../../shaders/nvg.glsl?raw';
import FLIR_SHADER from '../../shaders/flir.glsl?raw';
import THERMAL_SHADER from '../../shaders/thermal.glsl?raw';
import CRT_SHADER from '../../shaders/crt.glsl?raw';
import ANIME_SHADER from '../../shaders/anime.glsl?raw';
import NOIR_SHADER from '../../shaders/noir.glsl?raw';
import SNOW_SHADER from '../../shaders/snow.glsl?raw';
import AI_SHADER from '../../shaders/ai.glsl?raw';
import PIXELATION_SHADER from '../../shaders/pixelation.glsl?raw';

// ═══════════════════════════════════════════════════════
// GlobeView Component
// ═══════════════════════════════════════════════════════

export default function GlobeContainer() {
    const viewerRef = useRef(null);
    const { layers, visualMode, targetLocation, fxSettings, setMouseCoords } = useStore();

    // Throttled update function to heavily reduce React renders from mouse movement
    const throttledUpdate = useMemo(() => throttle((lat, lng, alt) => {
        setMouseCoords({
            lat,
            lng,
            alt,
            mgrs: latLonToMGRS(lat, lng)
        });
    }, 100), [setMouseCoords]);

    // Mouse Tracking for HUD Coordinates
    const handleMouseMove = useCallback((action) => {
        if (!viewerRef.current?.cesiumElement) return;
        const viewer = viewerRef.current.cesiumElement;
        const position = action.endPosition;
        if (!position) return;

        const ray = viewer.camera.getPickRay(position);
        if (!ray) return;

        const cartesian = viewer.scene.globe.pick(ray, viewer.scene);
        if (cartesian) {
            const cartographic = Cartographic.fromCartesian(cartesian);
            const lat = CesiumMath.toDegrees(cartographic.latitude);
            const lng = CesiumMath.toDegrees(cartographic.longitude);
            const alt = viewer.camera.positionCartographic.height; // Simulated drone alt from camera height

            throttledUpdate(lat, lng, alt);
        }
    }, [throttledUpdate]);

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
                {/* ─── 3D Buildings (OSM Ion) ─── */}
                <Cesium3DTileset
                    url={IonResource.fromAssetId(96188)}
                />

                {/* ─── MGRS Coordinate Tracker ─── */}
                <ScreenSpaceEventHandler>
                    <ScreenSpaceEvent action={handleMouseMove} type={ScreenSpaceEventType.MOUSE_MOVE} />
                </ScreenSpaceEventHandler>

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
