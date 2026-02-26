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
                // Rimuovi \r e splitta per newline, filtrando stringhe vuote
                const lines = text.replace(/\r/g, '').split('\n').map(l => l.trim()).filter(l => l.length > 0);

                const satRecords = [];
                // Un TLE CelesTrak standard possiede 3 linee: Titolo, Linea1, Linea 2
                for (let i = 0; i < lines.length - 2; i += 3) {
                    if (satRecords.length >= 1500) break; // Hard limit rendering performances (1500 satelliti)

                    const name = lines[i];
                    const tleLine1 = lines[i + 1];
                    const tleLine2 = lines[i + 2];

                    if (tleLine1 && tleLine2 && tleLine1.startsWith('1 ') && tleLine2.startsWith('2 ')) {
                        try {
                            const satrec = satellite.twoline2satrec(tleLine1, tleLine2);
                            if (!satrec) continue;

                            const positionAndVelocity = satellite.propagate(satrec, new Date());
                            const positionEci = positionAndVelocity ? positionAndVelocity.position : null;

                            // Check validità posizione 
                            if (typeof positionEci !== 'boolean' && positionEci) {
                                const gmst = satellite.gstime(new Date());
                                const positionGd = satellite.eciToGeodetic(positionEci, gmst);

                                const longitudeDeg = satellite.degreesLong(positionGd.longitude);
                                const latitudeDeg = satellite.degreesLat(positionGd.latitude);
                                const altitudeKm = positionGd.height;

                                if (isFinite(longitudeDeg) && isFinite(latitudeDeg) && isFinite(altitudeKm)) {
                                    satRecords.push({
                                        id: name.replace(/\s+/g, '_') + '_' + satrec.satnum,
                                        name: name,
                                        lat: latitudeDeg,
                                        lng: longitudeDeg,
                                        alt: altitudeKm
                                    });
                                }
                            }
                        } catch (err) {
                            // Ignore specific parse failures per singolo satellite
                            console.warn("TLE Parsing failed for: ", name);
                        }
                    }
                }

                if (satRecords.length === 0) {
                    console.error("[WORLDVIEW CelesTrak] Parsing fallito o file Vuoto. Text Preview: ", text.substring(0, 150));
                } else {
                    console.log(`[WORLDVIEW CelesTrak] ${satRecords.length} vettori in Orbita caricati ed elaborati tramite TLE Parser.`);
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
