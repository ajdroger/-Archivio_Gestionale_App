import { PostProcessStage } from 'cesium';

// Frammenti GLSL per i vari stage visivi (definiti internamente per sicurezza e velocità React)
const CRT_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float scanline = sin(v_textureCoordinates.y * 800.0) * 0.04;
    color.rgb -= scanline;
    out_FragColor = color;
}
`;

const NVG_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float luminance = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    vec3 nvgColor = vec3(0.1, 0.9, 0.2) * luminance * 1.5;
    out_FragColor = vec4(nvgColor, color.a);
}
`;

const FLIR_SHADER = `
uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float lum = dot(color.rgb, vec3(0.3, 0.6, 0.1));
    vec3 thermal = mix(vec3(0.0,0.0,0.5), vec3(1.0,0.0,0.0), lum);
    thermal = mix(thermal, vec3(1.0,1.0,0.0), smoothstep(0.5, 1.0, lum));
    out_FragColor = vec4(thermal, color.a);
}
`;

/**
 * Applica gli Shader PostProcess alla scena Cesium
 * @param {object} viewer - Istanza del Cesium Viewer
 * @param {string} mode - Modalità visuale ('CRT', 'NVG', 'FLIR', ecc.)
 * @param {object} fxSettings - Parametri FX dallo Store Zustand
 */
export function applyPostProcess(viewer, mode, fxSettings) {
    if (!viewer || !viewer.scene) return;

    // Rimuovi vecchi stage
    viewer.scene.postProcessStages.removeAll();

    let shaderSource = null;

    if (mode === 'CRT') shaderSource = CRT_SHADER;
    else if (mode === 'NVG') shaderSource = NVG_SHADER;
    else if (mode === 'FLIR') shaderSource = FLIR_SHADER;

    if (shaderSource) {
        const customStage = new PostProcessStage({
            fragmentShader: shaderSource,
            uniforms: {
                // Uniforms opzionali mappabili da fxSettings in futuro
            }
        });
        viewer.scene.postProcessStages.add(customStage);
    }
}
