import { Entity, EllipseGraphics, PointGraphics } from 'resium';
import { Cartesian3, Color } from 'cesium';
import { useUSGS } from '../api/useUSGS';

export function EarthquakeLayer() {
    const { data: earthquakes } = useUSGS(true);

    if (!earthquakes || earthquakes.length === 0) return null;

    return (
        <>
            {earthquakes.map((eq) => {
                const position = Cartesian3.fromDegrees(eq.lng, eq.lat, 0);
                // Raggio proporzionale alla magnitudo: M2.5→15km, M5→100km, M7→500km
                const radius = Math.pow(2, eq.mag) * 100;
                const alpha = Math.min(0.8, eq.mag / 8);

                return (
                    <Entity
                        key={eq.id}
                        name={`M${eq.mag.toFixed(1)} — ${eq.place}`}
                        description={`Magnitude: ${eq.mag}\nDepth: ${eq.depth} km\nTime: ${new Date(eq.time).toISOString()}`}
                        position={position}
                    >
                        <PointGraphics
                            pixelSize={Math.max(6, eq.mag * 3)}
                            color={Color.fromCssColorString('#ff3333').withAlpha(0.9)}
                            outlineColor={Color.fromCssColorString('#ff3333').withAlpha(0.4)}
                            outlineWidth={2}
                        />
                        <EllipseGraphics
                            semiMajorAxis={radius}
                            semiMinorAxis={radius}
                            material={Color.fromCssColorString('#ff3333').withAlpha(alpha * 0.3)}
                            outline={true}
                            outlineColor={Color.fromCssColorString('#ff3333').withAlpha(alpha * 0.6)}
                            outlineWidth={1}
                            height={0}
                        />
                    </Entity>
                );
            })}
        </>
    );
}
