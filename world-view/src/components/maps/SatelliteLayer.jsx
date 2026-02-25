import { Entity, PointGraphics } from 'resium';
import { Cartesian3, Color } from 'cesium';
import { useCelesTrak } from '../../api/celestrak';


export function SatelliteLayer() {
    const { data: satellites } = useCelesTrak(true);

    if (!satellites) return null;

    return (
        <>
            {satellites.map((sat, index) => {
                const position = Cartesian3.fromDegrees(sat.lng, sat.lat, sat.alt * 1000);
                return (
                    <Entity
                        key={sat.id || index.toString()}
                        name={sat.name}
                        description={`Orbital Data: TLE tracked.\nAlt: ${sat.alt.toFixed(2)} km`}
                        position={position}
                    >
                        <PointGraphics
                            pixelSize={6}
                            color={Color.fromCssColorString('#00ff41')}
                            outlineColor={Color.BLACK}
                            outlineWidth={1}
                        />
                    </Entity>
                );
            })}
        </>
    );
}
