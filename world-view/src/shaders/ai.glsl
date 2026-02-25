uniform sampler2D colorTexture;
in vec2 v_textureCoordinates;
out vec4 fragColor;
void main() {
    vec2 uv = v_textureCoordinates;
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
    vec3 dark = vec3(0.02, 0.03, 0.05);
    vec3 wire = vec3(0.0, 0.94, 1.0); 
    float edgeFactor = smoothstep(0.03, 0.1, edge);
    vec3 result = mix(dark, wire, edgeFactor);
    vec4 orig = texture(colorTexture, uv);
    float origLum = dot(orig.rgb, vec3(0.299, 0.587, 0.114));
    result += orig.rgb * 0.08 * origLum;
    fragColor = vec4(result, 1.0);
}
