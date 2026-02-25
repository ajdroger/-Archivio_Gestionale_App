import { renderHook, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useUSGS } from './usgs';

// Mock di fetch globale
global.fetch = vi.fn();

describe('useUSGS', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('ritorna data vuota se non abilitato', () => {
        const { result } = renderHook(() => useUSGS(false));
        expect(result.current.data).toEqual([]);
        expect(result.current.loading).toBe(false);
    });

    it('effettua fetch e parsa il GeoJSON restituendo terremoti', async () => {
        // Mock GeoJSON USGS
        const mockGeoJSON = {
            features: [
                {
                    id: 'us1000',
                    properties: {
                        mag: 5.5,
                        place: '10km W of Testville',
                        time: 1618317000000
                    },
                    geometry: {
                        coordinates: [-120.5, 36.2, 10.0] // lng, lat, depth
                    }
                }
            ]
        };

        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => mockGeoJSON
        });

        const { result } = renderHook(() => useUSGS(true));

        expect(result.current.loading).toBe(true);

        await waitFor(() => {
            expect(result.current.loading).toBe(false);
        });

        expect(result.current.data).toHaveLength(1);
        expect(result.current.data[0].mag).toBe(5.5);
        expect(result.current.data[0].place).toBe('10km W of Testville');
        expect(result.current.data[0].lng).toBe(-120.5);
        expect(result.current.data[0].lat).toBe(36.2);
    });

    it('gestisce gli errori REST endpoint', async () => {
        fetch.mockResolvedValueOnce({
            ok: false
        });

        const { result } = renderHook(() => useUSGS(true));

        await waitFor(() => {
            expect(result.current.error).toBe('USGS fetch failed');
        });
    });
});
