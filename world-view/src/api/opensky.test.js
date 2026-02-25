import { renderHook, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useOpenSky } from './opensky';

describe('useOpenSky Real ADSB Multi-Hub', () => {
    let originalFetch;

    beforeEach(() => {
        originalFetch = global.fetch;
        global.fetch = vi.fn();
    });

    afterEach(() => {
        global.fetch = originalFetch;
    });

    it('ritorna data vuota se non abilitato', () => {
        const { result } = renderHook(() => useOpenSky(false));
        expect(result.current.data).toEqual([]);
        expect(result.current.loading).toBe(false);
    });

    it('effettua fetch del proxy multihub e mappa data', async () => {
        const mockFlights = {
            ac: [
                { hex: '123ABC', flight: 'ALTR83', r: 'USA', lat: 37.7, lon: -122.4, alt_baro: 30000, gs: 450, track: 90 }
            ]
        };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => mockFlights
        });

        const { result } = renderHook(() => useOpenSky(true));

        expect(result.current.loading).toBe(true);

        await waitFor(() => {
            expect(result.current.loading).toBe(false);
        });

        expect(result.current.data).toHaveLength(1);
        expect(result.current.data[0].callsign).toBe('ALTR83');
        expect(result.current.data[0].lat).toBe(37.7);
        // gs in knots (450) translated to ms 
        expect(result.current.data[0].velocity).toBeCloseTo(231.5);
    });

    it('gestisce gli errori 500 del multihub', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false
        });

        const { result } = renderHook(() => useOpenSky(true));

        await waitFor(() => {
            expect(result.current.error).toBe('Global ADS-B MultiHub fetch failed');
        });
    });
});
