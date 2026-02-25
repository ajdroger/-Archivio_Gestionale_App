import * as mgrs from 'mgrs';

/**
 * Converte Latitudine e Longitudine in formato MGRS
 * @param lat Latitudine in gradi decimali
 * @param lng Longitudine in gradi decimali
 * @param accuracy Numero di cifre per la precisione (es. 5 = 1m)
 * @returns Stringa MGRS o 'OUT OF BOUNDS'
 */
export function latLonToMGRS(lat, lng, accuracy = 5) {
    if (lat < -80 || lat > 84) {
        return "OUT OF BOUNDS (UPS)";
    }
    try {
        const mgrsString = mgrs.forward([lng, lat], accuracy);
        // Formatta la stringa per leggibilità: es "54S UE 8625 4698" -> "54SUE86254698" poi spaziamo
        // Questo parser base divide in zona, digrafo e coordinate est/nord
        const match = mgrsString.match(/^(\d{1,2}[A-Z])([A-Z]{2})(\d+)$/);
        if (match) {
            const gridZone = match[1];
            const digraph = match[2];
            const coords = match[3];
            const half = coords.length / 2;
            const easting = coords.substring(0, half);
            const northing = coords.substring(half);

            return `${gridZone} ${digraph} ${easting} ${northing}`;
        }
        return mgrsString;
    } catch {
        return "INVALID_COORDS";
    }
}
