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
                const response = await fetch('https://opensky-network.org/api/states/all');
                if (!response.ok) throw new Error('OpenSky fetch failed');
                const json = await response.json();

                // Take a random sample or top 500 to keep performance optimal
                const limit = 500;
                const flights: FlightData[] = json.states.slice(0, limit).filter((s: any) => s[5] !== null && s[6] !== null).map((s: any) => ({
                    icao24: s[0],
                    callsign: s[1]?.trim() || 'UNKNOWN',
                    origin_country: s[2],
                    lng: s[5], // longitude
                    lat: s[6], // latitude
                    altitude: s[7] || s[13], // baro_altitude or geo_altitude
                    velocity: s[9],
                    true_track: s[10] // heading
                }));

                setData(flights);
            } catch (err: any) {
                setError(err.message);
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
