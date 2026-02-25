import { Entity, PolygonGraphics } from 'resium';
import { Cartesian3, Color } from 'cesium';

export function StreetTrafficLayer() {
    // Un semplice layer visuale di esempio su Austin
    return (
        <Entity name="Street Traffic Data" description="Live Traffic data from OpenStreetMap.">
            <PolygonGraphics
                hierarchy={Cartesian3.fromDegreesArray([
                    -97.75, 30.25,
                    -97.73, 30.25,
                    -97.73, 30.28,
                    -97.75, 30.28
                ])}
                material={Color.fromCssColorString('#ff3333').withAlpha(0.3)}
                outline={true}
                outlineColor={Color.RED}
            />
        </Entity>
    );
}

export function WeatherRadarLayer() {
    // Un semplice layer visuale di esempio meteo globale
    return (
        <Entity name="NOAA Weather Radar" description="Live Doppler Radar overlay (Mock). System Wide Alert.">
            <PolygonGraphics
                hierarchy={Cartesian3.fromDegreesArray([
                    -125.0, 24.0,
                    -65.0, 24.0,
                    -65.0, 50.0,
                    -125.0, 50.0
                ])}
                material={Color.fromCssColorString('#00f0ff').withAlpha(0.15)}
                outline={true}
                outlineColor={Color.fromCssColorString('#00f0ff').withAlpha(0.6)}
                outlineWidth={4}
            />
        </Entity>
    );
}
