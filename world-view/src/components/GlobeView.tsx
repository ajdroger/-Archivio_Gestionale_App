import { useEffect, useRef, useState, useMemo } from 'react';
import Globe from 'react-globe.gl';
import * as THREE from 'three';

import { useUSGS } from '../hooks/useUSGS';
import { useOpenSky } from '../hooks/useOpenSky';
import { useCelesTrak } from '../hooks/useCelesTrak';

export interface GlobeViewProps {
    layers: {
        earthquakes: boolean;
        flights: boolean;
        satellites: boolean;
        cctv: boolean;
    };
    visualMode: 'NORMAL' | 'CRT' | 'NVG' | 'FLIR' | 'THERMAL';
}

export default function GlobeView({ layers, visualMode }: GlobeViewProps) {
    const globeRef = useRef<any>(null);
    const [dimensions, setDimensions] = useState({ width: window.innerWidth, height: window.innerHeight });

    // Colore atmosfera basato sul visualMode
    const atmosphereColor = useMemo(() => {
        switch (visualMode) {
            case 'NVG': return '#00ff41';
            case 'FLIR': return '#ffffff';
            case 'THERMAL': return '#ff3333';
            default: return '#0077ff';
        }
    }, [visualMode]);

    // Materiale del globo personalizzato
    const globeMaterial = useMemo(() => {
        const material = new THREE.MeshPhongMaterial();
        material.bumpScale = 10;

        // Tinta base scura
        material.color = new THREE.Color('#050b14');

        if (visualMode === 'NVG') {
            material.color = new THREE.Color('#002200');
            material.emissive = new THREE.Color('#001100');
        } else if (visualMode === 'FLIR') {
            material.color = new THREE.Color('#111111');
            material.emissive = new THREE.Color('#333333');
        } else {
            material.emissive = new THREE.Color('#020813');
        }

        material.emissiveIntensity = 0.5;
        material.shininess = 0.2;
        return material;
    }, [visualMode]);

    // Hooks per dati live
    const { data: earthquakes } = useUSGS(layers.earthquakes);
    const { data: flights } = useOpenSky(layers.flights);
    const { data: satellites } = useCelesTrak(layers.satellites);

    useEffect(() => {
        const handleResize = () => setDimensions({ width: window.innerWidth, height: window.innerHeight });
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    useEffect(() => {
        if (globeRef.current) {
            // Point camera to Europe/Italy coordinates
            globeRef.current.pointOfView({ lat: 41.8719, lng: 12.5674, altitude: 2 }, 2000);

            const controls = globeRef.current.controls();
            if (controls) {
                controls.autoRotate = true;
                controls.autoRotateSpeed = 0.2;
            }
        }
    }, []);

    return (
        <div className="absolute top-0 left-0 w-full h-full" style={{ zIndex: 0 }}>
            {/* @ts-ignore */}
            <Globe
                ref={globeRef}
                width={dimensions.width}
                height={dimensions.height}
                globeImageUrl="//unpkg.com/three-globe/example/img/earth-water.png"
                bumpImageUrl="//unpkg.com/three-globe/example/img/earth-topology.png"
                backgroundImageUrl={visualMode === 'NORMAL' ? "//unpkg.com/three-globe/example/img/night-sky.png" : ""}
                backgroundColor={visualMode === 'NORMAL' ? "#050b14" : "#000000"}
                globeMaterial={globeMaterial}

                // --- EARTHQUAKES (Rings/Pulse Effect) ---
                ringsData={earthquakes}
                ringLat="lat"
                ringLng="lng"
                ringColor={() => '#ff3333'}
                ringMaxRadius={(d: any) => d.mag * 2}
                ringPropagationSpeed={3}
                ringRepeatPeriod={1500}

                // --- FLIGHTS ADS-B (Custom Extruded Cones) ---
                objectsData={flights}
                objectLat="lat"
                objectLng="lng"
                objectAltitude={0.05}
                objectLabel="callsign"
                objectThreeObject={() => {
                    const geometry = new THREE.ConeGeometry(0.2, 0.8, 4);
                    geometry.rotateX(Math.PI / 2); // allinea 3d
                    const color = visualMode === 'NVG' ? '#00ff41' : '#00ffff';
                    const material = new THREE.MeshBasicMaterial({ color, wireframe: visualMode === 'CRT' });
                    return new THREE.Mesh(geometry, material);
                }}

                // --- SATELLITES CELESTRAK (Labels & Dots) ---
                labelsData={satellites}
                labelLat="lat"
                labelLng="lng"
                labelAltitude={(d: any) => d.alt / 10000} // Normalizza altitudine orbitale
                labelText="name"
                labelSize={0.5}
                labelDotRadius={0.3}
                labelColor={() => visualMode === 'THERMAL' ? '#ffaa00' : '#00ff41'}
                labelResolution={2}

                atmosphereColor={atmosphereColor}
                atmosphereAltitude={0.15}
            />
        </div>
    );
}
