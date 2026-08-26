import { NeatGradient } from "https://esm.sh/@firecms/neat@1.0.10";

const config = {
    colors: [
        { color: '#FFBF00', enabled: true },
        { color: '#FF9700', enabled: true },
        { color: '#FFE400', enabled: true },
        { color: '#FFAE00', enabled: true },
        { color: '#FFD100', enabled: true },
        { color: '#FF9A9E', enabled: false },
    ],
    speed: 2.5,
    horizontalPressure: 3,
    verticalPressure: 4,
    waveFrequencyX: 2,
    waveFrequencyY: 3,
    waveAmplitude: 5,
    secondaryWaveEnabled: false,
    secondaryWaveFrequencyX: 3,
    secondaryWaveFrequencyY: 3,
    secondaryWaveAmplitude: 5,
    secondaryWaveSpeed: 0.6,
    secondaryWaveAngle: 1,
    shadows: 1,
    highlights: 5,
    colorBrightness: 1,
    colorSaturation: 7,
    wireframe: false,
    antialias: false,
    colorBlending: 8,
    backgroundColor: '#003FFF',
    backgroundAlpha: 1,
    grainScale: 0,
    grainSparsity: 0,
    grainIntensity: 0,
    grainSpeed: 1,
    resolution: 1,
    yOffset: 0,
    yOffsetWaveMultiplier: 4,
    yOffsetColorMultiplier: 4,
    yOffsetFlowMultiplier: 4,
    flowDistortionA: 0,
    flowDistortionB: 0,
    flowScale: 1,
    flowEase: 0,
    flowEnabled: true,
    enableProceduralTexture: false,
    transparentTextureVoid: false,
    textureMode: 'bitmap',
    bakeEdgeSoftness: 1,
    textureVoidLikelihood: 0.45,
    textureVoidWidthMin: 200,
    textureVoidWidthMax: 486,
    textureBandDensity: 2.15,
    textureColorBlending: 0.01,
    textureSeed: 333,
    textureEase: 0.5,
    proceduralBackgroundColor: '#000000',
    textureShapeTriangles: 20,
    textureShapeCircles: 15,
    textureShapeBars: 15,
    textureShapeSquiggles: 10,
    domainWarpEnabled: false,
    domainWarpIntensity: 0,
    domainWarpScale: 3,
    vignetteIntensity: 0,
    vignetteRadius: 0.8,
    fresnelEnabled: false,
    fresnelPower: 2,
    fresnelIntensity: 0.5,
    fresnelColor: '#FFFFFF',
    iridescenceEnabled: false,
    iridescenceIntensity: 0.5,
    iridescenceSpeed: 1,
    prismEdgeEnabled: false,
    prismEdgeIntensity: 0.5,
    prismEdgeThinness: 3,
    prismEdgeSpread: 1,
    prismEdgeSpeed: 0.5,
    prismEdgeRipple: 1,
    bloomIntensity: 0,
    bloomThreshold: 0.7,
    chromaticAberration: 0,
    shapeType: 'plane',
    shapeRotationX: 0,
    shapeRotationY: 0,
    shapeRotationZ: 0,
    shapeAutoRotateSpeedX: 0,
    shapeAutoRotateSpeedY: 0,
    sphereRadius: 15,
    torusRadius: 15,
    torusTube: 5,
    cylinderRadius: 10,
    cylinderHeight: 40,
    planeBend: 0,
    planeTwist: 0,
    silhouetteFade: 0.25,
    cylinderFade: 0.08,
    ribbonFade: 0.05,
    flatShading: true,
    cameraLock: true,
    cameraX: 0,
    cameraY: 0,
    cameraZ: 0,
    cameraRotationX: 0,
    cameraRotationY: 0,
    cameraRotationZ: 0,
    cameraZoom: 1,
};

function init() {
    const el = document.getElementById("gradient");
    if (!el) return;
    try {
        const gradient = new NeatGradient({ ref: el, ...config });
        window.addEventListener("scroll", () => {
            gradient.yOffset = window.scrollY * 0.08;
        }, { passive: true });
        // Resize handler - neat handles automatically but ensure visible
        window.addEventListener("resize", () => {
            if (el) el.style.width = '100%';
        });
        window.__tdtGradient = gradient;
    } catch (e) {
        console.error("NeatGradient failed:", e);
        // fallback: solid color if WebGL fails
        el.style.background = config.backgroundColor;
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
} else {
    init();
}
