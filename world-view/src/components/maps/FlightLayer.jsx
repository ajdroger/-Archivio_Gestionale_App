import React, { useEffect, useRef } from 'react';
import { useCesium } from 'resium';
import { CustomDataSource, Cartesian3, Cartesian2, Color, CallbackProperty, JulianDate } from 'cesium';
import { useOpenSky } from '../../api/opensky';

export function FlightLayer() {
    const { data: flights } = useOpenSky(true);
    const { viewer } = useCesium();
    const dataSourceRef = useRef(null);

    // Inizializza DataSource una sola volta
    useEffect(() => {
        if (!viewer) return;

        const ds = new CustomDataSource('flights_data');
        viewer.dataSources.add(ds);
        dataSourceRef.current = ds;

        return () => {
            if (viewer && !viewer.isDestroyed() && dataSourceRef.current) {
                viewer.dataSources.remove(dataSourceRef.current);
            }
        };
    }, [viewer]);

    // Update Entities bypassando totalmente il JSX Array reconciliation di React
    useEffect(() => {
        if (!flights || !dataSourceRef.current) return;

        const ds = dataSourceRef.current;
        const mToDeg = 1 / 111111;

        // Rimuove entità vecchie non più presenti nel payload
        const currentIds = new Set(flights.map((f, i) => f.icao24 || `flight_${i}`));
        for (let i = ds.entities.values.length - 1; i >= 0; i--) {
            const entity = ds.entities.values[i];
            if (!currentIds.has(entity.id)) {
                ds.entities.remove(entity);
            }
        }

        // Aggiunge o aggiorna voli esistenti
        flights.forEach((flight, index) => {
            const id = flight.icao24 || `flight_${index}`;
            const alt = flight.altitude || 10000;
            const desc = `Callsign: ${flight.callsign}\nAltitude: ${flight.altitude}m\nVelocity: ${flight.velocity}m/s\nHeading: ${flight.true_track}°`;
            const name = `Flight ${flight.callsign}`;

            let entity = ds.entities.getById(id);

            if (!entity) {
                // Nuova entità: instanziamo CallbackProperty una sola volta alla creazione
                let startTime = null;

                const dynamicPosition = new CallbackProperty((time, result) => {
                    // Questa callback accederà in chiusura ai dati aggiornati tramite
                    // proprietà salvate direttamente sull'entità se stessi mutano
                    const currentFlight = ds.entities.getById(id)?.flightData || flight;

                    if (!startTime) startTime = JulianDate.clone(time);
                    const elapsedSeconds = JulianDate.secondsDifference(time, startTime);

                    if (elapsedSeconds < 0 || elapsedSeconds > 300) {
                        return Cartesian3.fromDegrees(currentFlight.lng, currentFlight.lat, alt, undefined, result);
                    }

                    const distanceMeters = (currentFlight.velocity || 0) * elapsedSeconds;
                    const headingRad = ((currentFlight.true_track || 0) * Math.PI) / 180;
                    const dLat = (distanceMeters * Math.cos(headingRad)) * mToDeg;

                    // Protezione contro divisione per 0 ai Poli Terrestri che restituisce Infinity/NaN (Causa Crash WebGL)
                    const cosLat = Math.max(0.01, Math.abs(Math.cos((currentFlight.lat * Math.PI) / 180)));
                    const dLng = (distanceMeters * Math.sin(headingRad)) * (mToDeg / cosLat);

                    const finalLng = currentFlight.lng + dLng;
                    const finalLat = currentFlight.lat + dLat;

                    if (!isFinite(finalLng) || !isFinite(finalLat)) {
                        return Cartesian3.fromDegrees(currentFlight.lng, currentFlight.lat, alt, undefined, result);
                    }

                    return Cartesian3.fromDegrees(
                        finalLng,
                        finalLat,
                        alt,
                        undefined,
                        result
                    );
                }, false);

                entity = ds.entities.add({
                    id: id,
                    name: name,
                    description: desc,
                    position: dynamicPosition,
                    point: {
                        pixelSize: 12,
                        color: Color.fromCssColorString('#00f0ff')
                    },
                    label: {
                        text: `FLT ${flight.callsign}`,
                        font: "bold 11px Consolas",
                        fillColor: Color.fromCssColorString('#00f0ff'),
                        showBackground: true,
                        backgroundColor: Color.fromCssColorString('#050b14').withAlpha(0.7),
                        pixelOffset: new Cartesian2(0, -20),
                        disableDepthTestDistance: Number.POSITIVE_INFINITY
                    }
                });
            } else {
                // Aggiornamento entità già in mappa: aggiorno solo reference logica per l'estrapolazione e description
                entity.description = desc;
                entity.name = name;
            }

            // Salvo i dati grezzi più recenti nell'entità per permettere l'estrapolazione aggiornata
            entity.flightData = flight;
        });

    }, [flights]);

    return null; // Nessun rendering React. Cesium lavora "sotto coperta"
}
