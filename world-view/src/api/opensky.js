import { useState, useEffect } from 'react';

// Questa API raccoglie i dati aggregati da ADSB.lol tramice PHP Multi-curl
// Sfruttando simultaneamente 10 macro-scacchieri globali (New York, Londra, Tokyo, Dubai ecc)
// Restituisce un JSON "Real-World" enorme senza colpire rate-limiting unificati.
export function useOpenSky(enabled) {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!enabled) {
            setData([]);
            return;
        }

        const fetchData = async () => {
            setLoading(true);
            setError(null);

            try {
                // Fetch proxy multi-hub
                const response = await fetch('/MCAG_Militare-Civile-Archivio-Gestionale/public/world-view/proxy.php?target=adsb_multihub');
                if (!response.ok) throw new Error('Global ADS-B MultiHub fetch failed');

                const json = await response.json();
                if (!json.ac) throw new Error('Invalid ADS-B MultiHub Payload');

                // Mappa il formato ADSBExchange (ac[]) nel formato compatibile per il FlightLayer
                const flights = json.ac.map(ac => {
                    // ADSBExchange returns speed in knots, convert to m/s
                    const velocity_ms = (ac.gs || 0) * 0.514444;
                    // Altitude is in feet, convert to meters
                    const altitude_m = (ac.alt_baro === 'ground' ? 0 : (ac.alt_baro || ac.alt_geom || 30000)) * 0.3048;

                    return {
                        icao24: String(ac.hex || Math.random().toString()),
                        callsign: typeof ac.flight === 'string' ? ac.flight.trim() : (ac.r || 'UNKNOWN'),
                        origin_country: typeof ac.r === 'string' ? ac.r : 'N/A', // Registration
                        lng: Number(ac.lon),
                        lat: Number(ac.lat),
                        altitude: Number(altitude_m),
                        velocity: Number(velocity_ms),
                        true_track: Number(ac.track || Object.prototype.hasOwnProperty.call(ac, 'tru') ? ac.tru : 0)
                    };
                });

                setData(flights);
            } catch (err) {
                setError(err instanceof Error ? err.message : String(err));
            } finally {
                setLoading(false);
            }
        };

        fetchData();

        // Polling ogni 30 secondi. La scansione decentralizzata php evita ban rapidi per proxy singoli IP
        const interval = setInterval(fetchData, 30 * 1000);
        return () => clearInterval(interval);

    }, [enabled]);

    return { data, loading, error };
}
