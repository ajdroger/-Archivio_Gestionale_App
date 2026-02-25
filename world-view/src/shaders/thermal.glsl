uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float lum = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    // White-hot palette: cold→blue, warm→red/yellow, hot→white
    vec3 cold  = vec3(0.0, 0.0, 0.5);
    vec3 mid   = vec3(1.0, 0.3, 0.0);
    vec3 hot   = vec3(1.0, 1.0, 0.8);
    vec3 result;
    if (lum < 0.5) {
        result = mix(cold, mid, lum * 2.0);
    } else {
        result = mix(mid, hot, (lum - 0.5) * 2.0);
    }
    fragColor = vec4(result, 1.0);
}
