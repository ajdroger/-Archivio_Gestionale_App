uniform sampler2D colorTexture;

in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec2 uv = v_textureCoordinates;
    float rnd = fract(sin(dot(uv * 400.0 + czm_frameNumber * 0.01, vec2(12.9898, 78.233))) * 43758.5453);
    float rnd2 = fract(sin(dot(uv * 200.0 - czm_frameNumber * 0.007, vec2(39.346, 11.135))) * 43758.5453);
    float signal = step(0.3, rnd2);
    vec4 color = texture(colorTexture, uv);
    vec3 noiseColor = vec3(rnd);
    vec3 result = mix(noiseColor, color.rgb, signal * 0.6);
    float bar = step(0.97, fract(uv.y * 30.0 + czm_frameNumber * 0.002));
    result = mix(result, vec3(1.0), bar * 0.3);
    fragColor = vec4(result, 1.0);
}
