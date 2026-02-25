import { renderHook, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useOpenSky } from './opensky';

// Facciamo il mock di fetch
global.fetch = vi.fn();

describe('useOpenSky', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('ritorna data vuota se non abilitato', () => {
        const { result } = renderHook(() => useOpenSky(false));
        expect(result.current.data).toEqual([]);
        expect(result.current.loading).toBe(false);
    });

    it('effettua fetch e popola data se abilitato', async () => {
        const mockFlights = {
            states: [
                ['123456', 'ALTR83', 'USA', null, null, -122.4, 37.7, 10000, false, 250, 45, null, null, null, null, false, 0]
            ]
        };

        fetch.mockResolvedValueOnce({
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
    });

    it('gestisce gli errori REST endpoint', async () => {
        fetch.mockResolvedValueOnce({
            ok: false
        });

        const { result } = renderHook(() => useOpenSky(true));

        await waitFor(() => {
            expect(result.current.error).toBe('OpenSky fetch failed');
        });
    });
});
