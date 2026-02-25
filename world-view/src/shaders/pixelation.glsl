uniform sampler2D colorTexture;
uniform float pixelSize;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec2 uv = v_textureCoordinates;
    if (pixelSize > 0.001) {
        float ps = mix(1.0, 128.0, pixelSize);
        vec2 d = vec2(ps / 1920.0, ps / 1080.0);
        uv = d * floor(uv / d);
    }
    fragColor = texture(colorTexture, uv);
}
