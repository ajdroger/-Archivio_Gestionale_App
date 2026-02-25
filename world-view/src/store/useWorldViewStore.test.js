import { describe, it, expect, beforeEach } from 'vitest';
import { useStore } from './useWorldViewStore';

describe('useWorldViewStore', () => {
    beforeEach(() => {
        // Zustand conserva lo stato nel volo, potremmo voler risettare tutto nel setup.
        // Simuliamo la reinizializzazione forzata
        const initialState = useStore.getState();
        useStore.setState(initialState, true);
    });

    it('dovrebbe avere earthquakes attivi di default', () => {
        const state = useStore.getState();
        expect(state.layers.earthquakes).toBe(true);
        expect(state.layers.flights).toBe(false);
    });

    it('toggleLayer dovrebbe invertire lo stato', () => {
        useStore.getState().toggleLayer('flights');
        expect(useStore.getState().layers.flights).toBe(true);
        useStore.getState().toggleLayer('flights');
        expect(useStore.getState().layers.flights).toBe(false);
    });

    it('setVisualMode dovrebbe cambiare mode e applicare un preset FX', () => {
        useStore.getState().setVisualMode('NVG');
        const state = useStore.getState();
        expect(state.visualMode).toBe('NVG');
        expect(state.fxSettings.bloom).toBeGreaterThan(0);
        // noise è maggiore per l'NVG
        expect(state.fxSettings.noise).toBe(0.15);
    });
});
