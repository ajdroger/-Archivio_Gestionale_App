import { create } from 'zustand';

// ── FX Presets per Visual Mode ─────────────────────────
const FX_PRESETS = {
    NORMAL: { distortion: 0, bloom: 0.2, scanlines: 0, noise: 0 },
    CRT: { distortion: 0.15, bloom: 0.8, scanlines: 0.5, noise: 0.05 },
    NVG: { distortion: 0.05, bloom: 1.2, scanlines: 0.2, noise: 0.15 },
    FLIR: { distortion: 0, bloom: 1.0, scanlines: 0.1, noise: 0.08 },
    THERMAL: { distortion: 0, bloom: 1.5, scanlines: 0, noise: 0.02 },
    ANIME: { distortion: 0, bloom: 0.6, scanlines: 0, noise: 0 },
    NOIR: { distortion: 0.05, bloom: 0.4, scanlines: 0.3, noise: 0.12 },
    SNOW: { distortion: 0.3, bloom: 0.1, scanlines: 0.8, noise: 0.4 },
    AI: { distortion: 0, bloom: 1.0, scanlines: 0.1, noise: 0 },
};

// ── Store ──────────────────────────────────────────────
export const useStore = create((set) => ({
    // Layers — default earthquakes OFF
    layers: {
        earthquakes: false,
        flights: false,
        satellites: false,
        cctv: false,
        streetTraffic: false,
        weatherRadar: false,
    },
    toggleLayer: (key) =>
        set((state) => ({ layers: { ...state.layers, [key]: !state.layers[key] } })),

    // Visual Mode — default CRT
    visualMode: 'CRT',
    setVisualMode: (mode) =>
        set((state) => {
            const preset = FX_PRESETS[mode];
            return {
                visualMode: mode,
                fxSettings: { ...state.fxSettings, ...preset }
            };
        }),

    // Camera
    targetLocation: null,
    setTargetLocation: (loc) => set(() => ({ targetLocation: loc })),

    // FX Settings — CRT defaults
    fxSettings: {
        distortion: 0.15,
        bloom: 0.8,
        scanlines: 0.5,
        noise: 0.05,
        pixelation: 0,
        sharpen: 0.5,
        panopticOpacity: 0.74,
    },
    setFxSetting: (key, value) =>
        set((state) => ({ fxSettings: { ...state.fxSettings, [key]: value } })),
    setAllFxSettings: (settings) =>
        set(() => ({ fxSettings: settings })),

    // Real-Time HUD Coords
    mouseCoords: { lat: 0, lng: 0, alt: 0, mgrs: 'OUT OF BOUNDS' },
    setMouseCoords: (coords) => set(() => ({ mouseCoords: coords })),

    // CCTV Params default
    cctvParams: { heading: 0, pitch: -45, roll: 0, fov: 60, range: 100 },
    setCctvParam: (key, value) => set((state) => ({ cctvParams: { ...state.cctvParams, [key]: value } })),

    // Tactical InfoBox
    selectedInfo: null,
    setSelectedInfo: (info) => set(() => ({ selectedInfo: info })),
}));
