import { Ion } from 'cesium';
import { CESIUM_TOKEN } from '../api/config';

let isInitialized = false;

/**
 * Inizializza l'Engine Cesium globale (Setta il token Ion in modo sicuro centralizzato)
 */
export function initializeEngine() {
    if (isInitialized) return;

    try {
        Ion.defaultAccessToken = CESIUM_TOKEN;
        isInitialized = true;
        console.log('[WORLDVIEW] Cesium Engine Initialized.');
    } catch (error) {
        console.error('[WORLDVIEW] Failed to initialize Cesium Engine:', error);
    }
}
