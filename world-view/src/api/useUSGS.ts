import { useState, useEffect } from 'react';

export interface EarthquakeData {
    id: string;
    mag: number;
    place: string;
    time: number;
    lat: number;
    lng: number;
    depth: number;
}

export function useUSGS(enabled: boolean) {
    const [data, setData] = useState<EarthquakeData[]>([]);
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
                // Fetch earthquakes from the past month (significant ones) or past 24h all.
                // USGS GeoJSON Feed: M2.5+ past 24 hours
                const response = await fetch('/api/usgs/2.5_day.geojson');
                if (!response.ok) throw new Error('USGS fetch failed');
                const json = await response.json();

                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                const earthquakes: EarthquakeData[] = json.features.map((f: any) => ({
                    id: String(f.id),
                    mag: Number(f.properties.mag),
                    place: String(f.properties.place),
                    time: Number(f.properties.time),
                    lng: Number(f.geometry.coordinates[0]),
                    lat: Number(f.geometry.coordinates[1]),
                    depth: Number(f.geometry.coordinates[2])
                }));

                setData(earthquakes);
            } catch (err: unknown) {
                setError(err instanceof Error ? err.message : String(err));
            } finally {
                setLoading(false);
            }
        };

        fetchData();
        // Poll every 5 minutes
        const interval = setInterval(fetchData, 5 * 60 * 1000);
        return () => clearInterval(interval);
    }, [enabled]);

    return { data, loading, error };
}
