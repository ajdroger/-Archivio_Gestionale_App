uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec4 color = texture(colorTexture, v_textureCoordinates);
    float luminance = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    float t = luminance * 1.5;
    vec3 thermal = vec3(t, t * 0.4, t * 0.1);
    fragColor = vec4(thermal, 1.0);
}
