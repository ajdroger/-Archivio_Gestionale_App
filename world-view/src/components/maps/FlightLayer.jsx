import { Entity, PointGraphics } from 'resium';
import { Cartesian3, Color } from 'cesium';
import { useOpenSky } from '../../api/opensky';


export function FlightLayer() {
    const { data: flights } = useOpenSky(true);

    if (!flights) return null;

    return (
        <>
            {flights.map((flight, index) => {
                const alt = flight.altitude || 10000;
                const position = Cartesian3.fromDegrees(flight.lng, flight.lat, alt);

                return (
                    <Entity
                        key={flight.icao24 || index.toString()}
                        name={`Flight ${flight.callsign}`}
                        description={`Callsign: ${flight.callsign}\nAltitude: ${flight.altitude}m\nVelocity: ${flight.velocity}m/s`}
                        position={position}
                    >
                        <PointGraphics
                            pixelSize={8}
                            color={Color.fromCssColorString('#ffb000')}
                            outlineColor={Color.BLACK}
                            outlineWidth={2}
                        />
                    </Entity>
                );
            })}
        </>
    );
}
