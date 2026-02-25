uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec2 uv = v_textureCoordinates;
    vec4 color = texture(colorTexture, uv);
    float lum = dot(color.rgb, vec3(0.299, 0.587, 0.114));
    float q = floor(lum * 4.0 + 0.5) / 4.0;
    vec3 cel = color.rgb * (q / max(lum, 0.001));
    float tx = 1.0 / 1920.0;
    float ty = 1.0 / 1080.0;
    float tl = dot(texture(colorTexture, uv + vec2(-tx, -ty)).rgb, vec3(0.333));
    float t  = dot(texture(colorTexture, uv + vec2( 0., -ty)).rgb, vec3(0.333));
    float tr = dot(texture(colorTexture, uv + vec2( tx, -ty)).rgb, vec3(0.333));
    float ml = dot(texture(colorTexture, uv + vec2(-tx,  0.)).rgb, vec3(0.333));
    float mr = dot(texture(colorTexture, uv + vec2( tx,  0.)).rgb, vec3(0.333));
    float bl = dot(texture(colorTexture, uv + vec2(-tx,  ty)).rgb, vec3(0.333));
    float b  = dot(texture(colorTexture, uv + vec2( 0.,  ty)).rgb, vec3(0.333));
    float br = dot(texture(colorTexture, uv + vec2( tx,  ty)).rgb, vec3(0.333));
    float gx = -tl - 2.0*ml - bl + tr + 2.0*mr + br;
    float gy = -tl - 2.0*t  - tr + bl + 2.0*b  + br;
    float edge = sqrt(gx*gx + gy*gy);
    vec3 outline = mix(cel, vec3(0.0), smoothstep(0.08, 0.15, edge));
    float sat = 1.4;
    float grayVal = dot(outline, vec3(0.299, 0.587, 0.114));
    vec3 saturated = mix(vec3(grayVal), outline, sat);
    fragColor = vec4(saturated, 1.0);
}
