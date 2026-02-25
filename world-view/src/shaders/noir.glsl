uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float lum = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    float contrast = 2.2;
    lum = clamp((lum - 0.5) * contrast + 0.5, 0.0, 1.0);
    float noise = fract(sin(dot(v_textureCoordinates * 800.0, vec2(12.9898, 78.233))) * 43758.5453);
    lum += (noise - 0.5) * 0.12;
    vec3 bw = vec3(lum * 1.0, lum * 0.95, lum * 0.85);
    fragColor = vec4(bw, 1.0);
}
