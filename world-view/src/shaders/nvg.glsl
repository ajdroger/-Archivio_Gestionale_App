uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float luminance = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    float noise = fract(sin(dot(v_textureCoordinates * 500.0, vec2(12.9898, 78.233))) * 43758.5453);
    vec3 nvgColor = vec3(0.1, 0.9, 0.2) * (luminance + noise * 0.15);
    fragColor = vec4(nvgColor, 1.0);
}
