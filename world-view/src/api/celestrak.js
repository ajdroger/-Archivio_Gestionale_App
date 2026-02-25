import { useState, useEffect } from 'react';
import * as satellite from 'satellite.js';

export function useCelesTrak(enabled) {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!enabled) {
            setData([]);
            return;
        }

        const TLE_URL = '/MCAG_Militare-Civile-Archivio-Gestionale/public/world-view/proxy.php?target=celestrak';

        const fetchTLE = async () => {
            setLoading(true);
            setError(null);
            try {
                const response = await fetch(TLE_URL);
                if (!response.ok) throw new Error('CelesTrak fetch failed');
                const text = await response.text();
                const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 0);

                // Parse TLE pairs (every 3 lines: Name, Line 1, Line 2)
                const satRecords = [];
                for (let i = 0; i < lines.length && i < 3000; i += 3) {
                    // Limit to first 1000 sats (3000 lines) for performance
                    const name = lines[i];
                    const tleLine1 = lines[i + 1];
                    const tleLine2 = lines[i + 2];
                    if (tleLine1 && tleLine2) {
                        try {
                            const satrec = satellite.twoline2satrec(tleLine1, tleLine2);
                            const positionAndVelocity = satellite.propagate(satrec, new Date());
                            const positionEci = positionAndVelocity ? positionAndVelocity.position : null;
                            if (typeof positionEci !== 'boolean' && positionEci) {
                                const gmst = satellite.gstime(new Date());
                                const positionGd = satellite.eciToGeodetic(positionEci, gmst);

                                const longitudeDeg = satellite.degreesLong(positionGd.longitude);
                                const latitudeDeg = satellite.degreesLat(positionGd.latitude);
                                const altitudeKm = positionGd.height;

                                if (!isNaN(longitudeDeg) && !isNaN(latitudeDeg) && !isNaN(altitudeKm)) {
                                    satRecords.push({
                                        id: satrec.satnum || name,
                                        name: name,
                                        lat: latitudeDeg,
                                        lng: longitudeDeg,
                                        alt: altitudeKm
                                    });
                                }
                            }
                        } catch {
                            // Ignore invalid TLE parsing
                        }
                    }
                }

                setData(satRecords);
            } catch (err) {
                setError(err instanceof Error ? err.message : String(err));
            } finally {
                setLoading(false);
            }
        };

        fetchTLE();
        // Aggiorna posizioni ricaricando i TLE aggiornati (o ripropagando) ogni 60 secondi
        const interval = setInterval(fetchTLE, 60 * 1000);
        return () => clearInterval(interval);
    }, [enabled]);

    return { data, loading, error };
}
