import { useRef, useEffect, useCallback, useMemo, useState } from 'react';
import throttle from 'lodash/throttle';
import { Viewer, Cesium3DTileset, PostProcessStage, ScreenSpaceEventHandler, ScreenSpaceEvent } from 'resium';
import { Color, IonResource, Cartesian3, ScreenSpaceEventType, Math as CesiumMath, Cartographic } from 'cesium';

import { EarthquakeLayer } from './EarthquakeLayer';
import { SatelliteLayer } from './SatelliteLayer';
import { FlightLayer } from './FlightLayer';
import { VideoProjectionLayer } from './VideoProjectionLayer';
import { StreetTrafficLayer, WeatherRadarLayer } from './MockLayers';

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

import { applyPostProcess } from '../../core/postProcess';

// ═══════════════════════════════════════════════════════
// GlobeView Component
// ═══════════════════════════════════════════════════════

export default function GlobeContainer() {
    const viewerRef = useRef(null);
    const layers = useStore(state => state.layers);
    const visualMode = useStore(state => state.visualMode);
    const targetLocation = useStore(state => state.targetLocation);
    const fxSettings = useStore(state => state.fxSettings);
    const setMouseCoords = useStore(state => state.setMouseCoords);
    const setSelectedInfo = useStore(state => state.setSelectedInfo);

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

    // Handle Left Click for Custom InfoBox
    const handleLeftClick = useCallback((action) => {
        if (!viewerRef.current?.cesiumElement) return;
        const viewer = viewerRef.current.cesiumElement;

        // Use drillPick to find entities
        const pickedObjects = viewer.scene.drillPick(action.position);
        if (pickedObjects && pickedObjects.length > 0) {
            const entity = pickedObjects[0].id;
            if (entity && entity.name) {
                // Get description. Some properties are constant strings, some are dynamic
                const rawDesc = entity.description?.getValue ? entity.description.getValue(viewer.clock.currentTime) : entity.description;
                setSelectedInfo({
                    name: entity.name,
                    description: rawDesc ? String(rawDesc) : 'No telemetry data available.',
                    x: action.position.x,
                    y: action.position.y
                });
                return;
            }
        }
        setSelectedInfo(null);
    }, []);

    // Cesium Viewer Init & WebGL Cleanup
    useEffect(() => {
        if (viewerRef.current?.cesiumElement) {
            const viewer = viewerRef.current.cesiumElement;
            viewer.scene.globe.baseColor = Color.fromCssColorString('#050b14');
            viewer.scene.backgroundColor = Color.fromCssColorString('#050b14');
            viewer.scene.skyAtmosphere.show = true;
            viewer.scene.fog.enabled = true;
            viewer.scene.globe.depthTestAgainstTerrain = false; // Disabilita calcoli dispendiosi se non su zoom-in estremo

            // Forza requestRenderMode a True
            viewer.scene.requestRenderMode = true;
            viewer.scene.maximumRenderTimeChange = Infinity;

            // Mitigazione WebGL Lazy Initialization (Mipmap warnings)
            viewer.scene.fxaa = false; // Disable fast approximate anti-aliasing
            viewer.scene.msaaSamples = 1; // Disabilita Hardware Anti-Aliasing (fonte di lazy mipmaps proxy su Firefox/Chrome)
            viewer.scene.globe.maximumScreenSpaceError = 3; // Riduci caricamento texture aggressive per i tile lontani

            if (viewer.animation) viewer.animation.container.style.display = 'none';
            if (viewer.timeline) viewer.timeline.container.style.display = 'none';
            if (viewer.fullscreenButton) viewer.fullscreenButton.container.style.display = 'none';

            // Aggiorna la vista quando arrivano nuovi dati UI
            viewer.scene.requestRender();
        }

        // Cleanup radicale Context WebGL on Unmount (HMR Fix)
        return () => {
            if (viewerRef.current?.cesiumElement && !viewerRef.current?.cesiumElement.isDestroyed()) {
                viewerRef.current.cesiumElement.destroy();
            }
        };
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

    // Applicazione Programmatica degli Shader (Evita TypeError di Resium su postProcessStages)
    useEffect(() => {
        if (viewerRef.current?.cesiumElement && !viewerRef.current.cesiumElement.isDestroyed()) {
            applyPostProcess(viewerRef.current.cesiumElement, visualMode, fxSettings);
        }
    }, [visualMode, fxSettings]);

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
                selectionIndicator={true}
                sceneModePicker={false}
                navigationHelpButton={false}
                requestRenderMode={true} // Salva WebGL renderizzando solo se necessario
                maximumRenderTimeChange={Infinity}
                msaaSamples={1} // Inibisce MultiSample AntiAliasing (che trigghera texture lazy init)
                contextOptions={{
                    webgl: {
                        alpha: false,
                        antialias: false,
                        preserveDrawingBuffer: false,
                        failIfMajorPerformanceCaveat: false,
                        powerPreference: "high-performance"
                    }
                }}
            >
                {/* ─── 3D Buildings (OSM Ion) ─── */}
                <Cesium3DTileset
                    url={IonResource.fromAssetId(96188)}
                />

                {/* ─── MGRS Coordinate Tracker ─── */}
                <ScreenSpaceEventHandler>
                    <ScreenSpaceEvent action={handleMouseMove} type={ScreenSpaceEventType.MOUSE_MOVE} />
                    <ScreenSpaceEvent action={handleLeftClick} type={ScreenSpaceEventType.LEFT_CLICK} />
                </ScreenSpaceEventHandler>

                {/* ─── Dynamic Layers ─── */}
                {layers.earthquakes && <EarthquakeLayer />}
                {layers.satellites && <SatelliteLayer />}
                {layers.flights && <FlightLayer />}
                {layers.cctv && <VideoProjectionLayer />}
                {layers.streetTraffic && <StreetTrafficLayer />}
                {layers.weatherRadar && <WeatherRadarLayer />}

                {/* ─── Visual Mode Shaders ─── */}
                {/* Gli Shader JSX causavano TypeError/Race conditions col Viewer Lifecycle.
                    Gestiti ora dallo useEffect tramite `applyPostProcess` in modo Vanilla-Cesium. 
                */}

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
