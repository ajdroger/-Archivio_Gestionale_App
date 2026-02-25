import { PostProcessStage } from 'cesium';

import NVG_SHADER from '../shaders/nvg.glsl?raw';
import FLIR_SHADER from '../shaders/flir.glsl?raw';
import THERMAL_SHADER from '../shaders/thermal.glsl?raw';
import CRT_SHADER from '../shaders/crt.glsl?raw';
import ANIME_SHADER from '../shaders/anime.glsl?raw';
import NOIR_SHADER from '../shaders/noir.glsl?raw';
import SNOW_SHADER from '../shaders/snow.glsl?raw';
import AI_SHADER from '../shaders/ai.glsl?raw';
import PIXELATION_SHADER from '../shaders/pixelation.glsl?raw';

const SHADER_MAP = {
    'CRT': CRT_SHADER,
    'NVG': NVG_SHADER,
    'FLIR': FLIR_SHADER,
    'THERMAL': THERMAL_SHADER,
    'ANIME': ANIME_SHADER,
    'NOIR': NOIR_SHADER,
    'SNOW': SNOW_SHADER,
    'AI': AI_SHADER,
};

/**
 * Applica gli Shader PostProcess alla scena Cesium In Modo Vanilla (Prevenendo React Lifecycle Bugs)
 * @param {object} viewer - Istanza del Cesium Viewer
 * @param {string} mode - Modalità visuale ('CRT', 'NVG', 'FLIR', ecc.)
 * @param {object} fxSettings - Parametri FX dallo Store Zustand
 */
export function applyPostProcess(viewer, mode, fxSettings) {
    if (!viewer || !viewer.scene || !viewer.scene.postProcessStages) return;

    // Rimuovi vecchi stage
    viewer.scene.postProcessStages.removeAll();

    // 1. Applica il filtro della Visual Mode selezionata (se presente e non null)
    if (mode && SHADER_MAP[mode]) {
        const customStage = new PostProcessStage({
            fragmentShader: SHADER_MAP[mode],
            uniforms: {
                noiseIntensity: fxSettings.noise || 0.5,
                bloomFactor: fxSettings.bloom || 0.5,
                distortionAmount: fxSettings.distortion || 0.1,
                thermalIntensity: fxSettings.bloom || 0.5
            }
        });
        viewer.scene.postProcessStages.add(customStage);
    }

    // 2. Applica layer di Pixelation (se attivo)
    if (fxSettings.pixelation > 0.01) {
        const pixelStage = new PostProcessStage({
            fragmentShader: PIXELATION_SHADER,
            uniforms: {
                pixelSize: fxSettings.pixelation
            }
        });
        viewer.scene.postProcessStages.add(pixelStage);
    }
}
