import React, { useEffect, useRef } from 'react';
import { useCesium } from 'resium';
import { CustomDataSource, Cartesian3, Cartesian2, Color } from 'cesium';
import { useCelesTrak } from '../../api/celestrak';

export function SatelliteLayer() {
    const { data: satellites } = useCelesTrak(true);
    const { viewer } = useCesium();
    const dataSourceRef = useRef(null);

    // Inizializza DataSource una sola volta
    useEffect(() => {
        if (!viewer) return;

        const ds = new CustomDataSource('satellites_data');
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
        if (!satellites || !dataSourceRef.current) return;

        const ds = dataSourceRef.current;

        // Rimuove entità vecchie non più presenti nel payload
        const currentIds = new Set(satellites.map((s, i) => s.id || `sat_${i}`));
        for (let i = ds.entities.values.length - 1; i >= 0; i--) {
            const entity = ds.entities.values[i];
            if (!currentIds.has(entity.id)) {
                ds.entities.remove(entity);
            }
        }

        // Aggiunge o aggiorna satelliti esistenti
        satellites.forEach((sat, index) => {
            const id = sat.id || `sat_${index}`;
            const alt = sat.alt * 1000;
            const desc = `Orbital Data: TLE tracked.\nAlt: ${sat.alt.toFixed(2)} km`;
            const name = sat.name;
            const pos = Cartesian3.fromDegrees(sat.lng, sat.lat, alt);

            let entity = ds.entities.getById(id);

            if (!entity) {
                // Nuova entità statica (il TLE viene rivalutato ma per ora il fetch è netto)
                entity = ds.entities.add({
                    id: id,
                    name: name,
                    description: desc,
                    position: pos,
                    point: {
                        pixelSize: 15,
                        color: Color.fromCssColorString('#00ff41'),
                        disableDepthTestDistance: Number.POSITIVE_INFINITY
                    },
                    label: {
                        text: `SAT ${sat.name}`,
                        font: "bold 10px Consolas",
                        fillColor: Color.fromCssColorString('#00ff41'),
                        showBackground: true,
                        backgroundColor: Color.fromCssColorString('#050b14').withAlpha(0.7),
                        pixelOffset: new Cartesian2(0, -18),
                        disableDepthTestDistance: Number.POSITIVE_INFINITY
                    }
                });
            } else {
                // Aggiornamento entità già in mappa
                entity.position = pos;
                entity.description = desc;
                entity.name = name;
            }
        });

    }, [satellites]);

    return null; // Nessun rendering React. Cesium lavora "sotto coperta"
}
