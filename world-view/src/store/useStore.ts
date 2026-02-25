import { create } from 'zustand';

interface FxSettings {
    distortion: number;
    bloom: number;
    scanlines: number;
    noise: number;
}

interface WorldViewState {
    fxSettings: FxSettings;
    setFxSetting: (key: keyof FxSettings, value: number) => void;
    setAllFxSettings: (settings: FxSettings) => void;
}

export const useStore = create<WorldViewState>((set) => ({
    fxSettings: {
        distortion: 0.15,
        bloom: 0.8,
        scanlines: 0.5,
        noise: 0.05
    },
    setFxSetting: (key, value) =>
        set((state) => ({ fxSettings: { ...state.fxSettings, [key]: value } })),
    setAllFxSettings: (settings) =>
        set(() => ({ fxSettings: settings }))
}));
