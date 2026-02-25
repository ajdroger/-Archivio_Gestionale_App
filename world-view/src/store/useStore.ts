import { create } from 'zustand';

// ── Types ──────────────────────────────────────────────
export type VisualMode = 'NORMAL' | 'CRT' | 'NVG' | 'FLIR' | 'THERMAL' | 'ANIME' | 'NOIR' | 'SNOW' | 'AI';

export interface LocationDest {
    lat: number;
    lng: number;
    alt: number;
    name: string;
}

export interface LayersState {
    earthquakes: boolean;
    flights: boolean;
    satellites: boolean;
    cctv: boolean;
}

export interface FxSettings {
    distortion: number;
    bloom: number;
    scanlines: number;
    noise: number;
    pixelation: number;
    sharpen: number;
    panopticOpacity: number;
}

// ── FX Presets per Visual Mode ─────────────────────────
const FX_PRESETS: Record<VisualMode, Partial<FxSettings>> = {
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

// ── Store Interface ────────────────────────────────────
interface WorldViewState {
    // Layers
    layers: LayersState;
    toggleLayer: (key: keyof LayersState) => void;

    // Visual Mode
    visualMode: VisualMode;
    setVisualMode: (mode: VisualMode) => void;

    // Camera Target
    targetLocation: LocationDest | null;
    setTargetLocation: (loc: LocationDest | null) => void;

    // FX Settings
    fxSettings: FxSettings;
    setFxSetting: (key: keyof FxSettings, value: number) => void;
    setAllFxSettings: (settings: FxSettings) => void;
}

// ── Store ──────────────────────────────────────────────
export const useStore = create<WorldViewState>((set) => ({
    // Layers — default earthquakes ON
    layers: {
        earthquakes: true,
        flights: false,
        satellites: false,
        cctv: false,
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
}));
