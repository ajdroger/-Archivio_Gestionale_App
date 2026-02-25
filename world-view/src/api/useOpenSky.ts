import { useState, useEffect } from 'react';

export interface FlightData {
    icao24: string;
    callsign: string;
    origin_country: string;
    lng: number;
    lat: number;
    altitude: number;
    velocity: number;
    true_track: number;
}

export function useOpenSky(enabled: boolean) {
    const [data, setData] = useState<FlightData[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!enabled) {
            setData([]);
            return;
        }

        const fetchData = async () => {
            setLoading(true);
            setError(null);
            try {
                // OpenSky limits unauthenticated calls. We fetch a bounding box to avoid massive payloads if needed, 
                // or entire state vectors if allowed. Using global requires large payload.
                const response = await fetch('/api/opensky/states/all');
                if (!response.ok) throw new Error('OpenSky fetch failed');
                const json = await response.json();

                // Take a random sample or top 500 to keep performance optimal
                const limit = 500;
                type OpenSkyStateVector = (string | number | boolean | null)[];
                const flights: FlightData[] = json.states.slice(0, limit).filter((s: OpenSkyStateVector) => s[5] !== null && s[6] !== null).map((s: OpenSkyStateVector) => ({
                    icao24: String(s[0]),
                    callsign: typeof s[1] === 'string' ? s[1].trim() : 'UNKNOWN',
                    origin_country: String(s[2]),
                    lng: Number(s[5]),
                    lat: Number(s[6]),
                    altitude: Number(s[7] || s[13] || 0),
                    velocity: Number(s[9] || 0),
                    true_track: Number(s[10] || 0)
                }));

                setData(flights);
            } catch (err: unknown) {
                setError(err instanceof Error ? err.message : String(err));
            } finally {
                setLoading(false);
            }
        };

        fetchData();
        // Poll every 30 seconds (OpenSky update rate is ~10s, but we throttle to 30s for anon)
        const interval = setInterval(fetchData, 30 * 1000);
        return () => clearInterval(interval);
    }, [enabled]);

    return { data, loading, error };
}
